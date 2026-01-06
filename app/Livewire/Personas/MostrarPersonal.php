<?php

namespace App\Livewire\Personas;

use App\Models\Persona;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MostrarPersonal extends Component
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


    public function eliminarPersonal($id)
    {
        $personal = Persona::find($id);

        if ($personal) {
            $personal->delete();

            $this->dispatch('swal', [
            'title' => '¡Personal eliminado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
            ]);
        }
    }





    #[On('refreshPersonal')]
    public function render()
    {
        $personal = Persona::where(function($query) {
                        $query->where('nombre', 'like', '%' . $this->search . '%')
                            ->orWhere('titulo','like', '%' . $this->search . '%')
                            ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                            ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                            ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ['%' . $this->search . '%'])
                            ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno) LIKE ?", ['%' . $this->search . '%'])
                            ->orWhere('telefono_movil', 'like', '%' . $this->search . '%')
                            ->orWhere('correo', 'like', '%' . $this->search . '%')
                            ->orWhere('curp', 'like', '%' . $this->search . '%')
                            ->orWhere('rfc', 'like', '%' . $this->search . '%');
                    })
                    ->orderBy('nombre', 'asc')
                    ->orderBy('apellido_paterno', 'asc')
                    ->orderBy('apellido_materno', 'asc')
                    ->paginate(10);



        return view('livewire.personas.mostrar-personal' , compact('personal'));
    }
}
