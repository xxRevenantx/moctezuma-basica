<?php

namespace App\Livewire\Generacion;

use App\Models\Generacion;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGeneracion extends Component
{
    public $open = false;
    public $generacionId;
    public $anio_ingreso;
    public $anio_egreso;
    public $nivel_id;



    #[On('editarModal')]
    public function editarModal($id)
    {
        $generacion = Generacion::findOrFail($id);
        $this->generacionId = $generacion->id;
        $this->anio_ingreso = $generacion->anio_ingreso;
        $this->anio_egreso = $generacion->anio_egreso;
        $this->nivel_id = $generacion->nivel_id;


        $this->open = true;

        $this->dispatch('editar-cargado');
    }

    public function actualizarGeneracion()
    {
        $this->validate([
            'anio_ingreso' => 'required|integer|min:0|digits:4',
            'anio_egreso' => 'required|integer|min:0|digits:4|gt:anio_ingreso',
            'nivel_id' => 'required|exists:niveles,id',
        ], [
            'anio_ingreso.required' => 'La fecha de inicio es obligatorio.',
            'anio_ingreso.integer' => 'La fecha de inicio debe ser un número entero.',
            'anio_ingreso.min' => 'La fecha de inicio no puede ser negativa.',
            'anio_ingreso.digits' => 'La fecha de inicio debe tener 4 dígitos.',
            'anio_egreso.required' => 'La fecha de término es obligatoria.',
            'anio_egreso.integer' => 'La fecha de término debe ser un número entero.',
            'anio_egreso.min' => 'La fecha de término no puede ser negativa.',
            'anio_egreso.digits' => 'La fecha de término debe tener 4 dígitos.',
            'nivel_id.required' => 'El nivel educativo es obligatorio.',
            'nivel_id.exists' => 'El nivel educativo seleccionado no es válido.',
            'anio_egreso.gt' => 'La fecha de término debe ser mayor que la fecha de inicio.',
            'anio_ingreso.unique' => 'La fecha de inicio ya existe en otra generación.',
            'anio_egreso.unique' => 'La fecha de término ya existe en otra generación.',
        ]);


        $existe = \App\Models\Generacion::where('nivel_id', $this->nivel_id)
            ->where('anio_ingreso', $this->anio_ingreso)
            ->where('anio_egreso', $this->anio_egreso)
                ->where('id', '!=', $this->generacionId)
            ->exists();

        if ($existe) {
               $this->dispatch('swal', [
            'title' => 'Ya existe una generación en este nivel',
            'icon' => 'error',
            'position' => 'top',
        ]);

            return;
        }

        $generacion = Generacion::findOrFail($this->generacionId);



        $generacion->update([
            'anio_ingreso' => $this->anio_ingreso,
            'anio_egreso' => $this->anio_egreso,
            'nivel_id' => $this->nivel_id,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Generación actualizada correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshGeneraciones');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }



    public function cerrarModal()
    {
        $this->reset([
            'open',
            'generacionId',
            'anio_ingreso',
            'anio_egreso',
            'nivel_id'
        ]);
            $this->resetValidation();
    }


    public function render()
    {
        $niveles = \App\Models\Nivel::all();
        return view('livewire.generacion.editar-generacion', compact('niveles'));
    }
}
