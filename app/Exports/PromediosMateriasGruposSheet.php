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

class PromediosMateriasGruposSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(protected array $reporte)
    {
        //
    }

    public function title(): string
    {
        return 'Promedios por grupo';
    }

    public function array(): array
    {
        $filas = [
            ['PROMEDIOS POR GRUPO'],
            [($this->reporte['nivel']['nombre'] ?? 'Nivel') . ' · ' . ($this->reporte['ciclo']['texto'] ?? '—')],
            [],
            ['Grado', 'Semestre', 'Grupo', 'Generación', 'Alumnos', 'Promedio general', 'Estado'],
        ];

        foreach ($this->reporte['grupos'] ?? [] as $grupo) {
            $filas[] = [
                $grupo['grado'] ?? '—',
                $grupo['semestre'] ? 'Semestre ' . $grupo['semestre'] : '—',
                $grupo['grupo'] ?? '—',
                $grupo['generacion'] ?? '—',
                $grupo['total_alumnos'] ?? 0,
                $this->valor($grupo['promedio_general'] ?? null),
                ($grupo['provisional'] ?? true) ? 'Provisional' : 'Definitivo',
            ];
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $hoja = $event->sheet->getDelegate();
            $ultima = $hoja->getHighestRow();
            $hoja->mergeCells('A1:G1');
            $hoja->mergeCells('A2:G2');
            $hoja->getStyle('A1:G1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle('A4:G4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle("A1:G{$ultima}")->applyFromArray([
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
