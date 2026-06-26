<?php

namespace App\Exports\Distribucion;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DistribucionEscolarSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    public function __construct(
        private readonly string $titulo,
        private readonly array $encabezados,
        private readonly array $filas,
    ) {}

    public function array(): array
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return $this->encabezados;
    }

    public function title(): string
    {
        return mb_substr($this->titulo, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'FF006492'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaColumna = $sheet->getHighestColumn();
                $ultimaFila = max(1, $sheet->getHighestRow());

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$ultimaColumna}{$ultimaFila}");
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle("A1:{$ultimaColumna}{$ultimaFila}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin')
                    ->getColor()
                    ->setARGB('FFD1D5DB');
                $sheet->getStyle("A2:{$ultimaColumna}{$ultimaFila}")
                    ->getAlignment()
                    ->setVertical('top')
                    ->setWrapText(true);
            },
        ];
    }
}
