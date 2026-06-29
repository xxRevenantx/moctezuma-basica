<?php

namespace App\Services;

use App\Models\CalificacionCampoFormativo;
use App\Models\CampoFormativo;
use App\Models\DecisionPromocionOficial;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\TrayectoriaAcademica;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CalificacionOficialPrimariaService
{
    public function __construct(private readonly ListaAcademicaService $listas)
    {
    }

    public function campos(): Collection
    {
        return CampoFormativo::query()
            ->where('activo', true)
            ->whereIn('slug', [
                'lenguajes',
                'saberes-pensamiento-cientifico',
                'etica-naturaleza-sociedades',
                'humano-comunitario',
            ])
            ->orderBy('orden')
            ->get(['id', 'nombre', 'slug', 'color_fondo', 'color_texto', 'orden']);
    }

    public function esPrimaria(int $nivelId): bool
    {
        return Nivel::query()
            ->whereKey($nivelId)
            ->where('slug', 'primaria')
            ->exists();
    }

    /**
     * Vista de captura heredada. Se conserva por compatibilidad, aunque el
     * concentrado anual ya puede calcularse automáticamente desde las materias.
     */
    public function capturaPeriodo(
        int $nivelId,
        int $cicloEscolarId,
        int $generacionId,
        int $gradoId,
        int $grupoId,
        int $periodoId,
    ): array {
        $periodo = Periodos::query()
            ->with('periodoBasica:id,periodo,descripcion')
            ->findOrFail($periodoId);

        if (! $this->esPrimaria($nivelId)) {
            return [
                'periodo' => null,
                'campos' => collect(),
                'alumnos' => collect(),
            ];
        }

        $alumnos = $this->listas->alumnosPorContexto(
            cicloEscolarId: $cicloEscolarId,
            grupoIds: [$grupoId],
            fechaCorte: $periodo->fecha_fin ?: now(),
            nivelId: $nivelId,
            gradoId: $gradoId,
            generacionId: $generacionId,
        );

        $campos = $this->campos();

        $filasInternas = DB::table('calificaciones')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('calificaciones.periodo_id', $periodoId)
            ->where('calificaciones.ciclo_escolar_id', $cicloEscolarId)
            ->where('calificaciones.nivel_id', $nivelId)
            ->where('calificaciones.generacion_id', $generacionId)
            ->where('calificaciones.grado_id', $gradoId)
            ->where('calificaciones.grupo_id', $grupoId)
            ->where('calificaciones.es_numerica', true)
            ->whereNotNull('calificaciones.valor_numerico')
            ->whereBetween('calificaciones.valor_numerico', [0, 10])
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->where('materias.participa_en_calificacion_oficial', true)
            ->whereNotNull('materias.campo_formativo_id')
            ->select([
                'calificaciones.id as calificacion_id',
                'calificaciones.inscripcion_id',
                'materias.campo_formativo_id',
                'materias.id as materia_id',
                'materias.materia',
                'calificaciones.valor_numerico',
            ])
            ->orderBy('calificaciones.id')
            ->get()
            ->groupBy(fn ($fila) => $fila->inscripcion_id . '|' . $fila->campo_formativo_id)
            ->map(function (Collection $items): Collection {
                return $items
                    ->groupBy('materia_id')
                    ->map(fn (Collection $materia) => $materia->last())
                    ->values();
            });

        $oficiales = Schema::hasTable('calificaciones_campos_formativos')
            ? CalificacionCampoFormativo::query()
                ->where('periodo_id', $periodoId)
                ->whereIn('inscripcion_id', $alumnos->pluck('id')->all())
                ->get()
                ->keyBy(fn (CalificacionCampoFormativo $item) => $item->inscripcion_id . '|' . $item->campo_formativo_id)
            : collect();

        $filasAlumnos = $alumnos->map(function (Inscripcion $alumno) use ($campos, $filasInternas, $oficiales): array {
            $celdas = [];

            foreach ($campos as $campo) {
                $clave = $alumno->id . '|' . $campo->id;
                $materias = $filasInternas->get($clave, collect());
                $promedioPreciso = PromedioExcel::calcular($materias->pluck('valor_numerico'));
                $sugerenciaEntera = $promedioPreciso !== null
                    ? (int) max(0, min(10, floor($promedioPreciso + 0.000000001)))
                    : null;
                $registro = $oficiales->get($clave);

                $celdas[$campo->id] = [
                    'campo_id' => (int) $campo->id,
                    'campo' => $campo->nombre,
                    'promedio_sugerido_preciso' => $promedioPreciso,
                    'promedio_sugerido_texto' => PromedioExcel::formatear($promedioPreciso, 2, '—'),
                    'sugerencia_entera' => $sugerenciaEntera,
                    'oficial' => $registro?->calificacion_oficial,
                    'confirmada' => (bool) ($registro?->confirmada ?? false),
                    'materias' => $materias
                        ->map(fn ($materia) => [
                            'id' => (int) $materia->materia_id,
                            'nombre' => $materia->materia,
                            'calificacion' => (float) $materia->valor_numerico,
                        ])
                        ->values()
                        ->all(),
                ];
            }

            return [
                'inscripcion_id' => (int) $alumno->id,
                'trayectoria_academica_id' => $alumno->getAttribute('trayectoria_academica_id'),
                'matricula' => $alumno->matricula,
                'alumno' => $this->nombreAlumno($alumno),
                'campos' => $celdas,
            ];
        })->values();

        return [
            'periodo' => [
                'id' => (int) $periodo->id,
                'numero' => (int) ($periodo->periodoBasica?->periodo ?? 0),
                'descripcion' => $periodo->periodoBasica?->descripcion ?? 'Periodo',
                'fecha_inicio' => $periodo->fecha_inicio ? (string) $periodo->fecha_inicio : null,
                'fecha_fin' => $periodo->fecha_fin ? (string) $periodo->fecha_fin : null,
            ],
            'campos' => $campos,
            'alumnos' => $filasAlumnos,
        ];
    }

    /**
     * Reporte anual SEP para primaria.
     *
     * Reglas aplicadas:
     * - Cada periodo de un campo se obtiene promediando sus materias participantes.
     * - La propuesta del periodo se trunca a entero, sin redondear.
     * - Una calificación oficial confirmada, si existe, tiene prioridad.
     * - El promedio final de cada campo se obtiene con sus tres periodos y se
     *   trunca a un decimal para establecer el promedio oficial del campo.
     * - El promedio final de grado es la suma de los cuatro promedios oficiales
     *   de campo dividida entre cuatro.
     * - El resultado general se trunca a un decimal únicamente al presentarse.
     */
    public function reporteAnual(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $inscripcionId = null,
    ): array {
        if ($cicloEscolarId <= 0 || ! $this->esPrimaria($nivelId)) {
            return $this->reporteVacio();
        }

        $campos = $this->campos();

        if ($campos->count() !== 4) {
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
            return $this->reporteVacio($campos);
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
            ->where('nivel_id', $nivelId)
            ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
            ->where('calificable', true)
            ->where('extra', false)
            ->where('receso', false)
            ->whereNotNull('campo_formativo_id')
            ->select([
                'id',
                'grado_id',
                'campo_formativo_id',
                'materia',
                'orden',
                'participa_en_calificacion_oficial',
            ])
            ->orderBy('grado_id')
            ->orderBy('orden')
            ->orderBy('materia')
            ->get();

        $materiasPorGradoCampo = $materiasCatalogo
            ->groupBy(fn ($materia) => $materia->grado_id . '|' . $materia->campo_formativo_id);

        $calificacionesInternas = DB::table('calificaciones')
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
            ->whereNotNull('materias.campo_formativo_id')
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
                'materias.materia as materia_nombre',
                'materias.campo_formativo_id',
                'materias.participa_en_calificacion_oficial',
                'periodos.periodo_basica_id as numero_periodo',
            ])
            ->orderBy('calificaciones.id')
            ->get();

        $ultimaCalificacionPorMateriaPeriodo = $calificacionesInternas
            ->groupBy(fn ($fila) => implode('|', [
                $fila->inscripcion_id,
                $fila->materia_id,
                $fila->numero_periodo,
            ]))
            ->map(fn (Collection $items) => $items->last());

        $internasPorAlumnoCampoPeriodo = $ultimaCalificacionPorMateriaPeriodo
            ->values()
            ->groupBy(fn ($fila) => implode('|', [
                $fila->inscripcion_id,
                $fila->campo_formativo_id,
                $fila->numero_periodo,
            ]));

        $internasPorAlumnoMateriaPeriodo = $ultimaCalificacionPorMateriaPeriodo;

        $oficialesConfirmadas = collect();

        if (Schema::hasTable('calificaciones_campos_formativos')) {
            $oficialesConfirmadas = CalificacionCampoFormativo::query()
                ->join('periodos', 'periodos.id', '=', 'calificaciones_campos_formativos.periodo_id')
                ->where('calificaciones_campos_formativos.ciclo_escolar_id', $cicloEscolarId)
                ->where('calificaciones_campos_formativos.nivel_id', $nivelId)
                ->whereIn('calificaciones_campos_formativos.inscripcion_id', $idsAlumnos->all())
                ->where('calificaciones_campos_formativos.confirmada', true)
                ->whereIn('periodos.periodo_basica_id', [1, 2, 3])
                ->select([
                    'calificaciones_campos_formativos.id',
                    'calificaciones_campos_formativos.inscripcion_id',
                    'calificaciones_campos_formativos.campo_formativo_id',
                    'calificaciones_campos_formativos.calificacion_oficial',
                    'periodos.periodo_basica_id as numero_periodo',
                ])
                ->orderBy('calificaciones_campos_formativos.id')
                ->get()
                ->groupBy(fn ($fila) => implode('|', [
                    $fila->inscripcion_id,
                    $fila->campo_formativo_id,
                    $fila->numero_periodo,
                ]))
                ->map(fn (Collection $items) => $items->last());
        }

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
            $campos,
            $materiasPorGradoCampo,
            $internasPorAlumnoCampoPeriodo,
            $internasPorAlumnoMateriaPeriodo,
            $oficialesConfirmadas,
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

            $camposAlumno = [];
            $finalesOficiales = [];
            $promediosPorPeriodo = [1 => [], 2 => [], 3 => []];
            $periodosCompletos = [1 => true, 2 => true, 3 => true];
            $materiasDetalle = [];

            foreach ($campos as $campo) {
                $catalogoCampo = $materiasPorGradoCampo->get(
                    $gradoActualId . '|' . $campo->id,
                    collect()
                );

                $materiasParticipantes = $catalogoCampo
                    ->filter(fn ($materia) => (bool) $materia->participa_en_calificacion_oficial)
                    ->values();

                $periodos = [];
                $estadosPeriodo = [];
                $fuentesPeriodo = [];
                $capturasPeriodo = [];

                foreach ([1, 2, 3] as $numeroPeriodo) {
                    $clave = implode('|', [$alumnoId, $campo->id, $numeroPeriodo]);
                    $registroOficial = $oficialesConfirmadas->get($clave);
                    $filasCampo = $internasPorAlumnoCampoPeriodo->get($clave, collect())
                        ->filter(fn ($fila) => (bool) $fila->participa_en_calificacion_oficial)
                        ->values();

                    $valores = $filasCampo
                        ->filter(fn ($fila) => (bool) $fila->es_numerica && is_numeric($fila->valor_numerico))
                        ->pluck('valor_numerico')
                        ->map(fn ($valor) => (float) $valor)
                        ->values();

                    $promedioInternoPreciso = PromedioExcel::calcular($valores);
                    $propuestaEntera = $promedioInternoPreciso !== null
                        ? (float) floor($promedioInternoPreciso + 0.000000001)
                        : null;

                    $valorOficial = $registroOficial && is_numeric($registroOficial->calificacion_oficial)
                        ? (float) $registroOficial->calificacion_oficial
                        : null;

                    $periodos[$numeroPeriodo] = $valorOficial ?? $propuestaEntera;

                    $idsCapturados = $filasCampo
                        ->filter(fn ($fila) => (bool) $fila->es_numerica && is_numeric($fila->valor_numerico))
                        ->pluck('materia_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique();

                    $esperadas = $materiasParticipantes->count();
                    $capturadas = $idsCapturados->count();
                    $capturasPeriodo[$numeroPeriodo] = [
                        'esperadas' => $esperadas,
                        'capturadas' => $capturadas,
                        'promedio_interno_preciso' => $promedioInternoPreciso,
                    ];

                    $estadosPeriodo[$numeroPeriodo] = $valorOficial !== null
                        || ($esperadas > 0 && $capturadas >= $esperadas);
                    $fuentesPeriodo[$numeroPeriodo] = $valorOficial !== null ? 'confirmada' : 'automatica';

                    if ($periodos[$numeroPeriodo] !== null) {
                        $promediosPorPeriodo[$numeroPeriodo][] = $periodos[$numeroPeriodo];
                    }

                    if (! $estadosPeriodo[$numeroPeriodo]) {
                        $periodosCompletos[$numeroPeriodo] = false;
                    }
                }

                $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();
                $provisionalCalculoPreciso = PromedioExcel::calcular($periodos);
                $provisionalOficial = PromedioExcel::truncar($provisionalCalculoPreciso, 1);
                $campoCompleto = $capturados === 3
                    && collect($estadosPeriodo)->every(fn ($completo) => $completo === true);
                $finalCalculoPreciso = $campoCompleto ? $provisionalCalculoPreciso : null;
                $finalOficial = $campoCompleto
                    ? PromedioExcel::truncar($finalCalculoPreciso, 1)
                    : null;

                if ($finalOficial !== null) {
                    $finalesOficiales[] = $finalOficial;
                }

                $materiasCampoDetalle = $catalogoCampo->map(function ($materia) use (
                    $alumnoId,
                    $internasPorAlumnoMateriaPeriodo
                ): array {
                    $evaluaciones = [];

                    foreach ([1, 2, 3] as $numeroPeriodo) {
                        $registro = $internasPorAlumnoMateriaPeriodo->get(
                            implode('|', [$alumnoId, $materia->id, $numeroPeriodo])
                        );

                        $evaluaciones[$numeroPeriodo] = $registro
                            && (bool) $registro->es_numerica
                            && is_numeric($registro->valor_numerico)
                            ? (float) $registro->valor_numerico
                            : null;
                    }

                    $promedioProvisional = PromedioExcel::calcular($evaluaciones);
                    $completa = collect($evaluaciones)->filter(fn ($valor) => $valor !== null)->count() === 3;

                    return [
                        'materia_id' => (int) $materia->id,
                        'materia' => $materia->materia,
                        'orden' => (int) ($materia->orden ?? 999),
                        'participa' => (bool) $materia->participa_en_calificacion_oficial,
                        'evaluaciones' => $evaluaciones,
                        'promedio_provisional_preciso' => $promedioProvisional,
                        'promedio_final_preciso' => $completa ? $promedioProvisional : null,
                        'promedio' => PromedioExcel::formatear(
                            $completa ? $promedioProvisional : null,
                            1,
                            '—'
                        ),
                        'completo' => $completa,
                    ];
                })->values();

                $materiasDetalle = array_merge($materiasDetalle, $materiasCampoDetalle->all());

                $camposAlumno[$campo->id] = [
                    'campo_id' => (int) $campo->id,
                    'campo' => $campo->nombre,
                    'slug' => $campo->slug,
                    'color_fondo' => $campo->color_fondo,
                    'color_texto' => $campo->color_texto,
                    'periodos' => $periodos,
                    'periodos_completos' => $estadosPeriodo,
                    'fuentes_periodo' => $fuentesPeriodo,
                    'capturas_periodo' => $capturasPeriodo,
                    'capturados' => $capturados,
                    'provisional_calculo_preciso' => $provisionalCalculoPreciso,
                    'provisional_preciso' => $provisionalOficial,
                    'provisional' => PromedioExcel::formatear($provisionalOficial, 1, '—'),
                    'final_calculo_preciso' => $finalCalculoPreciso,
                    'final_preciso' => $finalOficial,
                    'final' => PromedioExcel::formatear($finalOficial, 1, '—'),
                    'completo' => $campoCompleto,
                    'materias' => $materiasCampoDetalle->all(),
                ];
            }

            $todosLosCamposCompletos = count($finalesOficiales) === 4;
            $promedioGeneralPreciso = $todosLosCamposCompletos
                ? PromedioExcel::calcular($finalesOficiales)
                : null;
            $promedioProvisionalPreciso = PromedioExcel::calcular(
                collect($camposAlumno)->pluck('provisional_preciso')
            );

            $esPrimerGrado = (int) ($grado?->orden ?? 0) === 1;
            $promocionSugerida = $esPrimerGrado
                ? true
                : ($todosLosCamposCompletos
                    ? collect($finalesOficiales)->every(fn ($promedio) => (float) $promedio >= 6.0)
                    : null);

            $decision = $decisiones->get($alumnoId . '|' . $gradoActualId);

            $periodosPromedio = [];
            foreach ([1, 2, 3] as $numeroPeriodo) {
                $periodosPromedio[$numeroPeriodo] = PromedioExcel::calcular($promediosPorPeriodo[$numeroPeriodo]);
                $periodosCompletos[$numeroPeriodo] = $periodosCompletos[$numeroPeriodo]
                    && count($promediosPorPeriodo[$numeroPeriodo]) === 4;
            }

            $totalCapturasMaterias = collect($materiasDetalle)
                ->sum(fn (array $materia) => collect($materia['evaluaciones'])->filter(fn ($valor) => $valor !== null)->count());

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
                'campos' => $camposAlumno,
                'materias' => collect($materiasDetalle)
                    ->sortBy([['orden', 'asc'], ['materia', 'asc']])
                    ->values()
                    ->all(),
                'promedios_periodo_precisos' => $periodosPromedio,
                'periodos_completos' => $periodosCompletos,
                'promedio_general_preciso' => $promedioGeneralPreciso,
                'promedio_general' => PromedioExcel::formatear($promedioGeneralPreciso, 1, '—'),
                'promedio_provisional_preciso' => $promedioProvisionalPreciso,
                'promedio_provisional' => PromedioExcel::formatear($promedioProvisionalPreciso, 1, '—'),
                'completo' => $todosLosCamposCompletos,
                'materias_capturadas' => $totalCapturasMaterias,
                'promocion_sugerida' => $promocionSugerida,
                'promocion_confirmada' => $decision?->promocion_confirmada,
                'promocion_confirmada_at' => optional($decision?->confirmada_at)->format('d/m/Y H:i'),
                'motivo_promocion' => $decision?->motivo,
                'campos_reprobados' => collect($camposAlumno)
                    ->filter(fn (array $campo) => $campo['final_preciso'] !== null && $campo['final_preciso'] < 6)
                    ->pluck('campo')
                    ->values()
                    ->all(),
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
                $conPromedio = $items
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
                    'promedio_preciso' => PromedioExcel::calcular($conPromedio),
                    'promedio' => PromedioExcel::formatear(PromedioExcel::calcular($conPromedio), 1, '—'),
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
            'campos' => $campos,
            'alumnos' => $filas,
            'grupos' => $gruposReporte,
            'resumen' => [
                'total_alumnos' => $filas->count(),
                'completos' => $filas->where('completo', true)->count(),
                'pendientes' => $filas->where('completo', false)->count(),
                'promedio_general_preciso' => PromedioExcel::calcular($promediosCompletos),
                'promedio_general' => PromedioExcel::formatear(PromedioExcel::calcular($promediosCompletos), 1, '—'),
                'promovidos_confirmados' => $filas->where('promocion_confirmada', true)->count(),
                'no_promovidos_confirmados' => $filas->where('promocion_confirmada', false)->count(),
            ],
        ];
    }

    private function reporteVacio(?Collection $campos = null): array
    {
        return [
            'campos' => $campos ?? collect(),
            'alumnos' => collect(),
            'grupos' => collect(),
            'resumen' => [
                'total_alumnos' => 0,
                'completos' => 0,
                'pendientes' => 0,
                'promedio_general_preciso' => null,
                'promedio_general' => '—',
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
