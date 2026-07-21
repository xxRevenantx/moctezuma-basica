<?php

namespace App\Services;

use App\Models\CambioAcademico;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MovimientoAlumno;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconstruccionHistorialCiclosService
{
    /**
     * Genera una vista previa sin modificar información.
     * Solo considera snapshots que contienen una ubicación escolar verificable.
     */
    public function diagnostico(?int $inscripcionId = null): array
    {
        $eventos = $this->eventos($inscripcionId);
        $existentes = InscripcionCiclo::query()
            ->when($inscripcionId, fn ($q) => $q->where('inscripcion_id', $inscripcionId))
            ->get(['inscripcion_id', 'ciclo_escolar_id'])
            ->mapWithKeys(fn (InscripcionCiclo $c) => [$c->inscripcion_id.'|'.$c->ciclo_escolar_id => true]);

        $candidatos = $eventos
            ->groupBy(fn (array $evento) => $evento['inscripcion_id'].'|'.$evento['snapshot']['ciclo_escolar_id'])
            ->map(function (Collection $grupoEventos, string $clave) use ($existentes, $eventos): array {
                [$alumnoId, $cicloId] = array_map('intval', explode('|', $clave));
                $ordenados = $grupoEventos->sortBy(fn (array $e) => $e['fecha'].'|'.str_pad((string) $e['orden'], 10, '0', STR_PAD_LEFT))->values();
                $primero = $ordenados->first();
                $ultimo = $ordenados->last();
                $confianzas = $ordenados->pluck('confianza')->unique()->values();
                $aplicable = ! $existentes->has($clave) && ! $confianzas->contains('revision');

                return [
                    'inscripcion_id' => $alumnoId,
                    'alumno' => $primero['alumno'],
                    'ciclo_escolar_id' => $cicloId,
                    'ciclo' => $primero['ciclo'],
                    'fecha_inicio' => $primero['fecha'],
                    'fecha_fin' => $this->fechaFin($ordenados, $eventos),
                    'asignaciones' => $ordenados->count(),
                    'resultado' => $this->resultadoFinal($ordenados, $eventos),
                    'nivel_confianza' => $confianzas->contains('alta') ? 'alta' : ($confianzas->contains('revision') ? 'revision' : 'exacto'),
                    'ya_existe' => $existentes->has($clave),
                    'aplicable' => $aplicable,
                    'origenes' => $ordenados->pluck('origen')->unique()->values()->all(),
                    'primera_ubicacion' => Arr::only($primero['snapshot'], ['nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id']),
                    'ultima_ubicacion' => Arr::only($ultimo['snapshot'], ['nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id']),
                ];
            })
            ->sortBy(fn (array $item) => $item['ciclo'].'|'.$item['alumno'])
            ->values();

        return [
            'total_eventos' => $eventos->count(),
            'total_ciclos_detectados' => $candidatos->count(),
            'ya_existentes' => $candidatos->where('ya_existe', true)->count(),
            'aplicables' => $candidatos->where('aplicable', true)->count(),
            'requieren_revision' => $candidatos->where('nivel_confianza', 'revision')->count(),
            'candidatos' => $candidatos->all(),
        ];
    }

    public function aplicar(?int $inscripcionId, int $usuarioId): array
    {
        return DB::transaction(function () use ($inscripcionId, $usuarioId): array {
            $eventos = $this->eventos($inscripcionId);
            $creados = 0;
            $asignaciones = 0;
            $omitidos = 0;

            foreach ($eventos->groupBy(fn (array $evento) => $evento['inscripcion_id'].'|'.$evento['snapshot']['ciclo_escolar_id']) as $grupoEventos) {
                $ordenados = $grupoEventos
                    ->sortBy(fn (array $e) => $e['fecha'].'|'.str_pad((string) $e['orden'], 10, '0', STR_PAD_LEFT))
                    ->values();
                $primero = $ordenados->first();
                $ultimo = $ordenados->last();

                if ($ordenados->contains(fn (array $e) => $e['confianza'] === 'revision')) {
                    $omitidos++;
                    continue;
                }

                $snapshotInicial = $primero['snapshot'];
                $resultado = $this->resultadoFinal($ordenados, $eventos);
                $fechaFin = $this->fechaFin($ordenados, $eventos);
                $cerrado = $resultado !== null;

                $ciclo = InscripcionCiclo::query()->firstOrCreate(
                    [
                        'inscripcion_id' => $primero['inscripcion_id'],
                        'ciclo_escolar_id' => $snapshotInicial['ciclo_escolar_id'],
                    ],
                    [
                        'matricula' => $snapshotInicial['matricula'] ?? null,
                        'nivel_id' => $snapshotInicial['nivel_id'],
                        'grado_id' => $snapshotInicial['grado_id'],
                        'generacion_id' => $snapshotInicial['generacion_id'],
                        'grupo_id' => $snapshotInicial['grupo_id'],
                        'semestre_id' => $snapshotInicial['semestre_id'] ?? null,
                        'fecha_ingreso' => $primero['fecha'],
                        'fecha_salida' => $fechaFin,
                        'estado' => $cerrado ? 'cerrado' : 'en_curso',
                        'estatus_ingreso' => $snapshotInicial['estatus'] ?? 'activo',
                        'estatus_actual_ciclo' => $ultimo['snapshot']['estatus'] ?? ($cerrado ? $resultado : 'activo'),
                        'resultado_final' => $resultado,
                        'promovido' => in_array($resultado, ['promovido', 'promovido_nivel'], true),
                        'cerrado_at' => $cerrado ? ($fechaFin ? CarbonImmutable::parse($fechaFin)->endOfDay() : now()) : null,
                        'cerrado_por' => $cerrado ? $usuarioId : null,
                        'motivo_cierre' => $cerrado ? 'Reconstrucción a partir de movimientos y cambios académicos existentes.' : null,
                        'snapshot_ingreso' => $snapshotInicial,
                        'snapshot_cierre' => $cerrado ? $ultimo['snapshot'] : null,
                        'origen' => 'reconstruccion_auditada',
                        'reconstruido' => true,
                        'nivel_confianza' => $ordenados->contains(fn (array $e) => $e['confianza'] === 'alta') ? 'alta' : 'exacto',
                    ]
                );

                if (! $ciclo->wasRecentlyCreated) {
                    $omitidos++;
                    continue;
                }

                $creados++;
                $eventosUnicos = $ordenados
                    ->unique(fn (array $e) => implode('|', [
                        $e['fecha'],
                        $e['snapshot']['nivel_id'],
                        $e['snapshot']['grado_id'],
                        $e['snapshot']['generacion_id'],
                        $e['snapshot']['grupo_id'],
                        $e['snapshot']['semestre_id'] ?? 0,
                    ]))
                    ->values();

                foreach ($eventosUnicos as $indice => $evento) {
                    $siguiente = $eventosUnicos->get($indice + 1);
                    $snapshot = $evento['snapshot'];
                    $fin = $siguiente
                        ? CarbonImmutable::parse($siguiente['fecha'])->subDay()->toDateString()
                        : $fechaFin;

                    $ciclo->asignaciones()->create([
                        'nivel_id' => $snapshot['nivel_id'],
                        'grado_id' => $snapshot['grado_id'],
                        'generacion_id' => $snapshot['generacion_id'],
                        'grupo_id' => $snapshot['grupo_id'],
                        'semestre_id' => $snapshot['semestre_id'] ?? null,
                        'fecha_inicio' => $evento['fecha'],
                        'fecha_fin' => $fin,
                        'tipo' => $indice === 0 ? 'asignacion_inicial' : 'reconstruccion_cambio',
                        'motivo' => 'Reconstruida desde '.$evento['origen'].'.',
                        'es_actual' => $indice === $eventosUnicos->count() - 1 && ! $cerrado,
                        'registrado_por' => $usuarioId,
                        'snapshot' => $snapshot,
                    ]);
                    $asignaciones++;
                }

                $this->vincularRegistrosAcademicos($ciclo);
            }

            return compact('creados', 'asignaciones', 'omitidos');
        });
    }


    private function vincularRegistrosAcademicos(InscripcionCiclo $ciclo): void
    {
        foreach ([
            'calificaciones',
            'ficha_descriptivas',
            'calificaciones_campos_formativos',
            'asistencias_finales_bachillerato',
            'decisiones_promocion_oficial',
            'lugares_preescolar',
            'movimientos_alumnos',
        ] as $tabla) {
            if (! Schema::hasTable($tabla)
                || ! Schema::hasColumn($tabla, 'inscripcion_ciclo_id')
                || ! Schema::hasColumn($tabla, 'ciclo_escolar_id')) {
                continue;
            }

            DB::table($tabla)
                ->where('inscripcion_id', $ciclo->inscripcion_id)
                ->where('ciclo_escolar_id', $ciclo->ciclo_escolar_id)
                ->whereNull('inscripcion_ciclo_id')
                ->update(['inscripcion_ciclo_id' => $ciclo->id]);
        }
    }

    private function eventos(?int $inscripcionId): Collection
    {
        $alumnos = Inscripcion::withTrashed()
            ->when($inscripcionId, fn ($q) => $q->whereKey($inscripcionId))
            ->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->keyBy('id');
        $grupos = Grupo::with('cicloEscolar:id,inicio_anio,fin_anio')->get()->keyBy('id');
        $eventos = collect();
        $orden = 0;

        MovimientoAlumno::query()
            ->when($inscripcionId, fn ($q) => $q->where('inscripcion_id', $inscripcionId))
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->each(function (MovimientoAlumno $movimiento) use (&$eventos, &$orden, $alumnos, $grupos): void {
                foreach ([
                    ['snapshot' => $movimiento->estado_anterior, 'sufijo' => 'estado_anterior'],
                    ['snapshot' => $movimiento->estado_nuevo, 'sufijo' => 'estado_nuevo'],
                ] as $dato) {
                    if ($evento = $this->normalizarEvento(
                        (int) $movimiento->inscripcion_id,
                        $dato['snapshot'],
                        (string) ($movimiento->fecha?->toDateString() ?: $movimiento->created_at?->toDateString()),
                        'movimiento:'.$movimiento->tipo.':'.$dato['sufijo'],
                        ++$orden,
                        $alumnos,
                        $grupos,
                        (int) ($movimiento->ciclo_escolar_id ?? 0)
                    )) {
                        $eventos->push($evento);
                    }
                }
            });

        CambioAcademico::query()
            ->when($inscripcionId, fn ($q) => $q->where('inscripcion_id', $inscripcionId))
            ->orderBy('realizado_at')
            ->orderBy('id')
            ->get()
            ->each(function (CambioAcademico $cambio) use (&$eventos, &$orden, $alumnos, $grupos): void {
                foreach ([
                    ['snapshot' => $cambio->datos_anteriores, 'sufijo' => 'datos_anteriores'],
                    ['snapshot' => $cambio->datos_nuevos, 'sufijo' => 'datos_nuevos'],
                ] as $dato) {
                    if ($evento = $this->normalizarEvento(
                        (int) $cambio->inscripcion_id,
                        $dato['snapshot'],
                        (string) ($cambio->realizado_at?->toDateString() ?: $cambio->created_at?->toDateString()),
                        'cambio:'.$cambio->tipo.':'.$dato['sufijo'],
                        ++$orden,
                        $alumnos,
                        $grupos
                    )) {
                        $eventos->push($evento);
                    }
                }
            });

        return $eventos;
    }

    private function normalizarEvento(
        int $inscripcionId,
        mixed $snapshot,
        string $fecha,
        string $origen,
        int $orden,
        Collection $alumnos,
        Collection $grupos,
        int $cicloAlternativo = 0
    ): ?array {
        if (! is_array($snapshot)) return null;
        $grupoId = (int) ($snapshot['grupo_id'] ?? 0);
        $grupo = $grupos->get($grupoId);
        $teniaCicloExplicito = filled($snapshot['ciclo_escolar_id'] ?? null);
        $cicloId = (int) ($snapshot['ciclo_escolar_id'] ?? 0);
        if (! $cicloId) {
            $cicloId = $cicloAlternativo ?: (int) ($grupo?->ciclo_escolar_id ?? 0);
        }
        $requeridos = [
            'nivel_id' => (int) ($snapshot['nivel_id'] ?? $grupo?->nivel_id ?? 0),
            'grado_id' => (int) ($snapshot['grado_id'] ?? $grupo?->grado_id ?? 0),
            'generacion_id' => (int) ($snapshot['generacion_id'] ?? $grupo?->generacion_id ?? 0),
            'grupo_id' => $grupoId,
        ];
        if (! $cicloId || collect($requeridos)->contains(fn ($valor) => ! $valor)) return null;

        $snapshot = array_merge($snapshot, $requeridos, [
            'ciclo_escolar_id' => $cicloId,
            'semestre_id' => $snapshot['semestre_id'] ?? $grupo?->semestre_id,
        ]);
        $alumno = $alumnos->get($inscripcionId);
        $nombre = trim(($alumno?->apellido_paterno ?? '').' '.($alumno?->apellido_materno ?? '').' '.($alumno?->nombre ?? ''));
        $confianza = $teniaCicloExplicito && $grupo && (int) $grupo->ciclo_escolar_id === $cicloId
            ? 'exacto'
            : ($grupo && $grupo->ciclo_escolar_id ? 'alta' : 'revision');

        return [
            'inscripcion_id' => $inscripcionId,
            'alumno' => $nombre ?: 'Alumno #'.$inscripcionId,
            'snapshot' => $snapshot,
            'fecha' => CarbonImmutable::parse($fecha ?: now())->toDateString(),
            'origen' => $origen,
            'orden' => $orden,
            'confianza' => $confianza,
            'ciclo' => $grupo?->cicloEscolar
                ? $grupo->cicloEscolar->inicio_anio.'-'.$grupo->cicloEscolar->fin_anio
                : 'Ciclo #'.$cicloId,
        ];
    }

    private function resultadoFinal(Collection $eventos, ?Collection $todos = null): ?string
    {
        $ultimo = $eventos->last();
        $estatus = (string) ($ultimo['snapshot']['estatus'] ?? '');
        if (in_array($estatus, ['egresado', 'baja_definitiva'], true)) return $estatus;
        if (in_array($estatus, ['traslado', 'trasladado'], true)) return 'trasladado';

        if ($todos) {
            $inscripcionId = (int) $ultimo['inscripcion_id'];
            $cicloId = (int) $ultimo['snapshot']['ciclo_escolar_id'];
            $fechaUltima = (string) $ultimo['fecha'];
            $destino = $todos
                ->filter(fn (array $e) => (int) $e['inscripcion_id'] === $inscripcionId
                    && (int) $e['snapshot']['ciclo_escolar_id'] !== $cicloId
                    && (string) $e['fecha'] >= $fechaUltima)
                ->sortBy(fn (array $e) => $e['fecha'].'|'.$e['orden'])
                ->first();

            if ($destino) {
                return (int) ($destino['snapshot']['nivel_id'] ?? 0) !== (int) ($ultimo['snapshot']['nivel_id'] ?? 0)
                    ? 'promovido_nivel'
                    : 'promovido';
            }
        }

        return $eventos->contains(fn (array $e) => str_contains($e['origen'], 'promocion'))
            ? 'promovido'
            : null;
    }

    private function fechaFin(Collection $eventos, ?Collection $todos = null): ?string
    {
        $resultado = $this->resultadoFinal($eventos, $todos);
        if ($resultado) return $eventos->last()['fecha'];
        return null;
    }
}
