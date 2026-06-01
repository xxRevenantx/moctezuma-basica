<?php

namespace App\Exports\Inscripciones;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InscripcionesExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Alumnos' => new InscripcionesSheet(),
            'Catálogos' => new CatalogosInscripcionesSheet(),
        ];
    }
}
