<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;

class MatriculaController extends Controller
{
    public function editar(string $slug_nivel, Inscripcion $inscripcion)
    {
        return view('matricula.index', compact('slug_nivel', 'inscripcion'));
    }
}
