<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\InscripcionCicloAsignacion;
use App\Models\TallerSesion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ListaAcademicaService
{
    /**
     * Devuelve la matrícula vigente de una asignación. Las consultas de
     * horarios y talleres conservan el comportamiento actual; los módulos que
     * necesiten reconstruir un ciclo deben solicitar explícitamente el
     * historial con usarHistorialCiclo=true.
     */
    public function alumnosDeAsignacion(
        AsignacionMateria $asignacion,
        CarbonInterface|string|null $fechaCorte = null
    ): Collection {
        return $this->alumnosPorContexto(
            cicloEscolarId: (int) $asignacion->ciclo_escolar_id,
            grupoIds: [(int) $asignacion->grupo_id],
            fechaCorte: $fechaCorte,
            nivelId: $asignacion->nivel_id,
            gradoId: $asignacion->grado_id,
            generacionId: $asignacion->generacion_id,
            semestreId: $asignacion->semestre_id,
        );
    }

    public function alumnosDeTaller(
        TallerSesion $sesion,
        Grupo|int $grupo,
        CarbonInterface|string|null $fechaCorte = null
    ): Collection {
        $grupo = $grupo instanceof Grupo ? $grupo : Grupo::query()->findOrFail($grupo);

        return $this->alumnosPorContexto(
            cicloEscolarId: (int) $sesion->ciclo_escolar_id,
            grupoIds: [(int) $grupo->id],
            fechaCorte: $fechaCorte,
            nivelId: $grupo->nivel_id,
            gradoId: $grupo->grado_id,
            generacionId: $grupo->generacion_id,
            semestreId: $grupo->semestre_id,
        );
    }

    /**
     * @param array<int> $grupoIds
     */
    public function alumnosPorContexto(
        int $cicloEscolarId,
        array $grupoIds,
        CarbonInterface|string|null $fechaCorte = null,
        ?int $nivelId = null,
        ?int $gradoId = null,
        ?int $generacionId = null,
        ?int $semestreId = null,
        bool $usarHistorialCiclo = false,
        bool $incluirNoActivos = false,
    ): Collection {
        $grupoIds = array_values(array_unique(array_filter(array_map('intval', $grupoIds))));

        if (
            $usarHistorialCiclo
            && Schema::hasTable('inscripcion_ciclos')
            && Schema::hasTable('inscripcion_ciclo_asignaciones')
        ) {
            $historicos = $this->alumnosHistoricosPorContexto(
                cicloEscolarId: $cicloEscolarId,
                grupoIds: $grupoIds,
                fechaCorte: $fechaCorte,
                nivelId: $nivelId,
                gradoId: $gradoId,
                generacionId: $generacionId,
                semestreId: $semestreId,
            );

            if ($historicos->isNotEmpty()) {
                return $historicos;
            }
        }

        return $this->alumnosActualesPorContexto(
            grupoIds: $grupoIds,
            nivelId: $nivelId,
            gradoId: $gradoId,
            generacionId: $generacionId,
            semestreId: $semestreId,
            incluirNoActivos: $incluirNoActivos,
        );
    }

    /**
     * @param array<int> $grupoIds
     */
    private function alumnosActualesPorContexto(
        array $grupoIds,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
        bool $incluirNoActivos,
    ): Collection {
        return Inscripcion::query()
            ->with(['nivel', 'grado', 'semestre', 'grupo.asignacionGrupo', 'generacion'])
            ->when($grupoIds !== [], fn(Builder $q) => $q->whereIn('grupo_id', $grupoIds))
            ->when($nivelId, fn(Builder $q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn(Builder $q) => $q->where('grado_id', $gradoId))
            ->when($generacionId, fn(Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn(Builder $q) => $q->where('semestre_id', $semestreId))
            ->when(!$incluirNoActivos, function (Builder $q): void {
                $q->where('activo', true)
                    ->whereNotIn('estatus', [
                        'baja_temporal',
                        'baja_definitiva',
                        'trasladado',
                        'suspendido',
                        'egresado',
                        'inactivo',
                    ]);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->each(function (Inscripcion $inscripcion): void {
                $inscripcion->setAttribute(
                    'estatus_historico',
                    $this->normalizarEstatusHistorico((string) ($inscripcion->estatus ?: 'activo'))
                );
                $inscripcion->setAttribute('inscripcion_ciclo_id', null);
            });
    }

    /**
     * Reconstruye la lista del ciclo seleccionado sin depender de la ubicación
     * actual guardada en inscripciones. La asignación vigente en la fecha de
     * corte tiene prioridad; el snapshot principal de inscripcion_ciclos sirve
     * como respaldo para bajas, egresos o registros reconstruidos.
     *
     * @param array<int> $grupoIds
     */
    private function alumnosHistoricosPorContexto(
        int $cicloEscolarId,
        array $grupoIds,
        CarbonInterface|string|null $fechaCorte,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
    ): Collection {
        $corte = $this->fechaCorte($fechaCorte);

        $query = InscripcionCiclo::query()
            ->with([
                // En las restricciones de eager loading Laravel entrega la
                // instancia de la relación (BelongsTo/HasMany), no un Builder.
                // No se tipan estos closures para mantener compatibilidad con Laravel 12.
                'inscripcion' => fn($relacion) => $relacion->with([
                    'nivel',
                    'grado',
                    'semestre',
                    'grupo.asignacionGrupo',
                    'generacion',
                ]),
                'asignaciones' => fn($relacion) => $relacion
                    ->with(['grupo.asignacionGrupo'])
                    ->orderBy('fecha_inicio')
                    ->orderBy('id'),
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereHas('inscripcion')
            ->where(function (Builder $contexto) use ($grupoIds, $corte, $nivelId, $gradoId, $generacionId, $semestreId): void {
                $contexto
                    ->whereHas('asignaciones', function (Builder $asignacion) use ($grupoIds, $corte, $nivelId, $gradoId, $generacionId, $semestreId): void {
                        $this->aplicarContextoAsignacion(
                            query: $asignacion,
                            grupoIds: $grupoIds,
                            corte: $corte,
                            nivelId: $nivelId,
                            gradoId: $gradoId,
                            generacionId: $generacionId,
                            semestreId: $semestreId,
                        );
                    })
                    ->orWhere(function (Builder $snapshot) use ($grupoIds, $nivelId, $gradoId, $generacionId, $semestreId): void {
                        // El snapshot solo es respaldo cuando no existe una
                        // cronología de asignaciones. Así se evita mostrar al
                        // alumno en dos grupos después de un cambio interno.
                        $snapshot->whereDoesntHave('asignaciones');

                        $this->aplicarContextoSnapshot(
                            query: $snapshot,
                            grupoIds: $grupoIds,
                            nivelId: $nivelId,
                            gradoId: $gradoId,
                            generacionId: $generacionId,
                            semestreId: $semestreId,
                        );
                    });
            });

        return $query
            ->get()
            ->map(function (InscripcionCiclo $registro) use ($grupoIds, $corte, $nivelId, $gradoId, $generacionId, $semestreId): ?Inscripcion {
                $inscripcion = $registro->inscripcion;

                if (!$inscripcion) {
                    return null;
                }

                $asignacion = $registro->asignaciones
                    ->first(function (InscripcionCicloAsignacion $item) use ($grupoIds, $corte, $nivelId, $gradoId, $generacionId, $semestreId): bool {
                        return $this->asignacionCoincide(
                            asignacion: $item,
                            grupoIds: $grupoIds,
                            corte: $corte,
                            nivelId: $nivelId,
                            gradoId: $gradoId,
                            generacionId: $generacionId,
                            semestreId: $semestreId,
                        );
                    });

                $inscripcion->setAttribute('matricula', $registro->matricula ?: $inscripcion->matricula);
                $inscripcion->setAttribute('inscripcion_ciclo_id', (int) $registro->id);
                $inscripcion->setAttribute('ciclo_escolar_historico_id', (int) $registro->ciclo_escolar_id);
                $inscripcion->setAttribute('nivel_id', (int) ($asignacion?->nivel_id ?: $registro->nivel_id));
                $inscripcion->setAttribute('grado_id', (int) ($asignacion?->grado_id ?: $registro->grado_id));
                $inscripcion->setAttribute('generacion_id', (int) ($asignacion?->generacion_id ?: $registro->generacion_id));
                $inscripcion->setAttribute('grupo_id', (int) ($asignacion?->grupo_id ?: $registro->grupo_id));
                $inscripcion->setAttribute('semestre_id', $asignacion?->semestre_id ?: $registro->semestre_id);
                $inscripcion->setAttribute('fecha_ingreso_ciclo', $registro->fecha_ingreso);
                $inscripcion->setAttribute('fecha_salida_ciclo', $registro->fecha_salida);
                $inscripcion->setAttribute('estado_ciclo', $registro->estado);
                $inscripcion->setAttribute('resultado_final_ciclo', $registro->resultado_final);
                $inscripcion->setAttribute('promovido_ciclo', (bool) $registro->promovido);
                $inscripcion->setAttribute(
                    'estatus_historico',
                    $this->normalizarEstatusHistorico(
                        (string) ($registro->resultado_final ?: $registro->estatus_actual_ciclo ?: $registro->estatus_ingreso ?: 'activo')
                    )
                );

                return $inscripcion;
            })
            ->filter()
            ->unique('id')
            ->sortBy(fn(Inscripcion $alumno) => mb_strtolower(trim(
                ($alumno->apellido_paterno ?? '') . ' ' .
                ($alumno->apellido_materno ?? '') . ' ' .
                ($alumno->nombre ?? '')
            )))
            ->values();
    }

    /**
     * @param array<int> $grupoIds
     */
    private function aplicarContextoAsignacion(
        Builder $query,
        array $grupoIds,
        Carbon $corte,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
    ): void {
        $query
            ->when($grupoIds !== [], fn(Builder $q) => $q->whereIn('grupo_id', $grupoIds))
            ->when($nivelId, fn(Builder $q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn(Builder $q) => $q->where('grado_id', $gradoId))
            ->when($generacionId, fn(Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn(Builder $q) => $q->where('semestre_id', $semestreId))
            ->whereDate('fecha_inicio', '<=', $corte->toDateString())
            ->where(function (Builder $vigencia) use ($corte): void {
                $vigencia
                    ->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $corte->toDateString());
            });
    }

    /**
     * @param array<int> $grupoIds
     */
    private function aplicarContextoSnapshot(
        Builder $query,
        array $grupoIds,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
    ): void {
        $query
            ->when($grupoIds !== [], fn(Builder $q) => $q->whereIn('grupo_id', $grupoIds))
            ->when($nivelId, fn(Builder $q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn(Builder $q) => $q->where('grado_id', $gradoId))
            ->when($generacionId, fn(Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn(Builder $q) => $q->where('semestre_id', $semestreId));
    }

    /**
     * @param array<int> $grupoIds
     */
    private function asignacionCoincide(
        InscripcionCicloAsignacion $asignacion,
        array $grupoIds,
        Carbon $corte,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
    ): bool {
        if ($grupoIds !== [] && !in_array((int) $asignacion->grupo_id, $grupoIds, true)) {
            return false;
        }

        if ($nivelId && (int) $asignacion->nivel_id !== $nivelId) {
            return false;
        }

        if ($gradoId && (int) $asignacion->grado_id !== $gradoId) {
            return false;
        }

        if ($generacionId && (int) $asignacion->generacion_id !== $generacionId) {
            return false;
        }

        if ($semestreId && (int) $asignacion->semestre_id !== $semestreId) {
            return false;
        }

        $inicio = $asignacion->fecha_inicio?->copy()->startOfDay();
        $fin = $asignacion->fecha_fin?->copy()->endOfDay();

        return (!$inicio || $inicio->lte($corte)) && (!$fin || $fin->gte($corte));
    }

    public function normalizarEstatusHistorico(string $estatus): string
    {
        $estatus = mb_strtolower(trim($estatus));

        return match ($estatus) {
            'promovido_nivel' => 'promovido',
            'baja_temporal_al_cierre' => 'baja_temporal',
            'traslado' => 'trasladado',
            'finalizado' => 'activo',
            '' => 'activo',
            default => $estatus,
        };
    }

    public function fechaCorte(CarbonInterface|string|null $fecha): Carbon
    {
        if ($fecha instanceof CarbonInterface) {
            return Carbon::instance($fecha)->startOfDay();
        }

        return filled($fecha)
            ? Carbon::parse($fecha)->startOfDay()
            : now()->startOfDay();
    }
}
