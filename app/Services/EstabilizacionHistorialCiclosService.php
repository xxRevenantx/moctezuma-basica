<?php

namespace App\Services;

use App\Enums\EstadoInscripcionCiclo;
use App\Enums\EstatusAlumnoCiclo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EstabilizacionHistorialCiclosService
{
    private const TABLAS_ACADEMICAS = [
        'calificaciones',
        'ficha_descriptivas',
        'calificaciones_campos_formativos',
        'asistencias_finales_bachillerato',
        'decisiones_promocion_oficial',
        'lugares_preescolar',
    ];

    public function diagnosticar(?int $inscripcionId = null): array
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            return [
                'disponible' => false,
                'mensaje' => 'La tabla inscripcion_ciclos todavía no existe.',
            ];
        }

        $historiales = DB::table('inscripcion_ciclos')
            ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId));

        $sinHistorialActual = 0;
        if (Schema::hasTable('inscripciones') && Schema::hasColumn('inscripciones', 'ciclo_escolar_id')) {
            $sinHistorialActual = DB::table('inscripciones as i')
                ->leftJoin('inscripcion_ciclos as ic', function ($join): void {
                    $join->on('ic.inscripcion_id', '=', 'i.id')
                        ->on('ic.ciclo_escolar_id', '=', 'i.ciclo_escolar_id');
                })
                ->when($inscripcionId, fn (Builder $query) => $query->where('i.id', $inscripcionId))
                ->whereNull('i.deleted_at')
                ->whereNotNull('i.ciclo_escolar_id')
                ->where('i.estatus', '!=', EstatusAlumnoCiclo::PREINSCRITO->value)
                ->whereNull('ic.id')
                ->count();
        }

        $multiplesEnCurso = DB::query()
            ->fromSub(
                DB::table('inscripcion_ciclos')
                    ->select('inscripcion_id', DB::raw('COUNT(*) as total'))
                    ->where('estado', EstadoInscripcionCiclo::EN_CURSO->value)
                    ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
                    ->groupBy('inscripcion_id')
                    ->having('total', '>', 1),
                'duplicados'
            )
            ->count();

        $asignaciones = [
            'cerradas_marcadas_actuales' => 0,
            'en_curso_sin_asignacion_actual' => 0,
            'multiples_asignaciones_actuales' => 0,
        ];

        if (Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            $asignaciones['cerradas_marcadas_actuales'] = DB::table('inscripcion_ciclo_asignaciones as a')
                ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'a.inscripcion_ciclo_id')
                ->when($inscripcionId, fn (Builder $query) => $query->where('ic.inscripcion_id', $inscripcionId))
                ->where('ic.estado', '!=', EstadoInscripcionCiclo::EN_CURSO->value)
                ->where('a.es_actual', true)
                ->count();

            $asignaciones['en_curso_sin_asignacion_actual'] = DB::table('inscripcion_ciclos as ic')
                ->leftJoin('inscripcion_ciclo_asignaciones as a', function ($join): void {
                    $join->on('a.inscripcion_ciclo_id', '=', 'ic.id')
                        ->where('a.es_actual', true);
                })
                ->when($inscripcionId, fn (Builder $query) => $query->where('ic.inscripcion_id', $inscripcionId))
                ->where('ic.estado', EstadoInscripcionCiclo::EN_CURSO->value)
                ->whereNull('a.id')
                ->count();

            $asignaciones['multiples_asignaciones_actuales'] = DB::query()
                ->fromSub(
                    DB::table('inscripcion_ciclo_asignaciones as a')
                        ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'a.inscripcion_ciclo_id')
                        ->select('a.inscripcion_ciclo_id', DB::raw('COUNT(*) as total'))
                        ->when($inscripcionId, fn (Builder $query) => $query->where('ic.inscripcion_id', $inscripcionId))
                        ->where('a.es_actual', true)
                        ->groupBy('a.inscripcion_ciclo_id')
                        ->having('total', '>', 1),
                    'duplicadas'
                )
                ->count();
        }

        $vinculos = [];
        foreach (array_merge(self::TABLAS_ACADEMICAS, ['movimientos_alumnos', 'cambios_academicos']) as $tabla) {
            if (! $this->tablaVinculable($tabla)) {
                continue;
            }

            $vinculos[$tabla] = [
                'sin_vinculo' => DB::table($tabla)
                    ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
                    ->whereNotNull('inscripcion_id')
                    ->whereNull('inscripcion_ciclo_id')
                    ->count(),
                'vinculo_incorrecto' => $this->contarVinculosIncorrectos($tabla, $inscripcionId),
            ];
        }

        return [
            'disponible' => true,
            'historiales' => [
                'total' => (clone $historiales)->count(),
                'estado_no_estandar' => (clone $historiales)
                    ->whereNotIn('estado', [
                        EstadoInscripcionCiclo::EN_CURSO->value,
                        EstadoInscripcionCiclo::CERRADO->value,
                    ])->count(),
                'multiples_en_curso_por_alumno' => $multiplesEnCurso,
                'inscripciones_actuales_sin_historial' => $sinHistorialActual,
                'ingreso_incompatible_con_ciclo_activo' => (clone $historiales)
                    ->where('estado', EstadoInscripcionCiclo::EN_CURSO->value)
                    ->whereIn('estatus_actual_ciclo', [
                        EstatusAlumnoCiclo::ACTIVO->value,
                        EstatusAlumnoCiclo::REINGRESO->value,
                        EstatusAlumnoCiclo::NO_PROMOVIDO->value,
                    ])
                    ->whereIn('estatus_ingreso', [
                        EstatusAlumnoCiclo::EGRESADO->value,
                        EstatusAlumnoCiclo::PENDIENTE_REINSCRIPCION->value,
                        EstatusAlumnoCiclo::NO_REINSCRITO->value,
                        EstatusAlumnoCiclo::BAJA_DEFINITIVA->value,
                        EstatusAlumnoCiclo::TRASLADADO->value,
                    ])
                    ->count(),
            ],
            'asignaciones' => $asignaciones,
            'vinculos' => $vinculos,
        ];
    }

    public function reparar(?int $inscripcionId = null): array
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            return ['disponible' => false, 'mensaje' => 'La tabla inscripcion_ciclos todavía no existe.'];
        }

        return DB::transaction(function () use ($inscripcionId): array {
            $resultado = [
                'disponible' => true,
                'estados_normalizados' => 0,
                'estatus_ingreso_normalizados' => 0,
                'historiales_actuales_creados' => 0,
                'asignaciones_corregidas' => 0,
                'vinculos_academicos' => 0,
                'movimientos_vinculados' => 0,
                'cambios_vinculados' => 0,
                'omitidos_por_ambiguedad' => 0,
            ];

            $resultado['estados_normalizados'] = DB::table('inscripcion_ciclos')
                ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
                ->whereIn('estado', ['finalizado', 'finalizada', 'concluido', 'concluida'])
                ->update([
                    'estado' => EstadoInscripcionCiclo::CERRADO->value,
                    'updated_at' => now(),
                ]);

            $resultado['estatus_ingreso_normalizados'] = DB::table('inscripcion_ciclos')
                ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
                ->where('estado', EstadoInscripcionCiclo::EN_CURSO->value)
                ->whereIn('estatus_actual_ciclo', [
                    EstatusAlumnoCiclo::ACTIVO->value,
                    EstatusAlumnoCiclo::REINGRESO->value,
                    EstatusAlumnoCiclo::NO_PROMOVIDO->value,
                ])
                ->whereIn('origen', [
                    'promocion_nivel',
                    'promocion_o_continuidad',
                    'registro_academico_vinculado',
                ])
                ->whereIn('estatus_ingreso', [
                    EstatusAlumnoCiclo::EGRESADO->value,
                    EstatusAlumnoCiclo::PENDIENTE_REINSCRIPCION->value,
                    EstatusAlumnoCiclo::NO_REINSCRITO->value,
                    EstatusAlumnoCiclo::BAJA_DEFINITIVA->value,
                    EstatusAlumnoCiclo::TRASLADADO->value,
                ])
                ->update([
                    'estatus_ingreso' => EstatusAlumnoCiclo::ACTIVO->value,
                    'updated_at' => now(),
                ]);

            $resultado['historiales_actuales_creados'] = $this->crearHistorialesActualesFaltantes($inscripcionId);
            $resultado['asignaciones_corregidas'] = $this->normalizarAsignaciones($inscripcionId);

            foreach (self::TABLAS_ACADEMICAS as $tabla) {
                $resultado['vinculos_academicos'] += $this->vincularPorCicloExplicito($tabla, $inscripcionId);
            }

            [$vinculados, $omitidos] = $this->vincularMovimientos($inscripcionId);
            $resultado['movimientos_vinculados'] = $vinculados;
            $resultado['omitidos_por_ambiguedad'] += $omitidos;

            [$vinculados, $omitidos] = $this->vincularCambiosAcademicos($inscripcionId);
            $resultado['cambios_vinculados'] = $vinculados;
            $resultado['omitidos_por_ambiguedad'] += $omitidos;

            return $resultado;
        });
    }

    private function crearHistorialesActualesFaltantes(?int $inscripcionId): int
    {
        if (! Schema::hasTable('inscripciones')) {
            return 0;
        }

        $creados = 0;
        $servicio = app(HistorialCicloEscolarService::class);

        Inscripcion::withTrashed()
            ->when($inscripcionId, fn ($query) => $query->whereKey($inscripcionId))
            ->whereNotNull('ciclo_escolar_id')
            ->where('estatus', '!=', EstatusAlumnoCiclo::PREINSCRITO->value)
            ->whereDoesntHave('ciclosEscolaresHistorial', function ($query): void {
                $query->whereColumn('inscripcion_ciclos.ciclo_escolar_id', 'inscripciones.ciclo_escolar_id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($alumnos) use (&$creados, $servicio): void {
                foreach ($alumnos as $alumno) {
                    try {
                        $servicio->asegurarCicloFormal(
                            $alumno,
                            'estabilizacion_automatica',
                            null,
                            $alumno->fecha_inscripcion?->toDateString()
                        );
                        $creados++;
                    } catch (Throwable) {
                        // El diagnóstico posterior conservará los casos incompletos para revisión manual.
                    }
                }
            });

        return $creados;
    }

    private function normalizarAsignaciones(?int $inscripcionId): int
    {
        if (! Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            return 0;
        }

        $corregidas = 0;

        $cerrados = DB::table('inscripcion_ciclos')
            ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
            ->where('estado', EstadoInscripcionCiclo::CERRADO->value)
            ->get(['id', 'fecha_salida']);

        foreach ($cerrados as $historial) {
            $corregidas += DB::table('inscripcion_ciclo_asignaciones')
                ->where('inscripcion_ciclo_id', $historial->id)
                ->where(function (Builder $query): void {
                    $query->where('es_actual', true)->orWhereNull('fecha_fin');
                })
                ->update([
                    'es_actual' => false,
                    'fecha_fin' => $historial->fecha_salida ?: DB::raw('fecha_fin'),
                    'updated_at' => now(),
                ]);
        }

        $enCurso = DB::table('inscripcion_ciclos')
            ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
            ->where('estado', EstadoInscripcionCiclo::EN_CURSO->value)
            ->pluck('id');

        foreach ($enCurso as $historialId) {
            $actuales = DB::table('inscripcion_ciclo_asignaciones')
                ->where('inscripcion_ciclo_id', $historialId)
                ->where('es_actual', true)
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->pluck('id');

            $mantenerId = $actuales->first();
            if (! $mantenerId) {
                $mantenerId = DB::table('inscripcion_ciclo_asignaciones')
                    ->where('inscripcion_ciclo_id', $historialId)
                    ->orderByDesc('fecha_inicio')
                    ->orderByDesc('id')
                    ->value('id');
            }

            if (! $mantenerId) {
                continue;
            }

            $corregidas += DB::table('inscripcion_ciclo_asignaciones')
                ->where('inscripcion_ciclo_id', $historialId)
                ->where('id', '!=', $mantenerId)
                ->where('es_actual', true)
                ->update(['es_actual' => false, 'updated_at' => now()]);

            $corregidas += DB::table('inscripcion_ciclo_asignaciones')
                ->where('id', $mantenerId)
                ->where(function (Builder $query): void {
                    $query->where('es_actual', false)->orWhereNotNull('fecha_fin');
                })
                ->update([
                    'es_actual' => true,
                    'fecha_fin' => null,
                    'updated_at' => now(),
                ]);
        }

        return $corregidas;
    }

    private function vincularPorCicloExplicito(string $tabla, ?int $inscripcionId): int
    {
        if (! $this->tablaVinculable($tabla) || ! Schema::hasColumn($tabla, 'ciclo_escolar_id')) {
            return 0;
        }

        $actualizados = 0;
        DB::table($tabla)
            ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
            ->whereNotNull('inscripcion_id')
            ->whereNotNull('ciclo_escolar_id')
            ->whereNull('inscripcion_ciclo_id')
            ->orderBy('id')
            ->chunkById(300, function ($filas) use ($tabla, &$actualizados): void {
                foreach ($filas as $fila) {
                    $historialId = DB::table('inscripcion_ciclos')
                        ->where('inscripcion_id', $fila->inscripcion_id)
                        ->where('ciclo_escolar_id', $fila->ciclo_escolar_id)
                        ->value('id');

                    if (! $historialId) {
                        continue;
                    }

                    $actualizados += DB::table($tabla)
                        ->where('id', $fila->id)
                        ->whereNull('inscripcion_ciclo_id')
                        ->update(['inscripcion_ciclo_id' => $historialId]);
                }
            });

        return $actualizados;
    }

    private function vincularMovimientos(?int $inscripcionId): array
    {
        if (! $this->tablaVinculable('movimientos_alumnos')) {
            return [0, 0];
        }

        $vinculados = 0;
        $omitidos = 0;

        DB::table('movimientos_alumnos')
            ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
            ->whereNotNull('inscripcion_id')
            ->whereNull('inscripcion_ciclo_id')
            ->orderBy('id')
            ->chunkById(200, function ($filas) use (&$vinculados, &$omitidos): void {
                foreach ($filas as $fila) {
                    $snapshots = [
                        $this->decodificarSnapshot($fila->estado_nuevo ?? null),
                        $this->decodificarSnapshot($fila->estado_anterior ?? null),
                    ];
                    $historial = $this->resolverHistorial(
                        (int) $fila->inscripcion_id,
                        filled($fila->ciclo_escolar_id ?? null) ? (int) $fila->ciclo_escolar_id : null,
                        $snapshots,
                        $fila->fecha ?? $fila->created_at ?? null
                    );

                    if (! $historial) {
                        $omitidos++;
                        continue;
                    }

                    $datos = ['inscripcion_ciclo_id' => $historial->id];
                    if (Schema::hasColumn('movimientos_alumnos', 'ciclo_escolar_id') && blank($fila->ciclo_escolar_id ?? null)) {
                        $datos['ciclo_escolar_id'] = $historial->ciclo_escolar_id;
                    }

                    $vinculados += DB::table('movimientos_alumnos')
                        ->where('id', $fila->id)
                        ->whereNull('inscripcion_ciclo_id')
                        ->update($datos);
                }
            });

        return [$vinculados, $omitidos];
    }

    private function vincularCambiosAcademicos(?int $inscripcionId): array
    {
        if (! $this->tablaVinculable('cambios_academicos')) {
            return [0, 0];
        }

        $vinculados = 0;
        $omitidos = 0;

        DB::table('cambios_academicos')
            ->when($inscripcionId, fn (Builder $query) => $query->where('inscripcion_id', $inscripcionId))
            ->whereNotNull('inscripcion_id')
            ->whereNull('inscripcion_ciclo_id')
            ->orderBy('id')
            ->chunkById(200, function ($filas) use (&$vinculados, &$omitidos): void {
                foreach ($filas as $fila) {
                    $snapshots = [
                        $this->decodificarSnapshot($fila->datos_nuevos ?? null),
                        $this->decodificarSnapshot($fila->datos_anteriores ?? null),
                    ];
                    $historial = $this->resolverHistorial(
                        (int) $fila->inscripcion_id,
                        null,
                        $snapshots,
                        $fila->realizado_at ?? $fila->created_at ?? null
                    );

                    if (! $historial) {
                        $omitidos++;
                        continue;
                    }

                    $vinculados += DB::table('cambios_academicos')
                        ->where('id', $fila->id)
                        ->whereNull('inscripcion_ciclo_id')
                        ->update(['inscripcion_ciclo_id' => $historial->id]);
                }
            });

        return [$vinculados, $omitidos];
    }

    private function resolverHistorial(
        int $inscripcionId,
        ?int $cicloEscolarId,
        array $snapshots,
        mixed $fecha
    ): ?object {
        $snapshots = collect($snapshots)->filter(fn (array $snapshot) => $snapshot !== [])->values();

        $ciclosDetectados = collect([$cicloEscolarId])
            ->merge($snapshots->pluck('ciclo_escolar_id'))
            ->filter(fn ($id) => filled($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($snapshots as $snapshot) {
            $grupoId = (int) ($snapshot['grupo_id'] ?? 0);
            if (! $grupoId || ! Schema::hasTable('grupos')) {
                continue;
            }

            $cicloGrupo = DB::table('grupos')->where('id', $grupoId)->value('ciclo_escolar_id');
            if ($cicloGrupo) {
                $ciclosDetectados->push((int) $cicloGrupo);
            }
        }
        $ciclosDetectados = $ciclosDetectados->unique()->values();

        if ($ciclosDetectados->count() === 1) {
            $historial = DB::table('inscripcion_ciclos')
                ->where('inscripcion_id', $inscripcionId)
                ->where('ciclo_escolar_id', $ciclosDetectados->first())
                ->first();
            if ($historial) {
                return $historial;
            }
        }

        foreach ($snapshots as $snapshot) {
            $query = DB::table('inscripcion_ciclos')->where('inscripcion_id', $inscripcionId);
            $aplicados = 0;

            foreach (['nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id'] as $campo) {
                if (! array_key_exists($campo, $snapshot) || blank($snapshot[$campo])) {
                    continue;
                }
                $query->where($campo, (int) $snapshot[$campo]);
                $aplicados++;
            }

            if ($aplicados >= 2) {
                $coincidencias = $query->get();
                if ($coincidencias->count() === 1) {
                    return $coincidencias->first();
                }
            }
        }

        if (filled($fecha)) {
            try {
                $fecha = CarbonImmutable::parse($fecha)->toDateString();
                $coincidencias = DB::table('inscripcion_ciclos')
                    ->where('inscripcion_id', $inscripcionId)
                    ->whereDate('fecha_ingreso', '<=', $fecha)
                    ->where(function (Builder $query) use ($fecha): void {
                        $query->whereNull('fecha_salida')->orWhereDate('fecha_salida', '>=', $fecha);
                    })
                    ->get();

                if ($coincidencias->count() === 1) {
                    return $coincidencias->first();
                }
            } catch (Throwable) {
                // La fecha no es concluyente; se intenta la última regla segura.
            }
        }

        $historiales = DB::table('inscripcion_ciclos')
            ->where('inscripcion_id', $inscripcionId)
            ->get();

        return $historiales->count() === 1 ? $historiales->first() : null;
    }

    private function decodificarSnapshot(mixed $snapshot): array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        if (is_object($snapshot)) {
            return (array) $snapshot;
        }

        if (! is_string($snapshot) || trim($snapshot) === '') {
            return [];
        }

        $decodificado = json_decode($snapshot, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    private function tablaVinculable(string $tabla): bool
    {
        return Schema::hasTable($tabla)
            && Schema::hasColumn($tabla, 'inscripcion_id')
            && Schema::hasColumn($tabla, 'inscripcion_ciclo_id');
    }

    private function contarVinculosIncorrectos(string $tabla, ?int $inscripcionId): int
    {
        return DB::table($tabla.' as r')
            ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'r.inscripcion_ciclo_id')
            ->when($inscripcionId, fn (Builder $builder) => $builder->where('r.inscripcion_id', $inscripcionId))
            ->where(function (Builder $builder) use ($tabla): void {
                $builder->whereColumn('r.inscripcion_id', '!=', 'ic.inscripcion_id');

                if (Schema::hasColumn($tabla, 'ciclo_escolar_id')) {
                    $builder->orWhere(function (Builder $ciclo): void {
                        $ciclo->whereNotNull('r.ciclo_escolar_id')
                            ->whereColumn('r.ciclo_escolar_id', '!=', 'ic.ciclo_escolar_id');
                    });
                }
            })
            ->count();
    }
}
