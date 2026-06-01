<?php

namespace App\Exports\Inscripciones;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PlantillaInscripcionesSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Plantilla';
    }

    public function array(): array
    {
        return [
            [
                'curp',
                'matricula',
                'folio',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'fecha_nacimiento',
                'genero',
                'fecha_inscripcion',
                'ciclo_escolar_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'grupo_id',
                'semestre_id',
                'ciclo_id',
            ],
            [
                'NUPD950408HGRXXX01',
                '2026PREESNUPD01',
                'FOLIO-001',
                'CARLOS ALBERTO',
                'NÚÑEZ',
                'PÉREZ',
                '1995-04-08',
                'H',
                '2026-06-01',
                '1',
                '1',
                '1',
                '1',
                '1',
                '',
                '1',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();

                $hoja->freezePane('A2');
                $hoja->setAutoFilter('A1:P1');

                $hoja->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                ]);

                $hoja->getStyle('A2:P2')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAF4FF'],
                    ],
                ]);

                $hoja->getRowDimension(1)->setRowHeight(24);

                foreach (range(2, 500) as $fila) {
                    $this->agregarLista($hoja, "H{$fila}", '"H,M"');

                    // Ciclo escolar
                    $this->agregarLista($hoja, "J{$fila}", "'Catálogos'!\$A\$2:\$A\$500");

                    // Nivel
                    $this->agregarLista($hoja, "K{$fila}", "'Catálogos'!\$D\$2:\$D\$500");

                    // Grado
                    $this->agregarLista($hoja, "L{$fila}", "'Catálogos'!\$G\$2:\$G\$500");

                    // Generación
                    $this->agregarLista($hoja, "M{$fila}", "'Catálogos'!\$J\$2:\$J\$500");

                    // Grupo
                    $this->agregarLista($hoja, "N{$fila}", "'Catálogos'!\$M\$2:\$M\$500");

                    // Semestre
                    $this->agregarLista($hoja, "O{$fila}", "'Catálogos'!\$Q\$2:\$Q\$500");

                    // Periodo de inscripción / ciclo
                    $this->agregarLista($hoja, "P{$fila}", "'Catálogos'!\$T\$2:\$T\$500");
                }

                $hoja->getStyle('G2:G500')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $hoja->getStyle('I2:I500')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

                $hoja->getStyle('A:P')->getAlignment()->setVertical('center');
            },
        ];
    }

    private function agregarLista($hoja, string $celda, string $formula): void
    {
        $validacion = $hoja->getCell($celda)->getDataValidation();

        $validacion->setType(DataValidation::TYPE_LIST);
        $validacion->setErrorStyle(DataValidation::STYLE_STOP);
        $validacion->setAllowBlank(true);
        $validacion->setShowDropDown(true);
        $validacion->setShowErrorMessage(true);
        $validacion->setErrorTitle('Dato no válido');
        $validacion->setError('Selecciona un valor permitido desde la hoja Catálogos.');
        $validacion->setFormula1($formula);
    }
}
