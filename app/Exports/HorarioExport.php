<?php

namespace App\Exports;

use App\Models\Dia;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\Semestre;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class HorarioExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithEvents, WithTitle
{
    protected ?Nivel $nivel = null;
    protected ?Generacion $generacion = null;
    protected ?Grado $grado = null;
    protected ?Grupo $grupo = null;
    protected ?Semestre $semestre = null;

    protected Collection $horas;
    protected Collection $dias;
    protected Collection $horariosGuardados;
    protected Collection $talleresGuardados;

    protected int $filaEncabezadoTabla = 9;
    protected int $filaFinalTabla = 9;

    public function __construct(
        protected ?int $nivel_id,
        protected ?int $grado_id,
        protected ?int $grupo_id,
        protected ?int $generacion_id,
        protected ?int $semestre_id = null,
        protected bool $esBachillerato = false,
        protected ?int $ciclo_escolar_id = null,
    ) {
        $this->cargarDatos();
    }

    public function title(): string
    {
        return 'Horario';
    }

    public function array(): array
    {
        $filas = [];

        $filas[] = ['CENTRO UNIVERSITARIO MOCTEZUMA A.C.'];
        $filas[] = ['HORARIO DE CLASES'];
        $filas[] = ['Exportado el ' . now()->format('d/m/Y h:i A')];
        $filas[] = [];

        $filas[] = [
            'Nivel:',
            $this->nivel?->nombre ?? 'N/D',
            'Generación:',
            $this->generacion
            ? $this->generacion->anio_ingreso . ' - ' . $this->generacion->anio_egreso
            : 'N/D',
        ];

        $filas[] = [
            'Grado:',
            $this->grado?->nombre ?? 'N/D',
            'Grupo:',
            $this->textoGrupo($this->grupo),
            $this->esBachillerato ? 'Semestre:' : '',
            $this->esBachillerato && $this->semestre
            ? $this->semestre->numero . '° semestre'
            : '',
        ];

        $filas[] = [];

        $encabezado = ['Hora'];

        foreach ($this->dias as $dia) {
            $encabezado[] = mb_strtoupper($dia->dia);
        }

        $filas[] = $encabezado;

        foreach ($this->horas as $hora) {
            $fila = [
                $this->formatearHora($hora->hora_inicio) . "\n" . $this->formatearHora($hora->hora_fin),
            ];

            foreach ($this->dias as $dia) {
                $clave = $hora->id . '-' . $dia->id;
                $horario = $this->horariosGuardados->get($clave);
                $talleres = $this->talleresGuardados->get($clave, collect());
                $contenidos = collect();

                if ($horario?->asignacionMateria) {
                    $materia = $horario->asignacionMateria?->materia;
                    $profesor = $horario->asignacionMateria?->profesor;
                    $nombreProfesor = $this->nombreProfesor($profesor);
                    $texto = $materia?->materia ?? 'Sin materia';

                    if (!empty($materia?->clave)) {
                        $texto .= "\nClave: " . $materia->clave;
                    }

                    $texto .= "\nDocente: " . $nombreProfesor;

                    if (!empty($materia?->receso)) {
                        $texto .= "\nRECESO";
                    }

                    $contenidos->push($texto);
                }

                foreach ($talleres->unique('taller_sesion_id') as $tallerHorario) {
                    $sesion = $tallerHorario->tallerSesion;
                    $grupos = $sesion?->grupos
                            ?->map(fn($grupo) => trim(($grupo->grado?->nombre ?? '') . ' ' . ($grupo->asignacionGrupo?->nombre ?? '')))
                        ->filter()
                        ->implode(', ');

                    $texto = "TALLER CONJUNTO\n" . ($sesion?->taller?->nombre ?? 'Taller');

                    if (!empty($sesion?->taller?->clave)) {
                        $texto .= "\nClave: " . $sesion->taller->clave;
                    }

                    $texto .= "\nDocente: " . $this->nombreProfesor($sesion?->profesor);
                    $texto .= "\nGrupos: " . ($grupos ?: 'Sin grupos');
                    $contenidos->push($texto);
                }

                $fila[] = $contenidos->isEmpty()
                    ? 'Sin asignar'
                    : $contenidos->implode("\n\n");
            }

            $filas[] = $fila;
        }

        $this->filaFinalTabla = count($filas);

        $totalCeldas = $this->horas->count() * $this->dias->count();
        $celdasAsignadas = $this->horariosGuardados
            ->keys()
            ->merge($this->talleresGuardados->keys())
            ->unique()
            ->count();
        $avance = $totalCeldas > 0 ? round(($celdasAsignadas / $totalCeldas) * 100) : 0;

        $filas[] = [];
        $filas[] = ['Resumen del horario'];
        $filas[] = ['Horas registradas:', $this->horas->count()];
        $filas[] = ['Días registrados:', $this->dias->count()];
        $filas[] = ['Celdas asignadas:', $celdasAsignadas . ' de ' . $totalCeldas];
        $filas[] = ['Avance:', $avance . '%'];

        return $filas;
    }

    public function columnWidths(): array
    {
        $anchos = [
            'A' => 18,
        ];

        $indice = 2;

        foreach ($this->dias as $dia) {
            $anchos[Coordinate::stringFromColumnIndex($indice)] = 34;
            $indice++;
        }

        return $anchos;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();

                $totalColumnas = max(2, $this->dias->count() + 1);
                $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

                $hoja->mergeCells("A1:{$ultimaColumna}1");
                $hoja->mergeCells("A2:{$ultimaColumna}2");
                $hoja->mergeCells("A3:{$ultimaColumna}3");

                $hoja->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
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

                $hoja->getStyle("A2:{$ultimaColumna}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '88AC2E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $hoja->getStyle("A3:{$ultimaColumna}3")->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 10,
                        'color' => ['rgb' => '64748B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $hoja->getStyle("A5:{$ultimaColumna}6")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $filaHeader = $this->filaEncabezadoTabla;
                $filaInicioCuerpo = $filaHeader + 1;
                $filaFinCuerpo = $this->filaFinalTabla;

                $hoja->getStyle("A{$filaHeader}:{$ultimaColumna}{$filaHeader}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E40AF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                ]);

                if ($filaFinCuerpo >= $filaInicioCuerpo) {
                    $hoja->getStyle("A{$filaInicioCuerpo}:{$ultimaColumna}{$filaFinCuerpo}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    $hoja->getStyle("A{$filaInicioCuerpo}:A{$filaFinCuerpo}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '334155'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F1F5F9'],
                        ],
                    ]);

                    for ($fila = $filaInicioCuerpo; $fila <= $filaFinCuerpo; $fila++) {
                        $hoja->getRowDimension($fila)->setRowHeight(72);

                        for ($columna = 2; $columna <= $totalColumnas; $columna++) {
                            $celda = Coordinate::stringFromColumnIndex($columna) . $fila;
                            $valor = (string) $hoja->getCell($celda)->getValue();

                            if (str_contains($valor, 'Sin asignar')) {
                                $hoja->getStyle($celda)->applyFromArray([
                                    'font' => [
                                        'italic' => true,
                                        'color' => ['rgb' => '94A3B8'],
                                    ],
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'F8FAFC'],
                                    ],
                                ]);
                            } elseif (str_contains($valor, 'TALLER CONJUNTO')) {
                                $hoja->getStyle($celda)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['rgb' => '0E7490'],
                                    ],
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'CFFAFE'],
                                    ],
                                ]);
                            } elseif (str_contains($valor, 'RECESO')) {
                                $hoja->getStyle($celda)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['rgb' => '92400E'],
                                    ],
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'FEF3C7'],
                                    ],
                                ]);
                            } else {
                                $hoja->getStyle($celda)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['rgb' => '0F172A'],
                                    ],
                                    'fill' => [
                                        'fillType' => Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'E0F2FE'],
                                    ],
                                ]);
                            }
                        }
                    }
                }

                $filaResumen = $this->filaFinalTabla + 2;

                $hoja->mergeCells("A{$filaResumen}:{$ultimaColumna}{$filaResumen}");

                $hoja->getStyle("A{$filaResumen}:{$ultimaColumna}{$filaResumen}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F766E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $hoja->getStyle("A" . ($filaResumen + 1) . ":B" . ($filaResumen + 4))->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0FDFA'],
                    ],
                ]);

                $hoja->freezePane("B{$filaInicioCuerpo}");
                $hoja->setAutoFilter("A{$filaHeader}:{$ultimaColumna}{$filaHeader}");

                $hoja->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $hoja->getPageMargins()->setTop(0.35);
                $hoja->getPageMargins()->setRight(0.25);
                $hoja->getPageMargins()->setLeft(0.25);
                $hoja->getPageMargins()->setBottom(0.35);

                $hoja->getStyle("A1:{$ultimaColumna}" . ($this->filaFinalTabla + 6))
                    ->getAlignment()
                    ->setWrapText(true);

                $hoja->getRowDimension(1)->setRowHeight(28);
                $hoja->getRowDimension(2)->setRowHeight(24);
                $hoja->getRowDimension(3)->setRowHeight(20);
                $hoja->getRowDimension($filaHeader)->setRowHeight(28);
            },
        ];
    }

    protected function cargarDatos(): void
    {
        $this->nivel = Nivel::query()->find($this->nivel_id);
        $this->generacion = Generacion::query()->find($this->generacion_id);
        $this->grado = Grado::query()->find($this->grado_id);

        $this->grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);

        $this->semestre = $this->esBachillerato && $this->semestre_id
            ? Semestre::query()->find($this->semestre_id)
            : null;

        $this->horas = Hora::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();

        $this->dias = Dia::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->get()
            ->unique('dia')
            ->values();

        $horarios = Horario::query()
            ->with([
                'asignacionMateria.materia',
                'asignacionMateria.profesor',
                'tallerSesion.taller',
                'tallerSesion.profesor',
                'tallerSesion.grupos.grado',
                'tallerSesion.grupos.asignacionGrupo',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->when($this->ciclo_escolar_id, fn($query) => $query->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->get();

        $this->horariosGuardados = $horarios
            ->whereNull('taller_sesion_id')
            ->keyBy(fn($horario) => $horario->hora_id . '-' . $horario->dia_id);

        $this->talleresGuardados = $horarios
            ->whereNotNull('taller_sesion_id')
            ->groupBy(fn($horario) => $horario->hora_id . '-' . $horario->dia_id);
    }

    protected function nombreProfesor($profesor): string
    {
        if (!$profesor) {
            return 'Sin profesor asignado';
        }

        $nombre = trim(
            ($profesor->titulo ?? '') . ' ' .
            ($profesor->nombre ?? '') . ' ' .
            ($profesor->apellido_paterno ?? '') . ' ' .
            ($profesor->apellido_materno ?? '')
        );

        return $nombre !== '' ? $nombre : 'Sin profesor asignado';
    }

    protected function textoGrupo(?Grupo $grupo, string $valorPorDefecto = 'Sin grupo'): string
    {
        if (!$grupo) {
            return $valorPorDefecto;
        }

        return $grupo->asignacionGrupo?->nombre ?? $valorPorDefecto;
    }

    protected function formatearHora(?string $hora): string
    {
        if (blank($hora)) {
            return 'N/D';
        }

        return Carbon::createFromFormat('H:i:s', $hora)->format('h:i A');
    }
}
