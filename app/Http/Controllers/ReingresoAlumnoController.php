<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\View\View;

class ReingresoAlumnoController extends Controller
{
    public function __invoke(string $slug_nivel): View
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);

        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        return view('reingreso-alumno.index', compact('nivel', 'slug_nivel'));
    }
}
