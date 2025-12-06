<?php

namespace App\Observers;

use App\Models\Grado;

class GradoObserver
{
     public function creating(Grado $grado): void
    {
        $grado->orden = Grado::max('orden') + 1;
    }


    public function deleted(Grado $grado): void
    {
        // Actualizar los estudiantes
        Grado::where('orden', '>', $grado->orden)
            ->decrement('orden');

    }
}
