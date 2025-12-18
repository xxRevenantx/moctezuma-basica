<?php

namespace App\Livewire\RolePersona;

use App\Models\RolePersona;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MostrarRolePersona extends Component
{
    use WithPagination;

    use WithFileUploads;

    public $search = '';

    public $archivo;

    public $erroresImportacion;


    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function eliminarPersonal($id)
    {
        $personal = RolePersona::find($id);

        if ($personal) {
            $personal->delete();

            $this->dispatch('swal', [
                'title' => '¡Role de la Persona eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        }
    }

    #[On('refreshRolePersona')]
    public function render()
    {
        $rolesPersona = RolePersona::with('rolesPersona')
            ->where('nombre', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);
        return view('livewire.role-persona.mostrar-role-persona', compact('rolesPersona'));
    }
}
