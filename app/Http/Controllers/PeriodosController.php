<?php

namespace App\Http\Controllers;

use App\Models\periodos;
class PeriodosController extends Controller
{

    public function index()
    {
        return view('periodos.index');
    }
}
