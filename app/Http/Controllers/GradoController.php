<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use App\Http\Requests\StoreGradoRequest;
use App\Http\Requests\UpdateGradoRequest;

class GradoController extends Controller
{

    public function index()
    {
     return view("grados.index");

    }


}
