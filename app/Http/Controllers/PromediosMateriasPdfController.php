<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Services\PromediosTresPeriodosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromediosMateriasPdfController extends Controller
{
    public function __invoke(Request $request, string $slug_nivel, PromediosTresPeriodosService $servicio)
    {
        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $datos = $request->validate([
            'ciclo_escolar_id' => ['required', Rule::exists('ciclo_escolares', 'id')],
            'generacion_id' => ['nullable', Rule::exists('generaciones', 'id')->where('nivel_id', $nivel->id)],
            'grado_id' => ['nullable', Rule::exists('grados', 'id')->where('nivel_id', $nivel->id)],
            'grupo_id' => ['nullable', Rule::exists('grupos', 'id')->where('nivel_id', $nivel->id)],
            'alcance' => ['nullable', Rule::in(['completo', 'nivel', 'grado', 'grupo'])],
        ]);

        $reporte = $servicio->generar(
            nivelId: (int) $nivel->id,
            cicloEscolarId: (int) $datos['ciclo_escolar_id'],
            generacionId: isset($datos['generacion_id']) ? (int) $datos['generacion_id'] : null,
            gradoId: isset($datos['grado_id']) ? (int) $datos['grado_id'] : null,
            grupoId: isset($datos['grupo_id']) ? (int) $datos['grupo_id'] : null,
        );

        $pdf = Pdf::loadView('pdf.promedios-materias-concentrado', [
            'reporte' => $reporte,
            'alcance' => $datos['alcance'] ?? 'completo',
        ])->setPaper('a3', 'landscape');

        $nombre = 'promedios-tres-periodos-' . $slug_nivel . '-' . ($reporte['ciclo']['texto'] ?? 'ciclo') . '.pdf';

        return $pdf->download($nombre);
    }
}
