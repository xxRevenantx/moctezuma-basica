<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CalificacionesDocenteHoja implements FromArray, WithTitle, WithColumnFormatting, WithStyles
{
    public function __construct(public string $titulo, public array $filas) {}

    public function title(): string
    {
        return mb_substr(str_replace(['[', ']', '/', '\\', '*', '?', ':'], '-', $this->titulo), 0, 31) ?: 'Calificaciones';
    }

    public function array(): array
    {
        return array_merge([
            ['ASIGNACION_ID', 'PERIODO_ID', 'MATRICULA', 'ALUMNO', 'ACTUAL', 'NUEVA'],
        ], $this->filas);
    }

    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_NUMBER, 'B' => NumberFormat::FORMAT_NUMBER, 'C' => NumberFormat::FORMAT_TEXT, 'E' => NumberFormat::FORMAT_TEXT, 'F' => NumberFormat::FORMAT_TEXT];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(52);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(19);
        $sheet->freezePane('E2');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:F1')->getFill()->setFillType('solid')->getStartColor()->setRGB('006492');
        $sheet->getProtection()->setSheet(true);
        $sheet->getStyle('F2:F' . max(count($this->filas) + 1, 2))->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
        // La protección de la plantilla ayuda al usuario, pero toda validación se repite en el servidor.
        return [];
    }
}
