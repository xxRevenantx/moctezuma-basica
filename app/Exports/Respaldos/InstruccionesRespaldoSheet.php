<?php

namespace App\Exports\Respaldos;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InstruccionesRespaldoSheet implements FromArray, WithTitle, WithEvents
{
    /**
     * @param array{
     *     tipo:string,
     *     titulo:string,
     *     descripcion:string,
     *     prefijo_archivo:string,
     *     tablas:array<string,array{hoja:string,descripcion:string,diferidas:array<int,string>}>
     * } $configuracion
     */
    public function __construct(private readonly array $configuracion)
    {
    }

    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        $filas = [
            [$this->configuracion['titulo']],
            [$this->configuracion['descripcion']],
            ['Generado', now()->format('d/m/Y H:i:s')],
            ['Regla principal', 'La columna id está protegida y nunca debe modificarse.'],
            ['Importación', 'Actualiza el registro con el mismo ID o lo crea usando exactamente ese ID.'],
            ['Eliminaciones', 'La importación no elimina registros que no aparezcan en el archivo.'],
            ['Seguridad', 'Toda la importación se ejecuta en una transacción. Si una fila falla, no se guarda ningún cambio.'],
            ['Relaciones', 'Los catálogos, usuarios y registros relacionados deben existir con los mismos IDs.'],
            [''],
            ['HOJA', 'TABLA', 'REGISTROS', 'CONTENIDO'],
        ];

        foreach ($this->configuracion['tablas'] as $tabla => $datos) {
            $filas[] = [
                $datos['hoja'],
                $tabla,
                DB::table($tabla)->count(),
                $datos['descripcion'],
            ];
        }

        $filas[] = [''];
        $filas[] = ['IMPORTANTE'];
        $filas[] = ['1.', 'No cambies el nombre de las hojas.'];
        $filas[] = ['2.', 'No elimines la columna id ni la columna interna oculta.'];
        $filas[] = ['3.', 'No cambies un ID para intentar mover información entre alumnos o calificaciones.'];
        $filas[] = ['4.', 'Antes de importar, conserva una copia adicional de este archivo y un respaldo SQL.'];
        $filas[] = ['5.', 'Los campos vacíos se importan como NULL cuando la base de datos lo permite.'];

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = $hoja->getHighestRow();

                $hoja->mergeCells('A1:D1');
                $hoja->mergeCells('A2:D2');
                $hoja->getRowDimension(1)->setRowHeight(34);
                $hoja->getRowDimension(2)->setRowHeight(30);

                $hoja->getStyle('A1:D1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
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
                        'italic' => true,
                        'color' => ['rgb' => '334155'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0F2FE'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->getStyle('A4:B8')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => ['wrapText' => true],
                ]);

                $hoja->getStyle('A4:A8')->getFont()->setBold(true);
                $hoja->getStyle('A10:D10')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '88AC2E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $cantidadTablas = count($this->configuracion['tablas']);
                $finTablas = 10 + $cantidadTablas;

                $hoja->getStyle("A10:D{$finTablas}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                $filaImportante = $finTablas + 2;
                $hoja->mergeCells("A{$filaImportante}:D{$filaImportante}");
                $hoja->getStyle("A{$filaImportante}:D{$filaImportante}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '991B1B'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEE2E2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                for ($fila = $filaImportante + 1; $fila <= $ultimaFila; $fila++) {
                    $hoja->mergeCells("B{$fila}:D{$fila}");
                }

                $hoja->getStyle("A1:D{$ultimaFila}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $hoja->getColumnDimension('A')->setWidth(24);
                $hoja->getColumnDimension('B')->setWidth(30);
                $hoja->getColumnDimension('C')->setWidth(14);
                $hoja->getColumnDimension('D')->setWidth(70);
                $hoja->freezePane('A3');
            },
        ];
    }
}
