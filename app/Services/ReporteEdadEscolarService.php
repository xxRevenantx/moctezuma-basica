<?php

namespace App\Services;

use App\Models\Inscripcion;
use Illuminate\Support\Collection;

class ReporteEdadEscolarService
{
    public function __construct(private readonly EdadEscolarService $edadEscolar)
    {
    }

    /**
     * Construye un listado operativo de alumnos activos con su clasificación
     * de edad escolar. No persiste edad ni clasificación en base de datos.
     *
     * @param array<string,mixed> $filtros
     * @return array<string,mixed>
     */
    public function generar(array $filtros = []): array
    {
        $situacion = (string) ($filtros['situacion'] ?? EdadEscolarService::SITUACION_EXTRAEDAD);

        $consulta = Inscripcion::query()
            ->visiblesEnListas()
            ->with([
                'nivel:id,nombre,slug,color',
                'grado:id,nivel_id,nombre,slug,orden',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
            ]);

        if (! empty($filtros['ciclo_escolar_id'])) {
            $consulta->where('ciclo_escolar_id', (int) $filtros['ciclo_escolar_id']);
        }

        if (! empty($filtros['nivel_id'])) {
            $consulta->where('nivel_id', (int) $filtros['nivel_id']);
        }

        if (! empty($filtros['grado_id'])) {
            $consulta->where('grado_id', (int) $filtros['grado_id']);
        }

        $alumnos = $consulta
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $filas = $alumnos
            ->map(function (Inscripcion $alumno): ?array {
                $analisis = $this->edadEscolar->analizar(
                    $alumno->fecha_nacimiento,
                    $alumno->nivel,
                    $alumno->grado,
                    $alumno->cicloEscolar,
                );

                if (! ($analisis['disponible'] ?? false)) {
                    return null;
                }

                return [
                    'id' => (int) $alumno->id,
                    'matricula' => (string) ($alumno->matricula ?? ''),
                    'curp' => (string) ($alumno->curp ?? ''),
                    'alumno' => trim(implode(' ', array_filter([
                        $alumno->apellido_paterno,
                        $alumno->apellido_materno,
                        $alumno->nombre,
                    ]))),
                    'genero' => (string) ($alumno->genero ?? ''),
                    'nivel' => (string) ($alumno->nivel?->nombre ?? ''),
                    'nivel_slug' => (string) ($alumno->nivel?->slug ?? ''),
                    'grado' => (string) ($alumno->grado?->nombre ?? ''),
                    'grupo' => (string) ($alumno->grupo?->asignacionGrupo?->nombre ?? ''),
                    'ciclo' => $alumno->cicloEscolar?->nombre ?? '',
                    'fecha_nacimiento' => $alumno->fecha_nacimiento?->format('d/m/Y'),
                    'edad_actual' => $analisis['edad_actual'],
                    'edad_corte' => $analisis['edad_corte'],
                    'edad_esperada' => $analisis['edad_esperada'],
                    'diferencia' => $analisis['diferencia'],
                    'situacion' => $analisis['situacion'],
                    'etiqueta' => $analisis['etiqueta'],
                    'fecha_corte' => $analisis['fecha_corte_texto'],
                    'es_bachillerato' => (bool) ($analisis['es_bachillerato'] ?? false),
                ];
            })
            ->filter()
            ->when($situacion !== '', fn (Collection $coleccion) => $coleccion->where('situacion', $situacion))
            ->values();

        return [
            'filtros' => [
                'situacion' => $situacion,
                'ciclo_escolar_id' => isset($filtros['ciclo_escolar_id']) ? (int) $filtros['ciclo_escolar_id'] : null,
                'nivel_id' => isset($filtros['nivel_id']) ? (int) $filtros['nivel_id'] : null,
                'grado_id' => isset($filtros['grado_id']) ? (int) $filtros['grado_id'] : null,
            ],
            'filas' => $filas,
            'total' => $filas->count(),
            'generado_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
