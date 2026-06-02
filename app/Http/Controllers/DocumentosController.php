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


    // OFICIOS
    public function oficios()
    {
        return view('documentos.oficios');
    }
}
