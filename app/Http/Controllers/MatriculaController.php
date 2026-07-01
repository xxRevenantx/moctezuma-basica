<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    public function editar(Request $request, string $slug_nivel, Inscripcion $inscripcion)
    {
        $trayectoriaId = $request->integer('trayectoria_id') ?: null;

        return view('matricula.index', compact(
            'slug_nivel',
            'inscripcion',
            'trayectoriaId'
        ));
    }
}
