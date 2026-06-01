<?php

namespace App\Exports;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CalificacionExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle, WithCharts
{
    protected ?int $nivel_id;
    protected ?int $grado_id;
    protected ?int $grupo_id;
    protected ?int $periodo_id;
    protected ?int $semestre_id;
    protected ?int $generacion_id;
    protected bool $esBachillerato;
    protected string $busqueda;

    protected string $nivelNombre = '—';
    protected string $gradoNombre = '—';
    protected string $grupoNombre = '—';
    protected string $semestreNombre = '—';
    protected string $periodoNombre = '—';
    protected string $cicloEscolarNombre = '—';
    protected string $generacionNombre = '—';

    protected array $materias = [];
    protected array $inscripciones = [];
    protected array $calificaciones = [];
    protected array $promedios = [];
    protected array $promediosPorMateria = [];
    protected array $periodosPorMateria = [];
    protected array $filas = [];

    protected int $filaResumenTitulo = 4;
    protected int $filaResumenValores = 5;

    protected int $filaEncabezadoTabla = 0;
    protected int $ultimaFilaTabla = 0;
    protected int $ultimaColumnaTabla = 0;

    protected int $filaPromediosMateriaTitulo = 0;
    protected int $filaPromediosMateriaHeader = 0;
    protected int $ultimaFilaPromediosMateria = 0;

    protected int $filaPromediosAlumnoTitulo = 0;
    protected int $filaPromediosAlumnoHeader = 0;
    protected int $ultimaFilaPromediosAlumno = 0;

    protected int $filaPeriodosMateriaTitulo = 0;
    protected int $filaPeriodosMateriaHeader = 0;
    protected int $ultimaFilaPeriodosMateria = 0;

    protected bool $preparado = false;

    public function __construct(
        ?int $nivel_id,
        ?int $grado_id,
        ?int $grupo_id,
        ?int $periodo_id,
        ?int $semestre_id = null,
        ?int $generacion_id = null,
        bool $esBachillerato = false,
        string $busqueda = ''
    ) {
        $this->nivel_id = $nivel_id;
        $this->grado_id = $grado_id;
        $this->grupo_id = $grupo_id;
        $this->periodo_id = $periodo_id;
        $this->semestre_id = $semestre_id;
        $this->generacion_id = $generacion_id;
        $this->esBachillerato = $esBachillerato;
        $this->busqueda = trim($busqueda);

        $this->resolverNombresFiltros();
    }

    public function title(): string
    {
        return 'Calificaciones';
    }

    public function array(): array
    {
        $this->prepararDatos();

        return $this->filas;
    }

    public function charts()
    {
        $this->prepararDatos();

        $charts = [];

        if ($this->filaPromediosMateriaHeader > 0 && $this->ultimaFilaPromediosMateria > $this->filaPromediosMateriaHeader) {
            $charts[] = $this->crearGraficaPromedioMateria();
        }

        if ($this->filaPromediosAlumnoHeader > 0 && $this->ultimaFilaPromediosAlumno > $this->filaPromediosAlumnoHeader) {
            $charts[] = $this->crearGraficaPromedioAlumno();
        }

        return $charts;
    }

    protected function prepararDatos(): void
    {
        if ($this->preparado) {
            return;
        }

        $this->materias = $this->obtenerMaterias();
        $this->inscripciones = $this->obtenerInscripciones();
        $this->calificaciones = $this->obtenerCalificaciones($this->inscripciones, $this->materias);
        $this->promedios = $this->calcularPromediosAlumnos($this->inscripciones, $this->materias, $this->calificaciones);
        $this->promediosPorMateria = $this->calcularPromediosPorMateria($this->inscripciones, $this->materias, $this->calificaciones);
        $this->periodosPorMateria = $this->obtenerPeriodosPorMateria($this->materias);
        $this->filas = $this->construirFilas();
        $this->preparado = true;
    }

    protected function construirFilas(): array
    {
        $filas = [];

        $promediosNumericos = collect($this->promedios)
            ->filter(fn($valor) => is_numeric($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGlobal = $promediosNumericos->isNotEmpty()
            ? $this->truncarPromedio((float) $promediosNumericos->avg())
            : 0;

        $totalAlumnos = count($this->inscripciones);

        $totalAprobados = $promediosNumericos
            ->filter(fn($valor) => $valor >= 6)
            ->count();

        $totalReprobados = $promediosNumericos
            ->filter(fn($valor) => $valor < 6)
            ->count();

        $porcentajeAprobacion = $totalAlumnos > 0
            ? round(($totalAprobados / $totalAlumnos) * 100)
            : 0;

        $totalCapturadas = collect($this->calificaciones)
            ->filter(fn($valor) => $valor !== null && $valor !== '')
            ->count();

        $totalCeldas = count($this->inscripciones) * count($this->materias);

        $porcentajeCaptura = $totalCeldas > 0
            ? round(($totalCapturadas / $totalCeldas) * 100)
            : 0;

        $filas[] = ['REPORTE GENERAL DE CALIFICACIONES'];
        $filas[] = ['Generado el ' . now()->format('d/m/Y h:i A')];
        $filas[] = [''];

        $filas[] = [
            'Promedio global',
            'Aprobación',
            'Alumnos',
            'Aprobados',
            'Reprobados',
            'Captura',
        ];

        $filas[] = [
            number_format($promedioGlobal, 1, '.', ''),
            $porcentajeAprobacion . '%',
            $totalAlumnos,
            $totalAprobados,
            $totalReprobados,
            $porcentajeCaptura . '%',
        ];

        $filas[] = [''];
        $filas[] = ['FILTROS APLICADOS'];
        $filas[] = ['Nivel', $this->nivelNombre];
        $filas[] = ['Generación', $this->generacionNombre];
        $filas[] = ['Grado', $this->gradoNombre];

        if ($this->esBachillerato) {
            $filas[] = ['Semestre', $this->semestreNombre];
        }

        $filas[] = ['Grupo', $this->grupoNombre];
        $filas[] = ['Periodo', $this->periodoNombre];
        $filas[] = ['Ciclo escolar', $this->cicloEscolarNombre];
        $filas[] = ['Búsqueda aplicada', $this->busqueda !== '' ? $this->busqueda : 'Sin filtro'];
        $filas[] = [''];

        /*
         * Se valida si existen promedios reales mayores a 0.
         * Si no existen, la columna LUGAR mostrará Pendiente.
         */
        $hayPromediosParaLugar = collect($this->promedios)
            ->filter(fn($valor) => is_numeric($valor) && (float) $valor > 0)
            ->isNotEmpty();

        $inscripcionesOrdenadas = collect($this->inscripciones)
            ->sortByDesc(function ($inscripcion) {
                $promedio = $this->promedios[$inscripcion['inscripcion_id']] ?? null;

                return is_numeric($promedio) ? (float) $promedio : -1;
            })
            ->values();

        $promediosUnicos = $hayPromediosParaLugar
            ? $inscripcionesOrdenadas
                ->map(function ($inscripcion) {
                    $promedio = $this->promedios[$inscripcion['inscripcion_id']] ?? null;

                    if (!is_numeric($promedio) || (float) $promedio <= 0) {
                        return null;
                    }

                    return number_format($this->truncarPromedio((float) $promedio), 1, '.', '');
                })
                ->filter()
                ->unique()
                ->values()
                ->take(3)
            : collect();

        $lugaresPorPromedio = [];

        foreach ($promediosUnicos as $index => $promedioUnico) {
            $lugaresPorPromedio[$promedioUnico] = $index + 1;
        }

        $encabezados = [
            'LUGAR',
            'MATRÍCULA',
            'ALUMNO',
            'GRADO',
        ];

        if ($this->esBachillerato) {
            $encabezados[] = 'SEMESTRE';
        }

        $encabezados[] = 'GRUPO';

        foreach ($this->materias as $materia) {
            $encabezados[] = mb_strtoupper($materia['materia']);
        }

        $encabezados[] = 'PROMEDIO';
        $encabezados[] = 'ESTADO';

        $filas[] = $encabezados;

        $this->filaEncabezadoTabla = count($filas);
        $this->ultimaColumnaTabla = count($encabezados);

        foreach ($inscripcionesOrdenadas as $inscripcion) {
            $inscripcionId = (int) $inscripcion['inscripcion_id'];
            $promedio = $this->promedios[$inscripcionId] ?? 'Pendiente';

            $promedioClave = null;

            if ($hayPromediosParaLugar && is_numeric($promedio) && (float) $promedio > 0) {
                $promedioClave = number_format($this->truncarPromedio((float) $promedio), 1, '.', '');
            }

            $lugar = $promedioClave && isset($lugaresPorPromedio[$promedioClave])
                ? $lugaresPorPromedio[$promedioClave] . '°'
                : 'Pendiente';

            $fila = [
                $lugar,
                $inscripcion['matricula'],
                $inscripcion['alumno'],
                $inscripcion['grado'],
            ];

            if ($this->esBachillerato) {
                $fila[] = $inscripcion['semestre'];
            }

            $fila[] = $inscripcion['grupo'];

            foreach ($this->materias as $materia) {
                $clave = $inscripcionId . '-' . $materia['id'];
                $fila[] = $this->calificaciones[$clave] ?? '';
            }

            $fila[] = $promedio;
            $fila[] = $this->estadoPromedio($promedio);

            $filas[] = $fila;
        }

        $this->ultimaFilaTabla = count($filas);
        $filas[] = [''];

        $this->filaPromediosMateriaTitulo = count($filas) + 1;
        $filas[] = ['PROMEDIO POR MATERIA'];
        $this->filaPromediosMateriaHeader = count($filas) + 1;
        $filas[] = ['Materia', 'Promedio', 'Capturadas', 'Estado'];

        foreach ($this->promediosPorMateria as $item) {
            $filas[] = [
                $item['materia'],
                $item['promedio_numero'],
                $item['total_capturadas'],
                $item['estado'],
            ];
        }

        $this->ultimaFilaPromediosMateria = count($filas);
        $filas[] = [''];

        $this->filaPromediosAlumnoTitulo = count($filas) + 1;
        $filas[] = ['PROMEDIO POR ALUMNO'];
        $this->filaPromediosAlumnoHeader = count($filas) + 1;
        $filas[] = ['Alumno', 'Promedio', 'Estado'];

        foreach ($inscripcionesOrdenadas as $inscripcion) {
            $inscripcionId = (int) $inscripcion['inscripcion_id'];
            $promedio = $this->promedios[$inscripcionId] ?? 'Pendiente';

            if (!is_numeric($promedio)) {
                continue;
            }

            $filas[] = [
                $inscripcion['alumno'],
                (float) $promedio,
                $this->estadoPromedio($promedio),
            ];
        }

        $this->ultimaFilaPromediosAlumno = count($filas);
        $filas[] = [''];

        $this->filaPeriodosMateriaTitulo = count($filas) + 1;
        $filas[] = ['PERIODOS POR MATERIA'];
        $this->filaPeriodosMateriaHeader = count($filas) + 1;
        $filas[] = ['Materia', 'Tipo', 'Periodo', 'Fecha inicio', 'Fecha fin'];

        foreach ($this->periodosPorMateria as $item) {
            $filas[] = [
                $item['materia'],
                $item['tipo'],
                $item['periodo'],
                $item['fecha_inicio'],
                $item['fecha_fin'],
            ];
        }

        $this->ultimaFilaPeriodosMateria = count($filas);

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->prepararDatos();
                $sheet = $event->sheet->getDelegate();

                $ultimaColumnaLetra = Coordinate::stringFromColumnIndex(max(1, $this->ultimaColumnaTabla));
                $ultimaFila = max($this->ultimaFilaPeriodosMateria, $this->ultimaFilaTabla, 1);

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Aptos')->setSize(10);

                $sheet->mergeCells("A1:{$ultimaColumnaLetra}1");
                $sheet->mergeCells("A2:{$ultimaColumnaLetra}2");

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => '0F172A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'],
                    ],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'color' => ['rgb' => '64748B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EFF6FF'],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->getStyle('A4:F4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '334155'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ]);

                foreach ([
                    'A5' => 'DBEAFE',
                    'B5' => 'DCFCE7',
                    'C5' => 'FEF3C7',
                    'D5' => 'EDE9FE',
                    'E5' => 'FFE4E6',
                    'F5' => 'E0F2FE',
                ] as $celda => $color) {
                    $sheet->getStyle($celda)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 14,
                            'color' => ['rgb' => '0F172A'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $color],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E2E8F0'],
                            ],
                        ],
                    ]);
                }

                $sheet->getRowDimension(4)->setRowHeight(22);
                $sheet->getRowDimension(5)->setRowHeight(28);

                $this->estilizarSeccion($sheet, 7, $ultimaColumnaLetra);
                $this->estilizarFiltros($sheet);
                $this->estilizarTablaPrincipal($sheet);
                $this->estilizarTablaPromediosMateria($sheet);
                $this->estilizarTablaPromediosAlumno($sheet);
                $this->estilizarTablaPeriodosMateria($sheet);

                if ($this->filaEncabezadoTabla > 0 && $this->ultimaFilaTabla >= $this->filaEncabezadoTabla) {
                    $sheet->freezePane('A' . ($this->filaEncabezadoTabla + 1));
                    $sheet->setAutoFilter("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->ultimaFilaTabla}");
                }

                $sheet->getColumnDimension('A')->setWidth(7);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(38);
                $sheet->getColumnDimension('D')->setWidth(15);

                if ($this->esBachillerato) {
                    $sheet->getColumnDimension('E')->setWidth(14);
                    $sheet->getColumnDimension('F')->setWidth(14);
                } else {
                    $sheet->getColumnDimension('E')->setWidth(14);
                }

                $columnaInicioMaterias = $this->esBachillerato ? 7 : 6;
                $columnaFinMaterias = $this->ultimaColumnaTabla - 2;

                for ($col = $columnaInicioMaterias; $col <= $columnaFinMaterias; $col++) {
                    $letra = Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($letra)->setWidth(16);
                }

                $letraPromedio = Coordinate::stringFromColumnIndex(max(1, $this->ultimaColumnaTabla - 1));
                $letraEstado = Coordinate::stringFromColumnIndex(max(1, $this->ultimaColumnaTabla));

                $sheet->getColumnDimension($letraPromedio)->setWidth(13);
                $sheet->getColumnDimension($letraEstado)->setWidth(16);

                $sheet->getStyle("A1:{$ultimaColumnaLetra}{$ultimaFila}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.35);
                $sheet->getPageMargins()->setRight(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setBottom(0.35);
            },
        ];
    }

    protected function estilizarSeccion($sheet, int $fila, string $ultimaColumnaLetra): void
    {
        $sheet->mergeCells("A{$fila}:{$ultimaColumnaLetra}{$fila}");
        $sheet->getStyle("A{$fila}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '1E3A8A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0F2FE'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);
    }

    protected function estilizarFiltros($sheet): void
    {
        $inicio = 8;
        $fin = $this->esBachillerato ? 15 : 14;

        for ($fila = $inicio; $fila <= $fin; $fila++) {
            $sheet->getStyle("A{$fila}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '0F172A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ]);

            $sheet->getStyle("A{$fila}:B{$fila}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);
        }
    }

    protected function estilizarTablaPrincipal($sheet): void
    {
        if ($this->filaEncabezadoTabla <= 0 || $this->ultimaColumnaTabla <= 0) {
            return;
        }

        $ultimaColumnaLetra = Coordinate::stringFromColumnIndex($this->ultimaColumnaTabla);

        $sheet->getStyle("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->filaEncabezadoTabla}")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1E3A8A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BFDBFE'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ]);

        $sheet->getRowDimension($this->filaEncabezadoTabla)->setRowHeight(34);

        $sheet->getStyle("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->ultimaFilaTabla}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);

        $columnaInicioMaterias = $this->esBachillerato ? 7 : 6;
        $columnaPromedio = $this->ultimaColumnaTabla - 1;
        $columnaEstado = $this->ultimaColumnaTabla;
        $inicioMateriasLetra = Coordinate::stringFromColumnIndex($columnaInicioMaterias);
        $estadoLetra = Coordinate::stringFromColumnIndex($columnaEstado);

        $sheet->getStyle('A' . ($this->filaEncabezadoTabla + 1) . ":A{$this->ultimaFilaTabla}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("{$inicioMateriasLetra}" . ($this->filaEncabezadoTabla + 1) . ":{$estadoLetra}{$this->ultimaFilaTabla}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($fila = $this->filaEncabezadoTabla + 1; $fila <= $this->ultimaFilaTabla; $fila++) {
            if ($fila % 2 === 0) {
                $sheet->getStyle("A{$fila}:{$ultimaColumnaLetra}{$fila}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F8FAFC');
            }

            /*
             * No se pintan las calificaciones de las materias.
             * Solo se conserva color en PROMEDIO y ESTADO.
             */
            $promedioLetra = Coordinate::stringFromColumnIndex($columnaPromedio);
            $promedio = $sheet->getCell("{$promedioLetra}{$fila}")->getValue();

            $this->pintarValorCalificacion($sheet, "{$promedioLetra}{$fila}", $promedio);

            $estado = $sheet->getCell("{$estadoLetra}{$fila}")->getValue();
            $this->pintarEstado($sheet, "{$estadoLetra}{$fila}", $estado);
        }
    }

    protected function estilizarTablaPromediosMateria($sheet): void
    {
        if ($this->filaPromediosMateriaTitulo <= 0) {
            return;
        }

        $this->estilizarTituloTabla($sheet, $this->filaPromediosMateriaTitulo, 'A', 'D', 'DCFCE7', '166534');
        $this->estilizarEncabezadoSimple($sheet, $this->filaPromediosMateriaHeader, 'A', 'D', 'BBF7D0', '14532D');

        $sheet->getStyle("A{$this->filaPromediosMateriaHeader}:D{$this->ultimaFilaPromediosMateria}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);

        for ($fila = $this->filaPromediosMateriaHeader + 1; $fila <= $this->ultimaFilaPromediosMateria; $fila++) {
            $this->pintarValorCalificacion($sheet, "B{$fila}", $sheet->getCell("B{$fila}")->getValue());
            $this->pintarEstado($sheet, "D{$fila}", $sheet->getCell("D{$fila}")->getValue());
        }

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(16);
    }

    protected function estilizarTablaPromediosAlumno($sheet): void
    {
        if ($this->filaPromediosAlumnoTitulo <= 0) {
            return;
        }

        $this->estilizarTituloTabla($sheet, $this->filaPromediosAlumnoTitulo, 'F', 'H', 'EDE9FE', '5B21B6');
        $this->estilizarEncabezadoSimple($sheet, $this->filaPromediosAlumnoHeader, 'F', 'H', 'DDD6FE', '4C1D95');

        $sheet->getStyle("F{$this->filaPromediosAlumnoHeader}:H{$this->ultimaFilaPromediosAlumno}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);

        for ($fila = $this->filaPromediosAlumnoHeader + 1; $fila <= $this->ultimaFilaPromediosAlumno; $fila++) {
            $this->pintarValorCalificacion($sheet, "G{$fila}", $sheet->getCell("G{$fila}")->getValue());
            $this->pintarEstado($sheet, "H{$fila}", $sheet->getCell("H{$fila}")->getValue());
        }

        $sheet->getColumnDimension('F')->setWidth(36);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(16);
    }

    protected function estilizarTablaPeriodosMateria($sheet): void
    {
        if ($this->filaPeriodosMateriaTitulo <= 0) {
            return;
        }

        $this->estilizarTituloTabla($sheet, $this->filaPeriodosMateriaTitulo, 'J', 'N', 'FEF3C7', '92400E');
        $this->estilizarEncabezadoSimple($sheet, $this->filaPeriodosMateriaHeader, 'J', 'N', 'FDE68A', '78350F');

        $sheet->getStyle("J{$this->filaPeriodosMateriaHeader}:N{$this->ultimaFilaPeriodosMateria}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);

        $sheet->getColumnDimension('J')->setWidth(34);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(22);
        $sheet->getColumnDimension('M')->setWidth(14);
        $sheet->getColumnDimension('N')->setWidth(14);
    }

    protected function estilizarTituloTabla($sheet, int $fila, string $columnaInicio, string $columnaFin, string $fill, string $font): void
    {
        $sheet->mergeCells("{$columnaInicio}{$fila}:{$columnaFin}{$fila}");
        $sheet->getStyle("{$columnaInicio}{$fila}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => $font],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fill],
            ],
        ]);
    }

    protected function estilizarEncabezadoSimple($sheet, int $fila, string $columnaInicio, string $columnaFin, string $fill, string $font): void
    {
        $sheet->getStyle("{$columnaInicio}{$fila}:{$columnaFin}{$fila}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $font],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fill],
            ],
        ]);
    }

    protected function pintarValorCalificacion($sheet, string $celda, mixed $valor): void
    {
        $valor = trim((string) $valor);

        if ($valor === '' || $valor === '—') {
            return;
        }

        if (!is_numeric($valor)) {
            $sheet->getStyle($celda)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '6D28D9'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EDE9FE'],
                ],
            ]);
            return;
        }

        $numero = (float) $valor;

        if ($numero < 6) {
            $fill = 'FFE4E6';
            $font = 'BE123C';
        } elseif ($numero < 8) {
            $fill = 'FEF3C7';
            $font = '92400E';
        } else {
            $fill = 'DCFCE7';
            $font = '166534';
        }

        $sheet->getStyle($celda)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $font],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fill],
            ],
        ]);
    }

    protected function pintarEstado($sheet, string $celda, mixed $estado): void
    {
        $estado = trim((string) $estado);

        $fill = match ($estado) {
            'Aprobado', 'Bueno' => 'DCFCE7',
            'Regular' => 'FEF3C7',
            'Reprobado', 'En riesgo' => 'FFE4E6',
            default => 'F1F5F9',
        };

        $font = match ($estado) {
            'Aprobado', 'Bueno' => '166534',
            'Regular' => '92400E',
            'Reprobado', 'En riesgo' => 'BE123C',
            default => '334155',
        };

        $sheet->getStyle($celda)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $font],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $fill],
            ],
        ]);
    }

    protected function crearGraficaPromedioMateria(): Chart
    {
        $sheet = "'Calificaciones'";
        $inicio = $this->filaPromediosMateriaHeader + 1;
        $fin = $this->ultimaFilaPromediosMateria;

        $labels = [
            new DataSeriesValues('String', "{$sheet}!\$B\${$this->filaPromediosMateriaHeader}", null, 1),
        ];

        $categories = [
            new DataSeriesValues('String', "{$sheet}!\$A\${$inicio}:\$A\${$fin}", null, max(1, $fin - $inicio + 1)),
        ];

        $values = [
            new DataSeriesValues('Number', "{$sheet}!\$B\${$inicio}:\$B\${$fin}", null, max(1, $fin - $inicio + 1)),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);

        $chart = new Chart(
            'grafica_promedio_materia',
            new Title('Promedio por materia'),
            $legend,
            $plotArea
        );

        $filaInicioGrafica = $this->ultimaFilaPeriodosMateria + 3;
        $chart->setTopLeftPosition("A{$filaInicioGrafica}");
        $chart->setBottomRightPosition('H' . ($filaInicioGrafica + 18));

        return $chart;
    }

    protected function crearGraficaPromedioAlumno(): Chart
    {
        $sheet = "'Calificaciones'";
        $inicio = $this->filaPromediosAlumnoHeader + 1;
        $fin = $this->ultimaFilaPromediosAlumno;

        $labels = [
            new DataSeriesValues('String', "{$sheet}!\$G\${$this->filaPromediosAlumnoHeader}", null, 1),
        ];

        $categories = [
            new DataSeriesValues('String', "{$sheet}!\$F\${$inicio}:\$F\${$fin}", null, max(1, $fin - $inicio + 1)),
        ];

        $values = [
            new DataSeriesValues('Number', "{$sheet}!\$G\${$inicio}:\$G\${$fin}", null, max(1, $fin - $inicio + 1)),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);

        $chart = new Chart(
            'grafica_promedio_alumno',
            new Title('Promedio por alumno'),
            $legend,
            $plotArea
        );

        $filaInicioGrafica = $this->ultimaFilaPeriodosMateria + 3;
        $chart->setTopLeftPosition("J{$filaInicioGrafica}");
        $chart->setBottomRightPosition('Q' . ($filaInicioGrafica + 18));

        return $chart;
    }

    protected function nombreGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? '—';
    }

    protected function resolverNombresFiltros(): void
    {
        $this->nivelNombre = Nivel::query()->where('id', $this->nivel_id)->value('nombre') ?? '—';
        $this->gradoNombre = Grado::query()->where('id', $this->grado_id)->value('nombre') ?? '—';

        $grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->where('id', $this->grupo_id)
            ->first();

        $this->grupoNombre = $this->nombreGrupo($grupo);

        $this->semestreNombre = Semestre::query()
            ->where('id', $this->semestre_id)
            ->value('numero') ?? '—';

        if ($this->generacion_id) {
            $generacion = Generacion::query()->find($this->generacion_id);

            $this->generacionNombre = $generacion
                ? trim(($generacion->anio_ingreso ?? '') . ' - ' . ($generacion->anio_egreso ?? ''))
                : '—';
        }

        $periodo = Periodos::query()
            ->with([
                'cicloEscolar',
                'periodoBasica',
                'parcialBachillerato',
            ])
            ->where('id', $this->periodo_id)
            ->first();

        if ($periodo) {
            $inicio = $periodo->fecha_inicio ? date('d/m/Y', strtotime($periodo->fecha_inicio)) : 'Sin inicio';
            $fin = $periodo->fecha_fin ? date('d/m/Y', strtotime($periodo->fecha_fin)) : 'Sin fin';
            $descripcion = 'Periodo seleccionado';

            if (!$this->esBachillerato) {
                $descripcion = $periodo?->periodoBasica?->descripcion
                    ?? $periodo?->periodoBasica?->periodo
                    ?? $descripcion;
            }

            if ($this->esBachillerato) {
                $descripcion = $periodo?->parcialBachillerato?->descripcion
                    ?? $periodo?->parcialBachillerato?->parcial
                    ?? $descripcion;
            }

            $this->periodoNombre = $descripcion . ' · ' . $inicio . ' - ' . $fin;

            $this->cicloEscolarNombre = $periodo->cicloEscolar
                ? $periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio
                : '—';
        }
    }

    protected function obtenerMaterias(): array
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->grupo_id) {
            return [];
        }

        $query = AsignacionMateria::query()
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select([
                'asignacion_materias.id',
                'asignacion_materias.materia_id',
                'asignacion_materias.grupo_id',
                'asignacion_materias.profesor_id',
                'asignacion_materias.orden',
                'materias.materia as materia',
                'materias.extra as extra',
                'materias.calificable as calificable',
                'materias.receso as receso',
            ])
            ->where('materias.nivel_id', $this->nivel_id)
            ->where('materias.grado_id', $this->grado_id)
            ->where('asignacion_materias.grupo_id', $this->grupo_id)
            ->where('materias.calificable', 1);

        if (Schema::hasColumn('materias', 'semestre_id')) {
            if ($this->esBachillerato) {
                $query->where('materias.semestre_id', $this->semestre_id);
            } else {
                $query->whereNull('materias.semestre_id');
            }
        }

        /*
         * Se respeta el orden de la asignación de materias.
         * Las materias sin orden se mandan al final.
         */
        $query
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id');

        return $query->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'materia' => $item->materia ?: 'MATERIA',
                    'orden' => $item->orden,
                    'extra' => (int) ($item->extra ?? 0),
                    'receso' => (int) ($item->receso ?? 0),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function obtenerInscripciones(): array
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->grupo_id) {
            return [];
        }

        $query = Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo' => function ($query) {
                    $query->select('id', 'asignacion_grupo_id')
                        ->with('asignacionGrupo:id,nombre');
                },
                'semestre:id,numero',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id);

        if ($this->generacion_id) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id);
        } else {
            if (Schema::hasColumn('inscripciones', 'semestre_id')) {
                $query->whereNull('semestre_id');
            }
        }

        if (Schema::hasColumn('inscripciones', 'activo')) {
            $query->where('activo', 1);
        }

        if ($this->busqueda !== '') {
            $buscar = $this->busqueda;

            $query->where(function ($q) use ($buscar) {
                $q->where('matricula', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                    ->orWhere(
                        DB::raw("TRIM(CONCAT(nombre,' ',IFNULL(apellido_paterno,''),' ',IFNULL(apellido_materno,'')))"),
                        'like',
                        "%{$buscar}%"
                    );
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'inscripcion_id' => (int) $item->id,
                    'matricula' => $item->matricula ?: '—',
                    'alumno' => trim(
                        ($item->nombre ?? '') . ' ' .
                        ($item->apellido_paterno ?? '') . ' ' .
                        ($item->apellido_materno ?? '')
                    ) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $this->nombreGrupo($item->grupo),
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();
    }

    protected function obtenerCalificaciones(array $inscripciones, array $materias): array
    {
        $idsInscripciones = collect($inscripciones)->pluck('inscripcion_id')->values()->all();
        $idsMaterias = collect($materias)->pluck('id')->values()->all();

        if (empty($idsInscripciones) || empty($idsMaterias) || !$this->periodo_id) {
            return [];
        }

        $query = Calificacion::query()
            ->whereIn('inscripcion_id', $idsInscripciones)
            ->whereIn('asignacion_materia_id', $idsMaterias)
            ->where('periodo_id', $this->periodo_id);

        if (Schema::hasColumn('calificaciones', 'nivel_id')) {
            $query->where('nivel_id', $this->nivel_id);
        }

        if (Schema::hasColumn('calificaciones', 'grado_id')) {
            $query->where('grado_id', $this->grado_id);
        }

        if (Schema::hasColumn('calificaciones', 'grupo_id')) {
            $query->where('grupo_id', $this->grupo_id);
        }

        if ($this->generacion_id && Schema::hasColumn('calificaciones', 'generacion_id')) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if (Schema::hasColumn('calificaciones', 'semestre_id')) {
            if ($this->esBachillerato) {
                $query->where('semestre_id', $this->semestre_id);
            } else {
                $query->whereNull('semestre_id');
            }
        }

        return $query->get()
            ->mapWithKeys(function ($item) {
                $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;

                return [
                    $clave => strtoupper(trim((string) $item->calificacion)),
                ];
            })
            ->toArray();
    }

    protected function calcularPromediosAlumnos(array $inscripciones, array $materias, array $calificaciones): array
    {
        $numeroMaterias = $this->obtenerNumeroMateriasAPromediar($materias);

        $materiasPromediables = $this->obtenerMateriasPromediables($materias);
        $promedios = [];

        foreach ($inscripciones as $inscripcion) {
            $inscripcionId = (int) $inscripcion['inscripcion_id'];

            /*
             * Si no hay materias asignadas para promediar,
             * el promedio queda como pendiente.
             */
            if ($numeroMaterias <= 0 || empty($materiasPromediables)) {
                $promedios[$inscripcionId] = 'Pendiente';
                continue;
            }

            $suma = 0;
            $totalNumericas = 0;

            foreach ($materiasPromediables as $materia) {
                $clave = $inscripcionId . '-' . $materia['id'];
                $valor = $calificaciones[$clave] ?? null;

                if (!$this->esValorNumericoValido($valor)) {
                    continue;
                }

                $suma += (float) $valor;
                $totalNumericas++;
            }

            /*
             * AC, NP o cualquier texto no suma y tampoco cuenta como divisor.
             */
            if ($totalNumericas === 0) {
                $promedios[$inscripcionId] = 'Pendiente';
                continue;
            }

            $promedio = $this->truncarPromedio($suma / $totalNumericas);

            $promedios[$inscripcionId] = number_format($promedio, 1, '.', '');
        }

        return $promedios;
    }

    protected function calcularPromediosPorMateria(array $inscripciones, array $materias, array $calificaciones): array
    {
        $materiasPromediables = $this->obtenerMateriasPromediables($materias);

        $promedios = [];

        foreach ($materiasPromediables as $materia) {
            $suma = 0;
            $total = 0;

            foreach ($inscripciones as $inscripcion) {
                $clave = $inscripcion['inscripcion_id'] . '-' . $materia['id'];
                $valor = $calificaciones[$clave] ?? null;

                if (!$this->esValorNumericoValido($valor)) {
                    continue;
                }

                $suma += (float) $valor;
                $total++;
            }

            $promedio = $total > 0
                ? $this->truncarPromedio($suma / $total)
                : 0;

            $promedios[] = [
                'materia' => $materia['materia'],
                'promedio' => number_format($promedio, 1, '.', ''),
                'promedio_numero' => (float) number_format($promedio, 1, '.', ''),
                'total_capturadas' => $total,
                'estado' => $this->estadoPromedio($promedio),
            ];
        }

        return $promedios;
    }

    protected function obtenerPeriodosPorMateria(array $materias): array
    {
        $periodo = Periodos::query()
            ->with([
                'periodoBasica',
                'parcialBachillerato',
            ])
            ->find($this->periodo_id);

        $nombrePeriodo = $this->periodoNombre;

        if ($periodo) {
            if (!$this->esBachillerato) {
                $nombrePeriodo = $periodo?->periodoBasica?->descripcion
                    ?? $periodo?->periodoBasica?->periodo
                    ?? $nombrePeriodo;
            }

            if ($this->esBachillerato) {
                $nombrePeriodo = $periodo?->parcialBachillerato?->descripcion
                    ?? $periodo?->parcialBachillerato?->parcial
                    ?? $nombrePeriodo;
            }
        }

        return collect($materias)
            ->map(function ($materia) use ($periodo, $nombrePeriodo) {
                return [
                    'materia' => $materia['materia'],
                    'tipo' => $this->esBachillerato ? 'Parcial bachillerato' : 'Periodo básica',
                    'periodo' => $nombrePeriodo,
                    'fecha_inicio' => $periodo?->fecha_inicio ? date('d/m/Y', strtotime($periodo->fecha_inicio)) : '—',
                    'fecha_fin' => $periodo?->fecha_fin ? date('d/m/Y', strtotime($periodo->fecha_fin)) : '—',
                ];
            })
            ->values()
            ->toArray();
    }

    protected function obtenerNumeroMateriasAPromediar(array $materias): int
    {
        if (!$this->nivel_id || !$this->grado_id) {
            return 0;
        }

        $query = MateriaPromediar::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id);

        if (Schema::hasColumn('materia_promediar', 'grupo_id')) {
            $query->where('grupo_id', $this->grupo_id);
        }

        if (Schema::hasColumn('materia_promediar', 'semestre_id')) {
            if ($this->esBachillerato) {
                $query->where('semestre_id', $this->semestre_id);
            } else {
                $query->whereNull('semestre_id');
            }
        }

        $registro = $query->first();

        /*
         * Si no existe registro o el número es 0,
         * no se promedia ninguna materia.
         */
        return $registro ? (int) $registro->numero_materias : 0;
    }

    protected function obtenerMateriasPromediables(array $materias): array
    {
        $numeroMaterias = $this->obtenerNumeroMateriasAPromediar($materias);

        if ($numeroMaterias <= 0) {
            return [];
        }

        return collect($materias)
            ->filter(function ($materia) {
                return (int) ($materia['extra'] ?? 0) === 0
                    && (int) ($materia['receso'] ?? 0) === 0;
            })
            ->sortBy([
                fn($materia) => ($materia['orden'] ?? null) === null ? 1 : 0,
                fn($materia) => $materia['orden'] ?? 999,
                fn($materia) => $materia['id'] ?? 999,
            ])
            ->values()
            ->toArray();
    }



    protected function esValorNumericoValido($valor): bool
    {
        $valor = strtoupper(trim((string) $valor));

        if ($valor === '' || !is_numeric($valor)) {
            return false;
        }

        $numero = (float) $valor;

        return $numero >= 0 && $numero <= 10;
    }

    protected function truncarPromedio(float $valor, int $decimales = 1): float
    {
        /*
         * promedio-numerico-pro:
         * Se toma solo el primer decimal sin redondear.
         * Ejemplo: 8.777777777777778 se muestra como 8.7.
         */
        $factor = pow(10, $decimales);

        return floor(($valor + 0.000000001) * $factor) / $factor;
    }

    protected function estadoPromedio($promedio): string
    {
        /*
         * Si no hay materias configuradas para promediar,
         * el estado debe quedar como Pendiente.
         */
        if ($this->obtenerNumeroMateriasAPromediar($this->materias) <= 0) {
            return 'Pendiente';
        }

        if (!is_numeric($promedio)) {
            return 'Pendiente';
        }

        $promedio = (float) $promedio;

        if ($promedio < 6) {
            return 'Reprobado';
        }

        if ($promedio < 8) {
            return 'Regular';
        }

        if ($promedio < 9.5) {
            return 'Bueno';
        }

        return 'Excelente';
    }
}
