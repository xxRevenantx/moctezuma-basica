<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Dia;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\HorarioAsignacionRegla;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\HorarioDocenteDisponibilidad;
use App\Models\HorarioRegla;
use App\Models\HorarioVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class HorarioOptimizadorService
{
    public function __construct(private readonly HorarioVersionService $versiones)
    {
    }

    /**
     * @return array{engine:string,propuestas:array<int,HorarioVersion>,advertencias:array<int,string>}
     */
    public function generarPropuestas(
        int $cicloEscolarId,
        int $nivelId,
        bool $conservarActual,
        ?int $usuarioId
    ): array {
        $this->sincronizarReglasAsignacion($cicloEscolarId, $nivelId);
        $payload = $this->construirPayload($cicloEscolarId, $nivelId, $conservarActual);

        if ($payload['assignments'] === []) {
            throw new \DomainException('No existen asignaciones de materias utilizables para generar propuestas.');
        }
        if ($payload['days'] === [] || $payload['hours'] === []) {
            throw new \DomainException('El nivel no tiene días u horas configurados.');
        }

        $resultado = $this->ejecutarPython($payload);
        $advertencias = [];
        if (! $resultado['ok']) {
            $advertencias[] = $resultado['error'];
            $resultado = $this->generarHeuristico($payload);
        }

        $asignaciones = AsignacionMateria::query()
            ->with(['materia', 'grupo', 'profesor'])
            ->whereIn('id', collect($payload['assignments'])->pluck('id'))
            ->get()
            ->keyBy('id');

        $bloqueados = collect($payload['locked']);
        $propuestas = [];
        foreach ($resultado['proposals'] as $propuesta) {
            $detalles = [];

            foreach ($bloqueados as $bloqueado) {
                if (isset($bloqueado['detail']) && is_array($bloqueado['detail'])) {
                    $detalles[] = array_merge($bloqueado['detail'], ['bloqueado' => true, 'origen' => 'bloqueado']);
                    continue;
                }
                $asignacion = $asignaciones->get((int) ($bloqueado['assignment_id'] ?? 0));
                if (! $asignacion) {
                    continue;
                }
                $detalles[] = $this->detalleDesdeAsignacion(
                    $asignacion,
                    (int) $bloqueado['day_id'],
                    (int) $bloqueado['hour_id'],
                    true,
                    'actual'
                );
            }

            foreach ($propuesta['assigned'] as $fila) {
                $asignacion = $asignaciones->get((int) $fila['assignment_id']);
                if (! $asignacion) {
                    continue;
                }
                $detalles[] = $this->detalleDesdeAsignacion(
                    $asignacion,
                    (int) $fila['day_id'],
                    (int) $fila['hour_id'],
                    false,
                    'generado'
                );
            }

            $detalles = $this->clasificarSimultaneidades($detalles, $asignaciones, collect($payload['external_locked'] ?? []));
            $metadata = [
                'motor' => $resultado['engine'],
                'estado_solver' => $propuesta['status'] ?? 'UNKNOWN',
                'objetivo_solver' => $propuesta['objective_value'] ?? null,
                'tiempo_solver' => $propuesta['wall_time'] ?? null,
                'sesiones_sin_asignar' => count($propuesta['unassigned'] ?? []),
                'conservar_horario_actual' => $conservarActual,
            ];

            $propuestas[] = $this->versiones->crearPropuesta(
                $cicloEscolarId,
                $nivelId,
                (string) $propuesta['objective'],
                $detalles,
                $metadata,
                $usuarioId
            );
        }

        return [
            'engine' => $resultado['engine'],
            'propuestas' => $propuestas,
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Regenera únicamente los bloques no fijados y conserva como restricciones
     * todos los detalles bloqueados de la versión seleccionada.
     *
     * @return array{engine:string,propuestas:array<int,HorarioVersion>,advertencias:array<int,string>}
     */
    public function regenerarNoBloqueados(HorarioVersion $version, ?int $usuarioId): array
    {
        $version->loadMissing('detalles');
        $bloqueados = $version->detalles
            ->where('bloqueado', true)
            ->whereNotNull('asignacion_materia_id')
            ->map(fn ($detalle) => [
                'assignment_id' => (int) $detalle->asignacion_materia_id,
                'group_id' => (int) $detalle->grupo_id,
                'teacher_id' => $detalle->profesor_id ? (int) $detalle->profesor_id : null,
                'day_id' => (int) $detalle->dia_id,
                'hour_id' => (int) $detalle->hora_id,
                'detail' => $detalle->only([
                    'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id',
                    'hora_id', 'dia_id', 'asignacion_materia_id', 'taller_sesion_id',
                    'profesor_id', 'sesion_compartida', 'clave_sesion_compartida',
                    'motivo_sesion_compartida', 'traslape_excepcional',
                    'motivo_traslape_excepcional', 'motivo_autorizacion_disponibilidad',
                    'coensenanza',
                ]),
            ])->values()->all();

        $this->sincronizarReglasAsignacion($version->ciclo_escolar_id, $version->nivel_id);
        $payload = $this->construirPayload(
            (int) $version->ciclo_escolar_id,
            (int) $version->nivel_id,
            false,
            $bloqueados
        );

        $resultado = $this->ejecutarPython($payload);
        $advertencias = [];
        if (! $resultado['ok']) {
            $advertencias[] = $resultado['error'];
            $resultado = $this->generarHeuristico($payload);
        }

        $asignaciones = AsignacionMateria::query()
            ->with(['materia', 'grupo', 'profesor'])
            ->whereIn('id', collect($payload['assignments'])->pluck('id'))
            ->get()->keyBy('id');
        $propuestas = [];
        foreach ($resultado['proposals'] as $propuesta) {
            $detalles = collect($bloqueados)->pluck('detail')->filter()->map(fn ($detalle) => array_merge($detalle, [
                'bloqueado' => true,
                'origen' => 'bloqueado',
            ]))->values()->all();
            foreach ($propuesta['assigned'] as $fila) {
                $asignacion = $asignaciones->get((int) $fila['assignment_id']);
                if ($asignacion) {
                    $detalles[] = $this->detalleDesdeAsignacion($asignacion, (int) $fila['day_id'], (int) $fila['hour_id'], false, 'regenerado');
                }
            }
            $detalles = $this->clasificarSimultaneidades($detalles, $asignaciones, collect($payload['external_locked'] ?? []));
            $propuestas[] = $this->versiones->crearPropuesta(
                (int) $version->ciclo_escolar_id,
                (int) $version->nivel_id,
                (string) $propuesta['objective'],
                $detalles,
                [
                    'motor' => $resultado['engine'],
                    'version_base_id' => $version->id,
                    'bloques_conservados' => count($bloqueados),
                    'sesiones_sin_asignar' => count($propuesta['unassigned'] ?? []),
                    'regeneracion_parcial' => true,
                ],
                $usuarioId
            );
        }

        return ['engine' => $resultado['engine'], 'propuestas' => $propuestas, 'advertencias' => $advertencias];
    }

    public function disponibilidadMotor(): array
    {
        $python = (string) config('horarios_inteligentes.python_bin');
        $script = (string) config('horarios_inteligentes.solver_script');
        if (! File::exists($script)) {
            return ['ok' => false, 'mensaje' => 'No se encontró el script del optimizador.'];
        }

        try {
            $process = new Process([$python, '-c', 'import ortools; print(ortools.__version__)']);
            $process->setTimeout(10);
            $process->run();
            return $process->isSuccessful()
                ? ['ok' => true, 'mensaje' => 'OR-Tools '.$process->getOutput()]
                : ['ok' => false, 'mensaje' => trim($process->getErrorOutput() ?: $process->getOutput())];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'mensaje' => $exception->getMessage()];
        }
    }

    private function sincronizarReglasAsignacion(int $cicloEscolarId, int $nivelId): void
    {
        AsignacionMateria::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->withCount('horarios')
            ->each(function (AsignacionMateria $asignacion): void {
                HorarioAsignacionRegla::query()->firstOrCreate(
                    ['asignacion_materia_id' => $asignacion->id],
                    [
                        'sesiones_semanales' => max(1, min(20, (int) $asignacion->horarios_count)),
                        'max_sesiones_dia' => 1,
                        'permitir_bloques_consecutivos' => false,
                        'max_bloques_consecutivos' => 2,
                        'dias_minimos' => max(1, min(5, (int) $asignacion->horarios_count)),
                        'preferencia_horaria' => 'cualquiera',
                        'permitir_multigrado' => true,
                        'bloqueada' => false,
                    ]
                );
            });
    }

    private function construirPayload(int $cicloEscolarId, int $nivelId, bool $conservarActual, ?array $bloqueadosPersonalizados = null): array
    {
        $dias = Dia::query()->where('nivel_id', $nivelId)->orderBy('orden')->get();
        $horas = Hora::query()->where('nivel_id', $nivelId)->orderBy('orden')->get();
        $minutosMinimos = (int) config('horarios_inteligentes.min_slot_minutes', 40);

        $asignaciones = AsignacionMateria::query()
            ->with(['materia', 'grupo', 'profesor'])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->whereHas('materia', fn ($q) => $q->where('receso', false))
            ->whereNotNull('grupo_id')
            ->orderBy('grupo_id')
            ->orderBy('orden')
            ->get();

        $reglas = HorarioAsignacionRegla::query()
            ->whereIn('asignacion_materia_id', $asignaciones->pluck('id'))
            ->get()->keyBy('asignacion_materia_id');

        $profesorIds = $asignaciones->pluck('profesor_id')->filter()->unique();
        $configuraciones = HorarioDocenteConfiguracion::query()
            ->with(['primeraHora', 'ultimaHora'])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('persona_id', $profesorIds)
            ->where(function ($q) use ($nivelId): void {
                $q->whereNull('nivel_id')->orWhere('nivel_id', $nivelId);
            })
            ->get()
            ->sortByDesc(fn ($item) => (int) $item->nivel_id === $nivelId)
            ->unique('persona_id')
            ->keyBy('persona_id');

        $disponibilidades = HorarioDocenteDisponibilidad::query()
            ->whereIn('configuracion_id', $configuraciones->pluck('id'))
            ->get();

        $reglasGlobales = HorarioRegla::query()->activas()->get()->keyBy('codigo');

        $locked = $bloqueadosPersonalizados ?? [];
        if ($bloqueadosPersonalizados === null && $conservarActual) {
            $locked = Horario::query()
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->whereNotNull('asignacion_materia_id')
                ->with('asignacionMateria')
                ->get()
                ->map(fn (Horario $horario) => [
                    'assignment_id' => $horario->asignacion_materia_id,
                    'group_id' => $horario->grupo_id,
                    'teacher_id' => $horario->profesor_id ?? $horario->asignacionMateria?->profesor_id,
                    'day_id' => $horario->dia_id,
                    'hour_id' => $horario->hora_id,
                ])->all();
        }

        $externalLocked = Horario::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', '!=', $nivelId)
            ->whereIn('profesor_id', $profesorIds)
            ->with(['dia', 'hora'])
            ->get()
            ->flatMap(function (Horario $externo) use ($dias, $horas): array {
                $diaActual = $dias->first(fn ($dia) => mb_strtolower((string) $dia->dia) === mb_strtolower((string) $externo->dia?->dia));
                if (! $diaActual || ! $externo->hora) {
                    return [];
                }
                return $horas->filter(fn ($hora) => (string) $hora->hora_inicio < (string) $externo->hora->hora_fin
                    && (string) $hora->hora_fin > (string) $externo->hora->hora_inicio)
                    ->map(fn ($hora) => [
                        'teacher_id' => (int) $externo->profesor_id,
                        'day_id' => (int) $diaActual->id,
                        'hour_id' => (int) $hora->id,
                        'external_level_id' => (int) $externo->nivel_id,
                        'external_horario_id' => (int) $externo->id,
                    ])->all();
            })->values()->all();

        return [
            'days' => $dias->map(fn ($dia) => ['id' => (int) $dia->id, 'name' => $dia->dia, 'order' => (int) $dia->orden])->all(),
            'hours' => $horas->map(function ($hora) use ($minutosMinimos): array {
                $inicio = \Carbon\Carbon::parse($hora->hora_inicio);
                $fin = \Carbon\Carbon::parse($hora->hora_fin);
                $duracion = $inicio->diffInMinutes($fin);
                return [
                    'id' => (int) $hora->id,
                    'start' => (string) $hora->hora_inicio,
                    'end' => (string) $hora->hora_fin,
                    'order' => (int) $hora->orden,
                    'duration' => $duracion,
                    'blocked' => $duracion < $minutosMinimos,
                ];
            })->all(),
            'assignments' => $asignaciones->map(function (AsignacionMateria $asignacion) use ($reglas): array {
                $regla = $reglas->get($asignacion->id);
                return [
                    'id' => (int) $asignacion->id,
                    'group_id' => (int) $asignacion->grupo_id,
                    'teacher_id' => $asignacion->profesor_id ? (int) $asignacion->profesor_id : null,
                    'subject' => $asignacion->materia?->materia ?? 'Materia',
                    'subject_slug' => $asignacion->materia?->slug,
                    'required' => max(1, (int) ($regla?->sesiones_semanales ?? 1)),
                    'max_per_day' => max(1, (int) ($regla?->max_sesiones_dia ?? 1)),
                    'allow_consecutive' => (bool) ($regla?->permitir_bloques_consecutivos ?? false),
                    'max_consecutive' => max(1, (int) ($regla?->max_bloques_consecutivos ?? 2)),
                    'min_days' => max(1, (int) ($regla?->dias_minimos ?? 1)),
                    'preference' => $regla?->preferencia_horaria ?? 'cualquiera',
                    'allow_multigrade' => (bool) ($regla?->permitir_multigrado ?? true),
                ];
            })->all(),
            'teacher_configs' => $configuraciones->mapWithKeys(fn ($cfg) => [(string) $cfg->persona_id => [
                'max_simultaneous' => max(1, (int) $cfg->max_grupos_simultaneos),
                'max_daily' => max(1, (int) $cfg->max_horas_diarias),
                'max_consecutive' => max(1, (int) $cfg->max_horas_consecutivas),
                'min_rest' => max(0, (int) $cfg->min_descanso_bloques),
                'max_gaps' => max(0, (int) $cfg->max_huecos_diarios),
                'first_order' => $cfg->primeraHora?->orden !== null ? (int) $cfg->primeraHora->orden : null,
                'last_order' => $cfg->ultimaHora?->orden !== null ? (int) $cfg->ultimaHora->orden : null,
                'allow_multigrade' => (bool) $cfg->permitir_multigrado,
                'allow_different_subjects' => (bool) $cfg->permitir_materias_distintas,
            ]])->all(),
            'availability' => $disponibilidades->mapWithKeys(function ($disp) use ($configuraciones): array {
                $cfg = $configuraciones->firstWhere('id', $disp->configuracion_id);
                if (! $cfg) {
                    return [];
                }
                return ["{$cfg->persona_id}:{$disp->dia_id}:{$disp->hora_id}" => $disp->estado];
            })->all(),
            'locked' => $locked,
            'external_locked' => $externalLocked,
            'rules' => $reglasGlobales->map(fn ($regla) => ['weight' => (int) $regla->peso, 'parameters' => $regla->parametros])->all(),
            'objectives' => ['compactar_docente', 'distribucion_alumnos', 'preferencias', 'equilibrio'],
            'seconds_per_objective' => (int) config('horarios_inteligentes.seconds_per_objective', 12),
        ];
    }

    private function ejecutarPython(array $payload): array
    {
        $python = (string) config('horarios_inteligentes.python_bin');
        $script = (string) config('horarios_inteligentes.solver_script');
        $temporal = storage_path('app/private/horarios/solver-'.Str::uuid().'.json');
        File::ensureDirectoryExists(dirname($temporal));
        File::put($temporal, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            $process = new Process([$python, $script, $temporal]);
            $process->setTimeout((int) config('horarios_inteligentes.timeout_seconds', 90));
            $process->run();
            $salida = trim($process->getOutput());
            $datos = json_decode($salida, true);
            if (! $process->isSuccessful() || ! is_array($datos) || ! ($datos['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'error' => trim($process->getErrorOutput() ?: ($datos['error'] ?? $salida ?: 'El motor no respondió correctamente.')),
                ];
            }
            return $datos;
        } catch (\Throwable $exception) {
            return ['ok' => false, 'error' => 'OR-Tools no pudo ejecutarse: '.$exception->getMessage()];
        } finally {
            File::delete($temporal);
        }
    }

    /**
     * Generador de respaldo. No reemplaza CP-SAT, pero permite trabajar si Python aún no está configurado.
     */
    private function generarHeuristico(array $payload): array
    {
        $propuestas = [];
        foreach ($payload['objectives'] as $objetivo) {
            $ocupacionGrupo = [];
            $ocupacionDocente = [];
            $conteoAsignacion = [];
            $asignacionesBloqueadasContadas = [];
            foreach ($payload['locked'] as $locked) {
                $ocupacionGrupo[$locked['group_id'].'-'.$locked['day_id'].'-'.$locked['hour_id']] = true;
                if ($locked['teacher_id']) {
                    $key = $locked['teacher_id'].'-'.$locked['day_id'].'-'.$locked['hour_id'];
                    $ocupacionDocente[$key] = ($ocupacionDocente[$key] ?? 0) + 1;
                }
                $claveSesion = $locked['assignment_id'].'-'.$locked['group_id'].'-'.$locked['day_id'].'-'.$locked['hour_id'];
                if (! isset($asignacionesBloqueadasContadas[$claveSesion])) {
                    $conteoAsignacion[$locked['assignment_id']] = ($conteoAsignacion[$locked['assignment_id']] ?? 0) + 1;
                    $asignacionesBloqueadasContadas[$claveSesion] = true;
                }
            }
            foreach ($payload['external_locked'] ?? [] as $external) {
                $key = $external['teacher_id'].'-'.$external['day_id'].'-'.$external['hour_id'];
                $ocupacionDocente[$key] = ($ocupacionDocente[$key] ?? 0) + 1;
            }

            $slots = collect($payload['days'])->flatMap(fn ($day) => collect($payload['hours'])
                ->where('blocked', false)
                ->map(fn ($hour) => ['day_id' => $day['id'], 'hour_id' => $hour['id'], 'day_order' => $day['order'], 'hour_order' => $hour['order']]))
                ->values();

            $assigned = [];
            $unassigned = [];
            foreach (collect($payload['assignments'])->sortByDesc(fn ($a) => $a['required']) as $assignment) {
                $faltan = max(0, $assignment['required'] - ($conteoAsignacion[$assignment['id']] ?? 0));
                for ($session = 0; $session < $faltan; $session++) {
                    $candidatos = $slots->filter(function ($slot) use ($assignment, $payload, $ocupacionGrupo, $ocupacionDocente): bool {
                        $gk = $assignment['group_id'].'-'.$slot['day_id'].'-'.$slot['hour_id'];
                        if (isset($ocupacionGrupo[$gk])) {
                            return false;
                        }
                        $teacher = $assignment['teacher_id'];
                        if ($teacher) {
                            $configDocente = $payload['teacher_configs'][(string) $teacher] ?? [];
                            $primera = $configDocente['first_order'] ?? null;
                            $ultima = $configDocente['last_order'] ?? null;
                            if ($primera !== null && $slot['hour_order'] < $primera) {
                                return false;
                            }
                            if ($ultima !== null && $slot['hour_order'] > $ultima) {
                                return false;
                            }

                            $state = $payload['availability']["{$teacher}:{$slot['day_id']}:{$slot['hour_id']}"] ?? 'disponible';
                            if ($state === 'no_disponible') {
                                return false;
                            }
                            $max = $configDocente['max_simultaneous'] ?? 2;
                            if (($ocupacionDocente[$teacher.'-'.$slot['day_id'].'-'.$slot['hour_id']] ?? 0) >= $max) {
                                return false;
                            }

                            $descanso = max(0, (int) ($configDocente['min_rest'] ?? 0));
                            if ($descanso > 0) {
                                foreach ($payload['hours'] as $otraHora) {
                                    if (abs((int) $otraHora['order'] - (int) $slot['hour_order']) > $descanso || (int) $otraHora['order'] === (int) $slot['hour_order']) {
                                        continue;
                                    }
                                    if (($ocupacionDocente[$teacher.'-'.$slot['day_id'].'-'.$otraHora['id']] ?? 0) > 0) {
                                        return false;
                                    }
                                }
                            }
                        }
                        return true;
                    })->sortBy(function ($slot) use ($assignment, $objetivo, $payload): int {
                        $teacher = $assignment['teacher_id'];
                        $state = $teacher ? ($payload['availability']["{$teacher}:{$slot['day_id']}:{$slot['hour_id']}"] ?? 'disponible') : 'disponible';
                        $reglas = $payload['rules'] ?? [];
                        $pesoPreferido = (int) ($reglas['premiar_bloque_preferido']['weight'] ?? 6);
                        $pesoAutorizacion = (int) ($reglas['disponibilidad_docente']['weight'] ?? 100);
                        $score = $state === 'preferido' ? -max(1, $pesoPreferido * 5) : ($state === 'autorizacion' ? max(25, $pesoAutorizacion) : 0);
                        if ($assignment['preference'] === 'primeras') {
                            $score += $slot['hour_order'] * 3;
                        } elseif ($assignment['preference'] === 'ultimas') {
                            $score -= $slot['hour_order'] * 3;
                        }
                        if ($objetivo === 'distribucion_alumnos') {
                            $score += $slot['day_order'] * 2;
                        } elseif ($objetivo === 'compactar_docente') {
                            $score += $slot['hour_order'];
                        }
                        return $score;
                    });

                    $slot = $candidatos->first();
                    if (! $slot) {
                        $unassigned[] = ['assignment_id' => $assignment['id'], 'session' => $session];
                        continue;
                    }

                    $assigned[] = [
                        'assignment_id' => $assignment['id'],
                        'group_id' => $assignment['group_id'],
                        'teacher_id' => $assignment['teacher_id'],
                        'day_id' => $slot['day_id'],
                        'hour_id' => $slot['hour_id'],
                    ];
                    $ocupacionGrupo[$assignment['group_id'].'-'.$slot['day_id'].'-'.$slot['hour_id']] = true;
                    if ($assignment['teacher_id']) {
                        $key = $assignment['teacher_id'].'-'.$slot['day_id'].'-'.$slot['hour_id'];
                        $ocupacionDocente[$key] = ($ocupacionDocente[$key] ?? 0) + 1;
                    }
                }
            }

            $propuestas[] = [
                'objective' => $objetivo,
                'status' => $unassigned === [] ? 'HEURISTIC_COMPLETE' : 'HEURISTIC_PARTIAL',
                'assigned' => $assigned,
                'unassigned' => $unassigned,
                'objective_value' => null,
                'wall_time' => null,
            ];
        }

        return ['ok' => true, 'engine' => 'php-heuristico-respaldo', 'proposals' => $propuestas];
    }

    private function detalleDesdeAsignacion(
        AsignacionMateria $asignacion,
        int $diaId,
        int $horaId,
        bool $bloqueado,
        string $origen
    ): array {
        return [
            'nivel_id' => (int) $asignacion->nivel_id,
            'grado_id' => (int) $asignacion->grado_id,
            'generacion_id' => (int) $asignacion->generacion_id,
            'semestre_id' => $asignacion->semestre_id ? (int) $asignacion->semestre_id : null,
            'grupo_id' => (int) $asignacion->grupo_id,
            'hora_id' => $horaId,
            'dia_id' => $diaId,
            'asignacion_materia_id' => (int) $asignacion->id,
            'profesor_id' => $asignacion->profesor_id ? (int) $asignacion->profesor_id : null,
            'bloqueado' => $bloqueado,
            'origen' => $origen,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $detalles
     * @return array<int,array<string,mixed>>
     */
    private function clasificarSimultaneidades(array $detalles, Collection $asignaciones, Collection $externos): array
    {
        $coleccion = collect($detalles);
        $grupos = $coleccion
            ->filter(fn ($d) => filled($d['profesor_id'] ?? null))
            ->groupBy(fn ($d) => $d['profesor_id'].'-'.$d['dia_id'].'-'.$d['hora_id'])
            ->filter(fn ($items) => $items->pluck('grupo_id')->unique()->count() > 1);

        foreach ($grupos as $items) {
            $indices = $items->keys();
            $materias = $items->map(function ($detalle) use ($asignaciones): string {
                $asignacion = $asignaciones->get((int) $detalle['asignacion_materia_id']);
                return mb_strtolower(trim((string) ($asignacion?->materia?->materia ?? '')));
            })->unique();

            if ($materias->count() === 1) {
                $clave = 'auto-compartida-'.Str::uuid();
                foreach ($indices as $indice) {
                    $coleccion[$indice] = array_merge($coleccion[$indice], [
                        'sesion_compartida' => true,
                        'clave_sesion_compartida' => $clave,
                        'motivo_sesion_compartida' => 'Sesión multigrado propuesta automáticamente para el mismo docente y materia.',
                    ]);
                }
            } else {
                foreach ($indices as $indice) {
                    $coleccion[$indice] = array_merge($coleccion[$indice], [
                        'traslape_excepcional' => true,
                        'motivo_traslape_excepcional' => 'Simultaneidad propuesta por el optimizador; requiere revisión y aceptación humana antes de publicar.',
                    ]);
                }
            }
        }

        $externosPorBloque = $externos->groupBy(fn ($item) => $item['teacher_id'].'-'.$item['day_id'].'-'.$item['hour_id']);
        foreach ($coleccion as $indice => $detalle) {
            if (! filled($detalle['profesor_id'] ?? null)) {
                continue;
            }
            $clave = $detalle['profesor_id'].'-'.$detalle['dia_id'].'-'.$detalle['hora_id'];
            if ($externosPorBloque->has($clave)) {
                $coleccion[$indice] = array_merge($detalle, [
                    'traslape_excepcional' => true,
                    'motivo_traslape_excepcional' => 'Cruce con otro nivel detectado por el optimizador; requiere autorización humana antes de publicar.',
                ]);
            }
        }

        return $coleccion->values()->all();
    }
}
