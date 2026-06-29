<?php

namespace App\Exports;

use App\Support\PromedioExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;

class CalificacionesOficialesPrimariaExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly array $reporte,
        private readonly string $ciclo,
    ) {
    }

    public function title(): string
    {
        return 'Evaluación oficial';
    }

    public function array(): array
    {
        $campos = $this->reporte['campos'];
        $filas = [];

        $encabezado1 = ['ALUMNO', 'MATRÍCULA', 'GRADO', 'GRUPO'];
        $encabezado2 = ['', '', '', ''];

        foreach ($campos as $campo) {
            $encabezado1[] = mb_strtoupper($campo->nombre);
            $encabezado1[] = '';
            $encabezado1[] = '';
            $encabezado1[] = '';
            $encabezado2[] = '1er periodo';
            $encabezado2[] = '2do periodo';
            $encabezado2[] = '3er periodo';
            $encabezado2[] = 'Promedio final';
        }

        $encabezado1[] = 'PROMEDIO FINAL DE GRADO';
        $encabezado1[] = 'PROMOCIÓN SUGERIDA';
        $encabezado1[] = 'PROMOCIÓN CONFIRMADA';
        $encabezado2[] = '';
        $encabezado2[] = '';
        $encabezado2[] = '';

        $filas[] = ['EVALUACIÓN OFICIAL DE PRIMARIA · CICLO ' . $this->ciclo];
        $filas[] = $encabezado1;
        $filas[] = $encabezado2;

        foreach ($this->reporte['alumnos'] as $alumno) {
            $fila = [
                $alumno['alumno'],
                $alumno['matricula'],
                $alumno['grado'],
                $alumno['grupo'],
            ];

            foreach ($campos as $campo) {
                $datos = $alumno['campos'][$campo->id];
                $fila[] = $datos['periodos'][1];
                $fila[] = $datos['periodos'][2];
                $fila[] = $datos['periodos'][3];
                // El promedio oficial del campo ya está truncado a un decimal.
                $fila[] = $datos['final_preciso'];
            }

            $fila[] = $alumno['promedio_general_preciso'];
            $fila[] = $alumno['promocion_sugerida'] === null
                ? 'PENDIENTE'
                : ($alumno['promocion_sugerida'] ? 'PROMOVIDA(O)' : 'NO PROMOVIDA(O)');
            $fila[] = $alumno['promocion_confirmada'] === null
                ? 'PENDIENTE'
                : ($alumno['promocion_confirmada'] ? 'PROMOVIDA(O)' : 'NO PROMOVIDA(O)');

            $filas[] = $fila;
        }

        $filas[] = [];
        $filas[] = [
            'NOTA: El promedio final de cada campo se obtiene de sus tres periodos y se trunca a un decimal. El promedio final de grado es la suma de los cuatro promedios oficiales de campo dividida entre cuatro; el resultado se presenta con un decimal truncado.',
        ];

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                ]);

                // Columnas fijas de alumno, matrícula, grado y grupo.
                foreach (range(1, 4) as $indiceColumna) {
                    $letra = Coordinate::stringFromColumnIndex($indiceColumna);
                    $sheet->mergeCells("{$letra}2:{$letra}3");
                }

                $indiceInicialCampo = 5;
                foreach ($this->reporte['campos'] as $campo) {
                    $inicio = Coordinate::stringFromColumnIndex($indiceInicialCampo);
                    $fin = Coordinate::stringFromColumnIndex($indiceInicialCampo + 3);
                    $sheet->mergeCells("{$inicio}2:{$fin}2");
                    $sheet->getStyle("{$inicio}2:{$fin}3")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB(ltrim((string) $campo->color_fondo, '#'));
                    $sheet->getStyle("{$inicio}2:{$fin}3")->getFont()
                        ->getColor()->setRGB(ltrim((string) $campo->color_texto, '#'));
                    $indiceInicialCampo += 4;
                }

                // Los promedios oficiales se muestran con un decimal.
                $primeraFilaDatos = 4;
                $ultimaFilaDatos = max($primeraFilaDatos, $highestRow - 2);
                $indiceCampo = 5;
                foreach ($this->reporte['campos'] as $campo) {
                    $columnaFinal = Coordinate::stringFromColumnIndex($indiceCampo + 3);
                    $sheet->getStyle("{$columnaFinal}{$primeraFilaDatos}:{$columnaFinal}{$ultimaFilaDatos}")
                        ->getNumberFormat()
                        ->setFormatCode('0.0');
                    $indiceCampo += 4;
                }

                $columnaPromedioGeneral = Coordinate::stringFromColumnIndex($indiceInicialCampo);
                $sheet->getStyle("{$columnaPromedioGeneral}{$primeraFilaDatos}:{$columnaPromedioGeneral}{$ultimaFilaDatos}")
                    ->getNumberFormat()
                    ->setFormatCode('0.0');

                // Promedio y promoción ocupan dos filas de encabezado.
                for ($indice = $indiceInicialCampo; $indice <= $indiceInicialCampo + 2; $indice++) {
                    $letra = Coordinate::stringFromColumnIndex($indice);
                    $sheet->mergeCells("{$letra}2:{$letra}3");
                }

                $sheet->getStyle("A2:{$highestColumn}3")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                $sheet->getStyle('A2:D3')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF8C5');
                $inicioFinal = Coordinate::stringFromColumnIndex($indiceInicialCampo);
                $sheet->getStyle("{$inicioFinal}2:{$highestColumn}3")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFE45C');

                $sheet->getStyle("A4:{$highestColumn}" . max(4, $highestRow - 2))->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->freezePane('E4');
                $sheet->getPageSetup()->setOrientation('landscape');
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.3)->setRight(0.3)->setBottom(0.3)->setLeft(0.3);
                $sheet->getStyle("A{$highestRow}:{$highestColumn}{$highestRow}")
                    ->getAlignment()->setWrapText(true);
            },
        ];
    }
}
