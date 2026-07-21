<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrevisualizarEstabilizacionCiclos extends Command
{
    protected $signature = 'ciclos:previsualizar-estabilizacion {--json : Devuelve el diagnóstico en JSON}';

    protected $description = 'Muestra los registros que serán revisados o reconstruidos durante la estabilización académica por ciclos.';

    public function handle(): int
    {
        $diagnostico = [
            'matriculas_faltantes' => $this->matriculasFaltantes(),
            'asignaciones_ciclo_inconsistente' => $this->asignacionesInconsistentes(),
            'sesiones_compartidas_detectadas' => $this->sesionesCompartidas(),
            'grupo_28' => $this->diagnosticoGrupo28(),
            'generaciones_fin_inconsistente' => $this->generacionesFinInconsistente(),
            'periodos_traslapados' => $this->periodosTraslapados(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('PREVISUALIZACIÓN DE ESTABILIZACIÓN ACADÉMICA');
        $this->line('Este comando es de solo lectura: no modifica la base de datos.');

        $this->seccion(
            'Matrículas activas sin historial vigente',
            $diagnostico['matriculas_faltantes'],
            ['id', 'matricula', 'alumno', 'nivel_id', 'ciclo_escolar_id']
        );

        $this->seccion(
            'Asignaciones de materias con ciclo distinto al grupo',
            $diagnostico['asignaciones_ciclo_inconsistente'],
            ['id', 'grupo_id', 'ciclo_asignacion_id', 'ciclo_grupo_id']
        );

        $this->seccion(
            'Coincidencias de profesor que se reconocerán como sesión compartida',
            $diagnostico['sesiones_compartidas_detectadas'],
            ['profesor_id', 'ciclo_escolar_id', 'dia_id', 'hora_id', 'total']
        );

        $grupo = $diagnostico['grupo_28'];
        $this->newLine();
        $this->comment('Grupo 28 sin ciclo');
        if ($grupo === null) {
            $this->line('No existe el grupo 28.');
        } else {
            $this->table(['id', 'ciclo', 'estado', 'referencias', 'acción propuesta'], [[
                $grupo['id'],
                $grupo['ciclo_escolar_id'] ?? 'Sin ciclo',
                $grupo['estado'] ?? 'Sin estado',
                $grupo['referencias'],
                $grupo['accion_propuesta'],
            ]]);
        }

        $this->seccion(
            'Generaciones cuyo ciclo final no corresponde al año de egreso',
            $diagnostico['generaciones_fin_inconsistente'],
            ['id', 'generacion', 'ciclo_fin', 'anio_egreso']
        );

        $this->seccion(
            'Traslapes de periodos detectados (se advertirán, no se bloquearán)',
            $diagnostico['periodos_traslapados'],
            ['periodo_a', 'periodo_b', 'ciclo_escolar_id', 'nivel_id']
        );

        $this->newLine();
        $this->info('Revisa especialmente las asignaciones 283–288 desde Centro de control > Revisión de ciclos.');

        return self::SUCCESS;
    }

    private function seccion(string $titulo, array $filas, array $columnas): void
    {
        $this->newLine();
        $this->comment($titulo . ': ' . count($filas));
        if ($filas !== []) {
            $this->table($columnas, array_map(
                fn (array $fila): array => array_map(fn (string $columna) => $fila[$columna] ?? '', $columnas),
                $filas
            ));
        }
    }

    private function matriculasFaltantes(): array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('matriculas_alumnos')) {
            return [];
        }

        return DB::table('inscripciones as i')
            ->leftJoin('matriculas_alumnos as ma', function ($join): void {
                $join->on('ma.inscripcion_id', '=', 'i.id')->where('ma.vigente', true);
            })
            ->whereNull('i.deleted_at')
            ->where('i.activo', true)
            ->whereIn('i.estatus', ['activo', 'reingreso', 'no_promovido'])
            ->whereNotNull('i.matricula')
            ->where('i.matricula', '!=', '')
            ->whereNull('ma.id')
            ->orderBy('i.id')
            ->selectRaw("i.id, i.matricula, CONCAT_WS(' ', i.nombre, i.apellido_paterno, i.apellido_materno) as alumno, i.nivel_id, i.ciclo_escolar_id")
            ->get()
            ->map(fn ($fila): array => (array) $fila)
            ->all();
    }

    private function asignacionesInconsistentes(): array
    {
        if (! Schema::hasTable('asignacion_materias') || ! Schema::hasTable('grupos')) {
            return [];
        }

        return DB::table('asignacion_materias as am')
            ->join('grupos as g', 'g.id', '=', 'am.grupo_id')
            ->whereNotNull('am.ciclo_escolar_id')
            ->whereNotNull('g.ciclo_escolar_id')
            ->whereColumn('am.ciclo_escolar_id', '!=', 'g.ciclo_escolar_id')
            ->orderBy('am.id')
            ->get([
                'am.id', 'am.grupo_id',
                'am.ciclo_escolar_id as ciclo_asignacion_id',
                'g.ciclo_escolar_id as ciclo_grupo_id',
            ])
            ->map(fn ($fila): array => (array) $fila)
            ->all();
    }

    private function sesionesCompartidas(): array
    {
        if (! Schema::hasTable('horarios') || ! Schema::hasTable('asignacion_materias')) {
            return [];
        }

        return DB::table('horarios as h')
            ->join('asignacion_materias as am', 'am.id', '=', 'h.asignacion_materia_id')
            ->whereNotNull('am.profesor_id')
            ->select('am.profesor_id', 'h.ciclo_escolar_id', 'h.dia_id', 'h.hora_id', DB::raw('COUNT(*) as total'))
            ->groupBy('am.profesor_id', 'h.ciclo_escolar_id', 'h.dia_id', 'h.hora_id')
            ->having('total', '>', 1)
            ->orderBy('am.profesor_id')
            ->get()
            ->map(fn ($fila): array => (array) $fila)
            ->all();
    }

    private function diagnosticoGrupo28(): ?array
    {
        if (! Schema::hasTable('grupos')) {
            return null;
        }

        $grupo = DB::table('grupos')->where('id', 28)->first();
        if (! $grupo) {
            return null;
        }

        $referencias = 0;
        foreach ([
            ['inscripciones', 'grupo_id'],
            ['asignacion_materias', 'grupo_id'],
            ['horarios', 'grupo_id'],
            ['persona_nivel_detalles', 'grupo_id'],
            ['calificaciones', 'grupo_id'],
        ] as [$tabla, $columna]) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, $columna)) {
                $referencias += DB::table($tabla)->where($columna, 28)->count();
            }
        }

        return [
            'id' => 28,
            'ciclo_escolar_id' => $grupo->ciclo_escolar_id,
            'estado' => $grupo->estado ?? null,
            'referencias' => $referencias,
            'accion_propuesta' => $grupo->ciclo_escolar_id === null && $referencias === 0
                ? 'Archivar sin eliminar; conservar editable desde Revisión de ciclos.'
                : 'Revisión manual.',
        ];
    }

    private function generacionesFinInconsistente(): array
    {
        if (! Schema::hasTable('generaciones') || ! Schema::hasColumn('generaciones', 'ciclo_escolar_fin_id')) {
            return [];
        }

        return DB::table('generaciones as gen')
            ->join('ciclo_escolares as ci', 'ci.id', '=', 'gen.ciclo_escolar_fin_id')
            ->whereColumn('ci.inicio_anio', '<', 'gen.anio_egreso')
            ->orderBy('gen.id')
            ->selectRaw("gen.id, CONCAT(gen.anio_ingreso, '-', gen.anio_egreso) as generacion, CONCAT(ci.inicio_anio, '-', ci.fin_anio) as ciclo_fin, gen.anio_egreso")
            ->get()
            ->map(fn ($fila): array => (array) $fila)
            ->all();
    }

    private function periodosTraslapados(): array
    {
        if (! Schema::hasTable('periodos')) {
            return [];
        }

        return DB::table('periodos as a')
            ->join('periodos as b', function ($join): void {
                $join->on('b.id', '>', 'a.id')
                    ->on('b.ciclo_escolar_id', '=', 'a.ciclo_escolar_id')
                    ->on('b.nivel_id', '=', 'a.nivel_id');
            })
            ->whereNotNull('a.fecha_inicio')
            ->whereNotNull('a.fecha_fin')
            ->whereNotNull('b.fecha_inicio')
            ->whereNotNull('b.fecha_fin')
            ->whereColumn('a.fecha_inicio', '<=', 'b.fecha_fin')
            ->whereColumn('a.fecha_fin', '>=', 'b.fecha_inicio')
            ->orderBy('a.id')
            ->get([
                'a.id as periodo_a', 'b.id as periodo_b',
                'a.ciclo_escolar_id', 'a.nivel_id',
            ])
            ->map(fn ($fila): array => (array) $fila)
            ->all();
    }
}
