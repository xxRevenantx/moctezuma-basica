<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use App\Models\Nivel;
use Illuminate\Http\Request;

class SubmoduloNivelController extends Controller
{

    public function submodulo($slug_nivel, $accion)
    {

        $accion = Accion::where('slug', $accion)->firstOrFail();
        $acciones = Accion::all();
        $nivel = Nivel::where('slug', $slug_nivel)->firstOrFail();


        return view('seleccionar-accion.index', [
            'slug_nivel' => $slug_nivel,
            'accion' => $accion,
            'accionn' => $accion->accion,
            'acciones' => $acciones,
            'nivel' => $nivel,
        ]);
    }

}
