<?php

namespace App\Http\Controllers;

use App\Exports\CierreGeneracionExport;
use App\Models\ProcesoCierreCiclo;
use App\Models\ProcesoCierreCicloDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CierreGeneracionReporteController extends Controller
{
    public function reporte(ProcesoCierreCiclo $proceso, string $formato): Response|BinaryFileResponse
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);
        abort_unless($proceso->tipo === 'cierre_nivel_continuidad', 404);

        $proceso->load([
            'nivel',
            'generacion',
            'grupoOrigen.asignacionGrupo',
            'cicloEscolar',
            'cicloDestino',
            'usuarioRealizo',
            'detalles.inscripcion' => fn ($relacion) => $relacion->withTrashed(),
            'detalles.inscripcionCicloOrigen.grado',
            'detalles.inscripcionCicloOrigen.grupo.asignacionGrupo',
            'detalles.inscripcionCicloOrigen.semestre',
            'detalles.inscripcionCicloDestino.nivel',
            'detalles.inscripcionCicloDestino.grado',
            'detalles.inscripcionCicloDestino.generacion',
            'detalles.inscripcionCicloDestino.grupo.asignacionGrupo',
            'detalles.inscripcionCicloDestino.semestre',
            'detalles.proyeccionContinuidad.cicloDestino',
            'detalles.proyeccionContinuidad.nivelDestino',
            'detalles.proyeccionContinuidad.gradoDestino',
            'detalles.proyeccionContinuidad.semestreDestino',
            'detalles.proyeccionContinuidad.generacionDestino',
            'detalles.proyeccionContinuidad.grupoDestino.asignacionGrupo',
        ]);

        $base = sprintf(
            'cierre-academico-%s-%s-proceso-%d',
            $proceso->nivel?->slug ?: 'nivel',
            $proceso->generacion?->etiqueta ?: 'generacion',
            $proceso->id,
        );

        if ($formato === 'excel') {
            return Excel::download(new CierreGeneracionExport($proceso), $base.'.xlsx');
        }

        abort_unless($formato === 'pdf', 404);

        return Pdf::loadView('pdf.cierre-generacion-continuidad', [
            'proceso' => $proceso,
            'detallesPorResultado' => $proceso->detalles->groupBy('resultado'),
            'logo' => $this->logoDataUri(),
        ])->setPaper('letter', 'landscape')->download($base.'.pdf');
    }

    public function comprobante(ProcesoCierreCiclo $proceso, ProcesoCierreCicloDetalle $detalle): Response
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);
        abort_unless((int) $detalle->proceso_cierre_ciclo_id === (int) $proceso->id, 404);

        $detalle->load([
            'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
            'inscripcionCicloOrigen.nivel',
            'inscripcionCicloOrigen.grado',
            'inscripcionCicloOrigen.generacion',
            'inscripcionCicloOrigen.grupo.asignacionGrupo',
            'inscripcionCicloOrigen.semestre',
            'inscripcionCicloDestino.nivel',
            'inscripcionCicloDestino.grado',
            'inscripcionCicloDestino.generacion',
            'inscripcionCicloDestino.grupo.asignacionGrupo',
            'inscripcionCicloDestino.semestre',
            'proyeccionContinuidad.cicloDestino',
            'proyeccionContinuidad.nivelDestino',
            'proyeccionContinuidad.gradoDestino',
            'proyeccionContinuidad.semestreDestino',
            'proyeccionContinuidad.generacionDestino',
            'proyeccionContinuidad.grupoDestino.asignacionGrupo',
        ]);
        $proceso->load(['nivel', 'generacion', 'cicloEscolar', 'cicloDestino', 'usuarioRealizo']);

        $nombre = 'comprobante-cierre-academico-'.$detalle->inscripcion_id.'-'.$proceso->id.'.pdf';

        return Pdf::loadView('pdf.comprobante-cierre-generacion', [
            'proceso' => $proceso,
            'detalle' => $detalle,
            'logo' => $this->logoDataUri(),
        ])->setPaper('letter')->download($nombre);
    }

    private function logoDataUri(): ?string
    {
        $ruta = public_path('imagenes/logo-oficial-cum.png');
        if (! is_file($ruta)) {
            $ruta = public_path('logo.png');
        }
        if (! is_file($ruta)) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($ruta));
    }
}
