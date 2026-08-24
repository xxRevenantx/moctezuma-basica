<?php

namespace App\Livewire\Accion\Concerns;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario as HorarioModel;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\TallerSesion;
use App\Services\CicloNivelGateService;
use App\Services\ContextoEscolarService;
use App\Services\GroqHorarioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Exports\HorarioExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

trait ProveeAnaliticaHorario
{
    public function getPuedeDescargarHorarioProperty(): bool
    {
        return $this->filtrosCompletos();
    }

    public function getUrlDescargaHorarioProperty(): string
    {
        if (!$this->puedeDescargarHorario) {
            return '#';
        }

        return route('misrutas.horarios.pdf', [
            'slug_nivel' => $this->slug_nivel,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
        ]);
    }

    protected function obtenerColorPastel(?string $texto = null): string
    {
        $texto = filled($texto) ? $texto : 'sin-profesor';

        $paleta = [
            '#FDE68A',
            '#BFDBFE',
            '#C7D2FE',
            '#A7F3D0',
            '#FBCFE8',
            '#DDD6FE',
            '#FECACA',
            '#BAE6FD',
            '#D9F99D',
            '#FED7AA',
            '#E9D5FF',
            '#99F6E4',
        ];

        $indice = abs(crc32((string) $texto)) % count($paleta);

        return $paleta[$indice];
    }

    protected function obtenerColorTexto(string $fondoHex): string
    {
        $hex = ltrim($fondoHex, '#');

        if (strlen($hex) !== 6) {
            return '#1F2937';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminosidad = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $luminosidad > 170 ? '#1F2937' : '#FFFFFF';
    }

    public function obtenerEstiloProfesor(?string $nombreProfesor = null): array
    {
        $fondo = $this->obtenerColorPastel($nombreProfesor);
        $texto = $this->obtenerColorTexto($fondo);

        return [
            'background' => $fondo,
            'color' => $texto,
            'border' => $texto === '#FFFFFF'
                ? 'rgba(255,255,255,0.25)'
                : 'rgba(15,23,42,0.08)',
        ];
    }

    public function textoGrupo($grupo, string $valorPorDefecto = 'Sin grupo'): string
    {
        if (!$grupo) {
            return $valorPorDefecto;
        }

        return $grupo->asignacionGrupo?->nombre ?? $valorPorDefecto;
    }

    public function getTotalCeldasProperty(): int
    {
        return $this->horas->count() * $this->dias->count();
    }

    public function getCeldasAsignadasProperty(): int
    {
        return $this->horariosGuardados
            ->keys()
            ->merge($this->talleresGuardados->keys())
            ->unique()
            ->count();
    }

    public function getAvanceHorarioProperty(): int
    {
        if ($this->totalCeldas <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->celdasAsignadas / $this->totalCeldas) * 100));
    }

    public function getResumenDocentesHorarioProperty(): \Illuminate\Support\Collection
    {
        $registros = $this->registrosCombinadosHorario();

        if (!$this->filtrosCompletos() || $registros->isEmpty()) {
            return collect();
        }

        return $registros
            ->groupBy('profesor')
            ->map(function ($items, $profesor) {
                $materias = $items
                    ->groupBy(fn($item) => ($item['taller_conjunto'] ? 'taller:' : 'materia:') . $item['materia'])
                    ->map(function ($materiasItems) {
                        $primero = $materiasItems->first();

                        return [
                            'materia' => $primero['materia'],
                            'clave' => $primero['clave'] ?? null,
                            'extra' => (bool) ($primero['extra'] ?? false),
                            'receso' => (bool) ($primero['receso'] ?? false),
                            'taller_conjunto' => (bool) ($primero['taller_conjunto'] ?? false),
                            'grupos' => $primero['grupos'] ?? null,
                            'modulos' => $materiasItems->count(),
                            'minutos' => $materiasItems->sum('minutos'),
                            'horarios' => $materiasItems
                                ->sortBy([
                                    ['dia_id', 'asc'],
                                    ['hora_inicio', 'asc'],
                                ])
                                ->map(function ($item) {
                                    return [
                                        'dia' => $item['dia'],
                                        'hora' => $item['hora_texto'],
                                    ];
                                })
                                ->values()
                                ->toArray(),
                        ];
                    })
                    ->values();

                $totalMinutos = $items->sum('minutos');

                return [
                    'profesor' => $profesor,
                    'sin_profesor' => $profesor === 'Sin profesor asignado',
                    'materias' => $materias,
                    'total_materias' => $materias->count(),
                    'total_modulos' => $items->count(),
                    'total_minutos' => $totalMinutos,
                    'total_horas_texto' => $this->formatearMinutosHorario($totalMinutos),
                    'dias' => $items
                        ->pluck('dia')
                        ->unique()
                        ->values()
                        ->implode(', '),
                    'estilo' => $this->obtenerEstiloProfesor($profesor),
                ];
            })
            ->sortBy([
                ['sin_profesor', 'asc'],
                ['profesor', 'asc'],
            ])
            ->values();
    }

    public function getTotalDocentesHorarioProperty(): int
    {
        return $this->resumenDocentesHorario
            ->filter(fn($docente) => !$docente['sin_profesor'])
            ->count();
    }

    public function getTotalMateriasHorarioProperty(): int
    {
        return $this->resumenDocentesHorario
            ->flatMap(fn($docente) => $docente['materias'])
            ->pluck('materia')
            ->unique()
            ->count();
    }

    public function getTotalHorasHorarioTextoProperty(): string
    {
        $minutos = $this->resumenDocentesHorario
            ->sum('total_minutos');

        return $this->formatearMinutosHorario($minutos);
    }

    public function getTotalSinProfesorHorarioProperty(): int
    {
        return $this->resumenDocentesHorario
            ->where('sin_profesor', true)
            ->sum('total_modulos');
    }

    public function formatearMinutosHorario(int|float $minutos): string
    {
        $minutos = (int) $minutos;

        if ($minutos <= 0) {
            return '0 h';
        }

        $horas = intdiv($minutos, 60);
        $restantes = $minutos % 60;

        if ($horas > 0 && $restantes > 0) {
            return $horas . ' h ' . $restantes . ' min';
        }

        if ($horas > 0) {
            return $horas . ' h';
        }

        return $restantes . ' min';
    }

    public function getGraficasHorarioProperty(): array
    {
        $registros = $this->registrosCombinadosHorario();

        if (!$this->filtrosCompletos() || $registros->isEmpty()) {
            return [
                'hay_datos' => false,
                'docentes' => ['labels' => [], 'series' => []],
                'materias' => ['labels' => [], 'series' => []],
                'dias' => ['labels' => [], 'series' => []],
                'global' => [
                    'avance' => 0,
                    'total_celdas' => $this->totalCeldas,
                    'celdas_asignadas' => 0,
                    'celdas_pendientes' => $this->totalCeldas,
                    'sin_profesor' => 0,
                    'docentes' => 0,
                    'materias' => 0,
                ],
            ];
        }

        $docentes = $registros
            ->groupBy('profesor')
            ->map(fn($items, $profesor) => [
                'profesor' => $this->recortarTextoHorario($profesor, 22),
                'modulos' => $items->count(),
            ])
            ->sortByDesc('modulos')
            ->values();

        $materias = $registros
            ->groupBy(fn($item) => ($item['taller_conjunto'] ? 'Taller: ' : '') . $item['materia'])
            ->map(fn($items, $materia) => [
                'materia' => $this->recortarTextoHorario($materia, 20),
                'modulos' => $items->count(),
            ])
            ->sortByDesc('modulos')
            ->values();

        $dias = $registros
            ->groupBy('dia')
            ->map(fn($items, $dia) => [
                'dia' => $dia,
                'modulos' => $items->count(),
            ])
            ->values();

        $totalCeldas = $this->totalCeldas;
        $celdasAsignadas = $this->celdasAsignadas;
        $celdasPendientes = max(0, $totalCeldas - $celdasAsignadas);

        return [
            'hay_datos' => true,
            'docentes' => [
                'labels' => $docentes->pluck('profesor')->toArray(),
                'series' => $docentes->pluck('modulos')->toArray(),
            ],
            'materias' => [
                'labels' => $materias->pluck('materia')->toArray(),
                'series' => $materias->pluck('modulos')->toArray(),
            ],
            'dias' => [
                'labels' => $dias->pluck('dia')->toArray(),
                'series' => $dias->pluck('modulos')->toArray(),
            ],
            'global' => [
                'avance' => $this->avanceHorario,
                'total_celdas' => $totalCeldas,
                'celdas_asignadas' => $celdasAsignadas,
                'celdas_pendientes' => $celdasPendientes,
                'sin_profesor' => $registros->where('sin_profesor', true)->count(),
                'docentes' => $registros->where('sin_profesor', false)->pluck('profesor')->unique()->count(),
                'materias' => $registros->map(fn($item) => ($item['taller_conjunto'] ? 'taller:' : 'materia:') . $item['materia'])->unique()->count(),
            ],
        ];
    }

    protected function registrosCombinadosHorario(): Collection
    {
        $normales = $this->horariosGuardados->map(function ($horario) {
            $asignacion = $horario->asignacionMateria;
            $materia = $asignacion?->materia;
            $profesor = $asignacion?->profesor;
            $dia = $this->dias->firstWhere('id', $horario->dia_id);
            $hora = $this->horas->firstWhere('id', $horario->hora_id);

            return $this->crearRegistroResumenHorario(
                profesor: $profesor,
                materia: $materia?->materia ?? 'Sin materia',
                clave: $materia?->clave,
                extra: (bool) ($materia?->extra ?? false),
                receso: (bool) ($materia?->receso ?? false),
                tallerConjunto: false,
                grupos: null,
                dia: $dia,
                hora: $hora,
            );
        });

        $talleres = $this->talleresGuardados
            ->flatten(1)
            ->unique('taller_sesion_id')
            ->map(function ($horario) {
                $sesion = $horario->tallerSesion;
                $grupos = $sesion?->grupos
                    ?->map(fn($grupo) => trim(($grupo->grado?->nombre ?? '') . ' ' . ($grupo->asignacionGrupo?->nombre ?? '')))
                    ->filter()
                    ->implode(', ');

                $dia = $this->dias->firstWhere('id', $horario->dia_id);
                $hora = $this->horas->firstWhere('id', $horario->hora_id);

                return $this->crearRegistroResumenHorario(
                    profesor: $sesion?->profesor,
                    materia: $sesion?->taller?->nombre ?? 'Taller conjunto',
                    clave: $sesion?->taller?->clave,
                    extra: false,
                    receso: false,
                    tallerConjunto: true,
                    grupos: $grupos ?: null,
                    dia: $dia,
                    hora: $hora,
                );
            });

        return $normales->concat($talleres)->values();
    }

    protected function crearRegistroResumenHorario(
        $profesor,
        string $materia,
        ?string $clave,
        bool $extra,
        bool $receso,
        bool $tallerConjunto,
        ?string $grupos,
        $dia,
        $hora,
    ): array {
        $nombreProfesor = $profesor
            ? trim(
                ($profesor->nombre ?? '') . ' ' .
                    ($profesor->apellido_paterno ?? '') . ' ' .
                    ($profesor->apellido_materno ?? '')
            )
            : 'Sin profesor asignado';

        $minutos = 0;
        $horaTexto = 'Sin hora';

        if ($hora?->hora_inicio && $hora?->hora_fin) {
            $inicio = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio);
            $fin = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin);
            $minutos = $inicio->diffInMinutes($fin);
            $horaTexto = $inicio->format('h:i A') . ' - ' . $fin->format('h:i A');
        }

        return [
            'profesor_id' => $profesor?->id,
            'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
            'materia' => $materia,
            'clave' => $clave,
            'extra' => $extra,
            'receso' => $receso,
            'taller_conjunto' => $tallerConjunto,
            'grupos' => $grupos,
            'dia' => $dia?->dia ?? 'Sin día',
            'dia_id' => $dia?->id,
            'hora_inicio' => $hora?->hora_inicio,
            'hora_fin' => $hora?->hora_fin,
            'hora_texto' => $horaTexto,
            'minutos' => $minutos,
            'sin_profesor' => !$profesor,
        ];
    }

    protected function recortarTextoHorario(string $texto, int $limite): string
    {
        return mb_strlen($texto) > $limite
            ? mb_substr($texto, 0, $limite) . '...'
            : $texto;
    }

    public function getDiagnosticoHorarioProperty(): array
    {
        if (!$this->filtrosCompletos()) {
            return [
                'hay_datos' => false,
                'estado' => 'sin_filtros',
                'titulo' => 'Selecciona los filtros del horario',
                'descripcion' => 'El diagnóstico se mostrará cuando selecciones generación, grado, grupo y semestre si aplica.',
                'color' => 'slate',
                'porcentaje_salud' => 0,
                'tarjetas' => [],
                'alertas' => collect(),
                'materias_pendientes' => collect(),
                'distribucion_materias' => collect(),
                'distribucion_dias' => collect(),
                'docentes_carga' => collect(),
                'dia_mayor_carga' => null,
                'dia_menor_carga' => null,
                'avance' => 0,
            ];
        }

        $totalCeldas = (int) $this->totalCeldas;
        $celdasAsignadas = (int) $this->celdasAsignadas;
        $celdasPendientes = max(0, $totalCeldas - $celdasAsignadas);
        $avance = (int) $this->avanceHorario;

        $horarios = $this->horariosGuardados;

        $materiasUsadasIds = $horarios
            ->pluck('asignacion_materia_id')
            ->filter()
            ->unique()
            ->values();

        $materiasPendientes = $this->materiasDisponibles
            ->filter(fn($asignacion) => !$materiasUsadasIds->contains($asignacion->id))
            ->map(function ($asignacion) {
                $materia = $asignacion->materia;
                $profesor = $asignacion->profesor;

                $nombreProfesor = $profesor
                    ? trim(
                        ($profesor->nombre ?? '') . ' ' .
                            ($profesor->apellido_paterno ?? '') . ' ' .
                            ($profesor->apellido_materno ?? '')
                    )
                    : 'Sin profesor asignado';

                return [
                    'id' => $asignacion->id,
                    'materia' => $materia?->materia ?? 'Sin materia',
                    'clave' => $materia?->clave,
                    'extra' => (bool) ($materia?->extra ?? false),
                    'receso' => (bool) ($materia?->receso ?? false),
                    'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                ];
            })
            ->values();

        $registros = $this->registrosCombinadosHorario();

        $sinProfesor = $registros->where('sin_profesor', true)->count();

        $distribucionDias = $this->dias
            ->map(function ($dia) use ($registros) {
                $items = $registros->where('dia_id', $dia->id);

                return [
                    'dia' => $dia->dia,
                    'modulos' => $items->count(),
                    'minutos' => $items->sum('minutos'),
                ];
            })
            ->values();

        $maxDia = $distribucionDias->sortByDesc('modulos')->first();
        $minDia = $distribucionDias->sortBy('modulos')->first();

        $distribucionMaterias = $registros
            ->groupBy('materia')
            ->map(function ($items, $materia) {
                return [
                    'materia' => $materia,
                    'modulos' => $items->count(),
                    'dias' => $items->pluck('dia')->unique()->values()->implode(', '),
                    'minutos' => $items->sum('minutos'),
                    'extra' => (bool) ($items->first()['extra'] ?? false),
                    'receso' => (bool) ($items->first()['receso'] ?? false),
                ];
            })
            ->sortByDesc('modulos')
            ->values();

        $docentesCarga = $registros
            ->groupBy('profesor')
            ->map(function ($items, $profesor) {
                $modulos = $items->count();
                $minutos = $items->sum('minutos');

                return [
                    'profesor' => $profesor,
                    'sin_profesor' => $profesor === 'Sin profesor asignado',
                    'modulos' => $modulos,
                    'minutos' => $minutos,
                    'horas' => $this->formatearMinutosHorario($minutos),
                    'estado' => $this->estadoCargaDocenteHorario($modulos),
                    'clase' => $this->claseCargaDocenteHorario($modulos),
                ];
            })
            ->sortByDesc('modulos')
            ->values();

        $docentesAltaCarga = $docentesCarga
            ->filter(fn($docente) => !$docente['sin_profesor'] && $docente['modulos'] >= 8)
            ->values();

        $puntosSalud = 100;

        if ($celdasPendientes > 0) {
            $puntosSalud -= 25;
        }

        if ($sinProfesor > 0) {
            $puntosSalud -= 25;
        }

        if ($materiasPendientes->count() > 0) {
            $puntosSalud -= 20;
        }

        if ($docentesAltaCarga->count() > 0) {
            $puntosSalud -= 10;
        }

        $puntosSalud = max(0, min(100, $puntosSalud));

        $estado = 'correcto';
        $titulo = 'Horario listo para revisión';
        $descripcion = 'El horario tiene buena estructura y no presenta observaciones críticas.';
        $color = 'emerald';

        if ($puntosSalud < 70) {
            $estado = 'advertencia';
            $titulo = 'Horario con observaciones importantes';
            $descripcion = 'Revisa los espacios pendientes, materias sin colocar o docentes sin asignar.';
            $color = 'amber';
        }

        if ($puntosSalud < 45) {
            $estado = 'critico';
            $titulo = 'Horario incompleto';
            $descripcion = 'El horario requiere ajustes antes de descargarse o compartirse.';
            $color = 'rose';
        }

        $alertas = collect();

        if ($celdasPendientes > 0) {
            $alertas->push([
                'tipo' => 'warning',
                'titulo' => 'Celdas pendientes',
                'mensaje' => 'Hay ' . $celdasPendientes . ' espacio(s) del horario sin materia asignada.',
            ]);
        }

        if ($sinProfesor > 0) {
            $alertas->push([
                'tipo' => 'danger',
                'titulo' => 'Materias sin profesor',
                'mensaje' => 'Hay ' . $sinProfesor . ' módulo(s) con materia asignada, pero sin profesor.',
            ]);
        }

        if ($materiasPendientes->count() > 0) {
            $alertas->push([
                'tipo' => 'warning',
                'titulo' => 'Materias disponibles sin colocar',
                'mensaje' => 'Hay ' . $materiasPendientes->count() . ' materia(s) asignadas al grupo que todavía no aparecen en el horario.',
            ]);
        }

        if ($docentesAltaCarga->count() > 0) {
            $alertas->push([
                'tipo' => 'info',
                'titulo' => 'Carga alta de docentes',
                'mensaje' => 'Hay ' . $docentesAltaCarga->count() . ' docente(s) con una carga considerable en este grupo.',
            ]);
        }

        if ($maxDia && ($maxDia['modulos'] ?? 0) > 0) {
            $alertas->push([
                'tipo' => 'success',
                'titulo' => 'Día con mayor carga',
                'mensaje' => $maxDia['dia'] . ' concentra la mayor carga con ' . $maxDia['modulos'] . ' módulo(s).',
            ]);
        }

        return [
            'hay_datos' => true,
            'estado' => $estado,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'color' => $color,
            'porcentaje_salud' => $puntosSalud,
            'tarjetas' => [
                [
                    'titulo' => 'Salud del horario',
                    'valor' => $puntosSalud . '%',
                    'detalle' => 'Calidad general',
                    'color' => $color,
                ],
                [
                    'titulo' => 'Pendientes',
                    'valor' => $celdasPendientes,
                    'detalle' => 'Celdas sin asignar',
                    'color' => $celdasPendientes > 0 ? 'amber' : 'emerald',
                ],
                [
                    'titulo' => 'Sin profesor',
                    'valor' => $sinProfesor,
                    'detalle' => 'Módulos incompletos',
                    'color' => $sinProfesor > 0 ? 'rose' : 'emerald',
                ],
                [
                    'titulo' => 'Materias sin colocar',
                    'valor' => $materiasPendientes->count(),
                    'detalle' => 'Disponibles no usadas',
                    'color' => $materiasPendientes->count() > 0 ? 'amber' : 'emerald',
                ],
            ],
            'alertas' => $alertas->values(),
            'materias_pendientes' => $materiasPendientes,
            'distribucion_materias' => $distribucionMaterias,
            'distribucion_dias' => $distribucionDias,
            'docentes_carga' => $docentesCarga,
            'dia_mayor_carga' => $maxDia,
            'dia_menor_carga' => $minDia,
            'avance' => $avance,
        ];
    }

    public function updatedTipoAnalisisHorarioIa(): void
    {
        $this->analisisHorarioIa = null;
    }

    public function generarAnalisisHorarioIa(GroqHorarioService $groq): void
    {
        if (!$this->filtrosCompletos()) {
            $this->dispatch('swal', [
                'title' => 'Completa los filtros del horario.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        try {
            $this->analisisHorarioIa = $groq->analizarHorario(
                datos: $this->construirResumenAnonimoHorarioIa(),
                tipo: $this->tipoAnalisisHorarioIa
            );
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('swal', [
                'title' => 'No fue posible generar las sugerencias.',
                'text' => $exception->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function limpiarAnalisisHorarioIa(): void
    {
        $this->analisisHorarioIa = null;
    }

    protected function invalidarAnalisisHorarioIa(): void
    {
        $this->analisisHorarioIa = null;
    }

    protected function construirResumenAnonimoHorarioIa(): array
    {
        $diagnostico = $this->diagnosticoHorario;
        $grado = $this->grados->firstWhere('id', $this->grado_id);
        $grupo = $this->grupos->firstWhere('id', $this->grupo_id) ?? $this->obtenerGrupoSeleccionado();
        $generacion = $this->generaciones->firstWhere('id', $this->generacion_id);
        $semestre = $this->semestres->firstWhere('id', $this->semestre_id);
        $ciclo = $this->ciclosEscolares->firstWhere('id', $this->ciclo_escolar_id);

        return [
            'contexto' => [
                'nivel' => $this->nivel?->nombre ?? 'No definido',
                'grado' => $grado?->nombre ?? 'No definido',
                'grupo' => $this->textoGrupo($grupo),
                'generacion' => $generacion
                    ? $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso
                    : 'No definida',
                'semestre' => $semestre?->numero ? $semestre->numero . '°' : null,
                'ciclo_escolar' => $ciclo
                    ? $ciclo->inicio_anio . ' - ' . $ciclo->fin_anio
                    : 'No definido',
            ],
            'estructura' => [
                'total_celdas' => (int) $this->totalCeldas,
                'celdas_asignadas' => (int) $this->celdasAsignadas,
                'celdas_pendientes' => max(0, (int) $this->totalCeldas - (int) $this->celdasAsignadas),
                'avance_porcentaje' => (int) $this->avanceHorario,
                'salud_porcentaje' => (int) ($diagnostico['porcentaje_salud'] ?? 0),
                'estado' => (string) ($diagnostico['estado'] ?? 'sin_datos'),
            ],
            'alertas' => collect($diagnostico['alertas'] ?? [])
                ->map(fn(array $alerta) => [
                    'tipo' => $alerta['tipo'] ?? 'info',
                    'titulo' => $alerta['titulo'] ?? 'Observación',
                    'mensaje' => $alerta['mensaje'] ?? '',
                ])
                ->values()
                ->all(),
            'materias_pendientes' => collect($diagnostico['materias_pendientes'] ?? [])
                ->map(fn(array $materia) => [
                    'materia' => $materia['materia'] ?? 'Sin materia',
                    'sin_profesor' => ($materia['profesor'] ?? '') === 'Sin profesor asignado',
                    'extra' => (bool) ($materia['extra'] ?? false),
                    'receso' => (bool) ($materia['receso'] ?? false),
                ])
                ->values()
                ->all(),
            'distribucion_por_dia' => collect($diagnostico['distribucion_dias'] ?? [])
                ->map(fn(array $dia) => [
                    'dia' => $dia['dia'] ?? 'N/D',
                    'modulos' => (int) ($dia['modulos'] ?? 0),
                    'minutos' => (int) ($dia['minutos'] ?? 0),
                ])
                ->values()
                ->all(),
            'distribucion_por_materia' => collect($diagnostico['distribucion_materias'] ?? [])
                ->map(fn(array $materia) => [
                    'materia' => $materia['materia'] ?? 'Sin materia',
                    'modulos' => (int) ($materia['modulos'] ?? 0),
                    'dias' => $materia['dias'] ?? '',
                    'extra' => (bool) ($materia['extra'] ?? false),
                    'receso' => (bool) ($materia['receso'] ?? false),
                ])
                ->values()
                ->all(),
            'carga_docente_anonima' => collect($diagnostico['docentes_carga'] ?? [])
                ->values()
                ->map(fn(array $docente, int $indice) => [
                    'referencia' => ($docente['sin_profesor'] ?? false)
                        ? 'Sin profesor asignado'
                        : 'Docente ' . ($indice + 1),
                    'modulos' => (int) ($docente['modulos'] ?? 0),
                    'minutos' => (int) ($docente['minutos'] ?? 0),
                    'estado' => $docente['estado'] ?? 'N/D',
                ])
                ->all(),
            'reglas' => [
                'Los cálculos y la disponibilidad son determinados por Laravel.',
                'La IA solo redacta explicaciones y sugerencias generales.',
                'No se enviaron nombres de docentes ni datos personales.',
            ],
        ];
    }

    public function estadoCargaDocenteHorario(int $modulos): string
    {
        if ($modulos >= 10) {
            return 'Muy alta';
        }

        if ($modulos >= 8) {
            return 'Alta';
        }

        if ($modulos >= 4) {
            return 'Normal';
        }

        return 'Ligera';
    }

    public function claseCargaDocenteHorario(int $modulos): string
    {
        if ($modulos >= 10) {
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300';
        }

        if ($modulos >= 8) {
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300';
        }

        if ($modulos >= 4) {
            return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300';
        }

        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300';
    }

    public function claseTarjetaDiagnosticoHorario(string $color): string
    {
        return match ($color) {
            'emerald' => 'border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300',
            'amber' => 'border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300',
            'rose' => 'border-rose-100 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300',
            'sky' => 'border-sky-100 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300',
            default => 'border-slate-100 bg-slate-50 text-slate-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-slate-300',
        };
    }

    public function claseAlertaDiagnosticoHorario(string $tipo): string
    {
        return match ($tipo) {
            'danger' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200',
            default => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200',
        };
    }

}
