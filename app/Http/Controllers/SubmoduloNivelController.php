<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use App\Models\Nivel;
use Illuminate\View\View;

class SubmoduloNivelController extends Controller
{
    public function submodulo(string $slug_nivel, string $accion): View
    {
        // Una sola consulta obtiene el catálogo y también valida la acción actual.
        $acciones = Accion::query()
            ->orderBy('id')
            ->get();

        $accionActual = $acciones->firstWhere('slug', $accion);

        abort_unless($accionActual, 404);

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        return view('seleccionar-accion.index', [
            'slug_nivel' => $slug_nivel,
            'accion' => $accionActual,
            'accionn' => $accionActual->accion,
            'acciones' => $acciones,
            'nivel' => $nivel,
        ]);
    }
}
