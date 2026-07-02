<?php

namespace App\Livewire\Generacion;

use App\Models\Generacion;
use App\Services\GestionAcademicaService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGeneraciones extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $incluir_inactivas = true;
    public bool $modalDesactivar = false;
    public ?int $generacionSeleccionada = null;
    public string $motivo = '';
    public bool $egresar_activos = true;

    public function updatingSearch(): void { $this->resetPage(); }

    public function prepararDesactivacion(int $id): void
    {
        $this->generacionSeleccionada = $id;
        $this->motivo = '';
        $this->egresar_activos = true;
        $this->modalDesactivar = true;
    }

    public function desactivar(GestionAcademicaService $service): void
    {
        $this->validate([
            'generacionSeleccionada' => ['required', 'exists:generaciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
            'egresar_activos' => ['boolean'],
        ]);
        $g = Generacion::query()->findOrFail($this->generacionSeleccionada);
        $afectados = $service->desactivarGeneracion($g, $this->motivo, $this->egresar_activos, auth()->id());
        $this->modalDesactivar = false;
        $this->dispatch('swal', [
            'title' => 'Generación desactivada',
            'text' => $this->egresar_activos ? "Se marcaron {$afectados} alumno(s) como egresados." : 'Los alumnos conservaron su estatus individual.',
            'icon' => 'success', 'position' => 'top-end',
        ]);
    }

    public function reactivar(int $id, GestionAcademicaService $service): void
    {
        $service->reactivarGeneracion(Generacion::query()->findOrFail($id), 'Reactivación administrativa desde el catálogo de generaciones.', auth()->id());
        $this->dispatch('swal', ['title' => 'Generación reactivada', 'icon' => 'success', 'position' => 'top-end']);
    }

    #[On('refreshGeneraciones')]
    public function render()
    {
        $query = Generacion::query()
            ->with(['nivel', 'cicloEscolarInicio', 'cicloEscolarFin'])
            ->withCount([
                'inscripciones as alumnos_total_count',
                'inscripciones as alumnos_activos_count' => fn ($q) => $q->whereIn('estatus', ['activo', 'reingreso', 'no_promovido']),
                'inscripciones as alumnos_egresados_count' => fn ($q) => $q->where('estatus', 'egresado'),
                'inscripciones as alumnos_bajas_count' => fn ($q) => $q->whereIn('estatus', ['baja_temporal', 'baja_definitiva', 'trasladado', 'suspendido', 'inactivo']),
            ])
            ->when(! $this->incluir_inactivas, fn ($q) => $q->where('status', true))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($sub) => $sub->where('nombre', 'like', $term)
                    ->orWhere('anio_ingreso', 'like', $term)
                    ->orWhere('anio_egreso', 'like', $term)
                    ->orWhereHas('nivel', fn ($n) => $n->where('nombre', 'like', $term)));
            })
            ->orderBy('nivel_id')->orderByDesc('anio_ingreso');

        $generaciones = $query->paginate(12);
        return view('livewire.generacion.mostrar-generaciones', compact('generaciones'));
    }
}
