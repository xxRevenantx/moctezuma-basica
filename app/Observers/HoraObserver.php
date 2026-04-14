<?php

namespace App\Observers;

use App\Models\Hora;

class HoraObserver
{

    public function creating(Hora $hora): void
    {
        $hora->orden = Hora::max('orden') + 1;
    }


    public function deleted(Hora $hora): void
    {
        // Actualizar las horas
        Hora::where('orden', '>', $hora->orden)
            ->decrement('orden');
    }
}
