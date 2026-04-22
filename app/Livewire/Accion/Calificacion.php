<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as CalificacionModel;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Exports\CalificacionExport;
use Maatwebsite\Excel\Facades\Excel;

class Calificacion extends Component
{
    /* =========================
     * LISTAS
     * ========================= */
    public $niveles = [];
    public $grados = [];
    public $grupos = [];
    public $periodos = [];
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
    public array $celdasModificadas = [];
    public array $promedios = [];

    public ?Nivel $nivel = null;
    public string $slug_nivel = '';

    public bool $hayCambios = false;

    /* =========================
     * MODAL BITÁCORA
     * ========================= */
    public bool $mostrarModalBitacora = false;

    protected array $messages = [
        'calificaciones.*.*.max' => 'Solo se permiten 2 caracteres.',
        'calificaciones.*.*.regex' => 'Solo se permite un número del 0 al 10 o AC, ED o RA.',
    ];

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->sincronizarNivelDesdeSlug();

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

    private function sincronizarNivelDesdeSlug(): void
    {
        $this->nivel = null;
        $this->nivel_id = null;

        if (empty($this->slug_nivel)) {
            return;
        }

        $this->nivel = $this->niveles->firstWhere('slug', $this->slug_nivel);

        if ($this->nivel) {
            $this->nivel_id = (int) $this->nivel->id;
        }
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
        $base = 'inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 text-white px-6 py-3 text-sm font-semibold shadow transition';

        return $this->puedeGuardar
            ? $base . ' hover:opacity-95'
            : $base . ' opacity-60 cursor-not-allowed';
    }

    public function getMensajeCambiosProperty(): string
    {
        if (!$this->hayCambios) {
            return 'Sin cambios pendientes';
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return 'Hay cambios pendientes con errores';
        }

        return 'Hay cambios pendientes por guardar';
    }

    public function getClaseEstadoCambiosProperty(): string
    {
        if (!$this->hayCambios) {
            return 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300';
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return 'border border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300';
        }

        return 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300';
    }

    public function getMostrarBotonBitacoraProperty(): bool
    {
        return !empty($this->nivel_id);
    }

    /* =========================
     * MODAL BITÁCORA
     * ========================= */
    public function abrirModalBitacora(): void
    {
        if (!$this->nivel_id) {
            return;
        }

        $this->mostrarModalBitacora = true;
    }

    public function cerrarModalBitacora(): void
    {
        $this->mostrarModalBitacora = false;
    }

    /* =========================
     * CARGA INICIAL
     * ========================= */
    private function cargarFiltrosInicialesPorNivel(): void
    {
        $this->grados = [];
        $this->grupos = [];
        $this->semestres = [];
        $this->periodos = [];

        if (!$this->nivel_id) {
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    public function updatedNivelId(): void
    {
        // Aunque ya no exista el select de nivel, este método se deja
        // por si nivel_id cambia internamente.
        $this->resetearFiltrosDependientes(false);

        if (!$this->nivel_id) {
            return;
        }

        $this->nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);
        $this->cargarFiltrosInicialesPorNivel();
    }

    public function updatedGradoId(): void
    {
        $this->grado_id = $this->grado_id ? (int) $this->grado_id : null;

        $this->grupo_id = null;
        $this->generacion_id = null;
        $this->periodo_id = null;
        $this->grupos = [];
        $this->periodos = [];

        if ($this->esBachillerato) {
            $this->semestre_id = null;
            $this->semestres = [];
        }

        $this->limpiarTabla();

        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        if ($this->esBachillerato) {
            $this->cargarSemestresDesdeGrupos();
            return;
        }

        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        if (!$this->esBachillerato) {
            return;
        }

        $this->semestre_id = $this->semestre_id ? (int) $this->semestre_id : null;

        $this->grupo_id = null;
        $this->generacion_id = null;
        $this->periodo_id = null;
        $this->grupos = [];
        $this->periodos = [];

        $this->limpiarTabla();

        if (!$this->nivel_id || !$this->grado_id || !$this->semestre_id) {
            return;
        }

        $this->cargarGrupos();
    }

    public function updatedGrupoId(): void
    {
        $this->grupo_id = $this->grupo_id ? (int) $this->grupo_id : null;

        $this->periodo_id = null;
        $this->periodos = [];

        $this->limpiarTabla();

        if (!$this->grupo_id) {
            $this->generacion_id = null;
            return;
        }

        if (!$this->grupoPerteneceAFiltros()) {
            $this->grupo_id = null;
            $this->generacion_id = null;

            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'El grupo no coincide con el grado o semestre seleccionado.',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->resolverGeneracionDesdeGrupo();
        $this->resolverPeriodoAutomatico();

        $this->cargarDatosSiListo();
    }

    public function updatedPeriodoId(): void
    {
        return;
    }

    public function updatedBusqueda(): void
    {
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->promedios = [];
        $this->hayCambios = false;

        $this->limpiarCeldasModificadas();
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

        $partes = explode('.', $propiedad);

        $inscripcionId = isset($partes[1]) ? (int) $partes[1] : 0;
        $asignacionId = isset($partes[2]) ? (int) $partes[2] : 0;

        if ($inscripcionId > 0 && $asignacionId > 0) {
            $this->marcarCeldaComoModificada($inscripcionId, $asignacionId);
        }

        if ($valor === '' || $valor === null) {
            $this->resetValidation($propiedad);
            $this->recalcularPromedios();
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

        $this->recalcularPromedios();
    }

    /* =========================
     * APOYO DE FILTROS
     * ========================= */
    private function resetearFiltrosDependientes(bool $mantenerNivel = true): void
    {
        $nivelActual = $this->nivel_id;
        $nivelModeloActual = $this->nivel;

        $this->grado_id = null;
        $this->grupo_id = null;
        $this->periodo_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->busqueda = '';

        $this->grados = [];
        $this->grupos = [];
        $this->periodos = [];
        $this->semestres = [];

        if (!$mantenerNivel) {
            $this->nivel_id = null;
            $this->nivel = null;
        } else {
            $this->nivel_id = $nivelActual;
            $this->nivel = $nivelModeloActual;
        }

        $this->cerrarModalBitacora();
        $this->limpiarTabla();
    }

    private function limpiarTabla(): void
    {
        $this->materias = [];
        $this->inscripciones = [];
        $this->calificaciones = [];
        $this->promedios = [];
        $this->hayCambios = false;

        $this->limpiarCeldasModificadas();
        $this->resetErrorBag();
    }

    private function grupoPerteneceAFiltros(): bool
    {
        if (!$this->grupo_id || !$this->nivel_id || !$this->grado_id) {
            return false;
        }

        $query = Grupo::query()
            ->where('id', $this->grupo_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id);

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id);
        }

        return $query->exists();
    }

    private function cargarSemestresDesdeGrupos(): void
    {
        $this->semestres = [];

        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $idsSemestres = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id')
            ->filter()
            ->values();

        if ($idsSemestres->isEmpty()) {
            return;
        }

        $this->semestres = Semestre::query()
            ->whereIn('id', $idsSemestres)
            ->orderBy('numero')
            ->get();
    }

    private function cargarGrupos(): void
    {
        $this->grupos = [];

        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $query = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id);

        if ($this->esBachillerato) {
            if (!$this->semestre_id) {
                return;
            }

            $query->where('semestre_id', $this->semestre_id);
        }

        $this->grupos = $query
            ->orderBy('nombre')
            ->get();
    }

    private function resolverGeneracionDesdeGrupo(): void
    {
        $this->generacion_id = null;

        if (!$this->grupo_id) {
            return;
        }

        $grupo = Grupo::query()
            ->where('id', $this->grupo_id)
            ->first(['id', 'generacion_id']);

        $this->generacion_id = $grupo?->generacion_id ? (int) $grupo->generacion_id : null;
    }

    private function obtenerGrupoIdsSeleccionados(): array
    {
        if (!$this->nivel_id || !$this->grupo_id) {
            return [];
        }

        return [(int) $this->grupo_id];
    }

    private function resolverPeriodoAutomatico(): void
    {
        $this->periodo_id = null;
        $this->periodos = [];

        if (!$this->nivel_id || !$this->grupo_id) {
            return;
        }

        $hoy = now()->toDateString();

        $query = Periodos::query()
            ->with('cicloEscolar')
            ->where('nivel_id', $this->nivel_id);

        if ($this->esBachillerato) {
            if (!$this->generacion_id || !$this->semestre_id) {
                return;
            }

            $query->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id);
        } else {
            $query->whereNull('generacion_id')
                ->whereNull('semestre_id');
        }

        $periodo = $query
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
                $this->grado_id &&
                $this->semestre_id &&
                $this->grupo_id &&
                $this->periodo_id &&
                $this->generacion_id
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

    /* =========================
     * CARGA DE DATOS
     * ========================= */
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
            $query->where('grado_id', $this->grado_id)
                ->whereNull('semestre');
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
                'extra' => (int) ($a->extra ?? 0),
            ];
        })->values()->toArray();
    }

    private function cargarInscripciones(): void
    {
        $busqueda = trim($this->busqueda);
        $grupoIds = $this->obtenerGrupoIdsSeleccionados();

        if (empty($grupoIds)) {
            $this->inscripciones = [];
            return;
        }

        $query = Inscripcion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->whereIn('grupo_id', $grupoIds);

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id)
                ->where('generacion_id', $this->generacion_id);
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

        $this->recalcularPromedios();
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
            $query->where('grado_id', $this->grado_id)
                ->where('generacion_id', $this->generacion_id)
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

        $this->limpiarCeldasModificadas();
        $this->recalcularPromedios();
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
                ->where('grado_id', $this->grado_id)
                ->whereIn('grupo_id', $grupoIds)
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

    /* =========================
     * APOYO VISUAL
     * ========================= */
    private function marcarCeldaComoModificada(int $inscripcionId, int $asignacionId): void
    {
        $this->celdasModificadas[$inscripcionId][$asignacionId] = true;
        $this->hayCambios = true;
    }

    private function limpiarCeldasModificadas(): void
    {
        $this->celdasModificadas = [];
    }

    public function celdaTieneError(int $inscripcionId, int $asignacionId): bool
    {
        return $this->getErrorBag()->has("calificaciones.$inscripcionId.$asignacionId");
    }

    public function celdaFueModificada(int $inscripcionId, int $asignacionId): bool
    {
        return (bool) data_get($this->celdasModificadas, $inscripcionId . '.' . $asignacionId, false);
    }

    public function celdaTieneValorValido(int $inscripcionId, int $asignacionId): bool
    {
        $valor = data_get($this->calificaciones, $inscripcionId . '.' . $asignacionId);

        if ($valor === null || $valor === '') {
            return false;
        }

        $valor = strtoupper(trim((string) $valor));

        return (bool) preg_match('/^(10|[0-9]|AC|ED|RA)$/', $valor);
    }

    public function claseInputCalificacion(int $inscripcionId, int $asignacionId): string
    {
        $base = 'w-24 rounded-xl px-3 py-1.5 text-center text-sm uppercase focus:outline-none focus:ring-2 transition';

        if ($this->celdaTieneError($inscripcionId, $asignacionId)) {
            return $base . ' border border-red-300 bg-red-50 text-red-700 focus:ring-red-200 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200';
        }

        if ($this->celdaFueModificada($inscripcionId, $asignacionId)) {
            return $base . ' border border-amber-300 bg-amber-50 text-amber-800 focus:ring-amber-200 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200';
        }

        if ($this->celdaTieneValorValido($inscripcionId, $asignacionId)) {
            return $base . ' border border-emerald-300 bg-emerald-50 text-emerald-800 focus:ring-emerald-200 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200';
        }

        return $base . ' border border-neutral-200 bg-white text-neutral-900 focus:ring-sky-300 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100';
    }

    private function recalcularPromedios(): void
    {
        $this->promedios = [];

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];
            $this->promedios[$inscripcionId] = $this->promedioFila($inscripcionId);
        }
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

    /* =========================
     * BITÁCORA
     * ========================= */
    private function registrarBitacoraCalificacion(
        int $inscripcionId,
        int $asignacionMateriaId,
        ?string $valorAnterior,
        ?string $valorNuevo,
        string $accion,
        ?int $cicloEscolarId
    ): void {
        BitacoraCalificacion::create([
            'user_id' => auth()->id(),
            'inscripcion_id' => $inscripcionId,
            'asignacion_materia_id' => $asignacionMateriaId,
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->semestre_id,
            'periodo_id' => $this->periodo_id,
            'ciclo_escolar_id' => $cicloEscolarId,
            'calificacion_anterior' => $valorAnterior,
            'calificacion_nueva' => $valorNuevo,
            'accion' => $accion,
            'comentario' => null,
        ]);
    }

    /* =========================
     * ACCIONES
     * ========================= */
    public function limpiarFiltros(): void
    {
        // El nivel ya viene desde la pestaña activa, así que se conserva.
        $this->sincronizarNivelDesdeSlug();
        $this->resetearFiltrosDependientes(true);

        if ($this->nivel_id) {
            $this->cargarFiltrosInicialesPorNivel();
        }
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

        if (!$this->grupoPerteneceAFiltros()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'El grupo ya no coincide con la selección actual.',
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
                'title' => 'No se encontró el grupo seleccionado.',
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

        $totalCambios = 0;

        DB::transaction(function () use ($periodoSeleccionado, &$totalCambios) {
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
                    $valorCapturado = $this->calificaciones[$inscripcionId][$asignacionId] ?? null;
                    $valorNuevo = strtoupper(trim((string) $valorCapturado));

                    $registroExistente = CalificacionModel::query()
                        ->where('inscripcion_id', $inscripcionId)
                        ->where('asignacion_materia_id', $asignacionId)
                        ->where('nivel_id', $this->nivel_id)
                        ->where('grado_id', $inscripcion->grado_id)
                        ->where('grupo_id', $inscripcion->grupo_id)
                        ->where('generacion_id', $inscripcion->generacion_id)
                        ->where('semestre_id', $inscripcion->semestre_id)
                        ->where('periodo_id', $this->periodo_id)
                        ->first();

                    $valorAnterior = $registroExistente
                        ? strtoupper(trim((string) $registroExistente->calificacion))
                        : null;

                    if ($valorNuevo === '') {
                        if ($registroExistente) {
                            $registroExistente->delete();

                            $this->registrarBitacoraCalificacion(
                                $inscripcionId,
                                $asignacionId,
                                $valorAnterior,
                                null,
                                'eliminar',
                                $periodoSeleccionado['ciclo_escolar_id']
                            );

                            $totalCambios++;
                        }

                        continue;
                    }

                    if (!$registroExistente) {
                        CalificacionModel::create([
                            'inscripcion_id' => $inscripcionId,
                            'asignacion_materia_id' => $asignacionId,
                            'nivel_id' => $this->nivel_id,
                            'grado_id' => $inscripcion->grado_id,
                            'grupo_id' => $inscripcion->grupo_id,
                            'generacion_id' => $inscripcion->generacion_id,
                            'semestre_id' => $inscripcion->semestre_id,
                            'periodo_id' => $this->periodo_id,
                            'ciclo_escolar_id' => $periodoSeleccionado['ciclo_escolar_id'],
                            'calificacion' => $valorNuevo,
                        ]);

                        $this->registrarBitacoraCalificacion(
                            $inscripcionId,
                            $asignacionId,
                            null,
                            $valorNuevo,
                            'crear',
                            $periodoSeleccionado['ciclo_escolar_id']
                        );

                        $totalCambios++;
                        continue;
                    }

                    if ($valorAnterior !== $valorNuevo) {
                        $registroExistente->update([
                            'calificacion' => $valorNuevo,
                        ]);

                        $this->registrarBitacoraCalificacion(
                            $inscripcionId,
                            $asignacionId,
                            $valorAnterior,
                            $valorNuevo,
                            'editar',
                            $periodoSeleccionado['ciclo_escolar_id']
                        );

                        $totalCambios++;
                    }
                }
            }
        });

        $this->resetErrorBag();
        $this->hayCambios = false;
        $this->limpiarCeldasModificadas();
        $this->recalcularPromedios();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $totalCambios > 0
                ? 'Calificaciones guardadas. Cambios registrados en bitácora.'
                : 'No hubo cambios reales que guardar.',
            'position' => 'top-end',
        ]);
    }

    public function exportarCalificaciones()
    {
        if (!$this->filtrosListos()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Completa los filtros antes de exportar.',
                'position' => 'top-end',
            ]);
            return;
        }

        $nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);
        $grado = collect($this->grados)->firstWhere('id', $this->grado_id);
        $grupo = collect($this->grupos)->firstWhere('id', $this->grupo_id);
        $semestre = collect($this->semestres)->firstWhere('id', $this->semestre_id);

        $nombreArchivo = 'CALIFICACIONES_' .
            mb_strtoupper((string) ($nivel?->nombre ?? 'NIVEL'), 'UTF-8') . '_' .
            'GRADO_' . ($grado?->nombre ?? 'GRADO') . '_' .
            'GRUPO_' . ($grupo?->nombre ?? 'GRUPO') .
            ($this->esBachillerato && $semestre ? '_SEMESTRE_' . $semestre->numero : '') .
            '_PERIODO_' . ($this->periodo_id ?? 'PERIODO');

        $nombreArchivo = str($nombreArchivo)
            ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
            ->replace('  ', ' ')
            ->trim()
            ->toString();

        return Excel::download(
            new CalificacionExport(
                nivel_id: $this->nivel_id,
                grado_id: $this->grado_id,
                grupo_id: $this->grupo_id,
                periodo_id: $this->periodo_id,
                semestre_id: $this->semestre_id,
                generacion_id: $this->generacion_id,
                esBachillerato: $this->esBachillerato,
                busqueda: $this->busqueda,
            ),
            $nombreArchivo . '.xlsx'
        );
    }

    public function exportarPdfCalificaciones()
    {
        if (!$this->filtrosListos()) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Completa los filtros antes de exportar.',
                'position' => 'top-end',
            ]);
            return;
        }

        $materias = $this->obtenerMateriasParaExportacionPdf();
        $inscripciones = $this->obtenerInscripcionesParaExportacionPdf();
        $calificaciones = $this->obtenerCalificacionesParaExportacionPdf($inscripciones, $materias);

        $promedios = [];
        foreach ($inscripciones as $fila) {
            $promedios[$fila['inscripcion_id']] = $this->calcularPromedioParaExportacionPdf(
                $fila['inscripcion_id'],
                $materias,
                $calificaciones
            );
        }

        $nivelNombre = collect($this->niveles)->firstWhere('id', $this->nivel_id)?->nombre ?? '—';
        $gradoNombre = collect($this->grados)->firstWhere('id', $this->grado_id)?->nombre ?? '—';
        $grupoNombre = collect($this->grupos)->firstWhere('id', $this->grupo_id)?->nombre ?? '—';
        $semestreNombre = collect($this->semestres)->firstWhere('id', $this->semestre_id)?->numero ?? '—';

        $periodoSeleccionado = $this->periodoSeleccionado;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calificaciones_pdf', [
            'titulo' => 'Reporte de calificaciones',
            'nivelNombre' => $nivelNombre,
            'gradoNombre' => $gradoNombre,
            'grupoNombre' => $grupoNombre,
            'semestreNombre' => $semestreNombre,
            'esBachillerato' => $this->esBachillerato,
            'busqueda' => $this->busqueda,
            'periodoSeleccionado' => $periodoSeleccionado,
            'materias' => $materias,
            'inscripciones' => $inscripciones,
            'calificaciones' => $calificaciones,
            'promedios' => $promedios,
        ])->setPaper('a4', 'landscape');

        $nombre = 'calificaciones_pdf';

        if ($this->nivel_id) {
            $nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);
            $nombre .= '_' . str($nivel?->slug ?? 'nivel')->slug('_');
        }

        if ($this->grado_id) {
            $nombre .= '_grado_' . $this->grado_id;
        }

        if ($this->grupo_id) {
            $nombre .= '_grupo_' . $this->grupo_id;
        }

        if ($this->periodo_id) {
            $nombre .= '_periodo_' . $this->periodo_id;
        }

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $nombre . '.pdf'
        );
    }

    public function getPuedeExportarPdfProperty(): bool
    {
        return $this->filtrosListos();
    }

    public function render()
    {
        return view('livewire.accion.calificacion', [
            'niveles' => $this->niveles,
            'grados' => $this->grados,
            'grupos' => $this->grupos,
            'periodos' => $this->periodos,
            'semestres' => $this->semestres,
            'promedios' => $this->promedios,
        ]);
    }
}
