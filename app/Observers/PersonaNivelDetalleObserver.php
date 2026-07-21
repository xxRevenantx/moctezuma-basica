<?php

namespace App\Observers;

use App\Models\PersonaNivelDetalle;
use App\Models\PersonaNivelHistorial;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class PersonaNivelDetalleObserver
{
    private const AUDITABLE = [
        'persona_nivel_id', 'persona_nivel_ciclo_id', 'persona_role_id', 'grado_id', 'grupo_id',
        'fecha_inicio', 'fecha_fin', 'estado', 'es_titular', 'es_titular_principal',
        'asignacion_materia_id', 'materia_manual', 'ajuste_horas_frente_grupo',
        'horas_administrativas', 'actividad_administrativa_id',
        'actividad_administrativa_manual', 'limite_horas_semanales',
        'observaciones', 'fecha_baja', 'motivo_baja', 'confirmado', 'pendiente_motivo',
        'archivado_at', 'archivado_por', 'motivo_archivo',
    ];

    public function creating(PersonaNivelDetalle $detalle): void
    {
        $detalle->fecha_inicio ??= now()->toDateString();
        $detalle->estado ??= PersonaNivelDetalle::ESTADO_ACTIVO;

        if ($detalle->es_titular_principal) {
            $detalle->es_titular = true;
        }
    }

    public function created(PersonaNivelDetalle $detalle): void
    {
        $detalle->loadMissing(['cabecera', 'cicloAsignacion.plantilla']);
        $this->registrar(
            $detalle,
            'creacion_detalle',
            null,
            Arr::only($detalle->getAttributes(), self::AUDITABLE),
            'Se agregó una función o asignación a la plantilla.'
        );
    }

    public function updating(PersonaNivelDetalle $detalle): void
    {
        if ($detalle->es_titular_principal) {
            $detalle->es_titular = true;
        }
    }

    public function updated(PersonaNivelDetalle $detalle): void
    {
        $cambios = Arr::only($detalle->getChanges(), self::AUDITABLE);

        if ($cambios === []) {
            return;
        }

        $anteriores = [];
        foreach (array_keys($cambios) as $campo) {
            $anteriores[$campo] = $detalle->getOriginal($campo);
        }

        $detalle->loadMissing(['cabecera', 'cicloAsignacion.plantilla']);
        $this->registrar(
            $detalle,
            'actualizacion_detalle',
            $anteriores,
            $cambios,
            'Se actualizó una función o asignación de la plantilla.'
        );
    }

    public function deleted(PersonaNivelDetalle $detalle): void
    {
        $detalle->loadMissing(['cabecera', 'cicloAsignacion.plantilla']);
        $this->registrar(
            $detalle,
            'eliminacion_detalle',
            Arr::only($detalle->getOriginal(), self::AUDITABLE),
            null,
            'Se eliminó una función o asignación de la plantilla.'
        );
    }

    private function registrar(
        PersonaNivelDetalle $detalle,
        string $accion,
        ?array $antes,
        ?array $despues,
        string $descripcion
    ): void {
        if (!Schema::hasTable('persona_nivel_historial')) {
            return;
        }

        $cabecera = $detalle->cabecera;
        $metadatos = [
            '_ciclo_escolar_id' => $detalle->cicloAsignacion?->plantilla?->ciclo_escolar_id,
            '_plantilla_id' => $detalle->cicloAsignacion?->plantilla_personal_nivel_id,
            '_persona_nivel_ciclo_id' => $detalle->persona_nivel_ciclo_id,
        ];

        $antes = array_merge($antes ?? [], $metadatos);
        $despues = array_merge($despues ?? [], $metadatos);

        PersonaNivelHistorial::query()->create([
            'persona_nivel_id' => $cabecera?->id,
            'persona_nivel_detalle_id' => $detalle->exists ? $detalle->id : null,
            'persona_id' => $cabecera?->persona_id,
            'nivel_id' => $cabecera?->nivel_id,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'usuario_id' => auth()->id(),
            'fecha' => now(),
        ]);
    }
}
