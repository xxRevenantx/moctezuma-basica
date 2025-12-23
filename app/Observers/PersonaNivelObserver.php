<?php

namespace App\Observers;

use App\Models\PersonaNivel;

class PersonaNivelObserver
{

     public function creating(PersonaNivel $personaNivel): void
    {
        $personaNivel->orden = PersonaNivel::max('orden') + 1;
    }


    public function deleted(PersonaNivel $personaNivel): void
    {
        // Actualizar los estudiantes
        PersonaNivel::where('orden', '>', $personaNivel->orden)
            ->decrement('orden');

    }
}
