<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentosController extends Controller
{
    // CONSTANCIAS
    public function constancias()
    {
        return view('documentos.constancias');
    }


    // CARTAS COMPROMISO
    public function cartasCompromiso()
    {
        return view('documentos.cartas-compromiso');
    }

    // OFICIOS
    public function oficios()
    {
        return view('documentos.oficios');
    }
}
