<?php

namespace App\Exports;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;

class CalificacionExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
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

    protected int $filaEncabezadoTabla = 0;
    protected int $ultimaFila = 0;
    protected int $ultimaColumna = 0;

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
        $materias = $this->obtenerMaterias();
        $inscripciones = $this->obtenerInscripciones();
        $calificaciones = $this->obtenerCalificaciones($inscripciones, $materias);

        $filas = [];

        // Cabecera principal
        $filas[] = ['REPORTE DE CALIFICACIONES'];
        $filas[] = [''];

        // Filtros aplicados
        $filas[] = ['Nivel', $this->nivelNombre];
        $filas[] = ['Grado', $this->gradoNombre];
        if ($this->esBachillerato) {
            $filas[] = ['Semestre', $this->semestreNombre];
        }
        $filas[] = ['Grupo', $this->grupoNombre];
        $filas[] = ['Periodo', $this->periodoNombre];
        $filas[] = ['Ciclo escolar', $this->cicloEscolarNombre];
        $filas[] = ['Búsqueda aplicada', $this->busqueda !== '' ? $this->busqueda : 'Sin filtro'];
        $filas[] = [''];

        // Encabezados de tabla
        $encabezados = [
            'MATRÍCULA',
            'ALUMNO',
            'GRADO',
        ];

        if ($this->esBachillerato) {
            $encabezados[] = 'SEMESTRE';
        }

        $encabezados[] = 'GRUPO';

        foreach ($materias as $materia) {
            $encabezados[] = mb_strtoupper($materia['materia']);
        }

        $encabezados[] = 'PROMEDIO';

        $filas[] = $encabezados;

        $this->filaEncabezadoTabla = count($filas);

        foreach ($inscripciones as $inscripcion) {
            $fila = [
                $inscripcion['matricula'],
                $inscripcion['alumno'],
                $inscripcion['grado'],
            ];

            if ($this->esBachillerato) {
                $fila[] = $inscripcion['semestre'];
            }

            $fila[] = $inscripcion['grupo'];

            foreach ($materias as $materia) {
                $clave = $inscripcion['inscripcion_id'] . '-' . $materia['id'];
                $fila[] = $calificaciones[$clave] ?? '';
            }

            $fila[] = $this->calcularPromedioFila($inscripcion['inscripcion_id'], $materias, $calificaciones);

            $filas[] = $fila;
        }

        $this->ultimaFila = count($filas);
        $this->ultimaColumna = count($encabezados);

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $ultimaColumnaLetra = Coordinate::stringFromColumnIndex($this->ultimaColumna);

                // Título principal
                $sheet->mergeCells("A1:{$ultimaColumnaLetra}1");
                $sheet->getStyle("A1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1D4ED8'],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                // Etiquetas de filtros
                $ultimaFilaFiltros = $this->filaEncabezadoTabla - 2;

                for ($fila = 3; $fila <= $ultimaFilaFiltros; $fila++) {
                    $sheet->getStyle("A{$fila}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '1E293B'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E2E8F0'],
                        ],
                    ]);

                    $sheet->getStyle("A{$fila}:B{$fila}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);
                }

                // Encabezados de tabla
                $sheet->getStyle("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->filaEncabezadoTabla}")
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '0F172A'],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                        ],
                    ]);

                // Bordes de tabla
                $sheet->getStyle("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->ultimaFila}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                // Alineación general
                $sheet->getStyle("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->ultimaFila}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Centrar columnas numéricas / materias / promedio
                $columnaInicioMaterias = $this->esBachillerato ? 6 : 5;
                $columnaPromedio = $this->ultimaColumna;

                $inicioLetra = Coordinate::stringFromColumnIndex($columnaInicioMaterias);
                $promedioLetra = Coordinate::stringFromColumnIndex($columnaPromedio);

                $sheet->getStyle("{$inicioLetra}" . ($this->filaEncabezadoTabla + 1) . ":{$promedioLetra}{$this->ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Congelar encabezados
                $sheet->freezePane('A' . ($this->filaEncabezadoTabla + 1));

                // Autofiltro
                $sheet->setAutoFilter("A{$this->filaEncabezadoTabla}:{$ultimaColumnaLetra}{$this->ultimaFila}");

                // Altura de encabezado de tabla
                $sheet->getRowDimension($this->filaEncabezadoTabla)->setRowHeight(28);
            },
        ];
    }

    private function resolverNombresFiltros(): void
    {
        $this->nivelNombre = Nivel::query()->where('id', $this->nivel_id)->value('nombre') ?? '—';
        $this->gradoNombre = Grado::query()->where('id', $this->grado_id)->value('nombre') ?? '—';
        $this->grupoNombre = Grupo::query()->where('id', $this->grupo_id)->value('nombre') ?? '—';
        $this->semestreNombre = Semestre::query()->where('id', $this->semestre_id)->value('numero') ?? '—';

        $periodo = Periodos::query()
            ->with('cicloEscolar')
            ->where('id', $this->periodo_id)
            ->first();

        if ($periodo) {
            $inicio = $periodo->fecha_inicio ? date('d/m/Y', strtotime($periodo->fecha_inicio)) : 'Sin inicio';
            $fin = $periodo->fecha_fin ? date('d/m/Y', strtotime($periodo->fecha_fin)) : 'Sin fin';

            $this->periodoNombre = $inicio . ' - ' . $fin;
            $this->cicloEscolarNombre = $periodo->cicloEscolar
                ? $periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio
                : '—';
        }
    }

    private function obtenerMaterias(): array
    {
        if (!$this->nivel_id || !$this->grupo_id) {
            return [];
        }

        $query = AsignacionMateria::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('calificable', 1)
            ->orderBy('orden')
            ->orderBy('materia');

        if ($this->esBachillerato && $this->semestre_id) {
            $query->where('semestre', $this->semestre_id);
        } else {
            $query->where('grado_id', $this->grado_id)
                ->whereNull('semestre');
        }

        return $query->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'materia' => $item->materia ?: 'MATERIA',
                    'extra' => (int) ($item->extra ?? 0),
                ];
            })
            ->values()
            ->toArray();
    }

    private function obtenerInscripciones(): array
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->grupo_id) {
            return [];
        }

        $query = Inscripcion::query()
            ->with(['grado:id,nombre', 'grupo:id,nombre', 'semestre:id,numero'])
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id);

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id)
                ->where('generacion_id', $this->generacion_id);
        }

        if ($this->busqueda !== '') {
            $buscar = $this->busqueda;

            $query->where(function ($q) use ($buscar) {
                $q->where('matricula', 'like', "%{$buscar}%")
                    ->orWhere(DB::raw("TRIM(CONCAT(nombre,' ',IFNULL(apellido_paterno,''),' ',IFNULL(apellido_materno,'')))"), 'like', "%{$buscar}%");
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
                    'alumno' => trim($item->nombre . ' ' . ($item->apellido_paterno ?? '') . ' ' . ($item->apellido_materno ?? '')) ?: '—',
                    'grado' => $item->grado?->nombre ?? '—',
                    'grupo' => $item->grupo?->nombre ?? '—',
                    'semestre' => $item->semestre?->numero ?? '—',
                ];
            })
            ->values()
            ->toArray();
    }

    private function obtenerCalificaciones(array $inscripciones, array $materias): array
    {
        $idsInscripciones = collect($inscripciones)->pluck('inscripcion_id')->values()->all();
        $idsMaterias = collect($materias)->pluck('id')->values()->all();

        if (empty($idsInscripciones) || empty($idsMaterias) || !$this->periodo_id) {
            return [];
        }

        $query = Calificacion::query()
            ->whereIn('inscripcion_id', $idsInscripciones)
            ->whereIn('asignacion_materia_id', $idsMaterias)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('periodo_id', $this->periodo_id);

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id)
                ->where('generacion_id', $this->generacion_id);
        }

        return $query->get()
            ->mapWithKeys(function ($item) {
                $clave = $item->inscripcion_id . '-' . $item->asignacion_materia_id;
                return [$clave => strtoupper(trim((string) $item->calificacion))];
            })
            ->toArray();
    }

    private function calcularPromedioFila(int $inscripcionId, array $materias, array $calificaciones): string
    {
        $numeroMaterias = $this->obtenerNumeroMateriasAPromediar();

        if ($numeroMaterias <= 0) {
            return '—';
        }

        $suma = 0;

        foreach ($materias as $materia) {
            if ((int) ($materia['extra'] ?? 0) !== 0) {
                continue;
            }

            $clave = $inscripcionId . '-' . $materia['id'];
            $valor = $calificaciones[$clave] ?? null;

            if ($valor === null || $valor === '') {
                continue;
            }

            $valor = strtoupper(trim((string) $valor));

            if (is_numeric($valor)) {
                $numero = (int) $valor;

                if ($numero >= 0 && $numero <= 10) {
                    $suma += $numero;
                }
            }
        }

        $promedio = $suma / $numeroMaterias;

        return number_format($promedio, 1);
    }

    private function obtenerNumeroMateriasAPromediar(): int
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->grupo_id) {
            return 0;
        }

        if ($this->esBachillerato) {
            $registro = MateriaPromediar::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('grado_id', $this->grado_id)
                ->where('grupo_id', $this->grupo_id)
                ->where('semestre_id', $this->semestre_id)
                ->first();

            return (int) ($registro?->numero_materias ?? 0);
        }

        $registro = MateriaPromediar::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->whereNull('semestre_id')
            ->first();

        return (int) ($registro?->numero_materias ?? 0);
    }
}
