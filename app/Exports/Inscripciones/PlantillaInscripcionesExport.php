<?php

namespace App\Exports\Inscripciones;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlantillaInscripcionesExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Plantilla' => new PlantillaInscripcionesSheet(),
            'Catálogos' => new CatalogosInscripcionesSheet(),
            'Instrucciones' => new InstruccionesInscripcionesSheet(),
        ];
    }
}
