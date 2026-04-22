<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Calificacion as CalificacionModel;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Calificacion extends Component
{
    /* =========================
     * LISTAS
     * ========================= */
    public $niveles = [];
    public $grados = [];
    public $grupos = [];
    public $periodos = [];
    public $generaciones = [];
    public $semestres = [];

    /* =========================
     * FILTROS
     * ========================= */
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $periodo_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null;

    /* =========================
     * OTROS DATOS
     * ========================= */
    public string $busqueda = '';
    public array $materias = [];
    public array $inscripciones = [];
    public array $calificaciones = [];

    public ?Nivel $nivel = null;
    public string $slug_nivel = '';

    public bool $hayCambios = false;

    protected array $messages = [
        'calificaciones.*.*.max' => 'Solo se permiten 2 caracteres.',
        'calificaciones.*.*.regex' => 'Solo se permite un número del 0 al 10 o AC, ED o RA.',
    ];

    public function mount(): void
    {
        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        if (!empty($this->slug_nivel)) {
            $this->nivel = $this->niveles->firstWhere('slug', $this->slug_nivel);

            if ($this->nivel) {
                $this->nivel_id = (int) $this->nivel->id;
            }
        }

        if ($this->nivel_id) {
            $this->cargarFiltrosInicialesPorNivel();
        }
    }

    protected function rules(): array
    {
        return [
            'calificaciones.*.*' => [
                'nullable',
                'string',
                'max:2',
                'regex:/^(10|[0-9]|AC|ED|RA)$/',
            ],
        ];
    }

    public function getEsBachilleratoProperty(): bool
    {
        if ($this->nivel_id) {
            $nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);

            if ($nivel) {
                return (int) $nivel->id === 4
                    || mb_strtolower((string) ($nivel->slug ?? '')) === 'bachillerato';
            }
        }

        return mb_strtolower((string) $this->slug_nivel) === 'bachillerato';
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

    private function cargarFiltrosInicialesPorNivel(): void
    {
        $this->grados = [];
        $this->grupos = [];
        $this->generaciones = [];
        $this->semestres = [];
        $this->periodos = [];

        if ($this->esBachillerato) {
            $this->generaciones = Generacion::query()
                ->whereIn('id', function ($query) {
                    $query->select('generacion_id')
                        ->from('grupos')
                        ->where('nivel_id', $this->nivel_id)
                        ->whereNotNull('generacion_id')
                        ->distinct();
                })
                ->orderByDesc('id')
                ->get();

            $this->cargarSemestresDesdeGrupos();
            $this->resolverPeriodoAutomaticoBachillerato();
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get();

        $this->cargarPeriodos();
    }

    public function updatedNivelId(): void
    {
        $this->resetearFiltrosDependientes();

        if (!$this->nivel_id) {
            return;
        }

        $this->nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);

        $this->cargarFiltrosInicialesPorNivel();
    }

    public function updatedGeneracionId(): void
    {
        if (!$this->esBachillerato) {
            return;
        }

        $this->semestre_id = null;
        $this->periodo_id = null;
        $this->periodos = [];

        $this->limpiarTabla();

        $this->cargarSemestresDesdeGrupos();
        $this->resolverPeriodoAutomaticoBachillerato();
    }

    public function updatedSemestreId(): void
    {
        if (!$this->esBachillerato) {
            return;
        }

        $this->periodo_id = null;
        $this->periodos = [];

        $this->limpiarTabla();

        $this->resolverPeriodoAutomaticoBachillerato();
        $this->cargarDatosSiListo();
    }

    public function updatedGradoId(): void
    {
        if ($this->esBachillerato) {
            return;
        }

        $this->grupo_id = null;
        $this->periodo_id = null;

        $this->grupos = [];
        $this->periodos = [];

        $this->limpiarTabla();

        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $this->cargarGrupos();
        $this->cargarPeriodos();
    }

    public function updatedGrupoId(): void
    {
        if ($this->esBachillerato) {
            return;
        }

        $this->limpiarTabla();
        $this->cargarDatosSiListo();
    }

    public function updatedPeriodoId(): void
    {
        if ($this->esBachillerato) {
            return;
        }

        $this->limpiarTabla();
        $this->cargarDatosSiListo();
    }

    public function updatedBusqueda(): void
    {
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        $this->resetErrorBag();

        $this->cargarDatosSiListo(false);
    }

    public function updated($propiedad, $valor): void
    {
        if (!str_starts_with($propiedad, 'calificaciones.')) {
            return;
        }

        if (is_string($valor)) {
            $valor = strtoupper(trim($valor));
            data_set($this, $propiedad, $valor);
        }

        if ($valor === '' || $valor === null) {
            $this->resetValidation($propiedad);
            $this->hayCambios = true;
            return;
        }

        $this->validateOnly(
            $propiedad,
            [
                $propiedad => [
                    'nullable',
                    'string',
                    'max:2',
                    'regex:/^(10|[0-9]|AC|ED|RA)$/',
                ],
            ],
            [
                'max' => 'Solo se permiten 2 caracteres.',
                'regex' => 'Solo se permite un número del 0 al 10 o AC, ED o RA.',
            ]
        );

        $this->hayCambios = true;
    }

    private function resetearFiltrosDependientes(): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->busqueda = '';

        $this->grados = [];
        $this->grupos = [];
        $this->periodos = [];
        $this->generaciones = [];
        $this->semestres = [];

        $this->limpiarTabla();
    }

    private function limpiarTabla(): void
    {
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->hayCambios = false;

        $this->resetErrorBag();
    }

    private function cargarSemestresDesdeGrupos(): void
    {
        $this->semestres = [];

        if (!$this->nivel_id) {
            return;
        }

        $query = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->whereNotNull('semestre_id');

        if ($this->generacion_id) {
            $query->where('generacion_id', $this->generacion_id);
        }

        $idsSemestres = $query
            ->distinct()
            ->pluck('semestre_id')
            ->filter()
            ->values();

        if ($idsSemestres->isEmpty()) {
            return;
        }

        $this->semestres = Semestre::query()
            ->whereIn('id', $idsSemestres)
            ->orderBy('id')
            ->get();
    }

    private function cargarGrupos(): void
    {
        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $this->grupos = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->orderBy('nombre')
            ->get();
    }

    private function obtenerGrupoIdsSeleccionados(): array
    {
        if (!$this->nivel_id) {
            return [];
        }

        if ($this->esBachillerato) {
            if (!$this->generacion_id || !$this->semestre_id) {
                return [];
            }

            return Grupo::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->toArray();
        }

        if (!$this->grupo_id) {
            return [];
        }

        return [(int) $this->grupo_id];
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
                $inicio = $item->fecha_inicio ? date('d/m/Y', strtotime($item->fecha_inicio)) : 'Sin inicio';
                $fin = $item->fecha_fin ? date('d/m/Y', strtotime($item->fecha_fin)) : 'Sin fin';

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
    }

    private function resolverPeriodoAutomaticoBachillerato(): void
    {
        if (!$this->esBachillerato) {
            return;
        }

        $this->periodo_id = null;
        $this->periodos = [];

        if (!$this->nivel_id || !$this->generacion_id || !$this->semestre_id) {
            return;
        }

        $hoy = now()->toDateString();

        $periodo = Periodos::query()
            ->with('cicloEscolar')
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('semestre_id', $this->semestre_id)
            ->orderByRaw("
                CASE
                    WHEN fecha_inicio <= ? AND fecha_fin >= ? THEN 0
                    WHEN fecha_inicio > ? THEN 1
                    ELSE 2
                END
            ", [$hoy, $hoy, $hoy])
            ->orderBy('fecha_inicio')
            ->first();

        if (!$periodo) {
            return;
        }

        $this->periodo_id = (int) $periodo->id;

        $inicio = $periodo->fecha_inicio ? date('d/m/Y', strtotime($periodo->fecha_inicio)) : 'Sin inicio';
        $fin = $periodo->fecha_fin ? date('d/m/Y', strtotime($periodo->fecha_fin)) : 'Sin fin';

        $this->periodos = [
            [
                'id' => (int) $periodo->id,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
                'ciclo_escolar_id' => $periodo->ciclo_escolar_id ? (int) $periodo->ciclo_escolar_id : null,
                'ciclo_escolar' => $periodo->cicloEscolar
                    ? $periodo->cicloEscolar->inicio_anio . '-' . $periodo->cicloEscolar->fin_anio
                    : 'Sin ciclo escolar',
                'etiqueta' => $inicio . ' - ' . $fin,
            ]
        ];
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

        if (!$periodo || empty($periodo['fecha_inicio']) || empty($periodo['fecha_fin'])) {
            return 'Periodo escolar';
        }

        $inicio = \Carbon\Carbon::parse($periodo['fecha_inicio'])->locale('es');
        $fin = \Carbon\Carbon::parse($periodo['fecha_fin'])->locale('es');

        return ucfirst($inicio->translatedFormat('F')) . ' - ' . ucfirst($fin->translatedFormat('F'));
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
        if (!$this->nivel_id) {
            return false;
        }

        if ($this->esBachillerato) {
            return (bool) (
                $this->generacion_id &&
                $this->semestre_id &&
                $this->periodo_id
            );
        }

        return (bool) (
            $this->grado_id &&
            $this->grupo_id &&
            $this->periodo_id
        );
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
        $grupoIds = $this->obtenerGrupoIdsSeleccionados();

        if (empty($grupoIds)) {
            $this->materias = [];
            return;
        }

        $query = AsignacionMateria::query()
            ->with('profesor')
            ->where('nivel_id', $this->nivel_id)
            ->whereIn('grupo_id', $grupoIds)
            ->where('calificable', 1)
            ->orderBy('orden')
            ->orderBy('materia');

        if ($this->esBachillerato && $this->semestre_id) {
            $query->where('semestre', $this->semestre_id);
        } else {
            if ($this->grado_id) {
                $query->where('grado_id', $this->grado_id);
            }
        }

        $asignaciones = $query->get();

        if ($this->esBachillerato) {
            $asignaciones = $asignaciones
                ->unique(function ($item) {
                    return ($item->slug ?: mb_strtolower(trim((string) $item->materia))) . '-' . ($item->semestre ?: 0);
                })
                ->values();
        }

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
                'extra' => (int) ($a->extra ?? 0),
            ];
        })->values()->toArray();
    }

    private function cargarInscripciones(): void
    {
        $busqueda = trim($this->busqueda);

        $query = Inscripcion::query()
            ->where('nivel_id', $this->nivel_id);

        if ($this->esBachillerato) {
            $query->where('generacion_id', $this->generacion_id);
        } else {
            $grupoIds = $this->obtenerGrupoIdsSeleccionados();

            if (empty($grupoIds)) {
                $this->inscripciones = [];
                return;
            }

            $query->where('grado_id', $this->grado_id)
                ->whereIn('grupo_id', $grupoIds);
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
            ->map(function ($item) {
                return [
                    'inscripcion_id' => (int) $item->id,
                    'matricula' => $item->matricula ?: '—',
                    'alumno' => trim($item->nombre . ' ' . ($item->apellido_paterno ?? '') . ' ' . ($item->apellido_materno ?? '')) ?: '—',
                ];
            })
            ->values()
            ->toArray();
    }

    private function prepararCalificacionesEnBlanco(): void
    {
        $calificacionesPrevias = $this->calificaciones;
        $this->calificaciones = [];

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $materia) {
                $asignacionId = (int) $materia['id'];

                $this->calificaciones[$inscripcionId][$asignacionId] =
                    $calificacionesPrevias[$inscripcionId][$asignacionId] ?? '';
            }
        }
    }

    private function cargarCalificacionesGuardadas(): void
    {
        $idsInscripciones = array_map(fn($item) => (int) $item['inscripcion_id'], $this->inscripciones);
        $idsAsignaciones = array_map(fn($item) => (int) $item['id'], $this->materias);
        $grupoIds = $this->obtenerGrupoIdsSeleccionados();

        if (empty($idsInscripciones) || empty($idsAsignaciones) || empty($grupoIds)) {
            return;
        }

        $query = CalificacionModel::query()
            ->whereIn('inscripcion_id', $idsInscripciones)
            ->whereIn('asignacion_materia_id', $idsAsignaciones)
            ->where('nivel_id', $this->nivel_id)
            ->whereIn('grupo_id', $grupoIds)
            ->where('periodo_id', $this->periodo_id);

        if ($this->esBachillerato) {
            $query->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id);
        } else {
            $query->where('grado_id', $this->grado_id);
        }

        $guardadas = $query->get();

        foreach ($guardadas as $item) {
            $inscripcionId = (int) $item->inscripcion_id;
            $asignacionId = (int) $item->asignacion_materia_id;

            if (isset($this->calificaciones[$inscripcionId][$asignacionId])) {
                $this->calificaciones[$inscripcionId][$asignacionId] = strtoupper(trim((string) $item->calificacion));
            }
        }
    }

    private function obtenerNumeroMateriasAPromediar(): int
    {
        $grupoIds = $this->obtenerGrupoIdsSeleccionados();

        if (empty($grupoIds) || !$this->nivel_id) {
            return 0;
        }

        if ($this->esBachillerato) {
            $registro = MateriaPromediar::query()
                ->where('nivel_id', $this->nivel_id)
                ->whereIn('grupo_id', $grupoIds)
                ->where('semestre_id', $this->semestre_id)
                ->first();

            return (int) ($registro?->numero_materias ?? 0);
        }

        if (!$this->grado_id || !$this->grupo_id) {
            return 0;
        }

        $registro = MateriaPromediar::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->whereNull('semestre_id')
            ->first();

        return (int) ($registro?->numero_materias ?? 0);
    }

    public function getTotalCeldasProperty(): int
    {
        $materiasBase = collect($this->materias)
            ->where('extra', 0)
            ->count();

        return count($this->inscripciones) * $materiasBase;
    }

    public function getCeldasCapturadasProperty(): int
    {
        $capturadas = 0;

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $materia) {
                if ((int) ($materia['extra'] ?? 0) !== 0) {
                    continue;
                }

                $asignacionId = (int) $materia['id'];
                $valor = $this->calificaciones[$inscripcionId][$asignacionId] ?? null;

                if ($valor === null || $valor === '') {
                    continue;
                }

                $valor = strtoupper(trim((string) $valor));

                if (preg_match('/^(10|[0-9]|AC|ED|RA)$/', $valor)) {
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

    public function promedioFila(int $inscripcionId): string
    {
        $numeroMaterias = $this->obtenerNumeroMateriasAPromediar();

        if ($numeroMaterias <= 0) {
            return '—';
        }

        $suma = 0;

        foreach ($this->materias as $materia) {
            if ((int) ($materia['extra'] ?? 0) !== 0) {
                continue;
            }

            $asignacionId = (int) $materia['id'];
            $valor = $this->calificaciones[$inscripcionId][$asignacionId] ?? null;

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

    public function limpiarFiltros(): void
    {
        $this->nivel_id = null;
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->busqueda = '';

        $this->grados = [];
        $this->grupos = [];
        $this->periodos = [];
        $this->generaciones = [];
        $this->semestres = [];

        $this->limpiarTabla();
    }

    public function marcarCambio(): void
    {
        $this->hayCambios = true;
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

        if (!$this->filtrosListos()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Completa los filtros antes de guardar.',
                'position' => 'top-end',
            ]);
            return;
        }

        $periodoSeleccionado = $this->periodoSeleccionado;

        if (!$periodoSeleccionado || empty($periodoSeleccionado['ciclo_escolar_id'])) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se encontró un periodo válido para esta selección.',
                'position' => 'top-end',
            ]);
            return;
        }

        $grupoIds = $this->obtenerGrupoIdsSeleccionados();

        if (empty($grupoIds)) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'No se encontraron grupos con esa combinación.',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->resetErrorBag();

        foreach ($this->calificaciones as $inscripcionId => $materias) {
            foreach ($materias as $asignacionId => $valor) {
                $valorNormalizado = strtoupper(trim((string) $valor));
                $this->calificaciones[$inscripcionId][$asignacionId] = $valorNormalizado;

                if ($valorNormalizado === '') {
                    continue;
                }

                if (!preg_match('/^(10|[0-9]|AC|ED|RA)$/', $valorNormalizado)) {
                    $this->addError(
                        "calificaciones.$inscripcionId.$asignacionId",
                        'Solo se permite un número del 0 al 10 o AC, ED o RA.'
                    );
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Hay calificaciones con error. Corrige los valores antes de guardar.',
                'position' => 'top-end',
            ]);
            return;
        }

        DB::transaction(function () use ($periodoSeleccionado) {
            foreach ($this->inscripciones as $fila) {
                $inscripcionId = (int) $fila['inscripcion_id'];

                if ($inscripcionId <= 0) {
                    continue;
                }

                $inscripcion = Inscripcion::find($inscripcionId);

                if (!$inscripcion) {
                    continue;
                }

                foreach ($this->materias as $materia) {
                    $asignacionId = (int) $materia['id'];
                    $valor = $this->calificaciones[$inscripcionId][$asignacionId] ?? null;
                    $valor = strtoupper(trim((string) $valor));

                    if ($valor === '') {
                        CalificacionModel::query()
                            ->where('inscripcion_id', $inscripcionId)
                            ->where('asignacion_materia_id', $asignacionId)
                            ->where('nivel_id', $this->nivel_id)
                            ->where('grado_id', $inscripcion->grado_id)
                            ->where('grupo_id', $inscripcion->grupo_id)
                            ->where('generacion_id', $inscripcion->generacion_id)
                            ->where('semestre_id', $inscripcion->semestre_id)
                            ->where('periodo_id', $this->periodo_id)
                            ->delete();

                        continue;
                    }

                    CalificacionModel::updateOrCreate(
                        [
                            'inscripcion_id' => $inscripcionId,
                            'asignacion_materia_id' => $asignacionId,
                            'nivel_id' => $this->nivel_id,
                            'grado_id' => $inscripcion->grado_id,
                            'grupo_id' => $inscripcion->grupo_id,
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
