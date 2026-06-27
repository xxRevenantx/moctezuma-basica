<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PromediosMateriasDetalleSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    protected array $filasGrupo = [];
    protected array $filasAlumno = [];
    protected array $filasEncabezado = [];

    public function __construct(
        protected string $nivelNombre,
        protected Collection $grupos,
    ) {
        //
    }

    public function title(): string
    {
        return 'Detalle por materia';
    }

    public function array(): array
    {
        $filas = [];
        $filas[] = ['DETALLE ANUAL POR MATERIA - ' . mb_strtoupper($this->nivelNombre)];
        $filas[] = ['Exportación generada el ' . now()->format('d/m/Y H:i')];
        $filas[] = [];

        foreach ($this->grupos as $grupo) {
            $this->filasGrupo[] = count($filas) + 1;
            $filas[] = [
                ($grupo['titulo'] ?? 'Grupo') .
                ' | Generación: ' . ($grupo['generacion'] ?? 'Sin generación') .
                ' | Promedio: ' . $this->formatear($grupo['promedio_grupo'] ?? null) .
                ' (' . (($grupo['completo'] ?? false) ? 'Definitivo' : 'Provisional') . ')',
            ];

            foreach (($grupo['alumnos'] ?? []) as $alumno) {
                $this->filasAlumno[] = count($filas) + 1;
                $filas[] = [
                    $alumno['alumno'] ?? 'Sin alumno',
                    'Matrícula: ' . ($alumno['matricula'] ?? '—'),
                    'Promedio general: ' . $this->formatear($alumno['promedio_general'] ?? null),
                    $alumno['estatus'] ?? 'Sin estado',
                ];

                $this->filasEncabezado[] = count($filas) + 1;
                $filas[] = [
                    '#',
                    'Materia',
                    'Periodo 1',
                    'Periodo 2',
                    'Periodo 3',
                    'Suma',
                    'Promedio final',
                    'Promedio provisional',
                    'Estado',
                ];

                foreach (($alumno['materias'] ?? []) as $indice => $materia) {
                    $filas[] = [
                        $indice + 1,
                        $materia['materia'] ?? 'Sin materia',
                        $this->formatear($materia['periodos'][1] ?? null),
                        $this->formatear($materia['periodos'][2] ?? null),
                        $this->formatear($materia['periodos'][3] ?? null),
                        $this->formatear($materia['suma_periodos'] ?? null),
                        $this->formatear($materia['promedio_materia'] ?? null),
                        $this->formatear($materia['promedio_provisional'] ?? null),
                        $materia['estatus'] ?? 'Sin captura',
                    ];
                }

                $filas[] = [];
            }

            $filas[] = [];
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = $hoja->getHighestRow();
                $ultimaColumna = 'I';

                $hoja->mergeCells("A1:{$ultimaColumna}1");
                $hoja->mergeCells("A2:{$ultimaColumna}2");

                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                foreach ($this->filasGrupo as $fila) {
                    $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    ]);
                }

                foreach ($this->filasAlumno as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '14532D']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    ]);
                }

                foreach ($this->filasEncabezado as $fila) {
                    $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $hoja->getColumnDimension('A')->setWidth(8);
                $hoja->getColumnDimension('B')->setWidth(38);
                foreach (range('C', 'I') as $columna) {
                    $hoja->getColumnDimension($columna)->setWidth(18);
                    $hoja->getStyle("{$columna}1:{$columna}{$ultimaFila}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $hoja->freezePane('A4');
            },
        ];
    }

    private function formatear(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }
}
