<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\ProfesorDocumentoPlantilla;
use App\Services\ProfesorDocumentoPortadaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProfesorDocumentoController extends Controller
{
    public function portadasPdf(Request $request, ProfesorDocumentoPortadaService $service)
    {
        $data = $request->validate([
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'modo_descarga' => ['required', 'in:individual,seleccionados'],
            'persona_individual_id' => ['nullable', 'integer', 'exists:personas,id'],
            'personas' => ['nullable', 'string'],
            'plantilla_id' => ['required', 'integer', 'exists:profesor_documento_plantillas,id'],
        ]);

        $nivel = Nivel::query()->findOrFail($data['nivel_id']);
        $ciclo = !empty($data['ciclo_escolar_id']) ? CicloEscolar::query()->find($data['ciclo_escolar_id']) : null;
        $plantilla = ProfesorDocumentoPlantilla::query()
            ->where('nivel_id', $nivel->id)
            ->where('tipo_documento', 'portada')
            ->findOrFail($data['plantilla_id']);

        $personas = $this->resolverPersonas($data, $nivel->id, $ciclo?->id);

        abort_if($personas->isEmpty(), 404, 'No se encontraron profesores para generar la portada.');

        $documentos = $personas->map(function (Persona $persona) use ($service, $nivel, $ciclo, $plantilla) {
            return [
                'persona' => $persona,
                'datos' => $service->datosPortada($persona, $nivel, $ciclo, $plantilla),
            ];
        })->values();

        $pdf = Pdf::loadView('pdf.profesor_portadas_pdf', [
            'nivel' => $nivel,
            'ciclo' => $ciclo,
            'plantilla' => $plantilla,
            'fondo_data_uri' => $service->fondoDataUri($plantilla),
            'configuracion' => $service->configuracionNormalizada($plantilla, $nivel),
            'documentos' => $documentos,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('portadas-personal-' . ($nivel->slug ?: $nivel->id) . '.pdf');
    }

    private function resolverPersonas(array $data, int $nivelId, ?int $cicloEscolarId)
    {
        $query = Persona::query()
            ->select('personas.*')
            ->with([
                'personaNiveles' => function ($q) use ($nivelId, $cicloEscolarId) {
                    $q->where('nivel_id', $nivelId)
                        ->with([
                            'detalles' => function ($detalles) use ($cicloEscolarId) {
                                if ($cicloEscolarId) {
                                    $detalles->vigenteEnCiclo($cicloEscolarId, false);
                                }

                                $detalles->with('personaRole.rolePersona:id,nombre,slug,status');
                            },
                        ]);
                },
            ])
            ->where('personas.status', 1)
            ->whereHas('personaNiveles', function ($q) use ($nivelId, $cicloEscolarId) {
                $q->where('nivel_id', $nivelId)
                    ->where('estado', 'activo')
                    ->when($cicloEscolarId, function ($cabecera) use ($cicloEscolarId) {
                        $cabecera->whereHas('detalles', function ($detalles) use ($cicloEscolarId) {
                            $detalles->vigenteEnCiclo($cicloEscolarId, false);
                        });
                    });
            });

        if (($data['modo_descarga'] ?? null) === 'individual') {
            return $query->whereKey((int) ($data['persona_individual_id'] ?? 0))->get();
        }

        $ids = collect(explode(',', (string) ($data['personas'] ?? '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $personas = $query->whereIn('personas.id', $ids->all())->get();

        return $personas->sortBy(fn ($persona) => $ids->search((int) $persona->id))->values();
    }
}
