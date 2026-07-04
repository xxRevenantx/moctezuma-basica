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
        protected string $nivelSlug,
        protected bool $esBachillerato,
        protected array $encabezadosPeriodos,
        protected array $resumen,
        protected Collection $gruposPromedios,
        protected string $modalidadBachillerato = 'semestral',
        protected array $filtros = [],
    ) {
    }

    public function sheets(): array
    {
        $hojas = [
            new PromediosGeneralesResumenSheet(
                nivelNombre: $this->nivelNombre,
                nivelSlug: $this->nivelSlug,
                esBachillerato: $this->esBachillerato,
                resumen: $this->resumen,
                modalidadBachillerato: $this->modalidadBachillerato,
                filtros: $this->filtros,
            ),
            new PromediosGeneralesDetalleSheet(
                nivelNombre: $this->nivelNombre,
                esBachillerato: $this->esBachillerato,
                encabezadosPeriodos: $this->encabezadosPeriodos,
                gruposPromedios: $this->gruposPromedios,
                modalidadBachillerato: $this->modalidadBachillerato,
            ),
        ];

        if (in_array($this->nivelSlug, ['primaria', 'secundaria'], true)) {
            $hojas[] = new PromediosGeneralesAcademicoSheet(
                nivelNombre: $this->nivelNombre,
                nivelSlug: $this->nivelSlug,
                gruposPromedios: $this->gruposPromedios,
            );
        }

        return $hojas;
    }
}
