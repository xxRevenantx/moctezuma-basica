<?php

namespace App\Livewire\ImageProfile;

use Livewire\Attributes\On;
use Livewire\Component;

class ImageProfile extends Component
{

    #[On('refreshHeader')]
    public function render()
    {
        return view('livewire.image-profile.image-profile');
    }
}
