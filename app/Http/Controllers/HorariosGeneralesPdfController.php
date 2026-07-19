<?php

namespace App\Http\Controllers;

use App\Models\Escuela;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\CicloEscolar;
use App\Services\HorarioGeneralBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HorariosGeneralesPdfController extends Controller
{
    public function __invoke(Request $request, HorarioGeneralBuilder $builder)
    {
        $datos = $request->validate([
            'slug_nivel' => ['required', 'string'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'alcance' => ['required', 'in:nivel,grado,grupo'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'filtro_grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'filtro_grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'filtro_dia_id' => ['nullable', 'integer', 'exists:dias,id'],
            'filtro_materia' => ['nullable', 'string', 'max:100', 'regex:/^(materia|taller):\d+$/'],
        ]);

        $nivel = Nivel::query()
            ->where('slug', $datos['slug_nivel'])
            ->firstOrFail();

        $cicloEscolar = CicloEscolar::query()->findOrFail($datos['ciclo_escolar_id']);
        $esBachillerato = (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';
        $alcance = $datos['alcance'];

        if (in_array($alcance, ['grado', 'grupo'], true)) {
            abort_unless(
                !empty($datos['generacion_id']) && !empty($datos['grado_id']),
                422,
                'Para descargar por grado o grupo debes seleccionar generación y grado.'
            );

            if ($esBachillerato) {
                abort_unless(
                    !empty($datos['semestre_id']),
                    422,
                    'Para bachillerato debes seleccionar un semestre.'
                );
            }
        }

        if ($alcance === 'grupo') {
            abort_unless(!empty($datos['grupo_id']), 422, 'Debes seleccionar un grupo.');
        }

        $grupos = $this->consultarGrupos(
            nivel: $nivel,
            cicloEscolar: $cicloEscolar,
            alcance: $alcance,
            generacionId: isset($datos['generacion_id']) ? (int) $datos['generacion_id'] : null,
            gradoId: isset($datos['grado_id']) ? (int) $datos['grado_id'] : null,
            semestreId: isset($datos['semestre_id']) ? (int) $datos['semestre_id'] : null,
            grupoId: isset($datos['grupo_id']) ? (int) $datos['grupo_id'] : null,
        );

        abort_if(
            $grupos->isEmpty(),
            404,
            'No se encontraron grupos con horario capturado para los filtros seleccionados.'
        );

        $tabla = $builder->construir(
            nivel: $nivel,
            cicloEscolar: $cicloEscolar,
            grupos: $grupos,
            filtros: [
                'grado_id' => $datos['filtro_grado_id'] ?? null,
                'grupo_id' => $datos['filtro_grupo_id'] ?? null,
                'dia_id' => $datos['filtro_dia_id'] ?? null,
                'materia' => $datos['filtro_materia'] ?? '',
            ],
        );

        abort_if(
            !$tabla || (int) ($tabla['total_actividades'] ?? 0) === 0,
            404,
            'No se encontraron actividades para generar el horario general.'
        );

        $escuela = Escuela::query()->first();
        abort_if(!$escuela, 404, 'No se encontró la información de la escuela.');

        $imagenesPorNivel = [
            'preescolar' => 'imagenes/personajes_preescolar.png',
            'primaria' => 'imagenes/personajes_primaria.png',
            'secundaria' => 'imagenes/personajes_secundaria.png',
            'bachillerato' => 'imagenes/personajes_bachillerato.png',
        ];

        $nombreArchivo = sprintf(
            'HORARIO_GENERAL_%s_%s-%s.pdf',
            mb_strtoupper(Str::slug((string) $nivel->nombre, '_'), 'UTF-8'),
            $cicloEscolar->inicio_anio,
            $cicloEscolar->fin_anio
        );

        return Pdf::loadView('pdf.horarios-generales', [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'ciclo_escolar' => $cicloEscolar,
            'tabla' => $tabla,
            'logo_izquierdo' => $builder->imagenBase64Publica('imagenes/logo-letra.png'),
            'logo_derecho' => $builder->imagenBase64Publica(
                !empty($nivel->logo)
                    ? 'storage/logos/' . $nivel->logo
                    : 'imagenes/logo-letra.png'
            ),
            'imagen_nivel' => $builder->imagenBase64Publica($imagenesPorNivel[$nivel->slug] ?? null),
        ])
            ->setPaper('letter', 'portrait')
            ->stream($nombreArchivo);
    }

    private function consultarGrupos(
        Nivel $nivel,
        CicloEscolar $cicloEscolar,
        string $alcance,
        ?int $generacionId,
        ?int $gradoId,
        ?int $semestreId,
        ?int $grupoId,
    ): Collection {
        $consulta = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
            ])
            ->where('nivel_id', $nivel->id)
            ->whereHas('horarios', function ($query) use ($nivel, $cicloEscolar) {
                $query
                    ->where('nivel_id', $nivel->id)
                    ->where('ciclo_escolar_id', $cicloEscolar->id)
                    ->where(function ($actividadQuery) {
                        $actividadQuery
                            ->whereNotNull('asignacion_materia_id')
                            ->orWhereNotNull('taller_sesion_id');
                    });
            });

        if (in_array($alcance, ['grado', 'grupo'], true)) {
            $consulta
                ->where('generacion_id', $generacionId)
                ->where('grado_id', $gradoId);

            $semestreId
                ? $consulta->where('semestre_id', $semestreId)
                : $consulta->whereNull('semestre_id');
        }

        if ($alcance === 'grupo') {
            $consulta->whereKey($grupoId);
        }

        return $consulta
            ->get()
            ->sortBy(function (Grupo $grupo) {
                return sprintf(
                    '%06d-%06d-%s-%06d-%06d',
                    (int) ($grupo->grado?->orden ?? 999999),
                    (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
                    Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
                    (int) ($grupo->generacion?->anio_ingreso ?? 999999),
                    (int) $grupo->id
                );
            })
            ->values();
    }
}
