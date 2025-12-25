<?php

namespace App\Livewire\PersonaNivel;

use App\Models\PersonaNivel;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPersonaNivel extends Component
{

     public ?int $personaId = null;

    public ?int $nivelId = null;
    public ?int $gradoId = null;
    public ?int $grupoId = null;
    public $PersonasRoles = [];
    public $niveles = [];

    public $grados = [];
    public $grupos = [];

    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;



       public $open = false;

    #[On('editarModal')]
    public function editarModal($id)
    {
        $personal = PersonaNivel::findOrFail($id);

        $this->personaId = $personal->id;
        $this->nivelId = $personal->nivel_id;
        $this->gradoId = $personal->grado_id;
        $this->grupoId   = $personal->grupo_id;
        $this->ingreso_seg = $personal->ingreso_seg;
        $this->ingreso_sep = $personal->ingreso_sep;


        // dd($this->status);

        $this->open = true;

        $this->dispatch('editar-cargado');
    }



    // ACTUALIZAR PERSONAL
    public function actualizarPersonal()
    {
        $this->validate([

        ]);


        $personal = PersonaNivel::findOrFail($this->personaId);




        $personal->update([

        ]);


        $this->dispatch('swal', [
            'title' => 'Persona actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonaList');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }




    public function cerrarModal()
    {
        $this->reset([
            'open',


        ]);
    }


    public function render()
    {
        return view('livewire.persona-nivel.editar-persona-nivel');
    }
}
