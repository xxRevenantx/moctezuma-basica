<?php

namespace App\Livewire\Director;

use App\Models\Director;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MostrarDirector extends Component
{
     use WithPagination;

    public $search = '';

    use WithFileUploads;

    public $archivo;

    public $erroresImportacion;


    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function eliminarDirectivo($id)
    {
        $directivo = Director::find($id);

        if ($directivo) {
            $directivo->delete();

            $this->dispatch('swal', [
            'title' => 'Directivo eliminado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
            ]);
        }
    }





    #[On('refreshDirectivos')]
    public function render()
    {
        $directivos = Director::where(function($query) {
                        $query->where('nombre', 'like', '%' . $this->search . '%')
                            ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                            ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                            ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ['%' . $this->search . '%'])
                            ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno) LIKE ?", ['%' . $this->search . '%'])
                            ->orWhere('telefono', 'like', '%' . $this->search . '%')
                            ->orWhere('correo', 'like', '%' . $this->search . '%')
                            ->orWhere('cargo', 'like', '%' . $this->search . '%');
                    })
                    ->orderBy('id', 'asc')
                    ->paginate(10);

        return view('livewire.director.mostrar-director', compact('directivos'));
    }
}
