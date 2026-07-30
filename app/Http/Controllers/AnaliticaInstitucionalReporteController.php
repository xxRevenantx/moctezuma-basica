<?php

namespace App\Http\Controllers;

use App\Exports\AnaliticaInstitucionalExport;
use App\Services\AnaliticaInstitucionalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnaliticaInstitucionalReporteController extends Controller
{
    public function __invoke(Request $request, string $formato, AnaliticaInstitucionalService $service): Response|BinaryFileResponse
    {
        abort_unless(auth()->user()?->canAccess('analitica.exportar'), 403);
        abort_unless(in_array($formato, ['pdf', 'excel'], true), 404);

        $filtros = $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'ciclo_comparacion_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'nivel_id' => ['nullable', 'integer', 'exists:niveles,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
        ]);

        $datos = $service->generar($filtros);
        $nombre = 'analitica-institucional-'.str_replace(' ', '-', strtolower((string) data_get($datos, 'contexto.ciclo', 'ciclo'))).'-'.now()->format('Ymd-His');

        if ($formato === 'excel') {
            return Excel::download(new AnaliticaInstitucionalExport($datos), $nombre.'.xlsx');
        }

        return Pdf::loadView('pdf.analitica-institucional', [
            'datos' => $datos,
            'logo' => $this->logoDataUri(),
        ])->setPaper('letter', 'landscape')->download($nombre.'.pdf');
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
