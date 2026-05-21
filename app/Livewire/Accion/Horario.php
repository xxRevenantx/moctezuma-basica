<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Dia;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario as HorarioModel;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

use App\Exports\HorarioExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class Horario extends Component
{
    public string $mensajeActualizacionHorario = '';
    public string $slug_nivel;

    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $horas;
    public Collection $dias;
    public Collection $semestres;
    public Collection $materiasDisponibles;
    public Collection $horariosGuardados;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;

    public bool $esBachillerato = false;

    public array $seleccionesHorario = [];

    public bool $mostrarModalTraslapeProfesor = false;

    public array $pendienteHorario = [
        'hora_id' => null,
        'dia_id' => null,
        'asignacion_materia_id' => null,
        'clave_celda' => null,
    ];

    public array $conflictosProfesor = [];

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->esBachillerato = (int) $this->nivel->id === 4;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->horas = collect();
        $this->dias = collect();
        $this->semestres = collect();
        $this->materiasDisponibles = collect();
        $this->horariosGuardados = collect();

        $this->cargarGeneraciones();
        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;

        if ($this->esBachillerato) {
            $this->semestre_id = null;
        }

        $this->resetEstadoTraslapeProfesor();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGrupoId(): void
    {
        $this->resetEstadoTraslapeProfesor();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    #[On('refrescarHorasDias')]
    public function refrescarHorasDias(): void
    {
        $this->mensajeActualizacionHorario = 'Actualizando horarios...';

        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();

        $this->mensajeActualizacionHorario = 'Horario actualizado correctamente.';
    }

    public function updatedSeleccionesHorario($value, $key): void
    {
        if (!$this->filtrosCompletos()) {
            return;
        }

        if (!str_contains((string) $key, '-')) {
            return;
        }

        [$horaId, $diaId] = array_map('intval', explode('-', (string) $key));

        $this->procesarCambioHorario(
            horaId: $horaId,
            diaId: $diaId,
            asignacionMateriaId: filled($value) ? (int) $value : null,
            claveCelda: (string) $key
        );
    }

    protected function procesarCambioHorario(
        int $horaId,
        int $diaId,
        ?int $asignacionMateriaId,
        string $claveCelda,
        bool $forzar = false
    ): void {
        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('hora_id', $horaId)
            ->where('dia_id', $diaId);

        if ($this->esBachillerato) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $horarioExistente = $consulta->first();

        if (blank($asignacionMateriaId)) {
            if ($horarioExistente) {
                $horarioExistente->delete();
            }

            $this->resetEstadoTraslapeProfesor();
            $this->cargarHorariosGuardados();
            $this->sincronizarSeleccionesHorario();

            return;
        }

        $asignacion = AsignacionMateria::query()
            ->with([
                'materia',
                'profesor',
            ])
            ->where('id', $asignacionMateriaId)
            ->where('grupo_id', $this->grupo_id)
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $this->grado_id);

                if ($this->esBachillerato) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereNull('semestre_id');
                }
            })
            ->first();

        if (!$asignacion) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        if (blank($asignacion->profesor_id)) {
            $this->guardarHorarioDirecto(
                horaId: $horaId,
                diaId: $diaId,
                asignacionMateriaId: $asignacionMateriaId,
                horarioExistente: $horarioExistente
            );

            return;
        }

        $horaActual = Hora::query()->find($horaId);

        if (!$horaActual) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        $conflictos = $this->buscarConflictosProfesor(
            profesorId: (int) $asignacion->profesor_id,
            diaId: $diaId,
            horaInicio: $horaActual->hora_inicio,
            horaFin: $horaActual->hora_fin,
            horarioActualId: $horarioExistente?->id
        );

        if (!$forzar && count($conflictos) > 0) {
            $this->pendienteHorario = [
                'hora_id' => $horaId,
                'dia_id' => $diaId,
                'asignacion_materia_id' => $asignacionMateriaId,
                'clave_celda' => $claveCelda,
            ];

            $this->conflictosProfesor = $conflictos;
            $this->mostrarModalTraslapeProfesor = true;

            $this->restaurarCeldaDesdeHorarioGuardado($claveCelda);

            return;
        }

        $this->guardarHorarioDirecto(
            horaId: $horaId,
            diaId: $diaId,
            asignacionMateriaId: $asignacionMateriaId,
            horarioExistente: $horarioExistente
        );
    }

    protected function guardarHorarioDirecto(
        int $horaId,
        int $diaId,
        int $asignacionMateriaId,
        ?HorarioModel $horarioExistente = null
    ): void {
        $datosBusqueda = [
            'nivel_id' => $this->nivel->id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'grupo_id' => $this->grupo_id,
            'hora_id' => $horaId,
            'dia_id' => $diaId,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
        ];

        if ($horarioExistente) {
            $horarioExistente->update([
                'asignacion_materia_id' => $asignacionMateriaId,
                'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            ]);
        } else {
            HorarioModel::query()->create([
                ...$datosBusqueda,
                'asignacion_materia_id' => $asignacionMateriaId,
            ]);
        }

        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    protected function buscarConflictosProfesor(
        int $profesorId,
        int $diaId,
        string $horaInicio,
        string $horaFin,
        ?int $horarioActualId = null
    ): array {
        $conflictos = HorarioModel::query()
            ->with([
                'hora',
                'nivel',
                'grado',
                'grupo.asignacionGrupo',
                'dia',
                'semestre',
                'asignacionMateria.materia',
                'asignacionMateria.profesor',
            ])
            ->where('dia_id', $diaId)
            ->when($horarioActualId, function ($query) use ($horarioActualId) {
                $query->where('id', '!=', $horarioActualId);
            })
            ->whereHas('asignacionMateria', function ($query) use ($profesorId) {
                $query->where('profesor_id', $profesorId);
            })
            ->whereHas('hora', function ($query) use ($horaInicio, $horaFin) {
                $query->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->get();

        return $conflictos->map(function ($item) {
            $profesor = $item->asignacionMateria?->profesor;

            $nombreProfesor = trim(
                ($profesor->nombre ?? '') . ' ' .
                    ($profesor->apellido_paterno ?? '') . ' ' .
                    ($profesor->apellido_materno ?? '')
            );

            return [
                'id' => $item->id,
                'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                'nivel' => $item->nivel?->nombre ?? 'N/D',
                'grado' => $item->grado?->nombre ?? 'N/D',
                'grupo' => $this->textoGrupo($item->grupo, 'N/D'),
                'dia' => $item->dia?->dia ?? 'N/D',
                'hora_inicio' => $item->hora?->hora_inicio,
                'hora_fin' => $item->hora?->hora_fin,
                'semestre' => $item->semestre?->numero ? $item->semestre->numero . '° semestre' : null,
                'materia' => $item->asignacionMateria?->materia?->materia ?? 'N/D',
            ];
        })->toArray();
    }

    public function confirmarGuardarConTraslape(): void
    {
        if (
            blank($this->pendienteHorario['hora_id']) ||
            blank($this->pendienteHorario['dia_id']) ||
            blank($this->pendienteHorario['asignacion_materia_id']) ||
            blank($this->pendienteHorario['clave_celda'])
        ) {
            $this->resetEstadoTraslapeProfesor();
            $this->sincronizarSeleccionesHorario();

            return;
        }

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('hora_id', (int) $this->pendienteHorario['hora_id'])
            ->where('dia_id', (int) $this->pendienteHorario['dia_id']);

        if ($this->esBachillerato) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $horarioExistente = $consulta->first();

        $this->guardarHorarioDirecto(
            horaId: (int) $this->pendienteHorario['hora_id'],
            diaId: (int) $this->pendienteHorario['dia_id'],
            asignacionMateriaId: (int) $this->pendienteHorario['asignacion_materia_id'],
            horarioExistente: $horarioExistente
        );
    }

    public function cancelarGuardarConTraslape(): void
    {
        $claveCelda = $this->pendienteHorario['clave_celda'] ?? null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();

        if ($claveCelda) {
            $this->restaurarCeldaDesdeHorarioGuardado($claveCelda);
        }
    }

    protected function resetEstadoTraslapeProfesor(): void
    {
        $this->mostrarModalTraslapeProfesor = false;

        $this->pendienteHorario = [
            'hora_id' => null,
            'dia_id' => null,
            'asignacion_materia_id' => null,
            'clave_celda' => null,
        ];

        $this->conflictosProfesor = [];
    }

    protected function sincronizarSeleccionesHorario(): void
    {
        $selecciones = [];

        foreach ($this->horas as $hora) {
            foreach ($this->dias as $dia) {
                $clave = $hora->id . '-' . $dia->id;
                $horario = $this->horariosGuardados->get($clave);

                $selecciones[$clave] = $horario?->asignacion_materia_id;
            }
        }

        $this->seleccionesHorario = $selecciones;
    }

    protected function restaurarCeldaDesdeHorarioGuardado(string $claveCelda): void
    {
        $horario = $this->horariosGuardados->get($claveCelda);

        $this->seleccionesHorario[$claveCelda] = $horario?->asignacion_materia_id;
    }

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

    public function limpiarFiltros(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->semestre_id = null;
        $this->grupos = collect();
        $this->semestres = collect();
        $this->materiasDisponibles = collect();
        $this->horariosGuardados = collect();
        $this->seleccionesHorario = [];
        $this->resetEstadoTraslapeProfesor();
    }

    public function getTotalCeldasProperty(): int
    {
        return $this->horas->count() * $this->dias->count();
    }

    public function getCeldasAsignadasProperty(): int
    {
        return $this->horariosGuardados->count();
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
        if (!$this->filtrosCompletos() || $this->horariosGuardados->isEmpty()) {
            return collect();
        }

        return $this->horariosGuardados
            ->map(function ($horario) {
                $asignacion = $horario->asignacionMateria;
                $materia = $asignacion?->materia;
                $profesor = $asignacion?->profesor;
                $hora = $this->horas->firstWhere('id', $horario->hora_id);
                $dia = $this->dias->firstWhere('id', $horario->dia_id);

                $nombreProfesor = $profesor
                    ? trim(
                        ($profesor->nombre ?? '') . ' ' .
                            ($profesor->apellido_paterno ?? '') . ' ' .
                            ($profesor->apellido_materno ?? '')
                    )
                    : 'Sin profesor asignado';

                $minutos = 0;

                if ($hora?->hora_inicio && $hora?->hora_fin) {
                    $inicio = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio);
                    $fin = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin);
                    $minutos = $inicio->diffInMinutes($fin);
                }

                return [
                    'profesor_id' => $profesor?->id,
                    'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                    'materia_id' => $materia?->id,
                    'materia' => $materia?->materia ?? 'Sin materia',
                    'clave' => $materia?->clave,
                    'extra' => (bool) ($materia?->extra ?? false),
                    'receso' => (bool) ($materia?->receso ?? false),
                    'dia' => $dia?->dia ?? 'Sin día',
                    'dia_id' => $dia?->id,
                    'hora_inicio' => $hora?->hora_inicio,
                    'hora_fin' => $hora?->hora_fin,
                    'hora_texto' => $hora
                        ? \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio)->format('h:i A') .
                        ' - ' .
                        \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin)->format('h:i A')
                        : 'Sin hora',
                    'minutos' => $minutos,
                ];
            })
            ->groupBy('profesor')
            ->map(function ($items, $profesor) {
                $materias = $items
                    ->groupBy('materia')
                    ->map(function ($materiasItems, $nombreMateria) {
                        return [
                            'materia' => $nombreMateria,
                            'clave' => $materiasItems->first()['clave'] ?? null,
                            'extra' => (bool) ($materiasItems->first()['extra'] ?? false),
                            'receso' => (bool) ($materiasItems->first()['receso'] ?? false),
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
        if (!$this->filtrosCompletos() || $this->horariosGuardados->isEmpty()) {
            return [
                'hay_datos' => false,
                'docentes' => [
                    'labels' => [],
                    'series' => [],
                ],
                'materias' => [
                    'labels' => [],
                    'series' => [],
                ],
                'dias' => [
                    'labels' => [],
                    'series' => [],
                ],
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

        $registros = $this->horariosGuardados
            ->map(function ($horario) {
                $asignacion = $horario->asignacionMateria;
                $materia = $asignacion?->materia;
                $profesor = $asignacion?->profesor;
                $dia = $this->dias->firstWhere('id', $horario->dia_id);
                $hora = $this->horas->firstWhere('id', $horario->hora_id);

                $nombreProfesor = $profesor
                    ? trim(
                        ($profesor->nombre ?? '') . ' ' .
                            ($profesor->apellido_paterno ?? '') . ' ' .
                            ($profesor->apellido_materno ?? '')
                    )
                    : 'Sin profesor asignado';

                $minutos = 0;

                if ($hora?->hora_inicio && $hora?->hora_fin) {
                    $inicio = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio);
                    $fin = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin);

                    $minutos = $inicio->diffInMinutes($fin);
                }

                return [
                    'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                    'materia' => $materia?->materia ?? 'Sin materia',
                    'dia' => $dia?->dia ?? 'Sin día',
                    'minutos' => $minutos,
                    'sin_profesor' => !$profesor,
                ];
            })
            ->values();

        $docentes = $registros
            ->groupBy('profesor')
            ->map(fn($items, $profesor) => [
                'profesor' => $this->recortarTextoHorario($profesor, 22),
                'modulos' => $items->count(),
            ])
            ->sortByDesc('modulos')
            ->values();

        $materias = $registros
            ->groupBy('materia')
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
        $avance = $this->avanceHorario;

        return [
            'hay_datos' => $registros->isNotEmpty(),
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
                'avance' => $avance,
                'total_celdas' => $totalCeldas,
                'celdas_asignadas' => $celdasAsignadas,
                'celdas_pendientes' => $celdasPendientes,
                'sin_profesor' => $registros->where('sin_profesor', true)->count(),
                'docentes' => $registros
                    ->where('sin_profesor', false)
                    ->pluck('profesor')
                    ->unique()
                    ->count(),
                'materias' => $registros
                    ->pluck('materia')
                    ->unique()
                    ->count(),
            ],
        ];
    }

    protected function recortarTextoHorario(string $texto, int $limite): string
    {
        return mb_strlen($texto) > $limite
            ? mb_substr($texto, 0, $limite) . '...'
            : $texto;
    }

    protected function consultaGruposBase(): Builder
    {
        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->where('grupos.nivel_id', $this->nivel->id);
    }

    protected function filtrosCompletos(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->grupo_id)
                && filled($this->semestre_id);
        }

        return filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id);
    }

    protected function filtrosMinimosParaMaterias(): bool
    {
        return $this->filtrosCompletos();
    }

    protected function obtenerGrupoSeleccionado(): ?Grupo
    {
        if (!$this->grupo_id) {
            return null;
        }

        return $this->consultaGruposBase()
            ->where('grupos.id', $this->grupo_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->where('grupos.generacion_id', $this->generacion_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('grupos.semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('grupos.semestre_id')
            )
            ->first();
    }

    protected function cargarGeneraciones(): void
    {
        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();
    }

    protected function cargarGrados(): void
    {
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    protected function cargarGrupos(): void
    {
        if (!$this->generacion_id || !$this->grado_id) {
            $this->grupos = collect();
            return;
        }

        if ($this->esBachillerato && !$this->semestre_id) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = $this->consultaGruposBase()
            ->where('grupos.generacion_id', $this->generacion_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('grupos.semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('grupos.semestre_id')
            )
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get();
    }

    protected function cargarHoras(): void
    {
        $this->horas = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();
    }

    protected function cargarDias(): void
    {
        $this->dias = Dia::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->get()
            ->unique('dia')
            ->values();
    }

    protected function cargarSemestres(): void
    {
        if (!$this->esBachillerato) {
            $this->semestres = collect();
            return;
        }

        if (!$this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get();
    }

    protected function cargarMateriasDisponibles(): void
    {
        if (!$this->filtrosMinimosParaMaterias()) {
            $this->materiasDisponibles = collect();
            return;
        }

        $this->materiasDisponibles = AsignacionMateria::query()
            ->with([
                'materia',
                'profesor',
            ])
            ->where('grupo_id', $this->grupo_id)
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $this->grado_id);

                if ($this->esBachillerato) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereNull('semestre_id');
                }
            })
            ->orderBy('asignacion_materias.orden')
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->orden ?? 0) <=> ($b->orden ?? 0),
                fn($a, $b) => ($a->materia?->orden ?? 0) <=> ($b->materia?->orden ?? 0),
                fn($a, $b) => strcmp($a->materia?->materia ?? '', $b->materia?->materia ?? ''),
            ])
            ->values();
    }

    protected function cargarHorariosGuardados(): void
    {
        if (!$this->filtrosCompletos()) {
            $this->horariosGuardados = collect();
            return;
        }

        $horarios = HorarioModel::query()
            ->with([
                'asignacionMateria.materia',
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->get();

        $this->horariosGuardados = $horarios->keyBy(function ($horario) {
            return $horario->hora_id . '-' . $horario->dia_id;
        });
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

        $registros = $horarios
            ->map(function ($horario) {
                $asignacion = $horario->asignacionMateria;
                $materia = $asignacion?->materia;
                $profesor = $asignacion?->profesor;
                $dia = $this->dias->firstWhere('id', $horario->dia_id);
                $hora = $this->horas->firstWhere('id', $horario->hora_id);

                $nombreProfesor = $profesor
                    ? trim(
                        ($profesor->nombre ?? '') . ' ' .
                            ($profesor->apellido_paterno ?? '') . ' ' .
                            ($profesor->apellido_materno ?? '')
                    )
                    : 'Sin profesor asignado';

                $minutos = 0;

                if ($hora?->hora_inicio && $hora?->hora_fin) {
                    $inicio = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio);
                    $fin = \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin);
                    $minutos = $inicio->diffInMinutes($fin);
                }

                return [
                    'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                    'sin_profesor' => !$profesor,
                    'materia' => $materia?->materia ?? 'Sin materia',
                    'clave' => $materia?->clave,
                    'extra' => (bool) ($materia?->extra ?? false),
                    'receso' => (bool) ($materia?->receso ?? false),
                    'dia' => $dia?->dia ?? 'Sin día',
                    'dia_id' => $dia?->id,
                    'hora_inicio' => $hora?->hora_inicio,
                    'hora_fin' => $hora?->hora_fin,
                    'minutos' => $minutos,
                ];
            })
            ->values();

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

    public function exportarHorario()
    {
        if (!$this->puedeDescargarHorario) {
            $this->dispatch('swal', [
                'title' => 'Selecciona todos los filtros antes de exportar el horario.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        $grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);

        $nombreNivel = mb_strtoupper($this->nivel?->nombre ?? $this->slug_nivel ?? 'NIVEL');

        $nombreGeneracion = Generacion::query()
            ->where('id', $this->generacion_id)
            ->select('anio_ingreso', 'anio_egreso')
            ->first();

        $textoGeneracion = $nombreGeneracion
            ? $nombreGeneracion->anio_ingreso . '_' . $nombreGeneracion->anio_egreso
            : 'SIN_GENERACION';

        $nombreGrado = Grado::query()
            ->where('id', $this->grado_id)
            ->value('nombre') ?? 'GRADO';

        $nombreGrupo = $this->textoGrupo($grupo);

        $textoSemestre = '';

        if ($this->esBachillerato) {
            $semestre = Semestre::query()
                ->where('id', $this->semestre_id)
                ->value('numero');

            $textoSemestre = '_SEMESTRE_' . Str::slug((string) ($semestre ?? $this->semestre_id), '_');
        }

        $nombreArchivo = 'HORARIO_' .
            Str::slug($nombreNivel, '_') .
            '_GENERACION_' . Str::slug($textoGeneracion, '_') .
            '_GRADO_' . Str::slug($nombreGrado, '_') .
            '_GRUPO_' . Str::slug($nombreGrupo, '_') .
            $textoSemestre .
            '.xlsx';

        return Excel::download(
            new HorarioExport(
                nivel_id: $this->nivel?->id ? (int) $this->nivel->id : null,
                grado_id: $this->grado_id ? (int) $this->grado_id : null,
                grupo_id: $this->grupo_id ? (int) $this->grupo_id : null,
                generacion_id: $this->generacion_id ? (int) $this->generacion_id : null,
                semestre_id: $this->semestre_id ? (int) $this->semestre_id : null,
                esBachillerato: $this->esBachillerato
            ),
            $nombreArchivo
        );
    }

    public function render()
    {
        return view('livewire.accion.horario');
    }
}
