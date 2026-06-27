<?php

namespace App\Livewire\Generacion;

use App\Models\Generacion;
use App\Services\CierreNivelReingresoService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGeneraciones extends Component
{
    use WithPagination;

    public $search = '';
    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function cerrar(int $id): void
    {
        try {
            app(CierreNivelReingresoService::class)->cerrarGeneracion(
                Generacion::query()->findOrFail($id),
                auth()->id()
            );
            $this->dispatch('swal', [
                'title' => 'Generación cerrada',
                'text' => 'Ya no aparecerá en módulos operativos, pero seguirá disponible en el historial.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('swal', [
                'title' => 'No se puede cerrar',
                'text' => collect($e->errors())->flatten()->first(),
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
        }
    }

    public function reactivar(int $id): void
    {
        app(CierreNivelReingresoService::class)->reactivarGeneracion(
            Generacion::query()->findOrFail($id)
        );
        $this->dispatch('swal', [
            'title' => 'Generación reactivada',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    #[On('refreshGeneraciones')]
    public function render()
    {
        $generaciones = Generacion::with('nivel')->withCount(['trayectoriasAcademicas as alumnos_activos_count' => fn ($q) => $q->where('activo', true)->where('es_actual', true)])
            ->where(function ($q) {
                $q->where('anio_ingreso', 'like', '%' . $this->search . '%')
                  ->orWhere('anio_egreso', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nivel_id', 'asc')
            ->orderBy('anio_ingreso', 'asc')
            ->paginate(10);

        $collection = $generaciones->getCollection();

        $groupedByNivel = $collection->groupBy(function ($g) {
            return optional($g->nivel)->nombre ?? 'Sin nivel asignado';
        });

        $totalGeneraciones     = $generaciones->total();
        $generacionesActivas   = $collection->where('status', 1)->count();
        $generacionesInactivas = $collection->where('status', 0)->count();

        return view('livewire.generacion.mostrar-generaciones', compact(
            'generaciones',
            'groupedByNivel',
            'totalGeneraciones',
            'generacionesActivas',
            'generacionesInactivas'
        ));
    }
}
