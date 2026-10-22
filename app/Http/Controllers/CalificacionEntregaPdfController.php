<?php

namespace App\Http\Controllers;

use App\Models\CalificacionEntrega;
use App\Services\Documentos\BladeWordExporter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalificacionEntregaPdfController extends Controller
{
    public function __invoke(CalificacionEntrega $entrega)
    {
        $user = auth()->user();
        abort_unless($user?->is_admin || (int) $entrega->user_id === (int) $user?->id, 403);
        abort_unless($entrega->pdf_disk && $entrega->pdf_path, 404, 'El comprobante no está disponible.');
        abort_unless(Storage::disk($entrega->pdf_disk)->exists($entrega->pdf_path), 404, 'El archivo PDF no existe.');

        if (request()->routeIs('docente.entregas.word')) {
            // La versión Word procede del snapshot inmutable, no de calificaciones actuales.
            $entrega->load([
                'detalles', 'periodo.periodoBasica:id,periodo,descripcion',
                'periodo.parcialBachillerato:id,parcial,descripcion',
                'cicloEscolar', 'nivel:id,nombre', 'generacion',
                'grado:id,nombre', 'grupo.asignacionGrupo:id,nombre', 'semestre:id,numero',
            ]);
            return app(BladeWordExporter::class)->descargar('pdf.calificacion-entrega-docente', [
                'entrega' => $entrega,
                'materias' => $entrega->detalles->groupBy('asignacion_materia_id'),
            ], $entrega->folio.'.docx', 'landscape');
        }

        return Storage::disk($entrega->pdf_disk)->download(
            $entrega->pdf_path,
            $entrega->folio.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
