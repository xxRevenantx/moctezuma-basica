<?php

namespace App\Observers;

use App\Models\AsignacionMateria;

class AsignacionMateriaObserver
{
    public function creating(AsignacionMateria $asignacionMateria): void
    {
        $asignacionMateria->sincronizarContextoDesdeGrupo();
        $asignacionMateria->estado ??= AsignacionMateria::ESTADO_BORRADOR;

        if (blank($asignacionMateria->orden)) {
            $asignacionMateria->orden = ((int) AsignacionMateria::query()
                ->where('grupo_id', $asignacionMateria->grupo_id)
                ->where('ciclo_escolar_id', $asignacionMateria->ciclo_escolar_id)
                ->max('orden')) + 1;
        }
    }

    public function updating(AsignacionMateria $asignacionMateria): void
    {
        if ($asignacionMateria->isDirty('grupo_id')) {
            $asignacionMateria->unsetRelation('grupo');
            $asignacionMateria->sincronizarContextoDesdeGrupo();
        }
    }

    public function deleting(AsignacionMateria $asignacionMateria): void
    {
        if ($asignacionMateria->tieneHistorial()) {
            throw new \LogicException('La carga tiene horario, calificaciones o auditoría. Debe archivarse, no eliminarse.');
        }
    }

    public function deleted(AsignacionMateria $asignacionMateria): void
    {
        AsignacionMateria::query()
            ->where('grupo_id', $asignacionMateria->grupo_id)
            ->where('ciclo_escolar_id', $asignacionMateria->ciclo_escolar_id)
            ->where('orden', '>', $asignacionMateria->orden)
            ->decrement('orden');
    }
}
