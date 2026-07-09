<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PersonaNivelReporteExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly Collection $filas,
        private readonly array $encabezados,
        private readonly string $titulo,
    ) {
    }

    public function collection(): Collection
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $ultimaColumna = $sheet->getHighestColumn();

                $sheet->insertNewRowBefore(1, 2);
                $ultimaFila = max(3, $sheet->getHighestRow());
                $sheet->mergeCells("A1:{$ultimaColumna}1");
                $sheet->setCellValue('A1', $this->titulo);
                $sheet->mergeCells("A2:{$ultimaColumna}2");
                $sheet->setCellValue('A2', 'Generado el ' . now()->format('d/m/Y H:i'));

                $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                ]);

                $sheet->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '334155']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF3D9']],
                ]);

                $sheet->getStyle("A3:{$ultimaColumna}3")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                ]);

                $sheet->getStyle("A3:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                ]);

                $sheet->freezePane('A4');
                $sheet->setAutoFilter("A3:{$ultimaColumna}3");
            },
        ];
    }
}
