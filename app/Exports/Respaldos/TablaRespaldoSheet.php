<?php

namespace App\Exports\Respaldos;

use DateTimeInterface;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class TablaRespaldoSheet extends DefaultValueBinder implements
    FromGenerator,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithEvents,
    WithCustomValueBinder,
    WithStrictNullComparison
{
    /** @var array<int,string> */
    private array $columnas;

    public function __construct(
        private readonly string $tabla,
        private readonly string $tituloHoja,
        private readonly string $descripcion
    ) {
        $this->columnas = Schema::getColumnListing($this->tabla);
    }

    public function title(): string
    {
        return mb_substr($this->tituloHoja, 0, 31);
    }

    public function headings(): array
    {
        return [...$this->columnas, '__id_original'];
    }

    public function generator(): Generator
    {
        foreach (DB::table($this->tabla)->orderBy('id')->cursor() as $registro) {
            yield $registro;
        }
    }

    public function map($registro): array
    {
        $fila = [];

        foreach ($this->columnas as $columna) {
            $fila[] = $this->normalizarValor($registro->{$columna} ?? null);
        }

        $fila[] = isset($registro->id) ? (string) $registro->id : null;

        return $fila;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = max(1, $hoja->getHighestRow());
                $ultimaColumnaNumero = count($this->columnas) + 1;
                $ultimaColumna = Coordinate::stringFromColumnIndex($ultimaColumnaNumero);
                $columnaOriginal = $ultimaColumna;
                $indiceId = array_search('id', $this->columnas, true);
                $columnaId = $indiceId === false
                    ? 'A'
                    : Coordinate::stringFromColumnIndex($indiceId + 1);

                $hoja->freezePane('A2');
                $hoja->setAutoFilter("A1:{$ultimaColumna}1");
                $hoja->getRowDimension(1)->setRowHeight(28);

                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->getStyle("{$columnaId}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '991B1B'],
                    ],
                ]);

                $hoja->getComment("{$columnaId}1")->getText()->createTextRun(
                    "IDENTIFICADOR PROTEGIDO. No debe modificarse.\n"
                    . 'Durante la importación se usa únicamente como llave y nunca se actualiza el ID de un registro existente.'
                );

                if ($ultimaFila >= 2) {
                    $hoja->getStyle("A2:{$ultimaColumna}{$ultimaFila}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_TEXT);

                    // Se permite editar datos, excepto el ID y la firma interna del ID.
                    $hoja->getStyle("A2:{$ultimaColumna}{$ultimaFila}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_UNPROTECTED);

                    $hoja->getStyle("{$columnaId}2:{$columnaId}{$ultimaFila}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_PROTECTED);

                    $hoja->getStyle("{$columnaOriginal}2:{$columnaOriginal}{$ultimaFila}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_PROTECTED);
                }

                $hoja->getColumnDimension($columnaOriginal)->setVisible(false);

                foreach ($this->columnas as $indice => $columna) {
                    $letra = Coordinate::stringFromColumnIndex($indice + 1);
                    $ancho = match (true) {
                        $columna === 'id', str_ends_with($columna, '_id') => 14,
                        str_contains($columna, 'fecha'),
                        in_array($columna, ['created_at', 'updated_at', 'deleted_at'], true) => 22,
                        str_contains($columna, 'observacion'),
                        str_contains($columna, 'motivo'),
                        str_starts_with($columna, 'estado_') => 36,
                        in_array($columna, ['curp', 'matricula', 'folio'], true) => 22,
                        default => 24,
                    };

                    $hoja->getColumnDimension($letra)->setWidth($ancho);
                }

                $hoja->getStyle("A1:{$ultimaColumna}{$ultimaFila}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $hoja->getProtection()
                    ->setPassword('moctezuma-respaldo')
                    ->setSheet(true)
                    ->setSort(true)
                    ->setAutoFilter(true)
                    ->setSelectUnlockedCells(true)
                    ->setSelectLockedCells(false);

                $hoja->getComment('A1')->getText()->createTextRun(
                    $this->descripcion . "\nTabla de origen: {$this->tabla}"
                );
            },
        ];
    }

    private function normalizarValor(mixed $valor): mixed
    {
        if ($valor === null) {
            return null;
        }

        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if (is_array($valor) || is_object($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $valor;
    }
}
