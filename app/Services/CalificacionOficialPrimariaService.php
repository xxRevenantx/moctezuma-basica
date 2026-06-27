<?php

namespace App\Services;

use App\Models\CalificacionCampoFormativo;
use App\Models\CampoFormativo;
use App\Models\DecisionPromocionOficial;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\TrayectoriaAcademica;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalificacionOficialPrimariaService
{
    public function __construct(private readonly ListaAcademicaService $listas)
    {
    }

    public function campos(): Collection
    {
        return CampoFormativo::query()
            ->where('activo', true)
            ->where('slug', '!=', 'sin-campo-formativo')
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
     * Devuelve los alumnos del periodo y la sugerencia calculada con las
     * materias internas que participan en la calificación oficial.
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
                'calificaciones.inscripcion_id',
                'materias.campo_formativo_id',
                'materias.id as materia_id',
                'materias.materia',
                'calificaciones.valor_numerico',
            ])
            ->get()
            ->groupBy(fn ($fila) => $fila->inscripcion_id . '|' . $fila->campo_formativo_id);

        $oficiales = CalificacionCampoFormativo::query()
            ->where('periodo_id', $periodoId)
            ->whereIn('inscripcion_id', $alumnos->pluck('id')->all())
            ->get()
            ->keyBy(fn (CalificacionCampoFormativo $item) => $item->inscripcion_id . '|' . $item->campo_formativo_id);

        $filasAlumnos = $alumnos->map(function (Inscripcion $alumno) use ($campos, $filasInternas, $oficiales): array {
            $celdas = [];

            foreach ($campos as $campo) {
                $clave = $alumno->id . '|' . $campo->id;
                $materias = $filasInternas->get($clave, collect());
                $promedioPreciso = PromedioExcel::calcular($materias->pluck('valor_numerico'));
                $sugerenciaEntera = $promedioPreciso !== null
                    ? max(0, min(10, (int) floor($promedioPreciso + 0.000000001)))
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
     * Reporte anual oficial de primaria. Los cuatro campos tienen el mismo peso.
     * El promedio final oficial solo existe cuando están completos los 3 periodos
     * de los cuatro campos. Los cálculos conservan toda la precisión.
     */
    public function reporteAnual(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $inscripcionId = null,
    ): array {
        if (! $this->esPrimaria($nivelId)) {
            return $this->reporteVacio();
        }

        $campos = $this->campos();
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
            ->unique(fn (TrayectoriaAcademica $trayectoria) => implode('|', [
                $trayectoria->inscripcion_id,
                $trayectoria->grado_id,
                $trayectoria->grupo_id,
            ]))
            ->values();

        $idsAlumnos = $trayectorias->pluck('inscripcion_id')->unique()->values();

        if ($idsAlumnos->isEmpty()) {
            $idsAlumnos = CalificacionCampoFormativo::query()
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
                ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
                ->when($grupoId, fn ($query) => $query->where('grupo_id', $grupoId))
                ->when($inscripcionId, fn ($query) => $query->where('inscripcion_id', $inscripcionId))
                ->pluck('inscripcion_id')
                ->unique()
                ->values();
        }

        $registrosConsulta = CalificacionCampoFormativo::query()
            ->join('periodos', 'periodos.id', '=', 'calificaciones_campos_formativos.periodo_id')
            ->where('calificaciones_campos_formativos.ciclo_escolar_id', $cicloEscolarId)
            ->where('calificaciones_campos_formativos.nivel_id', $nivelId)
            ->when($generacionId, fn ($query) => $query->where('calificaciones_campos_formativos.generacion_id', $generacionId))
            ->when($gradoId, fn ($query) => $query->where('calificaciones_campos_formativos.grado_id', $gradoId))
            ->when($grupoId, fn ($query) => $query->where('calificaciones_campos_formativos.grupo_id', $grupoId))
            ->when($inscripcionId, fn ($query) => $query->where('calificaciones_campos_formativos.inscripcion_id', $inscripcionId))
            ->whereIn('calificaciones_campos_formativos.inscripcion_id', $idsAlumnos->all())
            ->whereIn('periodos.periodo_basica_id', [1, 2, 3])
            ->select([
                'calificaciones_campos_formativos.*',
                'periodos.periodo_basica_id as numero_periodo',
            ])
            ->get();

        $registros = $registrosConsulta
            ->groupBy(fn ($fila) => $fila->inscripcion_id . '|' . $fila->campo_formativo_id);

        // Cuando no exista trayectoria reconstruida, el propio registro oficial
        // conserva el grado, grupo y generación históricos del ciclo consultado.
        $contextosOficiales = $registrosConsulta
            ->sortByDesc('id')
            ->unique('inscripcion_id')
            ->keyBy('inscripcion_id');

        $decisiones = DecisionPromocionOficial::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('inscripcion_id', $idsAlumnos->all())
            ->get()
            ->keyBy(fn (DecisionPromocionOficial $item) => $item->inscripcion_id . '|' . $item->grado_id);

        $alumnosPorId = Inscripcion::withTrashed()
            ->whereIn('id', $idsAlumnos->all())
            ->get()
            ->keyBy('id');

        $trayectoriasPorAlumno = $trayectorias->keyBy('inscripcion_id');

        $filas = $idsAlumnos->map(function ($alumnoId) use (
            $alumnosPorId,
            $trayectoriasPorAlumno,
            $campos,
            $registros,
            $grados,
            $decisiones,
            $contextosOficiales,
            $gradoId,
            $grupoId,
            $generacionId,
        ): ?array {
            $alumno = $alumnosPorId->get($alumnoId);

            if (! $alumno) {
                return null;
            }

            $trayectoria = $trayectoriasPorAlumno->get($alumnoId);
            $contextoOficial = $contextosOficiales->get($alumnoId);
            $gradoActualId = (int) ($trayectoria?->grado_id
                ?? $contextoOficial?->grado_id
                ?? $gradoId
                ?? $alumno->grado_id);
            $grupoActualId = (int) ($trayectoria?->grupo_id
                ?? $contextoOficial?->grupo_id
                ?? $grupoId
                ?? $alumno->grupo_id);
            $generacionActualId = (int) ($trayectoria?->generacion_id
                ?? $contextoOficial?->generacion_id
                ?? $generacionId
                ?? $alumno->generacion_id);
            $grado = $grados->get($gradoActualId) ?: Grado::query()->find($gradoActualId);
            $grupoNombre = $trayectoria?->grupo?->asignacionGrupo?->nombre
                ?? $alumno->grupo?->asignacionGrupo?->nombre
                ?? 'Sin grupo';

            $camposAlumno = [];
            $finalesCompletos = [];
            $promediosPorPeriodo = [1 => [], 2 => [], 3 => []];
            $tienePendientes = false;

            foreach ($campos as $campo) {
                $items = $registros->get($alumnoId . '|' . $campo->id, collect());
                $periodos = [];

                foreach ([1, 2, 3] as $numero) {
                    $registro = $items
                        ->where('numero_periodo', $numero)
                        ->sortByDesc('id')
                        ->first();
                    $valor = $registro?->calificacion_oficial;
                    $periodos[$numero] = is_numeric($valor) ? (float) $valor : null;

                    if ($periodos[$numero] !== null) {
                        $promediosPorPeriodo[$numero][] = $periodos[$numero];
                    }
                }

                $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();
                $provisionalCalculo = PromedioExcel::calcular($periodos);
                $provisionalOficial = PromedioExcel::truncar($provisionalCalculo, 1);
                $finalCalculo = $capturados === 3 ? $provisionalCalculo : null;
                $finalOficial = $capturados === 3 ? PromedioExcel::truncar($finalCalculo, 1) : null;

                /*
                 * En la boleta SEP el promedio final de cada campo se expresa
                 * oficialmente con un decimal. El promedio final de grado se
                 * obtiene promediando esos cuatro valores oficiales mostrados.
                 * Así, 9.6 + 9.0 + 9.3 + 9.6 produce 9.3 al truncar el resultado.
                 */
                if ($finalOficial !== null) {
                    $finalesCompletos[] = $finalOficial;
                } else {
                    $tienePendientes = true;
                }

                $camposAlumno[$campo->id] = [
                    'campo_id' => (int) $campo->id,
                    'campo' => $campo->nombre,
                    'slug' => $campo->slug,
                    'periodos' => $periodos,
                    'capturados' => $capturados,
                    'provisional_calculo_preciso' => $provisionalCalculo,
                    'provisional_preciso' => $provisionalOficial,
                    'provisional' => PromedioExcel::formatear($provisionalOficial, 1, '—'),
                    'final_calculo_preciso' => $finalCalculo,
                    'final_preciso' => $finalOficial,
                    'final' => PromedioExcel::formatear($finalOficial, 1, '—'),
                    'completo' => $capturados === 3,
                ];
            }

            $todosLosCamposCompletos = count($finalesCompletos) === $campos->count() && $campos->isNotEmpty();
            $promedioGeneralPreciso = $todosLosCamposCompletos
                ? PromedioExcel::calcular($finalesCompletos)
                : null;
            $promedioProvisionalPreciso = PromedioExcel::calcular(
                collect($camposAlumno)->pluck('provisional_preciso')
            );

            $esPrimerGrado = (int) ($grado?->orden ?? 0) === 1;
            $promocionSugerida = $esPrimerGrado
                ? true
                : ($promedioGeneralPreciso !== null ? $promedioGeneralPreciso >= 6.0 : null);
            $decision = $decisiones->get($alumnoId . '|' . $gradoActualId);

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
                'promedios_periodo_precisos' => [
                    1 => PromedioExcel::calcular($promediosPorPeriodo[1]),
                    2 => PromedioExcel::calcular($promediosPorPeriodo[2]),
                    3 => PromedioExcel::calcular($promediosPorPeriodo[3]),
                ],
                'promedio_general_preciso' => $promedioGeneralPreciso,
                'promedio_general' => PromedioExcel::formatear($promedioGeneralPreciso, 1, '—'),
                'promedio_provisional_preciso' => $promedioProvisionalPreciso,
                'promedio_provisional' => PromedioExcel::formatear($promedioProvisionalPreciso, 1, '—'),
                'completo' => $todosLosCamposCompletos && ! $tienePendientes,
                'promocion_sugerida' => $promocionSugerida,
                'promocion_confirmada' => $decision?->promocion_confirmada,
                'promocion_confirmada_at' => optional($decision?->confirmada_at)->format('d/m/Y H:i'),
                'motivo_promocion' => $decision?->motivo,
            ];
        })->filter()->sortBy([
            ['grado_orden', 'asc'],
            ['grupo', 'asc'],
            ['alumno', 'asc'],
        ])->values();

        $grupos = $filas
            ->groupBy(fn (array $fila) => $fila['grado_id'] . '|' . $fila['grupo_id'])
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $conPromedio = $items->pluck('promedio_general_preciso')->filter(fn ($valor) => $valor !== null);

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

        $promediosCompletos = $filas->pluck('promedio_general_preciso')->filter(fn ($valor) => $valor !== null);

        return [
            'campos' => $campos,
            'alumnos' => $filas,
            'grupos' => $grupos,
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

    private function reporteVacio(): array
    {
        return [
            'campos' => collect(),
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
