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

        // Evita que "fichas" quede activa si no es preescolar.
        if ($this->accionActual === 'fichas' && $this->slug_nivel !== 'preescolar') {
            $this->accionActual = null;
        }
    }

    public function ir(string $accion): void
    {
        // Preescolar no usa calificaciones; se envía a fichas.
        if ($accion === 'calificaciones' && $this->slug_nivel === 'preescolar') {
            $accion = 'fichas';
        }

        // Bloquea el acceso a fichas si no corresponde a preescolar.
        if ($accion === 'fichas' && $this->slug_nivel !== 'preescolar') {
            return;
        }

        $this->accionActual = $accion;

        if ($this->slug_nivel) {
            $parametros = [
                'slug_nivel' => $this->slug_nivel,
                'accion' => $accion,
            ];

            if ($this->slug_grado) {
                $parametros['slug_grado'] = $this->slug_grado;
            }

            $this->redirectRoute('submodulos.accion', $parametros, navigate: true);
            return;
        }

        $this->redirect(
            request()->fullUrlWithQuery(['accion' => $accion]),
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.accion.seleccionar-accion');
    }
}
