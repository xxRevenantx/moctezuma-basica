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

trait GestionaConflictosHorario
{
    protected function buscarConflictosProfesor(
        int $profesorId,
        int $diaId,
        string $horaInicio,
        string $horaFin,
        ?int $horarioActualId = null
    ): array {
        $diaActual = Dia::query()->find($diaId);

        if (!$diaActual) {
            return [];
        }

        $diaIds = Dia::query()
            ->whereRaw('LOWER(dia) = ?', [mb_strtolower($diaActual->dia)])
            ->pluck('id');

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
                'profesorAsignado',
                'tallerSesion.taller',
                'tallerSesion.profesor',
                'tallerSesion.grupos.asignacionGrupo',
                'tallerSesion.grupos.grado',
            ])
            ->whereIn('dia_id', $diaIds)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->when($horarioActualId, function ($query) use ($horarioActualId) {
                $query->where('id', '!=', $horarioActualId);
            })
            ->where(function ($query) use ($profesorId) {
                $query->where(function ($legacy) use ($profesorId) {
                    $legacy->where('profesor_id', $profesorId)
                        ->whereNull('asignacion_materia_id')
                        ->whereNull('taller_sesion_id');
                })->orWhereHas('asignacionMateria', function ($subQuery) use ($profesorId) {
                    $subQuery->where('profesor_id', $profesorId)
                        ->configurables();
                })->orWhereHas('tallerSesion', function ($subQuery) use ($profesorId) {
                    $subQuery->where('profesor_id', $profesorId)
                        ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA);
                });
            })
            ->whereHas('hora', function ($query) use ($horaInicio, $horaFin) {
                $query->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->get()
            ->unique(fn($item) => $item->taller_sesion_id
                ? 'taller-' . $item->taller_sesion_id
                : 'horario-' . $item->id)
            ->values();

        return $conflictos->map(function ($item) {
            $profesor = $item->profesorActividad();

            $nombreProfesor = trim(
                ($profesor->nombre ?? '') . ' ' .
                    ($profesor->apellido_paterno ?? '') . ' ' .
                    ($profesor->apellido_materno ?? '')
            );

            $grupos = $item->esTallerConjunto()
                ? $item->tallerSesion?->grupos
                ?->map(fn($grupo) => trim(
                    ($grupo->grado?->nombre ?? '') . ' ' .
                        ($grupo->asignacionGrupo?->nombre ?? '')
                ))
                ->filter()
                ->implode(', ')
                : $this->textoGrupo($item->grupo, 'N/D');

            return [
                'id' => $item->id,
                'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                'nivel' => $item->nivel?->nombre ?? 'N/D',
                'grado' => $item->esTallerConjunto() ? 'Varios grados' : ($item->grado?->nombre ?? 'N/D'),
                'grupo' => $grupos ?: 'N/D',
                'dia' => $item->dia?->dia ?? 'N/D',
                'hora_inicio' => $item->hora?->hora_inicio,
                'hora_fin' => $item->hora?->hora_fin,
                'semestre' => $item->semestre?->numero ? $item->semestre->numero . '° semestre' : null,
                'materia' => $item->nombreActividad(),
            ];
        })->toArray();
    }

    protected function buscarAlternativasViables(
        int $profesorId,
        int $diaSolicitadoId,
        int $horaSolicitadaId,
        ?int $horarioActualId = null
    ): array {
        $diaSolicitado = $this->dias->firstWhere('id', $diaSolicitadoId);
        $horaSolicitada = $this->horas->firstWhere('id', $horaSolicitadaId);

        if (!$diaSolicitado || !$horaSolicitada || !$this->grupo_id || !$this->ciclo_escolar_id) {
            return [];
        }

        $ordenDiaSolicitado = (int) ($diaSolicitado->orden ?? 0);
        $ordenHoraSolicitada = (int) ($horaSolicitada->orden ?? 0);
        $alternativas = collect();

        foreach ($this->dias->sortBy('orden') as $dia) {
            foreach ($this->horas->sortBy('orden') as $hora) {
                if ((int) $dia->id === $diaSolicitadoId && (int) $hora->id === $horaSolicitadaId) {
                    continue;
                }

                if ($this->grupoOcupadoEnBloque((int) $dia->id, (string) $hora->hora_inicio, (string) $hora->hora_fin)) {
                    continue;
                }

                $conflictos = $this->buscarConflictosProfesor(
                    profesorId: $profesorId,
                    diaId: (int) $dia->id,
                    horaInicio: (string) $hora->hora_inicio,
                    horaFin: (string) $hora->hora_fin,
                    horarioActualId: $horarioActualId
                );

                if (count($conflictos) > 0) {
                    continue;
                }

                $mismoDia = (int) $dia->id === $diaSolicitadoId;
                $distanciaDia = abs((int) ($dia->orden ?? 0) - $ordenDiaSolicitado);
                $distanciaHora = abs((int) ($hora->orden ?? 0) - $ordenHoraSolicitada);

                $alternativas->push([
                    'dia_id' => (int) $dia->id,
                    'hora_id' => (int) $hora->id,
                    'dia' => (string) $dia->dia,
                    'hora_inicio' => (string) $hora->hora_inicio,
                    'hora_fin' => (string) $hora->hora_fin,
                    'hora_texto' => $this->formatearBloqueHora((string) $hora->hora_inicio, (string) $hora->hora_fin),
                    'motivo' => $mismoDia
                        ? 'Mantiene la actividad en el mismo día y evita el traslape.'
                        : 'El grupo y el docente están disponibles en este bloque.',
                    'prioridad' => $mismoDia ? 'alta' : 'normal',
                    'puntaje' => ($mismoDia ? 0 : 100) + ($distanciaDia * 10) + $distanciaHora,
                ]);
            }
        }

        return $alternativas
            ->sortBy('puntaje')
            ->take((int) config('groq.horarios.max_alternativas', 8))
            ->values()
            ->map(function (array $alternativa, int $indice) {
                unset($alternativa['puntaje']);
                $alternativa['indice'] = $indice;

                return $alternativa;
            })
            ->all();
    }

    protected function grupoOcupadoEnBloque(int $diaId, string $horaInicio, string $horaFin): bool
    {
        $dia = Dia::query()->find($diaId);

        if (!$dia || !$this->grupo_id || !$this->ciclo_escolar_id) {
            return true;
        }

        $diaIds = Dia::query()
            ->whereRaw('LOWER(dia) = ?', [mb_strtolower((string) $dia->dia)])
            ->pluck('id');

        return HorarioModel::query()
            ->where('grupo_id', $this->grupo_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereIn('dia_id', $diaIds)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->whereHas('hora', function ($query) use ($horaInicio, $horaFin) {
                $query->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->exists();
    }

    public function analizarConflictoConIa(GroqHorarioService $groq): void
    {
        if (!$this->mostrarModalTraslapeProfesor || empty($this->conflictosProfesor)) {
            $this->dispatch('swal', [
                'title' => 'No hay un conflicto pendiente para analizar.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        try {
            $this->analisisConflictoIa = $groq->explicarConflicto(
                $this->construirContextoConflictoIa()
            );
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('swal', [
                'title' => 'No fue posible analizar el conflicto.',
                'text' => $exception->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function aplicarAlternativaConflicto(int $indice): void
    {
        $alternativa = collect($this->alternativasConflicto)
            ->firstWhere('indice', $indice);

        $asignacionMateriaId = (int) ($this->pendienteHorario['asignacion_materia_id'] ?? 0);

        if (!$alternativa || !$asignacionMateriaId) {
            $this->dispatch('swal', [
                'title' => 'La alternativa ya no está disponible.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $asignacion = AsignacionMateria::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->configurables()
            ->find($asignacionMateriaId);

        if (!$asignacion?->profesor_id) {
            $this->dispatch('swal', [
                'title' => 'No se pudo validar al docente de la materia.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $alternativasActuales = $this->buscarAlternativasViables(
            profesorId: (int) $asignacion->profesor_id,
            diaSolicitadoId: (int) ($this->pendienteHorario['dia_id'] ?? 0),
            horaSolicitadaId: (int) ($this->pendienteHorario['hora_id'] ?? 0)
        );

        $continuaDisponible = collect($alternativasActuales)->contains(
            fn(array $item) => (int) $item['dia_id'] === (int) $alternativa['dia_id']
                && (int) $item['hora_id'] === (int) $alternativa['hora_id']
        );

        if (!$continuaDisponible) {
            $this->dispatch('swal', [
                'title' => 'La disponibilidad cambió.',
                'text' => 'Actualiza el horario y selecciona otra alternativa.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $diaId = (int) $alternativa['dia_id'];
        $horaId = (int) $alternativa['hora_id'];
        $claveCelda = $horaId . '-' . $diaId;

        $this->resetEstadoTraslapeProfesor();

        $this->procesarCambioHorario(
            horaId: $horaId,
            diaId: $diaId,
            asignacionMateriaId: $asignacionMateriaId,
            claveCelda: $claveCelda
        );

        $this->dispatch('swal', [
            'title' => 'Materia colocada en una alternativa disponible.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    protected function construirContextoConflictoIa(): array
    {
        $asignacion = AsignacionMateria::query()
            ->with('materia:id,materia')
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->configurables()
            ->find((int) ($this->pendienteHorario['asignacion_materia_id'] ?? 0));

        $dia = Dia::query()->find((int) ($this->pendienteHorario['dia_id'] ?? 0));
        $hora = Hora::query()->find((int) ($this->pendienteHorario['hora_id'] ?? 0));
        $grado = $this->grados->firstWhere('id', $this->grado_id);
        $grupo = $this->grupos->firstWhere('id', $this->grupo_id) ?? $this->obtenerGrupoSeleccionado();
        $semestre = $this->semestres->firstWhere('id', $this->semestre_id);

        return [
            'solicitud' => [
                'nivel' => $this->nivel?->nombre ?? 'No definido',
                'grado' => $grado?->nombre ?? 'No definido',
                'grupo' => $this->textoGrupo($grupo),
                'semestre' => $semestre?->numero ? $semestre->numero . '°' : null,
                'materia' => $asignacion?->materia?->materia ?? 'Materia no definida',
                'dia' => $dia?->dia ?? 'No definido',
                'hora' => $hora
                    ? $this->formatearBloqueHora((string) $hora->hora_inicio, (string) $hora->hora_fin)
                    : 'No definida',
            ],
            'conflictos_detectados' => collect($this->conflictosProfesor)
                ->map(fn(array $conflicto) => [
                    'nivel' => $conflicto['nivel'] ?? 'N/D',
                    'grado' => $conflicto['grado'] ?? 'N/D',
                    'grupo' => $conflicto['grupo'] ?? 'N/D',
                    'materia' => $conflicto['materia'] ?? 'N/D',
                    'dia' => $conflicto['dia'] ?? 'N/D',
                    'hora' => $this->formatearBloqueHora(
                        (string) ($conflicto['hora_inicio'] ?? ''),
                        (string) ($conflicto['hora_fin'] ?? '')
                    ),
                    'semestre' => $conflicto['semestre'] ?? null,
                ])
                ->values()
                ->all(),
            'alternativas_validas' => collect($this->alternativasConflicto)
                ->map(fn(array $alternativa) => [
                    'dia' => $alternativa['dia'],
                    'hora' => $alternativa['hora_texto'],
                    'motivo' => $alternativa['motivo'],
                ])
                ->values()
                ->all(),
            'regla' => 'Las alternativas fueron verificadas contra la disponibilidad actual del grupo y del docente.',
        ];
    }

    protected function formatearBloqueHora(string $horaInicio, string $horaFin): string
    {
        if ($horaInicio === '' || $horaFin === '') {
            return 'Hora no definida';
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $horaInicio)->format('h:i A')
                . ' - '
                . \Carbon\Carbon::createFromFormat('H:i:s', $horaFin)->format('h:i A');
        } catch (\Throwable) {
            return $horaInicio . ' - ' . $horaFin;
        }
    }

    protected function maxGruposSimultaneosDocente(int $profesorId): int
    {
        return max(1, (int) (HorarioDocenteConfiguracion::query()
            ->where('persona_id', $profesorId)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where(function (Builder $query): void {
                $query->where('nivel_id', $this->nivel?->id)
                    ->orWhereNull('nivel_id');
            })
            ->where('activo', true)
            ->orderByRaw('nivel_id IS NULL')
            ->value('max_grupos_simultaneos') ?? 2));
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
            ->where('dia_id', (int) $this->pendienteHorario['dia_id'])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereNull('taller_sesion_id');

        if ($this->esBachillerato) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $horarioExistente = $consulta->first();

        $asignacion = AsignacionMateria::query()
            ->find((int) $this->pendienteHorario['asignacion_materia_id']);

        if (! $asignacion?->profesor_id) {
            $this->dispatch('swal', [
                'title' => 'No se pudo identificar al docente.',
                'text' => 'Asigna un docente a la materia antes de confirmar la sesión simultánea.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $hora = Hora::query()->find((int) $this->pendienteHorario['hora_id']);
        $conflictosActuales = $hora
            ? $this->buscarConflictosProfesor(
                profesorId: (int) $asignacion->profesor_id,
                diaId: (int) $this->pendienteHorario['dia_id'],
                horaInicio: (string) $hora->hora_inicio,
                horaFin: (string) $hora->hora_fin,
                horarioActualId: $horarioExistente?->id,
            )
            : [];
        $maximoSimultaneo = $this->maxGruposSimultaneosDocente((int) $asignacion->profesor_id);

        if (count($conflictosActuales) + 1 > $maximoSimultaneo) {
            $this->dispatch('swal', [
                'title' => 'Se excede el máximo de grupos simultáneos.',
                'text' => "El docente tiene permitido atender hasta {$maximoSimultaneo} grupos en el mismo bloque. Ajusta su configuración o elige otro horario.",
                'icon' => 'error',
                'position' => 'top-end',
            ]);
            return;
        }

        $claveCompartida = sprintf(
            'shared-%d-%d-%d-%d',
            (int) $this->ciclo_escolar_id,
            (int) ($asignacion?->profesor_id ?? 0),
            (int) $this->pendienteHorario['dia_id'],
            (int) $this->pendienteHorario['hora_id'],
        );

        if ($asignacion?->profesor_id) {
            HorarioModel::query()
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('dia_id', (int) $this->pendienteHorario['dia_id'])
                ->where('hora_id', (int) $this->pendienteHorario['hora_id'])
                ->where(function (Builder $q) use ($asignacion): void {
                    $q->where(function (Builder $legacy) use ($asignacion): void {
                        $legacy->where('profesor_id', $asignacion->profesor_id)
                            ->whereNull('asignacion_materia_id')
                            ->whereNull('taller_sesion_id');
                    })->orWhereHas('asignacionMateria', fn (Builder $materia) => $materia
                        ->where('profesor_id', $asignacion->profesor_id)
                        ->configurables());
                })
                ->update([
                    'sesion_compartida' => true,
                    'clave_sesion_compartida' => $claveCompartida,
                    'motivo_sesion_compartida' => trim($this->motivoSesionCompartida),
                ]);
        }

        $this->guardarHorarioDirecto(
            horaId: (int) $this->pendienteHorario['hora_id'],
            diaId: (int) $this->pendienteHorario['dia_id'],
            asignacionMateriaId: (int) $this->pendienteHorario['asignacion_materia_id'],
            horarioExistente: $horarioExistente,
            sesionCompartida: true,
            claveSesionCompartida: $claveCompartida,
            motivoSesionCompartida: trim($this->motivoSesionCompartida),
        );
    }

    public function cancelarGuardarConTraslape(): void
    {
        $claveCelda = $this->pendienteHorario['clave_celda'] ?? null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
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
        $this->alternativasConflicto = [];
        $this->analisisConflictoIa = null;
        $this->motivoSesionCompartida = 'Sesión compartida entre varios grados o grupos.';
    }

}
