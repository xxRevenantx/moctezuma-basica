<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;

class PersonaController extends Controller
{

    public function index()
    {
        return view('persona.index');
    }


    public function rolePersona()
    {
        return view('role-persona.index');
    }
}
