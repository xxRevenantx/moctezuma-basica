<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class NavNiveles extends Component
{
    #[On('refreshNiveles')]
    public function render()
    {
        $niveles = \App\Models\Nivel::orderBy('id')->get();
        return view('livewire.nav-niveles', compact('niveles') );
    }
}
