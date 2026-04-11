<?php

namespace App\Observers;

use App\Models\AsignacionMateria;

class AsignacionMateriaObserver
{


    public function creating(AsignacionMateria $asignacionMateria): void
    {
        $asignacionMateria->orden = AsignacionMateria::max('orden') + 1;
    }


    public function deleted(AsignacionMateria $asignacionMateria)
    {
        // Actualizar los estudiantes
        AsignacionMateria::where('orden', '>', $asignacionMateria->orden)
            ->decrement('orden');

    }

}
