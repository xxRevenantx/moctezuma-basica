<?php

namespace App\Http\Controllers;

use App\Models\cicloEscolar;
use App\Http\Requests\StorecicloEscolarRequest;
use App\Http\Requests\UpdatecicloEscolarRequest;

class CicloEscolarController extends Controller
{

    public function index()
    {
        return view('ciclos-escolares.index');
    }
}
