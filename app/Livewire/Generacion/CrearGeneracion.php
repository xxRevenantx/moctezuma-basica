<?php

namespace App\Livewire\Generacion;

use App\Models\Nivel;
use Livewire\Component;

class CrearGeneracion extends Component
{

    public $anio_ingreso;
    public $anio_egreso;
    public $nivel_id;

    public function guardarGeneracion()
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

        ]);

        // VERIFICAR SI YA EXISTE LA GENERACION
        $existe = \App\Models\Generacion::where('nivel_id', $this->nivel_id)
            ->where('anio_ingreso', $this->anio_ingreso)
            ->where('anio_egreso', $this->anio_egreso)
            ->exists();

        if ($existe) {
            $this->addError('anio_ingreso', 'La generación ya existe en este nivel.');
            return;
        }

        \App\Models\Generacion::create([
            'anio_ingreso' => $this->anio_ingreso,
            'anio_egreso' => $this->anio_egreso,
            'nivel_id' => $this->nivel_id,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Generación creada correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'anio_ingreso',
            'anio_egreso',
            'nivel_id',
        ]);

        $this->dispatch('refreshGeneraciones');
    }



    public function render()
    {
        $niveles = Nivel::all();
        return view('livewire.generacion.crear-generacion', compact('niveles'));
    }
}
