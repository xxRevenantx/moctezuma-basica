<?php

namespace App\Exports\Periodos;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PlantillaPeriodosSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Periodos';
    }

    public function array(): array
    {
        return [[
            'tipo',
            'nivel_id',
            'ciclo_escolar_id',
            'generacion_id',
            'semestre_id',
            'mes_basica_id',
            'periodo_basica_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
            'fecha_evaluacion_inicio',
            'fecha_evaluacion_fin',
            'fecha_captura_inicio',
            'fecha_captura_fin',
            'permitir_traslape',
            'motivo_traslape',
        ]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();

                $hoja->freezePane('A2');
                $hoja->setAutoFilter('A1:O1');
                $hoja->getRowDimension(1)->setRowHeight(28);

                $hoja->getStyle('A1:O1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D7E3EA'],
                        ],
                    ],
                ]);

                $hoja->getStyle('A2:O500')->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_HAIR,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                // Campos comunes.
                $hoja->getStyle('A2:C500')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EEF7FB');
                $hoja->getStyle('J2:M500')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EEF7FB');

                // Campos exclusivos de básica.
                $hoja->getStyle('F2:G500')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0F9E8');

                // Campos exclusivos de bachillerato.
                $hoja->getStyle('D2:E500')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF7E6');
                $hoja->getStyle('H2:I500')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF7E6');

                foreach (range(2, 500) as $fila) {
                    $this->agregarLista($hoja, "A{$fila}", '"BASICA,BACHILLERATO"');
                    $this->agregarLista($hoja, "B{$fila}", "'Catálogos'!\$A\$2:\$A\$200");
                    $this->agregarLista($hoja, "C{$fila}", "'Catálogos'!\$B\$2:\$B\$200");
                    $this->agregarLista($hoja, "D{$fila}", "'Catálogos'!\$C\$2:\$C\$200");
                    $this->agregarLista($hoja, "E{$fila}", "'Catálogos'!\$D\$2:\$D\$200");
                    $this->agregarLista($hoja, "F{$fila}", "'Catálogos'!\$E\$2:\$E\$200");
                    $this->agregarLista($hoja, "G{$fila}", "'Catálogos'!\$F\$2:\$F\$200");
                    $this->agregarLista($hoja, "H{$fila}", "'Catálogos'!\$G\$2:\$G\$200");
                    $this->agregarLista($hoja, "I{$fila}", "'Catálogos'!\$H\$2:\$H\$200");
                    $this->agregarLista($hoja, "N{$fila}", '"NO,SI"');
                }

                $hoja->getStyle('J2:M500')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

                $hoja->getColumnDimension('A')->setWidth(18);
                $hoja->getColumnDimension('B')->setWidth(24);
                $hoja->getColumnDimension('C')->setWidth(24);
                $hoja->getColumnDimension('D')->setWidth(28);
                $hoja->getColumnDimension('E')->setWidth(22);
                $hoja->getColumnDimension('F')->setWidth(28);
                $hoja->getColumnDimension('G')->setWidth(24);
                $hoja->getColumnDimension('H')->setWidth(25);
                $hoja->getColumnDimension('I')->setWidth(25);
                $hoja->getColumnDimension('J')->setWidth(16);
                $hoja->getColumnDimension('K')->setWidth(18);
                $hoja->getColumnDimension('L')->setWidth(18);
                $hoja->getColumnDimension('M')->setWidth(18);
                $hoja->getColumnDimension('N')->setWidth(18);
                $hoja->getColumnDimension('O')->setWidth(42);
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
        $validacion->setError('Selecciona una opción válida de la lista desplegable.');
        $validacion->setPromptTitle('Selecciona una opción');
        $validacion->setPrompt('Los valores disponibles provienen de la hoja Catálogos.');
        $validacion->setShowInputMessage(true);
        $validacion->setFormula1($formula);
    }
}
