<?php

namespace App\Livewire\Institucional;

use App\Livewire\Institucional\Concerns\SeleccionaNivel;
use Livewire\Component;

class CargaAcademica extends Component
{
    use SeleccionaNivel;

    public function mount(): void
    {
        $this->inicializarSelectorNivel();
    }

    public function render()
    {
        return view('livewire.institucional.carga-academica');
    }
}
