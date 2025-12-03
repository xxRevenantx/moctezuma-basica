<?php

namespace App\Http\Controllers;

use App\Models\Escuela;
use App\Http\Requests\StoreEscuelaRequest;
use App\Http\Requests\UpdateEscuelaRequest;

class EscuelaController extends Controller
{

    public function index()
    {
        return view("escuela.index");
    }


}
