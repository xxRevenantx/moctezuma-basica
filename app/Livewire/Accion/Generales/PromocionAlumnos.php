<?php

namespace App\Livewire\Accion\Generales;

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

class PromocionAlumnos extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestresOrigen;
    public Collection $gruposOrigen;
    public Collection $semestresDestino;
    public Collection $gruposDestino;

    public ?int $ciclo_origen_id = null;
    public ?int $ciclo_destino_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_origen_id = null;
    public ?int $semestre_origen_id = null;
    public ?int $grupo_origen_id = null;
    public ?int $grado_destino_id = null;
    public ?int $semestre_destino_id = null;
    public ?int $grupo_destino_id = null;
    public string $motivo = '';
    public array $seleccionados = [];
    public bool $seleccionarPagina = false;

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_origen_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_origen_id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->grados = Grado::query()->where('nivel_id', $this->nivel->id)->orderBy('orden')->get();
        $this->semestresOrigen = collect();
        $this->gruposOrigen = collect();
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
    }

    private function cargarGeneraciones(): Collection
    {
        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_origen_id, fn (Builder $query) => $query->whereHas(
                'grupos',
                fn (Builder $grupos) => $grupos
                    ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                    ->where('estado', 'activo')
            ))
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function resolverSiguienteCicloId(?int $cicloId): ?int
    {
        $origen = $this->ciclosEscolares->firstWhere('id', $cicloId);

        if (! $origen) {
            return null;
        }

        return $this->ciclosEscolares
            ->first(fn ($ciclo) => (int) $ciclo->inicio_anio === (int) $origen->inicio_anio + 1)?->id;
    }

    public function esBachillerato(): bool
    {
        return str_contains(mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')), 'bachillerato');
    }

    public function updatedCicloOrigenId(): void
    {
        $this->ciclo_origen_id = $this->ciclo_origen_id ? (int) $this->ciclo_origen_id : null;
        $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_origen_id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->limpiarContexto();
    }

    public function updatedCicloDestinoId(): void
    {
        $this->grupo_destino_id = null;
        $this->prepararDestinoAutomatico();
    }

    public function updatedGeneracionId(): void
    {
        $this->limpiarContexto(false);
    }

    public function updatedGradoOrigenId(): void
    {
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->semestresOrigen = $this->grado_origen_id
            ? Semestre::query()->where('grado_id', $this->grado_origen_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->gruposOrigen = $this->esBachillerato() ? collect() : $this->cargarGrupos($this->grado_origen_id, null, $this->ciclo_origen_id);
        $this->prepararDestinoAutomatico();
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function updatedSemestreOrigenId(): void
    {
        $this->grupo_origen_id = null;
        $this->gruposOrigen = $this->cargarGrupos($this->grado_origen_id, $this->semestre_origen_id, $this->ciclo_origen_id);
        $this->prepararDestinoAutomatico();
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function updatedGrupoOrigenId(): void
    {
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->resetPage();
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresDestino = $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->gruposDestino = $this->esBachillerato() ? collect() : $this->cargarGrupos($this->grado_destino_id, null, $this->ciclo_destino_id);
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->grupo_destino_id = null;
        $this->gruposDestino = $this->cargarGrupos($this->grado_destino_id, $this->semestre_destino_id, $this->ciclo_destino_id);
    }

    public function updatedSeleccionarPagina(bool $valor): void
    {
        $this->seleccionados = $valor
            ? $this->alumnosQuery()->limit(25)->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function promoverSeleccionados(GestionAcademicaService $service): void
    {
        $reglas = [
            'ciclo_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_destino_id' => ['required', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:inscripciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ];
        if ($this->esBachillerato()) {
            $reglas['semestre_origen_id'] = ['required', 'exists:semestres,id'];
            $reglas['semestre_destino_id'] = ['required', 'exists:semestres,id'];
        }
        $this->validate($reglas);

        $alumnos = $this->alumnosQuery()->whereIn('id', array_map('intval', $this->seleccionados))->get();
        foreach ($alumnos as $alumno) {
            $service->cambiarAsignacion($alumno, [
                'ciclo_escolar_id' => $this->ciclo_destino_id,
                'nivel_id' => $this->nivel->id,
                'generacion_id' => $this->generacion_id,
                'grado_id' => $this->grado_destino_id,
                'semestre_id' => $this->esBachillerato() ? $this->semestre_destino_id : null,
                'grupo_id' => $this->grupo_destino_id,
                'matricula' => $alumno->matricula,
            ], trim($this->motivo), auth()->id());
            if ($alumno->estatus === 'no_promovido') {
                $service->cambiarEstatus($alumno->fresh(), 'activo', 'Regularización por promoción. ' . trim($this->motivo), auth()->id());
            }
        }

        $total = $alumnos->count();
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->motivo = '';
        $this->resetPage();
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Promoción aplicada', 'text' => "Se promovieron {$total} alumno(s) dentro de la misma generación.", 'position' => 'top-end']);
    }

    public function marcarNoPromovidos(GestionAcademicaService $service): void
    {
        $this->validate([
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:inscripciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $alumnos = $this->alumnosQuery()->whereIn('id', array_map('intval', $this->seleccionados))->get();
        foreach ($alumnos as $alumno) {
            $service->cambiarEstatus($alumno, 'no_promovido', trim($this->motivo), auth()->id());
        }
        $total = $alumnos->count();
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->motivo = '';
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Resultado guardado', 'text' => "{$total} alumno(s) permanecen en el mismo grado o semestre y conservan su generación.", 'position' => 'top-end']);
    }

    private function prepararDestinoAutomatico(): void
    {
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();

        if ($this->esBachillerato() && $this->semestre_origen_id) {
            $origen = Semestre::query()->find($this->semestre_origen_id);
            $siguiente = Semestre::query()
                ->where('numero', '>', (int) ($origen?->numero ?? 0))
                ->whereHas('grado', fn (Builder $q) => $q->where('nivel_id', $this->nivel->id))
                ->orderBy('numero')
                ->first();
            if ($siguiente) {
                $this->ciclo_destino_id = (int) $siguiente->numero % 2 === 0
                    ? $this->ciclo_origen_id
                    : ($this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_destino_id);
                $this->grado_destino_id = $siguiente->grado_id;
                $this->semestre_destino_id = $siguiente->id;
                $this->semestresDestino = Semestre::query()->where('grado_id', $siguiente->grado_id)->orderBy('orden_global')->get();
                $this->gruposDestino = $this->cargarGrupos($siguiente->grado_id, $siguiente->id, $this->ciclo_destino_id);
            }
            return;
        }

        if ($this->grado_origen_id) {
            $origen = $this->grados->firstWhere('id', $this->grado_origen_id);
            $siguiente = $this->grados->first(fn ($g) => (int) $g->orden > (int) ($origen?->orden ?? 0));
            if ($siguiente) {
                $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id)
                    ?? $this->ciclo_destino_id;
                $this->grado_destino_id = $siguiente->id;
                $this->gruposDestino = $this->cargarGrupos($siguiente->id, null, $this->ciclo_destino_id);
            }
        }
    }

    private function cargarGrupos(?int $gradoId, ?int $semestreId, ?int $cicloEscolarId): Collection
    {
        if (! $this->generacion_id || ! $gradoId) {
            return collect();
        }
        return Grupo::query()
            ->with('asignacionGrupo')
            ->withCount(['inscripciones as alumnos_activos_count' => fn (Builder $alumnos) => $alumnos
                ->where('activo', true)
                ->whereNull('deleted_at')])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', 'activo')
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $gradoId)
            ->when($this->esBachillerato(), fn (Builder $q) => $q->where('semestre_id', $semestreId), fn (Builder $q) => $q->whereNull('semestre_id'))
            ->get()->sortBy(fn ($g) => $g->asignacionGrupo?->nombre ?? $g->id)->values();
    }

    private function alumnosQuery(): Builder
    {
        return Inscripcion::query()
            ->with(['grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_origen_id, fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_origen_id))
            ->when($this->generacion_id, fn (Builder $q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_origen_id, fn (Builder $q) => $q->where('grado_id', $this->grado_origen_id))
            ->when($this->esBachillerato() && $this->semestre_origen_id, fn (Builder $q) => $q->where('semestre_id', $this->semestre_origen_id))
            ->when($this->grupo_origen_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_origen_id))
            ->where('activo', true)
            ->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre');
    }

    private function limpiarContexto(bool $limpiarGeneracion = true): void
    {
        if ($limpiarGeneracion) $this->generacion_id = null;
        $this->grado_origen_id = null;
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresOrigen = collect();
        $this->gruposOrigen = collect();
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.accion.generales.promocion-alumnos', [
            'alumnos' => $this->alumnosQuery()->paginate(25),
        ]);
    }
}
