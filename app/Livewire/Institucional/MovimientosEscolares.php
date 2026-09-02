<?php

namespace App\Livewire\Institucional;

use App\Livewire\Institucional\Concerns\SeleccionaNivel;
use Livewire\Component;

class MovimientosEscolares extends Component
{
    use SeleccionaNivel;

    public string $tab = 'movimientos';

    public function mount(): void
    {
        $this->inicializarSelectorNivel();
    }

    public function seleccionarTab(string $tab): void
    {
        abort_unless(in_array($tab, ['movimientos', 'no-vigentes', 'reingreso'], true), 404);
        $this->tab = $tab;
    }

    public function render()
    {
        return view('livewire.institucional.movimientos-escolares');
    }
}
