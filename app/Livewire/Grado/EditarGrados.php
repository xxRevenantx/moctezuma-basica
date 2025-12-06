<?php

namespace App\Livewire\Grado;

use App\Models\Grado;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGrados extends Component
{

    public $open = false;
    public $gradoId;
    public $nombre;
    public $nivel_id;



    #[On('editarModal')]
    public function editarModal($id)
    {
        $grado = Grado::findOrFail($id);
        $this->gradoId = $grado->id;
        $this->nombre = $grado->nombre;
        $this->nivel_id = $grado->nivel_id;


        $this->open = true;

        $this->dispatch('editar-cargado');
    }

    public function actualizarGrado()
    {
        $this->validate([
            'nivel_id' => 'required|exists:niveles,id',
            'nombre' => 'required|numeric|min:1|max:6',
        ], [
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',
            'nombre.required' => 'El nombre del grado es obligatorio.',
            'nombre.numeric' => 'El nombre del grado debe ser un número.',
            'nombre.min' => 'El nombre del grado debe ser al menos :min.',
            'nombre.max' => 'El nombre del grado no debe ser mayor que :max.',
        ]);


        $existe = \App\Models\Grado::where('nivel_id', $this->nivel_id)
            ->where('nombre', $this->nombre)
            ->exists();

        if ($existe) {
            $this->addError('nombre', 'El grado ya existe en este nivel.');
            return;
        }

        $grado = Grado::findOrFail($this->gradoId);



        $grado->update([
            'nombre' => $this->nombre,
            'nivel_id' => $this->nivel_id,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Grado actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshGrados');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }



    public function cerrarModal()
    {
        $this->reset([
            'open',
            'gradoId',
            'nombre',
            'nivel_id'

        ]);
    }



    public function render()
    {
        $niveles = \App\Models\Nivel::all();
        return view('livewire.grado.editar-grados', compact('niveles'));
    }
}
