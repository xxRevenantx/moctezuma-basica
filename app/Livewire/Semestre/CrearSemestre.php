<?php

namespace App\Livewire\Semestre;

use App\Models\Grado;
use Livewire\Component;

class CrearSemestre extends Component
{
    public $numero;
    public $grado_id;

    public $mes_id;


    public function guardarSemestre()
    {
        $this->validate([
            'numero' => 'required|integer|min:1|max:6',
            'grado_id' => 'required|exists:grados,id',
            'mes_id' => 'required|exists:meses_bachilleratos,id',
        ],[
            'numero.required' => 'El número del semestre es obligatorio.',
            'numero.integer' => 'El número del semestre debe ser un número entero.',
            'numero.min' => 'El número del semestre debe ser al menos 1.',
            'numero.max' => 'El número del semestre no puede ser mayor a 6.',
            'grado_id.required' => 'El grado es obligatorio.',
            'grado_id.exists' => 'El grado seleccionado no es válido.',
            'mes_id.required' => 'Los meses de bachillerato son obligatorios.',
            'mes_id.exists' => 'Los meses de bachillerato seleccionados no son válidos.',
        ]);

        // VERIFICAR QUE NO EXISTA EL SEMESTRE EN ESE GRADO
         $existe = \App\Models\Semestre::where('numero', $this->numero)
            ->where('grado_id', $this->grado_id)
            ->exists();

        if ($existe) {
            $this->addError('numero', 'El semestre ya existe en este grado.');
            return;
        }

        \App\Models\Semestre::create([
            'numero' => $this->numero,
            'grado_id' => $this->grado_id,
            'mes_id' => $this->mes_id,
        ]);


    $this->dispatch('swal', [
            'title' => 'Semestre creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'numero',
            'grado_id',
            'mes_id',
        ]);

        $this->dispatch('refreshSemestres');
    }

    public function render()
    {
        $grados = Grado::whereHas('nivel', function($query) {
            $query->where('slug', 'bachillerato');
        })->get();

        $mesesBachilleratos = \App\Models\MesesBachillerato::all();

        return view('livewire.semestre.crear-semestre', compact('grados', 'mesesBachilleratos'));
    }
}
