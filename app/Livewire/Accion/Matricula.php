<?php

namespace App\Livewire\Accion;

use App\Exports\MatriculaExport;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\GestionAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Matricula extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $generacionesDestino;
    public Collection $grados;
    public Collection $semestres;
    public Collection $semestresDestino;
    public Collection $grupos;
    public Collection $gruposDestino;

    public ?int $ciclo_escolar_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public string $estatus = 'todos';
    public string $search = '';
    public bool $mostrar_archivados = false;
    public int $perPage = 20;

    public array $selected = [];
    public bool $selectPage = false;
    public ?int $destino_ciclo_escolar_id = null;
    public ?int $destino_generacion_id = null;
    public ?int $destino_grado_id = null;
    public ?int $destino_semestre_id = null;
    public ?int $destino_grupo_id = null;
    public string $motivo_cambio = '';

    public bool $modalBitacora = false;
    public ?int $alumnoBitacoraId = null;

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
        $this->semestres = collect();
        $this->semestresDestino = collect();
        $this->grupos = collect();
        $this->gruposDestino = collect();
    }

    public function esBachillerato(): bool
    {
        return str_contains(
            mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')),
            'bachillerato'
        );
    }

    private function cargarGeneraciones(?int $cicloEscolarId = null): Collection
    {
        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($cicloEscolarId, fn(Builder $query) => $query->whereHas(
                'grupos',
                fn(Builder $grupos) => $grupos
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('estado', 'activo')
            ))
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function cargarSemestres(?int $gradoId): Collection
    {
        return $gradoId
            ? Semestre::query()->where('grado_id', $gradoId)->orderBy('numero')->get()
            : collect();
    }

    private function cargarGrupos(?int $generacionId, ?int $gradoId, ?int $semestreId, ?int $cicloEscolarId = null): Collection
    {
        if (! $generacionId || ! $gradoId) {
            return collect();
        }

        $cicloEscolarId ??= $this->ciclo_escolar_id;

        return Grupo::query()
            ->with('asignacionGrupo')
            ->withCount(['inscripciones as alumnos_activos_count' => fn(Builder $alumnos) => $alumnos
                ->where('activo', true)
                ->whereNull('deleted_at')])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', 'activo')
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $generacionId)
            ->where('grado_id', $gradoId)
            ->when(
                $this->esBachillerato(),
                fn(Builder $query) => $query->where('semestre_id', $semestreId),
                fn(Builder $query) => $query->whereNull('semestre_id')
            )
            ->get()
            ->sortBy(fn($grupo) => $grupo->asignacionGrupo?->nombre ?? $grupo->id)
            ->values();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'destino_generacion_id',
            'destino_grado_id',
            'destino_semestre_id',
            'destino_grupo_id',
        ]);
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->semestres = collect();
        $this->semestresDestino = collect();
        $this->grupos = collect();
        $this->gruposDestino = collect();
        $this->filtrosCambiaron();
    }

    public function updatedDestinoCicloEscolarId(): void
    {
        $this->reset([
            'destino_generacion_id',
            'destino_grado_id',
            'destino_semestre_id',
            'destino_grupo_id',
        ]);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->filtrosCambiaron();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->esBachillerato()
            ? collect()
            : $this->cargarGrupos($this->generacion_id, $this->grado_id, null);
        $this->filtrosCambiaron();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->filtrosCambiaron();
    }

    public function updatedGrupoId(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedEstatus(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedMostrarArchivados(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedSearch(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedDestinoGeneracionId(): void
    {
        $this->destino_grupo_id = null;
        $this->gruposDestino = $this->cargarGrupos(
            $this->destino_generacion_id,
            $this->destino_grado_id,
            $this->destino_semestre_id,
            $this->destino_ciclo_escolar_id
        );
    }

    public function updatedDestinoGradoId(): void
    {
        $this->destino_semestre_id = null;
        $this->destino_grupo_id = null;
        $this->semestresDestino = $this->cargarSemestres($this->destino_grado_id);
        $this->gruposDestino = $this->esBachillerato()
            ? collect()
            : $this->cargarGrupos($this->destino_generacion_id, $this->destino_grado_id, null, $this->destino_ciclo_escolar_id);
    }

    public function updatedDestinoSemestreId(): void
    {
        $this->destino_grupo_id = null;
        $this->gruposDestino = $this->cargarGrupos(
            $this->destino_generacion_id,
            $this->destino_grado_id,
            $this->destino_semestre_id,
            $this->destino_ciclo_escolar_id
        );
    }

    private function filtrosCambiaron(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();
    }

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selected = [];
            return;
        }

        $this->selected = $this->query()
            ->forPage($this->getPage(), $this->perPage)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    private function query(): Builder
    {
        $query = $this->mostrar_archivados ? Inscripcion::withTrashed() : Inscripcion::query();

        return $query
            ->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo', 'nivel'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_escolar_id, fn(Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when(
                $this->generacion_id,
                fn(Builder $q) => $q->where('generacion_id', $this->generacion_id),
                fn(Builder $q) => $q->whereHas('generacion', fn(Builder $g) => $g->where('status', true))
            )
            ->when($this->grado_id, fn(Builder $q) => $q->where('grado_id', $this->grado_id))
            ->when($this->semestre_id, fn(Builder $q) => $q->where('semestre_id', $this->semestre_id))
            ->when($this->grupo_id, fn(Builder $q) => $q->where('grupo_id', $this->grupo_id))
            ->when($this->estatus !== 'todos', fn(Builder $q) => $q->where('estatus', $this->estatus))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $term = '%' . trim($this->search) . '%';
                $q->where(function (Builder $s) use ($term): void {
                    $s->where('matricula', 'like', $term)
                        ->orWhere('curp', 'like', $term)
                        ->orWhere('folio', 'like', $term)
                        ->orWhere('nombre', 'like', $term)
                        ->orWhere('apellido_paterno', 'like', $term)
                        ->orWhere('apellido_materno', 'like', $term);
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    public function cambiarGeneracionSeleccionados(GestionAcademicaService $service): void
    {
        $rules = [
            'destino_ciclo_escolar_id' => ['required', 'exists:ciclo_escolares,id'],
            'selected' => ['required', 'array', 'min:1'],
            'destino_generacion_id' => ['required', 'exists:generaciones,id'],
            'destino_grado_id' => ['required', 'exists:grados,id'],
            'destino_grupo_id' => ['required', 'exists:grupos,id'],
            'motivo_cambio' => ['required', 'string', 'min:5', 'max:1000'],
        ];

        if ($this->esBachillerato()) {
            $rules['destino_semestre_id'] = ['required', 'exists:semestres,id'];
        }

        $this->validate($rules);

        $total = 0;
        foreach (Inscripcion::withTrashed()->whereIn('id', $this->selected)->get() as $alumno) {
            $service->cambiarAsignacion($alumno, [
                'ciclo_escolar_id' => $this->destino_ciclo_escolar_id,
                'nivel_id' => $this->nivel->id,
                'generacion_id' => $this->destino_generacion_id,
                'grado_id' => $this->destino_grado_id,
                'semestre_id' => $this->esBachillerato() ? $this->destino_semestre_id : null,
                'grupo_id' => $this->destino_grupo_id,
                'matricula' => $alumno->matricula,
            ], $this->motivo_cambio, auth()->id());
            $total++;
        }

        $this->reset([
            'selected',
            'selectPage',
            'destino_generacion_id',
            'destino_grado_id',
            'destino_semestre_id',
            'destino_grupo_id',
            'motivo_cambio',
        ]);
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();

        $this->dispatch('swal', [
            'title' => 'Asignación actualizada',
            'text' => "Se modificaron {$total} alumno(s).",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function archivar(int $inscripcionId): void
    {
        $alumno = Inscripcion::query()->findOrFail($inscripcionId);
        $alumno->delete();

        $this->selected = array_values(array_filter(
            $this->selected,
            fn($id) => (int) $id !== $inscripcionId
        ));

        $this->dispatch('swal', [
            'title' => 'Alumno archivado',
            'text' => 'Su información permanece disponible al incluir expedientes archivados.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function restaurar(int $inscripcionId): void
    {
        $alumno = Inscripcion::withTrashed()->findOrFail($inscripcionId);
        $alumno->restore();

        $this->dispatch('swal', [
            'title' => 'Alumno restaurado',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function activarPreinscripcion(
        int $inscripcionId,
        string $motivo,
        GestionAcademicaService $service
    ): void {
        abort_unless(auth()->user()?->is_admin, 403);

        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500) {
            $this->dispatch('swal', [
                'title' => 'Motivo no válido',
                'text' => 'Escribe un motivo de activación de 5 a 500 caracteres.',
                'icon' => 'warning',
                'position' => 'center',
            ]);

            return;
        }

        $alumno = Inscripcion::query()->find($inscripcionId);

        if (! $alumno) {
            $this->dispatch('swal', [
                'title' => 'Alumno no disponible',
                'text' => 'El expediente no existe, está archivado o ya no se encuentra disponible.',
                'icon' => 'error',
                'position' => 'center',
            ]);

            return;
        }

        if (($alumno->estatus ?? 'activo') !== 'preinscrito') {
            $this->dispatch('swal', [
                'title' => 'La inscripción ya cambió',
                'text' => 'Solo los alumnos preinscritos pueden activarse desde este botón.',
                'icon' => 'info',
                'position' => 'center',
            ]);

            return;
        }

        try {
            $service->activarPreinscripcion(
                $alumno,
                $motivo,
                auth()->id()
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $mensaje = collect($exception->errors())->flatten()->first()
                ?: 'No fue posible activar la inscripción. Revisa la asignación académica.';

            $this->dispatch('swal', [
                'title' => 'No se pudo activar',
                'text' => $mensaje,
                'icon' => 'warning',
                'position' => 'center',
            ]);

            return;
        }

        $this->selected = array_values(array_filter(
            $this->selected,
            fn($id) => (int) $id !== $inscripcionId
        ));

        $this->dispatch('swal', [
            'title' => 'Inscripción activada',
            'text' => 'El alumno quedó inscrito y activo. También se habilitó su acceso y se registró el movimiento en la bitácora.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function abrirBitacora(int $id): void
    {
        $this->alumnoBitacoraId = $id;
        $this->modalBitacora = true;
    }

    public function cerrarBitacora(): void
    {
        $this->modalBitacora = false;
        $this->alumnoBitacoraId = null;
    }

    public function restaurarFiltrosMatricula(array $filtros): void
    {
        $pagina = max(1, (int) ($filtros['page'] ?? 1));

        $this->ciclo_escolar_id = filled($filtros['ciclo_escolar_id'] ?? null)
            ? (int) $filtros['ciclo_escolar_id']
            : ($this->ciclosEscolares->firstWhere('es_actual', true)?->id ?? $this->ciclosEscolares->first()?->id);
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);

        foreach (['generacion_id', 'grado_id', 'semestre_id', 'grupo_id'] as $campo) {
            $this->{$campo} = filled($filtros[$campo] ?? null) ? (int) $filtros[$campo] : null;
        }

        $this->estatus = (string) ($filtros['estatus'] ?? 'todos');
        $this->search = trim((string) ($filtros['search'] ?? ''));
        $this->mostrar_archivados = filter_var(
            $filtros['mostrar_archivados'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        $grupoSeleccionado = $this->grupo_id;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->grupo_id = $grupoSeleccionado
            && $this->grupos->contains(fn($grupo) => (int) $grupo->id === (int) $grupoSeleccionado)
            ? (int) $grupoSeleccionado
            : null;

        $this->selected = [];
        $this->selectPage = false;
        $this->setPage($pagina);
    }

    public function localizarAlumnoEnMatricula(int $inscripcionId): void
    {
        $alumno = Inscripcion::withTrashed()->findOrFail($inscripcionId);

        $this->ciclo_escolar_id = $alumno->ciclo_escolar_id ? (int) $alumno->ciclo_escolar_id : $this->ciclo_escolar_id;
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->generacion_id = $alumno->generacion_id ? (int) $alumno->generacion_id : null;
        $this->grado_id = $alumno->grado_id ? (int) $alumno->grado_id : null;
        $this->semestre_id = $alumno->semestre_id ? (int) $alumno->semestre_id : null;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->grupo_id = $alumno->grupo_id
            && $this->grupos->contains(fn($grupo) => (int) $grupo->id === (int) $alumno->grupo_id)
            ? (int) $alumno->grupo_id
            : null;
        $this->estatus = 'todos';
        $this->mostrar_archivados = $alumno->trashed();
        $this->search = $alumno->matricula
            ?: $alumno->curp
            ?: trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}");
        $this->selected = [];
        $this->selectPage = false;
        $this->setPage(1);
    }

    public function exportarExcel()
    {
        $rows = $this->query()->get();

        return Excel::download(
            new MatriculaExport($rows, $this->nivel->nombre, $this->esBachillerato()),
            'padron_' . $this->slug_nivel . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'search',
            'mostrar_archivados',
            'selected',
            'selectPage',
        ]);
        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->estatus = 'todos';
        $this->semestres = collect();
        $this->grupos = collect();
        $this->resetPage();
    }

    public function textoGrupo($grupo): string
    {
        if (! $grupo) {
            return '—';
        }

        $nombre = $grupo->asignacionGrupo?->nombre
            ?? $grupo->grupo
            ?? $grupo->nombre
            ?? 'Sin grupo';

        if (isset($grupo->alumnos_activos_count)) {
            return $nombre . ' · ' . number_format((int) $grupo->alumnos_activos_count) . ' alumnos · cupo ilimitado';
        }

        return $nombre;
    }

    public function etiquetaEstatus(?string $estatus): string
    {
        return match ($estatus) {
            'preinscrito' => 'Preinscrito',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'trasladado' => 'Trasladado',
            'suspendido' => 'Suspendido',
            'egresado' => 'Egresado',
            'inactivo' => 'Inactivo',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            default => 'Activo',
        };
    }

    public function render()
    {
        $alumnos = $this->query()->paginate($this->perPage);
        $resumenBase = (clone $this->query())->reorder();
        $resumen = [
            'total' => (clone $resumenBase)->count(),
            'hombres' => (clone $resumenBase)->where('genero', 'H')->count(),
            'mujeres' => (clone $resumenBase)->where('genero', 'M')->count(),
            'activos' => (clone $resumenBase)->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])->count(),
            'bajas' => (clone $resumenBase)->whereIn('estatus', ['baja_temporal', 'baja_definitiva', 'trasladado', 'suspendido', 'inactivo'])->count(),
            'egresados' => (clone $resumenBase)->where('estatus', 'egresado')->count(),
        ];

        $bitacoraAlumno = $this->alumnoBitacoraId
            ? Inscripcion::withTrashed()
            ->with(['cambiosAcademicos.usuario', 'generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->find($this->alumnoBitacoraId)
            : null;

        return view('livewire.accion.matricula', compact('alumnos', 'resumen', 'bitacoraAlumno'));
    }
}
