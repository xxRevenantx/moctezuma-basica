<?php

namespace App\Exports\Periodos;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InstruccionesPeriodosSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return [
            ['PLANTILLA PARA IMPORTAR PERIODOS'],
            [],
            ['Regla', 'Descripción'],
            ['1', 'Captura la información únicamente en la hoja “Periodos”.'],
            ['2', 'No cambies los encabezados ni el nombre de la hoja “Periodos”.'],
            ['3', 'Selecciona BASICA para preescolar, primaria o secundaria; selecciona BACHILLERATO para bachillerato.'],
            ['4', 'Usa las listas desplegables. El número ubicado antes de “|” es el ID utilizado por el sistema.'],
            ['5', 'Básica requiere: nivel, ciclo escolar, mes básica y periodo básica. Deja vacíos los campos de bachillerato.'],
            ['6', 'Bachillerato requiere: nivel, ciclo, generación, semestre, mes bachillerato y parcial. Deja vacíos los campos de básica.'],
            ['7', 'Las fechas deben escribirse como AAAA-MM-DD. Si capturas una fecha, debes capturar también la otra.'],
            ['8', 'Si un periodo ya existe, la importación actualizará sus fechas; no creará un duplicado.'],
            ['9', 'La importación es transaccional: si una fila tiene errores, no se guardará ninguna fila.'],
            ['10', 'La hoja “Ejemplos” es solo informativa y no se importa.'],
            [],
            ['Campos por tipo'],
            ['BASICA', 'tipo, nivel_id, ciclo_escolar_id, mes_basica_id, periodo_basica_id, fecha_inicio, fecha_fin'],
            ['BACHILLERATO', 'tipo, nivel_id, ciclo_escolar_id, generacion_id, semestre_id, mes_bachillerato_id, parcial_bachillerato_id, fecha_inicio, fecha_fin'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $hoja->mergeCells('A1:B1');
                $hoja->getStyle('A1:B1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                $hoja->getStyle('A3:B3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '88AC2E'],
                    ],
                ]);
                $hoja->getStyle('A4:B17')->getAlignment()->setWrapText(true);
                $hoja->getColumnDimension('A')->setWidth(20);
                $hoja->getColumnDimension('B')->setWidth(110);
            },
        ];
    }
}
