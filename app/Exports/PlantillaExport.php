<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PlantillaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected Collection $data;
    protected string $nivelNombre;

    public function __construct(Collection $data, string $nivelNombre = 'Nivel')
    {
        $this->data = $data;
        $this->nivelNombre = $nivelNombre;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Persona',
            'Nivel',
            'Grado',
            'Grupo',
            'Función / Materia',
            'Ingreso SEG',
            'Ingreso SEP',
            'Ingreso C.T.',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $ultimaFila = $this->data->count() + 3;

                // Insertar dos filas arriba
                $sheet->insertNewRowBefore(1, 2);

                // Título principal
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'Plantilla de personal por nivel');

                // Subtítulo
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', 'Nivel: ' . $this->nivelNombre);

                // Estilo título
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 15,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1D4ED8'],
                    ],
                ]);

                // Estilo subtítulo
                $sheet->getStyle('A2:I2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'],
                    ],
                ]);

                // Estilo encabezados
                $sheet->getStyle('A3:I3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F172A'],
                    ],
                ]);

                // Bordes a toda la tabla
                $sheet->getStyle('A3:I' . $ultimaFila)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                // Alineaciones
                $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C:E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Altura de filas
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(22);
            },
        ];
    }
}
