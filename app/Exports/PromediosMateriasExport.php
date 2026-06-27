<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PromediosMateriasExport implements WithMultipleSheets
{
    public function __construct(
        protected array $reporte,
        protected string $alcance = 'completo',
    ) {
        //
    }

    public function sheets(): array
    {
        $hojas = [
            new PromediosMateriasConcentradoSheet($this->reporte),
        ];

        if (in_array($this->alcance, ['completo', 'nivel', 'grado'], true)) {
            $hojas[] = new PromediosMateriasGradosSheet($this->reporte);
            $hojas[] = new PromediosMateriasGruposSheet($this->reporte);
        }

        if (in_array($this->alcance, ['completo', 'grado', 'grupo'], true)) {
            $hojas[] = new PromediosMateriasDetalleAlumnosSheet($this->reporte);
        }

        if ($this->alcance === 'completo') {
            $hojas[] = new PromediosMateriasCamposSheet($this->reporte);
            $hojas[] = new PromediosMateriasProvisionalesSheet($this->reporte);
        }

        return $hojas;
    }
}
