<?php

namespace App\Observers;

use App\Models\Accion;

class AccionObserver
{
        public function creating(Accion $accion): void
    {
        $accion->orden = Accion::max('orden') + 1;
    }


    public function deleted(Accion $accion): void
    {
        // Actualizar los estudiantes
        Accion::where('orden', '>', $accion->orden)
            ->decrement('orden');

    }
}
