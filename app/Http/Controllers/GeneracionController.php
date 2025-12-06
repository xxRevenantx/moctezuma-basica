<?php

namespace App\Http\Controllers;

use App\Models\GeneracionBasica;
use App\Http\Requests\StoreGeneracionBasicaRequest;
use App\Http\Requests\UpdateGeneracionBasicaRequest;

class GeneracionController extends Controller
{

    public function index()
    {
        return view('generaciones.index');
    }
}
