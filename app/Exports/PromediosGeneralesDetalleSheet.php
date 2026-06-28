<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PromediosGeneralesDetalleSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    protected array $filas = [];

    protected array $filasGrupo = [];

    protected array $filasEncabezado = [];

    protected int $totalColumnas = 0;

    public function __construct(
        protected string $nivelNombre,
        protected bool $esBachillerato,
        protected array $encabezadosPeriodos,
        protected Collection $gruposPromedios,
    ) {
        $this->totalColumnas = 6 + count($this->encabezadosPeriodos);

        $this->prepararFilas();
    }

    public function title(): string
    {
        return 'Detalle';
    }

    public function array(): array
    {
        return $this->filas;
    }

    protected function prepararFilas(): void
    {
        $this->filas[] = ['DETALLE DE PROMEDIOS - ' . mb_strtoupper($this->nivelNombre)];
        $this->filas[] = ['Exportación generada el ' . now()->format('d/m/Y H:i')];
        $this->filas[] = [];

        foreach ($this->gruposPromedios as $grupoPromedio) {
            $this->filasGrupo[] = count($this->filas) + 1;

            $this->filas[] = [
                $grupoPromedio['titulo'] ?? 'Grupo sin nombre',
            ];

            $this->filas[] = [
                'Total de alumnos',
                $grupoPromedio['total'] ?? 0,
                'Promedio',
                $grupoPromedio['promedio'] ?? '—',
                'Aprobados',
                $grupoPromedio['aprobados'] ?? 0,
                'En riesgo',
                $grupoPromedio['riesgo'] ?? 0,
                'Incompletos',
                $grupoPromedio['incompletos'] ?? 0,
            ];

            $this->filasEncabezado[] = count($this->filas) + 1;

            $encabezados = [
                '#',
                'Alumno',
                'Matrícula',
            ];

            foreach ($this->encabezadosPeriodos as $etiqueta) {
                $encabezados[] = $etiqueta;
            }

            $encabezados[] = 'Suma';
            $encabezados[] = 'Promedio';
            $encabezados[] = 'Estatus';

            $this->filas[] = $encabezados;

            foreach (($grupoPromedio['alumnos'] ?? []) as $index => $alumno) {
                $fila = [
                    $index + 1,
                    $alumno['alumno'] ?? 'Sin nombre',
                    $alumno['matricula'] ?? '—',
                ];

                foreach ($this->encabezadosPeriodos as $periodo => $etiqueta) {
                    $fila[] = isset($alumno['periodos'][$periodo]) && $alumno['periodos'][$periodo] !== null
                        ? $this->formatearDecimal($alumno['periodos'][$periodo])
                        : 'Pendiente';
                }

                $fila[] = collect($alumno['periodos'] ?? [])->contains(fn ($valor) => $valor !== null)
                    ? $this->formatearDecimal($alumno['suma_periodos'] ?? null)
                    : 'Pendiente';
                $promedioMostrar = $alumno['promedio_final'] ?? $alumno['promedio_provisional'] ?? null;
                $fila[] = $promedioMostrar !== null
                    ? $this->formatearDecimal($promedioMostrar) . (($alumno['completo'] ?? false) ? '' : ' PROV.')
                    : 'Pendiente';
                $fila[] = $alumno['estatus'] ?? 'Sin captura';

                $this->filas[] = $fila;
            }

            $this->filas[] = [];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();

                $ultimaColumna = Coordinate::stringFromColumnIndex($this->totalColumnas);
                $ultimaFila = $hoja->getHighestRow();

                $hoja->mergeCells("A1:{$ultimaColumna}1");
                $hoja->mergeCells("A2:{$ultimaColumna}2");

                $hoja->getRowDimension(1)->setRowHeight(30);
                $hoja->getRowDimension(2)->setRowHeight(22);

                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $hoja->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '475569'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                foreach ($this->filasGrupo as $fila) {
                    $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");

                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 13,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '006492'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $hoja->getRowDimension($fila)->setRowHeight(24);
                }

                foreach ($this->filasEncabezado as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '334155'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);

                    $hoja->getRowDimension($fila)->setRowHeight(24);
                }

                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")
                    ->getAlignment()
                    ->setWrapText(true);

                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")
                    ->getFont()
                    ->setName('Arial')
                    ->setSize(10);

                $hoja->getStyle("A1:{$ultimaColumna}2")
                    ->getFont()
                    ->setName('Arial');

                $hoja->freezePane('A4');

                $hoja->getColumnDimension('A')->setWidth(8);
                $hoja->getColumnDimension('B')->setWidth(38);
                $hoja->getColumnDimension('C')->setWidth(18);

                for ($columna = 4; $columna <= $this->totalColumnas; $columna++) {
                    $letra = Coordinate::stringFromColumnIndex($columna);
                    $hoja->getColumnDimension($letra)->setWidth(15);
                    $hoja->getStyle("{$letra}1:{$letra}{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $hoja->getStyle("A1:A{$ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $hoja->getStyle("B1:B{$ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $hoja->getStyle("C1:C{$ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                for ($fila = 1; $fila <= $ultimaFila; $fila++) {
                    $valor = (string) $hoja->getCell("{$ultimaColumna}{$fila}")->getValue();

                    if ($valor === 'Incompleto' || $valor === 'En riesgo') {
                        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEF2F2'],
                            ],
                            'font' => [
                                'color' => ['rgb' => '991B1B'],
                            ],
                        ]);
                    }

                    if ($valor === 'Aprobado') {
                        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'EFF6FF'],
                            ],
                            'font' => [
                                'color' => ['rgb' => '1E3A8A'],
                            ],
                        ]);
                    }

                    if ($valor === 'Destacado') {
                        $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'ECFDF5'],
                            ],
                            'font' => [
                                'color' => ['rgb' => '065F46'],
                            ],
                        ]);
                    }
                }
            },
        ];
    }

    protected function formatearDecimal(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '0.0');
    }
}
