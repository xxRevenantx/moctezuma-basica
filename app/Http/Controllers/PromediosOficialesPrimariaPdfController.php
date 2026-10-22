<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Escuela;
use App\Models\Nivel;
use App\Services\CalificacionOficialPrimariaService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Documentos\BladeWordExporter;
use Illuminate\Http\Request;

class PromediosOficialesPrimariaPdfController extends Controller
{
    public function __invoke(Request $request, CalificacionOficialPrimariaService $service)
    {
        $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
        ]);

        $nivel = Nivel::query()->where('slug', 'primaria')->firstOrFail();
        $ciclo = CicloEscolar::query()->findOrFail((int) $request->integer('ciclo_escolar_id'));
        $reporte = $service->reporteAnual(
            nivelId: (int) $nivel->id,
            cicloEscolarId: (int) $ciclo->id,
            generacionId: $request->filled('generacion_id') ? (int) $request->integer('generacion_id') : null,
            gradoId: $request->filled('grado_id') ? (int) $request->integer('grado_id') : null,
            grupoId: $request->filled('grupo_id') ? (int) $request->integer('grupo_id') : null,
        );

        $datosDocumento = [
            'reporte' => $reporte,
            'nivel' => $nivel,
            'ciclo' => $ciclo,
            'escuela' => Escuela::query()->first(),
        ];
        $nombre = 'PROMEDIOS_OFICIALES_PRIMARIA_' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio;
        if ($request->routeIs('generales.promedios-oficiales-primaria.word')) {
            return app(BladeWordExporter::class)->descargar('pdf.promedios-oficiales-primaria', $datosDocumento, $nombre.'.docx', 'landscape');
        }
        return Pdf::loadView('pdf.promedios-oficiales-primaria', $datosDocumento)
            ->setPaper('a3', 'landscape')->stream($nombre.'.pdf');
    }
}
