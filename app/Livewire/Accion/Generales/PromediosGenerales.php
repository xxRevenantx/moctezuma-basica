<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
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
            ->with(['asignacionGrupo:id,nombre', 'grado:id,nombre,orden', 'semestre:id,numero,grado_id'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->generacion_id !== '', fn($query) => $query->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($query) => $query->where('grado_id', $this->grado_id))
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('asignacion_grupo_id')
            ->get(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id']);
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

                    $periodos[$periodo] = $totalNumericas > 0
                        ? $this->redondearPromedio($valoresNumericos->sum() / $totalNumericas)
                        : null;

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
                 * Para primaria y secundaria el promedio final se divide entre los 3 periodos,
                 * aunque un periodo todavía no tenga promedio capturado.
                 * Si no existe ninguna captura numérica, se mantiene como pendiente.
                 * En bachillerato se conserva el promedio semestral con los parciales capturados.
                 */
                if (!$this->esBachillerato) {
                    $promedioFinal = $periodosCapturados > 0
                        ? $this->redondearPromedio($sumaPeriodos / $totalEsperado)
                        : null;
                } else {
                    $promedioFinal = $periodosCapturados > 0
                        ? $this->redondearPromedio($sumaPeriodos / $periodosCapturados)
                        : null;
                }

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
                    'suma_periodos' => $this->redondearPromedio($sumaPeriodos),
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
                    ->filter(fn(array $alumno) => ($alumno['promedio_final'] ?? null) !== null && (float) $alumno['promedio_final'] > 0)
                    ->sortByDesc('promedio_final')
                    ->pluck('promedio_final')
                    ->map(fn($promedio) => number_format((float) $promedio, 2, '.', ''))
                    ->unique()
                    ->values();

                return $items->map(function (array $alumno) use ($promediosUnicosDesc) {
                    $promedio = $alumno['promedio_final'] ?? null;

                    if ($promedio === null || !is_numeric($promedio) || (float) $promedio <= 0) {
                        $alumno['lugar'] = null;
                        $alumno['texto_lugar'] = 'Pendiente';

                        return $alumno;
                    }

                    $clavePromedio = number_format((float) $promedio, 2, '.', '');
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
            ->groupBy(function (array $alumno) {
                $base = $alumno['grado'] . ' · Grupo ' . $alumno['grupo'];

                if ($this->esBachillerato) {
                    $base .= ' · Semestre ' . ($alumno['semestre'] ?? '—');
                }

                return $base;
            })
            ->map(function (Collection $items, string $titulo) {
                return [
                    'titulo' => $titulo,
                    'total' => $items->count(),
                    'promedio' => $this->formatearDecimal($items->avg('promedio_final')),
                    'aprobados' => $items->filter(fn($item) => ($item['promedio_final'] ?? 0) >= 6)->count(),
                    'riesgo' => $items->filter(fn($item) => ($item['promedio_final'] ?? 0) > 0 && ($item['promedio_final'] ?? 0) < 6)->count(),
                    'incompletos' => $items->filter(fn($item) => $item['periodos_faltantes'] > 0)->count(),
                    'alumnos' => $items->values(),
                ];
            })
            ->values();
    }

    private function construirResumen(Collection $alumnos): array
    {
        $conPromedio = $alumnos->filter(fn($alumno) => $alumno['promedio_final'] !== null);
        $mejor = $conPromedio->sortByDesc('promedio_final')->first();

        return [
            'total_alumnos' => $alumnos->count(),
            'promedio_general' => $this->formatearDecimal($conPromedio->avg('promedio_final')),
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

    private function redondearPromedio(float $valor): float
    {
        /*
         * promedio-numerico-pro:
         * Se toma solo el primer decimal sin redondear.
         * El pequeño ajuste evita cortes incorrectos por precisión decimal.
         * Ejemplo: 8.777777777777778 se muestra como 8.7.
         */
        return floor(($valor + 0.000000001) * 10) / 10;
    }

    public function formatearDecimal(null|int|float|string $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0.0';
        }

        return number_format($this->redondearPromedio((float) $valor), 1, '.', '');
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
