<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PromediosMateriasGradosSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(protected array $reporte)
    {
        //
    }

    public function title(): string
    {
        return 'Promedios por grado';
    }

    public function array(): array
    {
        $filas = [
            ['PROMEDIOS POR GRADO O SEMESTRE'],
            [($this->reporte['nivel']['nombre'] ?? 'Nivel') . ' · ' . ($this->reporte['ciclo']['texto'] ?? '—')],
            [],
            ['Bloque', 'Grupos', 'Alumnos', 'Materias', 'Promedio general', 'Estado'],
        ];

        foreach ($this->reporte['bloques'] ?? [] as $bloque) {
            $filas[] = [
                $bloque['titulo'] ?? '—',
                $bloque['total_grupos'] ?? 0,
                $bloque['total_alumnos'] ?? 0,
                collect($bloque['materias'] ?? [])->count(),
                $this->valor($bloque['promedio_general'] ?? null),
                ($bloque['provisional'] ?? true) ? 'Provisional' : 'Definitivo',
            ];
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $hoja = $event->sheet->getDelegate();
            $ultima = $hoja->getHighestRow();
            $hoja->mergeCells('A1:F1');
            $hoja->mergeCells('A2:F2');
            $hoja->getStyle('A1:F1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle('A4:F4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '88AC2E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle("A1:F{$ultima}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $hoja->freezePane('A5');
        }];
    }

    private function valor(mixed $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }
}
