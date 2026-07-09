<?php

namespace App\Observers;

use App\Models\PersonaNivel;
use App\Models\PersonaNivelHistorial;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class PersonaNivelObserver
{
    private const AUDITABLE = [
        'persona_id', 'nivel_id', 'ingreso_seg', 'ingreso_sep', 'ingreso_ct',
        'fecha_inicio', 'fecha_fin', 'estado', 'horas_administrativas',
        'limite_horas_semanales', 'actividad_administrativa', 'observaciones',
        'fecha_baja', 'motivo_baja',
    ];

    public function creating(PersonaNivel $personaNivel): void
    {
        if (empty($personaNivel->orden)) {
            $personaNivel->orden = ((int) PersonaNivel::max('orden')) + 1;
        }

        $personaNivel->fecha_inicio ??= now()->toDateString();
        $personaNivel->estado ??= PersonaNivel::ESTADO_ACTIVO;
    }

    public function created(PersonaNivel $personaNivel): void
    {
        $this->registrar($personaNivel, 'creacion', null, Arr::only($personaNivel->getAttributes(), self::AUDITABLE), 'Se creó la asignación general al nivel.');
    }

    public function updated(PersonaNivel $personaNivel): void
    {
        $cambios = Arr::only($personaNivel->getChanges(), self::AUDITABLE);

        if ($cambios === []) {
            return;
        }

        $anteriores = [];
        foreach (array_keys($cambios) as $campo) {
            $anteriores[$campo] = $personaNivel->getOriginal($campo);
        }

        $this->registrar($personaNivel, 'actualizacion', $anteriores, $cambios, 'Se actualizó la asignación general al nivel.');
    }

    public function deleted(PersonaNivel $personaNivel): void
    {
        PersonaNivel::where('orden', '>', $personaNivel->orden)->decrement('orden');

        $this->registrar($personaNivel, 'eliminacion', Arr::only($personaNivel->getOriginal(), self::AUDITABLE), null, 'Se eliminó la asignación general al nivel.');
    }

    private function registrar(PersonaNivel $cabecera, string $accion, ?array $antes, ?array $despues, string $descripcion): void
    {
        if (!Schema::hasTable('persona_nivel_historial')) {
            return;
        }

        PersonaNivelHistorial::query()->create([
            'persona_nivel_id' => $cabecera->exists ? $cabecera->id : null,
            'persona_id' => $cabecera->persona_id,
            'nivel_id' => $cabecera->nivel_id,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'usuario_id' => auth()->id(),
            'fecha' => now(),
        ]);
    }
}
