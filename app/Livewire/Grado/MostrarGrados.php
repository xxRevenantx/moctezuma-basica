<?php

namespace App\Livewire\Grado;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGrados extends Component
{


    use WithPagination;

    public $search = '';

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }



    #[On('refreshGrados')]
    public function render()
    {
        $grados = \App\Models\Grado::with('nivel')
            ->when($this->search, function ($query) {
                $query->whereHas('nivel', function ($q) {
                    $q->where('nombre', 'like', "%{$this->search}%");
                })->orWhere('nombre', 'like', "%{$this->search}%");
            })
            ->orderBy('nivel_id')
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.grado.mostrar-grados', compact('grados'));
    }
}
