<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class HorarioCargaDocenteExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $reporte,
        private readonly array $configuracion,
        private readonly array $datosLaborales,
    ) {
    }

    public function sheets(): array
    {
        return [
            $this->hojaAsig(),
            $this->hojaGeneral(),
            $this->hojaFormatos(),
            $this->hojaComplementarias(),
        ];
    }

    private function hojaAsig(): HorarioCargaDocenteSheet
    {
        $rows = $this->encabezado('ASIGNACIÓN Y CARGA DOCENTE');
        $headerRows = [];
        $sectionRows = [];
        $recessRows = [];

        $headerRows[] = count($rows) + 1;
        $rows[] = [
            'DOCENTE',
            'ASIGNATURA / TALLER',
            'CLAVE',
            'GRUPOS',
            'SESIONES SEMANALES',
            'HORAS RELOJ',
            'NOMBRAMIENTO',
            'CLAVE PRESUPUESTAL',
        ];

        foreach (collect($this->reporte['carga_docente'] ?? []) as $fila) {
            $laboral = $this->datoLaboral($fila['profesor_id'] ?? null);
            $rows[] = [
                $fila['docente'] ?? 'Sin docente',
                $fila['materia'] ?? '—',
                $fila['clave'] ?: 'S/C',
                implode(', ', $fila['grupos'] ?? []),
                (int) ($fila['sesiones_semanales'] ?? 0),
                number_format((float) ($fila['horas_reloj'] ?? 0), 2),
                $laboral['nombramiento'],
                $laboral['clave_presupuestal'],
            ];
        }

        $rows[] = [];
        $sectionRows[] = count($rows) + 1;
        $rows[] = ['RESUMEN'];
        $resumen = $this->reporte['resumen'] ?? [];
        $rows[] = ['Docentes', (int) ($resumen['docentes'] ?? 0)];
        $rows[] = ['Sesiones semanales', (int) ($resumen['sesiones'] ?? 0)];
        $rows[] = ['Horas reloj', number_format((float) ($resumen['horas_reloj'] ?? 0), 2)];
        $rows[] = ['Alertas detectadas', (int) ($resumen['alertas'] ?? 0)];

        return new HorarioCargaDocenteSheet(
            title: 'ASIG',
            rows: $rows,
            headerRows: $headerRows,
            sectionRows: $sectionRows,
            recessRows: $recessRows,
            widths: ['A' => 34, 'B' => 34, 'C' => 16, 'D' => 30, 'E' => 18, 'F' => 16, 'G' => 24, 'H' => 24],
        );
    }

    private function hojaGeneral(): HorarioCargaDocenteSheet
    {
        $rows = $this->encabezado('HORARIO GENERAL');
        $headerRows = [];
        $sectionRows = [];
        $recessRows = [];
        $dias = collect($this->reporte['tabla_general']['dias'] ?? []);

        $headerRows[] = count($rows) + 1;
        $rows[] = array_merge(['HORA'], $dias->map(fn ($dia) => mb_strtoupper((string) $dia->dia))->all());

        foreach (collect($this->reporte['tabla_general']['filas'] ?? []) as $fila) {
            $rowNumber = count($rows) + 1;
            $row = [$fila['hora'] ?? '—'];

            if (!empty($fila['es_receso'])) {
                foreach ($dias as $dia) {
                    $row[] = 'RECESO';
                }
                $recessRows[] = $rowNumber;
                $rows[] = $row;
                continue;
            }

            foreach ($dias as $dia) {
                $actividades = collect($fila['celdas'][(int) $dia->id] ?? []);
                $row[] = $actividades->isEmpty()
                    ? ''
                    : $actividades->map(function (array $item): string {
                        $texto = ($item['grupo'] ?? '') . ' · ' . ($item['nombre'] ?? '');
                        if (!empty($item['profesor'])) {
                            $texto .= "\n" . $item['profesor'];
                        }
                        return trim($texto);
                    })->implode("\n\n");
            }

            $rows[] = $row;
        }

        $widths = ['A' => 17];
        for ($i = 2; $i <= max(2, $dias->count() + 1); $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 31;
        }

        return new HorarioCargaDocenteSheet(
            title: 'FOR-HORGEN',
            rows: $rows,
            headerRows: $headerRows,
            sectionRows: $sectionRows,
            recessRows: $recessRows,
            widths: $widths,
        );
    }

    private function hojaFormatos(): HorarioCargaDocenteSheet
    {
        $rows = $this->encabezado('FORMATOS DE HORARIO POR DOCENTE Y GRUPO');
        $headerRows = [];
        $sectionRows = [];
        $recessRows = [];
        $dias = collect($this->reporte['dias'] ?? []);

        foreach (collect($this->reporte['formatos_docentes'] ?? []) as $formato) {
            $rows[] = [];
            $sectionRows[] = count($rows) + 1;
            $rows[] = ['DOCENTE: ' . ($formato['nombre'] ?? '—')];
            $laboral = $this->datoLaboral($formato['id'] ?? null);
            $rows[] = [
                'Nombramiento:', $laboral['nombramiento'],
                'Clave presupuestal:', $laboral['clave_presupuestal'],
            ];

            $headerRows[] = count($rows) + 1;
            $rows[] = array_merge(['HORA'], $dias->map(fn ($dia) => mb_strtoupper((string) $dia->dia))->all());
            $this->agregarFilasMatriz($rows, $recessRows, $formato['filas'] ?? [], $dias);
        }

        foreach (collect($this->reporte['formatos_grupos'] ?? []) as $formato) {
            $rows[] = [];
            $sectionRows[] = count($rows) + 1;
            $rows[] = ['GRUPO: ' . ($formato['nombre'] ?? '—')];

            $headerRows[] = count($rows) + 1;
            $rows[] = array_merge(['HORA'], $dias->map(fn ($dia) => mb_strtoupper((string) $dia->dia))->all());
            $this->agregarFilasMatriz($rows, $recessRows, $formato['filas'] ?? [], $dias);
        }

        $widths = ['A' => 17];
        for ($i = 2; $i <= max(2, $dias->count() + 1); $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 31;
        }

        return new HorarioCargaDocenteSheet(
            title: 'FORM-HOR',
            rows: $rows,
            headerRows: $headerRows,
            sectionRows: $sectionRows,
            recessRows: $recessRows,
            widths: $widths,
        );
    }

    private function hojaComplementarias(): HorarioCargaDocenteSheet
    {
        $rows = $this->encabezado('MATERIAS COMPLEMENTARIAS Y TALLERES');
        $headerRows = [];
        $sectionRows = [];
        $recessRows = [];

        $headerRows[] = count($rows) + 1;
        $rows[] = [
            'TIPO',
            'ACTIVIDAD',
            'CLAVE',
            'DOCENTE',
            'GRUPOS',
            'SESIONES SEMANALES',
            'HORAS RELOJ',
            'NOMBRAMIENTO / CLAVE',
        ];

        foreach (collect($this->reporte['complementarias'] ?? []) as $fila) {
            $laboral = $this->datoLaboral($fila['profesor_id'] ?? null);
            $rows[] = [
                $fila['tipo'] ?? '—',
                $fila['nombre'] ?? '—',
                $fila['clave'] ?: 'S/C',
                $fila['docente'] ?? 'Sin docente',
                implode(', ', $fila['grupos'] ?? []),
                (int) ($fila['sesiones_semanales'] ?? 0),
                number_format((float) ($fila['horas_reloj'] ?? 0), 2),
                $laboral['nombramiento'] . ' / ' . $laboral['clave_presupuestal'],
            ];
        }

        return new HorarioCargaDocenteSheet(
            title: 'HOR-COMP.',
            rows: $rows,
            headerRows: $headerRows,
            sectionRows: $sectionRows,
            recessRows: $recessRows,
            widths: ['A' => 22, 'B' => 34, 'C' => 16, 'D' => 34, 'E' => 32, 'F' => 18, 'G' => 16, 'H' => 34],
        );
    }

    private function agregarFilasMatriz(array &$rows, array &$recessRows, iterable $filas, Collection $dias): void
    {
        foreach ($filas as $fila) {
            $rowNumber = count($rows) + 1;
            $row = [$fila['hora'] ?? '—'];

            if (!empty($fila['es_receso'])) {
                foreach ($dias as $dia) {
                    $row[] = 'RECESO';
                }
                $recessRows[] = $rowNumber;
                $rows[] = $row;
                continue;
            }

            foreach ($dias as $dia) {
                $items = collect($fila['celdas'][(int) $dia->id] ?? []);
                $row[] = $items->map(function (array $item): string {
                    $texto = (string) ($item['nombre'] ?? '');
                    if (!empty($item['clave'])) {
                        $texto .= ' [' . $item['clave'] . ']';
                    }
                    if (!empty($item['contexto'])) {
                        $texto .= "\n" . $item['contexto'];
                    }
                    return $texto;
                })->implode("\n\n");
            }

            $rows[] = $row;
        }
    }

    private function encabezado(string $titulo): array
    {
        $ciclo = $this->reporte['ciclo_escolar'] ?? null;
        $cicloTexto = $ciclo?->nombre ?? 'Sin ciclo';

        return [
            [$this->configuracion['escuela'] ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.'],
            [$titulo],
            [sprintf(
                'Ciclo: %s | CCT: %s | Zona: %s | Turno: %s',
                $cicloTexto,
                $this->configuracion['cct'] ?? 'S/C',
                $this->configuracion['zona_escolar'] ?? 'S/C',
                $this->configuracion['turno'] ?? 'S/C',
            )],
            [],
        ];
    }

    private function datoLaboral(int|string|null $profesorId): array
    {
        if (!$profesorId) {
            return [
                'nombramiento' => 'S/C',
                'clave_presupuestal' => 'S/C',
            ];
        }

        $dato = $this->datosLaborales[(int) $profesorId] ?? [];

        return [
            'nombramiento' => filled($dato['nombramiento'] ?? null)
                ? trim((string) $dato['nombramiento'])
                : 'S/C',
            'clave_presupuestal' => filled($dato['clave_presupuestal'] ?? null)
                ? trim((string) $dato['clave_presupuestal'])
                : 'S/C',
        ];
    }
}

class HorarioCargaDocenteSheet implements FromArray, ShouldAutoSize, WithColumnWidths, WithEvents, WithTitle
{
    public function __construct(
        private readonly string $title,
        private readonly array $rows,
        private readonly array $headerRows = [],
        private readonly array $sectionRows = [],
        private readonly array $recessRows = [],
        private readonly array $widths = [],
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function columnWidths(): array
    {
        return $this->widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $maxColumns = max(2, $this->maxColumnCount());
                $lastColumn = Coordinate::stringFromColumnIndex($maxColumns);
                $lastRow = max(1, count($this->rows));

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells("A3:{$lastColumn}3");

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 17, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '88AC2E']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                foreach ($this->headerRows as $row) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                }

                foreach ($this->sectionRows as $row) {
                    $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    ]);
                }

                foreach ($this->recessRows as $row) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '365314']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFCCB']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }

                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_HAIR)
                    ->getColor()->setRGB('E2E8F0');

                $sheet->freezePane('A5');
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
                $sheet->getPageMargins()->setTop(0.35)->setBottom(0.35)->setLeft(0.3)->setRight(0.3);
            },
        ];
    }

    private function maxColumnCount(): int
    {
        return collect($this->rows)
            ->map(fn (array $row) => count($row))
            ->max() ?: 1;
    }
}
