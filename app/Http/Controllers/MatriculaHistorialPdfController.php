<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Nivel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MatriculaHistorialPdfController extends Controller
{
    public function __invoke(Request $request, string $slug_nivel)
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $query = $request->boolean('mostrar_archivados') ? Inscripcion::withTrashed() : Inscripcion::query();
        $query->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $nivel->id)
            ->when(
                $request->integer('generacion_id'),
                fn ($q, $id) => $q->where('generacion_id', $id),
                fn ($q) => $q->whereHas('generacion', fn ($g) => $g->where('status', true))
            )
            ->when($request->integer('grado_id'), fn ($q, $id) => $q->where('grado_id', $id))
            ->when($request->integer('semestre_id'), fn ($q, $id) => $q->where('semestre_id', $id))
            ->when($request->integer('grupo_id'), fn ($q, $id) => $q->where('grupo_id', $id))
            ->when($request->input('estatus', 'todos') !== 'todos', fn ($q) => $q->where('estatus', $request->input('estatus')))
            ->when(trim((string) $request->input('search')) !== '', function ($q) use ($request) {
                $t = '%' . trim((string) $request->input('search')) . '%';
                $q->where(fn ($s) => $s->where('matricula', 'like', $t)->orWhere('curp', 'like', $t)->orWhere('nombre', 'like', $t)->orWhere('apellido_paterno', 'like', $t)->orWhere('apellido_materno', 'like', $t));
            })
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre');
        $rows = $query->get();
        $resumen = [
            'total' => $rows->count(), 'hombres' => $rows->where('genero', 'H')->count(), 'mujeres' => $rows->where('genero', 'M')->count(),
            'activos' => $rows->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])->count(), 'egresados' => $rows->where('estatus', 'egresado')->count(),
        ];
        return Pdf::loadView('pdf.matricula-generaciones', compact('rows', 'nivel', 'resumen'))->setPaper('letter', 'landscape')->stream('padron_' . $slug_nivel . '.pdf');
    }
}
