<?php

namespace App\Services;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromedioBachilleratoService
{
    public const PARCIALES = [1, 2];

    public function esBachillerato(int $nivelId): bool
    {
        return Nivel::query()
            ->whereKey($nivelId)
            ->where(function ($query): void {
                $query->where('slug', 'bachillerato')
                    ->orWhere('id', 4);
            })
            ->exists();
    }

    /**
     * Reglas exclusivas de bachillerato:
     *
     * 1. Solo participan materias calificables asignadas al grupo, semestre y
     *    ciclo escolar. Se excluyen materias extra y recesos; las asignaturas
     *    oficiales cuyo nombre contiene “Taller” sí participan.
     * 2. Cada materia debe tener calificación numérica de 0 a 10 en ambos
     *    parciales. NP, AC, textos y vacíos dejan el semestre incompleto.
     * 3. Promedio de materia = (P1 + P2) / 2.
     * 4. Promedio semestral = promedio de los promedios finales de materias.
     * 5. Los lugares se calculan por grupo y semestre. Los empates comparten
     *    lugar con numeración consecutiva: 1, 1, 2, 3.
     * 6. Reconocimiento: semestre completo, promedio mínimo de 6 y lugar 1 a 3.
     *    Una materia reprobada no bloquea el reconocimiento si el promedio
     *    semestral es aprobatorio.
     * 7. Diploma: únicamente sexto semestre, todas las materias completas y
     *    acreditadas con promedio final mínimo de 6.
     */
    public function reporteSemestral(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $semestreId = null,
    ): array {
        if ($cicloEscolarId <= 0 || ! $this->esBachillerato($nivelId)) {
            return $this->reporteVacio();
        }

        $contextos = $this->obtenerContextos(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
            semestreId: $semestreId,
        );

        if ($contextos->isEmpty()) {
            return $this->reporteVacio();
        }

        $idsAlumnos = $contextos->pluck('inscripcion_id')->unique()->values();
        $idsGrupos = $contextos->pluck('grupo_id')->unique()->values();

        $alumnos = Inscripcion::withTrashed()
            ->whereIn('id', $idsAlumnos->all())
            ->get()
            ->keyBy('id');

        $grados = Grado::query()
            ->whereIn('id', $contextos->pluck('grado_id')->unique()->all())
            ->get(['id', 'nombre', 'orden'])
            ->keyBy('id');

        $grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'semestre:id,grado_id,numero',
            ])
            ->whereIn('id', $idsGrupos->all())
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->keyBy('id');

        $catalogo = $this->obtenerCatalogoMaterias(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            idsGrupos: $idsGrupos,
        );

        $materiasPorGrupo = $catalogo['materias_por_grupo'];
        $asignacionALogica = $catalogo['asignacion_a_logica'];
        $idsAsignaciones = collect(array_keys($asignacionALogica))->map(fn ($id) => (int) $id)->values();

        $calificaciones = $this->obtenerCalificaciones(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            idsAlumnos: $idsAlumnos,
            idsAsignaciones: $idsAsignaciones,
            asignacionALogica: $asignacionALogica,
        );

        $filas = $contextos
            ->map(function (array $contexto) use (
                $alumnos,
                $grados,
                $grupos,
                $materiasPorGrupo,
                $calificaciones,
            ): ?array {
                $alumno = $alumnos->get($contexto['inscripcion_id']);
                $grado = $grados->get($contexto['grado_id']);
                $grupo = $grupos->get($contexto['grupo_id']);

                if (! $alumno || ! $grado || ! $grupo || ! $grupo->semestre) {
                    return null;
                }

                $catalogoGrupo = collect($materiasPorGrupo->get($grupo->id, collect()))
                    ->filter(fn (array $materia) => (int) $materia['grado_id'] === (int) $contexto['grado_id']
                        && (int) $materia['semestre_id'] === (int) $contexto['semestre_id'])
                    ->values();

                $materias = [];
                $promediosFinales = [];
                $promediosProvisionales = [];
                $valoresPorParcial = [1 => [], 2 => []];
                $materiasReprobadas = [];
                $clavesEspeciales = [];

                foreach ($catalogoGrupo as $materia) {
                    $evaluaciones = [];
                    $especiales = [];

                    foreach (self::PARCIALES as $parcial) {
                        $registro = $calificaciones->get(implode('|', [
                            $contexto['inscripcion_id'],
                            $materia['clave_logica'],
                            $parcial,
                        ]));

                        $esNumerica = $registro
                            && (bool) $registro->es_numerica
                            && is_numeric($registro->valor_numerico)
                            && (float) $registro->valor_numerico >= 0
                            && (float) $registro->valor_numerico <= 10;

                        $evaluaciones[$parcial] = $esNumerica
                            ? (float) $registro->valor_numerico
                            : null;

                        if ($evaluaciones[$parcial] !== null) {
                            $valoresPorParcial[$parcial][] = $evaluaciones[$parcial];
                            continue;
                        }

                        if ($registro) {
                            $especial = trim((string) ($registro->clave_especial ?: $registro->calificacion));

                            if ($especial !== '') {
                                $especial = mb_strtoupper($especial);
                                $especiales[$parcial] = $especial;
                                $clavesEspeciales[] = $materia['materia'] . ': ' . $especial;
                            }
                        }
                    }

                    $capturadas = collect($evaluaciones)->filter(fn ($valor) => $valor !== null)->count();
                    $completa = $capturadas === count(self::PARCIALES);
                    $promedioProvisional = PromedioExcel::calcular($evaluaciones);
                    $promedioFinal = $completa ? $promedioProvisional : null;

                    if ($promedioProvisional !== null) {
                        $promediosProvisionales[] = $promedioProvisional;
                    }

                    if ($promedioFinal !== null) {
                        $promediosFinales[] = $promedioFinal;

                        if ($promedioFinal < 6.0) {
                            $materiasReprobadas[] = $materia['materia'];
                        }
                    }

                    $materias[] = [
                        'asignacion_materia_id' => $materia['asignacion_materia_id'],
                        'asignacion_materia_ids' => $materia['asignacion_materia_ids'],
                        'materia_id' => $materia['materia_id'],
                        'materia' => $materia['materia'],
                        'clave' => $materia['clave'],
                        'orden' => $materia['orden'],
                        'participa' => true,
                        'evaluaciones' => $evaluaciones,
                        'especiales' => $especiales,
                        'capturadas' => $capturadas,
                        'faltantes' => max(count(self::PARCIALES) - $capturadas, 0),
                        'promedio_provisional_preciso' => $promedioProvisional,
                        'promedio_final_preciso' => $promedioFinal,
                        'promedio' => PromedioExcel::formatear($promedioFinal, 1, '—'),
                        'completo' => $completa,
                        'provisional' => ! $completa,
                    ];
                }

                $materiasColeccion = collect($materias);
                $semestreCompleto = $materiasColeccion->isNotEmpty()
                    && $materiasColeccion->every(fn (array $materia) => $materia['completo'] === true);

                $promedioFinal = $semestreCompleto
                    ? PromedioExcel::calcular($promediosFinales)
                    : null;
                $promedioProvisional = PromedioExcel::calcular($promediosProvisionales);

                $periodosCompletos = collect(self::PARCIALES)
                    ->mapWithKeys(fn (int $parcial) => [
                        $parcial => $materiasColeccion->isNotEmpty()
                            && count($valoresPorParcial[$parcial]) === $materiasColeccion->count(),
                    ])
                    ->all();

                return [
                    'inscripcion_id' => (int) $contexto['inscripcion_id'],
                    'generacion_id' => (int) $contexto['generacion_id'],
                    'matricula' => $alumno->matricula,
                    'alumno' => $this->nombreAlumno($alumno),
                    'grado_id' => (int) $contexto['grado_id'],
                    'grado' => $grado->nombre,
                    'grado_orden' => (int) ($grado->orden ?? 999),
                    'grupo_id' => (int) $contexto['grupo_id'],
                    'grupo' => $grupo->asignacionGrupo?->nombre ?? 'Sin grupo',
                    'semestre_id' => (int) $contexto['semestre_id'],
                    'semestre' => (int) $grupo->semestre->numero,
                    'materias' => $materiasColeccion
                        ->sortBy([['orden', 'asc'], ['materia', 'asc']])
                        ->values()
                        ->all(),
                    'materias_esperadas' => $materiasColeccion->count(),
                    'materias_completas' => $materiasColeccion->where('completo', true)->count(),
                    'materias_reprobadas' => array_values(array_unique($materiasReprobadas)),
                    'claves_especiales' => array_values(array_unique($clavesEspeciales)),
                    'promedios_periodo_precisos' => [
                        1 => PromedioExcel::calcular($valoresPorParcial[1]),
                        2 => PromedioExcel::calcular($valoresPorParcial[2]),
                    ],
                    'periodos_completos' => $periodosCompletos,
                    'promedio_general_preciso' => $promedioFinal,
                    'promedio_general_truncado' => PromedioExcel::truncar($promedioFinal, 1),
                    'promedio_general' => PromedioExcel::formatear($promedioFinal, 1, '—'),
                    'promedio_provisional_preciso' => $promedioProvisional,
                    'promedio_provisional_truncado' => PromedioExcel::truncar($promedioProvisional, 1),
                    'promedio_provisional' => PromedioExcel::formatear($promedioProvisional, 1, '—'),
                    'completo' => $semestreCompleto,
                    'aprobado_general' => $semestreCompleto
                        && is_numeric($promedioFinal)
                        && (float) $promedioFinal >= 6.0,
                    'todas_materias_acreditadas' => $semestreCompleto && $materiasReprobadas === [],
                    'lugar' => null,
                    'texto_lugar' => 'Pendiente',
                    'reconocimiento_disponible' => false,
                    'diploma_disponible' => false,
                ];
            })
            ->filter()
            ->values();

        $filas = $this->asignarLugares($filas);

        $filas = $filas
            ->map(function (array $fila): array {
                $fila['diploma_disponible'] = (int) $fila['semestre'] === 6
                    && ($fila['completo'] ?? false) === true
                    && ($fila['todas_materias_acreditadas'] ?? false) === true;

                return $fila;
            })
            ->sortBy([
                ['grado_orden', 'asc'],
                ['semestre', 'asc'],
                ['grupo', 'asc'],
                ['alumno', 'asc'],
            ])
            ->values();

        $gruposReporte = $filas
            ->groupBy(fn (array $fila) => implode('|', [
                $fila['grado_id'],
                $fila['grupo_id'],
                $fila['semestre_id'],
            ]))
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
                    'semestre_id' => $primero['semestre_id'],
                    'semestre' => $primero['semestre'],
                    'titulo' => $primero['grado'] . ' · Grupo ' . $primero['grupo'] . ' · Semestre ' . $primero['semestre'],
                    'total' => $items->count(),
                    'completos' => $items->where('completo', true)->count(),
                    'promedio_preciso' => PromedioExcel::calcular($promedios),
                    'promedio' => PromedioExcel::formatear(PromedioExcel::calcular($promedios), 1, '—'),
                    'alumnos' => $items->values(),
                ];
            })
            ->sortBy([
                ['grado_orden', 'asc'],
                ['semestre', 'asc'],
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
                    ->filter(fn (array $fila) => ($fila['materias_reprobadas'] ?? []) !== [])
                    ->count(),
                'con_reconocimiento' => $filas->where('reconocimiento_disponible', true)->count(),
                'con_diploma' => $filas->where('diploma_disponible', true)->count(),
            ],
        ];
    }

    private function obtenerContextos(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId,
        ?int $gradoId,
        ?int $grupoId,
        ?int $semestreId,
    ): Collection {
        $esCicloActual = (bool) DB::table('ciclo_escolares')
            ->where('id', $cicloEscolarId)
            ->value('es_actual');

        $inscripciones = $esCicloActual
            ? Inscripcion::query()
                ->where('nivel_id', $nivelId)
                ->where('activo', true)
                ->whereNotNull('semestre_id')
                ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
                ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
                ->when($grupoId, fn ($query) => $query->where('grupo_id', $grupoId))
                ->when($semestreId, fn ($query) => $query->where('semestre_id', $semestreId))
                ->get(['id', 'generacion_id', 'grado_id', 'grupo_id', 'semestre_id'])
                ->map(fn (Inscripcion $item) => [
                    'inscripcion_id' => (int) $item->id,
                    'generacion_id' => (int) $item->generacion_id,
                    'grado_id' => (int) $item->grado_id,
                    'grupo_id' => (int) $item->grupo_id,
                    'semestre_id' => (int) $item->semestre_id,
                    '_prioridad' => 1,
                ])
            : collect();

        $calificaciones = DB::table('calificaciones')
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->whereNotNull('semestre_id')
            ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
            ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
            ->when($grupoId, fn ($query) => $query->where('grupo_id', $grupoId))
            ->when($semestreId, fn ($query) => $query->where('semestre_id', $semestreId))
            ->select([
                'id',
                'inscripcion_id',
                'generacion_id',
                'grado_id',
                'grupo_id',
                'semestre_id',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'inscripcion_id' => (int) $item->inscripcion_id,
                'generacion_id' => (int) $item->generacion_id,
                'grado_id' => (int) $item->grado_id,
                'grupo_id' => (int) $item->grupo_id,
                'semestre_id' => (int) $item->semestre_id,
                '_prioridad' => 2,
            ]);

        return $inscripciones
            ->concat($calificaciones)
            ->filter(fn (array $contexto) => $contexto['inscripcion_id'] > 0
                && $contexto['generacion_id'] > 0
                && $contexto['grado_id'] > 0
                && $contexto['grupo_id'] > 0
                && $contexto['semestre_id'] > 0)
            ->sortBy('_prioridad')
            ->keyBy(fn (array $contexto) => $this->claveContexto($contexto))
            ->map(function (array $contexto): array {
                unset($contexto['_prioridad']);
                return $contexto;
            })
            ->values();
    }

    private function obtenerCatalogoMaterias(int $nivelId, int $cicloEscolarId, Collection $idsGrupos): array
    {
        if ($idsGrupos->isEmpty()) {
            return [
                'materias_por_grupo' => collect(),
                'asignacion_a_logica' => [],
            ];
        }

        $queryAsignaciones = DB::table('asignacion_materias')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->join('grupos', 'grupos.id', '=', 'asignacion_materias.grupo_id')
            ->whereIn('asignacion_materias.grupo_id', $idsGrupos->all())
            ->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId)
            ->where('asignacion_materias.estado', '!=', 'archivada')
            ->where('grupos.nivel_id', $nivelId)
            ->whereNotNull('grupos.semestre_id')
            ->where('materias.nivel_id', $nivelId);

        ReglasMateriaBachillerato::aplicarPromediables($queryAsignaciones);

        $asignaciones = $queryAsignaciones
            ->select([
                'asignacion_materias.id as asignacion_materia_id',
                'asignacion_materias.grupo_id',
                'asignacion_materias.orden as asignacion_orden',
                'grupos.grado_id',
                'grupos.semestre_id',
                'materias.id as materia_id',
                'materias.materia',
                'materias.clave',
                'materias.slug',
                'materias.orden as materia_orden',
            ])
            ->orderBy('asignacion_materias.id')
            ->get();

        $asignacionALogica = [];

        $materiasPorGrupo = $asignaciones
            ->groupBy(fn ($materia) => implode('|', [
                $materia->grupo_id,
                $materia->semestre_id,
                $materia->materia_id,
            ]))
            ->map(function (Collection $items, string $claveLogica) use (&$asignacionALogica): array {
                $ultima = $items->last();
                $ids = $items->pluck('asignacion_materia_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

                foreach ($ids as $id) {
                    $asignacionALogica[$id] = $claveLogica;
                }

                return [
                    'clave_logica' => $claveLogica,
                    'asignacion_materia_id' => (int) $ultima->asignacion_materia_id,
                    'asignacion_materia_ids' => $ids,
                    'grupo_id' => (int) $ultima->grupo_id,
                    'grado_id' => (int) $ultima->grado_id,
                    'semestre_id' => (int) $ultima->semestre_id,
                    'materia_id' => (int) $ultima->materia_id,
                    'materia' => $ultima->materia,
                    'clave' => $ultima->clave,
                    'orden' => (int) ($ultima->asignacion_orden ?? $ultima->materia_orden ?? 999),
                ];
            })
            ->groupBy('grupo_id')
            ->map(fn (Collection $materias) => $materias
                ->sortBy([['orden', 'asc'], ['materia', 'asc']])
                ->values());

        return [
            'materias_por_grupo' => $materiasPorGrupo,
            'asignacion_a_logica' => $asignacionALogica,
        ];
    }

    private function obtenerCalificaciones(
        int $nivelId,
        int $cicloEscolarId,
        Collection $idsAlumnos,
        Collection $idsAsignaciones,
        array $asignacionALogica,
    ): Collection {
        if ($idsAlumnos->isEmpty() || $idsAsignaciones->isEmpty()) {
            return collect();
        }

        return DB::table('calificaciones')
            ->join('periodos', 'periodos.id', '=', 'calificaciones.periodo_id')
            ->where('calificaciones.ciclo_escolar_id', $cicloEscolarId)
            ->where('calificaciones.nivel_id', $nivelId)
            ->whereIn('calificaciones.inscripcion_id', $idsAlumnos->all())
            ->whereIn('calificaciones.asignacion_materia_id', $idsAsignaciones->all())
            ->whereIn('periodos.parcial_bachillerato_id', self::PARCIALES)
            ->where('periodos.ciclo_escolar_id', $cicloEscolarId)
            ->where('periodos.nivel_id', $nivelId)
            ->whereColumn('periodos.semestre_id', 'calificaciones.semestre_id')
            ->whereColumn('periodos.generacion_id', 'calificaciones.generacion_id')
            ->select([
                'calificaciones.id as calificacion_id',
                'calificaciones.inscripcion_id',
                'calificaciones.asignacion_materia_id',
                'calificaciones.valor_numerico',
                'calificaciones.es_numerica',
                'calificaciones.calificacion',
                'calificaciones.clave_especial',
                'periodos.parcial_bachillerato_id as numero_parcial',
            ])
            ->orderBy('calificaciones.id')
            ->get()
            ->filter(fn ($fila) => isset($asignacionALogica[(int) $fila->asignacion_materia_id]))
            ->groupBy(fn ($fila) => implode('|', [
                $fila->inscripcion_id,
                $asignacionALogica[(int) $fila->asignacion_materia_id],
                $fila->numero_parcial,
            ]))
            ->map(fn (Collection $items) => $items->last());
    }

    private function asignarLugares(Collection $filas): Collection
    {
        return $filas
            ->groupBy(fn (array $fila) => implode('|', [
                $fila['grado_id'],
                $fila['grupo_id'],
                $fila['semestre_id'],
            ]))
            ->flatMap(function (Collection $items): Collection {
                $promediosUnicos = $items
                    ->filter(fn (array $fila) => ($fila['aprobado_general'] ?? false) === true)
                    ->sortByDesc('promedio_general_preciso')
                    ->pluck('promedio_general_preciso')
                    ->map(fn ($promedio) => PromedioExcel::claveComparacion($promedio))
                    ->filter()
                    ->unique()
                    ->values();

                return $items->map(function (array $fila) use ($promediosUnicos): array {
                    if (! ($fila['aprobado_general'] ?? false)) {
                        return $fila;
                    }

                    $clave = PromedioExcel::claveComparacion($fila['promedio_general_preciso']);
                    $indice = $clave !== null ? $promediosUnicos->search($clave) : false;
                    $lugar = $indice !== false ? ((int) $indice) + 1 : null;

                    $fila['lugar'] = $lugar;
                    $fila['texto_lugar'] = $lugar ? $lugar . '° lugar' : 'Pendiente';
                    $fila['reconocimiento_disponible'] = $lugar !== null && $lugar <= 3;

                    return $fila;
                });
            })
            ->values();
    }

    private function claveContexto(array $contexto): string
    {
        return implode('|', [
            $contexto['inscripcion_id'],
            $contexto['generacion_id'],
            $contexto['grado_id'],
            $contexto['grupo_id'],
            $contexto['semestre_id'],
        ]);
    }

    private function nombreAlumno(Inscripcion $alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])));
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
                'con_reconocimiento' => 0,
                'con_diploma' => 0,
            ],
        ];
    }
}
