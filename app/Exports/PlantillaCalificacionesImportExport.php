<?php

namespace App\Exports;

use App\Support\ReglasMateriaBachillerato;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class PlantillaCalificacionesImportExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private array $columnasTecnicas = [
        '__nivel_id',
        '__grado_id',
        '__grupo_id',
        '__generacion_id',
        '__semestre_id',
        '__ciclo_escolar_id',
        '__periodo_id',
        '__tipo_periodo',
        '__periodo_referencia_id',
    ];

    public function __construct(
        private array $inscripciones,
        private array $materias,
        private array $calificaciones,
        private array $observaciones,
        private array $contexto
    ) {}

    public function title(): string
    {
        return 'Importar calificaciones';
    }

    public function array(): array
    {
        $filas = [];

        $filas[] = ['SISTEMA WEB DE CONTROL ESCOLAR - PLANTILLA DE IMPORTACIÓN'];
        $filas[] = [$this->textoContexto()];

        /*
         * Fila técnica oculta.
         * Cada columna de materia guarda su asignacion_materia_id.
         * El usuario ve el nombre de la materia, pero la importación lee este id.
         */
        $filaIdsMaterias = ['', '', ''];

        foreach ($this->materias as $materia) {
            $filaIdsMaterias[] = (int) ($materia['id'] ?? 0);
        }

        $filaIdsMaterias[] = '';

        foreach ($this->columnasTecnicas as $columnaTecnica) {
            $filaIdsMaterias[] = $columnaTecnica;
        }

        $filas[] = $filaIdsMaterias;

        $filas[] = ['Indicaciones: captura únicamente en las columnas de materias. No modifiques inscripcion_id, matrícula, alumno ni las columnas ocultas.'];
        $indicacionValores = 'Valores permitidos: 0 a 10, AC, ED, RA, NP, SD. Si dejas una calificación vacía, se eliminará la calificación existente para esa materia.';

        if ($this->esBachillerato()) {
            $indicacionValores .= ' Las columnas EXTRA se importan y se muestran en boletas, pero no intervienen en promedio_actual.';
        }

        $filas[] = [$indicacionValores];

        $encabezados = [
            'inscripcion_id',
            'matricula',
            'alumno',
        ];

        foreach ($this->materias as $materia) {
            $encabezados[] = $this->nombreMateria($materia);
        }

        $encabezados[] = 'promedio_actual';

        foreach ($this->columnasTecnicas as $columnaTecnica) {
            $encabezados[] = $columnaTecnica;
        }

        $filas[] = $encabezados;

        foreach ($this->inscripciones as $inscripcion) {
            $inscripcionId = (int) ($inscripcion['inscripcion_id'] ?? 0);

            $fila = [
                $inscripcionId,
                $inscripcion['matricula'] ?? '',
                $inscripcion['alumno'] ?? '',
            ];

            foreach ($this->materias as $materia) {
                $asignacionMateriaId = (int) ($materia['id'] ?? 0);
                $fila[] = $this->calificaciones[$inscripcionId][$asignacionMateriaId] ?? '';
            }

            $fila[] = '';

            foreach ($this->columnasTecnicas as $columnaTecnica) {
                $claveContexto = ltrim($columnaTecnica, '_');
                $fila[] = $this->contexto[$claveContexto] ?? $this->contexto[$columnaTecnica] ?? '';
            }

            $filas[] = $fila;
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $totalColumnasVisibles = 4 + count($this->materias);
                $totalColumnas = $totalColumnasVisibles + count($this->columnasTecnicas);
                $ultimaColumnaVisible = Coordinate::stringFromColumnIndex($totalColumnasVisibles);
                $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);
                $ultimaFila = 6 + count($this->inscripciones);

                $sheet->mergeCells("A1:{$ultimaColumnaVisible}1");
                $sheet->mergeCells("A2:{$ultimaColumnaVisible}2");
                $sheet->mergeCells("A4:{$ultimaColumnaVisible}4");
                $sheet->mergeCells("A5:{$ultimaColumnaVisible}5");

                $sheet->getStyle("A1:{$ultimaColumnaVisible}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A2:{$ultimaColumnaVisible}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E293B']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A4:{$ultimaColumnaVisible}5")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A6:{$ultimaColumna}6")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $sheet->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                $sheet->freezePane('D7');
                $sheet->setAutoFilter("A6:{$ultimaColumnaVisible}6");

                // Se oculta la fila técnica con los ids reales de asignación de materia.
                $sheet->getRowDimension(3)->setVisible(false);

                $sheet->getColumnDimension('A')->setWidth(16);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(42);

                $sheet->getStyle("A7:C{$ultimaFila}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '334155']],
                ]);

                $this->configurarColumnasMaterias($sheet, $ultimaFila);
                $this->configurarPromedios($sheet, $ultimaFila, $totalColumnasVisibles);
                $this->configurarColumnasTecnicas($sheet, $totalColumnasVisibles, $totalColumnas);
                $this->protegerHoja($sheet, $ultimaFila, $totalColumnas, $totalColumnasVisibles);

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(4)->setRowHeight(30);
                $sheet->getRowDimension(5)->setRowHeight(30);
                $sheet->getRowDimension(6)->setRowHeight(42);
            },
        ];
    }

    private function configurarColumnasMaterias($sheet, int $ultimaFila): void
    {
        $columna = 4;

        foreach ($this->materias as $materia) {
            $columnaMateria = Coordinate::stringFromColumnIndex($columna);
            $nombreMateria = $this->nombreMateria($materia);
            $profesor = $this->limpiarTexto($materia['profesor'] ?? 'SIN PROFESOR ASIGNADO');
            $esExtraBachillerato = $this->esBachillerato()
                && ReglasMateriaBachillerato::esExtraInformativa($materia);

            $sheet->setCellValue(
                "{$columnaMateria}6",
                $nombreMateria . ($esExtraBachillerato ? ' · EXTRA · NO PROMEDIA' : '')
            );

            $comentario = $nombreMateria . "\nProfesor: {$profesor}\nCaptura 0 a 10, AC, ED, RA, NP o SD.";

            if ($esExtraBachillerato) {
                $comentario .= "\nMateria extra informativa: se importa y aparece en boletas, pero no interviene en el promedio.";
            }

            $sheet->getComment("{$columnaMateria}6")
                ->getText()
                ->createTextRun($comentario);

            $sheet->getColumnDimension($columnaMateria)->setWidth($esExtraBachillerato ? 25 : 18);

            $sheet->getStyle("{$columnaMateria}7:{$columnaMateria}{$ultimaFila}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $esExtraBachillerato ? 'FEF3C7' : 'ECFDF5'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $esExtraBachillerato ? '92400E' : '111827'],
                ],
            ]);

            for ($fila = 7; $fila <= $ultimaFila; $fila++) {
                $validacion = $sheet->getCell("{$columnaMateria}{$fila}")->getDataValidation();
                $validacion->setType(DataValidation::TYPE_LIST);
                $validacion->setErrorStyle(DataValidation::STYLE_STOP);
                $validacion->setAllowBlank(true);
                $validacion->setShowInputMessage(true);
                $validacion->setShowErrorMessage(true);
                $validacion->setShowDropDown(true);
                $validacion->setFormula1('"0,1,2,3,4,5,6,7,8,9,10,AC,ED,RA,NP,SD"');
                $validacion->setPromptTitle('Calificación permitida');
                $validacion->setPrompt('Usa 0 a 10, AC, ED, RA, NP o SD.');
                $validacion->setErrorTitle('Valor no permitido');
                $validacion->setError('La calificación debe ser de 0 a 10 o una clave válida.');
            }

            $columna++;
        }
    }

    private function configurarPromedios($sheet, int $ultimaFila, int $totalColumnasVisibles): void
    {
        $columnaPromedio = Coordinate::stringFromColumnIndex($totalColumnasVisibles);

        $sheet->setCellValue("{$columnaPromedio}6", 'promedio_actual');
        $sheet->getColumnDimension($columnaPromedio)->setWidth(18);

        for ($fila = 7; $fila <= $ultimaFila; $fila++) {
            $celdasCalificacion = [];
            $columna = 4;

            foreach ($this->materias as $materia) {
                if (!$this->esBachillerato() || ReglasMateriaBachillerato::esPromediable($materia)) {
                    $celdasCalificacion[] = Coordinate::stringFromColumnIndex($columna) . $fila;
                }

                $columna++;
            }

            if (empty($celdasCalificacion)) {
                continue;
            }

            if ($this->esBachillerato()) {
                $divisor = max(0, (int) ($this->contexto['numero_materias_promediar'] ?? count($celdasCalificacion)));

                if ($divisor > 0) {
                    $sheet->setCellValue(
                        "{$columnaPromedio}{$fila}",
                        '=IFERROR(ROUNDDOWN(SUM(' . implode(',', $celdasCalificacion) . ')/' . $divisor . ',1),"")'
                    );
                }

                continue;
            }

            $sheet->setCellValue(
                "{$columnaPromedio}{$fila}",
                '=IFERROR(ROUNDDOWN(AVERAGE(' . implode(',', $celdasCalificacion) . '),1),"")'
            );
        }

        $sheet->getStyle("{$columnaPromedio}7:{$columnaPromedio}{$ultimaFila}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'font' => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function configurarColumnasTecnicas($sheet, int $totalColumnasVisibles, int $totalColumnas): void
    {
        for ($columna = $totalColumnasVisibles + 1; $columna <= $totalColumnas; $columna++) {
            $letra = Coordinate::stringFromColumnIndex($columna);
            $sheet->getColumnDimension($letra)->setVisible(false);
        }
    }

    private function protegerHoja($sheet, int $ultimaFila, int $totalColumnas, int $totalColumnasVisibles): void
    {
        $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setSort(true);
        $sheet->getProtection()->setAutoFilter(true);

        $sheet->getStyle("A1:{$ultimaColumna}{$ultimaFila}")
            ->getProtection()
            ->setLocked(Protection::PROTECTION_PROTECTED);

        $columna = 4;

        foreach ($this->materias as $materia) {
            $columnaMateria = Coordinate::stringFromColumnIndex($columna);

            $sheet->getStyle("{$columnaMateria}7:{$columnaMateria}{$ultimaFila}")
                ->getProtection()
                ->setLocked(Protection::PROTECTION_UNPROTECTED);

            $columna++;
        }
    }

    private function esBachillerato(): bool
    {
        return ($this->contexto['tipo_periodo'] ?? null) === 'bachillerato'
            || ReglasMateriaBachillerato::esBachillerato($this->contexto['nivel_id'] ?? null);
    }

    private function textoContexto(): string
    {
        $etiqueta = ($this->contexto['tipo_periodo'] ?? '') === 'bachillerato' ? 'Parcial' : 'Periodo';

        return trim(implode(' | ', array_filter([
            'Nivel: ' . ($this->contexto['nivel'] ?? 'Sin nivel'),
            'Grado: ' . ($this->contexto['grado'] ?? 'Sin grado'),
            'Grupo: ' . ($this->contexto['grupo'] ?? 'Sin grupo'),
            'Generación: ' . ($this->contexto['generacion'] ?? 'Sin generación'),
            'Semestre: ' . ($this->contexto['semestre'] ?? null),
            $etiqueta . ': ' . ($this->contexto['periodo'] ?? 'Sin periodo'),
            'ID periodo: ' . ($this->contexto['periodo_id'] ?? 'Sin ID'),
        ])));
    }

    private function nombreMateria(array $materia): string
    {
        $nombre = $this->limpiarTexto($materia['materia'] ?? '');

        if ($nombre === '') {
            return 'Materia ' . ((int) ($materia['id'] ?? 0));
        }

        return mb_strtoupper($nombre, 'UTF-8');
    }

    private function limpiarTexto(?string $texto): string
    {
        return trim((string) $texto);
    }
}
