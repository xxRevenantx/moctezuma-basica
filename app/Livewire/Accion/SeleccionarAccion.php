<?php

namespace App\Livewire\Accion;

use Livewire\Component;

class SeleccionarAccion extends Component
{
    public $acciones;

    public ?string $slug_nivel = null;
    public ?string $slug_grado = null;
    public ?string $accionActual = null;

    public array $badges = [
        'bajas' => 0,
    ];

    public function mount($acciones, $slug_nivel = null, $slug_grado = null, $badges = null): void
    {
        $this->acciones = $acciones;
        $this->slug_nivel = $slug_nivel;
        $this->slug_grado = $slug_grado;

        if (is_array($badges)) {
            $this->badges = array_merge($this->badges, $badges);
        }

        $this->accionActual = request()->route('accion') ?? request('accion');

        // Preescolar trabaja con fichas, no con calificaciones.
        if ($this->slug_nivel === 'preescolar' && $this->accionActual === 'calificaciones') {
            $this->accionActual = 'fichas';
        }

        // Evita que fichas quede activa fuera de preescolar.
        if ($this->accionActual === 'fichas' && $this->slug_nivel !== 'preescolar') {
            $this->accionActual = null;
        }
    }

    public function render()
    {
        return view('livewire.accion.seleccionar-accion');
    }
}
