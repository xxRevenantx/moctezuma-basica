<?php

namespace App\Observers;

use App\Models\PersonaNivelCiclo;
use App\Models\PersonaNivelHistorial;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class PersonaNivelCicloObserver
{
    private const AUDITABLE = [
        'plantilla_personal_nivel_id', 'persona_nivel_id', 'estado', 'orden',
        'fecha_inicio', 'fecha_fin', 'fecha_baja', 'motivo_baja', 'copiado_desde_id',
    ];

    public function created(PersonaNivelCiclo $membresia): void
    {
        $this->registrar(
            $membresia,
            'alta_plantilla_ciclo',
            null,
            Arr::only($membresia->getAttributes(), self::AUDITABLE),
            'Se incorporó la persona a una plantilla de ciclo escolar.'
        );
    }

    public function updated(PersonaNivelCiclo $membresia): void
    {
        $cambios = Arr::only($membresia->getChanges(), self::AUDITABLE);
        if ($cambios === []) {
            return;
        }

        $anteriores = [];
        foreach (array_keys($cambios) as $campo) {
            $anteriores[$campo] = $membresia->getOriginal($campo);
        }

        $this->registrar(
            $membresia,
            'actualizacion_plantilla_ciclo',
            $anteriores,
            $cambios,
            'Se actualizó la participación de la persona en el ciclo escolar.'
        );
    }

    private function registrar(
        PersonaNivelCiclo $membresia,
        string $accion,
        ?array $antes,
        ?array $despues,
        string $descripcion
    ): void {
        if (!Schema::hasTable('persona_nivel_historial')) {
            return;
        }

        $membresia->loadMissing(['personaNivel', 'plantilla']);
        $cabecera = $membresia->personaNivel;

        $antes = array_merge($antes ?? [], [
            '_ciclo_escolar_id' => $membresia->plantilla?->ciclo_escolar_id,
            '_plantilla_id' => $membresia->plantilla_personal_nivel_id,
        ]);
        $despues = array_merge($despues ?? [], [
            '_ciclo_escolar_id' => $membresia->plantilla?->ciclo_escolar_id,
            '_plantilla_id' => $membresia->plantilla_personal_nivel_id,
        ]);

        PersonaNivelHistorial::query()->create([
            'persona_nivel_id' => $cabecera?->id,
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
