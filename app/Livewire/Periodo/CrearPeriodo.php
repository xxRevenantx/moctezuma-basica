<?php

namespace App\Livewire\Periodo;

use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\Periodos;
use Livewire\Component;

class CrearPeriodo extends Component
{
    //variables publicas
    public $nivel_id;
    public $generacion_id;
    public $semestre_id;
    public $ciclo_escolar_id;

    public $mes_bachillerato_id;
    public $fecha_inicio;
    public $fecha_fin;


    // GUARDAR PERIODOS
    public function guardarPeriodo()
    {
        $this->validate([
            'nivel_id' => 'required|exists:niveles,id',
            "generacion_id" => "required|exists:generaciones,id",
            "semestre_id" => "required|exists:semestres,id",
            "ciclo_escolar_id" => "required|exists:ciclo_escolares,id",
            "mes_bachillerato_id" => "required|exists:meses_bachilleratos,id",
            "fecha_inicio" => "nullable|date",
            "fecha_fin" => "nullable|date|after:fecha_inicio",
        ], [
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',
            'generacion_id.required' => 'La generación es obligatoria.',
            'generacion_id.exists' => 'La generación seleccionada no es válida.',
            'semestre_id.required' => 'El semestre es obligatorio.',
            'semestre_id.exists' => 'El semestre seleccionado no es válido.',
            'ciclo_escolar_id.required' => 'El ciclo escolar es obligatorio.',
            'ciclo_escolar_id.exists' => 'El ciclo escolar seleccionado no es válido.',
            'mes_bachillerato_id.required' => 'El mes es obligatorio.',
            'mes_bachillerato_id.exists' => 'El mes seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio no es una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin no es una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser una fecha posterior a la fecha de inicio.',
        ]);



        $existe = Periodos::where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('semestre_id', $this->semestre_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('mes_bachillerato_id', $this->mes_bachillerato_id)
            ->exists();

        if ($existe) {
            $this->addError('ciclo_escolar_id', 'El periodo para esta asignación ya existe.');
            return;
        }

        \App\Models\Periodos::create([
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->semestre_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'mes_bachillerato_id' => $this->mes_bachillerato_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ]);


        $this->dispatch('swal', [
            'title' => '¡Periodo creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'nivel_id',
            'generacion_id',
            'semestre_id',
            'ciclo_escolar_id',
            'mes_bachillerato_id',
            'fecha_inicio',
            'fecha_fin',
        ]);

        $this->dispatch('refreshPeriodos');
    }



    public function render()
    {
        $meses = MesesBachillerato::all();
        $niveles = \App\Models\Nivel::all();
        $generaciones = Generacion::all();
        $semestres = \App\Models\Semestre::all();
        $ciclosEscolares = \App\Models\CicloEscolar::all();

        return view('livewire.periodo.crear-periodo', compact('niveles', 'generaciones', 'semestres', 'ciclosEscolares', 'meses'));
    }
}
