<?php

namespace App\Livewire\PeriodoBachillerato;

use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\Periodo;
use App\Models\PeriodosBachillerato;
use Livewire\Component;

class CrearPeriodoBachillerato extends Component
{
    //variables publicas
    public $generacion_id;
    public $semestre_id;
    public $ciclo_escolar_id;

    public $mes_id;
    public $fecha_inicio;
    public $fecha_fin;


    // GUARDAR PERIODO BACHILLERATO
    public function guardarPeriodoBachillerato(){
         $this->validate([
            "generacion_id"=> "required|exists:generaciones,id",
            "semestre_id"=> "required|exists:semestres,id",
            "ciclo_escolar_id"=> "required|exists:ciclo_escolares,id",
            "mes_id"=> "required|exists:meses_bachilleratos,id",
            "fecha_inicio"=> "nullable|date",
            "fecha_fin"=> "nullable|date|after:fecha_inicio",
        ], [
            'generacion_id.required' => 'La generación es obligatoria.',
            'generacion_id.exists' => 'La generación seleccionada no es válida.',
            'semestre_id.required' => 'El semestre es obligatorio.',
            'semestre_id.exists' => 'El semestre seleccionado no es válido.',
            'ciclo_escolar_id.required' => 'El ciclo escolar es obligatorio.',
            'ciclo_escolar_id.exists' => 'El ciclo escolar seleccionado no es válido.',
            'mes_id.required' => 'El mes es obligatorio.',
            'mes_id.exists' => 'El mes seleccionado no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio no es una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin no es una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser una fecha posterior a la fecha de inicio.',
        ]);



          $existe = PeriodosBachillerato::where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('semestre_id', $this->semestre_id)
            ->exists();

        if ($existe) {
            $this->addError('ciclo_escolar_id', 'El periodo de bachillerato para la generación y semestre seleccionados ya existe en este ciclo escolar.');
            return;
        }

        \App\Models\PeriodosBachillerato::create([
            'generacion_id'=> $this->generacion_id,
            'semestre_id'=> $this->semestre_id,
            'ciclo_escolar_id'=> $this->ciclo_escolar_id,
            'mes_id'=> $this->mes_id,
            'fecha_inicio'=> $this->fecha_inicio,
            'fecha_fin'=> $this->fecha_fin,
        ]);


    $this->dispatch('swal', [
            'title' => '¡Periodo bachillerato creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'generacion_id',
            'semestre_id',
            'ciclo_escolar_id',
            'mes_id',
            'fecha_inicio',
            'fecha_fin',
        ]);

        $this->dispatch('refreshPeriodosBachillerato');
    }



    public function render()
    {
        $meses = MesesBachillerato::all();
        $generaciones = Generacion::where('nivel_id', 4)->orderBy('anio_ingreso','asc')->get();
        $semestres = \App\Models\Semestre::all();
        $ciclosEscolares = \App\Models\CicloEscolar::all();

        return view('livewire.periodo-bachillerato.crear-periodo-bachillerato', compact('generaciones', 'semestres', 'ciclosEscolares', 'meses'));
    }
}
