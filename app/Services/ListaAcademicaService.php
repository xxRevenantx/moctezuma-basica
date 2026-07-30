<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\InscripcionCicloAsignacion;
use App\Models\TallerSesion;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        CarbonInterface|string|null $fechaInicio = null,
        CarbonInterface|string|null $fechaFin = null,
        ?int $periodoId = null,
        bool $usarActualComoRespaldo = true,
        bool $incluirTodaGeneracionBachillerato = false,
    ): Collection {
        $grupoIds = array_values(array_unique(array_filter(array_map('intval', $grupoIds))));

        if ($usarHistorialCiclo) {
            $historicos = collect();

            if (
                Schema::hasTable('inscripcion_ciclos')
                && Schema::hasTable('inscripcion_ciclo_asignaciones')
            ) {
                $historicos = $this->alumnosHistoricosPorContexto(
                    cicloEscolarId: $cicloEscolarId,
                    grupoIds: $grupoIds,
                    fechaCorte: $fechaCorte,
                    fechaInicio: $fechaInicio,
                    fechaFin: $fechaFin,
                    nivelId: $nivelId,
                    gradoId: $gradoId,
                    generacionId: $generacionId,
                    semestreId: $semestreId,
                );
            }

            /*
             * Una calificación ya capturada es evidencia académica suficiente
             * para conservar al alumno en la lista, incluso si un historial
             * antiguo quedó incompleto o fue reconstruido parcialmente.
             */
            if (Schema::hasTable('calificaciones')) {
                $historicos = $historicos
                    ->concat($this->alumnosDesdeCalificacionesPorContexto(
                        cicloEscolarId: $cicloEscolarId,
                        grupoIds: $grupoIds,
                        periodoId: $periodoId,
                        nivelId: $nivelId,
                        gradoId: $gradoId,
                        generacionId: $generacionId,
                        semestreId: $semestreId,
                    ));
            }

            /*
             * Regla especial de Bachillerato: cuando se solicita, todos los
             * alumnos vinculados alguna vez a la generación permanecen
             * visibles a lo largo de sus ciclos, grados y semestres. La fecha
             * real de inscripción no limita la captura. Si ya existe evidencia
             * en otro grupo del mismo contexto, el alumno no se duplica.
             */
            if (
                $incluirTodaGeneracionBachillerato
                && $generacionId
                && $nivelId
                && $gradoId
                && $semestreId
                && $grupoIds !== []
            ) {
                $historicos = $historicos
                    ->concat($this->alumnosVinculadosGeneracionPorContexto(
                        cicloEscolarId: $cicloEscolarId,
                        grupoIds: $grupoIds,
                        nivelId: $nivelId,
                        gradoId: $gradoId,
                        generacionId: $generacionId,
                        semestreId: $semestreId,
                    ));
            }

            $historicos = $this->ordenarAlumnos($historicos);

            if ($historicos->isNotEmpty() || ! $usarActualComoRespaldo) {
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
            ->with(['nivel', 'grado', 'semestre', 'grupo.asignacionGrupo', 'generacion', 'cicloEscolar'])
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
                $inscripcion->setAttribute('ubicacion_actual', $this->ubicacionActual($inscripcion));
                $inscripcion->setAttribute('ubicacion_actual_distinta', false);
            });
    }

    /**
     * Reconstruye la lista del ciclo seleccionado sin depender de la ubicación
     * actual guardada en inscripciones. Para periodos académicos se utiliza el
     * cruce de rangos: una asignación pertenece al contexto cuando estuvo
     * vigente en cualquier momento entre fechaInicio y fechaFin.
     *
     * @param array<int> $grupoIds
     */
    private function alumnosHistoricosPorContexto(
        int $cicloEscolarId,
        array $grupoIds,
        CarbonInterface|string|null $fechaCorte,
        CarbonInterface|string|null $fechaInicio,
        CarbonInterface|string|null $fechaFin,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
    ): Collection {
        [$inicio, $fin] = $this->rangoFechas($fechaInicio, $fechaFin, $fechaCorte);

        $query = InscripcionCiclo::query()
            ->with([
                'inscripcion' => fn($relacion) => $relacion->with([
                    'nivel',
                    'grado',
                    'semestre',
                    'grupo.asignacionGrupo',
                    'generacion',
                    'cicloEscolar',
                ]),
                'asignaciones' => fn($relacion) => $relacion
                    ->with(['grupo.asignacionGrupo', 'grado', 'semestre'])
                    ->orderBy('fecha_inicio')
                    ->orderBy('id'),
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', '!=', 'anulado')
            ->whereHas('inscripcion')
            ->where(function (Builder $contexto) use ($grupoIds, $inicio, $fin, $nivelId, $gradoId, $generacionId, $semestreId): void {
                $contexto
                    ->whereHas('asignaciones', function (Builder $asignacion) use ($grupoIds, $inicio, $fin, $nivelId, $gradoId, $generacionId, $semestreId): void {
                        $this->aplicarContextoAsignacion(
                            query: $asignacion,
                            grupoIds: $grupoIds,
                            inicio: $inicio,
                            fin: $fin,
                            nivelId: $nivelId,
                            gradoId: $gradoId,
                            generacionId: $generacionId,
                            semestreId: $semestreId,
                        );
                    })
                    ->orWhere(function (Builder $snapshot) use ($grupoIds, $inicio, $fin, $nivelId, $gradoId, $generacionId, $semestreId): void {
                        /*
                         * El snapshot funciona como respaldo cuando no existe
                         * una asignación que coincida con el rango y contexto.
                         * Esto recupera historiales reconstruidos sin mezclar al
                         * alumno si sí existe una cronología válida.
                         */
                        $snapshot->whereDoesntHave('asignaciones', function (Builder $asignacion) use ($grupoIds, $inicio, $fin, $nivelId, $gradoId, $generacionId, $semestreId): void {
                            $this->aplicarContextoAsignacion(
                                query: $asignacion,
                                grupoIds: $grupoIds,
                                inicio: $inicio,
                                fin: $fin,
                                nivelId: $nivelId,
                                gradoId: $gradoId,
                                generacionId: $generacionId,
                                semestreId: $semestreId,
                            );
                        });

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
            ->map(function (InscripcionCiclo $registro) use ($grupoIds, $inicio, $fin, $nivelId, $gradoId, $generacionId, $semestreId): ?Inscripcion {
                $inscripcion = $registro->inscripcion;

                if (!$inscripcion) {
                    return null;
                }

                $ubicacionActual = $this->ubicacionActual($inscripcion);

                $asignacion = $registro->asignaciones
                    ->first(function (InscripcionCicloAsignacion $item) use ($grupoIds, $inicio, $fin, $nivelId, $gradoId, $generacionId, $semestreId): bool {
                        return $this->asignacionCoincide(
                            asignacion: $item,
                            grupoIds: $grupoIds,
                            inicio: $inicio,
                            fin: $fin,
                            nivelId: $nivelId,
                            gradoId: $gradoId,
                            generacionId: $generacionId,
                            semestreId: $semestreId,
                        );
                    });

                $nivelHistoricoId = (int) ($asignacion?->nivel_id ?: $registro->nivel_id);
                $gradoHistoricoId = (int) ($asignacion?->grado_id ?: $registro->grado_id);
                $generacionHistoricaId = (int) ($asignacion?->generacion_id ?: $registro->generacion_id);
                $grupoHistoricoId = (int) ($asignacion?->grupo_id ?: $registro->grupo_id);
                $semestreHistoricoId = $asignacion?->semestre_id ?: $registro->semestre_id;

                $inscripcion->setAttribute('matricula', $registro->matricula ?: $inscripcion->matricula);
                $inscripcion->setAttribute('inscripcion_ciclo_id', (int) $registro->id);
                $inscripcion->setAttribute('ciclo_escolar_historico_id', (int) $registro->ciclo_escolar_id);
                $inscripcion->setAttribute('nivel_id', $nivelHistoricoId);
                $inscripcion->setAttribute('grado_id', $gradoHistoricoId);
                $inscripcion->setAttribute('generacion_id', $generacionHistoricaId);
                $inscripcion->setAttribute('grupo_id', $grupoHistoricoId);
                $inscripcion->setAttribute('semestre_id', $semestreHistoricoId);
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
                $inscripcion->setAttribute('ubicacion_actual', $ubicacionActual);
                $inscripcion->setAttribute(
                    'ubicacion_actual_distinta',
                    $this->ubicacionEsDistinta(
                        $ubicacionActual,
                        $registro->ciclo_escolar_id,
                        $gradoHistoricoId,
                        $grupoHistoricoId,
                        $semestreHistoricoId,
                    )
                );

                return $inscripcion;
            })
            ->filter();
    }

    /**
     * Recupera alumnos respaldados por calificaciones del propio periodo.
     *
     * @param array<int> $grupoIds
     */
    private function alumnosDesdeCalificacionesPorContexto(
        int $cicloEscolarId,
        array $grupoIds,
        ?int $periodoId,
        ?int $nivelId,
        ?int $gradoId,
        ?int $generacionId,
        ?int $semestreId,
    ): Collection {
        $registros = Calificacion::query()
            ->with('inscripcionCiclo')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->when($periodoId, fn (Builder $q) => $q->where('periodo_id', $periodoId))
            ->when($grupoIds !== [], fn (Builder $q) => $q->whereIn('grupo_id', $grupoIds))
            ->when($nivelId, fn (Builder $q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn (Builder $q) => $q->where('grado_id', $gradoId))
            ->when($generacionId, fn (Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn (Builder $q) => $q->where('semestre_id', $semestreId))
            ->orderBy('inscripcion_id')
            ->orderBy('id')
            ->get()
            ->unique('inscripcion_id')
            ->values();

        if ($registros->isEmpty()) {
            return collect();
        }

        $alumnos = Inscripcion::withTrashed()
            ->with(['nivel', 'grado', 'semestre', 'grupo.asignacionGrupo', 'generacion', 'cicloEscolar'])
            ->whereIn('id', $registros->pluck('inscripcion_id')->all())
            ->get()
            ->keyBy('id');

        return $registros->map(function (Calificacion $calificacion) use ($alumnos): ?Inscripcion {
            $inscripcion = $alumnos->get((int) $calificacion->inscripcion_id);

            if (!$inscripcion) {
                return null;
            }

            $ubicacionActual = $this->ubicacionActual($inscripcion);
            $historial = $calificacion->inscripcionCiclo;

            $inscripcion->setAttribute('inscripcion_ciclo_id', $calificacion->inscripcion_ciclo_id ?: $historial?->id);
            $inscripcion->setAttribute('ciclo_escolar_historico_id', (int) $calificacion->ciclo_escolar_id);
            $inscripcion->setAttribute('nivel_id', (int) $calificacion->nivel_id);
            $inscripcion->setAttribute('grado_id', (int) $calificacion->grado_id);
            $inscripcion->setAttribute('generacion_id', (int) $calificacion->generacion_id);
            $inscripcion->setAttribute('grupo_id', (int) $calificacion->grupo_id);
            $inscripcion->setAttribute('semestre_id', $calificacion->semestre_id);
            $inscripcion->setAttribute(
                'estatus_historico',
                $this->normalizarEstatusHistorico((string) (
                    $historial?->resultado_final
                    ?: $historial?->estatus_actual_ciclo
                    ?: $historial?->estatus_ingreso
                    ?: $inscripcion->estatus
                    ?: 'activo'
                ))
            );
            $inscripcion->setAttribute('ubicacion_actual', $ubicacionActual);
            $inscripcion->setAttribute(
                'ubicacion_actual_distinta',
                $this->ubicacionEsDistinta(
                    $ubicacionActual,
                    $calificacion->ciclo_escolar_id,
                    $calificacion->grado_id,
                    $calificacion->grupo_id,
                    $calificacion->semestre_id,
                )
            );

            return $inscripcion;
        })->filter();
    }

    /**
     * Incluye por generación a los alumnos aunque el ciclo/semestre consultado
     * sea anterior a su fecha de inscripción. La inclusión es solo académica:
     * no modifica la ubicación real guardada en inscripciones.
     *
     * @param array<int> $grupoIds
     */
    private function alumnosVinculadosGeneracionPorContexto(
        int $cicloEscolarId,
        array $grupoIds,
        int $nivelId,
        int $gradoId,
        int $generacionId,
        int $semestreId,
    ): Collection {
        $grupoSeleccionadoId = (int) ($grupoIds[0] ?? 0);

        if ($grupoSeleccionadoId <= 0) {
            return collect();
        }

        $alumnos = Inscripcion::withTrashed()
            ->with(['nivel', 'grado', 'semestre', 'grupo.asignacionGrupo', 'generacion', 'cicloEscolar'])
            ->where(function (Builder $query) use ($nivelId, $generacionId): void {
                $query
                    ->where(function (Builder $actual) use ($nivelId, $generacionId): void {
                        $actual->where('nivel_id', $nivelId)
                            ->where('generacion_id', $generacionId);
                    })
                    ->orWhereExists(function ($subquery) use ($nivelId, $generacionId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->whereColumn('inscripcion_ciclos.inscripcion_id', 'inscripciones.id')
                            ->where('inscripcion_ciclos.nivel_id', $nivelId)
                            ->where('inscripcion_ciclos.generacion_id', $generacionId);
                    })
                    ->orWhereExists(function ($subquery) use ($nivelId, $generacionId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclo_asignaciones')
                            ->join(
                                'inscripcion_ciclos',
                                'inscripcion_ciclos.id',
                                '=',
                                'inscripcion_ciclo_asignaciones.inscripcion_ciclo_id'
                            )
                            ->whereColumn('inscripcion_ciclos.inscripcion_id', 'inscripciones.id')
                            ->where('inscripcion_ciclo_asignaciones.nivel_id', $nivelId)
                            ->where('inscripcion_ciclo_asignaciones.generacion_id', $generacionId);
                    })
                    ->orWhereExists(function ($subquery) use ($nivelId, $generacionId): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->whereColumn('calificaciones.inscripcion_id', 'inscripciones.id')
                            ->where('calificaciones.nivel_id', $nivelId)
                            ->where('calificaciones.generacion_id', $generacionId);
                    });
            })
            ->get();

        if ($alumnos->isEmpty()) {
            return collect();
        }

        $inscripcionIds = $alumnos->pluck('id')->map(fn ($id) => (int) $id)->all();

        $registrosCiclo = InscripcionCiclo::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', '!=', 'anulado')
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->get()
            ->keyBy('inscripcion_id');

        $gruposPorAlumno = collect($inscripcionIds)
            ->mapWithKeys(fn (int $id) => [$id => collect()]);

        $asignaciones = DB::table('inscripcion_ciclo_asignaciones as asignacion')
            ->join('inscripcion_ciclos as historial', 'historial.id', '=', 'asignacion.inscripcion_ciclo_id')
            ->where('historial.ciclo_escolar_id', $cicloEscolarId)
            ->where('historial.estado', '!=', 'anulado')
            ->whereIn('historial.inscripcion_id', $inscripcionIds)
            ->where('asignacion.nivel_id', $nivelId)
            ->where('asignacion.grado_id', $gradoId)
            ->where('asignacion.generacion_id', $generacionId)
            ->where('asignacion.semestre_id', $semestreId)
            ->get(['historial.inscripcion_id', 'asignacion.grupo_id']);

        foreach ($asignaciones as $asignacion) {
            $id = (int) $asignacion->inscripcion_id;
            $gruposPorAlumno[$id] = collect($gruposPorAlumno->get($id, collect()))
                ->push((int) $asignacion->grupo_id);
        }

        $snapshots = DB::table('inscripcion_ciclos')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', '!=', 'anulado')
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->where('generacion_id', $generacionId)
            ->where('semestre_id', $semestreId)
            ->get(['inscripcion_id', 'grupo_id']);

        foreach ($snapshots as $snapshot) {
            $id = (int) $snapshot->inscripcion_id;
            $gruposPorAlumno[$id] = collect($gruposPorAlumno->get($id, collect()))
                ->push((int) $snapshot->grupo_id);
        }

        $calificaciones = DB::table('calificaciones')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->where('generacion_id', $generacionId)
            ->where('semestre_id', $semestreId)
            ->get(['inscripcion_id', 'grupo_id']);

        foreach ($calificaciones as $calificacion) {
            $id = (int) $calificacion->inscripcion_id;
            $gruposPorAlumno[$id] = collect($gruposPorAlumno->get($id, collect()))
                ->push((int) $calificacion->grupo_id);
        }

        return $alumnos
            ->map(function (Inscripcion $inscripcion) use (
                $registrosCiclo,
                $gruposPorAlumno,
                $grupoSeleccionadoId,
                $cicloEscolarId,
                $nivelId,
                $gradoId,
                $generacionId,
                $semestreId,
            ): ?Inscripcion {
                $gruposContexto = collect($gruposPorAlumno->get((int) $inscripcion->id, collect()))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                /*
                 * Si ya quedó asignado a un grupo dentro del mismo ciclo,
                 * grado y semestre, no se presenta en los otros grupos.
                 */
                if ($gruposContexto->isNotEmpty() && ! $gruposContexto->contains($grupoSeleccionadoId)) {
                    return null;
                }

                $ubicacionActual = $this->ubicacionActual($inscripcion);
                $registro = $registrosCiclo->get((int) $inscripcion->id);
                $contextoPendiente = $gruposContexto->isEmpty();

                $inscripcion->setAttribute('matricula', $registro?->matricula ?: $inscripcion->matricula);
                $inscripcion->setAttribute('inscripcion_ciclo_id', $registro?->id ? (int) $registro->id : null);
                $inscripcion->setAttribute('ciclo_escolar_historico_id', $cicloEscolarId);
                $inscripcion->setAttribute('nivel_id', $nivelId);
                $inscripcion->setAttribute('grado_id', $gradoId);
                $inscripcion->setAttribute('generacion_id', $generacionId);
                $inscripcion->setAttribute('grupo_id', $grupoSeleccionadoId);
                $inscripcion->setAttribute('semestre_id', $semestreId);
                $inscripcion->setAttribute('incluido_por_generacion', true);
                $inscripcion->setAttribute('asignacion_contexto_pendiente', $contextoPendiente);
                $inscripcion->setAttribute(
                    'historial_inferido',
                    $contextoPendiente || (bool) ($registro?->reconstruido ?? false)
                );
                $inscripcion->setAttribute(
                    'estatus_historico',
                    $this->normalizarEstatusHistorico((string) (
                        $registro?->resultado_final
                        ?: $registro?->estatus_actual_ciclo
                        ?: $registro?->estatus_ingreso
                        ?: $inscripcion->estatus
                        ?: 'activo'
                    ))
                );
                $inscripcion->setAttribute('ubicacion_actual', $ubicacionActual);
                $inscripcion->setAttribute(
                    'ubicacion_actual_distinta',
                    $this->ubicacionEsDistinta(
                        $ubicacionActual,
                        $cicloEscolarId,
                        $gradoId,
                        $grupoSeleccionadoId,
                        $semestreId,
                    )
                );

                return $inscripcion;
            })
            ->filter();
    }

    /**
     * @param array<int> $grupoIds
     */
    private function aplicarContextoAsignacion(
        Builder $query,
        array $grupoIds,
        Carbon $inicio,
        Carbon $fin,
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
            ->whereDate('fecha_inicio', '<=', $fin->toDateString())
            ->where(function (Builder $vigencia) use ($inicio): void {
                $vigencia
                    ->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $inicio->toDateString());
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
        Carbon $inicio,
        Carbon $fin,
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

        $asignacionInicio = $asignacion->fecha_inicio?->copy()->startOfDay();
        $asignacionFin = $asignacion->fecha_fin?->copy()->endOfDay();

        return (!$asignacionInicio || $asignacionInicio->lte($fin))
            && (!$asignacionFin || $asignacionFin->gte($inicio));
    }

    private function ubicacionActual(Inscripcion $inscripcion): array
    {
        $texto = collect([
            $inscripcion->grado?->nombre,
            $inscripcion->semestre ? 'Sem. '.$inscripcion->semestre->numero : null,
            $inscripcion->grupo?->asignacionGrupo?->nombre
                ? 'Grupo '.$inscripcion->grupo->asignacionGrupo->nombre
                : null,
        ])->filter()->join(' · ');

        return [
            'ciclo_escolar_id' => (int) ($inscripcion->getRawOriginal('ciclo_escolar_id') ?? $inscripcion->ciclo_escolar_id ?? 0),
            'grado_id' => (int) ($inscripcion->getRawOriginal('grado_id') ?? $inscripcion->grado_id ?? 0),
            'semestre_id' => (int) ($inscripcion->getRawOriginal('semestre_id') ?? $inscripcion->semestre_id ?? 0),
            'grupo_id' => (int) ($inscripcion->getRawOriginal('grupo_id') ?? $inscripcion->grupo_id ?? 0),
            'texto' => $texto !== '' ? $texto : 'Sin ubicación actual',
        ];
    }

    private function ubicacionEsDistinta(
        array $ubicacionActual,
        mixed $cicloEscolarId,
        mixed $gradoId,
        mixed $grupoId,
        mixed $semestreId,
    ): bool {
        return (int) ($ubicacionActual['ciclo_escolar_id'] ?? 0) !== (int) $cicloEscolarId
            || (int) ($ubicacionActual['grado_id'] ?? 0) !== (int) $gradoId
            || (int) ($ubicacionActual['grupo_id'] ?? 0) !== (int) $grupoId
            || (int) ($ubicacionActual['semestre_id'] ?? 0) !== (int) $semestreId;
    }

    private function ordenarAlumnos(Collection $alumnos): Collection
    {
        return $alumnos
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
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoFechas(
        CarbonInterface|string|null $fechaInicio,
        CarbonInterface|string|null $fechaFin,
        CarbonInterface|string|null $fechaCorte,
    ): array {
        if (filled($fechaInicio) || filled($fechaFin)) {
            $inicio = $this->fechaCorte($fechaInicio ?: $fechaFin ?: $fechaCorte);
            $fin = $this->fechaCorte($fechaFin ?: $fechaInicio ?: $fechaCorte)->endOfDay();

            if ($inicio->gt($fin)) {
                return [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
            }

            return [$inicio->startOfDay(), $fin->endOfDay()];
        }

        $corte = $this->fechaCorte($fechaCorte);

        return [$corte->copy()->startOfDay(), $corte->copy()->endOfDay()];
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
