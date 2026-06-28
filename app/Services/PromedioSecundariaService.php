<?php

namespace App\Services;

use App\Models\DecisionPromocionOficial;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PromedioSecundariaService
{
    public function esSecundaria(int $nivelId): bool
    {
        return Nivel::query()
            ->whereKey($nivelId)
            ->where('slug', 'secundaria')
            ->exists();
    }

    /**
     * Calcula el promedio anual de secundaria por materia:
     *
     * 1. Promedio anual de la materia = PROMEDIO(P1, P2, P3).
     * 2. Promedio general = PROMEDIO(promedios anuales precisos de materias participantes).
     * 3. Textos y vacíos no se convierten en cero.
     * 4. El resultado definitivo solo existe cuando todas las materias
     *    participantes tienen sus tres periodos numéricos.
     * 5. El truncamiento se aplica únicamente al mostrar.
     */
    public function reporteAnual(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $inscripcionId = null,
    ): array {
        if ($cicloEscolarId <= 0 || ! $this->esSecundaria($nivelId)) {
            return $this->reporteVacio();
        }

        $grados = Grado::query()
            ->where('nivel_id', $nivelId)
            ->when($gradoId, fn ($query) => $query->whereKey($gradoId))
            ->orderBy('orden')
            ->get(['id', 'nombre', 'orden'])
            ->keyBy('id');

        $trayectorias = TrayectoriaAcademica::query()
            ->with([
                'inscripcion' => fn ($query) => $query->withTrashed(),
                'grupo.asignacionGrupo:id,nombre',
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
            ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
            ->when($grupoId, fn ($query) => $query->where('grupo_id', $grupoId))
            ->when($inscripcionId, fn ($query) => $query->where('inscripcion_id', $inscripcionId))
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (TrayectoriaAcademica $trayectoria) => $trayectoria->inscripcion !== null)
            ->unique('inscripcion_id')
            ->values();

        $contextosCalificacion = DB::table('calificaciones')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
            ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
            ->when($grupoId, fn ($query) => $query->where('grupo_id', $grupoId))
            ->when($inscripcionId, fn ($query) => $query->where('inscripcion_id', $inscripcionId))
            ->select([
                'id',
                'inscripcion_id',
                'generacion_id',
                'grado_id',
                'grupo_id',
            ])
            ->orderByDesc('id')
            ->get()
            ->unique('inscripcion_id')
            ->keyBy('inscripcion_id');

        $idsAlumnos = $trayectorias->pluck('inscripcion_id')
            ->merge($contextosCalificacion->keys())
            ->when($inscripcionId, fn (Collection $ids) => $ids->push($inscripcionId))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($idsAlumnos->isEmpty()) {
            return $this->reporteVacio();
        }

        $alumnosPorId = Inscripcion::withTrashed()
            ->whereIn('id', $idsAlumnos->all())
            ->get()
            ->keyBy('id');

        $trayectoriasPorAlumno = $trayectorias->keyBy('inscripcion_id');

        $grupoIds = $trayectorias->pluck('grupo_id')
            ->merge($contextosCalificacion->pluck('grupo_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->whereIn('id', $grupoIds->all())
            ->get(['id', 'asignacion_grupo_id'])
            ->keyBy('id');

        $materiasCatalogo = DB::table('materias')
            ->leftJoin('campos_formativos', 'campos_formativos.id', '=', 'materias.campo_formativo_id')
            ->where('materias.nivel_id', $nivelId)
            ->when($gradoId, fn ($query) => $query->where('materias.grado_id', $gradoId))
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->select([
                'materias.id',
                'materias.grado_id',
                'materias.materia',
                'materias.orden',
                'materias.participa_en_calificacion_oficial',
                'materias.campo_formativo_id',
                DB::raw("COALESCE(campos_formativos.nombre, 'Sin campo formativo') as campo_formativo"),
                DB::raw("COALESCE(campos_formativos.slug, 'sin-campo-formativo') as campo_slug"),
                DB::raw("COALESCE(campos_formativos.color_fondo, '#E2E8F0') as campo_color_fondo"),
                DB::raw("COALESCE(campos_formativos.color_texto, '#334155') as campo_color_texto"),
                DB::raw('COALESCE(campos_formativos.orden, 99) as campo_orden'),
            ])
            ->orderBy('materias.grado_id')
            ->orderByRaw('COALESCE(campos_formativos.orden, 99)')
            ->orderBy('materias.orden')
            ->orderBy('materias.materia')
            ->get()
            ->map(function ($materia) {
                $texto = Str::lower(Str::ascii((string) $materia->materia));
                $esTutoria = str_contains($texto, 'tutor') || str_contains($texto, 'socioemocional');

                $materia->es_tutoria = $esTutoria;
                $materia->participa_en_calificacion_oficial = (bool) $materia->participa_en_calificacion_oficial
                    && ! $esTutoria;

                return $materia;
            });

        $materiasPorGrado = $materiasCatalogo->groupBy('grado_id');

        $calificaciones = DB::table('calificaciones')
            ->join('periodos', 'periodos.id', '=', 'calificaciones.periodo_id')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('calificaciones.ciclo_escolar_id', $cicloEscolarId)
            ->where('calificaciones.nivel_id', $nivelId)
            ->whereIn('calificaciones.inscripcion_id', $idsAlumnos->all())
            ->whereIn('periodos.periodo_basica_id', [1, 2, 3])
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->select([
                'calificaciones.id as calificacion_id',
                'calificaciones.inscripcion_id',
                'calificaciones.grado_id',
                'calificaciones.grupo_id',
                'calificaciones.generacion_id',
                'calificaciones.valor_numerico',
                'calificaciones.es_numerica',
                'calificaciones.calificacion',
                'calificaciones.clave_especial',
                'materias.id as materia_id',
                'periodos.periodo_basica_id as numero_periodo',
            ])
            ->orderBy('calificaciones.id')
            ->get()
            ->groupBy(fn ($fila) => implode('|', [
                $fila->inscripcion_id,
                $fila->materia_id,
                $fila->numero_periodo,
            ]))
            ->map(fn (Collection $items) => $items->last());

        $decisiones = Schema::hasTable('decisiones_promocion_oficial')
            ? DecisionPromocionOficial::query()
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->whereIn('inscripcion_id', $idsAlumnos->all())
                ->get()
                ->keyBy(fn (DecisionPromocionOficial $item) => $item->inscripcion_id . '|' . $item->grado_id)
            : collect();

        $filas = $idsAlumnos->map(function (int $alumnoId) use (
            $alumnosPorId,
            $trayectoriasPorAlumno,
            $contextosCalificacion,
            $grados,
            $grupos,
            $materiasPorGrado,
            $calificaciones,
            $decisiones,
            $gradoId,
            $grupoId,
            $generacionId,
        ): ?array {
            $alumno = $alumnosPorId->get($alumnoId);

            if (! $alumno) {
                return null;
            }

            $trayectoria = $trayectoriasPorAlumno->get($alumnoId);
            $contexto = $contextosCalificacion->get($alumnoId);

            $gradoActualId = (int) ($trayectoria?->grado_id
                ?? $contexto?->grado_id
                ?? $gradoId
                ?? $alumno->grado_id);
            $grupoActualId = (int) ($trayectoria?->grupo_id
                ?? $contexto?->grupo_id
                ?? $grupoId
                ?? $alumno->grupo_id);
            $generacionActualId = (int) ($trayectoria?->generacion_id
                ?? $contexto?->generacion_id
                ?? $generacionId
                ?? $alumno->generacion_id);

            if ($gradoActualId <= 0 || $grupoActualId <= 0) {
                return null;
            }

            $grado = $grados->get($gradoActualId) ?: Grado::query()->find($gradoActualId);
            $grupo = $grupos->get($grupoActualId);
            $grupoNombre = $trayectoria?->grupo?->asignacionGrupo?->nombre
                ?? $grupo?->asignacionGrupo?->nombre
                ?? 'Sin grupo';

            $catalogo = $materiasPorGrado->get($gradoActualId, collect());
            $materias = [];
            $promediosFinalesParticipantes = [];
            $promediosProvisionalesParticipantes = [];
            $promediosPorPeriodo = [1 => [], 2 => [], 3 => []];
            $periodosCompletos = [1 => true, 2 => true, 3 => true];
            $materiasReprobadas = [];

            foreach ($catalogo as $materia) {
                $evaluaciones = [];
                $especiales = [];

                foreach ([1, 2, 3] as $numeroPeriodo) {
                    $registro = $calificaciones->get(
                        implode('|', [$alumnoId, $materia->id, $numeroPeriodo])
                    );

                    $esNumerica = $registro
                        && (bool) $registro->es_numerica
                        && is_numeric($registro->valor_numerico)
                        && (float) $registro->valor_numerico >= 0
                        && (float) $registro->valor_numerico <= 10;

                    $evaluaciones[$numeroPeriodo] = $esNumerica
                        ? (float) $registro->valor_numerico
                        : null;

                    if ($registro && ! $esNumerica) {
                        $especial = trim((string) ($registro->clave_especial ?: $registro->calificacion));
                        if ($especial !== '') {
                            $especiales[$numeroPeriodo] = mb_strtoupper($especial);
                        }
                    }

                    if ((bool) $materia->participa_en_calificacion_oficial) {
                        if ($evaluaciones[$numeroPeriodo] !== null) {
                            $promediosPorPeriodo[$numeroPeriodo][] = $evaluaciones[$numeroPeriodo];
                        } else {
                            $periodosCompletos[$numeroPeriodo] = false;
                        }
                    }
                }

                $capturadas = collect($evaluaciones)->filter(fn ($valor) => $valor !== null)->count();
                $promedioProvisional = PromedioExcel::calcular($evaluaciones);
                $completa = $capturadas === 3;
                $promedioFinal = $completa ? $promedioProvisional : null;

                if ((bool) $materia->participa_en_calificacion_oficial) {
                    if ($promedioProvisional !== null) {
                        $promediosProvisionalesParticipantes[] = $promedioProvisional;
                    }

                    if ($promedioFinal !== null) {
                        $promediosFinalesParticipantes[] = $promedioFinal;

                        if ($promedioFinal < 6.0) {
                            $materiasReprobadas[] = $materia->materia;
                        }
                    }
                }

                $materias[] = [
                    'materia_id' => (int) $materia->id,
                    'materia' => $materia->materia,
                    'orden' => (int) ($materia->orden ?? 999),
                    'participa' => (bool) $materia->participa_en_calificacion_oficial,
                    'es_tutoria' => (bool) $materia->es_tutoria,
                    'campo_formativo_id' => $materia->campo_formativo_id ? (int) $materia->campo_formativo_id : null,
                    'campo_formativo' => $materia->campo_formativo,
                    'campo_slug' => $materia->campo_slug,
                    'campo_color_fondo' => $materia->campo_color_fondo,
                    'campo_color_texto' => $materia->campo_color_texto,
                    'campo_orden' => (int) $materia->campo_orden,
                    'evaluaciones' => $evaluaciones,
                    'especiales' => $especiales,
                    'capturadas' => $capturadas,
                    'faltantes' => max(3 - $capturadas, 0),
                    'promedio_provisional_preciso' => $promedioProvisional,
                    'promedio_final_preciso' => $promedioFinal,
                    'promedio' => PromedioExcel::formatear($promedioFinal, 1, '—'),
                    'completo' => $completa,
                    'provisional' => ! $completa,
                ];
            }

            $materiasParticipantes = collect($materias)->where('participa', true)->values();
            $todasCompletas = $materiasParticipantes->isNotEmpty()
                && $materiasParticipantes->every(fn (array $materia) => $materia['completo'] === true);

            $promedioGeneralPreciso = $todasCompletas
                ? PromedioExcel::calcular($promediosFinalesParticipantes)
                : null;
            $promedioProvisionalPreciso = PromedioExcel::calcular($promediosProvisionalesParticipantes);

            foreach ([1, 2, 3] as $numeroPeriodo) {
                $periodosCompletos[$numeroPeriodo] = $periodosCompletos[$numeroPeriodo]
                    && count($promediosPorPeriodo[$numeroPeriodo]) === $materiasParticipantes->count()
                    && $materiasParticipantes->isNotEmpty();
            }

            $decision = $decisiones->get($alumnoId . '|' . $gradoActualId);

            $campos = collect($materias)
                ->groupBy('campo_slug')
                ->map(function (Collection $items): array {
                    $primera = $items->first();
                    return [
                        'campo_formativo_id' => $primera['campo_formativo_id'],
                        'campo' => $primera['campo_formativo'],
                        'slug' => $primera['campo_slug'],
                        'color_fondo' => $primera['campo_color_fondo'],
                        'color_texto' => $primera['campo_color_texto'],
                        'orden' => $primera['campo_orden'],
                        'materias' => $items->sortBy([['orden', 'asc'], ['materia', 'asc']])->values()->all(),
                    ];
                })
                ->sortBy('orden')
                ->values()
                ->all();

            return [
                'inscripcion_id' => (int) $alumnoId,
                'trayectoria_academica_id' => $trayectoria?->id,
                'matricula' => $alumno->matricula,
                'alumno' => $this->nombreAlumno($alumno),
                'grado_id' => $gradoActualId,
                'grado' => $grado?->nombre ?? 'Sin grado',
                'grado_orden' => (int) ($grado?->orden ?? 999),
                'grupo_id' => $grupoActualId,
                'grupo' => $grupoNombre,
                'generacion_id' => $generacionActualId,
                'materias' => collect($materias)
                    ->sortBy([
                        ['campo_orden', 'asc'],
                        ['orden', 'asc'],
                        ['materia', 'asc'],
                    ])
                    ->values()
                    ->all(),
                'campos' => $campos,
                'materias_esperadas' => $materiasParticipantes->count(),
                'materias_completas' => $materiasParticipantes->where('completo', true)->count(),
                'materias_reprobadas' => $materiasReprobadas,
                'promedios_periodo_precisos' => [
                    1 => PromedioExcel::calcular($promediosPorPeriodo[1]),
                    2 => PromedioExcel::calcular($promediosPorPeriodo[2]),
                    3 => PromedioExcel::calcular($promediosPorPeriodo[3]),
                ],
                'periodos_completos' => $periodosCompletos,
                'promedio_general_preciso' => $promedioGeneralPreciso,
                'promedio_general' => PromedioExcel::formatear($promedioGeneralPreciso, 1, '—'),
                'promedio_provisional_preciso' => $promedioProvisionalPreciso,
                'promedio_provisional' => PromedioExcel::formatear($promedioProvisionalPreciso, 1, '—'),
                'completo' => $todasCompletas,
                'promocion_sugerida' => null,
                'promocion_confirmada' => $decision?->promocion_confirmada,
                'promocion_confirmada_at' => optional($decision?->confirmada_at)->format('d/m/Y H:i'),
                'motivo_promocion' => $decision?->motivo,
            ];
        })->filter()->sortBy([
            ['grado_orden', 'asc'],
            ['grupo', 'asc'],
            ['alumno', 'asc'],
        ])->values();

        $gruposReporte = $filas
            ->groupBy(fn (array $fila) => $fila['grado_id'] . '|' . $fila['grupo_id'])
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $promedios = $items
                    ->where('completo', true)
                    ->pluck('promedio_general_preciso')
                    ->filter(fn ($valor) => $valor !== null);

                return [
                    'grado_id' => $primero['grado_id'],
                    'grado' => $primero['grado'],
                    'grado_orden' => $primero['grado_orden'],
                    'grupo_id' => $primero['grupo_id'],
                    'grupo' => $primero['grupo'],
                    'titulo' => $primero['grado'] . ' · Grupo ' . $primero['grupo'],
                    'total' => $items->count(),
                    'completos' => $items->where('completo', true)->count(),
                    'promedio_preciso' => PromedioExcel::calcular($promedios),
                    'promedio' => PromedioExcel::formatear(PromedioExcel::calcular($promedios), 1, '—'),
                    'alumnos' => $items->values(),
                ];
            })
            ->sortBy([
                ['grado_orden', 'asc'],
                ['grupo', 'asc'],
            ])
            ->values();

        $promediosCompletos = $filas
            ->where('completo', true)
            ->pluck('promedio_general_preciso')
            ->filter(fn ($valor) => $valor !== null);

        return [
            'alumnos' => $filas,
            'grupos' => $gruposReporte,
            'resumen' => [
                'total_alumnos' => $filas->count(),
                'completos' => $filas->where('completo', true)->count(),
                'pendientes' => $filas->where('completo', false)->count(),
                'promedio_general_preciso' => PromedioExcel::calcular($promediosCompletos),
                'promedio_general' => PromedioExcel::formatear(PromedioExcel::calcular($promediosCompletos), 1, '—'),
                'con_materias_reprobadas' => $filas
                    ->filter(fn (array $fila) => $fila['materias_reprobadas'] !== [])
                    ->count(),
                'promovidos_confirmados' => $filas->where('promocion_confirmada', true)->count(),
                'no_promovidos_confirmados' => $filas->where('promocion_confirmada', false)->count(),
            ],
        ];
    }

    private function reporteVacio(): array
    {
        return [
            'alumnos' => collect(),
            'grupos' => collect(),
            'resumen' => [
                'total_alumnos' => 0,
                'completos' => 0,
                'pendientes' => 0,
                'promedio_general_preciso' => null,
                'promedio_general' => '—',
                'con_materias_reprobadas' => 0,
                'promovidos_confirmados' => 0,
                'no_promovidos_confirmados' => 0,
            ],
        ];
    }

    private function nombreAlumno(Inscripcion $alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])));
    }
}
