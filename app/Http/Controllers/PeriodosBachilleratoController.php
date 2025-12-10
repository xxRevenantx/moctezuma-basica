<?php

namespace App\Http\Controllers;

use App\Models\PeriodosBachillerato;
use App\Http\Requests\StorePeriodosBachilleratoRequest;
use App\Http\Requests\UpdatePeriodosBachilleratoRequest;

class PeriodosBachilleratoController extends Controller
{

    public function index()
    {
        return view('periodos-bachillerato.index');
    }


}
