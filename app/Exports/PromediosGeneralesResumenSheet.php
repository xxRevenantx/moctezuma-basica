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

class PromediosGeneralesResumenSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        protected string $nivelNombre,
        protected bool $esBachillerato,
        protected array $resumen,
        protected array $filtros = [],
    ) {
        //
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $filas = [];

        $filas[] = ['CONCENTRADO FINAL DE ' . mb_strtoupper($this->nivelNombre)];
        $filas[] = ['Exportación generada el ' . now()->format('d/m/Y H:i')];
        $filas[] = [];

        $filas[] = ['FILTROS APLICADOS'];
        $filas[] = ['Filtro', 'Valor'];

        foreach ($this->filtros as $nombre => $valor) {
            $filas[] = [$nombre, $valor ?: 'Todos'];
        }

        $filas[] = [];
        $filas[] = ['RESUMEN GENERAL'];
        $filas[] = ['Indicador', 'Valor'];

        $filas[] = ['Total de alumnos', $this->resumen['total_alumnos'] ?? 0];
        $filas[] = ['Promedio general', $this->formatearDecimal($this->resumen['promedio_general'] ?? 0)];
        $filas[] = ['Aprobados', $this->resumen['aprobados'] ?? 0];
        $filas[] = ['En riesgo', $this->resumen['riesgo'] ?? 0];
        $filas[] = ['Incompletos', $this->resumen['incompletos'] ?? 0];
        $filas[] = ['Mejor promedio', $this->formatearDecimal($this->resumen['mejor_promedio'] ?? 0)];
        $filas[] = ['Mejor alumno', $this->resumen['mejor_alumno'] ?? 'Sin datos'];

        $filas[] = [];
        $filas[] = ['FÓRMULA APLICADA'];

        if ($this->esBachillerato) {
            $filas[] = ['Promedio semestral', '(Parcial 1 + Parcial 2) / parciales capturados'];
        } else {
            $filas[] = ['Promedio anual', '(Periodo 1 + Periodo 2 + Periodo 3) / periodos capturados'];
        }

        $filas[] = ['Nota', 'Solo se toman calificaciones numéricas. Se ignoran textos como AC, NP, SD, ED, RA y pendientes.'];

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();

                $hoja->mergeCells('A1:D1');
                $hoja->mergeCells('A2:D2');

                $hoja->getRowDimension(1)->setRowHeight(30);
                $hoja->getRowDimension(2)->setRowHeight(22);

                $hoja->getColumnDimension('A')->setWidth(28);
                $hoja->getColumnDimension('B')->setWidth(45);
                $hoja->getColumnDimension('C')->setWidth(20);
                $hoja->getColumnDimension('D')->setWidth(20);

                $hoja->getStyle('A1:D1')->applyFromArray([
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

                $hoja->getStyle('A2:D2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '475569'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                foreach ([4, 12, 21] as $filaTitulo) {
                    $hoja->mergeCells("A{$filaTitulo}:D{$filaTitulo}");

                    $hoja->getStyle("A{$filaTitulo}:D{$filaTitulo}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '88AC2E'],
                        ],
                    ]);
                }

                foreach ([5, 13] as $filaEncabezado) {
                    $hoja->getStyle("A{$filaEncabezado}:B{$filaEncabezado}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '334155'],
                        ],
                    ]);
                }

                $ultimaFila = $hoja->getHighestRow();

                $hoja->getStyle("A1:D{$ultimaFila}")->applyFromArray([
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

                $hoja->getStyle("B14:B19")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }

    protected function formatearDecimal(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '0.0');
    }
}
