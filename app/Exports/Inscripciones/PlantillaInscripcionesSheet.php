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
                'clave_grupo',
                'momento_ingreso_id',
                'tipo_ingreso',
                'motivo_captura_historica',
                'estado_inscripcion',
                'observaciones',
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
                '2026-08-01',
                'SELECCIONA UNA CLAVE DE LA HOJA CATÁLOGOS',
                '1',
                'nuevo_ingreso',
                '',
                'inscrito',
                'Documentación pendiente: entregar comprobante de domicilio.',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();

                $hoja->freezePane('A2');
                $hoja->setAutoFilter('A1:O1');

                $hoja->getStyle('A1:O1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                ]);

                $hoja->getStyle('A2:O2')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAF4FF'],
                    ],
                ]);

                $hoja->getRowDimension(1)->setRowHeight(24);

                foreach (range(2, 500) as $fila) {
                    $this->agregarLista($hoja, "H{$fila}", '"H,M"');
                    $this->agregarLista($hoja, "J{$fila}", "'Catálogos'!\$A\$2:\$A\$1000");
                    $this->agregarLista($hoja, "K{$fila}", "'Catálogos'!\$K\$2:\$K\$100");
                    $this->agregarLista(
                        $hoja,
                        "L{$fila}",
                        '"nuevo_ingreso,traslado,captura_historica"'
                    );
                    $this->agregarLista($hoja, "N{$fila}", '"preinscrito,inscrito"');
                }

                $hoja->getStyle('G2:G500')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $hoja->getStyle('I2:I500')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $hoja->getStyle('A:O')->getAlignment()->setVertical('center');
            },
        ];
    }

    private function agregarLista($hoja, string $celda, string $formula): void
    {
        $validacion = $hoja->getCell($celda)->getDataValidation();

        $validacion->setType(DataValidation::TYPE_LIST);
        $validacion->setErrorStyle(DataValidation::STYLE_STOP);
        $validacion->setAllowBlank(false);
        $validacion->setShowDropDown(true);
        $validacion->setShowErrorMessage(true);
        $validacion->setErrorTitle('Dato no válido');
        $validacion->setError('Selecciona un valor permitido.');
        $validacion->setFormula1($formula);
    }
}
