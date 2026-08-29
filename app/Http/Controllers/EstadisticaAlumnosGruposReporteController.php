<?php

namespace App\Http\Controllers;

use App\Exports\EstadisticaAlumnosGruposExport;
use App\Services\EstadisticaAlumnosGruposService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EstadisticaAlumnosGruposReporteController extends Controller
{
    public function __invoke(
        Request $request,
        string $formato,
        EstadisticaAlumnosGruposService $service,
    ): Response|BinaryFileResponse {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);
        abort_unless(in_array($formato, ['pdf', 'excel'], true), 404);

        $filtros = $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
        ]);

        $datos = $service->generar((int) $filtros['ciclo_escolar_id'], (int) $filtros['nivel_id']);
        $nombre = sprintf(
            'desglose-911-%s-%s-%s',
            Str::slug((string) data_get($datos, 'contexto.nivel', 'nivel')),
            Str::slug((string) data_get($datos, 'contexto.ciclo', 'ciclo')),
            now()->format('Ymd-His'),
        );

        if ($formato === 'excel') {
            return Excel::download(new EstadisticaAlumnosGruposExport($datos), $nombre.'.xlsx');
        }

        return Pdf::loadView('pdf.estadistica-alumnos-grupos', [
            'datos' => $datos,
            'logo' => $this->logoDataUri(),
        ])->setPaper('legal', 'landscape')->download($nombre.'.pdf');
    }

    private function logoDataUri(): ?string
    {
        foreach ([public_path('imagenes/logo-oficial-cum.png'), public_path('logo.png')] as $ruta) {
            if (is_file($ruta)) {
                $mime = mime_content_type($ruta) ?: 'image/png';
                return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($ruta));
            }
        }

        return null;
    }
}
