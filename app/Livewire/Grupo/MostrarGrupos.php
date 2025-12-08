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

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[On('refreshGrupos')]
    public function render()
    {
        $grupos = Grupo::where('nombre', 'like', '%' . $this->search . '%')->paginate(10);

        return view('livewire.grupo.mostrar-grupos', compact('grupos'));
    }
}
