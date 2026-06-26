<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Escuela;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DistribucionEscolarService
{
    public const CATEGORIAS = [
        'activo' => 'Activos',
        'inactivo' => 'Inactivos',
        'baja' => 'Bajas',
        'traslado' => 'Traslados',
        'suspendido' => 'Suspendidos',
        'egresado' => 'Egresados',
    ];

    /**
     * Construye los bloques de distribución por ciclo escolar.
     */
    public function bloques(Nivel $nivel, array $filtros = []): Collection
    {
        $nivel->loadMissing(['director', 'supervisor']);
        $centroTrabajo = $this->datosCentroTrabajo($nivel);

        $trayectorias = $this->trayectoriasCierrePorAlumnoYCiclo($nivel, $filtros);
        $estadosActuales = $this->estadosActuales($trayectorias->pluck('inscripcion_id'));

        $filtradas = $trayectorias
            ->filter(fn (TrayectoriaAcademica $trayectoria) => $this->cumpleFiltroEstado($trayectoria, $estadosActuales, $filtros))
            ->values();

        $director = $this->nombreDirector($nivel);
        $maestros = collect();

        return $filtradas
            ->groupBy('ciclo_escolar_id')
            ->map(function (Collection $registros, int|string $cicloEscolarId) use ($nivel, $director, $centroTrabajo, &$maestros) {
                /** @var TrayectoriaAcademica|null $primera */
                $primera = $registros->first();
                $ciclo = $primera?->cicloEscolar;

                $filas = $registros
                    ->groupBy(function (TrayectoriaAcademica $trayectoria) {
                        return implode('|', [
                            $trayectoria->grado_id ?: 0,
                            $trayectoria->grupo_id ?: 0,
                            $trayectoria->generacion_id ?: 0,
                            $trayectoria->semestre_id ?: 0,
                        ]);
                    })
                    ->map(function (Collection $grupoRegistros) use ($nivel, $director, $centroTrabajo, &$maestros) {
                        /** @var TrayectoriaAcademica $base */
                        $base = $grupoRegistros->first();
                        $claveMaestro = $nivel->id . '|' . ($base->grado_id ?: 0) . '|' . ($base->grupo_id ?: 0);

                        if (!$maestros->has($claveMaestro)) {
                            $maestros->put(
                                $claveMaestro,
                                $this->mostrarMaestro($nivel)
                                    ? $this->nombreMaestro($nivel->id, $base->grado_id, $base->grupo_id)
                                    : '—'
                            );
                        }

                        $conteos = collect(array_keys(self::CATEGORIAS))
                            ->mapWithKeys(fn (string $categoria) => [
                                $categoria => $grupoRegistros
                                    ->filter(fn (TrayectoriaAcademica $t) => $this->categoria($t) === $categoria)
                                    ->unique('inscripcion_id')
                                    ->count(),
                            ]);

                        $alumnosUnicos = $grupoRegistros->unique('inscripcion_id')->values();

                        return [
                            'ciclo_escolar_id' => (int) $base->ciclo_escolar_id,
                            'grado_id' => $base->grado_id ? (int) $base->grado_id : null,
                            'grupo_id' => $base->grupo_id ? (int) $base->grupo_id : null,
                            'generacion_id' => $base->generacion_id ? (int) $base->generacion_id : null,
                            'semestre_id' => $base->semestre_id ? (int) $base->semestre_id : null,
                            'regional' => $centroTrabajo['regional'],
                            'zona' => $centroTrabajo['zona'],
                            'cct' => $centroTrabajo['cct'],
                            'nombre_ct' => $centroTrabajo['nombre_ct'],
                            'nivel' => mb_strtoupper($nivel->nombre ?? 'SIN NIVEL'),
                            'turno' => 'Matutino',
                            'grado' => $base->grado?->nombre ?: '—',
                            'grado_orden' => (int) ($base->grado?->orden ?? 999),
                            'grupo' => $base->grupo?->asignacionGrupo?->nombre ?: '—',
                            'generacion' => $base->generacion
                                ? $base->generacion->anio_ingreso . '-' . $base->generacion->anio_egreso
                                : '—',
                            'generacion_ingreso' => (int) ($base->generacion?->anio_ingreso ?? 0),
                            'semestre' => $base->semestre?->numero
                                ? $base->semestre->numero . '°'
                                : '—',
                            'hombres' => $alumnosUnicos->filter(fn (TrayectoriaAcademica $t) => $t->inscripcion?->genero === 'H')->count(),
                            'mujeres' => $alumnosUnicos->filter(fn (TrayectoriaAcademica $t) => $t->inscripcion?->genero === 'M')->count(),
                            'total_historico' => $alumnosUnicos->count(),
                            'activos' => (int) $conteos->get('activo', 0),
                            'inactivos' => (int) $conteos->get('inactivo', 0),
                            'bajas' => (int) $conteos->get('baja', 0),
                            'traslados' => (int) $conteos->get('traslado', 0),
                            'suspendidos' => (int) $conteos->get('suspendido', 0),
                            'egresados' => (int) $conteos->get('egresado', 0),
                            'maestro' => $maestros->get($claveMaestro, '—'),
                            'director' => $director,
                        ];
                    })
                    ->sortBy([
                        ['grado_orden', 'asc'],
                        ['semestre_id', 'asc'],
                        ['grupo', 'asc'],
                        ['generacion_ingreso', 'asc'],
                    ])
                    ->values();

                return [
                    'ciclo_escolar_id' => (int) $cicloEscolarId,
                    'ciclo' => $ciclo?->nombre ?: 'Sin ciclo',
                    'inicio_anio' => (int) ($ciclo?->inicio_anio ?? 0),
                    'fin_anio' => (int) ($ciclo?->fin_anio ?? 0),
                    'es_actual' => (bool) ($ciclo?->es_actual ?? false),
                    'filas' => $filas,
                    'totales' => $this->totalesFilas($filas),
                ];
            })
            ->sortBy('inicio_anio')
            ->values();
    }

    /**
     * Obtiene el listado nominal de una fila de distribución.
     */
    public function detalleFila(Nivel $nivel, array $contexto, array $filtros = []): Collection
    {
        $filtrosBase = array_merge($filtros, [
            'ciclo_escolar_id' => $contexto['ciclo_escolar_id'] ?? null,
            'grado_id' => $contexto['grado_id'] ?? null,
            'grupo_id' => $contexto['grupo_id'] ?? null,
            'generacion_id' => $contexto['generacion_id'] ?? null,
            'semestre_id' => $contexto['semestre_id'] ?? null,
        ]);

        $trayectorias = $this->trayectoriasCierrePorAlumnoYCiclo($nivel, $filtrosBase);
        $estadosActuales = $this->estadosActuales($trayectorias->pluck('inscripcion_id'));
        $busqueda = Str::of((string) ($filtros['buscar'] ?? ''))->squish()->lower()->value();
        $estadoDetalle = (string) ($filtros['estado_detalle'] ?? 'todos');

        return $trayectorias
            ->filter(function (TrayectoriaAcademica $trayectoria) use ($estadosActuales, $busqueda, $estadoDetalle, $filtros) {
                $inscripcion = $trayectoria->inscripcion;

                if (!$inscripcion || !$this->cumpleFiltroEstado($trayectoria, $estadosActuales, $filtros)) {
                    return false;
                }

                if ($busqueda !== '') {
                    $texto = Str::of(implode(' ', [
                        $inscripcion->matricula,
                        $inscripcion->curp,
                        $inscripcion->apellido_paterno,
                        $inscripcion->apellido_materno,
                        $inscripcion->nombre,
                    ]))->squish()->lower()->value();

                    if (!str_contains($texto, $busqueda)) {
                        return false;
                    }
                }

                if ($estadoDetalle !== 'todos' && $this->categoria($trayectoria) !== $estadoDetalle) {
                    return false;
                }

                return true;
            })
            ->map(function (TrayectoriaAcademica $trayectoria) use ($estadosActuales) {
                $actual = $estadosActuales->get($trayectoria->inscripcion_id);
                $inscripcion = $trayectoria->inscripcion;

                return [
                    'inscripcion_id' => (int) $trayectoria->inscripcion_id,
                    'trayectoria_id' => (int) $trayectoria->id,
                    'matricula' => $inscripcion?->matricula ?: '—',
                    'curp' => $inscripcion?->curp ?: '—',
                    'nombre' => trim(implode(' ', array_filter([
                        $inscripcion?->apellido_paterno,
                        $inscripcion?->apellido_materno,
                        $inscripcion?->nombre,
                    ]))) ?: 'Alumno sin nombre',
                    'genero' => $inscripcion?->genero ?: '—',
                    'grado' => $trayectoria->grado?->nombre ?: '—',
                    'grupo' => $trayectoria->grupo?->asignacionGrupo?->nombre ?: '—',
                    'generacion' => $trayectoria->generacion
                        ? $trayectoria->generacion->anio_ingreso . '-' . $trayectoria->generacion->anio_egreso
                        : '—',
                    'semestre' => $trayectoria->semestre?->numero
                        ? $trayectoria->semestre->numero . '°'
                        : '—',
                    'estado_historico' => $this->etiquetaEstatus($trayectoria),
                    'categoria_historica' => $this->categoria($trayectoria),
                    'estado_actual' => $actual ? $this->etiquetaEstatus($actual) : 'Sin estado actual',
                    'categoria_actual' => $actual ? $this->categoria($actual) : 'inactivo',
                    'fecha_alta' => optional($trayectoria->fecha_inicio ?: $trayectoria->fecha_inscripcion)->format('d/m/Y') ?: '—',
                    'fecha_baja' => optional($trayectoria->fecha_baja ?: $trayectoria->fecha_fin)->format('d/m/Y') ?: '—',
                    'motivo' => $trayectoria->motivo_baja ?: '—',
                    'observaciones' => $trayectoria->observaciones_baja ?: '—',
                    'reconstruido' => (bool) $trayectoria->datos_reconstruidos,
                    'ya_no_esta' => !$actual || $this->categoria($actual) !== 'activo',
                ];
            })
            ->sortBy([
                ['nombre', 'asc'],
                ['matricula', 'asc'],
            ])
            ->values();
    }

    /**
     * Línea de tiempo del alumno dentro del nivel seleccionado.
     */
    public function trayectoriaAlumno(Nivel $nivel, int $inscripcionId): array
    {
        $trayectorias = TrayectoriaAcademica::query()
            ->with($this->relaciones())
            ->where('nivel_id', $nivel->id)
            ->where('inscripcion_id', $inscripcionId)
            ->orderBy('ciclo_escolar_id')
            ->orderBy('ciclo_id')
            ->orderBy('numero_estancia')
            ->orderBy('id')
            ->get();

        $cierres = $trayectorias
            ->groupBy('ciclo_escolar_id')
            ->map(fn (Collection $grupo) => $this->seleccionarCierre($grupo))
            ->filter()
            ->sortBy(fn (TrayectoriaAcademica $t) => (int) ($t->cicloEscolar?->inicio_anio ?? 0))
            ->values()
            ->map(fn (TrayectoriaAcademica $trayectoria) => [
                'id' => (int) $trayectoria->id,
                'ciclo' => $trayectoria->cicloEscolar?->nombre ?: 'Sin ciclo',
                'corte' => $trayectoria->ciclo?->ciclo ?: 'Sin corte',
                'nivel' => $trayectoria->nivel?->nombre ?: '—',
                'grado' => $trayectoria->grado?->nombre ?: '—',
                'grupo' => $trayectoria->grupo?->asignacionGrupo?->nombre ?: '—',
                'generacion' => $trayectoria->generacion
                    ? $trayectoria->generacion->anio_ingreso . '-' . $trayectoria->generacion->anio_egreso
                    : '—',
                'semestre' => $trayectoria->semestre?->numero
                    ? $trayectoria->semestre->numero . '°'
                    : '—',
                'estado' => $this->etiquetaEstatus($trayectoria),
                'categoria' => $this->categoria($trayectoria),
                'fecha_inicio' => optional($trayectoria->fecha_inicio ?: $trayectoria->fecha_inscripcion)->format('d/m/Y') ?: '—',
                'fecha_fin' => optional($trayectoria->fecha_fin ?: $trayectoria->fecha_baja)->format('d/m/Y') ?: '—',
                'motivo' => $trayectoria->motivo_baja ?: '—',
                'observaciones' => $trayectoria->observaciones_baja ?: '—',
                'reconstruido' => (bool) $trayectoria->datos_reconstruidos,
            ]);

        $movimientos = MovimientoAlumno::query()
            ->with(['usuario:id,name', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->where('inscripcion_id', $inscripcionId)
            ->where(function (Builder $query) use ($nivel) {
                $query->whereHas('trayectoriaAcademica', fn (Builder $t) => $t->where('nivel_id', $nivel->id))
                    ->orWhereHas('trayectoriaOrigen', fn (Builder $t) => $t->where('nivel_id', $nivel->id));
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MovimientoAlumno $movimiento) => [
                'id' => (int) $movimiento->id,
                'tipo' => $this->etiquetaMovimiento($movimiento->tipo),
                'fecha' => optional($movimiento->fecha)->format('d/m/Y') ?: '—',
                'ciclo' => $movimiento->cicloEscolar?->nombre ?: '—',
                'motivo' => $movimiento->motivo ?: '—',
                'observaciones' => $movimiento->observaciones ?: '—',
                'usuario' => $movimiento->usuario?->name ?: 'Sistema',
            ]);

        $alumno = $trayectorias->first()?->inscripcion;

        return [
            'alumno' => [
                'id' => (int) $inscripcionId,
                'matricula' => $alumno?->matricula ?: '—',
                'curp' => $alumno?->curp ?: '—',
                'nombre' => trim(implode(' ', array_filter([
                    $alumno?->apellido_paterno,
                    $alumno?->apellido_materno,
                    $alumno?->nombre,
                ]))) ?: 'Alumno sin nombre',
            ],
            'trayectorias' => $cierres,
            'movimientos' => $movimientos,
        ];
    }

    /**
     * Listado nominal completo para exportaciones.
     */
    public function listadoCompleto(Nivel $nivel, array $filtros = []): Collection
    {
        $trayectorias = $this->trayectoriasCierrePorAlumnoYCiclo($nivel, $filtros);
        $estadosActuales = $this->estadosActuales($trayectorias->pluck('inscripcion_id'));

        return $trayectorias
            ->filter(fn (TrayectoriaAcademica $trayectoria) => $this->cumpleFiltroEstado($trayectoria, $estadosActuales, $filtros))
            ->map(function (TrayectoriaAcademica $trayectoria) use ($estadosActuales) {
                $actual = $estadosActuales->get($trayectoria->inscripcion_id);
                $inscripcion = $trayectoria->inscripcion;

                return [
                    'ciclo' => $trayectoria->cicloEscolar?->nombre ?: '—',
                    'matricula' => $inscripcion?->matricula ?: '—',
                    'curp' => $inscripcion?->curp ?: '—',
                    'alumno' => trim(implode(' ', array_filter([
                        $inscripcion?->apellido_paterno,
                        $inscripcion?->apellido_materno,
                        $inscripcion?->nombre,
                    ]))) ?: 'Alumno sin nombre',
                    'genero' => $inscripcion?->genero === 'H' ? 'Hombre' : ($inscripcion?->genero === 'M' ? 'Mujer' : '—'),
                    'nivel' => $trayectoria->nivel?->nombre ?: '—',
                    'grado' => $trayectoria->grado?->nombre ?: '—',
                    'grupo' => $trayectoria->grupo?->asignacionGrupo?->nombre ?: '—',
                    'semestre' => $trayectoria->semestre?->numero ?: '—',
                    'generacion' => $trayectoria->generacion
                        ? $trayectoria->generacion->anio_ingreso . '-' . $trayectoria->generacion->anio_egreso
                        : '—',
                    'estado_historico' => $this->etiquetaEstatus($trayectoria),
                    'estado_actual' => $actual ? $this->etiquetaEstatus($actual) : 'Sin estado actual',
                    'categoria_historica' => $this->categoria($trayectoria),
                    'categoria_actual' => $actual ? $this->categoria($actual) : 'inactivo',
                    'fecha_alta' => optional($trayectoria->fecha_inicio ?: $trayectoria->fecha_inscripcion)->format('d/m/Y') ?: '—',
                    'fecha_baja' => optional($trayectoria->fecha_baja ?: $trayectoria->fecha_fin)->format('d/m/Y') ?: '—',
                    'motivo' => $trayectoria->motivo_baja ?: '—',
                    'observaciones' => $trayectoria->observaciones_baja ?: '—',
                    'reconstruido' => $trayectoria->datos_reconstruidos ? 'Sí' : 'No',
                ];
            })
            ->sortBy([
                ['ciclo', 'asc'],
                ['generacion', 'asc'],
                ['grado', 'asc'],
                ['grupo', 'asc'],
                ['alumno', 'asc'],
            ])
            ->values();
    }

    public function categorias(): array
    {
        return self::CATEGORIAS;
    }

    public function categoria(TrayectoriaAcademica $trayectoria): string
    {
        $estatus = (string) ($trayectoria->estatus ?: 'activo');

        if ($estatus === 'egresado') {
            return 'egresado';
        }

        if ($estatus === 'traslado') {
            return 'traslado';
        }

        if (in_array($estatus, ['baja_temporal', 'baja_definitiva'], true)) {
            return 'baja';
        }

        if ($estatus === 'suspendido') {
            return 'suspendido';
        }

        if (in_array($estatus, ['inactivo', 'archivado'], true)) {
            return 'inactivo';
        }

        if (!$trayectoria->activo) {
            return 'inactivo';
        }

        return 'activo';
    }

    public function etiquetaEstatus(TrayectoriaAcademica $trayectoria): string
    {
        return match ((string) $trayectoria->estatus) {
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'traslado' => 'Traslado',
            'reingreso' => 'Reingreso activo',
            'egresado' => 'Egresado',
            'no_promovido' => 'No promovido · activo',
            'promovido' => 'Promovido',
            'archivado' => 'Archivado',
            'inactivo' => 'Inactivo',
            'suspendido' => 'Suspendido',
            default => $trayectoria->activo ? 'Activo' : 'Inactivo',
        };
    }

    private function trayectoriasCierrePorAlumnoYCiclo(Nivel $nivel, array $filtros): Collection
    {
        $query = TrayectoriaAcademica::query()
            ->with($this->relaciones())
            ->where('nivel_id', $nivel->id)
            ->when(
                filled($filtros['ciclo_escolar_id'] ?? null),
                fn (Builder $q) => $q->where('ciclo_escolar_id', (int) $filtros['ciclo_escolar_id'])
            );

        return $query
            ->get()
            ->groupBy(fn (TrayectoriaAcademica $trayectoria) => $trayectoria->inscripcion_id . '|' . $trayectoria->ciclo_escolar_id)
            ->map(fn (Collection $grupo) => $this->seleccionarCierre($grupo))
            ->filter()
            ->filter(function (TrayectoriaAcademica $trayectoria) use ($filtros) {
                if (filled($filtros['generacion_id'] ?? null)
                    && (int) $trayectoria->generacion_id !== (int) $filtros['generacion_id']) {
                    return false;
                }

                if (filled($filtros['grado_id'] ?? null)
                    && (int) $trayectoria->grado_id !== (int) $filtros['grado_id']) {
                    return false;
                }

                if (filled($filtros['grupo_id'] ?? null)
                    && (int) $trayectoria->grupo_id !== (int) $filtros['grupo_id']) {
                    return false;
                }

                if (filled($filtros['semestre_id'] ?? null)
                    && (int) $trayectoria->semestre_id !== (int) $filtros['semestre_id']) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function seleccionarCierre(Collection $grupo): ?TrayectoriaAcademica
    {
        return $grupo
            ->sortByDesc(function (TrayectoriaAcademica $trayectoria) {
                return sprintf(
                    '%010d|%010d|%010d|%010d',
                    (int) $trayectoria->ciclo_id,
                    $trayectoria->vigente_en_corte ? 1 : 0,
                    (int) $trayectoria->numero_estancia,
                    (int) $trayectoria->id,
                );
            })
            ->first();
    }

    private function estadosActuales(Collection $inscripcionIds): Collection
    {
        $ids = $inscripcionIds->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $trayectorias = TrayectoriaAcademica::query()
            ->with($this->relaciones())
            ->whereIn('inscripcion_id', $ids)
            ->get();

        return $trayectorias
            ->groupBy('inscripcion_id')
            ->map(function (Collection $grupo) {
                $actual = $grupo
                    ->where('es_actual', true)
                    ->sortByDesc(fn (TrayectoriaAcademica $t) => sprintf('%010d|%010d|%010d', $t->ciclo_id, $t->numero_estancia, $t->id))
                    ->first();

                if ($actual) {
                    return $actual;
                }

                return $grupo
                    ->sortByDesc(function (TrayectoriaAcademica $t) {
                        return sprintf(
                            '%010d|%010d|%010d|%010d',
                            (int) ($t->cicloEscolar?->inicio_anio ?? 0),
                            (int) $t->ciclo_id,
                            (int) $t->numero_estancia,
                            (int) $t->id,
                        );
                    })
                    ->first();
            });
    }

    private function cumpleFiltroEstado(TrayectoriaAcademica $trayectoria, Collection $estadosActuales, array $filtros): bool
    {
        $estado = (string) ($filtros['estado'] ?? 'todos');
        $soloYaNoEstan = filter_var($filtros['solo_ya_no_estan'] ?? false, FILTER_VALIDATE_BOOL);
        $actual = $estadosActuales->get($trayectoria->inscripcion_id);

        if ($soloYaNoEstan && $actual && $this->categoria($actual) === 'activo') {
            return false;
        }

        if ($soloYaNoEstan && !$actual) {
            return true;
        }

        if ($estado === 'todos') {
            return true;
        }

        return $this->categoria($trayectoria) === $estado;
    }

    private function relaciones(): array
    {
        return [
            'inscripcion' => fn ($query) => $query->withTrashed()->select([
                'id',
                'matricula',
                'curp',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'genero',
                'activo',
                'fecha_baja',
                'motivo_baja',
                'observaciones_baja',
                'deleted_at',
            ]),
            'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
            'ciclo:id,ciclo',
            'nivel:id,nombre,slug,director_id',
            'grado:id,nivel_id,nombre,orden',
            'generacion:id,nivel_id,anio_ingreso,anio_egreso,status,deleted_at',
            'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
            'grupo.asignacionGrupo:id,nombre',
            'semestre:id,grado_id,numero',
        ];
    }

    private function totalesFilas(Collection $filas): array
    {
        return [
            'hombres' => (int) $filas->sum('hombres'),
            'mujeres' => (int) $filas->sum('mujeres'),
            'total_historico' => (int) $filas->sum('total_historico'),
            'activos' => (int) $filas->sum('activos'),
            'inactivos' => (int) $filas->sum('inactivos'),
            'bajas' => (int) $filas->sum('bajas'),
            'traslados' => (int) $filas->sum('traslados'),
            'suspendidos' => (int) $filas->sum('suspendidos'),
            'egresados' => (int) $filas->sum('egresados'),
        ];
    }

    private function datosCentroTrabajo(Nivel $nivel): array
    {
        $escuela = Escuela::query()->first(['nombre', 'regional']);
        $zona = $nivel->supervisor?->zona_escolar
            ?: $nivel->director?->zona_escolar
            ?: '—';

        return [
            'regional' => $escuela?->regional ?: '—',
            'zona' => $zona,
            'cct' => $nivel->cct ?: '—',
            'nombre_ct' => $escuela?->nombre ?: '—',
        ];
    }

    private function nombreDirector(Nivel $nivel): string
    {
        $director = $nivel->relationLoaded('director') ? $nivel->director : null;

        if (!$director && $nivel->director_id) {
            $director = DB::table('directores')->where('id', $nivel->director_id)->first();
        }

        if (!$director) {
            return '—';
        }

        return trim(collect([
            $director->titulo ?? null,
            $director->nombre ?? null,
            $director->apellido_paterno ?? null,
            $director->apellido_materno ?? null,
        ])->filter()->implode(' ')) ?: '—';
    }

    private function mostrarMaestro(Nivel $nivel): bool
    {
        $texto = Str::of(($nivel->slug ?? '') . ' ' . ($nivel->nombre ?? ''))->lower()->ascii()->value();

        return str_contains($texto, 'preescolar') || str_contains($texto, 'primaria');
    }

    private function nombreMaestro(int $nivelId, ?int $gradoId, ?int $grupoId): string
    {
        if (!$gradoId || !$grupoId) {
            return '—';
        }

        $maestros = DB::table('persona_nivel_detalles as pnd')
            ->join('persona_nivel as pn', 'pn.id', '=', 'pnd.persona_nivel_id')
            ->join('persona_role as pr', 'pr.id', '=', 'pnd.persona_role_id')
            ->join('role_personas as rp', 'rp.id', '=', 'pr.role_persona_id')
            ->join('personas as p', 'p.id', '=', 'pn.persona_id')
            ->where('pn.nivel_id', $nivelId)
            ->where('pnd.grado_id', $gradoId)
            ->where('pnd.grupo_id', $grupoId)
            ->where('p.status', true)
            ->whereIn('rp.slug', [
                'maestro_frente_a_grupo',
                'docente',
                'director_con_grupo',
            ])
            ->orderByRaw("CASE
                WHEN rp.slug = 'maestro_frente_a_grupo' THEN 1
                WHEN rp.slug = 'director_con_grupo' THEN 2
                WHEN rp.slug = 'docente' THEN 3
                ELSE 4 END")
            ->orderBy('pnd.orden')
            ->selectRaw("TRIM(CONCAT_WS(' ', p.titulo, p.nombre, p.apellido_paterno, p.apellido_materno)) as nombre_completo")
            ->pluck('nombre_completo')
            ->filter()
            ->unique()
            ->values();

        return $maestros->isNotEmpty() ? $maestros->implode(', ') : '—';
    }

    private function etiquetaMovimiento(string $tipo): string
    {
        return Str::of($tipo)
            ->replace('_', ' ')
            ->lower()
            ->ucfirst()
            ->value();
    }
}
