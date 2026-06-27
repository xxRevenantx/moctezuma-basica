<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Support\PromedioExcel;
use App\Services\CalificacionOficialPrimariaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Exports\PromediosGeneralesExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromediosGenerales extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $cicloEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;

    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';
    public string $semestre_id = '';

    public string $orden = 'nombre_asc';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->grupos = collect();
        $this->semestres = collect();

        $this->ciclo_escolar_id = (string) ($this->cicloEscolares->first()?->id ?? '');

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = '';
        $this->semestre_id = '';

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
        $this->semestre_id = '';

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function limpiarFiltros(): void
    {
        $this->ciclo_escolar_id = (string) ($this->cicloEscolares->first()?->id ?? '');
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->semestre_id = '';
        $this->orden = 'promedio_desc';

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function getEsBachilleratoProperty(): bool
    {
        return $this->nivel?->slug === 'bachillerato' || (int) ($this->nivel?->id ?? 0) === 4;
    }

    public function getEncabezadosPeriodosProperty(): array
    {
        if ($this->esBachillerato) {
            return [
                1 => '1er parcial',
                2 => '2do parcial',
            ];
        }

        return [
            1 => '1er periodo',
            2 => '2do periodo',
            3 => '3er periodo',
        ];
    }

    public function getConcentradoProperty(): array
    {
        $alumnos = $this->obtenerAlumnosConPromedio();

        return [
            'resumen' => $this->construirResumen($alumnos),
            'grupos' => $this->agruparAlumnos($alumnos),
            'grafica' => $this->construirDatosGrafica($alumnos),
        ];
    }

    private function cargarGrupos(): void
    {
        $this->grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'semestre:id,numero,grado_id',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->when(
                $this->generacion_id !== '',
                fn($query) => $query->where('generacion_id', $this->generacion_id)
            )
            ->when(
                $this->grado_id !== '',
                fn($query) => $query->where('grado_id', $this->grado_id)
            )
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->sort(function (Grupo $grupoA, Grupo $grupoB): int {
                /*
             * Primero se ordena por el campo "orden" del grado.
             */
                $ordenGradoA = (int) ($grupoA->grado?->orden ?? PHP_INT_MAX);
                $ordenGradoB = (int) ($grupoB->grado?->orden ?? PHP_INT_MAX);

                $comparacion = $ordenGradoA <=> $ordenGradoB;

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                /*
             * Después se ordena naturalmente por el nombre del grupo:
             * A, B, C...
             * 1, 2, 3, 10...
             */
                $nombreGrupoA = trim((string) ($grupoA->asignacionGrupo?->nombre ?? ''));
                $nombreGrupoB = trim((string) ($grupoB->asignacionGrupo?->nombre ?? ''));

                $comparacion = strnatcasecmp($nombreGrupoA, $nombreGrupoB);

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                /*
             * Si es bachillerato y coinciden grado y grupo,
             * finalmente se ordena por semestre.
             */
                $semestreA = (int) ($grupoA->semestre?->numero ?? PHP_INT_MAX);
                $semestreB = (int) ($grupoB->semestre?->numero ?? PHP_INT_MAX);

                return $semestreA <=> $semestreB;
            })
            ->values();
    }

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->whereHas('grado', fn($query) => $query->where('nivel_id', $this->nivel->id))
            ->when($this->grado_id !== '', fn($query) => $query->where('grado_id', $this->grado_id))
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global']);
    }

    private function obtenerAlumnosConPromedio(): Collection
    {
        if ($this->ciclo_escolar_id === '') {
            return collect();
        }

        if (($this->nivel?->slug ?? null) === 'primaria') {
            return $this->obtenerAlumnosOficialesPrimaria();
        }

        $campoPeriodo = $this->esBachillerato
            ? 'periodos.parcial_bachillerato_id'
            : 'periodos.periodo_basica_id';

        $limitePeriodos = $this->esBachillerato ? [1, 2] : [1, 2, 3];

        /*
         * Se consultan las calificaciones sin agrupar en SQL.
         * El promedio por periodo se calcula en PHP para aplicar promedio-numerico-pro.
         */
        $filas = DB::table('calificaciones')
            ->join('periodos', 'periodos.id', '=', 'calificaciones.periodo_id')
            ->join('inscripciones', 'inscripciones.id', '=', 'calificaciones.inscripcion_id')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->join('grados', 'grados.id', '=', 'calificaciones.grado_id')
            ->join('grupos', 'grupos.id', '=', 'calificaciones.grupo_id')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->leftJoin('semestres', 'semestres.id', '=', 'calificaciones.semestre_id')
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.activo', true)
            ->where('calificaciones.nivel_id', $this->nivel->id)
            ->where('calificaciones.ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('calificaciones.es_numerica', true)
            ->whereNotNull('calificaciones.valor_numerico')
            ->whereBetween('calificaciones.valor_numerico', [0, 10])
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->when(! $this->esBachillerato, fn ($query) =>
                $query->where('materias.participa_en_calificacion_oficial', true)
            )
            ->whereIn(DB::raw($campoPeriodo), $limitePeriodos)
            ->when($this->generacion_id !== '', fn($query) => $query->where('calificaciones.generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($query) => $query->where('calificaciones.grado_id', $this->grado_id))
            ->when($this->grupo_id !== '', fn($query) => $query->where('calificaciones.grupo_id', $this->grupo_id))
            ->when($this->semestre_id !== '', fn($query) => $query->where('calificaciones.semestre_id', $this->semestre_id))
            ->selectRaw('
                calificaciones.id as calificacion_id,
                calificaciones.inscripcion_id,
                calificaciones.generacion_id,
                calificaciones.grado_id,
                calificaciones.grupo_id,
                calificaciones.semestre_id,
                calificaciones.asignacion_materia_id,
                calificaciones.valor_numerico,
                inscripciones.matricula,
                inscripciones.nombre,
                inscripciones.apellido_paterno,
                inscripciones.apellido_materno,
                grados.nombre as grado_nombre,
                grados.orden as grado_orden,
                asignacion_grupos.nombre as grupo_nombre,
                semestres.numero as semestre_numero,
                ' . $campoPeriodo . ' as numero_periodo,
                asignacion_materias.orden as orden_materia
            ')
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->orderBy('calificaciones.id')
            ->get();

        $alumnos = $filas
            ->groupBy('inscripcion_id')
            ->map(function (Collection $registros) use ($limitePeriodos) {
                $primero = $registros->first();

                $periodos = [];
                $materiasCapturadas = 0;

                foreach ($limitePeriodos as $periodo) {
                    /*
                     * promedio-numerico-pro por periodo:
                     * Se recorren las materias ordenadas por asignacion_materias.orden.
                     * Solo se suman valores numéricos y no se usa take().
                     */
                    $registrosPeriodo = $registros
                        ->filter(fn($registro) => (int) $registro->numero_periodo === (int) $periodo)
                        ->sortBy([
                            fn($registro) => $registro->orden_materia === null ? 1 : 0,
                            fn($registro) => $registro->orden_materia ?? 999,
                            fn($registro) => $registro->asignacion_materia_id ?? 999,
                            fn($registro) => $registro->calificacion_id ?? 999,
                        ])
                        ->groupBy('asignacion_materia_id')
                        ->map(fn(Collection $items) => $items->last())
                        ->values();

                    $valoresNumericos = $registrosPeriodo
                        ->pluck('valor_numerico')
                        ->filter(fn($valor) => is_numeric($valor) && (float) $valor >= 0 && (float) $valor <= 10)
                        ->map(fn($valor) => (float) $valor)
                        ->values();

                    $totalNumericas = $valoresNumericos->count();

                    // Se conserva toda la precisión. El truncamiento se aplica
                    // únicamente cuando el valor se presenta en pantalla, PDF o Excel.
                    $periodos[$periodo] = PromedioExcel::calcular($valoresNumericos);

                    $materiasCapturadas += $totalNumericas;
                }

                $periodosCapturados = collect($periodos)
                    ->filter(fn($valor) => $valor !== null)
                    ->count();

                $sumaPeriodos = collect($periodos)
                    ->filter(fn($valor) => $valor !== null)
                    ->sum();

                $totalEsperado = count($limitePeriodos);

                /*
                 * Regla equivalente a PROMEDIO de Excel:
                 * - promedia únicamente evaluaciones numéricas disponibles;
                 * - no cuenta vacíos ni textos como cero;
                 * - si falta una evaluación, el resultado es provisional;
                 * - no se truncan resultados intermedios.
                 */
                $promedioFinal = PromedioExcel::calcular($periodos);

                return [
                    'inscripcion_id' => (int) $primero->inscripcion_id,
                    'generacion_id' => (int) $primero->generacion_id,
                    'matricula' => $primero->matricula,
                    'alumno' => trim(($primero->apellido_paterno ?? '') . ' ' . ($primero->apellido_materno ?? '') . ' ' . ($primero->nombre ?? '')),
                    'grado_id' => (int) $primero->grado_id,
                    'grado' => $primero->grado_nombre,
                    'grado_orden' => (int) ($primero->grado_orden ?? 0),
                    'grupo_id' => (int) $primero->grupo_id,
                    'grupo' => $primero->grupo_nombre ?? 'Sin grupo',
                    'semestre_id' => $primero->semestre_id ? (int) $primero->semestre_id : null,
                    'semestre' => $primero->semestre_numero ? (int) $primero->semestre_numero : null,
                    'periodos' => $periodos,
                    'suma_periodos' => (float) $sumaPeriodos,
                    'promedio_final' => $promedioFinal,
                    'periodos_capturados' => $periodosCapturados,
                    'periodos_faltantes' => max($totalEsperado - $periodosCapturados, 0),
                    'materias_capturadas' => $materiasCapturadas,
                    'estatus' => $this->obtenerEstatusPromedio($promedioFinal, $periodosCapturados, $totalEsperado),
                ];
            })
            ->values();

        $alumnos = $this->asignarLugaresPorGrupo($alumnos);

        return $this->ordenarAlumnos($alumnos);
    }


    private function obtenerAlumnosOficialesPrimaria(): Collection
    {
        $reporte = app(CalificacionOficialPrimariaService::class)->reporteAnual(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
        );

        $alumnos = collect($reporte['alumnos'])->map(function (array $fila): array {
            $periodos = $fila['promedios_periodo_precisos'];
            $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();

            return [
                'inscripcion_id' => $fila['inscripcion_id'],
                'generacion_id' => $fila['generacion_id'],
                'matricula' => $fila['matricula'],
                'alumno' => $fila['alumno'],
                'grado_id' => $fila['grado_id'],
                'grado' => $fila['grado'],
                'grado_orden' => $fila['grado_orden'],
                'grupo_id' => $fila['grupo_id'],
                'grupo' => $fila['grupo'],
                'semestre_id' => null,
                'semestre' => null,
                'periodos' => $periodos,
                'suma_periodos' => (float) collect($periodos)->filter(fn ($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => max(3 - $capturados, 0),
                'materias_capturadas' => collect($fila['campos'])->sum('capturados'),
                'estatus' => $this->obtenerEstatusPromedio(
                    $fila['promedio_general_preciso'],
                    $fila['completo'] ? 3 : $capturados,
                    3
                ),
                'fuente_oficial' => true,
                'promocion_sugerida' => $fila['promocion_sugerida'],
                'promocion_confirmada' => $fila['promocion_confirmada'],
            ];
        })->values();

        $alumnos = $this->asignarLugaresPorGrupo($alumnos);

        return $this->ordenarAlumnos($alumnos);
    }

    private function asignarLugaresPorGrupo(Collection $alumnos): Collection
    {
        /*
         * Los lugares se calculan por separado dentro de cada grupo.
         * Ejemplo: 1° de primaria grupo A tiene sus propios lugares,
         * y 1° de primaria grupo B inicia nuevamente desde 1° lugar.
         * Los empates comparten el mismo lugar.
         */
        return $alumnos
            ->groupBy(fn(array $alumno) => $this->claveGrupoAlumno($alumno))
            ->flatMap(function (Collection $items) {
                $promediosUnicosDesc = $items
                    ->filter(fn(array $alumno) => ($alumno['promedio_final'] ?? null) !== null)
                    ->sortByDesc('promedio_final')
                    ->pluck('promedio_final')
                    ->map(fn($promedio) => PromedioExcel::claveComparacion($promedio))
                    ->unique()
                    ->values();

                return $items->map(function (array $alumno) use ($promediosUnicosDesc) {
                    $promedio = $alumno['promedio_final'] ?? null;

                    if ($promedio === null || !is_numeric($promedio)) {
                        $alumno['lugar'] = null;
                        $alumno['texto_lugar'] = 'Pendiente';

                        return $alumno;
                    }

                    $clavePromedio = PromedioExcel::claveComparacion($promedio);
                    $indiceLugar = $promediosUnicosDesc->search($clavePromedio);

                    $lugar = $indiceLugar !== false
                        ? $indiceLugar + 1
                        : null;

                    $alumno['lugar'] = $lugar;
                    $alumno['texto_lugar'] = $lugar ? $lugar . '° lugar' : 'Pendiente';

                    return $alumno;
                });
            })
            ->values();
    }

    private function claveGrupoAlumno(array $alumno): string
    {
        /*
         * Esta clave evita que los lugares se mezclen entre grados, grupos o semestres.
         */
        return implode('|', [
            $alumno['grado_id'] ?? 'grado',
            $alumno['grupo_id'] ?? 'grupo',
            $this->esBachillerato ? ($alumno['semestre_id'] ?? 'semestre') : 'basica',
        ]);
    }

    private function ordenarAlumnos(Collection $alumnos): Collection
    {
        return match ($this->orden) {
            'promedio_asc' => $alumnos->sortBy([
                ['promedio_final', 'asc'],
                ['alumno', 'asc'],
            ])->values(),
            'nombre_asc' => $alumnos->sortBy('alumno')->values(),
            default => $alumnos->sortByDesc('promedio_final')->values(),
        };
    }

    private function agruparAlumnos(Collection $alumnos): Collection
    {
        return $alumnos
            /*
         * Se agrupa usando los identificadores reales del grado,
         * grupo y semestre, evitando mezclar grupos con títulos iguales.
         */
            ->groupBy(
                fn(array $alumno) => $this->claveGrupoAlumno($alumno)
            )
            ->map(function (Collection $items) {
                $primero = $items->first();

                $titulo = ($primero['grado'] ?? 'Sin grado')
                    . ' · Grupo '
                    . ($primero['grupo'] ?? 'Sin grupo');

                if ($this->esBachillerato) {
                    $titulo .= ' · Semestre '
                        . ($primero['semestre'] ?? '—');
                }

                return [
                    /*
                 * Campos internos utilizados solamente para ordenar.
                 */
                    '_grado_orden' => (int) (
                        $primero['grado_orden'] ?? PHP_INT_MAX
                    ),

                    '_grado_id' => (int) (
                        $primero['grado_id'] ?? PHP_INT_MAX
                    ),

                    '_grupo_nombre' => trim(
                        (string) ($primero['grupo'] ?? '')
                    ),

                    '_grupo_id' => (int) (
                        $primero['grupo_id'] ?? PHP_INT_MAX
                    ),

                    '_semestre' => (int) (
                        $primero['semestre'] ?? PHP_INT_MAX
                    ),

                    'titulo' => $titulo,

                    'total' => $items->count(),

                    'promedio' => $this->formatearDecimal(
                        PromedioExcel::calcular(
                            $items
                                ->pluck('promedio_final')
                                ->filter(fn($valor) => $valor !== null)
                        )
                    ),

                    'aprobados' => $items
                        ->filter(
                            fn(array $item) => ($item['promedio_final'] ?? 0) >= 6
                        )
                        ->count(),

                    'riesgo' => $items
                        ->filter(
                            fn(array $item) => ($item['promedio_final'] ?? null) !== null
                                && ($item['promedio_final'] ?? 0) < 6
                        )
                        ->count(),

                    'incompletos' => $items
                        ->filter(
                            fn(array $item) => ($item['periodos_faltantes'] ?? 0) > 0
                        )
                        ->count(),

                    /*
                 * Los alumnos conservan el orden elegido:
                 * promedio mayor, promedio menor o nombre.
                 */
                    'alumnos' => $items->values(),
                ];
            })
            ->sort(function (array $grupoA, array $grupoB): int {
                /*
             * 1. Orden del grado.
             */
                $comparacion = $grupoA['_grado_orden']
                    <=> $grupoB['_grado_orden'];

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                /*
             * 2. Nombre del grupo.
             */
                $comparacion = strnatcasecmp(
                    $grupoA['_grupo_nombre'],
                    $grupoB['_grupo_nombre']
                );

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                /*
             * 3. Semestre.
             */
                $comparacion = $grupoA['_semestre']
                    <=> $grupoB['_semestre'];

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                /*
             * 4. Identificadores como respaldo.
             */
                $comparacion = $grupoA['_grado_id']
                    <=> $grupoB['_grado_id'];

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                return $grupoA['_grupo_id']
                    <=> $grupoB['_grupo_id'];
            })
            ->map(function (array $grupo): array {
                /*
             * Se eliminan los campos internos antes de enviarlos al Blade.
             */
                unset(
                    $grupo['_grado_orden'],
                    $grupo['_grado_id'],
                    $grupo['_grupo_nombre'],
                    $grupo['_grupo_id'],
                    $grupo['_semestre']
                );

                return $grupo;
            })
            ->values();
    }

    private function construirResumen(Collection $alumnos): array
    {
        $conPromedio = $alumnos->filter(fn($alumno) => $alumno['promedio_final'] !== null);
        $mejor = $conPromedio->sortByDesc('promedio_final')->first();

        return [
            'total_alumnos' => $alumnos->count(),
            'promedio_general' => $this->formatearDecimal(PromedioExcel::calcular($conPromedio->pluck('promedio_final'))),
            'aprobados' => $conPromedio->filter(fn($alumno) => $alumno['promedio_final'] >= 6)->count(),
            'riesgo' => $conPromedio->filter(fn($alumno) => $alumno['promedio_final'] < 6)->count(),
            'incompletos' => $alumnos->filter(fn($alumno) => $alumno['periodos_faltantes'] > 0)->count(),
            'mejor_alumno' => $mejor['alumno'] ?? 'Sin datos',
            'mejor_promedio' => $this->formatearDecimal($mejor['promedio_final'] ?? null),
        ];
    }

    private function construirDatosGrafica(Collection $alumnos): array
    {
        $porGrupo = $this->agruparAlumnos($alumnos);

        return [
            'categorias' => $porGrupo->pluck('titulo')->values()->all(),
            'promedios' => $porGrupo->pluck('promedio')->map(fn($valor) => (float) $valor)->values()->all(),
            'aprobados' => $porGrupo->pluck('aprobados')->values()->all(),
            'riesgo' => $porGrupo->pluck('riesgo')->values()->all(),
            'incompletos' => $porGrupo->pluck('incompletos')->values()->all(),
        ];
    }

    private function obtenerEstatusPromedio(?float $promedio, int $capturados, int $esperados): string
    {
        if ($capturados === 0) {
            return 'Sin captura';
        }

        if ($capturados < $esperados) {
            return 'Incompleto';
        }

        if (($promedio ?? 0) < 6) {
            return 'En riesgo';
        }

        if (($promedio ?? 0) >= 9) {
            return 'Destacado';
        }

        return 'Aprobado';
    }

    public function formatearDecimal(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '0.0');
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $concentrado = $this->concentrado;

        $nombreArchivo = 'PROMEDIOS_GENERALES_' .
            Str::slug($this->nivel?->nombre ?? $this->slug_nivel, '_') .
            '_' .
            now()->format('Y_m_d_H_i_s') .
            '.xlsx';

        return Excel::download(
            new PromediosGeneralesExport(
                nivelNombre: $this->nivel?->nombre ?? 'Nivel',
                esBachillerato: $this->esBachillerato,
                encabezadosPeriodos: $this->encabezadosPeriodos,
                resumen: $concentrado['resumen'],
                gruposPromedios: $concentrado['grupos'],
                filtros: [
                    'Nivel' => $this->nivel?->nombre ?? 'Sin nivel',
                    'Ciclo escolar' => $this->ciclo_escolar_id ?: 'Sin seleccionar',
                    'Generación' => $this->generacion_id ?: 'Todas',
                    'Grado' => $this->grado_id ?: 'Todos',
                    'Grupo' => $this->grupo_id ?: 'Todos',
                    'Semestre' => $this->esBachillerato ? ($this->semestre_id ?: 'Todos') : 'No aplica',
                    'Orden' => match ($this->orden) {
                        'promedio_asc' => 'Promedio menor a mayor',
                        'nombre_asc' => 'Nombre A-Z',
                        default => 'Promedio mayor a menor',
                    },
                ],
            ),
            $nombreArchivo
        );
    }

    public function render()
    {
        return view('livewire.accion.generales.promedios-generales');
    }
}
