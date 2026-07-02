<?php

namespace App\Exports;

use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstadisticaCorteSheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private string $titulo,
        private string $nivelNombre,
        private string $cicloEscolarTexto,
        private string $generacionTexto,
        private array $filas,
        private array $totales,
        private array $bloques,
    ) {}

    public function title(): string
    {
        return mb_substr($this->titulo, 0, 31);
    }

    public function array(): array
    {
        $columnas = $this->columnasTotales();

        $datos = [
            ['CENTRO UNIVERSITARIO MOCTEZUMA'],
            ['Nivel: ' . $this->nivelNombre . '    |    Ciclo escolar: ' . $this->cicloEscolarTexto . '    |    Generación: ' . $this->generacionTexto],
            [],
            [$this->titulo],
            $this->filaEncabezadoPrincipal(),
            $this->filaEncabezadoSexos(),
        ];

        foreach ($this->filas as $fila) {
            $datos[] = $this->filaEstadistica($fila);
        }

        $datos[] = $this->filaTotales();
        $datos[] = [];
        $datos[] = ['Nota: H = hombres, M = mujeres, T = total.'];
        $datos[] = ['Los datos se calculan desde la matrícula actual agrupada por generación y estatus.'];

        return $datos;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            6 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->aplicarFormatoGeneral($sheet);
                $this->aplicarFormatoEncabezados($sheet);
                $this->aplicarFormatoTabla($sheet);
                $this->aplicarConfiguracionPagina($sheet);
            },
        ];
    }

    private function filaEncabezadoPrincipal(): array
    {
        $fila = ['GRADO'];

        foreach ($this->bloques as $bloque) {
            $fila[] = $bloque;
            $fila[] = '';
            $fila[] = '';
        }

        return $fila;
    }

    private function filaEncabezadoSexos(): array
    {
        $fila = [''];

        foreach ($this->bloques as $bloque => $titulo) {
            $fila[] = 'H';
            $fila[] = 'M';
            $fila[] = 'T';
        }

        return $fila;
    }

    private function filaEstadistica(array $fila): array
    {
        $datos = [$fila['grado'] ?? '—'];

        foreach ($this->bloques as $bloque => $titulo) {
            $datos[] = Arr::get($fila, $bloque . '.h', 0);
            $datos[] = Arr::get($fila, $bloque . '.m', 0);
            $datos[] = Arr::get($fila, $bloque . '.t', 0);
        }

        return $datos;
    }

    private function filaTotales(): array
    {
        $fila = ['TOTAL'];

        foreach ($this->bloques as $bloque => $titulo) {
            $fila[] = Arr::get($this->totales, $bloque . '.h', 0);
            $fila[] = Arr::get($this->totales, $bloque . '.m', 0);
            $fila[] = Arr::get($this->totales, $bloque . '.t', 0);
        }

        return $fila;
    }

    private function aplicarFormatoGeneral(Worksheet $sheet): void
    {
        $ultimaColumna = $this->columnaLetra($this->columnasTotales());
        $ultimaFila = $this->ultimaFilaTabla();

        $sheet->mergeCells("A1:{$ultimaColumna}1");
        $sheet->mergeCells("A2:{$ultimaColumna}2");
        $sheet->mergeCells("A4:{$ultimaColumna}4");
        $sheet->mergeCells('A5:A6');

        $sheet->getStyle("A1:{$ultimaColumna}1")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('006492');

        $sheet->getStyle("A4:{$ultimaColumna}4")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('88AC2E');

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(24);
        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->getRowDimension(6)->setRowHeight(20);

        $sheet->freezePane('B7');
        $sheet->setAutoFilter("A6:{$ultimaColumna}{$ultimaFila}");
    }

    private function aplicarFormatoEncabezados(Worksheet $sheet): void
    {
        $ultimaColumna = $this->columnaLetra($this->columnasTotales());
        $columnaActual = 2;

        $sheet->getStyle("A5:{$ultimaColumna}6")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '111827'],
                ],
            ],
        ]);

        $sheet->getStyle("A5:A6")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('0F172A');

        foreach ($this->bloques as $bloque => $titulo) {
            $inicio = $this->columnaLetra($columnaActual);
            $fin = $this->columnaLetra($columnaActual + 2);

            $sheet->mergeCells("{$inicio}5:{$fin}5");

            $sheet->getStyle("{$inicio}5:{$fin}5")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB($this->colorBloque($bloque));

            $sheet->getStyle("{$inicio}6:{$fin}6")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('E2E8F0');

            $columnaActual += 3;
        }
    }

    private function aplicarFormatoTabla(Worksheet $sheet): void
    {
        $ultimaColumna = $this->columnaLetra($this->columnasTotales());
        $ultimaFila = $this->ultimaFilaTabla();
        $filaTotal = $ultimaFila;

        $sheet->getStyle("A7:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '111827'],
                ],
            ],
        ]);

        for ($fila = 7; $fila < $filaTotal; $fila++) {
            $color = $fila % 2 === 0 ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$fila}:{$ultimaColumna}{$fila}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB($color);
        }

        $sheet->getStyle("A{$filaTotal}:{$ultimaColumna}{$filaTotal}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
        ]);

        $notaUno = $filaTotal + 2;
        $notaDos = $filaTotal + 3;

        $sheet->mergeCells("A{$notaUno}:{$ultimaColumna}{$notaUno}");
        $sheet->mergeCells("A{$notaDos}:{$ultimaColumna}{$notaDos}");

        $sheet->getStyle("A{$notaUno}:{$ultimaColumna}{$notaDos}")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '475569']],
            'alignment' => ['wrapText' => true],
        ]);

        $sheet->getColumnDimension('A')->setWidth(14);
    }

    private function aplicarConfiguracionPagina(Worksheet $sheet): void
    {
        $ultimaColumna = $this->columnaLetra($this->columnasTotales());
        $ultimaFila = $this->ultimaFilaTabla() + 3;

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()->setTop(0.35);
        $sheet->getPageMargins()->setRight(0.25);
        $sheet->getPageMargins()->setLeft(0.25);
        $sheet->getPageMargins()->setBottom(0.35);

        $sheet->getPageSetup()->setPrintArea("A1:{$ultimaColumna}{$ultimaFila}");
    }

    private function columnasTotales(): int
    {
        return 1 + (count($this->bloques) * 3);
    }

    private function ultimaFilaTabla(): int
    {
        return 6 + count($this->filas) + 1;
    }

    private function columnaLetra(int $indice): string
    {
        return Coordinate::stringFromColumnIndex($indice);
    }

    private function colorBloque(string $bloque): string
    {
        return match ($bloque) {
            'inicial' => '334155',
            'altas' => '2563EB',
            'inscripcion_total' => '4F46E5',
            'bajas' => 'E11D48',
            'existencia' => '059669',
            'promovidos' => '84CC16',
            'no_promovidos' => '64748B',
            default => '006492',
        };
    }
}
