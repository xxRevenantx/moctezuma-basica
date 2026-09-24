<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\TallerSesion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HorarioCargaDocenteService
{
    /**
     * Construye la fuente única de los cuatro formatos del reporte de Secundaria.
     *
     * - Materias normales: cada fila de horario equivale a una sesión docente.
     * - Talleres conjuntos: las proyecciones por grupo se agrupan por
     *   taller_sesion_id, evitando multiplicar la carga del profesor.
     * - Horas reloj: se calculan con la duración real del bloque horario.
     */
    public function construir(
        Nivel $nivel,
        CicloEscolar $cicloEscolar,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $profesorId = null,
    ): array {
        $grupos = $this->obtenerGrupos($nivel, $cicloEscolar, $gradoId, $grupoId);
        $dias = $this->obtenerDias($nivel);
        $horas = $this->obtenerHoras($nivel);

        $receso = app(HorarioRecesoService::class)->oficialNivel(
            nivelId: (int) $nivel->id,
            cicloEscolarId: (int) $cicloEscolar->id,
            grupos: $grupos,
            horas: $horas,
        );

        $recesoHoraIds = collect($receso['hora_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $horarios = $this->obtenerHorarios($nivel, $cicloEscolar, $grupos);
        $actividadesTodas = $horarios
            ->map(fn (Horario $horario) => $this->convertirActividad($horario))
            ->filter()
            ->values();

        $sesionesTodas = $this->construirSesionesCanonicas($actividadesTodas);

        $actividades = $profesorId
            ? $actividadesTodas->where('profesor_id', $profesorId)->values()
            : $actividadesTodas;

        $sesiones = $profesorId
            ? $sesionesTodas->where('profesor_id', $profesorId)->values()
            : $sesionesTodas;

        $cargaDocente = $this->construirCargaDocente($sesiones);
        $complementarias = $this->construirComplementarias($sesiones);
        $alertas = $this->construirAlertas(
            grupos: $grupos,
            dias: $dias,
            horas: $horas,
            recesoHoraIds: $recesoHoraIds,
            actividades: $actividadesTodas,
            sesiones: $sesionesTodas,
            receso: $receso,
        );

        $profesores = $sesionesTodas
            ->filter(fn (array $sesion) => filled($sesion['profesor_id']))
            ->map(fn (array $sesion) => [
                'id' => (int) $sesion['profesor_id'],
                'nombre' => (string) $sesion['profesor'],
            ])
            ->unique('id')
            ->sortBy(fn (array $item) => Str::lower(Str::ascii($item['nombre'])))
            ->values();

        $tablaGeneral = $this->construirMatrizGeneral(
            dias: $dias,
            horas: $horas,
            recesoHoraIds: $recesoHoraIds,
            actividades: $actividades,
        );

        $formatosDocentes = $this->construirFormatosDocentes(
            sesiones: $sesiones,
            dias: $dias,
            horas: $horas,
            recesoHoraIds: $recesoHoraIds,
        );

        $formatosGrupos = $this->construirFormatosGrupos(
            grupos: $grupos,
            actividades: $actividades,
            dias: $dias,
            horas: $horas,
            recesoHoraIds: $recesoHoraIds,
        );

        $minutos = (int) $sesiones->sum('minutos');

        return [
            'nivel' => $nivel,
            'ciclo_escolar' => $cicloEscolar,
            'grupos' => $grupos,
            'dias' => $dias,
            'horas' => $horas,
            'profesores' => $profesores,
            'actividades' => $actividades,
            'sesiones' => $sesiones,
            'carga_docente' => $cargaDocente,
            'tabla_general' => $tablaGeneral,
            'formatos_docentes' => $formatosDocentes,
            'formatos_grupos' => $formatosGrupos,
            'complementarias' => $complementarias,
            'alertas' => $alertas,
            'receso' => [
                'hora_ids' => $recesoHoraIds->all(),
                'inconsistente' => (bool) ($receso['inconsistente'] ?? false),
                'variantes' => (int) ($receso['variantes'] ?? 0),
                'grupos_sin_configurar' => (int) ($receso['grupos_sin_receso'] ?? 0),
                'heredado' => (bool) ($receso['heredado'] ?? false),
                'fuente' => (string) ($receso['fuente'] ?? 'sin_configuracion'),
            ],
            'resumen' => [
                'grupos' => $grupos->count(),
                'docentes' => $sesiones->whereNotNull('profesor_id')->pluck('profesor_id')->unique()->count(),
                'sesiones' => $sesiones->count(),
                'minutos' => $minutos,
                'horas_reloj' => round($minutos / 60, 2),
                'materias' => $sesiones
                    ->map(fn (array $item) => $item['tipo'] . ':' . $item['actividad_id'])
                    ->unique()
                    ->count(),
                'alertas' => (int) ($alertas['total'] ?? 0),
            ],
            'filtros' => [
                'grado_id' => $gradoId,
                'grupo_id' => $grupoId,
                'profesor_id' => $profesorId,
            ],
        ];
    }

    private function obtenerGrupos(
        Nivel $nivel,
        CicloEscolar $cicloEscolar,
        ?int $gradoId,
        ?int $grupoId,
    ): Collection {
        return Grupo::query()
            ->with([
                'grado:id,nivel_id,nombre,orden',
                'asignacionGrupo:id,nombre',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('estado', 'activo')
            ->when($gradoId, fn ($query) => $query->where('grado_id', $gradoId))
            ->when($grupoId, fn ($query) => $query->whereKey($grupoId))
            ->get()
            ->sortBy(fn (Grupo $grupo) => sprintf(
                '%06d-%s-%06d',
                (int) ($grupo->grado?->orden ?? 999999),
                Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
                (int) $grupo->id,
            ))
            ->values();
    }

    private function obtenerDias(Nivel $nivel): Collection
    {
        return Dia::query()
            ->where('nivel_id', $nivel->id)
            ->get(['id', 'nivel_id', 'dia', 'orden'])
            ->sortBy(fn ($dia) => $this->ordenDia((string) $dia->dia, (int) ($dia->orden ?? 999999)))
            ->values();
    }

    private function obtenerHoras(Nivel $nivel): Collection
    {
        return Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get(['id', 'nivel_id', 'hora_inicio', 'hora_fin', 'orden']);
    }

    private function obtenerHorarios(Nivel $nivel, CicloEscolar $cicloEscolar, Collection $grupos): Collection
    {
        if ($grupos->isEmpty()) {
            return collect();
        }

        return Horario::query()
            ->with([
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.grado:id,nivel_id,nombre,orden',
                'grupo.asignacionGrupo:id,nombre',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,ciclo_escolar_id,estado,orden',
                'asignacionMateria.materia:id,nivel_id,grado_id,semestre_id,materia,clave,extra,receso,orden',
                'asignacionMateria.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'profesorAsignado:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion:id,taller_id,profesor_id,ciclo_escolar_id,estado,dia_id,hora_id,ubicacion',
                'tallerSesion.taller:id,nivel_id,nombre,clave,slug',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->whereIn('grupo_id', $grupos->pluck('id'))
            ->where(function ($query) use ($cicloEscolar): void {
                $query
                    ->where(function ($materiaQuery) use ($cicloEscolar): void {
                        $materiaQuery
                            ->whereNotNull('asignacion_materia_id')
                            ->whereHas('asignacionMateria', function ($asignacionQuery) use ($cicloEscolar): void {
                                if ($cicloEscolar->es_actual && blank($cicloEscolar->cerrado_at)) {
                                    $asignacionQuery->where('estado', AsignacionMateria::ESTADO_ACTIVA);
                                } else {
                                    $asignacionQuery->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);
                                }
                            });
                    })
                    ->orWhere(function ($tallerQuery): void {
                        $tallerQuery
                            ->whereNotNull('taller_sesion_id')
                            ->whereHas('tallerSesion', fn ($sesionQuery) => $sesionQuery
                                ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA));
                    });
            })
            ->orderBy('hora_id')
            ->orderBy('dia_id')
            ->orderBy('grupo_id')
            ->orderBy('id')
            ->get();
    }

    private function convertirActividad(Horario $horario): ?array
    {
        $grupo = $horario->grupo;
        $dia = $horario->dia;
        $hora = $horario->hora;

        if (!$grupo || !$dia || !$hora) {
            return null;
        }

        $profesor = $horario->profesorActividad();
        $minutos = $this->duracionMinutos($hora->hora_inicio, $hora->hora_fin);

        if ($horario->taller_sesion_id) {
            $sesion = $horario->tallerSesion;
            $taller = $sesion?->taller;

            if (!$sesion || !$taller) {
                return null;
            }

            return [
                'horario_id' => (int) $horario->id,
                'clave_sesion' => 'taller:' . (int) $horario->taller_sesion_id,
                'tipo' => 'taller',
                'actividad_id' => (int) $taller->id,
                'nombre' => trim((string) ($taller->nombre ?? 'Taller')) ?: 'Taller',
                'clave' => trim((string) ($taller->clave ?? '')),
                'complementaria' => true,
                'profesor_id' => $profesor?->id ? (int) $profesor->id : null,
                'profesor' => $this->nombrePersona($profesor) ?: 'Sin docente',
                'grupo_id' => (int) $grupo->id,
                'grado_id' => (int) $grupo->grado_id,
                'grupo' => $this->etiquetaGrupo($grupo),
                'dia_id' => (int) $dia->id,
                'dia' => (string) $dia->dia,
                'hora_id' => (int) $hora->id,
                'hora' => $this->textoHora($hora->hora_inicio, $hora->hora_fin),
                'hora_inicio' => (string) $hora->hora_inicio,
                'hora_fin' => (string) $hora->hora_fin,
                'minutos' => $minutos,
                'ubicacion' => trim((string) ($sesion->ubicacion ?? '')),
                'traslape_excepcional' => (bool) $horario->traslape_excepcional,
            ];
        }

        $asignacion = $horario->asignacionMateria;
        $materia = $asignacion?->materia;

        if (!$asignacion || !$materia || (bool) $materia->receso) {
            return null;
        }

        return [
            'horario_id' => (int) $horario->id,
            'clave_sesion' => 'horario:' . (int) $horario->id,
            'tipo' => 'materia',
            'actividad_id' => (int) $materia->id,
            'nombre' => trim((string) ($materia->materia ?? 'Materia')) ?: 'Materia',
            'clave' => trim((string) ($materia->clave ?? '')),
            'complementaria' => (bool) ($materia->extra ?? false),
            'profesor_id' => $profesor?->id ? (int) $profesor->id : null,
            'profesor' => $this->nombrePersona($profesor) ?: 'Sin docente',
            'grupo_id' => (int) $grupo->id,
            'grado_id' => (int) $grupo->grado_id,
            'grupo' => $this->etiquetaGrupo($grupo),
            'dia_id' => (int) $dia->id,
            'dia' => (string) $dia->dia,
            'hora_id' => (int) $hora->id,
            'hora' => $this->textoHora($hora->hora_inicio, $hora->hora_fin),
            'hora_inicio' => (string) $hora->hora_inicio,
            'hora_fin' => (string) $hora->hora_fin,
            'minutos' => $minutos,
            'ubicacion' => '',
            'traslape_excepcional' => (bool) $horario->traslape_excepcional,
        ];
    }

    private function construirSesionesCanonicas(Collection $actividades): Collection
    {
        return $actividades
            ->groupBy('clave_sesion')
            ->map(function (Collection $items): array {
                $primero = $items->first();

                return [
                    ...$primero,
                    'grupos' => $items
                        ->pluck('grupo')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'grupo_ids' => $items
                        ->pluck('grupo_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all(),
                    'proyecciones' => $items->count(),
                ];
            })
            ->sortBy(fn (array $item) => sprintf(
                '%02d-%s-%s-%06d',
                $this->ordenDia((string) $item['dia']),
                (string) $item['hora_inicio'],
                Str::lower(Str::ascii((string) $item['profesor'])),
                (int) $item['actividad_id'],
            ))
            ->values();
    }

    private function construirCargaDocente(Collection $sesiones): Collection
    {
        return $sesiones
            ->groupBy(fn (array $item) => implode('|', [
                $item['profesor_id'] ?? 'sin-docente',
                $item['tipo'],
                $item['actividad_id'],
            ]))
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $minutos = (int) $items->sum('minutos');

                return [
                    'profesor_id' => $primero['profesor_id'],
                    'docente' => $primero['profesor'],
                    'tipo' => $primero['tipo'],
                    'actividad_id' => $primero['actividad_id'],
                    'materia' => $primero['nombre'],
                    'clave' => $primero['clave'],
                    'grupos' => $items
                        ->flatMap(fn (array $item) => $item['grupos'])
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'sesiones_semanales' => $items->count(),
                    'minutos' => $minutos,
                    'horas_reloj' => round($minutos / 60, 2),
                    'sin_docente' => blank($primero['profesor_id']),
                ];
            })
            ->sortBy(fn (array $item) => sprintf(
                '%d-%s-%s',
                $item['sin_docente'] ? 1 : 0,
                Str::lower(Str::ascii((string) $item['docente'])),
                Str::lower(Str::ascii((string) $item['materia'])),
            ))
            ->values();
    }

    private function construirComplementarias(Collection $sesiones): Collection
    {
        return $sesiones
            ->where('complementaria', true)
            ->groupBy(fn (array $item) => implode('|', [
                $item['tipo'],
                $item['actividad_id'],
                $item['profesor_id'] ?? 'sin-docente',
            ]))
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $minutos = (int) $items->sum('minutos');

                return [
                    'tipo' => $primero['tipo'] === 'taller' ? 'Taller conjunto' : 'Materia complementaria',
                    'actividad_id' => $primero['actividad_id'],
                    'nombre' => $primero['nombre'],
                    'clave' => $primero['clave'],
                    'profesor_id' => $primero['profesor_id'],
                    'docente' => $primero['profesor'],
                    'grupos' => $items
                        ->flatMap(fn (array $item) => $item['grupos'])
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'sesiones_semanales' => $items->count(),
                    'horas_reloj' => round($minutos / 60, 2),
                ];
            })
            ->sortBy(fn (array $item) => sprintf(
                '%s-%s',
                Str::lower(Str::ascii((string) $item['nombre'])),
                Str::lower(Str::ascii((string) $item['docente'])),
            ))
            ->values();
    }

    private function construirMatrizGeneral(
        Collection $dias,
        Collection $horas,
        Collection $recesoHoraIds,
        Collection $actividades,
    ): array {
        $filas = $horas->map(function ($hora) use ($dias, $recesoHoraIds, $actividades): array {
            $esReceso = $recesoHoraIds->contains((int) $hora->id);
            $celdas = [];

            foreach ($dias as $dia) {
                $celdas[(int) $dia->id] = $esReceso
                    ? []
                    : $actividades
                        ->where('dia_id', (int) $dia->id)
                        ->where('hora_id', (int) $hora->id)
                        ->sortBy(fn (array $item) => sprintf(
                            '%s-%s',
                            $item['grupo'],
                            Str::lower(Str::ascii($item['nombre'])),
                        ))
                        ->values()
                        ->all();
            }

            return [
                'hora_id' => (int) $hora->id,
                'hora' => $this->textoHora($hora->hora_inicio, $hora->hora_fin),
                'es_receso' => $esReceso,
                'celdas' => $celdas,
            ];
        })->values();

        return [
            'dias' => $dias,
            'filas' => $filas,
        ];
    }

    private function construirFormatosDocentes(
        Collection $sesiones,
        Collection $dias,
        Collection $horas,
        Collection $recesoHoraIds,
    ): Collection {
        return $sesiones
            ->filter(fn (array $item) => filled($item['profesor_id']))
            ->groupBy('profesor_id')
            ->map(function (Collection $items, $profesorId) use ($dias, $horas, $recesoHoraIds): array {
                $primero = $items->first();

                return [
                    'id' => (int) $profesorId,
                    'nombre' => (string) $primero['profesor'],
                    'filas' => $this->construirFilasMatriz(
                        items: $items,
                        dias: $dias,
                        horas: $horas,
                        recesoHoraIds: $recesoHoraIds,
                        modo: 'docente',
                    ),
                ];
            })
            ->sortBy(fn (array $item) => Str::lower(Str::ascii($item['nombre'])))
            ->values();
    }

    private function construirFormatosGrupos(
        Collection $grupos,
        Collection $actividades,
        Collection $dias,
        Collection $horas,
        Collection $recesoHoraIds,
    ): Collection {
        return $grupos
            ->map(function (Grupo $grupo) use ($actividades, $dias, $horas, $recesoHoraIds): array {
                $items = $actividades->where('grupo_id', (int) $grupo->id)->values();

                return [
                    'id' => (int) $grupo->id,
                    'nombre' => $this->etiquetaGrupo($grupo),
                    'filas' => $this->construirFilasMatriz(
                        items: $items,
                        dias: $dias,
                        horas: $horas,
                        recesoHoraIds: $recesoHoraIds,
                        modo: 'grupo',
                    ),
                ];
            })
            ->values();
    }

    private function construirFilasMatriz(
        Collection $items,
        Collection $dias,
        Collection $horas,
        Collection $recesoHoraIds,
        string $modo,
    ): Collection {
        return $horas
            ->map(function ($hora) use ($items, $dias, $recesoHoraIds, $modo): array {
                $esReceso = $recesoHoraIds->contains((int) $hora->id);
                $celdas = [];

                foreach ($dias as $dia) {
                    $celdas[(int) $dia->id] = $esReceso
                        ? []
                        : $items
                            ->where('dia_id', (int) $dia->id)
                            ->where('hora_id', (int) $hora->id)
                            ->map(function (array $item) use ($modo): array {
                                $contexto = $modo === 'docente'
                                    ? implode(', ', $item['grupos'] ?? [$item['grupo'] ?? ''])
                                    : (string) ($item['profesor'] ?? 'Sin docente');

                                return [
                                    'nombre' => (string) $item['nombre'],
                                    'clave' => (string) $item['clave'],
                                    'contexto' => $contexto,
                                    'tipo' => (string) $item['tipo'],
                                ];
                            })
                            ->values()
                            ->all();
                }

                return [
                    'hora' => $this->textoHora($hora->hora_inicio, $hora->hora_fin),
                    'es_receso' => $esReceso,
                    'celdas' => $celdas,
                ];
            })
            ->values();
    }

    private function construirAlertas(
        Collection $grupos,
        Collection $dias,
        Collection $horas,
        Collection $recesoHoraIds,
        Collection $actividades,
        Collection $sesiones,
        array $receso,
    ): array {
        $sinDocente = $sesiones
            ->filter(fn (array $item) => blank($item['profesor_id']))
            ->map(fn (array $item) => [
                'materia' => $item['nombre'],
                'grupos' => $item['grupos'],
                'dia' => $item['dia'],
                'hora' => $item['hora'],
            ])
            ->values();

        $ocupadas = $actividades
            ->map(fn (array $item) => implode('|', [
                $item['grupo_id'],
                $item['dia_id'],
                $item['hora_id'],
            ]))
            ->flip();

        $espaciosVacios = collect();

        foreach ($grupos as $grupo) {
            foreach ($dias as $dia) {
                foreach ($horas as $hora) {
                    if ($recesoHoraIds->contains((int) $hora->id)) {
                        continue;
                    }

                    $clave = implode('|', [(int) $grupo->id, (int) $dia->id, (int) $hora->id]);

                    if (!$ocupadas->has($clave)) {
                        $espaciosVacios->push([
                            'grupo' => $this->etiquetaGrupo($grupo),
                            'dia' => (string) $dia->dia,
                            'hora' => $this->textoHora($hora->hora_inicio, $hora->hora_fin),
                        ]);
                    }
                }
            }
        }

        $traslapesDocente = $sesiones
            ->filter(fn (array $item) => filled($item['profesor_id']))
            ->groupBy(fn (array $item) => implode('|', [
                $item['profesor_id'],
                $item['dia_id'],
                $item['hora_id'],
            ]))
            ->filter(fn (Collection $items) => $items->count() > 1)
            ->map(function (Collection $items): array {
                $primero = $items->first();

                return [
                    'docente' => $primero['profesor'],
                    'dia' => $primero['dia'],
                    'hora' => $primero['hora'],
                    'actividades' => $items
                        ->map(fn (array $item) => $item['nombre'] . ' - ' . implode(', ', $item['grupos']))
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        $traslapesGrupo = $actividades
            ->groupBy(fn (array $item) => implode('|', [
                $item['grupo_id'],
                $item['dia_id'],
                $item['hora_id'],
            ]))
            ->map(fn (Collection $items) => $items->unique('clave_sesion')->values())
            ->filter(fn (Collection $items) => $items->count() > 1)
            ->map(function (Collection $items): array {
                $primero = $items->first();

                return [
                    'grupo' => $primero['grupo'],
                    'dia' => $primero['dia'],
                    'hora' => $primero['hora'],
                    'actividades' => $items
                        ->map(fn (array $item) => $item['nombre'] . ' - ' . $item['profesor'])
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        $recesoInconsistente = (bool) ($receso['inconsistente'] ?? false);

        return [
            'sin_docente' => $sinDocente,
            'espacios_vacios' => $espaciosVacios,
            'traslapes_docente' => $traslapesDocente,
            'traslapes_grupo' => $traslapesGrupo,
            'receso_inconsistente' => $recesoInconsistente,
            'total' => $sinDocente->count()
                + $espaciosVacios->count()
                + $traslapesDocente->count()
                + $traslapesGrupo->count()
                + ($recesoInconsistente ? 1 : 0),
        ];
    }

    public function etiquetaGrupo(Grupo $grupo): string
    {
        $grado = trim((string) ($grupo->grado?->nombre ?? ''));
        $grado = preg_match('/^\d+$/', $grado) ? $grado . '°' : $grado;
        $nombreGrupo = trim((string) ($grupo->asignacionGrupo?->nombre ?? ''));
        $texto = trim($grado . ' ' . $nombreGrupo);

        return $texto !== '' ? $texto : 'Grupo ' . $grupo->id;
    }

    private function nombrePersona($persona): string
    {
        if (!$persona) {
            return '';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ])));
    }

    private function duracionMinutos(?string $inicio, ?string $fin): int
    {
        if (!$inicio || !$fin) {
            return 0;
        }

        try {
            $desde = Carbon::parse($inicio);
            $hasta = Carbon::parse($fin);

            return max(0, (int) $desde->diffInMinutes($hasta, false));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function textoHora(?string $inicio, ?string $fin): string
    {
        return $this->horaCorta($inicio) . ' - ' . $this->horaCorta($fin);
    }

    private function horaCorta(?string $hora): string
    {
        if (!$hora) {
            return '—';
        }

        try {
            return Carbon::parse($hora)->format('H:i');
        } catch (\Throwable) {
            return $hora;
        }
    }

    private function ordenDia(string $dia, int $orden = 999999): string
    {
        $nombre = Str::lower(Str::ascii(trim($dia)));
        $ordenNombre = match (true) {
            str_contains($nombre, 'lunes') => 1,
            str_contains($nombre, 'martes') => 2,
            str_contains($nombre, 'miercoles') => 3,
            str_contains($nombre, 'jueves') => 4,
            str_contains($nombre, 'viernes') => 5,
            default => 99,
        };

        return sprintf('%02d-%06d', $ordenNombre, $orden);
    }
}
