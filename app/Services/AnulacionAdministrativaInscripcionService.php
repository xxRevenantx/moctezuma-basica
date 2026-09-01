<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CambioAcademico;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MovimientoAlumno;
use App\Models\ProyeccionContinuidad;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Anulación administrativa de una inscripción YA INICIADA.
 *
 * Este flujo es deliberadamente distinto de "No inició el ciclo":
 * - permite que existan calificaciones, asistencias y demás evidencia académica;
 * - NO elimina esas evidencias;
 * - NO registra una baja;
 * - deja el historial del ciclo como ANULADO por error administrativo;
 * - retira al alumno de la matrícula vigente;
 * - conserva auditoría suficiente para saber quién, cuándo y por qué anuló.
 */
class AnulacionAdministrativaInscripcionService
{
    /**
     * Evidencias que se conservan y se muestran como advertencia.
     *
     * @var array<string, string>
     */
    private const TABLAS_EVIDENCIA = [
        'calificaciones' => 'calificaciones',
        'calificaciones_campos_formativos' => 'evaluaciones de campos formativos',
        'ficha_descriptivas' => 'fichas descriptivas',
        'asistencias_finales_bachillerato' => 'asistencias finales de bachillerato',
        'decisiones_promocion_oficial' => 'decisiones oficiales de promoción',
        'lugares_preescolar' => 'lugares o reconocimientos de preescolar',
        'bitacora_calificaciones' => 'movimientos de calificaciones',
        'calificacion_correcciones' => 'solicitudes o correcciones de calificaciones',
        'constancias_traslado' => 'constancias de traslado',
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

        if (!$alumno) {
            return [
                'puede_anular' => false,
                'bloqueos' => ['No se encontró el alumno.'],
                'advertencias' => [],
                'actividad' => [],
                'total_actividad' => 0,
                'historial_id' => null,
                'ya_anulado' => false,
                'proyeccion_confirmada_id' => null,
            ];
        }

        $historial = $this->historialObjetivo($alumno);

        return $this->evaluar($alumno, $historial);
    }

    public function anular(
        int $inscripcionId,
        string $motivo,
        string $fechaAnulacion,
        int $usuarioId,
    ): Inscripcion {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 20) {
            throw ValidationException::withMessages([
                'motivo_anulacion_administrativa' =>
                    'Describe el error administrativo con al menos 20 caracteres.',
            ]);
        }

        try {
            $fecha = CarbonImmutable::parse($fechaAnulacion)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'fecha_anulacion_administrativa' => 'La fecha indicada no es válida.',
            ]);
        }

        if ($fecha->isAfter(CarbonImmutable::today())) {
            throw ValidationException::withMessages([
                'fecha_anulacion_administrativa' => 'La fecha no puede estar en el futuro.',
            ]);
        }

        return DB::transaction(function () use (
            $inscripcionId,
            $motivo,
            $fecha,
            $usuarioId,
        ): Inscripcion {
            $alumno = Inscripcion::withTrashed()
                ->lockForUpdate()
                ->findOrFail($inscripcionId);

            $historial = $this->historialObjetivo($alumno, true);
            $diagnostico = $this->evaluar($alumno, $historial);

            if (!(bool) ($diagnostico['puede_anular'] ?? false)) {
                throw ValidationException::withMessages([
                    'anulacion_administrativa' =>
                        "No se puede realizar la anulación administrativa:\n- "
                        . implode("\n- ", (array) ($diagnostico['bloqueos'] ?? [])),
                ]);
            }

            /** @var InscripcionCiclo $historial */
            $antesAlumno = $this->snapshotAlumno($alumno);
            $antesHistorial = $this->snapshotHistorial($historial);

            $asignacionesAntes = $historial->asignaciones()
                ->orderBy('id')
                ->get()
                ->map(fn ($asignacion): array => $asignacion->getAttributes())
                ->values()
                ->all();

            $fechaCierre = $fecha;

            if (
                $historial->fecha_ingreso
                && $fechaCierre->lessThan(CarbonImmutable::parse($historial->fecha_ingreso))
            ) {
                $fechaCierre = CarbonImmutable::parse($historial->fecha_ingreso);
            }

            $fechaTexto = $fecha->toDateString();
            $fechaCierreTexto = $fechaCierre->toDateString();

            /*
             * Cierra solamente la asignación académica del ciclo que se anula.
             * No elimina el registro ni las evidencias relacionadas.
             */
            $historial->asignaciones()
                ->where('es_actual', true)
                ->update([
                    'es_actual' => false,
                    'fecha_fin' => $fechaCierreTexto,
                    'motivo' => 'Anulación administrativa: ' . $motivo,
                    'updated_at' => now(),
                ]);

            /*
             * Si el ciclo nació de una continuidad confirmada, se conserva la
             * promoción/egreso del ciclo anterior y la proyección queda marcada
             * como revertida por anulación administrativa.
             */
            $proyeccion = $this->proyeccionConfirmadaDelDestino($historial);

            if ($proyeccion) {
                $this->revertirProyeccionPorAnulacion(
                    $proyeccion,
                    $historial,
                    $motivo,
                    $fechaTexto,
                    $usuarioId,
                );
            }

            /*
             * Las proyecciones PENDIENTES que nazcan del ciclo inválido ya no
             * deben quedar disponibles para confirmarse posteriormente.
             */
            $this->cancelarProyeccionesPendientesDeOrigen(
                $historial,
                $motivo,
                $usuarioId,
            );

            $historial->forceFill([
                'estado' => InscripcionCiclo::ESTADO_ANULADO,
                'fecha_salida' => $fechaCierreTexto,
                'estatus_actual_ciclo' => 'inactivo',
                'resultado_final' => 'anulacion_administrativa',
                'promovido' => false,
                'cerrado_at' => now(),
                'cerrado_por' => $usuarioId,
                'motivo_cierre' => $motivo,
                'inscripcion_ciclo_destino_id' => null,
            ])->save();

            $despuesHistorial = $this->snapshotHistorial($historial->fresh());

            $historial->forceFill([
                'snapshot_cierre' => [
                    'tipo' => 'anulacion_administrativa_inscripcion',
                    'fecha_anulacion' => $fechaTexto,
                    'fecha_cierre_efectiva' => $fechaCierreTexto,
                    'motivo' => $motivo,
                    'actividad_academica_conservada' => (array) ($diagnostico['actividad'] ?? []),
                    'total_actividad_conservada' => (int) ($diagnostico['total_actividad'] ?? 0),
                    'proyeccion_continuidad_revertida_id' => $proyeccion?->id,
                    'antes' => $antesHistorial,
                    'despues' => Arr::except($despuesHistorial, ['snapshot_cierre']),
                    'asignaciones_antes' => $asignacionesAntes,
                ],
            ])->saveQuietly();

            /*
             * Regresa los punteros administrativos del alumno al último ciclo
             * válido anterior. Si no existe, conserva la ubicación como referencia,
             * pero el alumno queda inactivo y fuera de matrícula vigente.
             */
            $ultimoCicloValido = InscripcionCiclo::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('id', '!=', $historial->id)
                ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO)
                ->orderByDesc('ciclo_escolar_id')
                ->orderByDesc('id')
                ->first();

            $ubicacion = $ultimoCicloValido ?: $historial;
            $estatusRestaurado = $this->estatusPosteriorAnulacion($ultimoCicloValido);

            $alumno->forceFill([
                'matricula' => $ubicacion->matricula ?: $alumno->matricula,
                'ciclo_escolar_id' => $ubicacion->ciclo_escolar_id,
                'nivel_id' => $ubicacion->nivel_id,
                'grado_id' => $ubicacion->grado_id,
                'generacion_id' => $ubicacion->generacion_id,
                'grupo_id' => $ubicacion->grupo_id,
                'semestre_id' => $ubicacion->semestre_id,
                'estatus' => $estatusRestaurado,
                'activo' => false,
                'fecha_estatus' => $fechaTexto,
                'motivo_estatus' => 'Anulación administrativa de inscripción. ' . $motivo,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'indicador_reingreso' => false,
                'documentacion_reingreso_pendiente' => false,
                'usuario_acceso_activo' => false,
            ])->save();

            $alumno = $alumno->fresh();

            // Al quedar sin inscripción válida vigente, la matrícula operativa se cierra.
            $this->matriculas->cerrarVigentes($alumno, $fechaCierreTexto);

            $despuesAlumno = $this->snapshotAlumno($alumno);
            $despuesHistorial = $this->snapshotHistorial($historial->fresh());

            CambioAcademico::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $historial->id,
                'generacion_id' => $historial->generacion_id,
                'tipo' => 'anulacion_administrativa_inscripcion',
                'motivo' => $motivo,
                'datos_anteriores' => [
                    'alumno' => $antesAlumno,
                    'ciclo' => $antesHistorial,
                ],
                'datos_nuevos' => [
                    'alumno' => $despuesAlumno,
                    'ciclo' => $despuesHistorial,
                    'actividad_academica_conservada' => (array) ($diagnostico['actividad'] ?? []),
                    'total_actividad_conservada' => (int) ($diagnostico['total_actividad'] ?? 0),
                    'proyeccion_continuidad_revertida_id' => $proyeccion?->id,
                    'es_baja' => false,
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
                'nivel_nuevo_id' => $ultimoCicloValido?->nivel_id,
                'resultado_continuidad' => 'anulacion_administrativa',
                'usuario_acceso_activo' => false,
                'tipo' => 'anulacion_administrativa_inscripcion',
                'fecha' => $fechaTexto,
                'motivo' => $motivo,
                'observaciones' =>
                    'Inscripción anulada por error administrativo. '
                    . 'El alumno sí había iniciado el ciclo; por ello se conservaron '
                    . 'las evidencias académicas y documentales para auditoría. '
                    . 'La operación no se registró como baja.',
                'estado_anterior' => $antesAlumno,
                'estado_nuevo' => $despuesAlumno,
                'registrado_por' => $usuarioId,
            ]);

            return $alumno->fresh();
        });
    }

    private function historialObjetivo(
        Inscripcion $alumno,
        bool $bloquear = false,
    ): ?InscripcionCiclo {
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
                fn ($consulta) =>
                    $consulta->where('ciclo_escolar_id', $alumno->ciclo_escolar_id),
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
    private function evaluar(
        Inscripcion $alumno,
        ?InscripcionCiclo $historial,
    ): array {
        $bloqueos = collect();
        $advertencias = collect();
        $actividad = collect();

        if (!$historial) {
            $bloqueos->push(
                'No existe un historial formal del ciclo que pueda anularse.'
            );

            return $this->diagnosticoDesdeColecciones(
                $alumno,
                null,
                $bloqueos->all(),
                $advertencias->all(),
                $actividad->all(),
                null,
            );
        }

        if ($alumno->trashed()) {
            $bloqueos->push(
                'El expediente está archivado. Restáuralo antes de realizar una anulación administrativa.'
            );
        }

        if ($historial->estado === InscripcionCiclo::ESTADO_ANULADO) {
            $bloqueos->push('La inscripción de este ciclo ya se encuentra anulada.');
        } elseif ($historial->estado !== InscripcionCiclo::ESTADO_EN_CURSO) {
            $bloqueos->push(
                'El ciclo ya tiene un cierre formal. La anulación administrativa desde Matrícula solo se permite mientras el ciclo está en curso.'
            );
        }

        if (filled($historial->resultado_final) || (bool) $historial->promovido) {
            $bloqueos->push(
                'El historial ya tiene un resultado académico final. Debe corregirse desde el proceso de cierre correspondiente.'
            );
        }

        $otrosVigentes = InscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('id', '!=', $historial->id)
            ->where('estado', InscripcionCiclo::ESTADO_EN_CURSO)
            ->count();

        if ($otrosVigentes > 0) {
            $bloqueos->push(
                'El alumno tiene otra inscripción de ciclo en curso. Revisa primero su trayectoria para evitar anular el ciclo equivocado.'
            );
        }

        $proyeccionesPosterioresConfirmadas = ProyeccionContinuidad::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('inscripcion_ciclo_origen_id', $historial->id)
            ->where('estado', 'confirmada')
            ->count();

        if ($proyeccionesPosterioresConfirmadas > 0) {
            $bloqueos->push(
                'Este ciclo ya tiene una continuidad posterior confirmada. Revierte primero el ciclo posterior antes de anular administrativamente este ciclo.'
            );
        }

        foreach (self::TABLAS_EVIDENCIA as $tabla => $etiqueta) {
            $cantidad = $this->contarEvidencia($tabla, $alumno, $historial);

            if ($cantidad <= 0) {
                continue;
            }

            $actividad->push([
                'tabla' => $tabla,
                'etiqueta' => $etiqueta,
                'cantidad' => $cantidad,
            ]);
        }

        if ($actividad->isNotEmpty()) {
            $advertencias->push(
                'El alumno tiene actividad académica registrada. La anulación administrativa NO la eliminará; quedará vinculada al ciclo anulado como evidencia de auditoría.'
            );
        }

        $proyeccion = $this->proyeccionConfirmadaDelDestino($historial);

        if ($proyeccion) {
            $advertencias->push(
                'La inscripción proviene de una continuidad confirmada. La promoción o egreso del ciclo anterior se conservará y la proyección de este ciclo se marcará como revertida por anulación administrativa.'
            );
        }

        $advertencias->push(
            'Esta operación no es una baja y no debe utilizarse cuando el alumno simplemente deja de asistir. Úsala únicamente para corregir una inscripción que nunca debió existir administrativamente.'
        );

        return $this->diagnosticoDesdeColecciones(
            $alumno,
            $historial,
            $bloqueos->all(),
            $advertencias->all(),
            $actividad->all(),
            $proyeccion?->id,
        );
    }

    /**
     * @param array<int, string> $bloqueos
     * @param array<int, string> $advertencias
     * @param array<int, array<string, mixed>> $actividad
     * @return array<string, mixed>
     */
    private function diagnosticoDesdeColecciones(
        Inscripcion $alumno,
        ?InscripcionCiclo $historial,
        array $bloqueos,
        array $advertencias,
        array $actividad,
        ?int $proyeccionConfirmadaId,
    ): array {
        return [
            'puede_anular' => $historial !== null && $bloqueos === [],
            'bloqueos' => $bloqueos,
            'advertencias' => $advertencias,
            'actividad' => $actividad,
            'total_actividad' => collect($actividad)->sum('cantidad'),
            'historial_id' => $historial?->id,
            'ya_anulado' =>
                $historial?->estado === InscripcionCiclo::ESTADO_ANULADO,
            'proyeccion_confirmada_id' => $proyeccionConfirmadaId,
            'ciclo' => $historial?->cicloEscolar?->nombre,
            'nivel' => $historial?->nivel?->nombre,
            'grado' => $historial?->grado?->nombre,
            'semestre' => $historial?->semestre?->numero,
            'grupo' =>
                $historial?->grupo?->asignacionGrupo?->nombre
                ?? $historial?->grupo?->nombre,
            'alumno' => trim(implode(' ', array_filter([
                $alumno->nombre,
                $alumno->apellido_paterno,
                $alumno->apellido_materno,
            ], fn ($valor) => filled($valor)))),
        ];
    }

    private function contarEvidencia(
        string $tabla,
        Inscripcion $alumno,
        InscripcionCiclo $historial,
    ): int {
        if (!Schema::hasTable($tabla)) {
            return 0;
        }

        $consulta = DB::table($tabla);

        if (Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
            return (int) $consulta
                ->where('inscripcion_ciclo_id', $historial->id)
                ->count();
        }

        if (!Schema::hasColumn($tabla, 'inscripcion_id')) {
            return 0;
        }

        $consulta->where('inscripcion_id', $alumno->id);

        if (Schema::hasColumn($tabla, 'ciclo_escolar_id')) {
            $consulta->where(
                'ciclo_escolar_id',
                $historial->ciclo_escolar_id
            );
        }

        return (int) $consulta->count();
    }

        private function proyeccionConfirmadaDelDestino(
        InscripcionCiclo $historial,
    ): ?ProyeccionContinuidad {
        if (!Schema::hasTable('proyecciones_continuidad')) {
            return null;
        }

        /*
         * Primera opción: vínculo histórico exacto.
         */
        $exacta = ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('inscripcion_ciclo_destino_id', $historial->id)
            ->where('estado', 'confirmada')
            ->orderByDesc('id')
            ->first();

        if ($exacta) {
            return $exacta;
        }

        /*
         * Fallback para datos legacy o proyecciones donde el vínculo al
         * inscripcion_ciclo_destino_id quedó incompleto. Se limita al mismo
         * alumno, ciclo y ubicación proyectada para evitar asociar otra
         * continuidad.
         */
        return ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('estado', 'confirmada')
            ->where('ciclo_destino_id', $historial->ciclo_escolar_id)
            ->where('nivel_destino_id', $historial->nivel_id)
            ->when(
                $historial->grado_id,
                fn ($query) => $query->where('grado_destino_id', $historial->grado_id)
            )
            ->when(
                $historial->semestre_id,
                fn ($query) => $query->where('semestre_destino_id', $historial->semestre_id)
            )
            ->orderByDesc('id')
            ->first();
    }

    private function revertirProyeccionPorAnulacion(
        ProyeccionContinuidad $proyeccion,
        InscripcionCiclo $historial,
        string $motivo,
        string $fecha,
        int $usuarioId,
    ): void {
        $antes = $proyeccion->getAttributes();

        $cambios = ['estado' => 'revertida'];

        $opcionales = [
            'revertida_at' => now(),
            'revertida_por' => $usuarioId,
            'fecha_reversion' => $fecha,
            'tipo_reversion' => 'anulacion_administrativa',
            'motivo_reversion' => $motivo,
            'snapshot_reversion' => [
                'tipo' => 'anulacion_administrativa_inscripcion',
                'inscripcion_ciclo_destino_id' => $historial->id,
                'estado_anterior' => $antes,
                'motivo' => $motivo,
                'fecha' => $fecha,
            ],
        ];

        foreach ($opcionales as $columna => $valor) {
            if (Schema::hasColumn('proyecciones_continuidad', $columna)) {
                $cambios[$columna] = $valor;
            }
        }

        $proyeccion->forceFill($cambios)->save();

        if (
            $proyeccion->proceso_cierre_ciclo_detalle_id
            && Schema::hasTable('procesos_cierre_ciclo_detalles')
        ) {
            $detalleCambios = [];

            foreach ([
                'revertido_at' => now(),
                'revertido_por' => $usuarioId,
                'motivo_reversion' => $motivo,
                'reversion_estado' => 'anulacion_administrativa',
            ] as $columna => $valor) {
                if (Schema::hasColumn('procesos_cierre_ciclo_detalles', $columna)) {
                    $detalleCambios[$columna] = $valor;
                }
            }

            if ($detalleCambios !== []) {
                DB::table('procesos_cierre_ciclo_detalles')
                    ->where('id', $proyeccion->proceso_cierre_ciclo_detalle_id)
                    ->update(array_merge($detalleCambios, [
                        'updated_at' => now(),
                    ]));
            }
        }
    }

    private function cancelarProyeccionesPendientesDeOrigen(
        InscripcionCiclo $historial,
        string $motivo,
        int $usuarioId,
    ): void {
        if (!Schema::hasTable('proyecciones_continuidad')) {
            return;
        }

        $proyecciones = ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('inscripcion_ciclo_origen_id', $historial->id)
            ->where('estado', 'pendiente')
            ->lockForUpdate()
            ->get();

        foreach ($proyecciones as $proyeccion) {
            $cambios = [
                'estado' => 'cancelada',
            ];

            foreach ([
                'cancelada_at' => now(),
                'cancelada_por' => $usuarioId,
                'motivo_cancelacion' =>
                    'Cancelada por anulación administrativa del ciclo origen. '
                    . $motivo,
                'snapshot_cancelacion' => [
                    'tipo' => 'anulacion_administrativa_ciclo_origen',
                    'estado_anterior' => $proyeccion->getAttributes(),
                    'motivo' => $motivo,
                ],
            ] as $columna => $valor) {
                if (Schema::hasColumn('proyecciones_continuidad', $columna)) {
                    $cambios[$columna] = $valor;
                }
            }

            $proyeccion->forceFill($cambios)->save();
        }
    }

    private function estatusPosteriorAnulacion(
        ?InscripcionCiclo $ultimoCicloValido,
    ): string {
        if (!$ultimoCicloValido) {
            return 'inactivo';
        }

        $resultado = (string) ($ultimoCicloValido->resultado_final ?? '');

        return match ($resultado) {
            'egresado' => 'egresado',
            'baja_definitiva' => 'baja_definitiva',
            'trasladado', 'traslado' => 'trasladado',
            'promovido',
            'promovido_grado',
            'promovido_nivel',
            'continuidad',
            'no_promovido' => 'no_reinscrito',
            default => 'inactivo',
        };
    }

    /** @return array<string, mixed> */
    private function snapshotAlumno(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
            'id',
            'matricula',
            'ciclo_escolar_id',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'grupo_id',
            'semestre_id',
            'estatus',
            'activo',
            'fecha_estatus',
            'motivo_estatus',
            'fecha_baja',
            'motivo_baja',
            'indicador_reingreso',
            'documentacion_reingreso_pendiente',
            'usuario_acceso_activo',
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshotHistorial(InscripcionCiclo $historial): array
    {
        return Arr::only($historial->getAttributes(), [
            'id',
            'inscripcion_id',
            'ciclo_escolar_id',
            'matricula',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'grupo_id',
            'semestre_id',
            'fecha_ingreso',
            'fecha_salida',
            'estado',
            'estatus_ingreso',
            'estatus_actual_ciclo',
            'resultado_final',
            'promovido',
            'cerrado_at',
            'cerrado_por',
            'motivo_cierre',
            'inscripcion_ciclo_destino_id',
            'origen',
            'reconstruido',
            'nivel_confianza',
            'snapshot_ingreso',
            'snapshot_cierre',
        ]);
    }
}
