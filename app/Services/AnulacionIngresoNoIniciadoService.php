<?php

namespace App\Services;

use App\Models\CambioAcademico;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MovimientoAlumno;
use App\Models\ProyeccionContinuidad;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AnulacionIngresoNoIniciadoService
{
    /**
     * Registros que prueban que el alumno sí tuvo actividad en el ciclo.
     * Las tablas de auditoría no se incluyen porque no representan asistencia
     * ni evaluación académica.
     *
     * @var array<string, string>
     */
    private const TABLAS_ACTIVIDAD = [
        'calificaciones' => 'calificaciones',
        'calificaciones_campos_formativos' => 'evaluaciones de campos formativos',
        'ficha_descriptivas' => 'fichas descriptivas',
        'asistencias_finales_bachillerato' => 'asistencias finales de bachillerato',
        'decisiones_promocion_oficial' => 'decisiones oficiales de promoción',
        'lugares_preescolar' => 'lugares o reconocimientos de preescolar',
        'bitacora_calificaciones' => 'movimientos de calificaciones',
        'calificacion_correcciones' => 'solicitudes de corrección de calificaciones',
        'alertas_academicas' => 'alertas académicas',
        'riesgo_academico_evaluaciones' => 'evaluaciones de riesgo académico',
        'seguimiento_academico_casos' => 'casos de seguimiento académico',
        'integridad_academica_casos' => 'casos de integridad académica',
        'constancias_traslado' => 'constancias de traslado del ciclo',
    ];

    public function __construct(
        private readonly MatriculaAlumnoService $matriculas,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnosticar(int|Inscripcion $alumno): array
    {
        $alumno = $alumno instanceof Inscripcion
            ? $alumno
            : Inscripcion::withTrashed()->find($alumno);

        if (! $alumno) {
            return $this->diagnosticoBase('No se encontró el alumno.', false);
        }

        $historial = $this->historialObjetivo($alumno);

        return $this->evaluar($alumno, $historial);
    }

    public function anular(
        int $inscripcionId,
        string $motivo,
        string $fechaNotificacion,
        int $usuarioId,
    ): Inscripcion {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_anulacion_ingreso' => 'Escribe un motivo de al menos 10 caracteres.',
            ]);
        }

        try {
            $fechaNotificacion = CarbonImmutable::parse($fechaNotificacion)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'fecha_anulacion_ingreso' => 'La fecha indicada no es válida.',
            ]);
        }

        if ($fechaNotificacion->isAfter(CarbonImmutable::today())) {
            throw ValidationException::withMessages([
                'fecha_anulacion_ingreso' => 'La fecha no puede estar en el futuro.',
            ]);
        }

        return DB::transaction(function () use (
            $inscripcionId,
            $motivo,
            $fechaNotificacion,
            $usuarioId,
        ): Inscripcion {
            $alumno = Inscripcion::withTrashed()
                ->lockForUpdate()
                ->findOrFail($inscripcionId);

            $historial = $this->historialObjetivo($alumno, true);
            $diagnostico = $this->evaluar($alumno, $historial);

            if (! $diagnostico['puede_anular']) {
                throw ValidationException::withMessages([
                    'anulacion_ingreso' => "No se puede anular el ingreso:\n- "
                        .implode("\n- ", $diagnostico['bloqueos']),
                ]);
            }

            /** @var InscripcionCiclo $historial */
            $antesAlumno = $this->snapshotAlumno($alumno);
            $antesHistorial = $this->snapshotHistorial($historial);
            $antesAsignaciones = $historial->asignaciones()
                ->orderBy('id')
                ->get()
                ->map(fn ($asignacion): array => $asignacion->getAttributes())
                ->all();

            $fechaCierre = $fechaNotificacion;
            if ($historial->fecha_ingreso
                && $fechaCierre->lessThan(CarbonImmutable::parse($historial->fecha_ingreso))) {
                $fechaCierre = CarbonImmutable::parse($historial->fecha_ingreso);
            }

            $fechaCierreTexto = $fechaCierre->toDateString();
            $fechaNotificacionTexto = $fechaNotificacion->toDateString();

            $historial->asignaciones()
                ->where('es_actual', true)
                ->update([
                    'es_actual' => false,
                    'fecha_fin' => $fechaCierreTexto,
                    'motivo' => $motivo,
                    'updated_at' => now(),
                ]);

            $historial->forceFill([
                'estado' => InscripcionCiclo::ESTADO_ANULADO,
                'fecha_salida' => $fechaCierreTexto,
                'estatus_ingreso' => 'no_reinscrito',
                'estatus_actual_ciclo' => 'no_reinscrito',
                'resultado_final' => 'no_iniciado',
                'promovido' => false,
                'cerrado_at' => now(),
                'cerrado_por' => $usuarioId,
                'motivo_cierre' => $motivo,
                'inscripcion_ciclo_destino_id' => null,
                'origen' => 'ingreso_no_iniciado_anulado',
            ])->save();

            $despuesHistorial = $this->snapshotHistorial($historial->fresh());
            $historial->forceFill([
                'snapshot_cierre' => [
                    'tipo' => 'anulacion_ingreso_no_iniciado',
                    'fecha_notificacion_familiar' => $fechaNotificacionTexto,
                    'fecha_cierre_efectiva' => $fechaCierreTexto,
                    'motivo' => $motivo,
                    'antes' => $antesHistorial,
                    'despues' => Arr::except($despuesHistorial, ['snapshot_cierre']),
                    'asignaciones_antes' => $antesAsignaciones,
                ],
            ])->saveQuietly();

            $ultimoCicloReal = InscripcionCiclo::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('id', '!=', $historial->id)
                ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO)
                ->orderByDesc('ciclo_escolar_id')
                ->orderByDesc('id')
                ->first();

            $ubicacion = $ultimoCicloReal ?: $historial;

            $alumno->forceFill([
                'matricula' => $ubicacion->matricula ?: $alumno->matricula,
                'ciclo_escolar_id' => $ubicacion->ciclo_escolar_id,
                'nivel_id' => $ubicacion->nivel_id,
                'grado_id' => $ubicacion->grado_id,
                'generacion_id' => $ubicacion->generacion_id,
                'grupo_id' => $ubicacion->grupo_id,
                'semestre_id' => $ubicacion->semestre_id,
                'estatus' => 'no_reinscrito',
                'activo' => false,
                'fecha_estatus' => $fechaNotificacionTexto,
                'motivo_estatus' => $motivo,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'indicador_reingreso' => false,
                'documentacion_reingreso_pendiente' => false,
                'usuario_acceso_activo' => false,
            ])->save();

            $alumno = $alumno->fresh();
            $this->matriculas->cerrarVigentes($alumno, $fechaCierreTexto);
            $this->cancelarPreinscripcionRelacionada(
                $alumno->id,
                $historial->ciclo_escolar_id,
                $motivo,
                $usuarioId,
            );

            $despuesAlumno = $this->snapshotAlumno($alumno);
            $despuesHistorial = $this->snapshotHistorial($historial->fresh());

            CambioAcademico::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $historial->id,
                'generacion_id' => $historial->generacion_id,
                'tipo' => 'anulacion_ingreso_no_iniciado',
                'motivo' => $motivo,
                'datos_anteriores' => [
                    'alumno' => $antesAlumno,
                    'ciclo' => $antesHistorial,
                ],
                'datos_nuevos' => [
                    'alumno' => $despuesAlumno,
                    'ciclo' => $despuesHistorial,
                    'documentos_conservados' => (int) ($diagnostico['documentos_conservados'] ?? 0),
                ],
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            MovimientoAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $historial->id,
                'ciclo_escolar_id' => $historial->ciclo_escolar_id,
                'ciclo_id' => $alumno->ciclo_id,
                'nivel_anterior_id' => $historial->nivel_id,
                'nivel_nuevo_id' => $ultimoCicloReal?->nivel_id,
                'resultado_continuidad' => 'no_iniciado',
                'usuario_acceso_activo' => false,
                'tipo' => 'anulacion_ingreso_no_iniciado',
                'fecha' => $fechaNotificacionTexto,
                'motivo' => $motivo,
                'observaciones' => 'El alumno fue registrado administrativamente, pero no inició actividades. El historial se conservó como anulado/no iniciado y no se registró una baja.',
                'estado_anterior' => $antesAlumno,
                'estado_nuevo' => $despuesAlumno,
                'registrado_por' => $usuarioId,
            ]);

            return $alumno->fresh();
        });
    }

    private function historialObjetivo(Inscripcion $alumno, bool $bloquear = false): ?InscripcionCiclo
    {
        $query = InscripcionCiclo::query()
            ->with([
                'cicloEscolar',
                'nivel',
                'grado',
                'semestre',
                'grupo.asignacionGrupo',
            ])
            ->where('inscripcion_id', $alumno->id)
            ->when(
                $alumno->ciclo_escolar_id,
                fn ($consulta) => $consulta->where('ciclo_escolar_id', $alumno->ciclo_escolar_id),
            )
            ->orderByDesc('id');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluar(Inscripcion $alumno, ?InscripcionCiclo $historial): array
    {
        $bloqueos = collect();
        $actividad = collect();

        if (! $historial) {
            $bloqueos->push('No existe un historial formal del ciclo que pueda anularse.');

            return $this->diagnosticoDesdeColecciones($alumno, null, $bloqueos, $actividad, 0);
        }

        if ($historial->estado === InscripcionCiclo::ESTADO_ANULADO) {
            $bloqueos->push('El ingreso ya está anulado y se conserva como “No inició el ciclo”.');
        } elseif ($historial->estado !== InscripcionCiclo::ESTADO_EN_CURSO) {
            $bloqueos->push('El ciclo ya fue cerrado. Debe conservarse su resultado académico o registrarse la salida correspondiente.');
        }

        if (filled($historial->resultado_final) || (bool) $historial->promovido) {
            $bloqueos->push('El ciclo ya tiene un resultado académico final y no puede tratarse como un ingreso no iniciado.');
        }

        $proyeccion = $this->proyeccionConfirmadaDelDestino($historial);
        if ($proyeccion) {
            $bloqueos->push('Este ciclo proviene de una continuidad confirmada. Utiliza Generales → Confirmación del ciclo destino → Retirar del ciclo destino.');
        }

        if (in_array((string) $historial->origen, [
            'continuidad_confirmada',
            'promocion_nivel',
            'promocion_o_continuidad',
        ], true)) {
            $bloqueos->push('El historial fue creado por una promoción o continuidad. Debe revertirse desde el módulo de continuidad.');
        }

        $otrosVigentes = InscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('id', '!=', $historial->id)
            ->where('estado', InscripcionCiclo::ESTADO_EN_CURSO)
            ->count();

        if ($otrosVigentes > 0) {
            $bloqueos->push('El alumno tiene otra ubicación académica vigente. Revisa su trayectoria antes de anular el ingreso.');
        }

        foreach (self::TABLAS_ACTIVIDAD as $tabla => $etiqueta) {
            $cantidad = $this->contarActividad($tabla, $alumno, $historial);
            if ($cantidad <= 0) {
                continue;
            }

            $actividad->push([
                'tabla' => $tabla,
                'etiqueta' => $etiqueta,
                'cantidad' => $cantidad,
            ]);
            $bloqueos->push("Tiene {$cantidad} registro(s) de {$etiqueta} en el ciclo.");
        }

        if (Schema::hasTable('procesos_cierre_ciclo_detalles')) {
            $consulta = DB::table('procesos_cierre_ciclo_detalles')
                ->where('inscripcion_id', $alumno->id);

            if (Schema::hasColumn('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_origen_id')) {
                $consulta->where(function ($q) use ($historial): void {
                    $q->where('inscripcion_ciclo_origen_id', $historial->id);
                    if (Schema::hasColumn('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_destino_id')) {
                        $q->orWhere('inscripcion_ciclo_destino_id', $historial->id);
                    }
                });
            }

            $procesos = (int) $consulta->count();
            if ($procesos > 0) {
                $bloqueos->push("Tiene {$procesos} registro(s) dentro de un proceso de cierre de ciclo.");
            }
        }

        $documentos = $this->contarDocumentosConservados($alumno, $historial);

        return $this->diagnosticoDesdeColecciones(
            $alumno,
            $historial,
            $bloqueos,
            $actividad,
            $documentos,
            $proyeccion?->id,
        );
    }

    private function contarActividad(string $tabla, Inscripcion $alumno, InscripcionCiclo $historial): int
    {
        if (! Schema::hasTable($tabla)) {
            return 0;
        }

        $consulta = DB::table($tabla);

        if (Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
            return (int) $consulta->where('inscripcion_ciclo_id', $historial->id)->count();
        }

        if (! Schema::hasColumn($tabla, 'inscripcion_id')) {
            return 0;
        }

        $consulta->where('inscripcion_id', $alumno->id);

        if (Schema::hasColumn($tabla, 'ciclo_escolar_id')) {
            $consulta->where('ciclo_escolar_id', $historial->ciclo_escolar_id);
        }

        if (Schema::hasColumn($tabla, 'deleted_at')) {
            $consulta->whereNull('deleted_at');
        }

        return (int) $consulta->count();
    }

    private function contarDocumentosConservados(Inscripcion $alumno, InscripcionCiclo $historial): int
    {
        if (! Schema::hasTable('documentos_alumnos')) {
            return 0;
        }

        $consulta = DB::table('documentos_alumnos')
            ->where('inscripcion_id', $alumno->id);

        if (Schema::hasColumn('documentos_alumnos', 'ciclo_escolar_id')) {
            $consulta->where(function ($q) use ($historial): void {
                $q->where('ciclo_escolar_id', $historial->ciclo_escolar_id)
                    ->orWhereNull('ciclo_escolar_id');
            });
        }

        if (Schema::hasColumn('documentos_alumnos', 'deleted_at')) {
            $consulta->whereNull('deleted_at');
        }

        return (int) $consulta->count();
    }

    private function proyeccionConfirmadaDelDestino(InscripcionCiclo $historial): ?ProyeccionContinuidad
    {
        if (! Schema::hasTable('proyecciones_continuidad')
            || ! Schema::hasColumn('proyecciones_continuidad', 'inscripcion_ciclo_destino_id')) {
            return null;
        }

        return ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('inscripcion_ciclo_destino_id', $historial->id)
            ->where('estado', 'confirmada')
            ->first();
    }

    private function cancelarPreinscripcionRelacionada(
        int $inscripcionId,
        int $cicloEscolarId,
        string $motivo,
        int $usuarioId,
    ): void {
        if (! Schema::hasTable('preinscripciones_ciclos')) {
            return;
        }

        DB::table('preinscripciones_ciclos')
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->update([
                'estado' => 'cancelada',
                'cancelada_at' => now(),
                'cancelada_por' => $usuarioId,
                'motivo_cancelacion' => $motivo,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnosticoDesdeColecciones(
        Inscripcion $alumno,
        ?InscripcionCiclo $historial,
        Collection $bloqueos,
        Collection $actividad,
        int $documentos,
        ?int $proyeccionId = null,
    ): array {
        return [
            'puede_anular' => $bloqueos->isEmpty(),
            'bloqueos' => $bloqueos->unique()->values()->all(),
            'actividad' => $actividad->values()->all(),
            'documentos_conservados' => $documentos,
            'proyeccion_confirmada_id' => $proyeccionId,
            'ya_anulado' => $historial?->estado === InscripcionCiclo::ESTADO_ANULADO,
            'alumno' => trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"),
            'ciclo' => $historial?->cicloEscolar
                ? "{$historial->cicloEscolar->inicio_anio}-{$historial->cicloEscolar->fin_anio}"
                : null,
            'nivel' => $historial?->nivel?->nombre,
            'grado' => $historial?->grado?->nombre,
            'semestre' => $historial?->semestre?->numero,
            'grupo' => $historial?->grupo?->asignacionGrupo?->nombre,
            'estado_ciclo' => $historial?->estado,
            'estatus_ciclo' => $historial?->estatus_actual_ciclo,
            'resultado_ciclo' => $historial?->resultado_final,
            'fecha_ingreso' => $historial?->fecha_ingreso?->format('Y-m-d'),
            'historial_id' => $historial?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnosticoBase(string $mensaje, bool $puede): array
    {
        return [
            'puede_anular' => $puede,
            'bloqueos' => [$mensaje],
            'actividad' => [],
            'documentos_conservados' => 0,
            'proyeccion_confirmada_id' => null,
            'ya_anulado' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotAlumno(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
            'id', 'matricula', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id',
            'grupo_id', 'semestre_id', 'estatus', 'activo', 'fecha_inscripcion', 'fecha_estatus',
            'motivo_estatus', 'fecha_baja', 'motivo_baja', 'observaciones_baja',
            'indicador_reingreso', 'documentacion_reingreso_pendiente', 'usuario_acceso_activo',
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshotHistorial(InscripcionCiclo $historial): array
    {
        return Arr::only($historial->getAttributes(), [
            'id', 'inscripcion_id', 'ciclo_escolar_id', 'matricula', 'nivel_id', 'grado_id',
            'generacion_id', 'grupo_id', 'semestre_id', 'fecha_ingreso', 'fecha_salida', 'estado',
            'estatus_ingreso', 'estatus_actual_ciclo', 'resultado_final', 'promovido', 'cerrado_at',
            'cerrado_por', 'motivo_cierre', 'inscripcion_ciclo_destino_id', 'snapshot_ingreso',
            'snapshot_cierre', 'origen', 'reconstruido', 'nivel_confianza',
        ]);
    }
}
