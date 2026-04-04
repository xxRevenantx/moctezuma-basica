<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TutorsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected Collection $tutores;

    public function __construct(Collection $tutores)
    {
        $this->tutores = $tutores;
    }

    public function collection()
    {
        return $this->tutores->values();
    }

    public function startCell(): string
    {
        // Aquí le digo que los encabezados empiecen en la fila 4
        return 'A4';
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID',
            'CURP',
            'Parentesco',
            'Género',
            'Nombre',
            'Apellido paterno',
            'Apellido materno',
            'Nombre completo',
            'Fecha de nacimiento',
            'Ciudad de nacimiento',
            'Municipio de nacimiento',
            'Estado de nacimiento',
            'Calle',
            'Número',
            'Colonia',
            'Ciudad',
            'Municipio',
            'Estado',
            'Código postal',
            'Teléfono de casa',
            'Teléfono celular',
            'Correo electrónico',
            'Fecha de registro',
            'Última actualización',
        ];
    }

    public function map($tutor): array
    {
        static $numero = 0;
        $numero++;

        return [
            $numero,
            $tutor->id,
            $tutor->curp,
            $tutor->parentesco,
            $tutor->genero,
            $tutor->nombre,
            $tutor->apellido_paterno,
            $tutor->apellido_materno,
            trim($tutor->nombre . ' ' . $tutor->apellido_paterno . ' ' . $tutor->apellido_materno),
            $tutor->fecha_nacimiento,
            $tutor->ciudad_nacimiento,
            $tutor->municipio_nacimiento,
            $tutor->estado_nacimiento,
            $tutor->calle,
            $tutor->numero,
            $tutor->colonia,
            $tutor->ciudad,
            $tutor->municipio,
            $tutor->estado,
            $tutor->codigo_postal,
            $tutor->telefono_casa,
            $tutor->telefono_celular,
            $tutor->correo_electronico,
            optional($tutor->created_at)?->format('d/m/Y H:i'),
            optional($tutor->updated_at)?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Título principal
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
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
            ],

            // Subtítulo
            2 => [
                'font' => [
                    'italic' => true,
                    'size' => 11,
                    'color' => ['rgb' => '374151'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // Encabezados
            4 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $ultimaColumna = 'Y';
                $ultimaFila = $this->tutores->count() + 4; // encabezados en fila 4, datos desde fila 5
    
                // Título
                $sheet->mergeCells("A1:{$ultimaColumna}1");
                $sheet->setCellValue('A1', 'REPORTE GENERAL DE TUTORES');

                // Subtítulo
                $sheet->mergeCells("A2:{$ultimaColumna}2");
                $sheet->setCellValue('A2', 'Generado el ' . now()->format('d/m/Y H:i:s'));

                // Altura de filas
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(24);

                // Bordes a toda la tabla
                if ($this->tutores->count() > 0) {
                    $sheet->getStyle("A4:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'D1D5DB'],
                            ],
                        ],
                    ]);

                    // Ajuste de texto general
                    $sheet->getStyle("A4:{$ultimaColumna}{$ultimaFila}")
                        ->getAlignment()
                        ->setWrapText(true);

                    // Alineación vertical
                    $sheet->getStyle("A4:{$ultimaColumna}{$ultimaFila}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Centrar algunas columnas
                    $sheet->getStyle("A4:E{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("J5:J{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("T5:T{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("U5:U{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("X5:Y{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Fondo alternado
                    for ($fila = 5; $fila <= $ultimaFila; $fila++) {
                        if ($fila % 2 === 0) {
                            $sheet->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC'],
                                ],
                            ]);
                        }
                    }

                    // Nombre completo en negrita
                    $sheet->getStyle("I5:I{$ultimaFila}")->getFont()->setBold(true);
                }

                // Autofiltro
                $sheet->setAutoFilter("A4:{$ultimaColumna}4");

                // Congelar encabezados
                $sheet->freezePane('A5');

                // Anchos manuales
                $sheet->getColumnDimension('I')->setWidth(30);
                $sheet->getColumnDimension('N')->setWidth(25);
                $sheet->getColumnDimension('P')->setWidth(20);
                $sheet->getColumnDimension('Q')->setWidth(20);
                $sheet->getColumnDimension('R')->setWidth(20);
                $sheet->getColumnDimension('S')->setWidth(20);
                $sheet->getColumnDimension('W')->setWidth(30);

                // Borde del título
                $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '1E40AF'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
