<?php

namespace App\Livewire\Institucional;

use App\Livewire\Institucional\Concerns\SeleccionaNivel;
use Livewire\Component;

class CredencialesInstitucionales extends Component
{
    use SeleccionaNivel;

    public string $tab = 'alumnos';

    public function mount(): void
    {
        $this->inicializarSelectorNivel();
    }

    public function seleccionarTab(string $tab): void
    {
        abort_unless(in_array($tab, ['alumnos', 'personal'], true), 404);
        $this->tab = $tab;
    }

    public function render()
    {
        return view('livewire.institucional.credenciales-institucionales');
    }
}
