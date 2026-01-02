<?php

namespace App\Livewire\Grupo;

use App\Models\Grupo;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGrupos extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ELIMINAR GRUPO
    public function eliminar($id)
    {
        $grupo = Grupo::find($id);

        if ($grupo) {
            $grupo->delete();
            $this->dispatch('refreshGrupos');
        }
    }

    #[On('refreshGrupos')]
    public function render()
    {
        $grupos = Grupo::query()
            ->with(['nivel', 'grado', 'generacion', 'semestre'])
            ->where('nombre', 'like', '%' . $this->search . '%')
            ->orderBy('nivel_id', 'asc')
            ->orderBy('nombre', 'asc')
            ->paginate(12);

        // Colección de la página actual
        $collection = $grupos->getCollection();

        // Agrupar por nombre de nivel
        $groupedByNivel = $collection->groupBy(function ($g) {
            return optional($g->nivel)->nombre ?? 'Sin nivel asignado';
        });

        $totalGrupos    = $grupos->total();
        $totalNiveles   = $collection->pluck('nivel_id')->filter()->unique()->count();
        $gruposSinNivel = $collection->whereNull('nivel_id')->count();

        return view('livewire.grupo.mostrar-grupos', [
            'grupos'          => $grupos,
            'groupedByNivel'  => $groupedByNivel,
            'totalGrupos'     => $totalGrupos,
            'totalNiveles'    => $totalNiveles,
            'gruposSinNivel'  => $gruposSinNivel,
        ]);
    }
}
