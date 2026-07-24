<?php

namespace App\Services;

use App\Models\CambioAcademico;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MovimientoAlumno;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GestionAcademicaService
{
    public function __construct(
        private readonly MatriculaAlumnoService $matriculas,
        private readonly HistorialCicloEscolarService $historialCiclos,
    ) {}

    public const ESTATUS = [
        'activo',
        'preinscrito',
        'baja_temporal',
        'baja_definitiva',
        'trasladado',
        'suspendido',
        'egresado',
        'inactivo',
        'reingreso',
        'no_promovido',
    ];

    public function cambiarAsignacion(Inscripcion $alumno, array $destino, string $motivo, ?int $usuarioId, ?string $resultadoCambioCiclo = null, ?string $fecha = null): Inscripcion
    {
        return DB::transaction(function () use ($alumno, $destino, $motivo, $usuarioId, $resultadoCambioCiclo, $fecha): Inscripcion {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);
            $antes = $this->snapshot($alumno);

            $generacion = Generacion::query()->findOrFail((int) $destino['generacion_id']);
            if ((int) $generacion->nivel_id !== (int) $destino['nivel_id']) {
                throw ValidationException::withMessages(['generacion_id' => 'La generación no pertenece al nivel seleccionado.']);
            }

            if (!$generacion->status) {
                throw ValidationException::withMessages(['generacion_id' => 'No se puede asignar un alumno a una generación inactiva.']);
            }

            $grupo = Grupo::query()->findOrFail((int) $destino['grupo_id']);
            $cicloDestinoId = (int) ($destino['ciclo_escolar_id'] ?? $grupo->ciclo_escolar_id ?? $alumno->ciclo_escolar_id);
            $destino['ciclo_escolar_id'] = $cicloDestinoId ?: null;

            $valido = $cicloDestinoId > 0
                && (int) $grupo->ciclo_escolar_id === $cicloDestinoId
                && $grupo->estado === 'activo'
                && (int) $grupo->nivel_id === (int) $destino['nivel_id']
                && (int) $grupo->generacion_id === (int) $destino['generacion_id']
                && (int) $grupo->grado_id === (int) $destino['grado_id']
                && (int) ($grupo->semestre_id ?? 0) === (int) ($destino['semestre_id'] ?? 0);

            if (!$valido) {
                throw ValidationException::withMessages(['grupo_id' => 'El grupo no corresponde al ciclo escolar, generación, grado y semestre seleccionados, o está inactivo.']);
            }

            $alumno->update(Arr::only($destino, [
                'ciclo_escolar_id',
                'nivel_id',                'grado_id',
                'generacion_id',
                'grupo_id',
                'semestre_id',
                'matricula',
            ]));

            $alumno = $alumno->fresh();
            $despues = $this->snapshot($alumno);
            $this->matriculas->sincronizarCambioAsignacion($alumno, $antes, $despues, $usuarioId, $fecha);
            $this->historialCiclos->registrarCambioAsignacion($alumno, $antes, $despues, $motivo, $usuarioId, $fecha, $resultadoCambioCiclo);
            $this->registrarCambio($alumno, 'cambio_asignacion', $motivo, $antes, $despues, $usuarioId);
            $this->registrarMovimiento($alumno, 'cambio_asignacion', $motivo, $antes, $despues, $usuarioId, $fecha);

            return $alumno->fresh();
        });
    }

    public function promoverAlumno(
        Inscripcion $alumno,
        array $destino,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null
    ): Inscripcion {
        $resultado = (int) $alumno->nivel_id === (int) ($destino['nivel_id'] ?? $alumno->nivel_id)
            ? 'promovido'
            : 'promovido_nivel';

        $destino['estatus'] = 'activo';

        $actualizado = $this->cambiarAsignacion(
            $alumno,
            $destino,
            $motivo,
            $usuarioId,
            $resultado,
            $fecha
        );

        if (($actualizado->estatus ?? 'activo') !== 'activo') {
            $actualizado = $this->cambiarEstatus(
                $actualizado,
                'activo',
                'Activación en el ciclo destino. ' . $motivo,
                $usuarioId,
                $fecha
            );
        }

        return $actualizado;
    }

    public function continuarNoPromovido(
        Inscripcion $alumno,
        array $destino,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null
    ): Inscripcion {
        $actualizado = $this->cambiarAsignacion(
            $alumno,
            $destino,
            $motivo,
            $usuarioId,
            'no_promovido',
            $fecha
        );

        return $this->cambiarEstatus(
            $actualizado,
            'no_promovido',
            'Continuidad en el mismo grado o semestre. '.$motivo,
            $usuarioId,
            $fecha
        );
    }

    public function activarPreinscripcion(
        Inscripcion $alumno,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null
    ): Inscripcion {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            throw ValidationException::withMessages([
                'motivo_activacion' => 'Escribe un motivo de activación de al menos 5 caracteres.',
            ]);
        }

        return DB::transaction(function () use ($alumno, $motivo, $usuarioId, $fecha): Inscripcion {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);

            if ($alumno->trashed()) {
                throw ValidationException::withMessages([
                    'alumno' => 'Primero restaura el expediente antes de activar la inscripción.',
                ]);
            }

            if (($alumno->estatus ?? 'activo') !== 'preinscrito') {
                throw ValidationException::withMessages([
                    'estatus' => 'Solo se pueden activar alumnos que estén en estatus preinscrito.',
                ]);
            }

            $camposRequeridos = [
                'matricula' => $alumno->matricula,
                'ciclo_escolar_id' => $alumno->ciclo_escolar_id,
                'nivel_id' => $alumno->nivel_id,
                'grado_id' => $alumno->grado_id,
                'generacion_id' => $alumno->generacion_id,
                'grupo_id' => $alumno->grupo_id,
            ];

            if (collect($camposRequeridos)->contains(fn ($valor) => blank($valor))) {
                throw ValidationException::withMessages([
                    'alumno' => 'La preinscripción está incompleta. Revisa matrícula, ciclo escolar, nivel, grado, generación y grupo.',
                ]);
            }

            $generacion = Generacion::query()->find((int) $alumno->generacion_id);

            if (! $generacion || ! $generacion->status) {
                throw ValidationException::withMessages([
                    'generacion_id' => 'La generación asignada está inactiva. Corrige la asignación antes de activar.',
                ]);
            }

            $grupo = Grupo::query()->find((int) $alumno->grupo_id);
            $grupoValido = $grupo
                && ($grupo->estado ?? 'activo') === 'activo'
                && (int) $grupo->ciclo_escolar_id === (int) $alumno->ciclo_escolar_id
                && (int) $grupo->nivel_id === (int) $alumno->nivel_id
                && (int) $grupo->grado_id === (int) $alumno->grado_id
                && (int) $grupo->generacion_id === (int) $alumno->generacion_id
                && (int) ($grupo->semestre_id ?? 0) === (int) ($alumno->semestre_id ?? 0);

            if (! $grupoValido) {
                throw ValidationException::withMessages([
                    'grupo_id' => 'El grupo asignado ya no está activo o no coincide con la ubicación académica del alumno.',
                ]);
            }

            $antes = $this->snapshot($alumno);
            $fechaMovimiento = $fecha ?: now()->toDateString();

            $alumno->update([
                'estatus' => 'activo',
                'activo' => true,
                'fecha_estatus' => $fechaMovimiento,
                'motivo_estatus' => $motivo,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'indicador_reingreso' => false,
                'documentacion_reingreso_pendiente' => false,
                'usuario_acceso_activo' => true,
            ]);

            $alumno = $alumno->fresh();
            $this->matriculas->asegurarVigente($alumno, 'activacion_preinscripcion', $usuarioId, $fechaMovimiento);
            $this->historialCiclos->formalizarPreinscripcion($alumno, $usuarioId, $fechaMovimiento, $motivo);
            $despues = $this->snapshot($alumno);

            $this->registrarCambio(
                $alumno,
                'activacion_preinscripcion',
                $motivo,
                $antes,
                $despues,
                $usuarioId
            );

            $this->registrarMovimiento(
                $alumno,
                'activacion_preinscripcion',
                $motivo,
                $antes,
                $despues,
                $usuarioId,
                $fechaMovimiento
            );

            return $alumno->fresh();
        });
    }

    public function cambiarEstatus(Inscripcion $alumno, string $estatus, string $motivo, ?int $usuarioId, ?string $fecha = null): Inscripcion
    {
        if (!in_array($estatus, self::ESTATUS, true)) {
            throw ValidationException::withMessages(['estatus' => 'El estatus seleccionado no es válido.']);
        }

        return DB::transaction(function () use ($alumno, $estatus, $motivo, $usuarioId, $fecha): Inscripcion {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);
            $antes = $this->snapshot($alumno);
            $esActivo = in_array($estatus, Inscripcion::ESTATUS_ACTIVOS, true);
            $esBajaAdministrativa = in_array($estatus, Inscripcion::ESTATUS_BAJA_ADMINISTRATIVA, true);
            $fechaMovimiento = $fecha ?: now()->toDateString();

            $alumno->update([
                'estatus' => $estatus,
                'activo' => $esActivo,
                'fecha_estatus' => $fechaMovimiento,
                'motivo_estatus' => $motivo,
                // Egreso, preinscripción u otros estados no activos no son una baja.
                'fecha_baja' => $esBajaAdministrativa ? $fechaMovimiento : null,
                'motivo_baja' => $esBajaAdministrativa ? $motivo : null,
                'indicador_reingreso' => $estatus === 'reingreso',
                'tipo_ultimo_ingreso' => $estatus === 'reingreso' ? 'reingreso' : $alumno->tipo_ultimo_ingreso,
                'fecha_ultimo_ingreso' => $estatus === 'reingreso' ? $fechaMovimiento : $alumno->fecha_ultimo_ingreso,
            ]);

            $alumno = $alumno->fresh();
            $this->matriculas->aplicarEstatus($alumno, $estatus, $usuarioId, $fechaMovimiento);
            if (($antes['estatus'] ?? null) === 'preinscrito' && ! in_array($estatus, ['activo', 'reingreso', 'no_promovido'], true)) {
                $this->historialCiclos->cancelarPreinscripcion($alumno, $motivo, $usuarioId, $fechaMovimiento);
            } else {
                $this->historialCiclos->registrarEstatus($alumno, $estatus, $motivo, $usuarioId, $fechaMovimiento);
            }
            $despues = $this->snapshot($alumno);
            $this->registrarCambio($alumno, 'cambio_estatus', $motivo, $antes, $despues, $usuarioId);
            $this->registrarMovimiento($alumno, $estatus, $motivo, $antes, $despues, $usuarioId, $fechaMovimiento);

            return $alumno->fresh();
        });
    }

    public function desactivarGeneracion(Generacion $generacion, string $motivo, bool $egresarActivos, ?int $usuarioId): int
    {
        return DB::transaction(function () use ($generacion, $motivo, $egresarActivos, $usuarioId): int {
            $generacion = Generacion::query()->lockForUpdate()->findOrFail($generacion->id);
            $antes = $this->snapshotGeneracion($generacion);
            $afectados = 0;

            if ($egresarActivos) {
                $alumnos = Inscripcion::query()
                    ->where('generacion_id', $generacion->id)
                    ->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])
                    ->lockForUpdate()->get();
                foreach ($alumnos as $alumno) {
                    $this->cambiarEstatus($alumno, 'egresado', 'Egreso por finalización de la generación ' . $generacion->etiqueta . '. ' . $motivo, $usuarioId);
                    $afectados++;
                }
            }

            $generacion->update([
                'status' => false,
                'cerrada_at' => now(),
                'cerrada_por' => $usuarioId,
                'motivo_desactivacion' => $motivo,
                'observaciones' => $motivo,
            ]);

            CambioAcademico::query()->create([
                'generacion_id' => $generacion->id,
                'tipo' => $egresarActivos ? 'finalizacion_generacion' : 'desactivacion_generacion',
                'motivo' => $motivo,
                'datos_anteriores' => $antes,
                'datos_nuevos' => $this->snapshotGeneracion($generacion->fresh()),
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            return $afectados;
        });
    }

    public function reactivarGeneracion(
        Generacion $generacion,
        string $motivo,
        ?int $usuarioId,
        bool $reactivarEgresados = false
    ): int {
        return DB::transaction(function () use ($generacion, $motivo, $usuarioId, $reactivarEgresados): int {
            $generacion = Generacion::query()->lockForUpdate()->findOrFail($generacion->id);
            $antes = $this->snapshotGeneracion($generacion);

            $generacion->update([
                'status' => true,
                'cerrada_at' => null,
                'cerrada_por' => null,
                'reactivada_at' => now(),
                'reactivada_por' => $usuarioId,
                'motivo_desactivacion' => null,
                'observaciones' => $motivo,
            ]);

            $afectados = 0;

            if ($reactivarEgresados) {
                $egresados = Inscripcion::query()
                    ->where('generacion_id', $generacion->id)
                    ->where('estatus', 'egresado')
                    ->lockForUpdate()
                    ->get();

                foreach ($egresados as $alumno) {
                    $actualizado = $this->cambiarEstatus(
                        $alumno,
                        'activo',
                        'Reactivación administrativa para correcciones de la generación '
                        . $generacion->etiqueta . '. ' . $motivo,
                        $usuarioId
                    );

                    $actualizado->forceFill([
                        'observaciones_baja' => null,
                    ])->save();

                    $afectados++;
                }
            }

            CambioAcademico::query()->create([
                'generacion_id' => $generacion->id,
                'tipo' => $reactivarEgresados
                    ? 'reactivacion_generacion_con_egresados'
                    : 'reactivacion_generacion',
                'motivo' => $motivo,
                'datos_anteriores' => $antes,
                'datos_nuevos' => array_merge(
                    $this->snapshotGeneracion($generacion->fresh()),
                    ['alumnos_egresados_reactivados' => $afectados]
                ),
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            return $afectados;
        });
    }

    public function registrarInscripcionInicial(
        Inscripcion $alumno,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null
    ): void {
        $alumno = $alumno->fresh();
        $this->historialCiclos->asegurarCicloFormal($alumno, 'inscripcion_inicial', $usuarioId, $fecha);
        $despues = $this->snapshot($alumno);

        $this->registrarCambio(
            $alumno,
            'inscripcion_inicial',
            $motivo,
            null,
            $despues,
            $usuarioId
        );

        $this->registrarMovimiento(
            $alumno,
            'inscripcion_inicial',
            $motivo,
            [],
            $despues,
            $usuarioId,
            $fecha ?: now()->toDateString()
        );
    }

    public function registrarCambio(Inscripcion $alumno, string $tipo, string $motivo, ?array $antes, ?array $despues, ?int $usuarioId): CambioAcademico
    {
        return CambioAcademico::query()->create([
            'inscripcion_id' => $alumno->id,
            'inscripcion_ciclo_id' => $this->historialCiclos->resolverId($alumno, (int) ($despues['ciclo_escolar_id'] ?? $antes['ciclo_escolar_id'] ?? $alumno->ciclo_escolar_id)),
            'generacion_id' => $alumno->generacion_id,
            'tipo' => $tipo,
            'motivo' => $motivo,
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'realizado_por' => $usuarioId,
            'realizado_at' => now(),
        ]);
    }

    public function snapshot(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
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
            'fecha_inscripcion',
            'indicador_reingreso',
            'tipo_ultimo_ingreso',
            'fecha_ultimo_ingreso',
            'documentacion_reingreso_pendiente',
            'usuario_acceso_activo',
            'deleted_at',
        ]);
    }

    private function snapshotGeneracion(Generacion $generacion): array
    {
        return Arr::only($generacion->getAttributes(), [
            'nombre',
            'nivel_id',
            'anio_ingreso',
            'anio_egreso',
            'status',
            'fecha_inicio',
            'fecha_termino',
            'cerrada_at',
            'cerrada_por',
            'motivo_desactivacion',
        ]);
    }

    private function registrarMovimiento(Inscripcion $alumno, string $tipo, string $motivo, array $antes, array $despues, ?int $usuarioId, ?string $fecha = null): void
    {
        if (!$usuarioId) {
            return;
        }

        MovimientoAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'inscripcion_ciclo_id' => $this->historialCiclos->resolverId($alumno, (int) ($despues['ciclo_escolar_id'] ?? $antes['ciclo_escolar_id'] ?? $alumno->ciclo_escolar_id)),
            'ciclo_escolar_id' => $despues['ciclo_escolar_id'] ?? $antes['ciclo_escolar_id'] ?? null,
            'ciclo_id' => $alumno->ciclo_id,
            'nivel_anterior_id' => $antes['nivel_id'] ?? null,
            'nivel_nuevo_id' => $despues['nivel_id'] ?? null,
            'usuario_acceso_activo' => $despues['usuario_acceso_activo'] ?? null,
            'tipo' => $tipo,
            'fecha' => $fecha ?: now()->toDateString(),
            'motivo' => $motivo,
            'observaciones' => 'Cambio administrativo de generación, ubicación o estatus.',
            'estado_anterior' => $antes,
            'estado_nuevo' => $despues,
            'registrado_por' => $usuarioId,
        ]);
    }
}
