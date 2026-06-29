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
     * Reglas oficiales para secundaria:
     *
     * 1. Solo participan materias asignadas al grupo y ciclo escolar que en la
     *    base de datos tengan participa_en_calificacion_oficial = 1.
     * 2. Se excluyen materias extra, recesos, talleres y registros sin uno de
     *    los cuatro campos formativos oficiales.
     * 3. Promedio anual de materia = promedio numérico de P1, P2 y P3,
     *    truncado a un decimal.
     * 4. Promedio general = promedio de los promedios anuales truncados de las
     *    materias; el resultado final también se trunca a un decimal.
     * 5. El resultado es definitivo únicamente cuando todas las materias
     *    participantes tienen calificación numérica en los tres periodos.
     * 6. La decisión de promoción no modifica los promedios ni su estado.
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
            ->when($grupoId, fn (Collection $ids) => $ids->push($grupoId))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($grupoIds->isEmpty()) {
            return $this->reporteVacio();
        }

        $grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->whereIn('id', $grupoIds->all())
            ->get(['id', 'asignacion_grupo_id'])
            ->keyBy('id');

        /*
         * La asignación del grupo y las banderas de materias son la fuente de
         * verdad. No se deduce la participación a partir del nombre.
         */
        $asignacionesCatalogo = DB::table('asignacion_materias')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->join('campos_formativos', 'campos_formativos.id', '=', 'materias.campo_formativo_id')
            ->whereIn('asignacion_materias.grupo_id', $grupoIds->all())
            ->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId)
            ->where('asignacion_materias.estado', '!=', 'archivada')
            ->where('materias.nivel_id', $nivelId)
            ->when($gradoId, fn ($query) => $query->where('materias.grado_id', $gradoId))
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->where('materias.participa_en_calificacion_oficial', true)
            ->where('campos_formativos.activo', true)
            ->where('campos_formativos.slug', '!=', 'sin-campo-formativo')
            ->select([
                'asignacion_materias.id as asignacion_materia_id',
                'asignacion_materias.grupo_id',
                'materias.id as materia_id',
                'materias.grado_id',
                'materias.materia',
                'materias.clave',
                'materias.orden',
                'materias.campo_formativo_id',
                'campos_formativos.nombre as campo_formativo',
                'campos_formativos.slug as campo_slug',
                'campos_formativos.color_fondo as campo_color_fondo',
                'campos_formativos.color_texto as campo_color_texto',
                'campos_formativos.orden as campo_orden',
            ])
            ->orderBy('asignacion_materias.id')
            ->get();

        $materiasPorGrupo = $asignacionesCatalogo
            ->groupBy(fn ($materia) => $materia->grupo_id . '|' . $materia->materia_id)
            ->map(fn (Collection $asignaciones) => $asignaciones->last())
            ->groupBy('grupo_id')
            ->map(fn (Collection $materias) => $materias
                ->sortBy([
                    ['campo_orden', 'asc'],
                    ['orden', 'asc'],
                    ['materia', 'asc'],
                ])
                ->values());

        $idsAsignaciones = $asignacionesCatalogo
            ->pluck('asignacion_materia_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $calificaciones = $idsAsignaciones->isEmpty()
            ? collect()
            : DB::table('calificaciones')
                ->join('periodos', 'periodos.id', '=', 'calificaciones.periodo_id')
                ->where('calificaciones.ciclo_escolar_id', $cicloEscolarId)
                ->where('calificaciones.nivel_id', $nivelId)
                ->whereIn('calificaciones.inscripcion_id', $idsAlumnos->all())
                ->whereIn('calificaciones.asignacion_materia_id', $idsAsignaciones->all())
                ->whereIn('periodos.periodo_basica_id', [1, 2, 3])
                ->select([
                    'calificaciones.id as calificacion_id',
                    'calificaciones.inscripcion_id',
                    'calificaciones.asignacion_materia_id',
                    'calificaciones.valor_numerico',
                    'calificaciones.es_numerica',
                    'calificaciones.calificacion',
                    'calificaciones.clave_especial',
                    'periodos.periodo_basica_id as numero_periodo',
                ])
                ->orderBy('calificaciones.id')
                ->get()
                ->groupBy(fn ($fila) => implode('|', [
                    $fila->inscripcion_id,
                    $fila->asignacion_materia_id,
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
            $materiasPorGrupo,
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

            $catalogo = collect($materiasPorGrupo->get($grupoActualId, collect()))
                ->filter(fn ($materia) => (int) $materia->grado_id === $gradoActualId)
                ->values();

            $materias = [];
            $promediosFinalesParticipantes = [];
            $promediosProvisionalesParticipantes = [];
            $promediosPorPeriodo = [1 => [], 2 => [], 3 => []];
            $capturaCompletaPorPeriodo = [1 => true, 2 => true, 3 => true];
            $materiasReprobadas = [];

            foreach ($catalogo as $materia) {
                $evaluaciones = [];
                $especiales = [];

                foreach ([1, 2, 3] as $numeroPeriodo) {
                    $registro = $calificaciones->get(implode('|', [
                        $alumnoId,
                        $materia->asignacion_materia_id,
                        $numeroPeriodo,
                    ]));

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

                    if ($evaluaciones[$numeroPeriodo] !== null) {
                        $promediosPorPeriodo[$numeroPeriodo][] = $evaluaciones[$numeroPeriodo];
                    } else {
                        $capturaCompletaPorPeriodo[$numeroPeriodo] = false;
                    }
                }

                $capturadas = collect($evaluaciones)
                    ->filter(fn ($valor) => $valor !== null)
                    ->count();
                /*
                 * El promedio anual de la materia siempre se calcula con los
                 * tres periodos y conserva toda la precisión internamente.
                 * El truncamiento se usa exclusivamente al presentar.
                 */
                $promedioProvisional = PromedioExcel::calcular($evaluaciones);
                $completa = $capturadas === 3;
                $promedioFinal = $completa ? $promedioProvisional : null;
                $promedioFinalTruncado = PromedioExcel::truncar($promedioFinal, 1);
                $promedioProvisionalTruncado = PromedioExcel::truncar($promedioProvisional, 1);

                /*
                 * Regla institucional de secundaria:
                 * cada promedio anual de materia se trunca primero a un decimal.
                 * El promedio general se obtiene usando exclusivamente esos
                 * valores truncados; nunca se redondean.
                 */
                if ($promedioProvisionalTruncado !== null) {
                    $promediosProvisionalesParticipantes[] = $promedioProvisionalTruncado;
                }

                if ($promedioFinalTruncado !== null) {
                    $promediosFinalesParticipantes[] = $promedioFinalTruncado;

                    if ($promedioFinalTruncado < 6.0) {
                        $materiasReprobadas[] = $materia->materia;
                    }
                }

                $materias[] = [
                    'asignacion_materia_id' => (int) $materia->asignacion_materia_id,
                    'materia_id' => (int) $materia->materia_id,
                    'materia' => $materia->materia,
                    'clave' => $materia->clave,
                    'orden' => (int) ($materia->orden ?? 999),
                    'participa' => true,
                    'campo_formativo_id' => (int) $materia->campo_formativo_id,
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
                    'promedio_provisional_truncado' => $promedioProvisionalTruncado,
                    'promedio_final_preciso' => $promedioFinal,
                    'promedio_final_truncado' => $promedioFinalTruncado,
                    'promedio' => PromedioExcel::formatear($promedioFinal, 1, '—'),
                    'completo' => $completa,
                    'provisional' => ! $completa,
                ];
            }

            $materiasParticipantes = collect($materias);
            $todasCompletas = $materiasParticipantes->isNotEmpty()
                && $materiasParticipantes->every(fn (array $materia) => $materia['completo'] === true);

            /*
             * Promedio general de secundaria:
             * 1. Se trunca a un decimal el promedio anual de cada materia.
             * 2. Se suman esos valores truncados.
             * 3. Se dividen entre el número de materias participantes.
             * 4. El resultado final también se trunca a un decimal.
             *
             * Ejemplo:
             * 67.7 / 10 = 6.77 => se muestra 6.7, nunca 6.8.
             */
            $promedioGeneralPreciso = $todasCompletas
                ? PromedioExcel::calcular($promediosFinalesParticipantes)
                : null;
            $promedioGeneralTruncado = PromedioExcel::truncar($promedioGeneralPreciso, 1);
            $promedioProvisionalPreciso = PromedioExcel::calcular($promediosProvisionalesParticipantes);
            $promedioProvisionalTruncado = PromedioExcel::truncar($promedioProvisionalPreciso, 1);

            /*
             * Un periodo se presenta como definitivo únicamente cuando el ciclo
             * completo está capturado. Así, P1 y P2 conservan la etiqueta PROV.
             * mientras exista cualquier evaluación pendiente en P1, P2 o P3.
             */
            $periodosCompletos = collect([1, 2, 3])
                ->mapWithKeys(fn (int $numeroPeriodo) => [
                    $numeroPeriodo => $todasCompletas
                        && $capturaCompletaPorPeriodo[$numeroPeriodo]
                        && count($promediosPorPeriodo[$numeroPeriodo]) === $materiasParticipantes->count(),
                ])
                ->all();

            $decision = $decisiones->get($alumnoId . '|' . $gradoActualId);

            $campos = $materiasParticipantes
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
                        'materias' => $items
                            ->sortBy([['orden', 'asc'], ['materia', 'asc']])
                            ->values()
                            ->all(),
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
                'materias' => $materiasParticipantes
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
                'materias_reprobadas' => array_values(array_unique($materiasReprobadas)),
                'promedios_periodo_precisos' => [
                    1 => PromedioExcel::calcular($promediosPorPeriodo[1]),
                    2 => PromedioExcel::calcular($promediosPorPeriodo[2]),
                    3 => PromedioExcel::calcular($promediosPorPeriodo[3]),
                ],
                'periodos_completos' => $periodosCompletos,
                'promedio_general_preciso' => $promedioGeneralPreciso,
                'promedio_general_truncado' => $promedioGeneralTruncado,
                'promedio_general' => PromedioExcel::formatear($promedioGeneralPreciso, 1, '—'),
                'promedio_provisional_preciso' => $promedioProvisionalPreciso,
                'promedio_provisional_truncado' => $promedioProvisionalTruncado,
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
                    'promedio_truncado' => PromedioExcel::truncar(PromedioExcel::calcular($promedios), 1),
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
                'promedio_general_truncado' => PromedioExcel::truncar(PromedioExcel::calcular($promediosCompletos), 1),
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
                'promedio_general_truncado' => null,
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
