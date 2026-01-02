<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use App\Models\Nivel;
use Illuminate\Http\Request;

class SeleccionarNivelController extends Controller
{

    public function index($slug_nivel)
    {
        return view('seleccionar-nivel.index', [
            'slug_nivel' => $slug_nivel
        ]);
    }

}
