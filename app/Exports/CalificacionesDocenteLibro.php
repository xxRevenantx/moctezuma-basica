<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CalificacionesDocenteLibro implements WithMultipleSheets
{
    public function __construct(private array $hojas) {}

    public function sheets(): array
    {
        return $this->hojas;
    }
}
