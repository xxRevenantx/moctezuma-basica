<?php

namespace App\Livewire\Generacion;

use App\Models\Generacion;
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

    public function eliminar($id)
    {
        $gen = Generacion::find($id);

        if ($gen) {
            $gen->delete();
            $this->dispatch('refreshGeneraciones');
        }
    }

    #[On('refreshGeneraciones')]
    public function render()
    {
        $generaciones = Generacion::with('nivel')
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
