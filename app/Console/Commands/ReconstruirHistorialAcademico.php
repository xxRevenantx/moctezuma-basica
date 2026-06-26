<?php

namespace App\Console\Commands;

use App\Models\MovimientoAlumno;
use App\Models\TrayectoriaAcademica;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReconstruirHistorialAcademico extends Command
{
    protected $signature = 'historial:reconstruir
        {--aplicar : Guarda las trayectorias detectadas; sin esta opción solo muestra una simulación}
        {--inscripcion_id= : Limita el análisis a un alumno}
        {--ciclo_escolar_id= : Limita el análisis a un ciclo escolar}
        {--fuente=todas : Fuentes: todas, calificaciones o documentos}';

    protected $description = 'Reconstruye trayectorias faltantes usando evidencia académica sin copiar la ubicación actual a ciclos anteriores.';

    private array $cortes = [];

    public function handle(): int
    {
        if (!$this->estructuraDisponible()) {
            $this->error('Primero ejecuta las migraciones. Faltan columnas del historial académico integral.');

            return self::FAILURE;
        }

        $fuente = mb_strtolower(trim((string) $this->option('fuente')));
        if (!in_array($fuente, ['todas', 'calificaciones', 'documentos'], true)) {
            $this->error('La fuente debe ser: todas, calificaciones o documentos.');

            return self::INVALID;
        }

        $this->cortes = $this->resolverCortes();
        if (!$this->cortes['inicio']) {
            $this->error('No fue posible identificar el corte "Inicio de ciclo" en la tabla ciclos.');

            return self::FAILURE;
        }

        $evidencias = collect();

        if (in_array($fuente, ['todas', 'calificaciones'], true)) {
            $evidencias = $evidencias->merge($this->evidenciasCalificaciones());
        }

        if (in_array($fuente, ['todas', 'documentos'], true)) {
            $evidencias = $evidencias->merge($this->evidenciasDocumentos());
        }

        if ($evidencias->isEmpty()) {
            $this->info('No se encontró evidencia académica utilizable con los filtros indicados.');

            return self::SUCCESS;
        }

        $grupos = $evidencias->groupBy(fn(array $item) => implode(':', [
            $item['inscripcion_id'],
            $item['ciclo_escolar_id'],
            $item['ciclo_id'],
        ]));

        $aplicar = (bool) $this->option('aplicar');
        $usuarioId = $this->usuarioAuditoria();
        $resumen = [
            'detectadas' => 0,
            'creadas' => 0,
            'existentes' => 0,
            'conflictos' => 0,
            'incompletas' => 0,
        ];
        $vista = [];

        foreach ($grupos as $items) {
            /** @var Collection<int, array<string, mixed>> $items */
            $contextos = $items
                ->map(fn(array $item) => $this->normalizarEvidencia($item))
                ->unique(fn(array $item) => implode(':', [
                    $item['nivel_id'],
                    $item['grado_id'],
                    $item['grupo_id'],
                    $item['generacion_id'],
                    $item['semestre_id'] ?? 0,
                ]))
                ->values();

            $base = $items->first();
            $resumen['detectadas']++;

            if ($contextos->count() !== 1) {
                $resumen['conflictos']++;
                $vista[] = $this->filaVista($base, 'CONFLICTO', $items->pluck('fuente')->unique()->implode(', '));
                continue;
            }

            $evidencia = $contextos->first();
            if (!$this->evidenciaCompleta($evidencia)) {
                $resumen['incompletas']++;
                $vista[] = $this->filaVista($evidencia, 'INCOMPLETA', $evidencia['fuente']);
                continue;
            }

            $existe = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $evidencia['inscripcion_id'])
                ->where('ciclo_escolar_id', $evidencia['ciclo_escolar_id'])
                ->where('ciclo_id', $evidencia['ciclo_id'])
                ->exists();

            if ($existe) {
                $resumen['existentes']++;
                $vista[] = $this->filaVista($evidencia, 'YA EXISTE', $evidencia['fuente']);
                continue;
            }

            if ($aplicar) {
                DB::transaction(function () use ($evidencia, $usuarioId): void {
                    $numeroEstancia = ((int) TrayectoriaAcademica::query()
                        ->where('inscripcion_id', $evidencia['inscripcion_id'])
                        ->where('ciclo_escolar_id', $evidencia['ciclo_escolar_id'])
                        ->where('ciclo_id', $evidencia['ciclo_id'])
                        ->lockForUpdate()
                        ->max('numero_estancia')) + 1;

                    $trayectoria = TrayectoriaAcademica::query()->create([
                        'inscripcion_id' => $evidencia['inscripcion_id'],
                        'ciclo_escolar_id' => $evidencia['ciclo_escolar_id'],
                        'ciclo_id' => $evidencia['ciclo_id'],
                        'nivel_id' => $evidencia['nivel_id'],
                        'grado_id' => $evidencia['grado_id'],
                        'generacion_id' => $evidencia['generacion_id'],
                        'grupo_id' => $evidencia['grupo_id'],
                        'semestre_id' => $evidencia['semestre_id'],
                        'activo' => true,
                        'estatus' => 'activo',
                        'fecha_inscripcion' => $evidencia['fecha_inicio'],
                        'fecha_inicio' => $evidencia['fecha_inicio'],
                        'fecha_fin' => $evidencia['fecha_fin'],
                        'numero_estancia' => $numeroEstancia,
                        'vigente_en_corte' => true,
                        'es_actual' => false,
                        'origen' => 'reconstruccion_historica',
                        'datos_reconstruidos' => true,
                        'promovido' => null,
                        'fecha_promocion' => null,
                        'trayectoria_origen_id' => null,
                    ]);

                    MovimientoAlumno::query()->create([
                        'inscripcion_id' => $trayectoria->inscripcion_id,
                        'trayectoria_academica_id' => $trayectoria->id,
                        'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                        'ciclo_id' => $trayectoria->ciclo_id,
                        'trayectoria_origen_id' => null,
                        'tipo' => 'reconstruccion_historica',
                        'fecha' => now()->toDateString(),
                        'motivo' => 'Trayectoria reconstruida a partir de evidencia existente.',
                        'observaciones' => 'Fuentes: ' . $evidencia['fuente'] . '. No se modificó la ubicación académica actual del alumno.',
                        'estado_anterior' => null,
                        'estado_nuevo' => [
                            'trayectoria_id' => $trayectoria->id,
                            'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                            'ciclo_id' => $trayectoria->ciclo_id,
                            'nivel_id' => $trayectoria->nivel_id,
                            'grado_id' => $trayectoria->grado_id,
                            'generacion_id' => $trayectoria->generacion_id,
                            'grupo_id' => $trayectoria->grupo_id,
                            'semestre_id' => $trayectoria->semestre_id,
                            'datos_reconstruidos' => true,
                        ],
                        'registrado_por' => $usuarioId,
                    ]);
                });

                $resumen['creadas']++;
                $vista[] = $this->filaVista($evidencia, 'CREADA', $evidencia['fuente']);
            } else {
                $vista[] = $this->filaVista($evidencia, 'CREARÍA', $evidencia['fuente']);
            }
        }

        $this->table(
            ['Alumno', 'Ciclo', 'Corte', 'Contexto', 'Resultado', 'Evidencia'],
            array_slice($vista, 0, 150)
        );

        if (count($vista) > 150) {
            $this->warn('La vista se limitó a 150 filas. El resumen sí contempla todos los registros.');
        }

        $this->newLine();
        $this->components->twoColumnDetail('Contextos detectados', (string) $resumen['detectadas']);
        $this->components->twoColumnDetail($aplicar ? 'Trayectorias creadas' : 'Trayectorias que se crearían', (string) ($aplicar ? $resumen['creadas'] : collect($vista)->where(4, 'CREARÍA')->count()));
        $this->components->twoColumnDetail('Ya existentes', (string) $resumen['existentes']);
        $this->components->twoColumnDetail('Conflictos omitidos', (string) $resumen['conflictos']);
        $this->components->twoColumnDetail('Evidencias incompletas', (string) $resumen['incompletas']);

        if (!$aplicar) {
            $this->newLine();
            $this->warn('Simulación: no se guardó ningún cambio. Revisa los conflictos y vuelve a ejecutar con --aplicar.');
        }

        return self::SUCCESS;
    }

    private function evidenciasCalificaciones(): Collection
    {
        if (!Schema::hasTable('calificaciones')) {
            return collect();
        }

        $query = DB::table('calificaciones as c')
            ->leftJoin('periodos as p', 'p.id', '=', 'c.periodo_id')
            ->select([
                'c.inscripcion_id',
                'c.ciclo_escolar_id',
                'c.nivel_id',
                'c.grado_id',
                'c.grupo_id',
                'c.generacion_id',
                'c.semestre_id',
                'p.periodo_basica_id',
                'p.parcial_bachillerato_id',
                'p.fecha_inicio',
                'p.fecha_fin',
            ])
            ->selectRaw('MIN(COALESCE(c.fecha_captura, c.created_at)) as fecha_evidencia')
            ->selectRaw('COUNT(*) as evidencias')
            ->whereNotNull('c.inscripcion_id')
            ->whereNotNull('c.ciclo_escolar_id')
            ->groupBy([
                'c.inscripcion_id',
                'c.ciclo_escolar_id',
                'c.nivel_id',
                'c.grado_id',
                'c.grupo_id',
                'c.generacion_id',
                'c.semestre_id',
                'p.periodo_basica_id',
                'p.parcial_bachillerato_id',
                'p.fecha_inicio',
                'p.fecha_fin',
            ]);

        $this->aplicarFiltros($query, 'c');

        return $query->get()->map(function ($fila): array {
            return [
                'inscripcion_id' => (int) $fila->inscripcion_id,
                'ciclo_escolar_id' => (int) $fila->ciclo_escolar_id,
                'ciclo_id' => $this->corteDesdePeriodo(
                    $fila->periodo_basica_id ? (int) $fila->periodo_basica_id : null,
                    $fila->parcial_bachillerato_id ? (int) $fila->parcial_bachillerato_id : null
                ),
                'nivel_id' => (int) $fila->nivel_id,
                'grado_id' => (int) $fila->grado_id,
                'grupo_id' => (int) $fila->grupo_id,
                'generacion_id' => (int) $fila->generacion_id,
                'semestre_id' => $fila->semestre_id ? (int) $fila->semestre_id : null,
                'fecha_inicio' => $this->fechaSegura($fila->fecha_inicio ?: $fila->fecha_evidencia),
                'fecha_fin' => $this->fechaSegura($fila->fecha_fin),
                'fuente' => 'calificaciones (' . (int) $fila->evidencias . ')',
            ];
        });
    }

    private function evidenciasDocumentos(): Collection
    {
        if (!Schema::hasTable('documentos_alumnos') || !Schema::hasTable('tipos_documentos')) {
            return collect();
        }

        $query = DB::table('documentos_alumnos as d')
            ->join('tipos_documentos as td', 'td.id', '=', 'd.tipo_documento_id')
            ->leftJoin('grupos as g', 'g.id', '=', 'd.grupo_id')
            ->select([
                'd.inscripcion_id',
                'd.ciclo_escolar_id',
                'd.nivel_id',
                'd.grado_id',
                'd.grupo_id',
                'g.generacion_id',
                'g.semestre_id',
                'd.fecha_documento',
                'td.slug',
            ])
            ->whereNotNull('d.inscripcion_id')
            ->whereNotNull('d.ciclo_escolar_id')
            ->whereNotNull('d.nivel_id')
            ->whereNotNull('d.grado_id')
            ->whereNotNull('d.grupo_id')
            ->whereNull('d.trayectoria_academica_id')
            ->whereIn('td.slug', [
                'boleta-final-grado',
                'certificado-estudios',
                'certificado-terminacion',
                'constancia-estudios',
                'constancia-baja-traslado',
            ]);

        $this->aplicarFiltros($query, 'd');

        return $query->get()->map(function ($fila): array {
            $esFinal = in_array($fila->slug, ['boleta-final-grado', 'certificado-estudios', 'certificado-terminacion'], true);

            return [
                'inscripcion_id' => (int) $fila->inscripcion_id,
                'ciclo_escolar_id' => (int) $fila->ciclo_escolar_id,
                'ciclo_id' => $esFinal
                    ? ($this->cortes['fin'] ?: $this->cortes['inicio'])
                    : $this->cortes['inicio'],
                'nivel_id' => (int) $fila->nivel_id,
                'grado_id' => (int) $fila->grado_id,
                'grupo_id' => (int) $fila->grupo_id,
                'generacion_id' => $fila->generacion_id ? (int) $fila->generacion_id : 0,
                'semestre_id' => $fila->semestre_id ? (int) $fila->semestre_id : null,
                'fecha_inicio' => $this->fechaSegura($fila->fecha_documento),
                'fecha_fin' => null,
                'fuente' => 'documento ' . $fila->slug,
            ];
        });
    }

    private function aplicarFiltros($query, string $alias): void
    {
        if ($this->option('inscripcion_id')) {
            $query->where("{$alias}.inscripcion_id", (int) $this->option('inscripcion_id'));
        }

        if ($this->option('ciclo_escolar_id')) {
            $query->where("{$alias}.ciclo_escolar_id", (int) $this->option('ciclo_escolar_id'));
        }
    }

    private function resolverCortes(): array
    {
        $ciclos = DB::table('ciclos')->get(['id', 'ciclo']);
        $inicio = $ciclos->first(fn($ciclo) => Str::contains(Str::lower($ciclo->ciclo), ['inicio', 'primer']))?->id;
        $medio = $ciclos->first(fn($ciclo) => Str::contains(Str::lower($ciclo->ciclo), ['medio', 'segundo']))?->id;
        $fin = $ciclos->first(fn($ciclo) => Str::contains(Str::lower($ciclo->ciclo), ['fin', 'tercer']))?->id;

        return [
            'inicio' => $inicio ? (int) $inicio : ($ciclos->first()?->id ? (int) $ciclos->first()->id : null),
            'medio' => $medio ? (int) $medio : null,
            'fin' => $fin ? (int) $fin : ($ciclos->last()?->id ? (int) $ciclos->last()->id : null),
        ];
    }

    private function corteDesdePeriodo(?int $periodoBasicaId, ?int $parcialBachilleratoId): int
    {
        if ($periodoBasicaId === 2) {
            return $this->cortes['medio'] ?: $this->cortes['inicio'];
        }

        if ($periodoBasicaId && $periodoBasicaId >= 3) {
            return $this->cortes['fin'] ?: $this->cortes['inicio'];
        }

        if ($parcialBachilleratoId && $parcialBachilleratoId >= 2) {
            return $this->cortes['fin'] ?: $this->cortes['inicio'];
        }

        return $this->cortes['inicio'];
    }

    private function normalizarEvidencia(array $item): array
    {
        $item['fuente'] = collect(explode('|', (string) $item['fuente']))
            ->map(fn(string $fuente) => trim($fuente))
            ->filter()
            ->unique()
            ->implode(', ');

        return $item;
    }

    private function evidenciaCompleta(array $evidencia): bool
    {
        foreach (['inscripcion_id', 'ciclo_escolar_id', 'ciclo_id', 'nivel_id', 'grado_id', 'grupo_id', 'generacion_id'] as $campo) {
            if (empty($evidencia[$campo])) {
                return false;
            }
        }

        return true;
    }

    private function filaVista(array $evidencia, string $resultado, string $fuente): array
    {
        $alumno = DB::table('inscripciones')->where('id', $evidencia['inscripcion_id'] ?? 0)->first([
            'matricula',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
        ]);
        $ciclo = DB::table('ciclo_escolares')->where('id', $evidencia['ciclo_escolar_id'] ?? 0)->first(['inicio_anio', 'fin_anio']);
        $corte = DB::table('ciclos')->where('id', $evidencia['ciclo_id'] ?? 0)->value('ciclo') ?: '—';
        $nivel = DB::table('niveles')->where('id', $evidencia['nivel_id'] ?? 0)->value('nombre') ?: '—';
        $grado = DB::table('grados')->where('id', $evidencia['grado_id'] ?? 0)->value('nombre') ?: '—';
        $grupo = DB::table('grupos as g')
            ->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'g.asignacion_grupo_id')
            ->where('g.id', $evidencia['grupo_id'] ?? 0)
            ->value('ag.nombre') ?: '—';

        $nombre = $alumno
            ? trim(($alumno->matricula ?: '') . ' · ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno . ' ' . $alumno->nombre)
            : 'Alumno #' . ($evidencia['inscripcion_id'] ?? '—');

        return [
            $nombre,
            $ciclo ? "{$ciclo->inicio_anio}-{$ciclo->fin_anio}" : '—',
            $corte,
            "{$nivel} · {$grado} · Grupo {$grupo}",
            $resultado,
            $fuente,
        ];
    }

    private function fechaSegura($fecha): ?Carbon
    {
        if (!$fecha) {
            return null;
        }

        try {
            return Carbon::parse($fecha);
        } catch (\Throwable) {
            return null;
        }
    }

    private function usuarioAuditoria(): int
    {
        $id = User::query()->where('is_admin', true)->value('id') ?: User::query()->value('id');

        if (!$id) {
            throw new \RuntimeException('No existe un usuario para registrar la reconstrucción.');
        }

        return (int) $id;
    }

    private function estructuraDisponible(): bool
    {
        return Schema::hasTable('trayectorias_academicas')
            && Schema::hasTable('movimientos_alumnos')
            && Schema::hasColumn('trayectorias_academicas', 'numero_estancia')
            && Schema::hasColumn('trayectorias_academicas', 'datos_reconstruidos')
            && Schema::hasColumn('movimientos_alumnos', 'ciclo_escolar_id');
    }
}
