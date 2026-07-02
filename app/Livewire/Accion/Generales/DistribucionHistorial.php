<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\DistribucionEscolarService;
use Illuminate\Support\Collection;
use Livewire\Component;

class DistribucionHistorial extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public string $generacion_id = '';
    public string $grado_id = '';
    public string $semestre_id = '';
    public string $grupo_id = '';
    public string $estado = 'todos';
    public bool $solo_ya_no_estan = false;

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->generaciones = Generacion::query()->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')->orderByDesc('anio_ingreso')->get();
        $this->grados = Grado::query()->where('nivel_id', $this->nivel->id)->orderBy('orden')->get();
        $this->semestres = collect();
        $this->grupos = collect();
    }

    public function updatedGeneracionId(): void { $this->cargarGrupos(); }
    public function updatedGradoId(): void
    {
        $this->semestre_id = '';
        $this->semestres = $this->grado_id !== ''
            ? Semestre::query()->where('grado_id', $this->grado_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->cargarGrupos();
    }
    public function updatedSemestreId(): void { $this->cargarGrupos(); }

    private function cargarGrupos(): void
    {
        $this->grupo_id = '';
        $this->grupos = Grupo::query()->with('asignacionGrupo')
            ->where('nivel_id', $this->nivel->id)
            ->when($this->generacion_id !== '', fn ($q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn ($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->semestre_id !== '', fn ($q) => $q->where('semestre_id', $this->semestre_id))
            ->get()->sortBy(fn ($g) => $g->asignacionGrupo?->nombre ?? $g->id)->values();
    }

    public function getCategoriasProperty(): array
    {
        return app(DistribucionEscolarService::class)->categorias();
    }

    public function getBloquesProperty(): Collection
    {
        return app(DistribucionEscolarService::class)->bloques($this->nivel, $this->filtros());
    }

    public function getListadoProperty(): Collection
    {
        return app(DistribucionEscolarService::class)->listadoCompleto($this->nivel, $this->filtros());
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['generacion_id', 'grado_id', 'semestre_id', 'grupo_id', 'solo_ya_no_estan']);
        $this->estado = 'todos';
        $this->semestres = collect();
        $this->grupos = collect();
    }

    public function getUrlPdfProperty(): string { return route('generales.distribucion.pdf', array_merge(['slug_nivel' => $this->slug_nivel], $this->filtrosUrl())); }
    public function getUrlExcelProperty(): string { return route('generales.distribucion.excel', array_merge(['slug_nivel' => $this->slug_nivel], $this->filtrosUrl())); }
    public function getUrlZipProperty(): string { return route('generales.distribucion.zip', array_merge(['slug_nivel' => $this->slug_nivel], $this->filtrosUrl())); }

    private function filtros(): array
    {
        return [
            'generacion_id' => $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            'grado_id' => $this->grado_id !== '' ? (int) $this->grado_id : null,
            'semestre_id' => $this->semestre_id !== '' ? (int) $this->semestre_id : null,
            'grupo_id' => $this->grupo_id !== '' ? (int) $this->grupo_id : null,
            'estado' => $this->estado,
            'solo_ya_no_estan' => $this->solo_ya_no_estan,
        ];
    }

    private function filtrosUrl(): array
    {
        return array_filter($this->filtros(), fn ($v) => $v !== null && $v !== '' && $v !== false && $v !== 'todos');
    }

    public function render()
    {
        return view('livewire.accion.generales.distribucion-historial');
    }
}
