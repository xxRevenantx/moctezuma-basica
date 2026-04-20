<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    public function editar($slug_nivel, $inscripcion)
    {


        // Retorna una vista con el formulario de edición, pasando la matrícula encontrada
        return view('matricula.index', compact('slug_nivel', 'inscripcion'));
    }
}
