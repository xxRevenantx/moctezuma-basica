<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class MatriculaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected Collection $rows;
    protected string $nivelNombre;
    protected string $generacionNombre;
    protected string $gradoNombre;
    protected string $semestreNombre;
    protected string $grupoNombre;
    protected string $search;
    protected bool $esBachillerato;

    public function __construct(
        Collection $rows,
        string $nivelNombre = '—',
        string $generacionNombre = '—',
        string $gradoNombre = '—',
        string $semestreNombre = '—',
        string $grupoNombre = '—',
        string $search = '',
        bool $esBachillerato = false
    ) {
        $this->rows = $rows;
        $this->nivelNombre = $nivelNombre;
        $this->generacionNombre = $generacionNombre;
        $this->gradoNombre = $gradoNombre;
        $this->semestreNombre = $semestreNombre;
        $this->grupoNombre = $grupoNombre;
        $this->search = trim($search);
        $this->esBachillerato = $esBachillerato;
    }

    public function collection()
    {
        return $this->rows->values()->map(function ($row, $index) {
            $data = [
                'no' => $index + 1,
                'matricula' => $row->matricula ?? '—',
                'folio' => $row->folio ?? '—',
                'apellido_paterno' => $row->apellido_paterno ?? '—',
                'apellido_materno' => $row->apellido_materno ?? '—',
                'nombre' => $row->nombre ?? '—',
                'curp' => $row->curp ?? '—',
                'genero' => $row->genero ?? '—',
                'generacion' => $row->generacion
                    ? ($row->generacion->anio_ingreso . ' - ' . $row->generacion->anio_egreso)
                    : '—',
                'grado' => $row->grado?->nombre ?? '—',
            ];

            if ($this->esBachillerato) {
                $data['semestre'] = $row->semestre?->numero ?? '—';
            }

            $data['grupo'] = $row->grupo?->asignacionGrupo?->nombre ?? '—';

            return $data;
        });
    }

    public function headings(): array
    {
        $headings = [
            'No.',
            'Matrícula',
            'Folio',
            'Apellido paterno',
            'Apellido materno',
            'Nombre(s)',
            'CURP',
            'Género',
            'Generación',
            'Grado',
        ];

        if ($this->esBachillerato) {
            $headings[] = 'Semestre';
        }

        $headings[] = 'Grupo';

        return $headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalDatos = $this->rows->count();
                $totalColumnas = count($this->headings());
                $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

                $filaEncabezados = 6;
                $primeraFilaDatos = 7;
                $ultimaFila = $totalDatos + $filaEncabezados;

                $azulMoctezuma = '006492';
                $verdeMoctezuma = '88AC2E';
                $azulOscuro = '0F172A';
                $grisTexto = '1E293B';
                $grisBorde = 'CBD5E1';
                $grisFila = 'F8FAFC';
                $verdeSuave = 'EAF4DD';
                $azulSuave = 'E0F2FE';
                $verdeClaro = 'DCFCE7';
                $rosaClaro = 'FCE7F3';

                $sheet->insertNewRowBefore(1, 5);

                $sheet->mergeCells("A1:{$ultimaColumna}1");
                $sheet->setCellValue('A1', 'MATRÍCULA DE ALUMNOS');

                $sheet->mergeCells("A2:{$ultimaColumna}2");
                $sheet->setCellValue('A2', 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.');

                $sheet->mergeCells("A3:{$ultimaColumna}3");
                $sheet->setCellValue(
                    'A3',
                    'Nivel: ' . $this->nivelNombre .
                    '   |   Generación: ' . $this->generacionNombre .
                    '   |   Grado: ' . $this->gradoNombre .
                    '   |   Semestre: ' . $this->semestreNombre .
                    '   |   Grupo: ' . $this->grupoNombre
                );

                $sheet->mergeCells("A4:{$ultimaColumna}4");
                $sheet->setCellValue(
                    'A4',
                    'Búsqueda aplicada: ' . ($this->search !== '' ? $this->search : 'Sin filtro de búsqueda')
                );

                $sheet->mergeCells("A5:{$ultimaColumna}5");
                $sheet->setCellValue(
                    'A5',
                    'Total de alumnos exportados: ' . $totalDatos .
                    '   |   Fecha de exportación: ' . now()->format('d/m/Y H:i')
                );

                $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $azulMoctezuma],
                    ],
                ]);

                $sheet->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $verdeMoctezuma],
                    ],
                ]);

                $sheet->getStyle("A3:{$ultimaColumna}5")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => $grisTexto],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $verdeSuave],
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => $verdeMoctezuma],
                        ],
                    ],
                ]);

                $sheet->getStyle("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $azulOscuro],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '334155'],
                        ],
                    ],
                ]);

                if ($totalDatos > 0) {
                    $sheet->getStyle("A{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                        'font' => [
                            'size' => 10,
                            'color' => ['rgb' => $grisTexto],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => $grisBorde],
                            ],
                        ],
                    ]);

                    // Filas alternadas.
                    for ($fila = $primeraFilaDatos; $fila <= $ultimaFila; $fila++) {
                        if ($fila % 2 === 0) {
                            $sheet->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $grisFila],
                                ],
                            ]);
                        }
                    }

                    // Columnas escolares: Generación, Grado, Semestre y Grupo.
                    $columnaInicioEscolar = 'I';
                    $columnaFinEscolar = $this->esBachillerato ? 'L' : 'K';

                    $sheet->getStyle("{$columnaInicioEscolar}{$primeraFilaDatos}:{$columnaFinEscolar}{$ultimaFila}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $azulSuave],
                        ],
                    ]);

                    // Género H / M.
                    for ($fila = $primeraFilaDatos; $fila <= $ultimaFila; $fila++) {
                        $genero = $sheet->getCell("H{$fila}")->getValue();

                        if ($genero === 'H') {
                            $sheet->getStyle("H{$fila}")->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => $azulMoctezuma],
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $azulSuave],
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                ],
                            ]);
                        }

                        if ($genero === 'M') {
                            $sheet->getStyle("H{$fila}")->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => '9D174D'],
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $rosaClaro],
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                ],
                            ]);
                        }
                    }

                    // Matrícula con fondo verde suave.
                    $sheet->getStyle("B{$primeraFilaDatos}:B{$ultimaFila}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '166534'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $verdeClaro],
                        ],
                    ]);
                }

                $sheet->getStyle("A:{$ultimaColumna}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A:A")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("H:{$ultimaColumna}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane("A{$primeraFilaDatos}");
                $sheet->setAutoFilter("A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}");

                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(3)->setRowHeight(26);
                $sheet->getRowDimension(4)->setRowHeight(22);
                $sheet->getRowDimension(5)->setRowHeight(22);
                $sheet->getRowDimension($filaEncabezados)->setRowHeight(34);

                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(22);
                $sheet->getColumnDimension('E')->setWidth(22);
                $sheet->getColumnDimension('F')->setWidth(26);
                $sheet->getColumnDimension('G')->setWidth(22);
                $sheet->getColumnDimension('H')->setWidth(12);
                $sheet->getColumnDimension('I')->setWidth(20);
                $sheet->getColumnDimension('J')->setWidth(18);

                if ($this->esBachillerato) {
                    $sheet->getColumnDimension('K')->setWidth(16);
                    $sheet->getColumnDimension('L')->setWidth(16);
                } else {
                    $sheet->getColumnDimension('K')->setWidth(16);
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.4);
                $sheet->getPageMargins()->setRight(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setBottom(0.4);
            },
        ];
    }
}
