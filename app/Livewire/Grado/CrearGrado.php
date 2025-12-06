<?php

namespace App\Livewire\Grado;

use Livewire\Component;

class CrearGrado extends Component
{
    public $nivel_id;
    public $nombre;

    public function guardarGrado(){
        $this->validate([
            'nivel_id' => 'required|exists:niveles,id',
            'nombre' => 'required|numeric|min:1|max:6',

        ],[
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',
            'nombre.required' => 'El grado es obligatorio.',
            'nombre.numeric' => 'El grado debe ser un número.',
            'nombre.min' => 'El  grado debe ser al menos :min.',
            'nombre.max' => 'El  grado no debe ser mayor a :max.',
        ]);

        $existe = \App\Models\Grado::where('nivel_id', $this->nivel_id)
            ->where('nombre', $this->nombre)
            ->exists();

        if ($existe) {
            $this->addError('nombre', 'El grado ya existe en este nivel.');
            return;
        }

        \App\Models\Grado::create([
            'nivel_id' => $this->nivel_id,
            'nombre' => $this->nombre,
        ]);


    $this->dispatch('swal', [
            'title' => '¡Grado creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'nivel_id',
            'nombre',
        ]);

        $this->dispatch('refreshGrados');
    }
    public function render()
    {
        $niveles = \App\Models\Nivel::orderBy('id')->get();
        return view('livewire.grado.crear-grado', compact('niveles'));
    }
}
