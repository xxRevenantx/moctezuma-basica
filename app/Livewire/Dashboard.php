<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Dashboard extends Component
{
    public $nivel_id = '';

    public array $niveles = [];
    public array $resumen = [];
    public array $resumenNiveles = [];
    public array $alertas = [];
    public array $periodosProximos = [];
    public array $alumnosDocumentosPendientes = [];
    public array $gruposSinHorario = [];
    public array $graficaAlumnosNivel = [];

    public function mount(): void
    {
        $this->cargarDashboard();
    }

    public function updatedNivelId(): void
    {
        $this->cargarDashboard();

        $this->dispatch('actualizarGraficaAlumnosNivel', data: $this->graficaAlumnosNivel);
    }

    public function cargarDashboard(): void
    {
        $this->niveles = $this->obtenerNiveles();
        $this->resumen = $this->obtenerResumenGeneral();
        $this->resumenNiveles = $this->obtenerResumenPorNivel();
        $this->alertas = $this->obtenerAlertas();
        $this->periodosProximos = $this->obtenerPeriodosProximos();
        $this->alumnosDocumentosPendientes = $this->obtenerAlumnosConDocumentosPendientes();
        $this->gruposSinHorario = $this->obtenerGruposSinHorario();
        $this->graficaAlumnosNivel = $this->obtenerDatosGraficaAlumnosNivel();
    }

    private function tablaExiste(string $tabla): bool
    {
        return Schema::hasTable($tabla);
    }

    private function columnaExiste(string $tabla, string $columna): bool
    {
        return $this->tablaExiste($tabla) && Schema::hasColumn($tabla, $columna);
    }

    private function aplicarFiltroNivel($query, string $tabla)
    {
        if ($this->nivel_id && $this->columnaExiste($tabla, 'nivel_id')) {
            $query->where($tabla . '.nivel_id', $this->nivel_id);
        }

        return $query;
    }

    private function aplicarStatusActivo($query, string $tabla)
    {
        if ($this->columnaExiste($tabla, 'status')) {
            $query->whereIn($tabla . '.status', [
                1,
                '1',
                true,
                'true',
                'activo',
                'Activo',
                'ACTIVO',
            ]);
        }

        return $query;
    }

    private function contarTabla(string $tabla, bool $filtrarNivel = true, bool $filtrarStatus = false): int
    {
        if (!$this->tablaExiste($tabla)) {
            return 0;
        }

        $query = DB::table($tabla);

        if ($filtrarNivel) {
            $query = $this->aplicarFiltroNivel($query, $tabla);
        }

        if ($filtrarStatus) {
            $query = $this->aplicarStatusActivo($query, $tabla);
        }

        return $query->count();
    }

    private function obtenerNiveles(): array
    {
        if (!$this->tablaExiste('niveles')) {
            return [];
        }

        return DB::table('niveles')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->get()
            ->map(fn ($nivel) => [
                'id' => $nivel->id,
                'nombre' => $nivel->nombre,
            ])
            ->toArray();
    }

    private function obtenerResumenGeneral(): array
    {
        $alumnos = $this->contarTabla('inscripciones', true, true);
        $materias = $this->contarTabla('asignacion_materias');
        $calificaciones = $this->contarTabla('calificaciones', false);

        $totalEsperadoCalificaciones = $alumnos * max($materias, 1);

        $avanceCalificaciones = $totalEsperadoCalificaciones > 0
            ? floor(($calificaciones / $totalEsperadoCalificaciones) * 100)
            : 0;

        return [
            'alumnos' => $alumnos,
            'docentes' => $this->contarTabla('personas', false, true),
            'grupos' => $this->contarTabla('grupos'),
            'materias' => $materias,
            'horarios' => $this->contarTabla('horarios'),
            'periodos' => $this->contarTabla('periodos'),
            'calificaciones' => $calificaciones,
            'avance_calificaciones' => min($avanceCalificaciones, 100),
        ];
    }

    private function obtenerResumenPorNivel(): array
    {
        if (!$this->tablaExiste('niveles')) {
            return [];
        }

        return DB::table('niveles')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->get()
            ->map(function ($nivel) {
                return [
                    'id' => $nivel->id,
                    'nombre' => $nivel->nombre,
                    'alumnos' => $this->contarPorNivel('inscripciones', $nivel->id, true),
                    'grupos' => $this->contarPorNivel('grupos', $nivel->id),
                    'materias' => $this->contarPorNivel('asignacion_materias', $nivel->id),
                    'horarios' => $this->contarPorNivel('horarios', $nivel->id),
                    'periodos' => $this->contarPorNivel('periodos', $nivel->id),
                ];
            })
            ->toArray();
    }

    private function contarPorNivel(string $tabla, int $nivelId, bool $filtrarStatus = false): int
    {
        if (!$this->columnaExiste($tabla, 'nivel_id')) {
            return 0;
        }

        $query = DB::table($tabla)->where('nivel_id', $nivelId);

        if ($filtrarStatus) {
            $query = $this->aplicarStatusActivo($query, $tabla);
        }

        return $query->count();
    }

    private function obtenerAlertas(): array
    {
        $gruposSinHorario = count($this->obtenerGruposSinHorario());
        $materiasSinDocente = $this->contarMateriasSinDocente();
        $periodosPorCerrar = count($this->obtenerPeriodosProximos());
        $alumnosSinGrupo = $this->contarAlumnosSinGrupo();
        $documentosPendientes = count($this->obtenerAlumnosConDocumentosPendientes());

        return [
            [
                'titulo' => 'Documentos pendientes',
                'descripcion' => 'Alumnos con archivos o documentos faltantes.',
                'cantidad' => $documentosPendientes,
                'color' => 'from-amber-500 to-orange-500',
            ],
            [
                'titulo' => 'Grupos sin horario',
                'descripcion' => 'Grupos que todavía no tienen horario asignado.',
                'cantidad' => $gruposSinHorario,
                'color' => 'from-rose-500 to-red-500',
            ],
            [
                'titulo' => 'Materias sin docente',
                'descripcion' => 'Materias registradas sin profesor asignado.',
                'cantidad' => $materiasSinDocente,
                'color' => 'from-sky-500 to-blue-600',
            ],
            [
                'titulo' => 'Periodos por cerrar',
                'descripcion' => 'Periodos próximos a finalizar.',
                'cantidad' => $periodosPorCerrar,
                'color' => 'from-violet-500 to-purple-600',
            ],
            [
                'titulo' => 'Alumnos sin grupo',
                'descripcion' => 'Inscripciones que aún no tienen grupo asignado.',
                'cantidad' => $alumnosSinGrupo,
                'color' => 'from-emerald-500 to-teal-600',
            ],
        ];
    }

    private function contarMateriasSinDocente(): int
    {
        if (!$this->columnaExiste('asignacion_materias', 'profesor_id')) {
            return 0;
        }

        $query = DB::table('asignacion_materias')
            ->whereNull('profesor_id');

        $query = $this->aplicarFiltroNivel($query, 'asignacion_materias');

        return $query->count();
    }

    private function contarAlumnosSinGrupo(): int
    {
        if (!$this->columnaExiste('inscripciones', 'grupo_id')) {
            return 0;
        }

        $query = DB::table('inscripciones')
            ->whereNull('grupo_id');

        $query = $this->aplicarFiltroNivel($query, 'inscripciones');
        $query = $this->aplicarStatusActivo($query, 'inscripciones');

        return $query->count();
    }

    private function obtenerPeriodosProximos(): array
    {
        if (!$this->tablaExiste('periodos') || !$this->columnaExiste('periodos', 'fecha_fin')) {
            return [];
        }

        $query = DB::table('periodos')
            ->leftJoin('niveles', 'niveles.id', '=', 'periodos.nivel_id')
            ->select(
                'periodos.id',
                'periodos.fecha_inicio',
                'periodos.fecha_fin',
                'niveles.nombre as nivel'
            )
            ->whereNotNull('periodos.fecha_fin')
            ->whereDate('periodos.fecha_fin', '>=', now()->toDateString())
            ->whereDate('periodos.fecha_fin', '<=', now()->addDays(30)->toDateString())
            ->orderBy('periodos.fecha_fin')
            ->limit(5);

        if ($this->nivel_id && $this->columnaExiste('periodos', 'nivel_id')) {
            $query->where('periodos.nivel_id', $this->nivel_id);
        }

        return $query->get()->map(fn ($periodo) => [
            'id' => $periodo->id,
            'nivel' => $periodo->nivel ?? 'Sin nivel',
            'fecha_inicio' => $periodo->fecha_inicio,
            'fecha_fin' => $periodo->fecha_fin,
        ])->toArray();
    }

    private function obtenerAlumnosConDocumentosPendientes(): array
    {
        if (!$this->tablaExiste('inscripciones')) {
            return [];
        }

        $columnasDocumentos = [
            'acta_nacimiento',
            'curp_documento',
            'certificado_estudios',
            'comprobante_domicilio',
            'certificado_medico',
            'fotos_infantiles',
            'foto',
        ];

        $columnasExistentes = collect($columnasDocumentos)
            ->filter(fn ($columna) => $this->columnaExiste('inscripciones', $columna))
            ->values();

        if ($columnasExistentes->isEmpty()) {
            return [];
        }

        $query = DB::table('inscripciones')
            ->leftJoin('niveles', 'niveles.id', '=', 'inscripciones.nivel_id')
            ->leftJoin('grupos', 'grupos.id', '=', 'inscripciones.grupo_id')
            ->select(
                'inscripciones.id',
                'inscripciones.nombre',
                'inscripciones.apellido_paterno',
                'inscripciones.apellido_materno',
                'niveles.nombre as nivel',
                'grupos.nombre as grupo'
            );

        foreach ($columnasExistentes as $index => $columna) {
            if ($index === 0) {
                $query->where(function ($q) use ($columna) {
                    $q->whereNull('inscripciones.' . $columna)
                        ->orWhere('inscripciones.' . $columna, '')
                        ->orWhere('inscripciones.' . $columna, 0);
                });
            } else {
                $query->orWhere(function ($q) use ($columna) {
                    $q->whereNull('inscripciones.' . $columna)
                        ->orWhere('inscripciones.' . $columna, '')
                        ->orWhere('inscripciones.' . $columna, 0);
                });
            }
        }

        if ($this->nivel_id && $this->columnaExiste('inscripciones', 'nivel_id')) {
            $query->where('inscripciones.nivel_id', $this->nivel_id);
        }

        return $query
            ->limit(5)
            ->get()
            ->map(fn ($alumno) => [
                'id' => $alumno->id,
                'nombre' => trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno),
                'nivel' => $alumno->nivel ?? 'Sin nivel',
                'grupo' => $alumno->grupo ?? 'Sin grupo',
            ])
            ->toArray();
    }

    private function obtenerGruposSinHorario(): array
    {
        if (!$this->tablaExiste('grupos')) {
            return [];
        }

        $query = DB::table('grupos')
            ->leftJoin('niveles', 'niveles.id', '=', 'grupos.nivel_id')
            ->select(
                'grupos.id',
                'grupos.nombre',
                'niveles.nombre as nivel'
            );

        if ($this->tablaExiste('horarios') && $this->columnaExiste('horarios', 'grupo_id')) {
            $query->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('horarios')
                    ->whereColumn('horarios.grupo_id', 'grupos.id');
            });
        }

        if ($this->nivel_id && $this->columnaExiste('grupos', 'nivel_id')) {
            $query->where('grupos.nivel_id', $this->nivel_id);
        }

        return $query
            ->orderBy('niveles.id')
            ->orderBy('grupos.nombre')
            ->limit(5)
            ->get()
            ->map(fn ($grupo) => [
                'id' => $grupo->id,
                'grupo' => $grupo->nombre,
                'nivel' => $grupo->nivel ?? 'Sin nivel',
            ])
            ->toArray();
    }

    private function obtenerDatosGraficaAlumnosNivel(): array
    {
        if (!$this->tablaExiste('niveles')) {
            return [
                'labels' => [],
                'series' => [],
            ];
        }

        $niveles = DB::table('niveles')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->get();

        $labels = [];
        $series = [];

        foreach ($niveles as $nivel) {
            if ($this->nivel_id && (int) $this->nivel_id !== (int) $nivel->id) {
                continue;
            }

            $labels[] = $nivel->nombre;
            $series[] = $this->contarPorNivel('inscripciones', $nivel->id, true);
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
