<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromedioAnualBachilleratoService
{
    public function __construct(
        private readonly PromedioBachilleratoService $semestral,
    ) {
    }

    public function reporteAnual(
        int $nivelId,
        int $cicloEscolarId,
        int $generacionId,
        string $orden = 'promedio_desc',
    ): array {
        $contexto = $this->resolverContexto(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
        );

        if (! $contexto['valido']) {
            return $this->reporteVacio($contexto);
        }

        $semestreInicial = $contexto['semestres'][0];
        $semestreFinal = $contexto['semestres'][1];

        $reporteInicial = $this->semestral->reporteSemestral(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            semestreId: (int) $semestreInicial->id,
        );

        $reporteFinal = $this->semestral->reporteSemestral(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            semestreId: (int) $semestreFinal->id,
        );

        $filasIniciales = collect($reporteInicial['alumnos'] ?? [])->keyBy('inscripcion_id');
        $filasFinales = collect($reporteFinal['alumnos'] ?? [])->keyBy('inscripcion_id');

        $idsAlumnos = $filasIniciales->keys()
            ->merge($filasFinales->keys())
            ->unique()
            ->values();

        $alumnos = $idsAlumnos
            ->map(function ($inscripcionId) use (
                $filasIniciales,
                $filasFinales,
                $semestreInicial,
                $semestreFinal,
                $contexto,
            ): array {
                $filaInicial = $filasIniciales->get($inscripcionId);
                $filaFinal = $filasFinales->get($inscripcionId);
                $base = $filaFinal ?: $filaInicial ?: [];

                $promedioInicial = $filaInicial['promedio_general_preciso'] ?? null;
                $promedioFinal = $filaFinal['promedio_general_preciso'] ?? null;

                $semestreInicialCompleto = (bool) ($filaInicial['completo'] ?? false);
                $semestreFinalCompleto = (bool) ($filaFinal['completo'] ?? false);
                $anualCompleto = $semestreInicialCompleto && $semestreFinalCompleto;

                $promedioAnual = $anualCompleto
                    ? PromedioExcel::calcular([$promedioInicial, $promedioFinal])
                    : null;

                $promedioProvisional = PromedioExcel::calcular([
                    $promedioInicial ?? ($filaInicial['promedio_provisional_preciso'] ?? null),
                    $promedioFinal ?? ($filaFinal['promedio_provisional_preciso'] ?? null),
                ]);

                $materiasReprobadasInicial = collect($filaInicial['materias_reprobadas'] ?? [])
                    ->map(fn (string $materia): string => 'Semestre ' . $semestreInicial->numero . ': ' . $materia);
                $materiasReprobadasFinal = collect($filaFinal['materias_reprobadas'] ?? [])
                    ->map(fn (string $materia): string => 'Semestre ' . $semestreFinal->numero . ': ' . $materia);
                $materiasReprobadas = $materiasReprobadasInicial
                    ->merge($materiasReprobadasFinal)
                    ->unique()
                    ->values()
                    ->all();

                $todasMateriasAcreditadas = $anualCompleto
                    && ($filaInicial['todas_materias_acreditadas'] ?? false) === true
                    && ($filaFinal['todas_materias_acreditadas'] ?? false) === true;

                $elegible = $anualCompleto
                    && $todasMateriasAcreditadas
                    && is_numeric($promedioAnual)
                    && (float) $promedioAnual >= 6.0;

                $faltantes = [];

                if (! $filaInicial) {
                    $faltantes[] = 'Sin información del semestre ' . $semestreInicial->numero;
                } elseif (! $semestreInicialCompleto) {
                    $faltantes[] = 'Semestre ' . $semestreInicial->numero . ' incompleto';
                }

                if (! $filaFinal) {
                    $faltantes[] = 'Sin información del semestre ' . $semestreFinal->numero;
                } elseif (! $semestreFinalCompleto) {
                    $faltantes[] = 'Semestre ' . $semestreFinal->numero . ' incompleto';
                }

                $estatus = match (true) {
                    ! $anualCompleto => 'Anual incompleto',
                    ! $todasMateriasAcreditadas => 'En riesgo · materias no acreditadas',
                    ! is_numeric($promedioAnual) => 'Anual incompleto',
                    (float) $promedioAnual >= 9.0 => 'Destacado',
                    (float) $promedioAnual >= 6.0 => 'Aprobado',
                    default => 'En riesgo',
                };

                return [
                    'inscripcion_id' => (int) ($base['inscripcion_id'] ?? $inscripcionId),
                    'generacion_id' => (int) ($base['generacion_id'] ?? $contexto['generacion']->id),
                    'matricula' => $base['matricula'] ?? '—',
                    'alumno' => $base['alumno'] ?? 'Alumno sin nombre',
                    'grado_id' => (int) ($base['grado_id'] ?? 0),
                    'grado' => $base['grado'] ?? ($contexto['nombre_anio'] ?? 'Año académico'),
                    'grado_orden' => (int) ($base['grado_orden'] ?? $contexto['numero_anio']),
                    'grupo_id' => (int) ($base['grupo_id'] ?? 0),
                    'grupo' => $base['grupo'] ?? 'Sin grupo',
                    'semestre_id' => null,
                    'semestre' => null,
                    'periodos' => [
                        1 => $promedioInicial,
                        2 => $promedioFinal,
                    ],
                    'periodos_completos' => [
                        1 => $semestreInicialCompleto,
                        2 => $semestreFinalCompleto,
                    ],
                    'suma_periodos' => (float) collect([$promedioInicial, $promedioFinal])
                        ->filter(fn ($valor) => is_numeric($valor))
                        ->sum(),
                    'promedio_final' => $promedioAnual,
                    'promedio_provisional' => $promedioProvisional,
                    'promedio_mostrado' => $promedioAnual ?? $promedioProvisional,
                    'periodos_capturados' => collect([$promedioInicial, $promedioFinal])
                        ->filter(fn ($valor) => is_numeric($valor))
                        ->count(),
                    'periodos_faltantes' => collect([$semestreInicialCompleto, $semestreFinalCompleto])
                        ->filter(fn (bool $completo) => ! $completo)
                        ->count(),
                    'materias_capturadas' => (int) ($filaInicial['materias_completas'] ?? 0)
                        + (int) ($filaFinal['materias_completas'] ?? 0),
                    'materias_esperadas' => (int) ($filaInicial['materias_esperadas'] ?? 0)
                        + (int) ($filaFinal['materias_esperadas'] ?? 0),
                    'completo' => $anualCompleto,
                    'campos' => [],
                    'materias' => [],
                    'campos_reprobados' => [],
                    'materias_reprobadas' => $materiasReprobadas,
                    'claves_especiales' => collect($filaInicial['claves_especiales'] ?? [])
                        ->merge($filaFinal['claves_especiales'] ?? [])
                        ->unique()
                        ->values()
                        ->all(),
                    'promocion_sugerida' => $elegible,
                    'promocion_confirmada' => null,
                    'lugar' => null,
                    'texto_lugar' => 'Pendiente',
                    'reconocimiento_disponible' => false,
                    'diploma_disponible' => false,
                    'es_grado_terminal' => (int) $semestreFinal->numero === 6,
                    'es_semestre_terminal' => (int) $semestreFinal->numero === 6,
                    'fuente_calculo' => 'promedio_anual_bachillerato',
                    'estatus' => $estatus,
                    'todas_materias_acreditadas' => $todasMateriasAcreditadas,
                    'elegible_reconocimiento' => $elegible,
                    'faltantes_anuales' => $faltantes,
                    'semestres_detalle' => [
                        (int) $semestreInicial->numero => $filaInicial,
                        (int) $semestreFinal->numero => $filaFinal,
                    ],
                    'semestre_inicial_numero' => (int) $semestreInicial->numero,
                    'semestre_final_numero' => (int) $semestreFinal->numero,
                ];
            })
            ->values();

        $alumnos = $this->asignarLugares($alumnos)
            ->sort(function (array $a, array $b) use ($orden): int {
                if ($orden === 'nombre_asc') {
                    return strnatcasecmp($a['alumno'] ?? '', $b['alumno'] ?? '');
                }

                $promedioA = $a['promedio_final'] ?? $a['promedio_provisional'] ?? -1;
                $promedioB = $b['promedio_final'] ?? $b['promedio_provisional'] ?? -1;
                $comparacion = (float) $promedioA <=> (float) $promedioB;

                if ($orden !== 'promedio_asc') {
                    $comparacion *= -1;
                }

                return $comparacion !== 0
                    ? $comparacion
                    : strnatcasecmp($a['alumno'] ?? '', $b['alumno'] ?? '');
            })
            ->values();

        $diagnostico = $this->diagnostico(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            semestreIds: [(int) $semestreInicial->id, (int) $semestreFinal->id],
            alumnos: $alumnos,
            semestreInicial: (int) $semestreInicial->numero,
            semestreFinal: (int) $semestreFinal->numero,
            alumnosSemestreInicial: collect($reporteInicial['alumnos'] ?? [])->count(),
            alumnosSemestreFinal: collect($reporteFinal['alumnos'] ?? [])->count(),
        );

        $definitivos = $alumnos
            ->where('completo', true)
            ->filter(fn (array $alumno) => is_numeric($alumno['promedio_final'] ?? null));
        $mejor = $definitivos->sortByDesc('promedio_final')->first();

        $grupo = [
            'titulo' => 'Generación ' . $contexto['generacion']->anio_ingreso . '-' . $contexto['generacion']->anio_egreso
                . ' · ' . $contexto['nombre_anio']
                . ' · Semestres ' . $semestreInicial->numero . ' y ' . $semestreFinal->numero,
            'total' => $alumnos->count(),
            'promedio' => PromedioExcel::formatear(
                PromedioExcel::calcular($definitivos->pluck('promedio_final')),
                1,
                '—'
            ),
            'aprobados' => $alumnos->where('elegible_reconocimiento', true)->count(),
            'riesgo' => $alumnos->filter(fn (array $alumno) => ($alumno['completo'] ?? false)
                && ! ($alumno['todas_materias_acreditadas'] ?? false))->count(),
            'incompletos' => $alumnos->where('completo', false)->count(),
            'con_reconocimiento' => $alumnos->where('reconocimiento_disponible', true)->count(),
            'pendientes_decision' => 0,
            'alumnos' => $alumnos,
        ];

        return [
            'alumnos' => $alumnos,
            'grupos' => collect([$grupo]),
            'resumen' => [
                'total_alumnos' => $alumnos->count(),
                'promedio_general' => PromedioExcel::formatear(
                    PromedioExcel::calcular($definitivos->pluck('promedio_final')),
                    1,
                    '—'
                ),
                'aprobados' => $alumnos->where('elegible_reconocimiento', true)->count(),
                'riesgo' => $alumnos->filter(fn (array $alumno) => ($alumno['completo'] ?? false)
                    && ! ($alumno['todas_materias_acreditadas'] ?? false))->count(),
                'incompletos' => $alumnos->where('completo', false)->count(),
                'pendientes_decision' => 0,
                'mejor_alumno' => $mejor['alumno'] ?? 'Sin datos',
                'mejor_promedio' => PromedioExcel::formatear($mejor['promedio_final'] ?? null, 1, '—'),
                'con_reconocimiento' => $alumnos->where('reconocimiento_disponible', true)->count(),
            ],
            'grafica' => [
                'categorias' => [$grupo['titulo']],
                'promedios' => [(float) ($grupo['promedio'] === '—' ? 0 : $grupo['promedio'])],
                'aprobados' => [$grupo['aprobados']],
                'riesgo' => [$grupo['riesgo']],
                'incompletos' => [$grupo['incompletos']],
            ],
            'campos' => collect(),
            'diagnostico' => $diagnostico,
            'contexto' => $contexto,
        ];
    }

    public function resolverContexto(int $nivelId, int $cicloEscolarId, int $generacionId): array
    {
        $nivel = Nivel::query()->find($nivelId);
        $ciclo = CicloEscolar::query()->find($cicloEscolarId);
        $generacion = Generacion::query()
            ->whereKey($generacionId)
            ->where('nivel_id', $nivelId)
            ->first();

        $errores = [];

        if (! $nivel || ! $this->semestral->esBachillerato($nivelId)) {
            $errores[] = 'El nivel seleccionado no corresponde a bachillerato.';
        }

        if (! $ciclo) {
            $errores[] = 'El ciclo escolar seleccionado no existe.';
        }

        if (! $generacion) {
            $errores[] = 'Selecciona una generación válida de bachillerato.';
        }

        $numeroAnio = null;
        $numerosSemestre = [];
        $semestres = collect();

        if ($ciclo && $generacion) {
            $diferencia = (int) $ciclo->inicio_anio - (int) $generacion->anio_ingreso;
            $numeroAnio = $diferencia + 1;

            if ($numeroAnio < 1 || $numeroAnio > 3) {
                $errores[] = 'La generación no corresponde académicamente al ciclo escolar seleccionado.';
            } else {
                $numerosSemestre = [($numeroAnio * 2) - 1, $numeroAnio * 2];
                $semestres = Semestre::query()
                    ->whereIn('numero', $numerosSemestre)
                    ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivelId))
                    ->orderBy('numero')
                    ->get(['id', 'grado_id', 'numero', 'orden_global']);

                if ($semestres->count() !== 2) {
                    $errores[] = 'No están configurados los dos semestres correspondientes al año académico.';
                }
            }
        }

        $nombreAnio = match ($numeroAnio) {
            1 => 'Primer año',
            2 => 'Segundo año',
            3 => 'Tercer año',
            default => 'Año no determinado',
        };

        return [
            'valido' => $errores === [],
            'errores' => $errores,
            'nivel' => $nivel,
            'ciclo' => $ciclo,
            'generacion' => $generacion,
            'numero_anio' => $numeroAnio,
            'nombre_anio' => $nombreAnio,
            'numeros_semestre' => $numerosSemestre,
            'semestres' => $semestres->values(),
        ];
    }

    private function asignarLugares(Collection $alumnos): Collection
    {
        $promediosUnicos = $alumnos
            ->where('elegible_reconocimiento', true)
            ->sortByDesc('promedio_final')
            ->pluck('promedio_final')
            ->map(fn ($promedio) => PromedioExcel::claveComparacion($promedio))
            ->filter()
            ->unique()
            ->values();

        return $alumnos->map(function (array $alumno) use ($promediosUnicos): array {
            if (! ($alumno['elegible_reconocimiento'] ?? false)) {
                return $alumno;
            }

            $clave = PromedioExcel::claveComparacion($alumno['promedio_final'] ?? null);
            $indice = $clave !== null ? $promediosUnicos->search($clave) : false;
            $lugar = $indice !== false ? ((int) $indice) + 1 : null;

            $alumno['lugar'] = $lugar;
            $alumno['texto_lugar'] = $lugar ? $lugar . '° lugar' : 'Pendiente';
            $alumno['reconocimiento_disponible'] = $lugar !== null && $lugar <= 3;

            return $alumno;
        })->values();
    }

    private function diagnostico(
        int $nivelId,
        int $cicloEscolarId,
        int $generacionId,
        array $semestreIds,
        Collection $alumnos,
        int $semestreInicial,
        int $semestreFinal,
        int $alumnosSemestreInicial,
        int $alumnosSemestreFinal,
    ): array {
        $duplicados = DB::table('calificaciones')
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('generacion_id', $generacionId)
            ->whereIn('semestre_id', $semestreIds)
            ->selectRaw('inscripcion_id, asignacion_materia_id, periodo_id, COUNT(*) as total')
            ->groupBy('inscripcion_id', 'asignacion_materia_id', 'periodo_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $inconsistenciasPeriodo = DB::table('calificaciones as c')
            ->join('periodos as p', 'p.id', '=', 'c.periodo_id')
            ->where('c.nivel_id', $nivelId)
            ->where('c.ciclo_escolar_id', $cicloEscolarId)
            ->where('c.generacion_id', $generacionId)
            ->whereIn('c.semestre_id', $semestreIds)
            ->where(function ($query): void {
                $query->whereColumn('p.nivel_id', '!=', 'c.nivel_id')
                    ->orWhereColumn('p.ciclo_escolar_id', '!=', 'c.ciclo_escolar_id')
                    ->orWhereColumn('p.generacion_id', '!=', 'c.generacion_id')
                    ->orWhereColumn('p.semestre_id', '!=', 'c.semestre_id');
            })
            ->count();

        $calificacionesFueraDeSemestre = DB::table('calificaciones')
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('generacion_id', $generacionId)
            ->whereNotNull('semestre_id')
            ->whereNotIn('semestre_id', $semestreIds)
            ->count();

        $semestresConGrupo = DB::table('grupos')
            ->where('nivel_id', $nivelId)
            ->where('generacion_id', $generacionId)
            ->whereIn('semestre_id', $semestreIds)
            ->pluck('semestre_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        $gruposFaltantes = collect($semestreIds)
            ->map(fn ($id) => (int) $id)
            ->diff($semestresConGrupo)
            ->values();

        $inconsistencias = $inconsistenciasPeriodo
            + $calificacionesFueraDeSemestre
            + $gruposFaltantes->count();

        $sinInicial = $alumnos->filter(fn (array $alumno) => empty($alumno['semestres_detalle'][$semestreInicial]))->count();
        $sinFinal = $alumnos->filter(fn (array $alumno) => empty($alumno['semestres_detalle'][$semestreFinal]))->count();

        $alertas = [];

        if ($alumnosSemestreInicial === 0) {
            $alertas[] = 'No se encontraron alumnos ni calificaciones válidas para el semestre ' . $semestreInicial . '.';
        }

        if ($alumnosSemestreFinal === 0) {
            $alertas[] = 'No se encontraron alumnos ni calificaciones válidas para el semestre ' . $semestreFinal . '.';
        }

        if ($gruposFaltantes->isNotEmpty()) {
            $alertas[] = $gruposFaltantes->count() . ' semestre(s) esperado(s) no tienen un grupo configurado para la generación seleccionada.';
        }

        if ($sinInicial > 0) {
            $alertas[] = $sinInicial . ' alumno(s) no tienen información del semestre ' . $semestreInicial . '.';
        }

        if ($sinFinal > 0) {
            $alertas[] = $sinFinal . ' alumno(s) no tienen información del semestre ' . $semestreFinal . '.';
        }

        if ($duplicados->isNotEmpty()) {
            $alertas[] = $duplicados->count() . ' combinación(es) de calificación duplicada fueron detectadas; se usa la captura más reciente.';
        }

        if ($inconsistenciasPeriodo > 0) {
            $alertas[] = $inconsistenciasPeriodo . ' calificación(es) no coinciden con la configuración de su periodo.';
        }

        if ($calificacionesFueraDeSemestre > 0) {
            $alertas[] = $calificacionesFueraDeSemestre . ' calificación(es) pertenecen a semestres distintos de los esperados para este ciclo y generación.';
        }

        return [
            'completos' => $alumnos->where('completo', true)->count(),
            'incompletos' => $alumnos->where('completo', false)->count(),
            'sin_semestre_inicial' => $sinInicial,
            'sin_semestre_final' => $sinFinal,
            'duplicados' => $duplicados->count(),
            'alumnos_con_duplicados' => $duplicados->pluck('inscripcion_id')->unique()->count(),
            'inconsistencias' => $inconsistencias,
            'inconsistencias_periodo' => $inconsistenciasPeriodo,
            'calificaciones_fuera_de_semestre' => $calificacionesFueraDeSemestre,
            'grupos_faltantes' => $gruposFaltantes->count(),
            'alertas' => array_values(array_unique($alertas)),
        ];
    }

    private function reporteVacio(array $contexto): array
    {
        return [
            'alumnos' => collect(),
            'grupos' => collect(),
            'resumen' => [
                'total_alumnos' => 0,
                'promedio_general' => '—',
                'aprobados' => 0,
                'riesgo' => 0,
                'incompletos' => 0,
                'pendientes_decision' => 0,
                'mejor_alumno' => 'Sin datos',
                'mejor_promedio' => '—',
                'con_reconocimiento' => 0,
            ],
            'grafica' => [
                'categorias' => [],
                'promedios' => [],
                'aprobados' => [],
                'riesgo' => [],
                'incompletos' => [],
            ],
            'campos' => collect(),
            'diagnostico' => [
                'completos' => 0,
                'incompletos' => 0,
                'sin_semestre_inicial' => 0,
                'sin_semestre_final' => 0,
                'duplicados' => 0,
                'alumnos_con_duplicados' => 0,
                'inconsistencias' => 0,
                'inconsistencias_periodo' => 0,
                'calificaciones_fuera_de_semestre' => 0,
                'grupos_faltantes' => 0,
                'alertas' => $contexto['errores'] ?? [],
            ],
            'contexto' => $contexto,
        ];
    }
}
