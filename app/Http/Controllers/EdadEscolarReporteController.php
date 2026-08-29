<?php

namespace App\Http\Controllers;

use App\Exports\EdadEscolarExport;
use App\Services\EdadEscolarService;
use App\Services\ReporteEdadEscolarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EdadEscolarReporteController extends Controller
{
    public function __invoke(
        Request $request,
        string $formato,
        ReporteEdadEscolarService $service,
    ): Response|BinaryFileResponse {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);
        abort_unless(in_array($formato, ['pdf', 'excel'], true), 404);

        $filtros = $request->validate([
            'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'nivel_id' => ['nullable', 'integer', 'exists:niveles,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'situacion' => ['nullable', Rule::in([
                EdadEscolarService::SITUACION_ADECUADA,
                EdadEscolarService::SITUACION_MAYOR,
                EdadEscolarService::SITUACION_EXTRAEDAD,
                EdadEscolarService::SITUACION_MENOR,
            ])],
        ]);

        $filtros['situacion'] = $filtros['situacion'] ?? EdadEscolarService::SITUACION_EXTRAEDAD;
        $datos = $service->generar($filtros);
        $nombre = 'edad-escolar-'.Str::slug((string) $filtros['situacion']).'-'.now()->format('Ymd-His');

        if ($formato === 'excel') {
            return Excel::download(new EdadEscolarExport($datos), $nombre.'.xlsx');
        }

        return Pdf::loadView('pdf.edad-escolar', [
            'datos' => $datos,
            'titulo' => $filtros['situacion'] === EdadEscolarService::SITUACION_EXTRAEDAD
                ? 'Reporte de alumnos en extraedad'
                : 'Reporte de edad escolar',
        ])->setPaper('letter', 'landscape')->download($nombre.'.pdf');
    }
}
