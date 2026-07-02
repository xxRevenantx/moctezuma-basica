<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Nivel;
use App\Services\GestionAcademicaService;
use Illuminate\Support\Collection;
use Livewire\Component;

class CierreNivelContinuidad extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $generaciones;
    public ?int $generacion_id = null;
    public string $motivo = '';
    public bool $egresar_activos = true;

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->refrescarGeneraciones();
    }

    public function desactivar(GestionAcademicaService $service): void
    {
        $datos = $this->validate([
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
            'egresar_activos' => ['boolean'],
        ]);
        $generacion = Generacion::query()->where('nivel_id', $this->nivel->id)->findOrFail($datos['generacion_id']);
        if (! $generacion->status) {
            $this->addError('generacion_id', 'La generación ya está desactivada.');
            return;
        }
        $afectados = $service->desactivarGeneracion($generacion, trim($datos['motivo']), (bool) $datos['egresar_activos'], auth()->id());
        $this->reset(['generacion_id', 'motivo']);
        $this->egresar_activos = true;
        $this->refrescarGeneraciones();
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Generación desactivada', 'text' => $afectados . ' alumno(s) fueron marcados como egresados.', 'position' => 'top-end']);
    }

    public function reactivar(int $generacionId, GestionAcademicaService $service): void
    {
        $this->validate(['motivo' => ['required', 'string', 'min:5', 'max:1000']]);
        $generacion = Generacion::query()->where('nivel_id', $this->nivel->id)->where('status', false)->findOrFail($generacionId);
        $service->reactivarGeneracion($generacion, trim($this->motivo), auth()->id());
        $this->motivo = '';
        $this->refrescarGeneraciones();
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Generación reactivada', 'text' => 'Los estatus individuales de los alumnos no fueron modificados.', 'position' => 'top-end']);
    }

    private function refrescarGeneraciones(): void
    {
        $this->generaciones = Generacion::query()->withCount([
            'inscripciones',
            'inscripciones as activos_count' => fn ($q) => $q->whereIn('estatus', ['activo', 'reingreso', 'no_promovido']),
            'inscripciones as egresados_count' => fn ($q) => $q->where('estatus', 'egresado'),
        ])->where('nivel_id', $this->nivel->id)->orderByDesc('status')->orderByDesc('anio_ingreso')->get();
    }

    public function render()
    {
        return view('livewire.accion.generales.cierre-nivel-continuidad');
    }
}
