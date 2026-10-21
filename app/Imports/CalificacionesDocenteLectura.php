<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class CalificacionesDocenteLectura implements ToArray
{
    public function array(array $array): void
    {
        // Excel::toArray devuelve todas las hojas para su validación antes de guardar.
    }
}
