<?php

namespace App\Http\Controllers\Basica;
use App\Http\Controllers\Controller;

use App\Models\GeneracionBasica;
use App\Http\Requests\StoreGeneracionBasicaRequest;
use App\Http\Requests\UpdateGeneracionBasicaRequest;

class GeneracionBasicaController extends Controller
{

    public function index()
    {
        return view('basica.generaciones.index');
    }


}
