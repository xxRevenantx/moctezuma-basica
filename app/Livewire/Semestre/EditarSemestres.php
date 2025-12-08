<?php

namespace App\Livewire\Semestre;

use App\Models\Grado;
use App\Models\Semestre;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarSemestres extends Component
{
     public $open = false;
    public $semestreId;
    public $numero;
    public $grado_id;
    public $mes_id;



    #[On('editarModal')]
    public function editarModal($id)
    {
        $semestre = Semestre::findOrFail($id);
        $this->semestreId = $semestre->id;
        $this->numero = $semestre->numero;
        $this->grado_id = $semestre->grado_id;
        $this->mes_id = $semestre->mes_id;


        $this->open = true;

        $this->dispatch('editar-cargado');
    }

    public function actualizarSemestre()
    {
        $this->validate([
            'numero' => 'required|integer|min:1|max:6',
            'grado_id' => 'required|exists:grados,id',
            'mes_id' => 'required|exists:meses_bachilleratos,id',

        ], [
            'numero'=> 'El número del semestre es obligatorio y debe estar entre 1 y 6.',
            'grado_id.required' => 'El grado es obligatorio.',
            'grado_id.exists' => 'El grado seleccionado no es válido.',
            'mes_id.required' => 'El mes es obligatorio.',
            'mes_id.exists' => 'El mes seleccionado no es válido.',
        ]);


        $existe = \App\Models\Semestre::where('numero', $this->numero)
            ->where('grado_id', $this->grado_id)
            ->where('mes_id', $this->mes_id)
            ->where('id', '!=', $this->semestreId)
            ->exists();

        if ($existe) {
            $this->addError('numero', 'El semestre ya existe en este grado y mes.');
            return;
        }

        $semestre = Semestre::findOrFail($this->semestreId);



        $semestre->update([
            'numero' => $this->numero,
            'grado_id' => $this->grado_id,
            'mes_id' => $this->mes_id,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Semestre actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshSemestres');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }



    public function cerrarModal()
    {
        $this->reset([
            'open',
           'semestreId',
            'numero',
            'grado_id',
            'mes_id',

        ]);
    }


    public function render()
    {
         $grados = Grado::whereHas('nivel', function($query) {
            $query->where('slug', 'bachillerato');
        })->get();
        $mesesBachilleratos = \App\Models\MesesBachillerato::all();
        return view('livewire.semestre.editar-semestres', compact('grados', 'mesesBachilleratos'));
    }
}
