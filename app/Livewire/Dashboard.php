<?php

namespace App\Livewire;

use App\Models\Inscripcion;
use App\Services\ExpedienteDigitalService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Dashboard extends Component
{
    public $nivel_id = '';

    public array $niveles = [];
    public array $resumen = [];
    public array $resumenNiveles = [];
    public array $alertas = [];
    public array $periodosProximos = [];
    public array $alumnosDocumentosPendientes = [];
    public array $gruposSinHorario = [];
    public array $graficaAlumnosNivel = [];

    public function mount(): void
    {
        $this->cargarDashboard();
    }

    public function updatedNivelId(): void
    {
        $this->cargarDashboard();

        $this->dispatch('actualizarGraficaAlumnosNivel', data: $this->graficaAlumnosNivel);
    }

    public function cargarDashboard(): void
    {
        $this->niveles = $this->obtenerNiveles();
        $this->resumen = $this->obtenerResumenGeneral();
        $this->resumenNiveles = $this->obtenerResumenPorNivel();
        $this->periodosProximos = $this->obtenerPeriodosProximos();
        $this->alumnosDocumentosPendientes = auth()->user()?->is_admin
            ? $this->obtenerAlumnosConDocumentosPendientes()
            : [];
        $this->gruposSinHorario = $this->obtenerGruposSinHorario();
        $this->alertas = $this->obtenerAlertas();
        $this->graficaAlumnosNivel = $this->obtenerDatosGraficaAlumnosNivel();
    }

    private function tablaExiste(string $tabla): bool
    {
        return Schema::hasTable($tabla);
    }

    private function columnaExiste(string $tabla, string $columna): bool
    {
        return $this->tablaExiste($tabla) && Schema::hasColumn($tabla, $columna);
    }

    private function aplicarFiltroNivel(Builder $query, string $tabla): Builder
    {
        if ($this->nivel_id && $this->columnaExiste($tabla, 'nivel_id')) {
            $query->where($tabla . '.nivel_id', $this->nivel_id);
        }

        return $query;
    }

    private function aplicarCicloActual(Builder $query, string $tabla): Builder
    {
        if (!$this->columnaExiste($tabla, 'ciclo_escolar_id') || !$this->tablaExiste('ciclo_escolares')) {
            return $query;
        }

        $cicloActualId = DB::table('ciclo_escolares')
            ->where('es_actual', 1)
            ->value('id')
            ?? DB::table('ciclo_escolares')->orderByDesc('inicio_anio')->value('id');

        if ($cicloActualId) {
            $query->where($tabla . '.ciclo_escolar_id', $cicloActualId);
        }

        return $query;
    }

    private function aplicarActivo(Builder $query, string $tabla): Builder
    {
        if ($this->columnaExiste($tabla, 'activo')) {
            $query->where($tabla . '.activo', 1);
        } elseif ($this->columnaExiste($tabla, 'status')) {
            $query->whereIn($tabla . '.status', [1, '1', true, 'true', 'activo', 'Activo', 'ACTIVO']);
        }

        if ($this->columnaExiste($tabla, 'estatus')) {
            $query->whereRaw('LOWER(' . $tabla . '.estatus) = ?', ['activo']);
        }

        if ($this->columnaExiste($tabla, 'deleted_at')) {
            $query->whereNull($tabla . '.deleted_at');
        }

        return $query;
    }

    private function contarTabla(string $tabla, bool $filtrarNivel = true, bool $filtrarActivo = false): int
    {
        if (!$this->tablaExiste($tabla)) {
            return 0;
        }

        $query = DB::table($tabla);

        if ($filtrarNivel) {
            $this->aplicarFiltroNivel($query, $tabla);
        }

        if ($filtrarActivo) {
            $this->aplicarActivo($query, $tabla);
        }

        return $query->count();
    }

    private function contarPorNivel(string $tabla, int $nivelId, bool $filtrarActivo = false): int
    {
        if (!$this->columnaExiste($tabla, 'nivel_id')) {
            return 0;
        }

        $query = DB::table($tabla)->where($tabla . '.nivel_id', $nivelId);

        if ($filtrarActivo) {
            $this->aplicarActivo($query, $tabla);
        }

        return $query->count();
    }

    private function obtenerNiveles(): array
    {
        if (!$this->tablaExiste('niveles')) {
            return [];
        }

        return DB::table('niveles')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->get()
            ->map(fn($nivel) => [
                'id' => $nivel->id,
                'nombre' => $nivel->nombre,
            ])
            ->toArray();
    }

    private function obtenerResumenGeneral(): array
    {
        $alumnos = $this->contarTabla('inscripciones', true, true);
        $materias = $this->contarMateriasAsignadas();
        $calificaciones = $this->contarTabla('calificaciones', true);

        $totalEsperadoCalificaciones = $alumnos * max($materias, 1);

        $avanceCalificaciones = $totalEsperadoCalificaciones > 0
            ? floor(($calificaciones / $totalEsperadoCalificaciones) * 100)
            : 0;

        return [
            'alumnos' => $alumnos,
            'docentes' => $this->contarDocentesActivos(),
            'grupos' => $this->contarTabla('grupos'),
            'materias' => $materias,
            'horarios' => $this->contarTabla('horarios'),
            'periodos' => $this->contarTabla('periodos'),
            'calificaciones' => $calificaciones,
            'avance_calificaciones' => min($avanceCalificaciones, 100),
        ];
    }

    private function obtenerResumenPorNivel(): array
    {
        if (!$this->tablaExiste('niveles')) {
            return [];
        }

        $niveles = DB::table('niveles')->select('id', 'nombre')->orderBy('id');

        if ($this->nivel_id) {
            $niveles->where('id', $this->nivel_id);
        }

        return $niveles->get()
            ->map(function ($nivel) {
                $grupos = $this->obtenerDistribucionGrupos((int) $nivel->id, (string) $nivel->nombre);
                $hombres = collect($grupos)->sum('hombres');
                $mujeres = collect($grupos)->sum('mujeres');
                $total = $hombres + $mujeres;

                return [
                    'id' => $nivel->id,
                    'nombre' => $nivel->nombre,
                    'alumnos' => $total,
                    'hombres' => $hombres,
                    'mujeres' => $mujeres,
                    'porcentaje_hombres' => $total > 0 ? round(($hombres / $total) * 100) : 0,
                    'porcentaje_mujeres' => $total > 0 ? round(($mujeres / $total) * 100) : 0,
                    'grupos' => count($grupos),
                    'grupos_con_alumnos' => collect($grupos)->where('total', '>', 0)->count(),
                    'detalle_grupos' => $grupos,
                    'materias' => $this->contarMateriasAsignadasPorNivel((int) $nivel->id),
                    'horarios' => $this->contarPorNivel('horarios', $nivel->id),
                    'periodos' => $this->contarPorNivel('periodos', $nivel->id),
                ];
            })
            ->toArray();
    }

    private function obtenerDistribucionGrupos(int $nivelId, string $nombreNivel): array
    {
        if (!$this->tablaExiste('grupos')) {
            return [];
        }

        $query = DB::table('grupos')
            ->leftJoin('grados', 'grados.id', '=', 'grupos.grado_id')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->leftJoin('generaciones', 'generaciones.id', '=', 'grupos.generacion_id')
            ->leftJoin('semestres', 'semestres.id', '=', 'grupos.semestre_id')
            ->where('grupos.nivel_id', $nivelId)
            ->select([
                'grupos.id',
                'grados.nombre as grado',
                'grados.orden as grado_orden',
                'asignacion_grupos.nombre as grupo',
                'generaciones.anio_ingreso',
                'generaciones.anio_egreso',
                'semestres.numero as semestre_numero',
                'semestres.orden_global as semestre_orden',
            ])
            ->orderByRaw('COALESCE(semestres.orden_global, grados.orden, 999)')
            ->orderBy('asignacion_grupos.nombre')
            ->get();

        if ($query->isEmpty()) {
            return [];
        }

        $conteos = DB::table('inscripciones')
            ->where('nivel_id', $nivelId)
            ->whereIn('grupo_id', $query->pluck('id'));

        $this->aplicarActivo($conteos, 'inscripciones');

        $conteos = $conteos
            ->selectRaw("grupo_id, SUM(CASE WHEN genero = 'H' THEN 1 ELSE 0 END) as hombres, SUM(CASE WHEN genero = 'M' THEN 1 ELSE 0 END) as mujeres, COUNT(*) as total")
            ->groupBy('grupo_id')
            ->get()
            ->keyBy('grupo_id');

        $esBachillerato = str_contains(mb_strtolower($nombreNivel), 'bachiller');

        return $query->map(function ($grupo) use ($conteos, $esBachillerato) {
            $conteo = $conteos->get($grupo->id);
            $hombres = (int) ($conteo->hombres ?? 0);
            $mujeres = (int) ($conteo->mujeres ?? 0);
            $total = (int) ($conteo->total ?? 0);
            $generacion = $grupo->anio_ingreso && $grupo->anio_egreso
                ? $grupo->anio_ingreso . '-' . $grupo->anio_egreso
                : null;

            if ($esBachillerato && $grupo->semestre_orden) {
                $nombre = $grupo->semestre_orden . '.º semestre';
            } elseif ($esBachillerato && $grupo->semestre_numero) {
                $nombre = $grupo->semestre_numero . '.º semestre';
            } else {
                $nombre = $grupo->grado ?: 'Sin grado';
            }

            if ($grupo->grupo) {
                $nombre .= ' · Grupo ' . $grupo->grupo;
            }

            return [
                'id' => $grupo->id,
                'nombre' => $nombre,
                'generacion' => $generacion,
                'hombres' => $hombres,
                'mujeres' => $mujeres,
                'total' => $total,
                'porcentaje_hombres' => $total > 0 ? round(($hombres / $total) * 100) : 0,
                'porcentaje_mujeres' => $total > 0 ? round(($mujeres / $total) * 100) : 0,
            ];
        })->values()->toArray();
    }

    private function contarDocentesActivos(): int
    {
        if (!$this->tablaExiste('personas')) {
            return 0;
        }

        if ($this->tablaExiste('persona_role') && $this->tablaExiste('role_personas')) {
            $query = DB::table('personas')
                ->join('persona_role', 'persona_role.persona_id', '=', 'personas.id')
                ->join('role_personas', 'role_personas.id', '=', 'persona_role.role_persona_id')
                ->where(function ($q) {
                    $q->where('role_personas.slug', 'like', '%docente%')
                        ->orWhere('role_personas.slug', 'like', '%profesor%')
                        ->orWhere('role_personas.nombre', 'like', '%Docente%')
                        ->orWhere('role_personas.nombre', 'like', '%Profesor%');
                });

            $this->aplicarActivo($query, 'personas');

            return $query->distinct('personas.id')->count('personas.id');
        }

        $query = DB::table('personas');
        $this->aplicarActivo($query, 'personas');

        return $query->count();
    }

    private function contarMateriasAsignadas(): int
    {
        if (!$this->tablaExiste('asignacion_materias')) {
            return 0;
        }

        $query = DB::table('asignacion_materias');
        $this->aplicarCicloActual($query, 'asignacion_materias');

        if (Schema::hasColumn('asignacion_materias', 'estado')) {
            $query->where('asignacion_materias.estado', '!=', 'archivada');
        }

        if ($this->nivel_id && $this->tablaExiste('grupos') && $this->columnaExiste('grupos', 'nivel_id')) {
            $query->join('grupos', 'grupos.id', '=', 'asignacion_materias.grupo_id')
                ->where('grupos.nivel_id', $this->nivel_id);
        }

        return $query->count();
    }

    private function contarMateriasAsignadasPorNivel(int $nivelId): int
    {
        if (!$this->tablaExiste('asignacion_materias') || !$this->tablaExiste('grupos')) {
            return 0;
        }

        $query = DB::table('asignacion_materias')
            ->join('grupos', 'grupos.id', '=', 'asignacion_materias.grupo_id')
            ->where('grupos.nivel_id', $nivelId);

        $this->aplicarCicloActual($query, 'asignacion_materias');

        if (Schema::hasColumn('asignacion_materias', 'estado')) {
            $query->where('asignacion_materias.estado', '!=', 'archivada');
        }

        return $query->count();
    }

    private function obtenerAlertas(): array
    {
        $gruposSinHorario = count($this->gruposSinHorario ?: $this->obtenerGruposSinHorario());
        $materiasSinDocente = $this->contarMateriasSinDocente();
        $periodosPorCerrar = count($this->periodosProximos ?: $this->obtenerPeriodosProximos());
        $alumnosSinGrupo = $this->contarAlumnosSinGrupo();
        $documentosPendientes = count($this->alumnosDocumentosPendientes ?: $this->obtenerAlumnosConDocumentosPendientes());

        return [
            [
                'titulo' => 'Documentos pendientes',
                'descripcion' => 'Alumnos con archivos o documentos faltantes.',
                'cantidad' => $documentosPendientes,
                'color' => 'from-amber-500 to-orange-500',
            ],
            [
                'titulo' => 'Grupos sin horario',
                'descripcion' => 'Grupos que todavía no tienen horario asignado.',
                'cantidad' => $gruposSinHorario,
                'color' => 'from-rose-500 to-red-500',
            ],
            [
                'titulo' => 'Materias sin docente',
                'descripcion' => 'Materias registradas sin profesor asignado.',
                'cantidad' => $materiasSinDocente,
                'color' => 'from-sky-500 to-blue-600',
            ],
            [
                'titulo' => 'Periodos por cerrar',
                'descripcion' => 'Periodos próximos a finalizar.',
                'cantidad' => $periodosPorCerrar,
                'color' => 'from-violet-500 to-purple-600',
            ],
            [
                'titulo' => 'Alumnos sin grupo',
                'descripcion' => 'Inscripciones que aún no tienen grupo asignado.',
                'cantidad' => $alumnosSinGrupo,
                'color' => 'from-emerald-500 to-teal-600',
            ],
        ];
    }

    private function contarMateriasSinDocente(): int
    {
        if (!$this->columnaExiste('asignacion_materias', 'profesor_id')) {
            return 0;
        }

        $query = DB::table('asignacion_materias')
            ->whereNull('asignacion_materias.profesor_id');

        $this->aplicarCicloActual($query, 'asignacion_materias');

        if (Schema::hasColumn('asignacion_materias', 'estado')) {
            $query->where('asignacion_materias.estado', '!=', 'archivada');
        }

        if ($this->nivel_id && $this->tablaExiste('grupos')) {
            $query->join('grupos', 'grupos.id', '=', 'asignacion_materias.grupo_id')
                ->where('grupos.nivel_id', $this->nivel_id);
        }

        return $query->count();
    }

    private function contarAlumnosSinGrupo(): int
    {
        if (!$this->columnaExiste('inscripciones', 'grupo_id')) {
            return 0;
        }

        $query = DB::table('inscripciones')
            ->where(function ($q) {
                $q->whereNull('inscripciones.grupo_id')
                    ->orWhere('inscripciones.grupo_id', 0);
            });

        $this->aplicarFiltroNivel($query, 'inscripciones');
        $this->aplicarActivo($query, 'inscripciones');

        return $query->count();
    }

    private function obtenerPeriodosProximos(): array
    {
        if (!$this->tablaExiste('periodos') || !$this->columnaExiste('periodos', 'fecha_fin')) {
            return [];
        }

        $query = DB::table('periodos')
            ->leftJoin('niveles', 'niveles.id', '=', 'periodos.nivel_id')
            ->leftJoin('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
            ->select(
                'periodos.id',
                'periodos.fecha_inicio',
                'periodos.fecha_fin',
                'niveles.nombre as nivel',
                'periodos_basica.periodo as periodo_basica',
                'parciales.parcial as parcial_bachillerato'
            )
            ->whereNotNull('periodos.fecha_fin')
            ->whereDate('periodos.fecha_fin', '>=', now()->toDateString())
            ->whereDate('periodos.fecha_fin', '<=', now()->addDays(30)->toDateString())
            ->orderBy('periodos.fecha_fin')
            ->limit(5);

        if ($this->nivel_id && $this->columnaExiste('periodos', 'nivel_id')) {
            $query->where('periodos.nivel_id', $this->nivel_id);
        }

        return $query->get()->map(fn($periodo) => [
            'id' => $periodo->id,
            'nivel' => $periodo->nivel ?? 'Sin nivel',
            'periodo' => $periodo->periodo_basica ?? $periodo->parcial_bachillerato ?? 'Sin periodo',
            'fecha_inicio' => $periodo->fecha_inicio,
            'fecha_fin' => $periodo->fecha_fin,
        ])->toArray();
    }

    private function obtenerAlumnosConDocumentosPendientes(): array
    {
        if (!auth()->user()?->is_admin) {
            return [];
        }

        if (
            !$this->tablaExiste('inscripciones') ||
            !$this->tablaExiste('tipos_documentos') ||
            !$this->tablaExiste('documentos_alumnos')
        ) {
            return [];
        }

        $servicio = app(ExpedienteDigitalService::class);

        $query = Inscripcion::query()
            ->with([
                'nivel:id,nombre,slug,color',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
                'documentos.nivel:id,nombre,slug,color',
            ])
            ->where('activo', true)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');

        if ($this->nivel_id) {
            $query->where('nivel_id', $this->nivel_id);
        }

        return $query->get()
            ->map(function (Inscripcion $alumno) use ($servicio) {
                $resumen = $servicio->resumen($alumno);

                if ($resumen['completo']) {
                    return null;
                }

                return [
                    'id' => $alumno->id,
                    'nombre' => trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno),
                    'nivel' => $alumno->nivel?->nombre ?? 'Sin nivel',
                    'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo',
                    'pendientes' => $resumen['pendientes'],
                    'completados' => $resumen['completados'],
                    'total' => $resumen['total'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function obtenerGruposSinHorario(): array
    {
        if (!$this->tablaExiste('grupos')) {
            return [];
        }

        $query = DB::table('grupos')
            ->leftJoin('niveles', 'niveles.id', '=', 'grupos.nivel_id')
            ->leftJoin('grados', 'grados.id', '=', 'grupos.grado_id')
            ->leftJoin('generaciones', 'generaciones.id', '=', 'grupos.generacion_id')
            ->leftJoin('semestres', 'semestres.id', '=', 'grupos.semestre_id')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select(
                'grupos.id',
                'niveles.nombre as nivel',
                'grados.nombre as grado',
                'generaciones.anio_ingreso as generacion_inicio',
                'generaciones.anio_egreso as generacion_fin',
                'semestres.numero as semestre',
                'asignacion_grupos.nombre as grupo'
            );

        if ($this->tablaExiste('horarios') && $this->columnaExiste('horarios', 'grupo_id')) {
            $query->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('horarios')
                    ->whereColumn('horarios.grupo_id', 'grupos.id');
            });
        }

        if ($this->nivel_id && $this->columnaExiste('grupos', 'nivel_id')) {
            $query->where('grupos.nivel_id', $this->nivel_id);
        }

        return $query
            ->orderBy('niveles.id')
            ->orderBy('grados.id')
            ->orderBy('asignacion_grupos.nombre')
            ->limit(5)
            ->get()
            ->map(function ($grupo) {
                $detalle = collect([
                    $grupo->grado,
                    $grupo->grupo,
                    $grupo->semestre ? 'Semestre ' . $grupo->semestre : null,
                    $grupo->generacion_inicio . ' - ' . $grupo->generacion_fin,
                ])->filter()->implode(' - ');

                return [
                    'id' => $grupo->id,
                    'grupo' => $detalle ?: 'Sin grupo',
                    'nivel' => $grupo->nivel ?? 'Sin nivel',
                ];
            })
            ->toArray();
    }

    private function obtenerDatosGraficaAlumnosNivel(): array
    {
        if (!$this->tablaExiste('niveles')) {
            return [
                'labels' => [],
                'series' => [],
            ];
        }

        $niveles = DB::table('niveles')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->get();

        $labels = [];
        $series = [];

        foreach ($niveles as $nivel) {
            if ($this->nivel_id && (int) $this->nivel_id !== (int) $nivel->id) {
                continue;
            }

            $labels[] = $nivel->nombre;
            $series[] = $this->contarPorNivel('inscripciones', (int) $nivel->id, true);
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    public function rutaSegura(string $nombreRuta, string $respaldo = '#'): string
    {
        return Route::has($nombreRuta) ? route($nombreRuta) : $respaldo;
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
