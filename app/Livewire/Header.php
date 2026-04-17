<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Header extends Component
{
    #[On('refreshHeader')]
    public function render()
    {
        $cicloEscolar = \App\Models\CicloEscolar::query()
            ->orderByDesc('id')
            ->first();
        return view('livewire.header', [
            'cicloEscolar' => $cicloEscolar,
        ]);
    }
}
