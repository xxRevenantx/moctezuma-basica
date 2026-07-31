<?php

namespace App\Livewire\Accion;

use App\Exports\AlumnosNoVigentesExport;
use App\Models\CambioAcademico;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\AlumnosNoVigentesService;
use App\Services\GestionAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class AlumnosNoVigentes extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?string $slug_grado = null;
    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public ?int $ciclo_escolar_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public string $categoria = 'todos';
    public string $search = '';
    public int $perPage = 20;

    public array $selected = [];
    public bool $selectPage = false;
    public string $motivo_activacion = 'Documentación validada y confirmación formal de inscripción.';
    public string $seguimiento_administrativo = '';

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel, ?string $slug_grado = null): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->slug_grado = $slug_grado;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
        $this->semestres = collect();
        $this->grupos = collect();

        $categoria = request('categoria');
        if (is_string($categoria) && in_array($categoria, AlumnosNoVigentesService::CATEGORIAS, true)) {
            $this->categoria = $categoria;
        }

        $alumno = request()->integer('alumno');
        if ($alumno > 0) {
            $this->search = (string) Inscripcion::withTrashed()->whereKey($alumno)->value('matricula');
        }
    }

    public function esBachillerato(): bool
    {
        return str_contains(
            mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')),
            'bachillerato'
        );
    }

    public function getEsCicloActualProperty(): bool
    {
        return (bool) $this->ciclosEscolares
            ->firstWhere('id', (int) $this->ciclo_escolar_id)?->es_actual;
    }

    public function updatedCicloEscolarId(): void
    {
        $this->reset(['generacion_id', 'grado_id', 'semestre_id', 'grupo_id']);
        $this->generaciones = $this->cargarGeneraciones();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->filtrosCambiaron();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos();
        $this->filtrosCambiaron();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = $this->grado_id
            ? Semestre::query()->where('grado_id', $this->grado_id)->orderBy('numero')->get()
            : collect();
        $this->grupos = $this->esBachillerato() ? collect() : $this->cargarGrupos();
        $this->filtrosCambiaron();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos();
        $this->filtrosCambiaron();
    }

    public function updatedGrupoId(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedCategoria(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedSearch(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedPerPage(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selected = [];
            return;
        }

        $this->selected = $this->query()
            ->forPage($this->getPage(), $this->perPage)
            ->pluck('inscripciones.id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function seleccionarCategoria(string $categoria): void
    {
        abort_unless(in_array($categoria, AlumnosNoVigentesService::CATEGORIAS, true), 404);
        $this->categoria = $categoria;
        $this->filtrosCambiaron();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['generacion_id', 'grado_id', 'semestre_id', 'grupo_id', 'search']);
        $this->categoria = 'todos';
        $this->semestres = collect();
        $this->grupos = collect();
        $this->filtrosCambiaron();
    }

    public function activarPreinscripcion(int $inscripcionId, GestionAcademicaService $gestion): void
    {
        abort_unless($this->esCicloActual, 422, 'Solo se pueden activar preinscripciones del ciclo actual.');

        $alumno = $this->query()
            ->whereKey($inscripcionId)
            ->firstOrFail();

        $gestion->activarPreinscripcion(
            $alumno,
            $this->motivoActivacionNormalizado(),
            auth()->id(),
            now()->toDateString()
        );

        $this->quitarSeleccion($inscripcionId);
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Inscripción activada',
            'text' => 'El alumno pasó a Matrícula activa y ya puede aparecer en listas operativas.',
            'position' => 'top-end',
        ]);
    }

    public function activarPreinscritosSeleccionados(GestionAcademicaService $gestion): void
    {
        abort_unless($this->esCicloActual, 422, 'Solo se pueden activar preinscripciones del ciclo actual.');

        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
            'motivo_activacion' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $alumnos = $this->query()
            ->whereIn('inscripciones.id', array_map('intval', $this->selected))
            ->where('inscripciones.estatus', 'preinscrito')
            ->get();

        if ($alumnos->isEmpty()) {
            $this->addError('selected', 'Selecciona al menos un alumno preinscrito disponible.');
            return;
        }

        foreach ($alumnos as $alumno) {
            $gestion->activarPreinscripcion(
                $alumno,
                $this->motivoActivacionNormalizado(),
                auth()->id(),
                now()->toDateString()
            );
        }

        $total = $alumnos->count();
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $total === 1 ? 'Inscripción activada' : "{$total} inscripciones activadas",
            'text' => 'Los alumnos activados fueron enviados a Matrícula activa.',
            'position' => 'top-end',
        ]);
    }

    public function registrarSeguimientoSeleccionados(): void
    {
        $datos = $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
            'seguimiento_administrativo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $alumnos = $this->query()
            ->whereIn('inscripciones.id', array_map('intval', $datos['selected']))
            ->get();

        if ($alumnos->isEmpty()) {
            $this->addError('selected', 'Los alumnos seleccionados ya no están disponibles.');
            return;
        }

        DB::transaction(function () use ($alumnos, $datos): void {
            foreach ($alumnos as $alumno) {
                $service = app(AlumnosNoVigentesService::class);
                $contexto = $service->contextoDe($alumno);
                $snapshot = [
                    'estatus' => $alumno->estatus,
                    'activo' => (bool) $alumno->activo,
                    'ciclo_escolar_id' => $contexto?->ciclo_escolar_id,
                    'estado_ciclo' => $contexto?->estado,
                    'estatus_ciclo' => $service->estatusDe($contexto, $alumno),
                    'resultado_final' => $contexto instanceof InscripcionCiclo ? $contexto->resultado_final : null,
                ];

                CambioAcademico::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $contexto instanceof InscripcionCiclo ? $contexto->id : null,
                    'generacion_id' => $contexto?->generacion_id ?: $alumno->generacion_id,
                    'tipo' => 'seguimiento_administrativo_no_vigente',
                    'motivo' => trim($datos['seguimiento_administrativo']),
                    'datos_anteriores' => $snapshot,
                    'datos_nuevos' => $snapshot,
                    'realizado_por' => auth()->id(),
                    'realizado_at' => now(),
                ]);

                MovimientoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $contexto instanceof InscripcionCiclo ? $contexto->id : null,
                    'ciclo_escolar_id' => $contexto?->ciclo_escolar_id,
                    'ciclo_id' => $alumno->ciclo_id,
                    'nivel_anterior_id' => $contexto?->nivel_id ?: $alumno->nivel_id,
                    'nivel_nuevo_id' => $contexto?->nivel_id ?: $alumno->nivel_id,
                    'tipo' => 'seguimiento_administrativo',
                    'fecha' => now()->toDateString(),
                    'motivo' => trim($datos['seguimiento_administrativo']),
                    'observaciones' => trim($datos['seguimiento_administrativo']),
                    'estado_anterior' => $snapshot,
                    'estado_nuevo' => $snapshot,
                    'registrado_por' => auth()->id(),
                ]);
            }
        });

        $total = $alumnos->count();
        $this->selected = [];
        $this->selectPage = false;
        $this->seguimiento_administrativo = '';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Seguimiento registrado',
            'text' => "Se registró la nota administrativa para {$total} alumno(s).",
            'position' => 'top-end',
        ]);
    }

    public function restaurar(int $inscripcionId): void
    {
        abort_unless($this->categoria === 'archivados', 422);

        $alumno = Inscripcion::withTrashed()->whereKey($inscripcionId)->firstOrFail();
        $alumno->restore();
        $this->quitarSeleccion($inscripcionId);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Expediente restaurado',
            'text' => 'El expediente conservó su estatus previo. Si estaba Activo, regresó a Matrícula activa.',
            'position' => 'top-end',
        ]);
    }

    public function exportarExcel(AlumnosNoVigentesService $service)
    {
        $rows = $this->query()->get();
        $categoria = str($service->etiquetaCategoria($this->categoria))->slug('_');
        $ciclo = $this->ciclosEscolares->firstWhere('id', (int) $this->ciclo_escolar_id);
        $nombre = sprintf(
            'alumnos_no_vigentes_%s_%s_%s.xlsx',
            $this->slug_nivel,
            $ciclo ? "{$ciclo->inicio_anio}_{$ciclo->fin_anio}" : 'ciclo',
            $categoria
        );

        return Excel::download(
            new AlumnosNoVigentesExport($rows, $this->esBachillerato()),
            $nombre
        );
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre
            ?? $grupo?->nombre
            ?? '—';
    }

    public function nombreCompleto(Inscripcion $alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ], fn ($valor) => filled($valor)))) ?: '—';
    }

    public function claseEstatus(string $estatus): string
    {
        return match ($estatus) {
            'preinscrito' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300',
            'pendiente_reinscripcion' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
            'no_reinscrito', 'no_iniciado' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
            'egresado' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300',
            'reingreso', 'no_promovido' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300',
            'archivado' => 'bg-slate-200 text-slate-700 dark:bg-neutral-800 dark:text-slate-300',
            'pendiente_regularizacion' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-neutral-800 dark:text-slate-300',
        };
    }

    private function query(): Builder
    {
        return app(AlumnosNoVigentesService::class)->query(
            $this->nivel,
            (int) $this->ciclo_escolar_id,
            $this->filtros()
        );
    }

    private function filtros(): array
    {
        return [
            'categoria' => $this->categoria,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'search' => $this->search,
        ];
    }

    private function cargarGeneraciones(): Collection
    {
        $cicloId = (int) $this->ciclo_escolar_id;

        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where(function (Builder $query) use ($cicloId): void {
                $query
                    ->whereHas('inscripcionCiclos', fn (Builder $historial) => $historial
                        ->where('ciclo_escolar_id', $cicloId)
                        ->where('nivel_id', $this->nivel->id))
                    ->orWhereHas('grupos', fn (Builder $grupo) => $grupo
                        ->withTrashed()
                        ->where('ciclo_escolar_id', $cicloId)
                        ->where('nivel_id', $this->nivel->id));
            })
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function cargarGrupos(): Collection
    {
        if (! $this->generacion_id || ! $this->grado_id) {
            return collect();
        }

        return Grupo::withTrashed()
            ->with('asignacionGrupo')
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato(),
                fn (Builder $grupo) => $grupo->where('semestre_id', $this->semestre_id),
                fn (Builder $grupo) => $grupo->whereNull('semestre_id')
            )
            ->get()
            ->sortBy(fn ($grupo) => $grupo->asignacionGrupo?->nombre ?? $grupo->id)
            ->values();
    }

    private function filtrosCambiaron(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();
    }

    private function quitarSeleccion(int $inscripcionId): void
    {
        $this->selected = array_values(array_filter(
            $this->selected,
            fn ($id) => (int) $id !== $inscripcionId
        ));
        $this->selectPage = false;
    }

    private function motivoActivacionNormalizado(): string
    {
        $motivo = trim($this->motivo_activacion);

        if (mb_strlen($motivo) < 5) {
            $motivo = 'Documentación validada y confirmación formal de inscripción.';
        }

        return $motivo;
    }

    public function render(AlumnosNoVigentesService $service)
    {
        $alumnos = $this->query()->paginate($this->perPage);
        $resumen = $service->resumen(
            $this->nivel,
            (int) $this->ciclo_escolar_id,
            $this->filtros()
        );

        return view('livewire.accion.alumnos-no-vigentes', compact('alumnos', 'resumen'));
    }
}
