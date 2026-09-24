<?php

namespace App\Http\Controllers;

use App\Models\CartaCompromiso;
use App\Services\ExpedienteArchivoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CartaCompromisoPdfController extends Controller
{
    public function individual(CartaCompromiso $carta)
    {
        abort_if($carta->estado_documento === 'cancelada', 404);

        $this->cargarRelaciones($carta);
        $contenido = $this->renderizar([$carta]);
        $this->archivarSiHaceFalta($carta);

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . Str::slug($carta->folio, '_') . '.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function masivas(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $cartas = CartaCompromiso::query()
            ->whereIn('id', $ids)
            ->where('estado_documento', 'emitida')
            ->with([
                'alumno.nivel',
                'alumno.grado',
                'alumno.grupo.asignacionGrupo',
                'alumno.cicloEscolar',
                'tutor',
            ])
            ->get()
            ->sortBy(fn (CartaCompromiso $carta) => $ids->search($carta->id))
            ->values();

        abort_if($cartas->isEmpty(), 404);

        foreach ($cartas as $carta) {
            $this->archivarSiHaceFalta($carta);
        }

        $contenido = $this->renderizar($cartas->all());

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cartas_compromiso_' . now()->format('Ymd_His') . '.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function cargarRelaciones(CartaCompromiso $carta): void
    {
        $carta->load([
            'alumno.nivel',
            'alumno.grado',
            'alumno.grupo.asignacionGrupo',
            'alumno.cicloEscolar',
            'tutor',
        ]);
    }

    private function renderizar(array $cartas): string
    {
        return Pdf::loadView('pdf.carta_compromiso', [
            'cartas' => $cartas,
        ])->setPaper('letter', 'portrait')->output();
    }

    private function archivarSiHaceFalta(CartaCompromiso $carta): void
    {
        if ($carta->documento_alumno_id || ! $carta->alumno) {
            return;
        }

        try {
            $contenidoIndividual = $this->renderizar([$carta]);
            $documento = app(ExpedienteArchivoService::class)->guardarPdfGenerado(
                $carta->alumno,
                'carta-compromiso',
                $contenidoIndividual,
                [
                    'folio' => $carta->folio,
                    'nivel_id' => $carta->nivel_id,
                    'grado_id' => $carta->grado_id,
                    'grupo_id' => $carta->grupo_id,
                    'ciclo_escolar_id' => $carta->ciclo_escolar_id,
                    'fecha_documento' => $carta->fecha_expedicion?->toDateString(),
                    'motivo' => $carta->motivo_texto,
                    'estado' => 'emitida',
                    'observaciones' => 'Generada automáticamente desde el módulo Carta compromiso.',
                ]
            );

            $carta->forceFill(['documento_alumno_id' => $documento->id])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
