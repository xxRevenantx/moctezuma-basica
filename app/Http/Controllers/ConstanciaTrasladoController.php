<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaTraslado;
use App\Services\ConstanciaTrasladoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ConstanciaTrasladoController extends Controller
{
    public function show(ConstanciaTraslado $constancia, ConstanciaTrasladoService $service)
    {
        abort_unless(auth()->user()?->is_admin, 403);

        if ($constancia->ruta_pdf && Storage::disk('local')->exists($constancia->ruta_pdf)) {
            $nombre = $constancia->documentoAlumno?->nombre_original
                ?: 'constancia-traslado-' . $constancia->folio . '.pdf';

            return response()->file(Storage::disk('local')->path($constancia->ruta_pdf), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $nombre) . '"',
            ]);
        }

        $constancia->load([
            'inscripcion.matriculasAlumno',
            'trayectoriaAcademica.nivel',
            'trayectoriaAcademica.grado',
            'trayectoriaAcademica.grupo.asignacionGrupo',
            'trayectoriaAcademica.generacion',
            'trayectoriaAcademica.semestre',
            'cicloEscolar',
        ]);

        $calificaciones = $service->calificacionesPara($constancia);
        $pdf = Pdf::loadView('pdf.constancia-traslado-calificaciones', compact('constancia', 'calificaciones'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('constancia-traslado-' . $constancia->folio . '.pdf');
    }
}
