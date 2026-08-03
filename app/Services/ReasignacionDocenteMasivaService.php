<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Horario;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\HorarioVersionDetalle;
use App\Models\HorarioVersionEvento;
use App\Models\Persona;
use App\Models\ReasignacionDocenteDetalle;
use App\Models\ReasignacionDocenteLote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReasignacionDocenteMasivaService
{
    public function __construct(
        private readonly PlantillaDocenteService $plantillaDocente,
    ) {
    }

    /**
     * @param  array<int, int|string>  $asignacionIds
     * @return array{
     *     filas: array<int, array<string, mixed>>,
     *     conflictos: array<int, array<string, mixed>>,
     *     ids_aplicables: array<int, int>,
     *     resumen: array<string, int>
     * }
     */
    public function previsualizar(
        array $asignacionIds,
        int $profesorDestinoId,
        int $cicloEscolarId,
        int $nivelId,
        bool $incluirCerradas = false,
        bool $incluirArchivadas = false,
    ): array {
        $ids = collect($asignacionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'reasignacion_seleccionados' => 'Selecciona al menos una materia para continuar.',
            ]);
        }

        if (! $this->plantillaDocente->pertenece($profesorDestinoId, $cicloEscolarId, $nivelId)) {
            throw ValidationException::withMessages([
                'reasignacion_destino_id' => 'El profesor destino debe pertenecer a la plantilla publicada del mismo ciclo y nivel con una función docente activa y confirmada.',
            ]);
        }

        $profesorDestino = Persona::query()->findOrFail($profesorDestinoId);
        $nombreDestino = $this->nombrePersona($profesorDestino);

        $asignaciones = AsignacionMateria::query()
            ->with([
                'materia',
                'profesor',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'grupo.asignacionGrupo',
                'horarios' => fn ($query) => $query
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->with(['dia', 'hora', 'nivel', 'grado', 'grupo.asignacionGrupo']),
            ])
            ->withCount(['horarios', 'calificaciones', 'bitacoraCalificaciones'])
            ->whereIn('id', $ids)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->get()
            ->keyBy('id');

        $filas = [];
        $aplicables = new EloquentCollection();
        $excluidas = 0;
        $conHistorial = 0;
        $cerradas = 0;
        $archivadas = 0;

        foreach ($ids as $id) {
            /** @var AsignacionMateria|null $asignacion */
            $asignacion = $asignaciones->get($id);
            $resultado = 'Lista para reasignar';
            $aplicable = true;

            if (! $asignacion) {
                $resultado = 'No pertenece al ciclo o nivel seleccionado';
                $aplicable = false;
            } elseif (! $asignacion->materia || $asignacion->materia->receso) {
                $resultado = 'Excluida: receso o registro auxiliar';
                $aplicable = false;
            } elseif ($asignacion->estado === AsignacionMateria::ESTADO_CERRADA && ! $incluirCerradas) {
                $resultado = 'Excluida: carga cerrada sin autorización explícita';
                $aplicable = false;
                $cerradas++;
            } elseif ($asignacion->estado === AsignacionMateria::ESTADO_ARCHIVADA && ! $incluirArchivadas) {
                $resultado = 'Excluida: carga archivada sin autorización explícita';
                $aplicable = false;
                $archivadas++;
            } elseif ((int) ($asignacion->profesor_id ?? 0) === $profesorDestinoId) {
                $resultado = 'Omitida: ya pertenece al docente destino';
                $aplicable = false;
            }

            if ($asignacion?->tieneHistorial()) {
                $conHistorial++;
            }

            if ($aplicable && $asignacion) {
                $aplicables->push($asignacion);
            } else {
                $excluidas++;
            }

            $filas[$id] = $this->filaPrevisualizacion(
                asignacion: $asignacion,
                nombreDestino: $nombreDestino,
                resultado: $resultado,
                aplicable: $aplicable,
            );
        }

        $conflictos = $this->detectarConflictos(
            asignaciones: $aplicables,
            profesorDestinoId: $profesorDestinoId,
            cicloEscolarId: $cicloEscolarId,
            nivelId: $nivelId,
        );

        $conflictosPorAsignacion = collect($conflictos)->groupBy('asignacion_id');

        foreach ($aplicables as $asignacion) {
            $deLaAsignacion = $conflictosPorAsignacion->get($asignacion->id, collect())->values()->all();
            $filas[$asignacion->id]['conflictos'] = $deLaAsignacion;
            $filas[$asignacion->id]['resultado'] = $deLaAsignacion === []
                ? 'Sin conflicto'
                : count($deLaAsignacion) . ' conflicto(s) por revisar';
        }

        $filasOrdenadas = $ids
            ->map(fn (int $id) => $filas[$id] ?? null)
            ->filter()
            ->values()
            ->all();

        return [
            'filas' => $filasOrdenadas,
            'conflictos' => $conflictos,
            'ids_aplicables' => $aplicables->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'resumen' => [
                'seleccionadas' => $ids->count(),
                'aplicables' => $aplicables->count(),
                'excluidas' => $excluidas,
                'grupos' => $aplicables->pluck('grupo_id')->filter()->unique()->count(),
                'bloques' => $aplicables->sum(fn (AsignacionMateria $a) => $a->horarios->count()),
                'con_historial' => $conHistorial,
                'conflictos' => count($conflictos),
                'cerradas' => $cerradas,
                'archivadas' => $archivadas,
            ],
        ];
    }

    /**
     * @param  array<int, int|string>  $asignacionIds
     */
    public function aplicar(
        array $asignacionIds,
        int $profesorDestinoId,
        int $cicloEscolarId,
        int $nivelId,
        string $modo,
        ?int $profesorOrigenId,
        bool $incluirCerradas,
        bool $incluirArchivadas,
        bool $autorizarConflictos,
        ?string $motivoConflictos,
        ?int $usuarioId,
    ): ReasignacionDocenteLote {
        $preview = $this->previsualizar(
            asignacionIds: $asignacionIds,
            profesorDestinoId: $profesorDestinoId,
            cicloEscolarId: $cicloEscolarId,
            nivelId: $nivelId,
            incluirCerradas: $incluirCerradas,
            incluirArchivadas: $incluirArchivadas,
        );

        if ($preview['resumen']['aplicables'] === 0) {
            throw ValidationException::withMessages([
                'reasignacion_seleccionados' => 'No hay materias aplicables en la selección actual.',
            ]);
        }

        $hayConflictos = $preview['resumen']['conflictos'] > 0;
        $motivo = trim((string) $motivoConflictos);

        if ($hayConflictos && ! $autorizarConflictos) {
            throw ValidationException::withMessages([
                'reasignacion_autorizar_conflictos' => 'Revisa y autoriza los conflictos antes de aplicar la reasignación.',
            ]);
        }

        if ($hayConflictos && mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'reasignacion_motivo_conflictos' => 'Explica la excepción administrativa con al menos 10 caracteres.',
            ]);
        }

        $idsAplicables = collect($preview['ids_aplicables'])->map(fn ($id) => (int) $id)->values();
        $profesorDestinoNombre = $this->nombrePersona(Persona::query()->find($profesorDestinoId));
        $profesorOrigenNombre = $profesorOrigenId
            ? $this->nombrePersona(Persona::query()->withTrashed()->find($profesorOrigenId))
            : ($modo === 'profesor' ? 'Sin docente asignado' : 'Selección manual');
        $horariosConConflicto = collect($preview['conflictos'])
            ->pluck('horario_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();
        $slotsConConflicto = collect($preview['conflictos'])
            ->map(fn (array $conflicto) => implode('-', [
                (int) ($conflicto['asignacion_id'] ?? 0),
                (int) ($conflicto['grupo_id'] ?? 0),
                (int) ($conflicto['dia_id'] ?? 0),
                (int) ($conflicto['hora_id'] ?? 0),
            ]))
            ->unique();

        return DB::transaction(function () use (
            $idsAplicables,
            $profesorDestinoId,
            $cicloEscolarId,
            $nivelId,
            $modo,
            $profesorOrigenId,
            $incluirCerradas,
            $incluirArchivadas,
            $preview,
            $hayConflictos,
            $motivo,
            $usuarioId,
            $horariosConConflicto,
            $slotsConConflicto,
            $profesorOrigenNombre,
            $profesorDestinoNombre,
        ): ReasignacionDocenteLote {
            $lote = ReasignacionDocenteLote::query()->create([
                'uuid' => (string) Str::uuid(),
                'ciclo_escolar_id' => $cicloEscolarId,
                'nivel_id' => $nivelId,
                'profesor_origen_id' => $profesorOrigenId,
                'profesor_destino_id' => $profesorDestinoId,
                'modo' => in_array($modo, ['seleccion', 'profesor'], true) ? $modo : 'seleccion',
                'estado' => ReasignacionDocenteLote::ESTADO_APLICADA,
                'total_asignaciones' => 0,
                'total_horarios' => 0,
                'total_versiones' => 0,
                'total_conflictos' => (int) $preview['resumen']['conflictos'],
                'conflictos_autorizados' => $hayConflictos,
                'motivo_autorizacion_conflictos' => $hayConflictos ? $motivo : null,
                'metadata' => [
                    'seleccionadas' => $preview['resumen']['seleccionadas'],
                    'excluidas' => $preview['resumen']['excluidas'],
                    'cerradas_excluidas' => $preview['resumen']['cerradas'],
                    'archivadas_excluidas' => $preview['resumen']['archivadas'],
                    'profesor_origen_nombre' => $profesorOrigenNombre,
                    'profesor_destino_nombre' => $profesorDestinoNombre,
                ],
                'aplicado_por' => $usuarioId,
                'aplicado_at' => now(),
            ]);

            $totalHorarios = 0;
            $totalVersiones = 0;
            $versionesAfectadas = [];

            foreach ($idsAplicables as $asignacionId) {
                /** @var AsignacionMateria $asignacion */
                $asignacion = AsignacionMateria::query()
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('nivel_id', $nivelId)
                    ->lockForUpdate()
                    ->findOrFail($asignacionId);

                $asignacion->loadMissing('materia');

                if (! $asignacion->materia || $asignacion->materia->receso) {
                    throw ValidationException::withMessages([
                        'reasignacion_seleccionados' => 'Una materia seleccionada se convirtió en receso o registro auxiliar antes de aplicar el lote. Actualiza la vista previa.',
                    ]);
                }

                if ($asignacion->estado === AsignacionMateria::ESTADO_CERRADA && ! $incluirCerradas) {
                    throw ValidationException::withMessages([
                        'reasignacion_seleccionados' => 'Una carga fue cerrada después de la vista previa. Activa la inclusión de cerradas y vuelve a revisar.',
                    ]);
                }

                if ($asignacion->estado === AsignacionMateria::ESTADO_ARCHIVADA && ! $incluirArchivadas) {
                    throw ValidationException::withMessages([
                        'reasignacion_seleccionados' => 'Una carga fue archivada después de la vista previa. Activa la inclusión de archivadas y vuelve a revisar.',
                    ]);
                }

                $profesorAnteriorId = $asignacion->profesor_id ? (int) $asignacion->profesor_id : null;
                $profesorAnteriorNombre = $profesorAnteriorId
                    ? $this->nombrePersona(Persona::query()->withTrashed()->find($profesorAnteriorId))
                    : 'Sin docente asignado';

                if ($modo === 'profesor' && (int) ($profesorAnteriorId ?? 0) !== (int) ($profesorOrigenId ?? 0)) {
                    throw ValidationException::withMessages([
                        'reasignacion_seleccionados' => 'El docente de una carga cambió después de la vista previa. Actualiza la selección antes de aplicar.',
                    ]);
                }

                if ((int) ($profesorAnteriorId ?? 0) === $profesorDestinoId) {
                    throw ValidationException::withMessages([
                        'reasignacion_destino_id' => 'Una carga ya pertenece al profesor destino. Actualiza la vista previa antes de aplicar.',
                    ]);
                }

                $horarios = Horario::query()
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('asignacion_materia_id', $asignacion->id)
                    ->lockForUpdate()
                    ->get();

                $versiones = HorarioVersionDetalle::query()
                    ->with('version:id,estado')
                    ->where('asignacion_materia_id', $asignacion->id)
                    ->whereHas('version', fn ($query) => $query->whereIn('estado', ['propuesta', 'borrador']))
                    ->lockForUpdate()
                    ->get();

                $horariosSnapshot = $horarios->map(fn (Horario $horario) => [
                    'id' => (int) $horario->id,
                    'profesor_id' => $horario->profesor_id ? (int) $horario->profesor_id : null,
                    'asignacion_materia_id' => (int) $horario->asignacion_materia_id,
                    'grupo_id' => (int) $horario->grupo_id,
                    'dia_id' => (int) $horario->dia_id,
                    'hora_id' => (int) $horario->hora_id,
                    'traslape_excepcional' => (bool) $horario->traslape_excepcional,
                    'motivo_traslape_excepcional' => $horario->motivo_traslape_excepcional,
                    'motivo_autorizacion_disponibilidad' => $horario->motivo_autorizacion_disponibilidad,
                ])->values()->all();

                $versionesSnapshot = $versiones->map(fn (HorarioVersionDetalle $detalle) => [
                    'id' => (int) $detalle->id,
                    'horario_version_id' => (int) $detalle->horario_version_id,
                    'profesor_id' => $detalle->profesor_id ? (int) $detalle->profesor_id : null,
                    'asignacion_materia_id' => (int) $detalle->asignacion_materia_id,
                    'grupo_id' => (int) $detalle->grupo_id,
                    'dia_id' => (int) $detalle->dia_id,
                    'hora_id' => (int) $detalle->hora_id,
                    'traslape_excepcional' => (bool) $detalle->traslape_excepcional,
                    'motivo_traslape_excepcional' => $detalle->motivo_traslape_excepcional,
                    'motivo_autorizacion_disponibilidad' => $detalle->motivo_autorizacion_disponibilidad,
                ])->values()->all();

                ReasignacionDocenteDetalle::query()->create([
                    'reasignacion_docente_lote_id' => $lote->id,
                    'asignacion_materia_id' => $asignacion->id,
                    'profesor_anterior_id' => $profesorAnteriorId,
                    'profesor_nuevo_id' => $profesorDestinoId,
                    'grupo_id' => $asignacion->grupo_id,
                    'materia_id' => $asignacion->materia_id,
                    'estado_asignacion' => $asignacion->estado,
                    'resultado' => ReasignacionDocenteDetalle::RESULTADO_APLICADA,
                    'contexto_snapshot' => [
                        'asignacion_materia_id' => (int) $asignacion->id,
                        'grupo_id' => (int) $asignacion->grupo_id,
                        'materia_id' => (int) $asignacion->materia_id,
                        'ciclo_escolar_id' => (int) $asignacion->ciclo_escolar_id,
                        'nivel_id' => (int) $asignacion->nivel_id,
                        'grado_id' => (int) $asignacion->grado_id,
                        'generacion_id' => (int) $asignacion->generacion_id,
                        'semestre_id' => $asignacion->semestre_id ? (int) $asignacion->semestre_id : null,
                        'estado' => $asignacion->estado,
                        'profesor_anterior_nombre' => $profesorAnteriorNombre,
                        'profesor_nuevo_nombre' => $profesorDestinoNombre,
                    ],
                    'horarios_snapshot' => $horariosSnapshot,
                    'versiones_snapshot' => $versionesSnapshot,
                    'aplicado_at' => now(),
                ]);

                $asignacion->update(['profesor_id' => $profesorDestinoId]);

                foreach ($horarios as $horario) {
                    $datosHorario = ['profesor_id' => $profesorDestinoId];

                    if ($hayConflictos && $horariosConConflicto->contains((int) $horario->id)) {
                        $datosHorario['traslape_excepcional'] = true;
                        $datosHorario['motivo_traslape_excepcional'] = $motivo;
                        $datosHorario['motivo_autorizacion_disponibilidad'] = $motivo;
                    }

                    $horario->update($datosHorario);
                }

                foreach ($versiones as $detalleVersion) {
                    $datosVersion = ['profesor_id' => $profesorDestinoId];

                    $slotVersion = implode('-', [
                        (int) $asignacion->id,
                        (int) $detalleVersion->grupo_id,
                        (int) $detalleVersion->dia_id,
                        (int) $detalleVersion->hora_id,
                    ]);

                    if ($hayConflictos && $slotsConConflicto->contains($slotVersion)) {
                        $datosVersion['traslape_excepcional'] = true;
                        $datosVersion['motivo_traslape_excepcional'] = $motivo;
                        $datosVersion['motivo_autorizacion_disponibilidad'] = $motivo;
                    }

                    $detalleVersion->update($datosVersion);
                    $versionesAfectadas[(int) $detalleVersion->horario_version_id][] = (int) $asignacion->id;
                }

                $totalHorarios += $horarios->count();
                $totalVersiones += $versiones->count();
            }

            foreach ($versionesAfectadas as $versionId => $asignacionesVersion) {
                HorarioVersionEvento::query()->create([
                    'horario_version_id' => $versionId,
                    'tipo' => 'reasignacion_docente_masiva',
                    'titulo' => 'Docentes actualizados desde carga académica',
                    'descripcion' => 'La propuesta o borrador se sincronizó con una reasignación masiva. Las versiones publicadas no fueron alteradas.',
                    'metadata' => [
                        'lote_uuid' => $lote->uuid,
                        'profesor_destino_id' => $profesorDestinoId,
                        'asignaciones' => array_values(array_unique($asignacionesVersion)),
                    ],
                    'usuario_id' => $usuarioId,
                    'ocurrido_at' => now(),
                ]);
            }

            $lote->update([
                'total_asignaciones' => $idsAplicables->count(),
                'total_horarios' => $totalHorarios,
                'total_versiones' => $totalVersiones,
            ]);

            return $lote->fresh([
                'profesorOrigen',
                'profesorDestino',
                'detalles',
            ]);
        });
    }

    /**
     * @return array{revertidas:int,omitidas:int,estado:string}
     */
    public function revertir(ReasignacionDocenteLote $lote, ?int $usuarioId): array
    {
        if ($lote->estado !== ReasignacionDocenteLote::ESTADO_APLICADA) {
            throw ValidationException::withMessages([
                'historial_reasignaciones' => 'Este lote ya no puede revertirse.',
            ]);
        }

        return DB::transaction(function () use ($lote, $usuarioId): array {
            $lote = ReasignacionDocenteLote::query()->lockForUpdate()->findOrFail($lote->id);
            $detalles = $lote->detalles()
                ->where('resultado', ReasignacionDocenteDetalle::RESULTADO_APLICADA)
                ->lockForUpdate()
                ->get();

            $revertidas = 0;
            $omitidas = 0;
            $versionesAfectadas = [];

            foreach ($detalles as $detalle) {
                $validacion = $this->validarReversionDetalle($detalle);

                if ($validacion !== null) {
                    $detalle->update([
                        'resultado' => ReasignacionDocenteDetalle::RESULTADO_OMITIDA,
                        'motivo_omision' => $validacion,
                    ]);
                    $omitidas++;
                    continue;
                }

                /** @var AsignacionMateria $asignacion */
                $asignacion = AsignacionMateria::query()->lockForUpdate()->findOrFail($detalle->asignacion_materia_id);
                $asignacion->update(['profesor_id' => $detalle->profesor_anterior_id]);

                foreach ($detalle->horarios_snapshot ?? [] as $snapshot) {
                    Horario::query()->whereKey((int) $snapshot['id'])->update([
                        'profesor_id' => $snapshot['profesor_id'] ?? null,
                        'traslape_excepcional' => (bool) ($snapshot['traslape_excepcional'] ?? false),
                        'motivo_traslape_excepcional' => $snapshot['motivo_traslape_excepcional'] ?? null,
                        'motivo_autorizacion_disponibilidad' => $snapshot['motivo_autorizacion_disponibilidad'] ?? null,
                    ]);
                }

                foreach ($detalle->versiones_snapshot ?? [] as $snapshot) {
                    HorarioVersionDetalle::query()->whereKey((int) $snapshot['id'])->update([
                        'profesor_id' => $snapshot['profesor_id'] ?? null,
                        'traslape_excepcional' => (bool) ($snapshot['traslape_excepcional'] ?? false),
                        'motivo_traslape_excepcional' => $snapshot['motivo_traslape_excepcional'] ?? null,
                        'motivo_autorizacion_disponibilidad' => $snapshot['motivo_autorizacion_disponibilidad'] ?? null,
                    ]);
                    $versionesAfectadas[(int) $snapshot['horario_version_id']][] = (int) $detalle->asignacion_materia_id;
                }

                $detalle->update([
                    'resultado' => ReasignacionDocenteDetalle::RESULTADO_REVERTIDA,
                    'motivo_omision' => null,
                    'revertido_at' => now(),
                ]);
                $revertidas++;
            }

            foreach ($versionesAfectadas as $versionId => $asignacionesVersion) {
                HorarioVersionEvento::query()->create([
                    'horario_version_id' => $versionId,
                    'tipo' => 'reversion_reasignacion_docente',
                    'titulo' => 'Reasignación masiva revertida',
                    'descripcion' => 'Se restauraron los docentes anteriores en la propuesta o borrador editable.',
                    'metadata' => [
                        'lote_uuid' => $lote->uuid,
                        'asignaciones' => array_values(array_unique($asignacionesVersion)),
                    ],
                    'usuario_id' => $usuarioId,
                    'ocurrido_at' => now(),
                ]);
            }

            $estado = $omitidas > 0
                ? ReasignacionDocenteLote::ESTADO_REVERSION_PARCIAL
                : ReasignacionDocenteLote::ESTADO_REVERTIDA;

            $lote->update([
                'estado' => $estado,
                'revertido_por' => $usuarioId,
                'revertido_at' => now(),
                'metadata' => [
                    ...($lote->metadata ?? []),
                    'ultima_reversion' => [
                        'revertidas' => $revertidas,
                        'omitidas' => $omitidas,
                        'fecha' => now()->toIso8601String(),
                    ],
                ],
            ]);

            return compact('revertidas', 'omitidas', 'estado');
        });
    }

    public function sincronizarProfesorIndividual(
        AsignacionMateria $asignacion,
        ?int $profesorAnteriorId,
        ?int $profesorNuevoId,
        ?int $usuarioId,
    ): void {
        if ($profesorAnteriorId === $profesorNuevoId) {
            return;
        }

        Horario::query()
            ->where('ciclo_escolar_id', $asignacion->ciclo_escolar_id)
            ->where('asignacion_materia_id', $asignacion->id)
            ->update(['profesor_id' => $profesorNuevoId]);

        $versionIds = HorarioVersionDetalle::query()
            ->where('asignacion_materia_id', $asignacion->id)
            ->whereHas('version', fn ($query) => $query->whereIn('estado', ['propuesta', 'borrador']))
            ->pluck('horario_version_id')
            ->unique();

        HorarioVersionDetalle::query()
            ->where('asignacion_materia_id', $asignacion->id)
            ->whereHas('version', fn ($query) => $query->whereIn('estado', ['propuesta', 'borrador']))
            ->update(['profesor_id' => $profesorNuevoId]);

        foreach ($versionIds as $versionId) {
            HorarioVersionEvento::query()->create([
                'horario_version_id' => $versionId,
                'tipo' => 'cambio_docente_carga',
                'titulo' => 'Docente sincronizado desde carga académica',
                'descripcion' => 'Se actualizó el profesor en la propuesta o borrador. Las versiones publicadas permanecen intactas.',
                'metadata' => [
                    'asignacion_materia_id' => (int) $asignacion->id,
                    'profesor_anterior_id' => $profesorAnteriorId,
                    'profesor_nuevo_id' => $profesorNuevoId,
                ],
                'usuario_id' => $usuarioId,
                'ocurrido_at' => now(),
            ]);
        }
    }

    private function filaPrevisualizacion(
        ?AsignacionMateria $asignacion,
        string $nombreDestino,
        string $resultado,
        bool $aplicable,
    ): array {
        if (! $asignacion) {
            return [
                'id' => null,
                'materia' => 'Registro no disponible',
                'grupo' => '—',
                'generacion' => '—',
                'semestre' => null,
                'profesor_actual' => '—',
                'profesor_nuevo' => $nombreDestino,
                'bloques' => 0,
                'estado' => '—',
                'con_historial' => false,
                'aplicable' => false,
                'resultado' => $resultado,
                'conflictos' => [],
            ];
        }

        $grupo = trim(($asignacion->grupo?->grado?->nombre ?? 'Sin grado') . ' · Grupo ' . ($asignacion->grupo?->asignacionGrupo?->nombre ?? '—'));
        $generacion = ($asignacion->grupo?->generacion?->anio_ingreso ?? '—') . '-' . ($asignacion->grupo?->generacion?->anio_egreso ?? '—');

        return [
            'id' => (int) $asignacion->id,
            'materia' => $asignacion->materia?->materia ?? 'Materia',
            'grupo' => $grupo,
            'generacion' => $generacion,
            'semestre' => $asignacion->grupo?->semestre?->numero,
            'profesor_actual' => $asignacion->profesor ? $this->nombrePersona($asignacion->profesor) : 'Sin docente asignado',
            'profesor_nuevo' => $nombreDestino,
            'bloques' => $asignacion->horarios->count(),
            'estado' => $asignacion->estado,
            'con_historial' => $asignacion->tieneHistorial(),
            'aplicable' => $aplicable,
            'resultado' => $resultado,
            'conflictos' => [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detectarConflictos(
        EloquentCollection $asignaciones,
        int $profesorDestinoId,
        int $cicloEscolarId,
        int $nivelId,
    ): array {
        if ($asignaciones->isEmpty()) {
            return [];
        }

        $configuracion = HorarioDocenteConfiguracion::query()
            ->with('disponibilidades')
            ->where('persona_id', $profesorDestinoId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where(fn ($query) => $query->where('nivel_id', $nivelId)->orWhereNull('nivel_id'))
            ->where('activo', true)
            ->orderByRaw('nivel_id IS NULL')
            ->first();

        $maximoSimultaneo = max(1, (int) ($configuracion?->max_grupos_simultaneos ?? 2));
        $permitirMultigrado = (bool) ($configuracion?->permitir_multigrado ?? true);
        $permitirMateriasDistintas = (bool) ($configuracion?->permitir_materias_distintas ?? false);
        $disponibilidades = collect($configuracion?->disponibilidades ?? [])
            ->keyBy(fn ($disp) => $disp->dia_id . '-' . $disp->hora_id);

        $idsAsignaciones = $asignaciones->pluck('id')->map(fn ($id) => (int) $id);

        $eventosExistentes = Horario::query()
            ->with([
                'dia',
                'hora',
                'nivel',
                'grado',
                'grupo.asignacionGrupo',
                'asignacionMateria.materia',
                'tallerSesion.taller',
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where(function (Builder $query) use ($idsAsignaciones): void {
                $query->whereNull('asignacion_materia_id')
                    ->orWhereNotIn('asignacion_materia_id', $idsAsignaciones);
            })
            ->where(function ($query) use ($profesorDestinoId): void {
                $query->where('profesor_id', $profesorDestinoId)
                    ->orWhereHas('asignacionMateria', fn ($sub) => $sub
                        ->where('profesor_id', $profesorDestinoId)
                        ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA))
                    ->orWhereHas('tallerSesion', fn ($sub) => $sub->where('profesor_id', $profesorDestinoId));
            })
            ->get()
            ->map(fn (Horario $horario) => $this->eventoHorario($horario, false))
            ->filter();

        $eventosNuevos = $asignaciones
            ->flatMap(fn (AsignacionMateria $asignacion) => $asignacion->horarios->map(
                fn (Horario $horario) => $this->eventoHorario($horario, true, $asignacion)
            ))
            ->filter();

        $todos = $eventosExistentes->concat($eventosNuevos)->values();
        $conflictos = collect();

        foreach ($eventosNuevos as $evento) {
            $disponibilidad = $disponibilidades->get($evento['dia_id'] . '-' . $evento['hora_id']);

            if ($disponibilidad && in_array($disponibilidad->estado, ['no_disponible', 'autorizacion'], true)) {
                $conflictos->push([
                    'asignacion_id' => $evento['asignacion_id'],
                    'horario_id' => $evento['horario_id'],
                    'grupo_id' => $evento['grupo_id'],
                    'dia_id' => $evento['dia_id'],
                    'hora_id' => $evento['hora_id'],
                    'tipo' => 'disponibilidad',
                    'titulo' => $disponibilidad->estado === 'no_disponible'
                        ? 'Docente no disponible'
                        : 'Bloque sujeto a autorización',
                    'detalle' => $evento['dia'] . ' ' . $evento['hora_texto']
                        . ($disponibilidad->motivo ? ': ' . $disponibilidad->motivo : ''),
                ]);
            }

            $simultaneos = $todos->filter(function (array $otro) use ($evento): bool {
                if ($otro['clave'] === $evento['clave']) {
                    return false;
                }

                return $otro['dia_normalizado'] === $evento['dia_normalizado']
                    && $otro['inicio'] < $evento['fin']
                    && $otro['fin'] > $evento['inicio'];
            })->push($evento);

            $grupos = $simultaneos->pluck('grupo_clave')->filter()->unique();
            $materias = $simultaneos->pluck('actividad')->filter()->unique();

            if ($grupos->count() > $maximoSimultaneo) {
                $otros = $simultaneos
                    ->reject(fn (array $otro) => $otro['clave'] === $evento['clave'])
                    ->map(fn (array $otro) => $otro['contexto'])
                    ->filter()
                    ->unique()
                    ->implode('; ');

                $conflictos->push([
                    'asignacion_id' => $evento['asignacion_id'],
                    'horario_id' => $evento['horario_id'],
                    'grupo_id' => $evento['grupo_id'],
                    'dia_id' => $evento['dia_id'],
                    'hora_id' => $evento['hora_id'],
                    'tipo' => 'maximo_simultaneo',
                    'titulo' => 'Se excede el máximo de grupos simultáneos',
                    'detalle' => $evento['dia'] . ' ' . $evento['hora_texto']
                        . ". Máximo permitido: {$maximoSimultaneo}. Coincide con: " . ($otros ?: 'otro bloque'),
                ]);
            }

            if (! $permitirMultigrado && $grupos->count() > 1) {
                $conflictos->push([
                    'asignacion_id' => $evento['asignacion_id'],
                    'horario_id' => $evento['horario_id'],
                    'grupo_id' => $evento['grupo_id'],
                    'dia_id' => $evento['dia_id'],
                    'hora_id' => $evento['hora_id'],
                    'tipo' => 'multigrado_no_permitido',
                    'titulo' => 'La configuración no permite multigrado',
                    'detalle' => $evento['dia'] . ' ' . $evento['hora_texto'] . ' reúne más de un grupo.',
                ]);
            }

            if (! $permitirMateriasDistintas && $materias->count() > 1 && $simultaneos->count() > 1) {
                $conflictos->push([
                    'asignacion_id' => $evento['asignacion_id'],
                    'horario_id' => $evento['horario_id'],
                    'grupo_id' => $evento['grupo_id'],
                    'dia_id' => $evento['dia_id'],
                    'hora_id' => $evento['hora_id'],
                    'tipo' => 'materias_distintas',
                    'titulo' => 'Materias distintas en el mismo bloque',
                    'detalle' => $evento['dia'] . ' ' . $evento['hora_texto'] . ': ' . $materias->implode(', '),
                ]);
            }
        }

        return $conflictos
            ->unique(fn (array $c) => implode('|', [
                $c['asignacion_id'] ?? 0,
                $c['horario_id'] ?? 0,
                $c['tipo'] ?? '',
                $c['detalle'] ?? '',
            ]))
            ->values()
            ->all();
    }

    private function eventoHorario(
        Horario $horario,
        bool $nuevo,
        ?AsignacionMateria $asignacion = null,
    ): ?array {
        $inicio = (string) ($horario->hora?->hora_inicio ?? '');
        $fin = (string) ($horario->hora?->hora_fin ?? '');
        $dia = (string) ($horario->dia?->dia ?? '');

        if ($inicio === '' || $fin === '' || $dia === '') {
            return null;
        }

        $asignacion ??= $horario->asignacionMateria;
        $actividad = $asignacion?->materia?->materia
            ?? $horario->tallerSesion?->taller?->nombre
            ?? 'Actividad';
        $grupo = trim(($horario->grado?->nombre ?? 'Sin grado') . ' ' . ($horario->grupo?->asignacionGrupo?->nombre ?? ''));
        $nivel = $horario->nivel?->nombre ?? 'Nivel';

        return [
            'clave' => ($nuevo ? 'nuevo-' : 'existente-') . $horario->id,
            'nuevo' => $nuevo,
            'horario_id' => (int) $horario->id,
            'asignacion_id' => $asignacion?->id ? (int) $asignacion->id : null,
            'dia_id' => (int) $horario->dia_id,
            'hora_id' => (int) $horario->hora_id,
            'grupo_id' => (int) $horario->grupo_id,
            'dia' => $dia,
            'dia_normalizado' => mb_strtolower(trim($dia)),
            'inicio' => $inicio,
            'fin' => $fin,
            'hora_texto' => substr($inicio, 0, 5) . '-' . substr($fin, 0, 5),
            'grupo_clave' => (int) $horario->grupo_id,
            'actividad' => $actividad,
            'contexto' => trim($nivel . ' · ' . $grupo . ' · ' . $actividad),
        ];
    }

    private function validarReversionDetalle(ReasignacionDocenteDetalle $detalle): ?string
    {
        $asignacion = AsignacionMateria::query()->find($detalle->asignacion_materia_id);
        $snapshot = $detalle->contexto_snapshot ?? [];

        if (! $asignacion) {
            return 'La carga académica ya no existe.';
        }

        if ((int) ($asignacion->profesor_id ?? 0) !== (int) ($detalle->profesor_nuevo_id ?? 0)) {
            return 'El profesor de la carga cambió después de aplicar el lote.';
        }

        foreach (['grupo_id', 'materia_id', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id'] as $campo) {
            if ((int) ($asignacion->{$campo} ?? 0) !== (int) ($snapshot[$campo] ?? 0)) {
                return 'El contexto académico cambió después de aplicar el lote.';
            }
        }

        if ((int) ($asignacion->semestre_id ?? 0) !== (int) ($snapshot['semestre_id'] ?? 0)) {
            return 'El semestre cambió después de aplicar el lote.';
        }

        if ((string) $asignacion->estado !== (string) ($snapshot['estado'] ?? '')) {
            return 'El estado de la carga cambió después de aplicar el lote.';
        }

        $horariosSnapshotIds = collect($detalle->horarios_snapshot ?? [])
            ->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $horariosActualesIds = Horario::query()
            ->where('ciclo_escolar_id', (int) ($snapshot['ciclo_escolar_id'] ?? 0))
            ->where('asignacion_materia_id', $detalle->asignacion_materia_id)
            ->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        if ($horariosActualesIds->all() !== $horariosSnapshotIds->all()) {
            return 'Los bloques operativos de la carga cambiaron después de aplicar el lote.';
        }

        $versionesSnapshotIds = collect($detalle->versiones_snapshot ?? [])
            ->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $versionesEditablesActualesIds = HorarioVersionDetalle::query()
            ->where('asignacion_materia_id', $detalle->asignacion_materia_id)
            ->whereHas('version', fn ($query) => $query->whereIn('estado', ['propuesta', 'borrador']))
            ->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        if ($versionesEditablesActualesIds->all() !== $versionesSnapshotIds->all()) {
            return 'Las propuestas o borradores de la carga cambiaron después de aplicar el lote.';
        }

        $versionNuevaPosterior = HorarioVersionDetalle::query()
            ->where('asignacion_materia_id', $detalle->asignacion_materia_id)
            ->when(
                $versionesSnapshotIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $versionesSnapshotIds)
            )
            ->where('created_at', '>', $detalle->aplicado_at)
            ->exists();

        if ($versionNuevaPosterior) {
            return 'Se creó una nueva versión de horario después de aplicar el lote.';
        }

        foreach ($detalle->horarios_snapshot ?? [] as $horarioSnapshot) {
            $horario = Horario::query()->find((int) $horarioSnapshot['id']);

            if (! $horario
                || (int) $horario->asignacion_materia_id !== (int) $detalle->asignacion_materia_id
                || (int) ($horario->profesor_id ?? 0) !== (int) ($detalle->profesor_nuevo_id ?? 0)
                || (int) $horario->grupo_id !== (int) ($horarioSnapshot['grupo_id'] ?? 0)
                || (int) $horario->dia_id !== (int) ($horarioSnapshot['dia_id'] ?? 0)
                || (int) $horario->hora_id !== (int) ($horarioSnapshot['hora_id'] ?? 0)) {
                return 'Uno o más bloques operativos cambiaron después de aplicar el lote.';
            }
        }

        foreach ($detalle->versiones_snapshot ?? [] as $versionSnapshot) {
            $versionDetalle = HorarioVersionDetalle::query()
                ->with('version:id,estado')
                ->find((int) $versionSnapshot['id']);

            if (! $versionDetalle
                || ! in_array($versionDetalle->version?->estado, ['propuesta', 'borrador'], true)
                || (int) $versionDetalle->asignacion_materia_id !== (int) $detalle->asignacion_materia_id
                || (int) ($versionDetalle->profesor_id ?? 0) !== (int) ($detalle->profesor_nuevo_id ?? 0)
                || (int) $versionDetalle->grupo_id !== (int) ($versionSnapshot['grupo_id'] ?? 0)
                || (int) $versionDetalle->dia_id !== (int) ($versionSnapshot['dia_id'] ?? 0)
                || (int) $versionDetalle->hora_id !== (int) ($versionSnapshot['hora_id'] ?? 0)) {
                return 'Una propuesta o borrador cambió o fue publicado después de aplicar el lote.';
            }
        }

        return null;
    }

    private function nombrePersona(?Persona $persona): string
    {
        if (! $persona) {
            return 'Sin docente asignado';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ]))) ?: 'Docente';
    }
}
