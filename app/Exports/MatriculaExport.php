<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class MatriculaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Collection $rows,
        protected string $nivelNombre = '—',
        protected string $generacionNombre = 'Todas',
        protected string $gradoNombre = 'Todos',
        protected string $semestreNombre = 'Todos',
        protected string $grupoNombre = 'Todos',
        protected string $search = '',
        protected bool $esBachillerato = false,
        protected string $cicloEscolarNombre = '—',
        protected string $corteNombre = '—',
        protected string $estatusNombre = 'Todos',
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows->values()->map(function ($row, int $index) {
            $trayectoria = $row->getRelation('trayectoriaContexto');
            $grupo = $row->grupo?->asignacionGrupo?->nombre
                ?? $row->grupo?->grupo
                ?? $row->grupo?->nombre
                ?? '—';

            $base = [
                $index + 1,
                $row->matricula_contexto ?? $row->matricula ?? '—',
                $row->folio ?? '—',
                $row->curp ?? '—',
                $row->apellido_paterno ?? '—',
                $row->apellido_materno ?? '—',
                $row->nombre ?? '—',
                $row->genero === 'H' ? 'Hombre' : ($row->genero === 'M' ? 'Mujer' : '—'),
                $row->generacion
                    ? "{$row->generacion->anio_ingreso}-{$row->generacion->anio_egreso}"
                    : '—',
                $row->grado?->nombre ?? '—',
            ];

            if ($this->esBachillerato) {
                $base[] = $row->semestre?->numero ? 'Semestre ' . $row->semestre->numero : '—';
            }

            return array_merge($base, [
                $grupo,
                $trayectoria?->cicloEscolar?->nombre ?? $this->cicloEscolarNombre,
                $trayectoria?->ciclo?->ciclo ?? $this->corteNombre,
                $trayectoria?->etiqueta_estatus ?? $this->estatusNombre,
                optional($row->fecha_inscripcion)->format('d/m/Y') ?: '—',
                optional($trayectoria?->fecha_inscripcion ?? $trayectoria?->fecha_inicio)->format('d/m/Y') ?: '—',
                optional($trayectoria?->fecha_baja)->format('d/m/Y') ?: '—',
                $trayectoria?->motivo_baja ?: '—',
                $trayectoria?->datos_reconstruidos ? 'Sí' : 'No',
                $row->deleted_at ? 'Archivado' : 'Disponible',
            ]);
        });
    }

    public function headings(): array
    {
        $headings = [
            'No.',
            'Matrícula del nivel',
            'Folio',
            'CURP',
            'Apellido paterno',
            'Apellido materno',
            'Nombre(s)',
            'Sexo',
            'Generación',
            'Grado',
        ];

        if ($this->esBachillerato) {
            $headings[] = 'Semestre';
        }

        return array_merge($headings, [
            'Grupo',
            'Ciclo escolar',
            'Corte',
            'Estatus',
            'Fecha de ingreso al plantel',
            'Fecha de inscripción al ciclo',
            'Fecha de baja',
            'Motivo / tipo de baja',
            'Datos reconstruidos',
            'Registro',
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headings = $this->headings();
                $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
                $firstDataRow = 7;
                $lastDataRow = max($firstDataRow, $firstDataRow + $this->rows->count() - 1);

                $sheet->insertNewRowBefore(1, 5);
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells("A3:{$lastColumn}3");
                $sheet->mergeCells("A4:{$lastColumn}4");
                $sheet->mergeCells("A5:{$lastColumn}5");

                $sheet->setCellValue('A1', 'CENTRO UNIVERSITARIO MOCTEZUMA');
                $sheet->setCellValue('A2', 'MATRÍCULA HISTÓRICA DE ALUMNOS');
                $sheet->setCellValue('A3', "Nivel: {$this->nivelNombre} | Ciclo escolar: {$this->cicloEscolarNombre} | Corte: {$this->corteNombre}");
                $sheet->setCellValue('A4', "Generación: {$this->generacionNombre} | Grado: {$this->gradoNombre} | Semestre: {$this->semestreNombre} | Grupo: {$this->grupoNombre}");
                $sheet->setCellValue('A5', 'Estatus: ' . $this->estatusNombre . ($this->search !== '' ? " | Búsqueda: {$this->search}" : ''));

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '88AC2E']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A3:{$lastColumn}5")->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '334155']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("A6:{$lastColumn}6")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']],
                    ],
                ]);

                if ($this->rows->isNotEmpty()) {
                    $sheet->getStyle("A{$firstDataRow}:{$lastColumn}{$lastDataRow}")->applyFromArray([
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                        ],
                    ]);

                    for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                $sheet->freezePane('A7');
                $sheet->setAutoFilter("A6:{$lastColumn}6");
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(23);
                $sheet->getRowDimension(6)->setRowHeight(38);
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.35)->setBottom(0.35)->setLeft(0.25)->setRight(0.25);
                $sheet->getHeaderFooter()->setOddFooter('&LGenerado: ' . now()->format('d/m/Y H:i') . '&RPágina &P de &N');
            },
        ];
    }
}
