<?php

namespace App\Observers;

use App\Models\Dia;

class DiaObserver
{
    public function creating(Dia $dia): void
    {
        $dia->orden = Dia::max('orden') + 1;
    }


    public function deleted(Dia $dia): void
    {

        Dia::where('orden', '>', $dia->orden)
            ->decrement('orden');
    }
}
