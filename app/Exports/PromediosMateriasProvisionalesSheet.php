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

class PromediosMateriasProvisionalesSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(protected array $reporte)
    {
        //
    }

    public function title(): string
    {
        return 'Registros provisionales';
    }

    public function array(): array
    {
        $filas = [
            ['REGISTROS PROVISIONALES'],
            [($this->reporte['nivel']['nombre'] ?? 'Nivel') . ' · ' . ($this->reporte['ciclo']['texto'] ?? '—')],
            [],
            ['Matrícula', 'Alumno', 'Grado', 'Semestre', 'Grupo', 'Materia', 'Capturadas', 'Faltantes', 'Promedio provisional'],
        ];

        foreach ($this->reporte['alumnos'] ?? [] as $alumno) {
            foreach ($alumno['materias'] ?? [] as $materia) {
                if (! ($materia['provisional'] ?? false)) {
                    continue;
                }

                $filas[] = [
                    $alumno['matricula'] ?? '—',
                    $alumno['alumno'] ?? '—',
                    $alumno['grado'] ?? '—',
                    $alumno['semestre'] ? 'Semestre ' . $alumno['semestre'] : '—',
                    $alumno['grupo'] ?? '—',
                    $materia['materia'] ?? '—',
                    $materia['capturadas'] ?? 0,
                    $materia['faltantes'] ?? 0,
                    $this->valor($materia['promedio'] ?? null),
                ];
            }
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $hoja = $event->sheet->getDelegate();
            $ultima = $hoja->getHighestRow();
            $hoja->mergeCells('A1:I1');
            $hoja->mergeCells('A2:I2');
            $hoja->getStyle('A1:I1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $hoja->getStyle('A4:I4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92400E']],
            ]);
            $hoja->getStyle("A1:I{$ultima}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FCD34D']]],
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
