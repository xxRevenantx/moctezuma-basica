<?php

namespace App\Http\Controllers;

use App\Models\periodos_basico;
use App\Http\Requests\Storeperiodos_basicoRequest;
use App\Http\Requests\Updateperiodos_basicoRequest;

class PeriodosBasicoController extends Controller
{

    public function index()
    {
        return view('periodos-basica.index');
    }
}
