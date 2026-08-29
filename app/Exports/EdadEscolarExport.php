<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EdadEscolarExport implements FromCollection, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly array $datos)
    {
    }

    public function collection(): Collection
    {
        $filas = [[
            'Matrícula', 'CURP', 'Alumno', 'Sexo', 'Nivel', 'Grado', 'Grupo', 'Ciclo escolar',
            'Fecha nacimiento', 'Edad actual', 'Edad al corte', 'Edad esperada', 'Diferencia', 'Situación', 'Fecha corte',
        ]];

        foreach ($this->datos['filas'] ?? [] as $fila) {
            $filas[] = [
                $fila['matricula'] ?? '',
                $fila['curp'] ?? '',
                $fila['alumno'] ?? '',
                $fila['genero'] ?? '',
                $fila['nivel'] ?? '',
                $fila['grado'] ?? '',
                $fila['grupo'] ?? '',
                $fila['ciclo'] ?? '',
                $fila['fecha_nacimiento'] ?? '',
                $fila['edad_actual'] ?? '',
                $fila['edad_corte'] ?? '',
                $fila['edad_esperada'] ?? '',
                $fila['diferencia'] ?? '',
                $fila['etiqueta'] ?? '',
                $fila['fecha_corte'] ?? '',
            ];
        }

        return collect($filas);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);
        $sheet->getStyle('A1:O1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8EEF8');
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }
}
