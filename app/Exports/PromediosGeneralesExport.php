<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PromediosGeneralesExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected string $nivelNombre,
        protected bool $esBachillerato,
        protected array $encabezadosPeriodos,
        protected array $resumen,
        protected Collection $gruposPromedios,
        protected array $filtros = [],
    ) {
        //
    }

    public function sheets(): array
    {
        return [
            new PromediosGeneralesResumenSheet(
                nivelNombre: $this->nivelNombre,
                esBachillerato: $this->esBachillerato,
                resumen: $this->resumen,
                filtros: $this->filtros,
            ),

            new PromediosGeneralesDetalleSheet(
                nivelNombre: $this->nivelNombre,
                esBachillerato: $this->esBachillerato,
                encabezadosPeriodos: $this->encabezadosPeriodos,
                gruposPromedios: $this->gruposPromedios,
            ),
        ];
    }
}
