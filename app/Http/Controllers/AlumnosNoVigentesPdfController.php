<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Services\AlumnosNoVigentesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlumnosNoVigentesPdfController extends Controller
{
    public function __invoke(Request $request, AlumnosNoVigentesService $service): Response
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $request->merge([
            'slug_nivel' => (string) $request->route('slug_nivel'),
        ]);

        $datos = $request->validate([
            'slug_nivel' => ['required', 'string', 'exists:niveles,slug'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'categoria' => ['nullable', 'string', 'in:' . implode(',', AlumnosNoVigentesService::CATEGORIAS)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $nivel = Nivel::query()->where('slug', $datos['slug_nivel'])->firstOrFail();
        $ciclo = CicloEscolar::query()->findOrFail((int) $datos['ciclo_escolar_id']);
        $filtros = [
            'categoria' => $datos['categoria'] ?? 'todos',
            'generacion_id' => $datos['generacion_id'] ?? null,
            'grado_id' => $datos['grado_id'] ?? null,
            'semestre_id' => $datos['semestre_id'] ?? null,
            'grupo_id' => $datos['grupo_id'] ?? null,
            'search' => $datos['search'] ?? '',
        ];

        $alumnos = $service->query($nivel, $ciclo->id, $filtros)->get();
        $categoria = $service->etiquetaCategoria($filtros['categoria']);

        $pdf = Pdf::loadView('pdf.alumnos-no-vigentes', compact(
            'alumnos',
            'nivel',
            'ciclo',
            'categoria',
            'service'
        ))->setPaper('letter', 'landscape');

        $nombre = sprintf(
            'alumnos_no_vigentes_%s_%s_%s.pdf',
            $nivel->slug,
            $ciclo->inicio_anio . '_' . $ciclo->fin_anio,
            str($filtros['categoria'])->slug('_')
        );

        return $pdf->stream($nombre);
    }
}
