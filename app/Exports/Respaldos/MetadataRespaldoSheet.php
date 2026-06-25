<?php

namespace App\Exports\Respaldos;

use App\Support\RespaldoAcademico;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MetadataRespaldoSheet implements FromArray, WithTitle, WithEvents
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
        return '__metadata';
    }

    public function array(): array
    {
        return [
            ['formato', 'moctezuma_respaldo_academico'],
            ['version', RespaldoAcademico::VERSION_FORMATO],
            ['tipo', $this->configuracion['tipo']],
            ['generado_at', now()->toIso8601String()],
            ['tablas', json_encode(array_keys($this->configuracion['tablas']), JSON_UNESCAPED_UNICODE)],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
            },
        ];
    }
}
