<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\ReanudacionLaboral;
use App\Services\ReanudacionesArchivoService;
use App\Services\ReanudacionesService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReanudacionesLaboralesController extends Controller
{
    public function preview(Request $request, string $token, ReanudacionesService $service)
    {
        $sesion = session("reanudaciones_preview.{$token}");
        abort_unless(is_array($sesion), 404, 'La vista previa expiró.');
        abort_unless((int) ($sesion['usuario_id'] ?? 0) === (int) $request->user()?->id, 403);

        $parametros = $sesion['parametros'] ?? [];
        $ciclo = CicloEscolar::query()->find((int) ($parametros['ciclo_escolar_id'] ?? 0));
        abort_unless($ciclo, 404, 'El ciclo escolar de la vista previa ya no está disponible.');

        $documentos = collect($service->construirDocumentos(
            ids: (array) ($parametros['seleccionados'] ?? []),
            ciclo: $ciclo,
            tipo: (string) ($parametros['tipo'] ?? ''),
            fechaDirector: (string) ($parametros['fecha_director'] ?? ''),
            fechaDocente: (string) ($parametros['fecha_docente'] ?? ''),
            copias: $parametros['copias'] ?? null,
        ));
        abort_if($documentos->isEmpty(), 404, 'No hay documentos para previsualizar.');

        $nivelId = $request->integer('nivel_id');
        if ($nivelId) {
            $documentos = $documentos->filter(fn (array $doc) => (int) data_get($doc, 'nivel.id') === $nivelId)->values();
            abort_if($documentos->isEmpty(), 404, 'No hay documentos para el nivel seleccionado.');

            return $service->renderPdf($documentos->all())
                ->stream('vista-previa-reanudaciones-' . Str::slug((string) data_get($documentos->first(), 'nivel.nombre')) . '.pdf');
        }

        $niveles = $documentos
            ->groupBy(fn (array $doc) => (int) data_get($doc, 'nivel.id'))
            ->map(function (Collection $items, int $id) use ($token) {
                return [
                    'id' => $id,
                    'nombre' => (string) data_get($items->first(), 'nivel.nombre', 'Nivel'),
                    'cantidad' => $items->count(),
                    'url' => route('misrutas.reanudaciones.preview', ['token' => $token, 'nivel_id' => $id]),
                ];
            })->values();

        if ($niveles->count() === 1) {
            return redirect()->to((string) $niveles->first()['url']);
        }

        return view('reanudaciones.preview-index', compact('niveles'));
    }

    public function individual(Request $request, ReanudacionLaboral $reanudacion, ReanudacionesArchivoService $archivos)
    {
        $ruta = $archivos->asegurarPdf($reanudacion);
        $nombre = 'reanudacion-' . Str::slug($reanudacion->persona_nombre) . '.pdf';

        if ($request->boolean('descargar')) {
            return response()->download($ruta, $nombre);
        }

        return response()->file($ruta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombre . '"',
        ]);
    }

    public function lote(Request $request, string $formato, ReanudacionesArchivoService $archivos)
    {
        abort_unless(in_array($formato, ['zip', 'word'], true), 404);

        $lote = trim((string) $request->query('lote'));
        abort_if($lote === '', 422, 'El lote es obligatorio.');

        $registros = ReanudacionLaboral::query()
            ->where('lote_uuid', $lote)
            ->orderBy('nivel_id')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (ReanudacionLaboral $registro) => [
                (int) $registro->nivel_id,
                (int) data_get($registro->snapshot, 'orden_plantilla', PHP_INT_MAX),
                (int) $registro->id,
            ])
            ->values();
        abort_if($registros->isEmpty(), 404, 'No se encontró el lote solicitado.');

        if ($formato === 'zip') {
            $ruta = $archivos->crearZip($registros);
            return response()->download($ruta, 'reanudaciones-pdf-' . now()->format('Ymd-His') . '.zip')->deleteFileAfterSend(true);
        }

        $ruta = $archivos->crearWordMasivo($registros);
        return response()->download($ruta, 'reanudaciones-masivas-' . now()->format('Ymd-His') . '.docx')->deleteFileAfterSend(true);
    }
}
