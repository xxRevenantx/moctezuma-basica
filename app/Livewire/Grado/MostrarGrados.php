<?php

namespace App\Livewire\Grado;

use Livewire\Attributes\On;
use Livewire\Component;

class MostrarGrados extends Component
{
    #[On('refreshGrados')]
    public function render()
    {
        $grados = \App\Models\Grado::with('nivel')
            ->orderBy('orden')
            ->paginate(10);

        return view('livewire.grado.mostrar-grados', compact('grados') );
    }
}
