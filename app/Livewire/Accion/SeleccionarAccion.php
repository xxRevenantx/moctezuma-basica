<?php

namespace App\Livewire\Accion;

use Livewire\Component;

class SeleccionarAccion extends Component
{
    public $acciones; // Collection|array (viene del controller)

    public ?string $slug_nivel = null;
    public ?string $slug_grado = null;

    public ?string $accionActual = null;

    // badges opcionales (ej. bajas)
    public array $badges = [
        'bajas' => 0,
    ];

    public function mount($acciones, $slug_nivel = null, $badges = null): void
    {
        $this->acciones   = $acciones;
        $this->slug_nivel = $slug_nivel;


        if (is_array($badges)) {
            $this->badges = array_merge($this->badges, $badges);
        }

        // acción actual desde ruta {accion} o query ?accion=
        $this->accionActual = request()->route('accion') ?? request('accion');
    }

    public function ir(string $accion): void
    {
        // Guardamos estado activo (por si renderiza algo abajo inmediatamente)
        $this->accionActual = $accion;

        // Navegación SPA (sin recarga) con Livewire v3
        if ($this->slug_nivel) {
            $this->redirectRoute('submodulos.accion', [
                'slug_nivel' => $this->slug_nivel,
                'accion'     => $accion,
            ], navigate: true);

            return;
        }

        // Fallback: querystring
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
