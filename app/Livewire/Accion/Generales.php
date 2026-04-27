<?php

namespace App\Livewire\Accion;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Nivel;
use Livewire\Component;
use Illuminate\Support\Collection;
class Generales extends Component
{

    public $nivel;

    public Collection $niveles;
    public Collection $grados;
    public Collection $generaciones;


    public string $slug_nivel = '';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderBy('anio_ingreso', 'desc')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);
    }
    public function render()
    {
        return view('livewire.accion.generales');
    }
}
