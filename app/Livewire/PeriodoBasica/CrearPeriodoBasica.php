<?php

namespace App\Livewire\PeriodoBasica;

use Livewire\Component;

class CrearPeriodoBasica extends Component
{


    public $ciclo_escolar_id;
    public $periodo_id;
    public $parcial_inicio;
    public $parcial_fin;


    // GUARDAR PERIODO BASICA
    public function guardarPeriodoBasico()
    {
        $this->validate([
            'ciclo_escolar_id' => 'required|exists:ciclo_escolares,id',
            'periodo_id' => 'required|exists:periodos,id',
            'parcial_inicio' => 'required|date',
            'parcial_fin' => 'required|date|after_or_equal:parcial_inicio',
        ], [
            'ciclo_escolar_id.required' => 'El campo ciclo escolar es obligatorio.',
            'ciclo_escolar_id.exists' => 'El ciclo escolar seleccionado no es válido.',
            'periodo_id.required' => 'El campo período es obligatorio.',
            'periodo_id.exists' => 'El período seleccionado no es válido.',
            'parcial_inicio.required' => 'El campo fecha de inicio es obligatorio.',
            'parcial_inicio.date' => 'El campo fecha de inicio debe ser una fecha válida.',
            'parcial_fin.required' => 'El campo fecha de fin es obligatorio.',
            'parcial_fin.date' => 'El campo fecha de fin debe ser una fecha válida.',
            'parcial_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);
    }

    public function render()
    {

        $ciclosEscolares = \App\Models\CicloEscolar::all();
        $periodos = \App\Models\Periodo::all();
        return view('livewire.periodo-basica.crear-periodo-basica', compact('ciclosEscolares', 'periodos'));
    }
}
