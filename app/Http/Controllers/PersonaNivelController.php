<?php

namespace App\Http\Controllers;

use App\Models\PersonaNivel;
use App\Http\Requests\StorePersonaNivelRequest;
use App\Http\Requests\UpdatePersonaNivelRequest;

class PersonaNivelController extends Controller
{

    public function plantilla()
    {
        return view('persona_nivel.index');
    }


}
