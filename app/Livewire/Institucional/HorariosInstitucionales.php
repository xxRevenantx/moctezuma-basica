<?php

namespace App\Livewire\Institucional;

use App\Livewire\Institucional\Concerns\SeleccionaNivel;
use Livewire\Component;

class HorariosInstitucionales extends Component
{
    use SeleccionaNivel;

    public string $tab = 'grupo';

    public function mount(): void
    {
        $this->inicializarSelectorNivel();
    }

    public function seleccionarTab(string $tab): void
    {
        abort_unless(in_array($tab, [
            'grupo',
            'generales',
            'vacios',
            'docentes',
            'carga_docente',
            'planificador',
            'talleres',
        ], true), 404);

        if (in_array($tab, ['carga_docente', 'talleres'], true) && $this->slug_nivel !== 'secundaria') {
            $tab = 'grupo';
        }

        $this->tab = $tab;
    }

    protected function alCambiarNivel(): void
    {
        if (in_array($this->tab, ['carga_docente', 'talleres'], true) && $this->slug_nivel !== 'secundaria') {
            $this->tab = 'grupo';
        }
    }

    public function render()
    {
        return view('livewire.institucional.horarios-institucionales');
    }
}
