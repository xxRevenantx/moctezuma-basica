<?php

namespace App\Exports\Periodos;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlantillaPeriodosExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PlantillaPeriodosSheet(),
            new CatalogosPeriodosSheet(),
            new EjemplosPeriodosSheet(),
            new InstruccionesPeriodosSheet(),
        ];
    }
}
