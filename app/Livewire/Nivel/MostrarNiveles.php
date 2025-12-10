<?php

namespace App\Livewire\Nivel;

use App\Models\Nivel;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarNiveles extends Component
{
    use WithPagination;

    public $search = '';

    // Cuando cambie el buscador, regresa a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }




    public function eliminar($id)
    {
        $nivel = Nivel::find($id);

        if ($nivel) {
            $nivel->delete();

            $this->dispatch('swal', [
                'title' => 'Nivel eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        }
    }


    #[On('refreshNiveles')]
    public function render()
    {
        $niveles = Nivel::with(['director', 'supervisor'])
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', "%{$this->search}%")
                    ->orWhere('cct', 'like', "%{$this->search}%")
                    ->orWhereHas('director', function ($q) {
                        $q->where('nombre', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('supervisor', function ($q) {
                        $q->where('nombre', 'like', "%{$this->search}%");
                    });
            })
            ->orderBy('id')
            ->paginate(10);

        return view('livewire.nivel.mostrar-niveles', compact('niveles'));
    }
}
