<?php

namespace App\Livewire\Generacion;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGeneraciones extends Component
{
    use WithPagination;

    public $search = '';

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }


    #[On('refreshGeneraciones')]
    public function render()
    {
        $generaciones = \App\Models\Generacion::where('anio_ingreso', 'like', '%' . $this->search . '%')
            ->orWhere('anio_egreso', 'like', '%' . $this->search . '%')
            ->orderBy('nivel_id', 'asc')
            ->orderBy('anio_ingreso', 'asc')
            ->paginate(10);
        return view('livewire.generacion.mostrar-generaciones', compact('generaciones'));
    }
}
