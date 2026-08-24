<?php

namespace App\Livewire\Institucional;

use App\Livewire\Institucional\Concerns\SeleccionaNivel;
use Livewire\Component;

class ResultadosAcademicos extends Component
{
    use SeleccionaNivel;

    public string $tab = 'concentrado';

    public function mount(): void
    {
        $this->inicializarSelectorNivel();

        if ($this->slug_nivel === 'preescolar') {
            $this->tab = 'lugares';
        }
    }

    public function seleccionarTab(string $tab): void
    {
        $permitidos = ['concentrado', 'materias', 'oficial', 'lugares'];
        abort_unless(in_array($tab, $permitidos, true), 404);

        if ($this->slug_nivel === 'preescolar') {
            $tab = 'lugares';
        } elseif ($tab === 'oficial' && $this->slug_nivel !== 'primaria') {
            $tab = 'concentrado';
        } elseif ($tab === 'lugares') {
            $tab = 'concentrado';
        }

        $this->tab = $tab;
    }

    protected function alCambiarNivel(): void
    {
        if ($this->slug_nivel === 'preescolar') {
            $this->tab = 'lugares';
            return;
        }

        if (($this->tab === 'oficial' && $this->slug_nivel !== 'primaria') || $this->tab === 'lugares') {
            $this->tab = 'concentrado';
        }
    }

    public function render()
    {
        return view('livewire.institucional.resultados-academicos');
    }
}
