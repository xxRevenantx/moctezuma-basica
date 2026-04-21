<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Calificacion as CalificacionModel;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Calificacion extends Component
{
    /** =======================
     * LISTAS PARA SELECTS
     * ======================= */
    public $niveles = [];
    public $grados = [];
    public $grupos = [];
    public $periodos = [];
    public $generaciones = [];
    public $semestres = [];

    /** =======================
     * FILTROS
     * ======================= */
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $periodo_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null;

    /** =======================
     * BUSCADOR
     * ======================= */
    public string $busqueda = '';

    /** =======================
     * TABLA
     * ======================= */
    public array $materias = [];
    public array $inscripciones = [];
    public array $calificaciones = [];

    /** =======================
     * ESTADO UI
     * ======================= */
    public bool $hayCambios = false;

    public function mount(): void
    {
        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();
    }

    public function getEsBachilleratoProperty(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);

        if (!$nivel) {
            return false;
        }

        return (int) $nivel->id === 4 || mb_strtolower((string) $nivel->slug) === 'bachillerato';
    }

    public function getPuedeGuardarProperty(): bool
    {
        return $this->hayCambios && $this->getErrorBag()->isEmpty();
    }

    public function getClaseGuardarProperty(): string
    {
        $base = 'inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-sky-400 to-indigo-500 text-white px-6 py-3 text-sm font-semibold shadow transition';

        return $this->puedeGuardar
            ? $base . ' hover:opacity-95'
            : $base . ' opacity-60 cursor-not-allowed';
    }

    public function updatedNivelId(): void
    {
        // Aquí limpio filtros dependientes.
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->busqueda = '';

        // Aquí limpio listas dependientes.
        $this->grados = [];
        $this->grupos = [];
        $this->periodos = [];
        $this->generaciones = [];
        $this->semestres = [];

        // Aquí limpio tabla.
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        if (!$this->nivel_id) {
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get();

        if ($this->esBachillerato) {
            $this->generaciones = Generacion::query()
                ->orderByDesc('id')
                ->get();

            $this->semestres = Semestre::query()
                ->orderBy('id')
                ->get();
        }

        $this->cargarPeriodos();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->busqueda = '';

        $this->grupos = [];
        $this->periodos = [];
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $this->cargarGrupos();
        $this->cargarPeriodos();
    }

    public function updatedSemestreId(): void
    {
        if (!$this->esBachillerato) {
            return;
        }

        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->busqueda = '';

        $this->grupos = [];
        $this->periodos = [];
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        $this->cargarGrupos();
        $this->cargarPeriodos();
    }

    public function updatedGeneracionId(): void
    {
        if (!$this->esBachillerato) {
            return;
        }

        $this->periodo_id = null;
        $this->busqueda = '';

        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        $this->cargarPeriodos();
    }


    public function updatedPeriodoId(): void
    {
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        $this->cargarDatosSiListo();
    }

    public function updatedGrupoId(): void
    {
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        if ($this->esBachillerato && $this->grupo_id) {
            $grupo = Grupo::query()
                ->select('id', 'generacion_id', 'semestre_id')
                ->find($this->grupo_id);

            if ($grupo) {
                if ($grupo->generacion_id) {
                    $this->generacion_id = (int) $grupo->generacion_id;
                }

                if ($grupo->semestre_id) {
                    $this->semestre_id = (int) $grupo->semestre_id;
                }
            }
        }

        $this->cargarPeriodos();
        $this->cargarDatosSiListo();
    }

    public function updatedBusqueda(): void
    {
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        $this->cargarDatosSiListo(false);
    }

    private function cargarGrupos(): void
    {
        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $query = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id);

        if ($this->esBachillerato && $this->semestre_id) {
            $query->where('semestre_id', $this->semestre_id);
        }

        $this->grupos = $query
            ->orderBy('nombre')
            ->get();
    }

    private function cargarPeriodos(): void
    {
        $this->periodos = [];

        if (!$this->nivel_id) {
            return;
        }

        $query = Periodos::query()
            ->with('cicloEscolar')
            ->where('nivel_id', $this->nivel_id);

        if ($this->esBachillerato) {
            if ($this->generacion_id) {
                $query->where('generacion_id', $this->generacion_id);
            }

            if ($this->semestre_id) {
                $query->where('semestre_id', $this->semestre_id);
            }
        }

        $this->periodos = $query
            ->orderBy('fecha_inicio')
            ->get()
            ->map(function ($item) {
                $fechaInicio = $item->fecha_inicio
                    ? \Carbon\Carbon::parse($item->fecha_inicio)
                    : null;

                $fechaFin = $item->fecha_fin
                    ? \Carbon\Carbon::parse($item->fecha_fin)
                    : null;

                $inicio = $fechaInicio ? $fechaInicio->format('d/m/Y') : 'Sin inicio';
                $fin = $fechaFin ? $fechaFin->format('d/m/Y') : 'Sin fin';

                return [
                    'id' => (int) $item->id,
                    'fecha_inicio' => $item->fecha_inicio,
                    'fecha_fin' => $item->fecha_fin,
                    'ciclo_escolar_id' => $item->ciclo_escolar_id ? (int) $item->ciclo_escolar_id : null,
                    'ciclo_escolar' => $item->cicloEscolar
                        ? $item->cicloEscolar->inicio_anio . '-' . $item->cicloEscolar->fin_anio
                        : 'Sin ciclo escolar',
                    'etiqueta' => $inicio . ' - ' . $fin,
                ];
            })
            ->values()
            ->toArray();

        if ($this->periodo_id && !collect($this->periodos)->contains('id', (int) $this->periodo_id)) {
            $this->periodo_id = null;
        }
    }

    public function getPeriodoSeleccionadoProperty(): ?array
    {
        if (!$this->periodo_id || empty($this->periodos)) {
            return null;
        }

        return collect($this->periodos)->firstWhere('id', (int) $this->periodo_id);
    }

    public function getNombrePeriodoProperty(): string
    {
        $periodo = $this->periodoSeleccionado;

        if (
            !$periodo ||
            empty($periodo['fecha_inicio']) ||
            empty($periodo['fecha_fin'])
        ) {
            return 'Periodo escolar';
        }

        $inicio = \Carbon\Carbon::parse($periodo['fecha_inicio'])->locale('es');
        $fin = \Carbon\Carbon::parse($periodo['fecha_fin'])->locale('es');

        $mesInicio = ucfirst($inicio->translatedFormat('F'));
        $mesFin = ucfirst($fin->translatedFormat('F'));

        return $mesInicio . ' - ' . $mesFin;
    }

    public function getEstadoPeriodoProperty(): string
    {
        $periodo = $this->periodoSeleccionado;

        if (!$periodo || empty($periodo['fecha_inicio']) || empty($periodo['fecha_fin'])) {
            return 'Sin definir';
        }

        $hoy = now()->startOfDay();
        $inicio = \Carbon\Carbon::parse($periodo['fecha_inicio'])->startOfDay();
        $fin = \Carbon\Carbon::parse($periodo['fecha_fin'])->startOfDay();

        if ($hoy->lt($inicio)) {
            return 'Próximo';
        }

        if ($hoy->gt($fin)) {
            return 'Finalizado';
        }

        return 'En curso';
    }

    public function getClaseEstadoPeriodoProperty(): string
    {
        return match ($this->estadoPeriodo) {
            'Finalizado' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
            'En curso' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/30 dark:text-sky-300',
            'Próximo' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
            default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
        };
    }

    public function getPorcentajePeriodoProperty(): int
    {
        $periodo = $this->periodoSeleccionado;

        if (!$periodo || empty($periodo['fecha_inicio']) || empty($periodo['fecha_fin'])) {
            return 0;
        }

        $hoy = now()->startOfDay();
        $inicio = \Carbon\Carbon::parse($periodo['fecha_inicio'])->startOfDay();
        $fin = \Carbon\Carbon::parse($periodo['fecha_fin'])->startOfDay();

        if ($hoy->lte($inicio)) {
            return 0;
        }

        if ($hoy->gte($fin)) {
            return 100;
        }

        $totalDias = max($inicio->diffInDays($fin), 1);
        $diasTranscurridos = $inicio->diffInDays($hoy);

        return (int) floor(($diasTranscurridos / $totalDias) * 100);
    }

    private function filtrosListos(): bool
    {
        $basicos = (bool) (
            $this->nivel_id &&
            $this->grado_id &&
            $this->grupo_id &&
            $this->periodo_id
        );

        if (!$basicos) {
            return false;
        }

        if ($this->esBachillerato) {
            return (bool) ($this->generacion_id && $this->semestre_id);
        }

        return true;
    }

    private function cargarDatosSiListo(bool $recargarMaterias = true): void
    {
        if (!$this->filtrosListos()) {
            return;
        }

        if ($recargarMaterias) {
            $this->cargarMaterias();
        }

        $this->cargarInscripciones();
        $this->prepararCalificacionesEnBlanco();
        $this->cargarCalificacionesGuardadas();
    }

    private function cargarMaterias(): void
    {
        $query = AsignacionMateria::query()
            ->with('profesor')
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('calificable', 1)
            ->orderBy('orden')
            ->orderBy('materia');

        if ($this->esBachillerato && $this->semestre_id) {
            $query->where('semestre', $this->semestre_id);
        }

        $asignaciones = $query->get();

        $this->materias = $asignaciones->map(function ($a) {
            $nombreProfesor = 'SIN PROFESOR ASIGNADO';

            if ($a->profesor) {
                $nombreProfesor = trim(
                    collect([
                        $a->profesor->nombre ?? null,
                        $a->profesor->apellido_paterno ?? null,
                        $a->profesor->apellido_materno ?? null,
                    ])->filter()->implode(' ')
                );
            }

            return [
                'id' => (int) $a->id,
                'materia' => $a->materia ?: 'MATERIA',
                'profesor' => $nombreProfesor,
                'calificable' => (bool) $a->calificable,
            ];
        })->values()->toArray();
    }

    private function cargarInscripciones(): void
    {
        $busqueda = trim($this->busqueda);

        $query = Inscripcion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('activo', true);

        if ($this->esBachillerato) {
            $query->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id);
        }

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('matricula', 'like', "%{$busqueda}%")
                    ->orWhere(DB::raw("TRIM(CONCAT(nombre,' ',IFNULL(apellido_paterno,''),' ',IFNULL(apellido_materno,'')))"), 'like', "%{$busqueda}%");
            });
        }

        $this->inscripciones = $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($r) {
                return [
                    'inscripcion_id' => (int) $r->id,
                    'matricula' => $r->matricula ?: '—',
                    'alumno' => trim($r->nombre . ' ' . ($r->apellido_paterno ?? '') . ' ' . ($r->apellido_materno ?? '')) ?: '—',
                ];
            })
            ->values()
            ->toArray();
    }

    private function prepararCalificacionesEnBlanco(): void
    {
        $calificacionesGuardadas = $this->calificaciones;

        $this->calificaciones = [];

        foreach ($this->inscripciones as $fila) {
            $insId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $m) {
                $asigId = (int) $m['id'];
                $this->calificaciones[$insId][$asigId] = $calificacionesGuardadas[$insId][$asigId] ?? '';
            }
        }
    }

    private function cargarCalificacionesGuardadas(): void
    {
        $idsIns = array_map(fn($f) => (int) $f['inscripcion_id'], $this->inscripciones);
        $idsAsig = array_map(fn($m) => (int) $m['id'], $this->materias);

        if (empty($idsIns) || empty($idsAsig)) {
            return;
        }

        $guardadas = CalificacionModel::query()
            ->whereIn('inscripcion_id', $idsIns)
            ->whereIn('asignacion_materia_id', $idsAsig)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('periodo_id', $this->periodo_id)
            ->get();

        foreach ($guardadas as $g) {
            $insId = (int) $g->inscripcion_id;
            $asigId = (int) $g->asignacion_materia_id;

            if (isset($this->calificaciones[$insId][$asigId])) {
                $this->calificaciones[$insId][$asigId] = (string) ((int) $g->calificacion);
            }
        }
    }

    public function limpiarFiltros(): void
    {
        $this->nivel_id = null;
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;

        $this->grados = [];
        $this->grupos = [];
        $this->periodos = [];
        $this->generaciones = [];
        $this->semestres = [];

        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;
        $this->busqueda = '';

        $this->resetErrorBag();
    }

    public function marcarCambio(): void
    {
        $this->hayCambios = true;
    }

    public function updated($propiedad, $valor): void
    {
        if (!str_starts_with($propiedad, 'calificaciones.')) {
            return;
        }

        if ($valor === '' || $valor === null) {
            $this->resetValidation($propiedad);
            $this->hayCambios = true;
            return;
        }

        $this->validateOnly(
            $propiedad,
            [
                $propiedad => 'nullable|integer|min:0|max:10',
            ],
            [
                'integer' => 'Debe ser un número entero.',
                'min' => 'Debe estar entre 0 y 10.',
                'max' => 'Debe estar entre 0 y 10.',
            ]
        );

        $this->hayCambios = true;
    }

    public function getTotalCeldasProperty(): int
    {
        return count($this->inscripciones) * count($this->materias);
    }

    public function getCeldasCapturadasProperty(): int
    {
        $capturadas = 0;

        foreach ($this->inscripciones as $fila) {
            $insId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $m) {
                $asigId = (int) $m['id'];
                $v = $this->calificaciones[$insId][$asigId] ?? null;

                if ($v === null || $v === '') {
                    continue;
                }

                if (is_numeric($v) && (int) $v >= 0 && (int) $v <= 10) {
                    $capturadas++;
                }
            }
        }

        return $capturadas;
    }

    public function getPorcentajeCapturaProperty(): float
    {
        $total = $this->totalCeldas;

        if ($total <= 0) {
            return 0.0;
        }

        return round(($this->celdasCapturadas / $total) * 100, 1);
    }

    public function promedioFila(int $inscripcionId): float
    {
        $suma = 0;
        $cont = 0;

        foreach ($this->materias as $m) {
            $asigId = (int) $m['id'];
            $v = $this->calificaciones[$inscripcionId][$asigId] ?? null;

            if ($v === null || $v === '' || !is_numeric($v)) {
                continue;
            }

            $v = (int) $v;

            if ($v < 0 || $v > 10) {
                continue;
            }

            $suma += $v;
            $cont++;
        }

        if ($cont === 0) {
            return 0.0;
        }

        $promedio = $suma / $cont;

        return floor($promedio * 10) / 10;
    }

    public function guardarCalificaciones(): void
    {
        if (!$this->hayCambios) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No hay cambios por guardar.',
                'position' => 'top-end',
            ]);
            return;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Hay calificaciones con error. Corrige los valores antes de guardar.',
                'position' => 'top-end',
            ]);
            return;
        }

        if (!$this->filtrosListos()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Completa todos los filtros antes de guardar.',
                'position' => 'top-end',
            ]);
            return;
        }

        $periodoSeleccionado = collect($this->periodos)
            ->firstWhere('id', (int) $this->periodo_id);

        if (!$periodoSeleccionado || empty($periodoSeleccionado['ciclo_escolar_id'])) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'El periodo seleccionado no tiene ciclo escolar asignado.',
                'position' => 'top-end',
            ]);
            return;
        }

        DB::transaction(function () use ($periodoSeleccionado) {
            foreach ($this->inscripciones as $fila) {
                $insId = (int) $fila['inscripcion_id'];

                if ($insId <= 0) {
                    continue;
                }

                $inscripcion = Inscripcion::find($insId);

                if (!$inscripcion) {
                    continue;
                }

                foreach ($this->materias as $m) {
                    $asigId = (int) $m['id'];
                    $valor = $this->calificaciones[$insId][$asigId] ?? null;

                    if ($valor === null || $valor === '') {
                        CalificacionModel::query()
                            ->where('inscripcion_id', $insId)
                            ->where('asignacion_materia_id', $asigId)
                            ->where('periodo_id', $this->periodo_id)
                            ->delete();
                        continue;
                    }

                    if (!is_numeric($valor)) {
                        continue;
                    }

                    $valor = (int) $valor;

                    if ($valor < 0 || $valor > 10) {
                        continue;
                    }

                    CalificacionModel::updateOrCreate(
                        [
                            'inscripcion_id' => $insId,
                            'asignacion_materia_id' => $asigId,
                            'nivel_id' => $this->nivel_id,
                            'grado_id' => $this->grado_id,
                            'grupo_id' => $this->grupo_id,
                            'generacion_id' => $inscripcion->generacion_id,
                            'semestre_id' => $inscripcion->semestre_id,
                            'periodo_id' => $this->periodo_id,
                            'ciclo_escolar_id' => $periodoSeleccionado['ciclo_escolar_id'],
                        ],
                        [
                            'calificacion' => $valor,
                        ]
                    );
                }
            }
        });

        $this->resetErrorBag();
        $this->hayCambios = false;

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Calificaciones guardadas',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('livewire.accion.calificacion', [
            'niveles' => $this->niveles,
            'grados' => $this->grados,
            'grupos' => $this->grupos,
            'periodos' => $this->periodos,
            'generaciones' => $this->generaciones,
            'semestres' => $this->semestres,
        ]);
    }
}
