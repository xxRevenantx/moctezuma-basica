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

class PromediosGeneralesAcademicoSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private array $filas = [];

    public function __construct(
        protected string $nivelNombre,
        protected string $nivelSlug,
        protected Collection $gruposPromedios,
    ) {
        $this->preparar();
    }

    public function title(): string
    {
        return $this->nivelSlug === 'primaria' ? 'Campos y materias' : 'Materias y campos';
    }

    public function array(): array
    {
        return $this->filas;
    }

    private function preparar(): void
    {
        $this->filas[] = ['DETALLE ACADÉMICO - ' . mb_strtoupper($this->nivelNombre)];
        $this->filas[] = [$this->nivelSlug === 'primaria'
            ? 'Cada promedio final de campo se establece con un decimal truncado antes de calcular el promedio general.'
            : 'Los cálculos conservan precisión completa y se truncan únicamente al presentar.'];
        $this->filas[] = [];
        $this->filas[] = [
            'Grupo',
            'Alumno',
            'Matrícula',
            'Campo formativo',
            'Materia',
            'Participa',
            '1er periodo',
            '2do periodo',
            '3er periodo',
            'Promedio anual de materia',
            'Promedio final de campo',
            'Promedio general',
            'Estado',
        ];

        foreach ($this->gruposPromedios as $grupo) {
            foreach (($grupo['alumnos'] ?? []) as $alumno) {
                if ($this->nivelSlug === 'primaria') {
                    foreach (($alumno['campos'] ?? []) as $campo) {
                        $materias = collect($campo['materias'] ?? []);

                        if ($materias->isEmpty()) {
                            $this->filas[] = $this->fila(
                                grupo: $grupo['titulo'] ?? 'Sin grupo',
                                alumno: $alumno,
                                campo: $campo,
                                materia: null,
                            );
                            continue;
                        }

                        foreach ($materias as $materia) {
                            $this->filas[] = $this->fila(
                                grupo: $grupo['titulo'] ?? 'Sin grupo',
                                alumno: $alumno,
                                campo: $campo,
                                materia: $materia,
                            );
                        }
                    }
                    continue;
                }

                foreach (($alumno['materias'] ?? []) as $materia) {
                    $campo = [
                        'campo' => $materia['campo_formativo'] ?? 'Sin campo formativo',
                        'final_preciso' => null,
                    ];

                    $this->filas[] = $this->fila(
                        grupo: $grupo['titulo'] ?? 'Sin grupo',
                        alumno: $alumno,
                        campo: $campo,
                        materia: $materia,
                    );
                }
            }
        }
    }

    private function fila(string $grupo, array $alumno, array $campo, ?array $materia): array
    {
        $evaluaciones = $materia['evaluaciones'] ?? [1 => null, 2 => null, 3 => null];
        $promedioMateria = $this->nivelSlug === 'secundaria'
            ? ($materia['promedio_final_preciso'] ?? null)
            : ($materia['promedio_final_preciso']
                ?? $materia['promedio_provisional_preciso']
                ?? null);
        $promedioCampo = $campo['final_preciso']
            ?? $campo['provisional_preciso']
            ?? null;
        $promedioGeneral = $this->nivelSlug === 'secundaria'
            ? ($alumno['promedio_final'] ?? null)
            : ($alumno['promedio_final']
                ?? $alumno['promedio_provisional']
                ?? null);

        return [
            $grupo,
            $alumno['alumno'] ?? 'Sin nombre',
            $alumno['matricula'] ?? '—',
            $campo['campo'] ?? 'Sin campo formativo',
            $materia['materia'] ?? '—',
            isset($materia['participa']) ? ($materia['participa'] ? 'Sí' : 'No') : 'Sí',
            $this->valor($evaluaciones[1] ?? null),
            $this->valor($evaluaciones[2] ?? null),
            $this->valor($evaluaciones[3] ?? null),
            $this->valor($promedioMateria),
            $this->valor($promedioCampo),
            $this->valor($promedioGeneral),
            $alumno['estatus'] ?? 'Pendiente',
        ];
    }

    private function valor(mixed $valor): string
    {
        /*
         * Excel aplica redondeo cuando recibe el valor preciso y solo se asigna
         * el formato 0.0. Para respetar la regla institucional, exportamos el
         * valor ya truncado a un decimal. El cálculo preciso permanece en el
         * servicio y se usa para promedio general, lugares y promoción.
         */
        return PromedioExcel::formatear($valor, 1, '—');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $ultimaFila = $hoja->getHighestRow();

                $hoja->mergeCells('A1:M1');
                $hoja->mergeCells('A2:M2');
                $hoja->freezePane('A5');
                $hoja->setAutoFilter("A4:M{$ultimaFila}");

                $hoja->getStyle('A1:M1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $hoja->getStyle('A4:M4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $hoja->getStyle("A1:M{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $anchos = [
                    'A' => 25,
                    'B' => 38,
                    'C' => 18,
                    'D' => 34,
                    'E' => 30,
                    'F' => 12,
                    'G' => 14,
                    'H' => 14,
                    'I' => 14,
                    'J' => 22,
                    'K' => 22,
                    'L' => 18,
                    'M' => 26,
                ];

                foreach ($anchos as $columna => $ancho) {
                    $hoja->getColumnDimension($columna)->setWidth($ancho);
                }

                $hoja->getStyle("F5:M{$ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $hoja->getStyle("G5:L{$ultimaFila}")
                    ->getNumberFormat()
                    ->setFormatCode('0.0');
            },
        ];
    }
}
