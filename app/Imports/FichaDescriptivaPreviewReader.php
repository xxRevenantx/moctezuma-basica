<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Lector de vista previa: no importa ni modifica registros. */
class FichaDescriptivaPreviewReader implements ToArray, WithHeadingRow
{
    public function array(array $rows): void
    {
        // Excel::toArray() sólo lee; este método no guarda cambios.
    }
}
