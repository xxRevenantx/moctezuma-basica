<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Nivel;
use App\Models\CicloEscolar;
use App\Services\CierreCicloEscolarService;
use Illuminate\Support\Collection;
use Livewire\Component;

class PanelCierreCiclo extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $generaciones;
    public Collection $ciclos;
    public ?int $generacion_id = null;
    public ?int $ciclo_escolar_id = null;
    public string $fecha_egreso = '';
    public string $motivo = '';
    public bool $cerrar_generacion = true;
    public bool $cerrar_ciclo = false;
    public string $confirmacion = '';
    public int $paso = 1;
    public array $seleccionados = [];

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->generaciones = Generacion::query()->where('nivel_id', $this->nivel->id)->orderByDesc('status')->orderByDesc('anio_ingreso')->get();
        $this->ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get();
        $this->ciclo_escolar_id = $this->ciclos->firstWhere('es_actual', true)?->id ?? $this->ciclos->first()?->id;
        $this->fecha_egreso = now()->toDateString();
    }

    public function updatedGeneracionId(): void
    {
        $this->paso = 1;
        $this->seleccionados = [];
        $generacion = $this->generaciones->firstWhere('id', $this->generacion_id);
        if ($generacion?->fecha_termino) $this->fecha_egreso = $generacion->fecha_termino->toDateString();
    }

    public function preparar(CierreCicloEscolarService $service): void
    {
        $this->validate(['generacion_id' => ['required', 'integer', 'exists:generaciones,id'], 'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id']]);
        $this->seleccionados = $service->candidatos($this->nivel->id, $this->generacion_id, $this->ciclo_escolar_id)->where('apto', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->paso = 2;
    }

    public function siguiente(): void
    {
        if ($this->paso === 2 && count($this->seleccionados) === 0) {
            $this->addError('seleccionados', 'Selecciona al menos un alumno apto para continuar.');
            return;
        }
        $this->paso = min(4, $this->paso + 1);
    }

    public function anterior(): void { $this->paso = max(1, $this->paso - 1); }

    public function ejecutar(CierreCicloEscolarService $service): void
    {
        $datos = $this->validate([
            'generacion_id' => ['required', 'integer', 'exists:generaciones,id'],
            'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'fecha_egreso' => ['required', 'date'], 'motivo' => ['required', 'string', 'min:10', 'max:1500'],
            'cerrar_generacion' => ['boolean'], 'cerrar_ciclo' => ['boolean'],
            'confirmacion' => ['required', 'in:EGRESAR'], 'seleccionados' => ['required', 'array', 'min:1'],
        ]);
        $datos['nivel_id'] = $this->nivel->id;
        $ids = collect($this->seleccionados)->map(fn ($id) => (int) $id)->all();
        $proceso = $service->ejecutar($datos, $ids, auth()->id());
        $this->reset(['motivo', 'confirmacion', 'seleccionados']);
        $this->cerrar_generacion = true; $this->cerrar_ciclo = false; $this->paso = 1;
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Cierre procesado', 'text' => "Se egresaron {$proceso->total_procesados} alumno(s) y se excluyeron {$proceso->total_excluidos}.", 'position' => 'top-end']);
    }

    public function getDiagnosticoProperty(): array
    {
        return app(CierreCicloEscolarService::class)->diagnostico($this->nivel->id, $this->generacion_id, $this->ciclo_escolar_id);
    }

    public function getCandidatosProperty(): Collection
    {
        if (! $this->generacion_id) return collect();
        return app(CierreCicloEscolarService::class)->candidatos($this->nivel->id, $this->generacion_id, $this->ciclo_escolar_id);
    }

    public function render() { return view('livewire.accion.generales.panel-cierre-ciclo'); }
}
