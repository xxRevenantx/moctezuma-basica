<?php

namespace App\Livewire\Semestre;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarSemestres extends Component
{

    use WithPagination;

    public $search = '';

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[On('refreshSemestres')]
    public function render()
    {
        $semestres = \App\Models\Semestre::where('numero', 'like', '%' . $this->search . '%')
            ->with('mesesBachillerato')
            ->orderBy('grado_id')
            ->paginate(10);



        return view('livewire.semestre.mostrar-semestres', compact('semestres'));
    }
}
