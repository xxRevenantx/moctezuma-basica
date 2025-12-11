<?php

namespace App\Livewire\Generacion;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGeneraciones extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[On('refreshGeneraciones')]
    public function render()
    {
        $generaciones = \App\Models\Generacion::with('nivel')
            ->where(function ($q) {
                $q->where('anio_ingreso', 'like', '%' . $this->search . '%')
                    ->orWhere('anio_egreso', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nivel_id', 'asc')
            ->orderBy('anio_ingreso', 'asc')
            ->paginate(10);

        // Colección interna (la página actual)
        $collection = $generaciones->getCollection();

        // Agrupar por nombre de nivel
        $groupedByNivel = $collection->groupBy(function ($g) {
            return optional($g->nivel)->nombre ?? 'Sin nivel asignado';
        });

        // Resumen
        $totalGeneraciones    = $generaciones->total();
        $generacionesActivas  = $collection->where('status', 'activa')->count();
        $generacionesInactivas = $totalGeneraciones - $generacionesActivas;

        return view('livewire.generacion.mostrar-generaciones', [
            'generaciones'          => $generaciones,
            'groupedByNivel'        => $groupedByNivel,
            'totalGeneraciones'     => $totalGeneraciones,
            'generacionesActivas'   => $generacionesActivas,
            'generacionesInactivas' => $generacionesInactivas,
        ]);
    }
}
