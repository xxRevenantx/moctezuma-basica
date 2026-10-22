<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Escuela;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\CalificacionOficialPrimariaService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Documentos\BladeWordExporter;
use Illuminate\Http\Request;

class BoletaOficialPrimariaController extends Controller
{
    public function __invoke(
        Request $request,
        Inscripcion $inscripcion,
        CalificacionOficialPrimariaService $service,
    ) {
        $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
        ]);

        $nivel = Nivel::query()->where('slug', 'primaria')->firstOrFail();
        $ciclo = CicloEscolar::query()->findOrFail((int) $request->integer('ciclo_escolar_id'));

        $reporte = $service->reporteAnual(
            nivelId: (int) $nivel->id,
            cicloEscolarId: (int) $ciclo->id,
            generacionId: $request->filled('generacion_id') ? (int) $request->integer('generacion_id') : null,
            gradoId: (int) $request->integer('grado_id'),
            grupoId: (int) $request->integer('grupo_id'),
            inscripcionId: (int) $inscripcion->id,
        );

        $alumno = collect($reporte['alumnos'])->first();
        abort_if(! $alumno, 404, 'No se encontraron calificaciones oficiales para el alumno.');

        $escuela = Escuela::query()->first();

        $datosDocumento = [
            'alumnoModel' => $inscripcion,
            'alumno' => $alumno,
            'campos' => $reporte['campos'],
            'nivel' => $nivel,
            'ciclo' => $ciclo,
            'escuela' => $escuela,
        ];
        $nombre = 'BOLETA_OFICIAL_' . $inscripcion->matricula . '_' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio;
        if ($request->routeIs('calificaciones.boleta-oficial-primaria.word')) {
            return app(BladeWordExporter::class)->descargar('pdf.boleta-oficial-primaria', $datosDocumento, $nombre.'.docx', 'landscape');
        }
        return Pdf::loadView('pdf.boleta-oficial-primaria', $datosDocumento)
            ->setPaper('letter', 'landscape')->stream($nombre.'.pdf');
    }
}
