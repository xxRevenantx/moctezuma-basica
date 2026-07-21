<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\InscripcionCicloAsignacion;
use App\Models\PreinscripcionCiclo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HistorialCicloEscolarService
{
    public const RESULTADOS_CIERRE = [
        'promovido',
        'promovido_nivel',
        'no_promovido',
        'egresado',
        'baja_definitiva',
        'trasladado',
        'baja_temporal_al_cierre',
        'continuidad',
    ];

    public function registrarPreinscripcion(
        Inscripcion $alumno,
        ?int $usuarioId,
        ?string $fecha = null
    ): PreinscripcionCiclo {
        $alumno = $alumno->fresh();
        $fecha = $this->fecha($fecha ?: $alumno->fecha_inscripcion);

        return PreinscripcionCiclo::query()->updateOrCreate(
            [
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => $alumno->ciclo_escolar_id,
            ],
            [
                'nivel_id' => $alumno->nivel_id,
                'grado_id' => $alumno->grado_id,
                'generacion_id' => $alumno->generacion_id,
                'grupo_id' => $alumno->grupo_id,
                'semestre_id' => $alumno->semestre_id,
                'matricula_propuesta' => $alumno->matricula,
                'fecha_preinscripcion' => $fecha,
                'estado' => 'pendiente',
                'formalizada_at' => null,
                'formalizada_por' => null,
                'cancelada_at' => null,
                'cancelada_por' => null,
                'motivo_cancelacion' => null,
                'snapshot' => $this->snapshot($alumno),
            ]
        );
    }

    public function formalizarPreinscripcion(
        Inscripcion $alumno,
        ?int $usuarioId,
        ?string $fecha = null,
        string $motivo = 'Formalización de preinscripción.'
    ): InscripcionCiclo {
        return DB::transaction(function () use ($alumno, $usuarioId, $fecha, $motivo): InscripcionCiclo {
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);
            $fecha = $this->fecha($fecha ?: $alumno->fecha_inscripcion);

            $preinscripcion = PreinscripcionCiclo::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('ciclo_escolar_id', $alumno->ciclo_escolar_id)
                ->lockForUpdate()
                ->first();

            if (! $preinscripcion) {
                $preinscripcion = $this->registrarPreinscripcion($alumno, $usuarioId, $fecha);
            }

            $preinscripcion->update([
                'estado' => 'formalizada',
                'formalizada_at' => now(),
                'formalizada_por' => $usuarioId,
                'snapshot' => array_merge($preinscripcion->snapshot ?? [], [
                    'formalizacion' => $this->snapshot($alumno),
                    'motivo' => $motivo,
                ]),
            ]);

            return $this->asegurarCicloFormal($alumno, 'preinscripcion_formalizada', $usuarioId, $fecha);
        });
    }

    public function cancelarPreinscripcion(
        Inscripcion $alumno,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null
    ): void {
        $preinscripcion = PreinscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('ciclo_escolar_id', $alumno->ciclo_escolar_id)
            ->first();

        if (! $preinscripcion) {
            $preinscripcion = $this->registrarPreinscripcion($alumno, $usuarioId, $fecha);
        }

        $preinscripcion->update([
            'estado' => 'cancelada',
            'cancelada_at' => now(),
            'cancelada_por' => $usuarioId,
            'motivo_cancelacion' => $motivo,
        ]);
    }

    public function asegurarCicloFormal(
        Inscripcion $alumno,
        string $origen,
        ?int $usuarioId,
        ?string $fecha = null
    ): InscripcionCiclo {
        $alumno = $alumno->fresh();

        return $this->asegurarCicloDesdeSnapshot(
            $alumno->id,
            $this->snapshot($alumno),
            $origen,
            $usuarioId,
            $fecha ?: $alumno->fecha_inscripcion
        );
    }

    public function registrarCambioAsignacion(
        Inscripcion $alumno,
        array $antes,
        array $despues,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null,
        ?string $resultadoCambioCiclo = null
    ): InscripcionCiclo {
        return DB::transaction(function () use ($alumno, $antes, $despues, $motivo, $usuarioId, $fecha, $resultadoCambioCiclo): InscripcionCiclo {
            $fecha = $this->fecha($fecha);
            $cicloAnteriorId = (int) ($antes['ciclo_escolar_id'] ?? 0);
            $cicloNuevoId = (int) ($despues['ciclo_escolar_id'] ?? 0);

            if ($cicloAnteriorId > 0 && $cicloAnteriorId !== $cicloNuevoId) {
                $origen = $this->asegurarCicloDesdeSnapshot(
                    $alumno->id,
                    $antes,
                    'reconstruccion_movimiento',
                    $usuarioId,
                    $antes['fecha_inscripcion'] ?? $fecha,
                    true,
                    'alta'
                );

                $resultado = $resultadoCambioCiclo ?: 'continuidad';
                $this->cerrarCiclo(
                    $origen,
                    $resultado,
                    $motivo,
                    $usuarioId,
                    $fecha,
                    in_array($resultado, ['promovido', 'promovido_nivel'], true)
                );

                $destino = $this->asegurarCicloDesdeSnapshot(
                    $alumno->id,
                    $despues,
                    $resultado === 'promovido_nivel' ? 'promocion_nivel' : 'promocion_o_continuidad',
                    $usuarioId,
                    $fecha,
                    false,
                    'exacto'
                );

                $origen->update(['inscripcion_ciclo_destino_id' => $destino->id]);

                return $destino->refresh();
            }

            $ciclo = $this->asegurarCicloDesdeSnapshot(
                $alumno->id,
                $despues,
                'cambio_asignacion',
                $usuarioId,
                $antes['fecha_inscripcion'] ?? $fecha
            );

            $this->cerrarAsignacionActual($ciclo, $fecha);
            $this->crearAsignacion($ciclo, $despues, $this->tipoCambio($antes, $despues), $motivo, $usuarioId, $fecha);
            $ciclo->update($this->camposResumen($despues) + [
                'estatus_actual_ciclo' => $despues['estatus'] ?? $ciclo->estatus_actual_ciclo,
                'snapshot_cierre' => null,
            ]);

            return $ciclo->refresh();
        });
    }

    public function registrarEstatus(
        Inscripcion $alumno,
        string $estatus,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null
    ): ?InscripcionCiclo {
        if ($estatus === 'preinscrito') {
            $this->registrarPreinscripcion($alumno, $usuarioId, $fecha);
            return null;
        }

        return DB::transaction(function () use ($alumno, $estatus, $motivo, $usuarioId, $fecha): InscripcionCiclo {
            $fecha = $this->fecha($fecha);
            $ciclo = $this->asegurarCicloFormal($alumno, 'cambio_estatus', $usuarioId, $fecha);

            $ciclo->update([
                'estatus_actual_ciclo' => $estatus,
            ]);

            $resultado = match ($estatus) {
                'baja_definitiva' => 'baja_definitiva',
                'trasladado', 'traslado' => 'trasladado',
                'egresado' => 'egresado',
                default => null,
            };

            if ($resultado) {
                $this->cerrarCiclo($ciclo, $resultado, $motivo, $usuarioId, $fecha, false);
            }

            return $ciclo->refresh();
        });
    }

    public function cerrarCiclo(
        InscripcionCiclo $ciclo,
        string $resultado,
        string $motivo,
        ?int $usuarioId,
        ?string $fecha = null,
        bool $promovido = false
    ): InscripcionCiclo {
        $fecha = $this->fecha($fecha);
        $this->cerrarAsignacionActual($ciclo, $fecha);

        $ciclo->update([
            'estado' => 'cerrado',
            'fecha_salida' => $fecha,
            'resultado_final' => $resultado,
            'promovido' => $promovido,
            'cerrado_at' => now(),
            'cerrado_por' => $usuarioId,
            'motivo_cierre' => $motivo,
            'snapshot_cierre' => $this->snapshotCiclo($ciclo->fresh()),
        ]);

        return $ciclo->refresh();
    }

    public function cicloActual(Inscripcion $alumno, ?int $cicloEscolarId = null): ?InscripcionCiclo
    {
        return InscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->when($cicloEscolarId, fn ($query) => $query->where('ciclo_escolar_id', $cicloEscolarId))
            ->orderByRaw("CASE WHEN estado = 'en_curso' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();
    }

    public function resolverId(Inscripcion $alumno, ?int $cicloEscolarId = null): ?int
    {
        return $this->cicloActual($alumno, $cicloEscolarId ?: $alumno->ciclo_escolar_id)?->id;
    }

    public function vincularRegistroAcademico(Model $registro): void
    {
        if (! Schema::hasTable('inscripcion_ciclos') || ! Schema::hasColumn($registro->getTable(), 'inscripcion_ciclo_id')) {
            return;
        }

        if ($registro->getAttribute('inscripcion_ciclo_id')) {
            return;
        }

        $inscripcionId = (int) $registro->getAttribute('inscripcion_id');
        $cicloEscolarId = (int) $registro->getAttribute('ciclo_escolar_id');

        if (! $inscripcionId || ! $cicloEscolarId) {
            return;
        }

        $id = InscripcionCiclo::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->value('id');

        if ($id) {
            $registro->setAttribute('inscripcion_ciclo_id', $id);
        }
    }

    private function asegurarCicloDesdeSnapshot(
        int $inscripcionId,
        array $snapshot,
        string $origen,
        ?int $usuarioId,
        mixed $fecha,
        bool $reconstruido = false,
        string $confianza = 'exacto'
    ): InscripcionCiclo {
        $fecha = $this->fecha($fecha);
        $cicloEscolarId = (int) ($snapshot['ciclo_escolar_id'] ?? 0);

        if (! $cicloEscolarId) {
            throw new \InvalidArgumentException('No es posible crear el historial sin ciclo escolar.');
        }

        $ciclo = InscripcionCiclo::query()->firstOrCreate(
            [
                'inscripcion_id' => $inscripcionId,
                'ciclo_escolar_id' => $cicloEscolarId,
            ],
            $this->camposResumen($snapshot) + [
                'fecha_ingreso' => $fecha,
                'estado' => 'en_curso',
                'estatus_ingreso' => $snapshot['estatus'] ?? 'activo',
                'estatus_actual_ciclo' => $snapshot['estatus'] ?? 'activo',
                'snapshot_ingreso' => $snapshot,
                'origen' => $origen,
                'reconstruido' => $reconstruido,
                'nivel_confianza' => $confianza,
            ]
        );

        if (! $ciclo->asignaciones()->exists()) {
            $this->crearAsignacion($ciclo, $snapshot, 'asignacion_inicial', 'Asignación inicial del ciclo.', $usuarioId, $fecha);
        }

        return $ciclo;
    }

    private function crearAsignacion(
        InscripcionCiclo $ciclo,
        array $snapshot,
        string $tipo,
        string $motivo,
        ?int $usuarioId,
        string $fecha
    ): InscripcionCicloAsignacion {
        return $ciclo->asignaciones()->create([
            'nivel_id' => $snapshot['nivel_id'],
            'grado_id' => $snapshot['grado_id'],
            'generacion_id' => $snapshot['generacion_id'],
            'grupo_id' => $snapshot['grupo_id'],
            'semestre_id' => $snapshot['semestre_id'] ?? null,
            'fecha_inicio' => $fecha,
            'fecha_fin' => null,
            'tipo' => $tipo,
            'motivo' => $motivo,
            'es_actual' => true,
            'registrado_por' => $usuarioId,
            'snapshot' => Arr::only($snapshot, [
                'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id', 'matricula', 'estatus',
            ]),
        ]);
    }

    private function cerrarAsignacionActual(InscripcionCiclo $ciclo, string $fecha): void
    {
        $ciclo->asignaciones()
            ->where('es_actual', true)
            ->update([
                'es_actual' => false,
                'fecha_fin' => $fecha,
                'updated_at' => now(),
            ]);
    }

    private function camposResumen(array $snapshot): array
    {
        return [
            'matricula' => $snapshot['matricula'] ?? null,
            'nivel_id' => $snapshot['nivel_id'],
            'grado_id' => $snapshot['grado_id'],
            'generacion_id' => $snapshot['generacion_id'],
            'grupo_id' => $snapshot['grupo_id'],
            'semestre_id' => $snapshot['semestre_id'] ?? null,
        ];
    }

    private function tipoCambio(array $antes, array $despues): string
    {
        if ((int) ($antes['grupo_id'] ?? 0) !== (int) ($despues['grupo_id'] ?? 0)) {
            return 'cambio_grupo';
        }
        if ((int) ($antes['grado_id'] ?? 0) !== (int) ($despues['grado_id'] ?? 0)) {
            return 'cambio_grado';
        }
        if ((int) ($antes['semestre_id'] ?? 0) !== (int) ($despues['semestre_id'] ?? 0)) {
            return 'cambio_semestre';
        }

        return 'correccion_administrativa';
    }

    private function snapshot(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
            'matricula', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id',
            'estatus', 'activo', 'fecha_inscripcion', 'fecha_estatus', 'motivo_estatus', 'fecha_baja', 'motivo_baja',
        ]);
    }

    private function snapshotCiclo(InscripcionCiclo $ciclo): array
    {
        return Arr::only($ciclo->getAttributes(), [
            'matricula', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id',
            'fecha_ingreso', 'fecha_salida', 'estatus_ingreso', 'estatus_actual_ciclo', 'resultado_final', 'promovido',
        ]);
    }

    private function fecha(mixed $fecha = null): string
    {
        return CarbonImmutable::parse($fecha ?: now())->toDateString();
    }
}
