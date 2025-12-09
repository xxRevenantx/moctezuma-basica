<?php

namespace App\Livewire\PeriodoBasica;

use App\Models\Periodos_basico;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPeriodoBasica extends Component
{

    public $periodoId;
    public $ciclo_escolar_id;
    public $periodo_id;
    public $parcial_inicio;
    public $parcial_fin;

    public $open = false;

    public $periodo_nombre;

      #[On('editarModal')]
    public function editarModal($id)
    {


        $periodo_basica = Periodos_basico::findOrFail($id);

        $this->periodoId = $periodo_basica->id;
        $this->ciclo_escolar_id = $periodo_basica->ciclo_escolar_id;
        $this->periodo_id       = $periodo_basica->periodo_id;
        $this->parcial_inicio   = $periodo_basica->parcial_inicio;
        $this->parcial_fin      = $periodo_basica->parcial_fin;

        $this->periodo_nombre = \App\Models\Periodo::where('id', $this->periodo_id)->first()->nombre;




         $this->open = true;

        // Notificar a Alpine que ya se cargaron los datos
        $this->dispatch('editar-cargado');
    }


    public function actualizarPeriodo()
    {
        $this->validate([
            'ciclo_escolar_id' => 'required|exists:ciclo_escolares,id',
            'periodo_id' => 'required|exists:periodos,id',
            'parcial_inicio' => 'required|date',
            'parcial_fin' => 'required|date|after_or_equal:parcial_inicio',
        ], [
            'ciclo_escolar_id'=> 'required|exists:ciclo_escolares,id',
            'periodo_id' => 'required|exists:periodos,id',
            'parcial_inicio.required' => 'El campo fecha de inicio es obligatorio.',
            'parcial_inicio.date' => 'El campo fecha de inicio debe ser una fecha válida.',
            'parcial_fin.required' => 'El campo fecha de fin es obligatorio.',
            'parcial_fin.date' => 'El campo fecha de fin debe ser una fecha válida.',
            'parcial_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

        ]);

        $periodo_basica = Periodos_basico::where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo_id', $this->periodo_id)
            ->where('id', '!=', $this->periodoId)
            ->exists();

            if ($periodo_basica) {
            $this->addError('ciclo_escolar_id', 'El periodo básico ya existe para este ciclo escolar.');
            return;
            }

        $periodo_basica = Periodos_basico::findOrFail($this->periodoId);

        $periodo_basica->update([
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'periodo_id' => $this->periodo_id,
            'parcial_inicio' => $this->parcial_inicio,
            'parcial_fin' => $this->parcial_fin,
        ]);


        $this->dispatch('swal', [
            'title' => '¡Periodo básico actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);



        $this->dispatch('refreshPeriodosBasica');

         $this->dispatch('cerrar-modal-editar');
    }

    public function cerrarModal()
    {
        $this->reset([
            'open',
            'ciclo_escolar_id',
            'periodo_id',
            'parcial_inicio',
            'parcial_fin',
        ]);
        $this->resetValidation();
    }


    public function render()
    {
        $ciclosEscolares = \App\Models\CicloEscolar::all();
        $periodos = \App\Models\Periodo::all();
        return view('livewire.periodo-basica.editar-periodo-basica', compact('ciclosEscolares', 'periodos'));
    }
}
