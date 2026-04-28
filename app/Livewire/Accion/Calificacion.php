<?php

namespace App\Livewire\Accion;

use App\Models\Calificacion as ModelsCalificacion;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Calificacion extends Component
{
    public string $slug_nivel = '';

    public $nivel_id = null;
    public $generacion_id = null;
    public $grado_id = null;
    public $grupo_id = null;
    public $semestre_id = null;

    public $parcial_bachillerato_id = null;
    public $periodo_basica_id = null;

    public $periodo_id = null;

    public string $busqueda = '';

    public array $inscripciones = [];
    public array $materias = [];
    public array $calificaciones = [];
    public array $calificacionesOriginales = [];
    public array $promedios = [];

    public bool $mostrarModalBitacora = false;

    public Collection $niveles;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public Collection $parciales;
    public Collection $periodosBasica;

    public ?array $periodoSeleccionado = null;

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->nivel_id = $nivel->id;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
        $this->periodosBasica = collect();

        $this->cargarCatalogos();
    }

    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel_id === 4;
    }

    public function cargarCatalogos(): void
    {
        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->grados = collect();
        $this->grupos = collect();

        $this->semestres = Semestre::query()
            ->orderBy('numero')
            ->get();

        $this->parciales = Parcial::query()
            ->orderBy('parcial')
            ->get();

        $this->periodosBasica = PeriodosBasica::query()
            ->orderBy('periodo')
            ->get();
    }

    public function updatedGeneracionId(): void
    {
        $this->reset([
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'periodo_id',
            'periodoSeleccionado',
            'busqueda',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        $this->cargarGrados();
    }

    public function updatedGradoId(): void
    {
        $this->reset([
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'periodo_id',
            'periodoSeleccionado',
            'busqueda',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->reset([
            'grupo_id',
            'parcial_bachillerato_id',
            'periodo_id',
            'periodoSeleccionado',
            'busqueda',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        $this->cargarGrupos();
    }

    public function updatedGrupoId(): void
    {
        $this->reset([
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'periodo_id',
            'periodoSeleccionado',
            'busqueda',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        if ($this->esBachillerato) {
            return;
        }

        $this->cargarDatos();
    }

    public function updatedParcialBachilleratoId(): void
    {
        $this->reset([
            'periodo_id',
            'periodoSeleccionado',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        $this->cargarDatos();
    }

    public function updatedPeriodoBasicaId(): void
    {
        $this->reset([
            'periodo_id',
            'periodoSeleccionado',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        $this->cargarDatos();
    }

    public function updatedBusqueda(): void
    {
        $this->cargarDatos();
    }

    private function cargarGrados(): void
    {
        if (blank($this->generacion_id)) {
            $this->grados = collect();
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get();
    }

    private function cargarGrupos(): void
    {
        if (blank($this->generacion_id) || blank($this->grado_id)) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when($this->esBachillerato, function ($query) {
                $query->where('semestre_id', $this->semestre_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'periodo_id',
            'periodoSeleccionado',
            'busqueda',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
    }

    public function cargarDatos(): void
    {
        $this->reset([
            'periodo_id',
            'periodoSeleccionado',
            'inscripciones',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'promedios',
        ]);

        if ($this->esBachillerato) {
            if (
                blank($this->generacion_id) ||
                blank($this->grado_id) ||
                blank($this->semestre_id) ||
                blank($this->grupo_id) ||
                blank($this->parcial_bachillerato_id)
            ) {
                return;
            }
        } else {
            if (
                blank($this->generacion_id) ||
                blank($this->grado_id) ||
                blank($this->grupo_id) ||
                blank($this->periodo_basica_id)
            ) {
                return;
            }
        }

        $this->cargarPeriodoSeleccionado();

        if (blank($this->periodo_id)) {
            return;
        }

        $this->cargarInscripciones();
        $this->cargarMaterias();
        $this->cargarCalificaciones();
        $this->calcularPromedios();
    }

    public function cargarPeriodoSeleccionado(): void
    {
        $query = Periodos::query()
            ->with([
                'cicloEscolar',
                'mesesBasica',
                'periodoBasica',
                'mesesBachillerato',
                'parcialBachillerato',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id)
                ->where('parcial_bachillerato_id', $this->parcial_bachillerato_id);
        } else {
            $query->where('periodo_basica_id', $this->periodo_basica_id);
        }

        $periodo = $query->latest('id')->first();

        if (!$periodo) {
            $this->periodo_id = null;
            $this->periodoSeleccionado = null;
            return;
        }

        $this->periodo_id = $periodo->id;

        $this->periodoSeleccionado = [
            'id' => $periodo->id,
            'ciclo_escolar' => trim(($periodo->cicloEscolar->inicio_anio ?? '') . ' - ' . ($periodo->cicloEscolar->fin_anio ?? '')),
            'periodo' => $this->esBachillerato
                ? ($periodo->mesesBachillerato->meses ?? 'Sin periodo')
                : ($periodo->mesesBasica->meses ?? 'Sin periodo'),
            'parcial' => $this->esBachillerato
                ? ($periodo->parcialBachillerato->descripcion ?? 'Sin parcial')
                : ($periodo->periodoBasica->descripcion ?? 'Sin periodo'),
            'fecha_inicio' => $periodo->fecha_inicio,
            'fecha_fin' => $periodo->fecha_fin,
        ];
    }

    private function cargarInscripciones(): void
    {
        $query = Inscripcion::query()
            ->with(['alumno'])
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where(function ($q) {
                $q->where('activo', true)
                    ->orWhere('activo', 1);
            });

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id);
        }

        if (filled($this->busqueda)) {
            $busqueda = '%' . trim($this->busqueda) . '%';

            $query->where(function ($q) use ($busqueda) {
                $q->where('matricula', 'like', $busqueda)
                    ->orWhereHas('alumno', function ($alumno) use ($busqueda) {
                        $alumno->where('nombre', 'like', $busqueda)
                            ->orWhere('apellido_paterno', 'like', $busqueda)
                            ->orWhere('apellido_materno', 'like', $busqueda)
                            ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", [$busqueda])
                            ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombre) LIKE ?", [$busqueda]);
                    });
            });
        }

        $this->inscripciones = $query
            ->get()
            ->sortBy([
                fn($a, $b) => strcmp($a->alumno->apellido_paterno ?? '', $b->alumno->apellido_paterno ?? ''),
                fn($a, $b) => strcmp($a->alumno->apellido_materno ?? '', $b->alumno->apellido_materno ?? ''),
                fn($a, $b) => strcmp($a->alumno->nombre ?? '', $b->alumno->nombre ?? ''),
            ])
            ->map(function ($inscripcion) {
                return [
                    'inscripcion_id' => $inscripcion->id,
                    'matricula' => $inscripcion->matricula ?? 'SIN MATRÍCULA',
                    'alumno' => trim(
                        ($inscripcion->alumno->apellido_paterno ?? '') . ' ' .
                            ($inscripcion->alumno->apellido_materno ?? '') . ' ' .
                            ($inscripcion->alumno->nombre ?? '')
                    ),
                ];
            })
            ->values()
            ->toArray();
    }

    private function cargarMaterias(): void
    {
        $query = AsignacionMateria::query()
            ->with(['profesor'])
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where(function ($q) {
                $q->where('calificable', true)
                    ->orWhere('calificable', 1)
                    ->orWhere('calificable', 'si');
            });

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id);
        }

        $this->materias = $query
            ->orderBy('materia')
            ->get()
            ->map(function ($materia) {
                return [
                    'id' => $materia->id,
                    'materia' => $materia->materia ?? $materia->nombre ?? 'Materia',
                    'profesor' => $materia->profesor
                        ? trim(($materia->profesor->nombre ?? '') . ' ' . ($materia->profesor->apellidos ?? ''))
                        : 'SIN PROFESOR ASIGNADO',
                ];
            })
            ->values()
            ->toArray();
    }

    private function cargarCalificaciones(): void
    {
        if (empty($this->inscripciones) || empty($this->materias) || blank($this->periodo_id)) {
            return;
        }

        $inscripcionIds = collect($this->inscripciones)
            ->pluck('inscripcion_id')
            ->values()
            ->all();

        $materiaIds = collect($this->materias)
            ->pluck('id')
            ->values()
            ->all();

        $calificacionesGuardadas = ModelsCalificacion::query()
            ->where('periodo_id', $this->periodo_id)
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->whereIn('asignacion_materia_id', $materiaIds)
            ->get();

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $materia) {
                $materiaId = (int) $materia['id'];

                $calificacion = $calificacionesGuardadas
                    ->where('inscripcion_id', $inscripcionId)
                    ->where('asignacion_materia_id', $materiaId)
                    ->first();

                $valor = $calificacion?->calificacion;

                $this->calificaciones[$inscripcionId][$materiaId] = $valor !== null ? (string) $valor : '';
                $this->calificacionesOriginales[$inscripcionId][$materiaId] = $valor !== null ? (string) $valor : '';
            }
        }
    }

    public function guardarCalificaciones(): void
    {
        if (!$this->puedeGuardar) {
            $this->addError('calificaciones', 'Selecciona todos los filtros requeridos antes de guardar.');
            return;
        }

        $this->validate($this->reglasCalificaciones(), $this->mensajesCalificaciones());

        foreach ($this->calificaciones as $inscripcionId => $materias) {
            foreach ($materias as $materiaId => $valor) {
                if ($valor === '' || $valor === null) {
                    ModelsCalificacion::query()
                        ->where('periodo_id', $this->periodo_id)
                        ->where('inscripcion_id', $inscripcionId)
                        ->where('asignacion_materia_id', $materiaId)
                        ->delete();

                    continue;
                }

                ModelsCalificacion::updateOrCreate(
                    [
                        'periodo_id' => $this->periodo_id,
                        'inscripcion_id' => $inscripcionId,
                        'asignacion_materia_id' => $materiaId,
                    ],
                    [
                        'calificacion' => $valor,
                        'fecha_captura' => now(),
                    ]
                );
            }
        }

        $this->calificacionesOriginales = $this->calificaciones;

        $this->calcularPromedios();

        $this->dispatch('swal', [
            'title' => '¡Calificaciones guardadas correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function reglasCalificaciones(): array
    {
        $reglas = [];

        foreach ($this->calificaciones as $inscripcionId => $materias) {
            foreach ($materias as $materiaId => $valor) {
                $reglas["calificaciones.$inscripcionId.$materiaId"] = [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:10',
                ];
            }
        }

        return $reglas;
    }

    private function mensajesCalificaciones(): array
    {
        return [
            'calificaciones.*.*.numeric' => 'Debe ser número.',
            'calificaciones.*.*.min' => 'Mínimo 0.',
            'calificaciones.*.*.max' => 'Máximo 10.',
        ];
    }

    public function calcularPromedios(): void
    {
        $this->promedios = [];

        foreach ($this->calificaciones as $inscripcionId => $materias) {
            $valores = collect($materias)
                ->filter(fn($valor) => $valor !== '' && $valor !== null && is_numeric($valor))
                ->map(fn($valor) => (float) $valor)
                ->values();

            if ($valores->isEmpty()) {
                $this->promedios[$inscripcionId] = '—';
                continue;
            }

            $promedio = $valores->avg();

            $this->promedios[$inscripcionId] = $this->truncarDecimal($promedio, 1);
        }
    }

    private function truncarDecimal(float $numero, int $decimales = 1): string
    {
        $factor = pow(10, $decimales);

        return number_format(floor($numero * $factor) / $factor, $decimales);
    }

    public function claseInputCalificacion($inscripcionId, $materiaId): string
    {
        $base = 'w-full rounded-xl border px-2 py-1.5 text-center text-sm font-semibold outline-none transition focus:ring-2';

        $valor = $this->calificaciones[$inscripcionId][$materiaId] ?? '';

        if ($valor === '' || $valor === null) {
            return $base . ' border-neutral-200 bg-white text-neutral-800 focus:ring-sky-300 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100';
        }

        if (!is_numeric($valor) || (float) $valor < 0 || (float) $valor > 10) {
            return $base . ' border-red-300 bg-red-50 text-red-700 focus:ring-red-300 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300';
        }

        return $base . ' border-emerald-300 bg-emerald-50 text-emerald-700 focus:ring-emerald-300 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    public function getPuedeGuardarProperty(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->semestre_id)
                && filled($this->grupo_id)
                && filled($this->parcial_bachillerato_id)
                && filled($this->periodo_id)
                && count($this->inscripciones) > 0
                && count($this->materias) > 0;
        }

        return filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && filled($this->periodo_basica_id)
            && filled($this->periodo_id)
            && count($this->inscripciones) > 0
            && count($this->materias) > 0;
    }

    public function getPuedeExportarPdfProperty(): bool
    {
        return $this->puedeGuardar;
    }

    public function getHayCambiosProperty(): bool
    {
        return $this->calificaciones !== $this->calificacionesOriginales;
    }

    public function getMensajeCambiosProperty(): string
    {
        return $this->hayCambios
            ? 'Cambios pendientes'
            : 'Sin cambios pendientes';
    }

    public function getClaseEstadoCambiosProperty(): string
    {
        return $this->hayCambios
            ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900'
            : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900';
    }

    public function getClaseGuardarProperty(): string
    {
        if (!$this->puedeGuardar) {
            return 'inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-neutral-300 px-5 py-3 text-sm font-semibold text-neutral-500 opacity-70 dark:bg-neutral-800 dark:text-neutral-500';
        }

        return 'inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow transition hover:opacity-95';
    }

    public function getCeldasCapturadasProperty(): int
    {
        return collect($this->calificaciones)
            ->flatMap(fn($materias) => $materias)
            ->filter(fn($valor) => $valor !== '' && $valor !== null)
            ->count();
    }

    public function getTotalCeldasProperty(): int
    {
        return count($this->inscripciones) * count($this->materias);
    }

    public function getPorcentajeCapturaProperty(): int
    {
        if ($this->totalCeldas <= 0) {
            return 0;
        }

        return (int) round(($this->celdasCapturadas / $this->totalCeldas) * 100);
    }

    public function getNombrePeriodoProperty(): string
    {
        if (!$this->periodoSeleccionado) {
            return 'Sin periodo';
        }

        return trim(($this->periodoSeleccionado['periodo'] ?? 'Sin periodo') . ' - ' . ($this->periodoSeleccionado['parcial'] ?? ''));
    }

    public function getEstadoPeriodoProperty(): string
    {
        if (!$this->periodoSeleccionado) {
            return 'Sin periodo';
        }

        if (empty($this->periodoSeleccionado['fecha_inicio']) || empty($this->periodoSeleccionado['fecha_fin'])) {
            return 'Sin fechas';
        }

        $hoy = Carbon::today();
        $inicio = Carbon::parse($this->periodoSeleccionado['fecha_inicio']);
        $fin = Carbon::parse($this->periodoSeleccionado['fecha_fin']);

        if ($hoy->lt($inicio)) {
            return 'Próximo';
        }

        if ($hoy->gt($fin)) {
            return 'Finalizado';
        }

        return 'Vigente';
    }

    public function getClaseEstadoPeriodoProperty(): string
    {
        return match ($this->estadoPeriodo) {
            'Vigente' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900',
            'Finalizado' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900',
            'Próximo' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900',
            default => 'bg-neutral-50 text-neutral-700 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:text-neutral-300 dark:ring-neutral-700',
        };
    }

    public function getPorcentajePeriodoProperty(): int
    {
        if (!$this->periodoSeleccionado) {
            return 0;
        }

        if (empty($this->periodoSeleccionado['fecha_inicio']) || empty($this->periodoSeleccionado['fecha_fin'])) {
            return 0;
        }

        $inicio = Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($this->periodoSeleccionado['fecha_fin'])->endOfDay();
        $hoy = Carbon::today();

        if ($hoy->lte($inicio)) {
            return 0;
        }

        if ($hoy->gte($fin)) {
            return 100;
        }

        $totalDias = max(1, $inicio->diffInDays($fin));
        $diasTranscurridos = $inicio->diffInDays($hoy);

        return min(100, max(0, (int) round(($diasTranscurridos / $totalDias) * 100)));
    }

    public function getMostrarBotonBitacoraProperty(): bool
    {
        return filled($this->periodo_id)
            && filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id);
    }

    public function abrirModalBitacora(): void
    {
        $this->mostrarModalBitacora = true;
    }

    public function cerrarModalBitacora(): void
    {
        $this->mostrarModalBitacora = false;
    }

    public function exportarCalificaciones()
    {
        $this->dispatch('swal', [
            'title' => 'Exportación pendiente de conectar',
            'icon' => 'info',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('livewire.accion.calificacion', [
            'niveles' => $this->niveles,
            'generaciones' => $this->generaciones,
            'grados' => $this->grados,
            'grupos' => $this->grupos,
            'semestres' => $this->semestres,
            'parciales' => $this->parciales,
            'periodosBasica' => $this->periodosBasica,
            'hayCambios' => $this->hayCambios,
        ]);
    }
}
