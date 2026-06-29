<?php

namespace App\Livewire\Accion\Generales;

use App\Exports\PromediosGeneralesExport;
use App\Models\CicloEscolar;
use App\Models\DecisionPromocionOficial;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\CalificacionOficialPrimariaService;
use App\Services\PromedioBachilleratoService;
use App\Services\PromedioSecundariaService;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
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
    public string $orden = 'promedio_desc';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->grupos = collect();
        $this->semestres = collect();

        $this->ciclo_escolar_id = (string) (
            $this->cicloEscolares->firstWhere('es_actual', true)?->id
            ?? $this->cicloEscolares->first()?->id
            ?? ''
        );

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
        $this->ciclo_escolar_id = (string) (
            $this->cicloEscolares->firstWhere('es_actual', true)?->id
            ?? $this->cicloEscolares->first()?->id
            ?? ''
        );
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->semestre_id = '';
        $this->orden = 'promedio_desc';

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function getEsPrimariaProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'primaria';
    }

    public function getEsSecundariaProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'secundaria';
    }

    public function getEsBachilleratoProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'bachillerato'
            || (int) ($this->nivel?->id ?? 0) === 4;
    }

    public function getEncabezadosPeriodosProperty(): array
    {
        return $this->esBachillerato
            ? [1 => '1er parcial', 2 => '2do parcial']
            : [1 => '1er periodo', 2 => '2do periodo', 3 => '3er periodo'];
    }

    public function getConcentradoProperty(): array
    {
        $alumnos = $this->obtenerAlumnosConPromedio();

        return [
            'resumen' => $this->construirResumen($alumnos),
            'grupos' => $this->agruparAlumnos($alumnos),
            'grafica' => $this->construirDatosGrafica($alumnos),
            'campos' => $this->esPrimaria
                ? app(CalificacionOficialPrimariaService::class)->campos()
                : collect(),
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
            ->when($this->generacion_id !== '', fn ($query) => $query->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn ($query) => $query->where('grado_id', $this->grado_id))
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->sort(function (Grupo $grupoA, Grupo $grupoB): int {
                $comparacion = (int) ($grupoA->grado?->orden ?? PHP_INT_MAX)
                    <=> (int) ($grupoB->grado?->orden ?? PHP_INT_MAX);

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = strnatcasecmp(
                    trim((string) ($grupoA->asignacionGrupo?->nombre ?? '')),
                    trim((string) ($grupoB->asignacionGrupo?->nombre ?? ''))
                );

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                return (int) ($grupoA->semestre?->numero ?? PHP_INT_MAX)
                    <=> (int) ($grupoB->semestre?->numero ?? PHP_INT_MAX);
            })
            ->values();
    }

    private function cargarSemestres(): void
    {
        if (! $this->esBachillerato) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->whereHas('grado', fn ($query) => $query->where('nivel_id', $this->nivel->id))
            ->when($this->grado_id !== '', fn ($query) => $query->where('grado_id', $this->grado_id))
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global']);
    }

    private function obtenerAlumnosConPromedio(): Collection
    {
        if ($this->ciclo_escolar_id === '') {
            return collect();
        }

        if ($this->esPrimaria) {
            return $this->agregarDisponibilidadDiploma($this->obtenerAlumnosPrimaria());
        }

        if ($this->esSecundaria) {
            return $this->agregarDisponibilidadDiploma($this->obtenerAlumnosSecundaria());
        }

        if ($this->esBachillerato) {
            return $this->obtenerAlumnosBachillerato();
        }

        return $this->agregarDisponibilidadDiploma($this->obtenerAlumnosGenericos());
    }

    private function agregarDisponibilidadDiploma(Collection $alumnos): Collection
    {
        $gradoTerminalId = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');

        $semestreTerminalNumero = $this->esBachillerato
            ? Semestre::query()
                ->whereHas('grado', fn ($query) => $query->where('nivel_id', $this->nivel->id))
                ->max('numero')
            : null;

        return $alumnos->map(function (array $alumno) use ($gradoTerminalId, $semestreTerminalNumero): array {
            $esGradoTerminal = $gradoTerminalId !== null
                && (int) ($alumno['grado_id'] ?? 0) === (int) $gradoTerminalId;

            $esSemestreTerminal = ! $this->esBachillerato
                || (
                    $semestreTerminalNumero !== null
                    && (int) ($alumno['semestre'] ?? 0) === (int) $semestreTerminalNumero
                );

            $diplomaDisponible = false;

            if ($esGradoTerminal && $esSemestreTerminal && ($alumno['completo'] ?? false)) {
                if (in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
                    $diplomaDisponible = ($alumno['promocion_confirmada'] ?? null) === true;
                } elseif ($this->esBachillerato) {
                    $diplomaDisponible = $this->estaAprobadoAcademicamente($alumno);
                }
            }

            $alumno['es_grado_terminal'] = $esGradoTerminal;
            $alumno['es_semestre_terminal'] = $esSemestreTerminal;
            $alumno['diploma_disponible'] = $diplomaDisponible;

            return $alumno;
        })->values();
    }

    private function obtenerAlumnosPrimaria(): Collection
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
            $periodosCompletos = $fila['periodos_completos'] ?? [1 => false, 2 => false, 3 => false];
            $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();

            $filaNormalizada = [
                'inscripcion_id' => $fila['inscripcion_id'],
                'trayectoria_academica_id' => $fila['trayectoria_academica_id'] ?? null,
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
                'periodos_completos' => $periodosCompletos,
                'suma_periodos' => (float) collect($periodos)->filter(fn ($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'promedio_provisional' => $fila['promedio_provisional_preciso'],
                'promedio_mostrado' => $fila['promedio_general_preciso'] ?? $fila['promedio_provisional_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => collect($periodosCompletos)->filter(fn ($valor) => ! $valor)->count(),
                'materias_capturadas' => $fila['materias_capturadas'] ?? 0,
                'completo' => (bool) $fila['completo'],
                'campos' => $fila['campos'] ?? [],
                'materias' => $fila['materias'] ?? [],
                'campos_reprobados' => $fila['campos_reprobados'] ?? [],
                'materias_reprobadas' => [],
                'promocion_sugerida' => $fila['promocion_sugerida'],
                'promocion_confirmada' => $fila['promocion_confirmada'],
                'fuente_calculo' => 'campos_formativos',
            ];

            $filaNormalizada['estatus'] = $this->obtenerEstatusAlumno($filaNormalizada);

            return $filaNormalizada;
        })->values();

        return $this->ordenarAlumnos($this->asignarLugaresPorGrupo($alumnos));
    }

    private function obtenerAlumnosSecundaria(): Collection
    {
        $reporte = app(PromedioSecundariaService::class)->reporteAnual(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
        );

        $alumnos = collect($reporte['alumnos'])->map(function (array $fila): array {
            $periodos = $fila['promedios_periodo_precisos'];
            $periodosCompletos = $fila['periodos_completos'] ?? [1 => false, 2 => false, 3 => false];
            $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();

            $filaNormalizada = [
                'inscripcion_id' => $fila['inscripcion_id'],
                'trayectoria_academica_id' => $fila['trayectoria_academica_id'] ?? null,
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
                'periodos_completos' => $periodosCompletos,
                'suma_periodos' => (float) collect($periodos)->filter(fn ($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'promedio_provisional' => $fila['promedio_provisional_preciso'],
                'promedio_mostrado' => $fila['promedio_general_preciso'] ?? $fila['promedio_provisional_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => collect($periodosCompletos)->filter(fn ($valor) => ! $valor)->count(),
                'materias_capturadas' => $fila['materias_completas'] ?? 0,
                'materias_esperadas' => $fila['materias_esperadas'] ?? 0,
                'completo' => (bool) $fila['completo'],
                'campos' => $fila['campos'] ?? [],
                'materias' => $fila['materias'] ?? [],
                'campos_reprobados' => [],
                'materias_reprobadas' => $fila['materias_reprobadas'] ?? [],
                'promocion_sugerida' => null,
                'promocion_confirmada' => $fila['promocion_confirmada'],
                'fuente_calculo' => 'materias',
            ];

            $filaNormalizada['estatus'] = $this->obtenerEstatusAlumno($filaNormalizada);

            return $filaNormalizada;
        })->values();

        return $this->ordenarAlumnos($this->asignarLugaresPorGrupo($alumnos));
    }

    private function obtenerAlumnosBachillerato(): Collection
    {
        $reporte = app(PromedioBachilleratoService::class)->reporteSemestral(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
            semestreId: $this->semestre_id !== '' ? (int) $this->semestre_id : null,
        );

        $alumnos = collect($reporte['alumnos'])->map(function (array $fila): array {
            $periodos = $fila['promedios_periodo_precisos'] ?? [1 => null, 2 => null];
            $periodosCompletos = $fila['periodos_completos'] ?? [1 => false, 2 => false];
            $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();

            $filaNormalizada = [
                'inscripcion_id' => $fila['inscripcion_id'],
                'trayectoria_academica_id' => $fila['trayectoria_academica_id'] ?? null,
                'generacion_id' => $fila['generacion_id'],
                'matricula' => $fila['matricula'],
                'alumno' => $fila['alumno'],
                'grado_id' => $fila['grado_id'],
                'grado' => $fila['grado'],
                'grado_orden' => $fila['grado_orden'],
                'grupo_id' => $fila['grupo_id'],
                'grupo' => $fila['grupo'],
                'semestre_id' => $fila['semestre_id'],
                'semestre' => $fila['semestre'],
                'periodos' => $periodos,
                'periodos_completos' => $periodosCompletos,
                'suma_periodos' => (float) collect($periodos)->filter(fn ($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'promedio_provisional' => $fila['promedio_provisional_preciso'],
                'promedio_mostrado' => $fila['promedio_general_preciso'] ?? $fila['promedio_provisional_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => collect($periodosCompletos)->filter(fn ($valor) => ! $valor)->count(),
                'materias_capturadas' => $fila['materias_completas'] ?? 0,
                'materias_esperadas' => $fila['materias_esperadas'] ?? 0,
                'completo' => (bool) ($fila['completo'] ?? false),
                'campos' => [],
                'materias' => $fila['materias'] ?? [],
                'campos_reprobados' => [],
                'materias_reprobadas' => $fila['materias_reprobadas'] ?? [],
                'claves_especiales' => $fila['claves_especiales'] ?? [],
                'promocion_sugerida' => $fila['aprobado_general'] ?? false,
                'promocion_confirmada' => null,
                'lugar' => $fila['lugar'] ?? null,
                'texto_lugar' => $fila['texto_lugar'] ?? 'Pendiente',
                'reconocimiento_disponible' => (bool) ($fila['reconocimiento_disponible'] ?? false),
                'diploma_disponible' => (bool) ($fila['diploma_disponible'] ?? false),
                'es_grado_terminal' => (int) ($fila['semestre'] ?? 0) === 6,
                'es_semestre_terminal' => (int) ($fila['semestre'] ?? 0) === 6,
                'fuente_calculo' => 'materias_bachillerato',
            ];

            $filaNormalizada['estatus'] = $this->obtenerEstatusAlumno($filaNormalizada);

            return $filaNormalizada;
        })->values();

        return $this->ordenarAlumnos($alumnos);
    }

    private function obtenerAlumnosGenericos(): Collection
    {
        $campoPeriodo = $this->esBachillerato
            ? 'periodos.parcial_bachillerato_id'
            : 'periodos.periodo_basica_id';
        $limitePeriodos = $this->esBachillerato ? [1, 2] : [1, 2, 3];

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
            ->where('calificaciones.nivel_id', $this->nivel->id)
            ->where('calificaciones.ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('calificaciones.es_numerica', true)
            ->whereNotNull('calificaciones.valor_numerico')
            ->whereBetween('calificaciones.valor_numerico', [0, 10])
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->whereIn(DB::raw($campoPeriodo), $limitePeriodos)
            ->when($this->generacion_id !== '', fn ($query) => $query->where('calificaciones.generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn ($query) => $query->where('calificaciones.grado_id', $this->grado_id))
            ->when($this->grupo_id !== '', fn ($query) => $query->where('calificaciones.grupo_id', $this->grupo_id))
            ->when($this->semestre_id !== '', fn ($query) => $query->where('calificaciones.semestre_id', $this->semestre_id))
            ->selectRaw(''
                . 'calificaciones.id as calificacion_id, '
                . 'calificaciones.inscripcion_id, '
                . 'calificaciones.generacion_id, '
                . 'calificaciones.grado_id, '
                . 'calificaciones.grupo_id, '
                . 'calificaciones.semestre_id, '
                . 'calificaciones.asignacion_materia_id, '
                . 'calificaciones.valor_numerico, '
                . 'inscripciones.matricula, '
                . 'inscripciones.nombre, '
                . 'inscripciones.apellido_paterno, '
                . 'inscripciones.apellido_materno, '
                . 'grados.nombre as grado_nombre, '
                . 'grados.orden as grado_orden, '
                . 'asignacion_grupos.nombre as grupo_nombre, '
                . 'semestres.numero as semestre_numero, '
                . $campoPeriodo . ' as numero_periodo, '
                . 'asignacion_materias.orden as orden_materia'
            )
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->orderBy('calificaciones.id')
            ->get();

        $alumnos = $filas
            ->groupBy('inscripcion_id')
            ->map(function (Collection $registros) use ($limitePeriodos): array {
                $primero = $registros->first();
                $periodos = [];
                $materiasCapturadas = 0;

                foreach ($limitePeriodos as $periodo) {
                    $registrosPeriodo = $registros
                        ->filter(fn ($registro) => (int) $registro->numero_periodo === (int) $periodo)
                        ->sortBy([
                            fn ($registro) => $registro->orden_materia === null ? 1 : 0,
                            fn ($registro) => $registro->orden_materia ?? 999,
                            fn ($registro) => $registro->asignacion_materia_id ?? 999,
                            fn ($registro) => $registro->calificacion_id ?? 999,
                        ])
                        ->groupBy('asignacion_materia_id')
                        ->map(fn (Collection $items) => $items->last())
                        ->values();

                    $valores = $registrosPeriodo
                        ->pluck('valor_numerico')
                        ->filter(fn ($valor) => is_numeric($valor) && (float) $valor >= 0 && (float) $valor <= 10)
                        ->map(fn ($valor) => (float) $valor)
                        ->values();

                    $periodos[$periodo] = PromedioExcel::calcular($valores);
                    $materiasCapturadas += $valores->count();
                }

                $capturados = collect($periodos)->filter(fn ($valor) => $valor !== null)->count();
                $completo = $capturados === count($limitePeriodos);
                $promedioProvisional = PromedioExcel::calcular($periodos);

                $fila = [
                    'inscripcion_id' => (int) $primero->inscripcion_id,
                    'trayectoria_academica_id' => null,
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
                    'periodos_completos' => collect($limitePeriodos)->mapWithKeys(fn ($numero) => [$numero => $periodos[$numero] !== null])->all(),
                    'suma_periodos' => (float) collect($periodos)->filter(fn ($valor) => $valor !== null)->sum(),
                    'promedio_final' => $completo ? $promedioProvisional : null,
                    'promedio_provisional' => $promedioProvisional,
                    'promedio_mostrado' => $promedioProvisional,
                    'periodos_capturados' => $capturados,
                    'periodos_faltantes' => max(count($limitePeriodos) - $capturados, 0),
                    'materias_capturadas' => $materiasCapturadas,
                    'completo' => $completo,
                    'campos' => [],
                    'materias' => [],
                    'campos_reprobados' => [],
                    'materias_reprobadas' => [],
                    'promocion_sugerida' => $completo ? $promedioProvisional >= 6 : null,
                    'promocion_confirmada' => null,
                    'fuente_calculo' => 'periodos',
                ];

                $fila['estatus'] = $this->obtenerEstatusAlumno($fila);

                return $fila;
            })
            ->values();

        return $this->ordenarAlumnos($this->asignarLugaresPorGrupo($alumnos));
    }

    private function asignarLugaresPorGrupo(Collection $alumnos): Collection
    {
        return $alumnos
            ->groupBy(fn (array $alumno) => $this->claveGrupoAlumno($alumno))
            ->flatMap(function (Collection $items): Collection {
                $promediosUnicosDesc = $items
                    ->filter(fn (array $alumno) => $this->esElegibleParaLugar($alumno))
                    ->sortByDesc('promedio_final')
                    ->pluck('promedio_final')
                    ->map(fn ($promedio) => PromedioExcel::claveComparacion($promedio))
                    ->filter()
                    ->unique()
                    ->values();

                return $items->map(function (array $alumno) use ($promediosUnicosDesc): array {
                    if (! $this->esElegibleParaLugar($alumno)) {
                        $alumno['lugar'] = null;
                        $alumno['texto_lugar'] = 'Pendiente';
                        return $alumno;
                    }

                    $indice = $promediosUnicosDesc->search(
                        PromedioExcel::claveComparacion($alumno['promedio_final'])
                    );
                    $lugar = $indice !== false ? ((int) $indice) + 1 : null;

                    $alumno['lugar'] = $lugar;
                    $alumno['texto_lugar'] = $lugar ? $lugar . '° lugar' : 'Pendiente';

                    return $alumno;
                });
            })
            ->values();
    }

    private function esElegibleParaLugar(array $alumno): bool
    {
        if (! ($alumno['completo'] ?? false) || ! is_numeric($alumno['promedio_final'] ?? null)) {
            return false;
        }

        if ($this->esPrimaria) {
            return ($alumno['promocion_sugerida'] ?? false) === true;
        }

        if ($this->esSecundaria) {
            return ($alumno['materias_reprobadas'] ?? []) === [];
        }

        return (float) $alumno['promedio_final'] >= 6.0;
    }

    private function claveGrupoAlumno(array $alumno): string
    {
        return implode('|', [
            $alumno['grado_id'] ?? 'grado',
            $alumno['grupo_id'] ?? 'grupo',
            $this->esBachillerato ? ($alumno['semestre_id'] ?? 'semestre') : 'basica',
        ]);
    }

    private function ordenarAlumnos(Collection $alumnos): Collection
    {
        return $alumnos->sort(function (array $a, array $b): int {
            if ($this->orden === 'nombre_asc') {
                return strnatcasecmp($a['alumno'] ?? '', $b['alumno'] ?? '');
            }

            $promedioA = $a['promedio_final'] ?? $a['promedio_provisional'] ?? -1;
            $promedioB = $b['promedio_final'] ?? $b['promedio_provisional'] ?? -1;
            $comparacion = (float) $promedioA <=> (float) $promedioB;

            if ($this->orden !== 'promedio_asc') {
                $comparacion *= -1;
            }

            return $comparacion !== 0
                ? $comparacion
                : strnatcasecmp($a['alumno'] ?? '', $b['alumno'] ?? '');
        })->values();
    }

    private function agruparAlumnos(Collection $alumnos): Collection
    {
        return $alumnos
            ->groupBy(fn (array $alumno) => $this->claveGrupoAlumno($alumno))
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $titulo = ($primero['grado'] ?? 'Sin grado') . ' · Grupo ' . ($primero['grupo'] ?? 'Sin grupo');

                if ($this->esBachillerato) {
                    $titulo .= ' · Semestre ' . ($primero['semestre'] ?? '—');
                }

                $promediosDefinitivos = $items
                    ->where('completo', true)
                    ->pluck('promedio_final')
                    ->filter(fn ($valor) => $valor !== null);

                return [
                    '_grado_orden' => (int) ($primero['grado_orden'] ?? PHP_INT_MAX),
                    '_grupo_nombre' => trim((string) ($primero['grupo'] ?? '')),
                    '_semestre' => (int) ($primero['semestre'] ?? PHP_INT_MAX),
                    'titulo' => $titulo,
                    'total' => $items->count(),
                    'promedio' => $this->formatearDecimal(PromedioExcel::calcular($promediosDefinitivos)),
                    'aprobados' => $items->filter(fn (array $item) => $this->estaAprobadoAcademicamente($item))->count(),
                    'riesgo' => $items->filter(fn (array $item) => $this->estaEnRiesgoAcademico($item))->count(),
                    'incompletos' => $items->where('completo', false)->count(),
                    'pendientes_decision' => $items->filter(fn (array $item) => ($item['completo'] ?? false)
                        && in_array($this->slug_nivel, ['primaria', 'secundaria'], true)
                        && ($item['promocion_confirmada'] ?? null) === null)->count(),
                    'alumnos' => $items->values(),
                ];
            })
            ->sort(function (array $a, array $b): int {
                $comparacion = $a['_grado_orden'] <=> $b['_grado_orden'];
                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = strnatcasecmp($a['_grupo_nombre'], $b['_grupo_nombre']);
                return $comparacion !== 0 ? $comparacion : ($a['_semestre'] <=> $b['_semestre']);
            })
            ->map(function (array $grupo): array {
                unset($grupo['_grado_orden'], $grupo['_grupo_nombre'], $grupo['_semestre']);
                return $grupo;
            })
            ->values();
    }

    private function construirResumen(Collection $alumnos): array
    {
        $definitivos = $alumnos
            ->where('completo', true)
            ->filter(fn (array $alumno) => is_numeric($alumno['promedio_final'] ?? null));
        $mejor = $definitivos->sortByDesc('promedio_final')->first();

        return [
            'total_alumnos' => $alumnos->count(),
            'promedio_general' => $this->formatearDecimal(PromedioExcel::calcular($definitivos->pluck('promedio_final'))),
            'aprobados' => $alumnos->filter(fn (array $alumno) => $this->estaAprobadoAcademicamente($alumno))->count(),
            'riesgo' => $alumnos->filter(fn (array $alumno) => $this->estaEnRiesgoAcademico($alumno))->count(),
            'incompletos' => $alumnos->where('completo', false)->count(),
            'pendientes_decision' => $alumnos->filter(fn (array $alumno) => ($alumno['completo'] ?? false)
                && in_array($this->slug_nivel, ['primaria', 'secundaria'], true)
                && ($alumno['promocion_confirmada'] ?? null) === null)->count(),
            'mejor_alumno' => $mejor['alumno'] ?? 'Sin datos',
            'mejor_promedio' => $this->formatearDecimal($mejor['promedio_final'] ?? null),
        ];
    }

    private function construirDatosGrafica(Collection $alumnos): array
    {
        $porGrupo = $this->agruparAlumnos($alumnos);

        return [
            'categorias' => $porGrupo->pluck('titulo')->values()->all(),
            'promedios' => $porGrupo->pluck('promedio')->map(fn ($valor) => (float) $valor)->values()->all(),
            'aprobados' => $porGrupo->pluck('aprobados')->values()->all(),
            'riesgo' => $porGrupo->pluck('riesgo')->values()->all(),
            'incompletos' => $porGrupo->pluck('incompletos')->values()->all(),
        ];
    }

    private function estaAprobadoAcademicamente(array $alumno): bool
    {
        if (! ($alumno['completo'] ?? false)) {
            return false;
        }

        if ($this->esPrimaria) {
            return ($alumno['promocion_sugerida'] ?? false) === true;
        }

        if ($this->esSecundaria) {
            return ($alumno['materias_reprobadas'] ?? []) === [];
        }

        return is_numeric($alumno['promedio_final'] ?? null)
            && (float) $alumno['promedio_final'] >= 6.0;
    }

    private function estaEnRiesgoAcademico(array $alumno): bool
    {
        if (! ($alumno['completo'] ?? false)) {
            return false;
        }

        if ($this->esPrimaria) {
            return ($alumno['promocion_sugerida'] ?? null) === false;
        }

        if ($this->esSecundaria) {
            return ($alumno['materias_reprobadas'] ?? []) !== [];
        }

        return is_numeric($alumno['promedio_final'] ?? null)
            && (float) $alumno['promedio_final'] < 6.0;
    }

    private function obtenerEstatusAlumno(array $alumno): string
    {
        $tieneDatos = collect($alumno['periodos'] ?? [])->contains(fn ($valor) => $valor !== null)
            || ($alumno['promedio_provisional'] ?? null) !== null;

        if (! $tieneDatos) {
            return 'Sin captura';
        }

        if (! ($alumno['completo'] ?? false)) {
            return 'Incompleto';
        }

        if (in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
            if (($alumno['promocion_confirmada'] ?? null) === true) {
                return 'Promovido';
            }

            if (($alumno['promocion_confirmada'] ?? null) === false) {
                return 'No promovido';
            }

            return $this->estaEnRiesgoAcademico($alumno)
                ? 'En riesgo · decisión pendiente'
                : 'Decisión pendiente';
        }

        $promedio = $alumno['promedio_final'] ?? null;

        if (! is_numeric($promedio)) {
            return 'Incompleto';
        }

        if ((float) $promedio < 6) {
            return 'En riesgo';
        }

        return (float) $promedio >= 9 ? 'Destacado' : 'Aprobado';
    }

    public function confirmarPromocion(int $inscripcionId, bool $promovido): void
    {
        if (! in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
            return;
        }

        $fila = $this->concentrado['grupos']
            ->flatMap(fn (array $grupo) => $grupo['alumnos'])
            ->firstWhere('inscripcion_id', $inscripcionId);

        if (! $fila) {
            $this->addError('promocion', 'No se encontró al alumno dentro del concentrado actual.');
            return;
        }

        if (! ($fila['completo'] ?? false) || ! is_numeric($fila['promedio_final'] ?? null)) {
            $this->addError('promocion', 'No se puede confirmar la promoción mientras existan periodos, campos o materias pendientes.');
            return;
        }

        DecisionPromocionOficial::query()->updateOrCreate(
            [
                'inscripcion_id' => $inscripcionId,
                'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                'grado_id' => (int) $fila['grado_id'],
            ],
            [
                'trayectoria_academica_id' => $fila['trayectoria_academica_id'] ?? null,
                'nivel_id' => (int) $this->nivel->id,
                'grupo_id' => (int) $fila['grupo_id'],
                'generacion_id' => (int) $fila['generacion_id'],
                'promedio_final' => (float) $fila['promedio_final'],
                'promocion_sugerida' => $this->esPrimaria ? $fila['promocion_sugerida'] : null,
                'promocion_confirmada' => $promovido,
                'confirmada_por' => auth()->id(),
                'confirmada_at' => now(),
            ]
        );


        $this->dispatch('swal', [
            'title' => $promovido ? 'Promoción confirmada' : 'No promoción confirmada',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function formatearDecimal(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $concentrado = $this->concentrado;

        $nombreArchivo = 'PROMEDIOS_GENERALES_'
            . Str::slug($this->nivel?->nombre ?? $this->slug_nivel, '_')
            . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(
            new PromediosGeneralesExport(
                nivelNombre: $this->nivel?->nombre ?? 'Nivel',
                nivelSlug: $this->slug_nivel,
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
