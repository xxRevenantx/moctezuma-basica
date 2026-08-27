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
use App\Services\ContextoCicloEscolarSesion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Exports\HorarioExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

trait GestionaContextoHorario
{
    public function updatedCicloEscolarId(): void
    {
        app(ContextoCicloEscolarSesion::class)->recordar($this->ciclo_escolar_id);
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->cargarGeneraciones();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
        $this->invalidarAnalisisHorarioIa();

        if ($this->esBachillerato) {
            $this->semestre_id = null;
        }

        $this->resetEstadoTraslapeProfesor();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGrupoId(): void
    {
        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;

        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
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
        $this->talleresGuardados = collect();
        $this->seleccionesHorario = [];
        $this->traslapesHorario = [];
        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
    }

    protected function consultaGruposBase(): Builder
    {
        if (!$this->ciclo_escolar_id) {
            return Grupo::query()->whereRaw('1 = 0');
        }

        return app(ContextoEscolarService::class)
            ->consultaGrupos(
                nivelId: (int) $this->nivel->id,
                cicloEscolarId: (int) $this->ciclo_escolar_id,
            )
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*');
    }

    protected function filtrosCompletos(): bool
    {
        if (!$this->ciclo_escolar_id) {
            return false;
        }

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

        return app(ContextoEscolarService::class)->grupoValido(
            grupoId: (int) $this->grupo_id,
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
            semestreId: $this->semestre_id,
            bachillerato: $this->esBachillerato,
        );
    }

    protected function cargarGeneraciones(): void
    {
        if (!$this->ciclo_escolar_id) {
            $this->generaciones = collect();
            return;
        }

        $this->generaciones = app(ContextoEscolarService::class)->generaciones(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
        );
    }

    protected function cargarGrados(): void
    {
        if (!$this->ciclo_escolar_id || !$this->generacion_id) {
            $this->grados = collect();
            return;
        }

        $this->grados = app(ContextoEscolarService::class)->grados(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
        );
    }

    protected function cargarGrupos(): void
    {
        if (!$this->ciclo_escolar_id || !$this->generacion_id || !$this->grado_id) {
            $this->grupos = collect();
            return;
        }

        if ($this->esBachillerato && !$this->semestre_id) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = app(ContextoEscolarService::class)->grupos(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
            semestreId: $this->semestre_id,
            bachillerato: $this->esBachillerato,
        );
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
        if (!$this->esBachillerato || !$this->ciclo_escolar_id || !$this->generacion_id || !$this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = app(ContextoEscolarService::class)->semestres(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
        );
    }

    protected function cargarMateriasDisponibles(): void
    {
        if (!$this->filtrosMinimosParaMaterias()) {
            $this->materiasDisponibles = collect();
            return;
        }

        /*
         * El horario consume cargas académicas del ciclo seleccionado.
         * No crea asignaciones automáticamente: las cargas se preparan y confirman
         * desde Asignación de materias, con lo que no se contamina el historial.
         */

        $this->materiasDisponibles = AsignacionMateria::query()
            ->with([
                'materia',
                'profesor',
            ])
            ->where('grupo_id', $this->grupo_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->configurables()
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $this->grado_id);

                if ($this->nivel?->slug === 'secundaria') {
                    $query->where('slug', '!=', 'taller');
                }

                $query->where('receso', false);

                if ($this->esBachillerato) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereNull('semestre_id');
                }
            })
            ->get()
            ->sortBy([
                // El orden real de la materia debe tener prioridad.
                fn($a, $b) => ($a->materia?->orden ?? PHP_INT_MAX)
                    <=> ($b->materia?->orden ?? PHP_INT_MAX),
                fn($a, $b) => ($a->orden ?? PHP_INT_MAX)
                    <=> ($b->orden ?? PHP_INT_MAX),
                fn($a, $b) => strcmp(
                    $a->materia?->materia ?? '',
                    $b->materia?->materia ?? ''
                ),
            ])
            ->values();
    }

    protected function sincronizarMateriasFaltantesDelGrupo(): void
    {
        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            return;
        }

        $materias = Materia::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('receso', false)
            ->when($this->nivel?->slug === 'secundaria', fn($query) => $query->where('slug', '!=', 'taller'))
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'orden']);

        if ($materias->isEmpty()) {
            return;
        }

        $materiasYaAsignadas = AsignacionMateria::query()
            ->where('grupo_id', $grupo->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereIn('materia_id', $materias->pluck('id'))
            ->pluck('materia_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $materias
            ->reject(fn($materia) => in_array((int) $materia->id, $materiasYaAsignadas, true))
            ->each(function ($materia) use ($grupo) {
                AsignacionMateria::query()->firstOrCreate(
                    [
                        'ciclo_escolar_id' => $this->ciclo_escolar_id,
                        'grupo_id' => $grupo->id,
                        'materia_id' => $materia->id,
                    ],
                    [
                        'profesor_id' => null,
                        'nivel_id' => $grupo->nivel_id,
                        'grado_id' => $grupo->grado_id,
                        'generacion_id' => $grupo->generacion_id,
                        'semestre_id' => $grupo->semestre_id,
                        'estado' => AsignacionMateria::ESTADO_BORRADOR,
                        'orden' => (int) ($materia->orden ?? 0),
                    ]
                );
            });
    }

    protected function cargarHorariosGuardados(): void
    {
        if (!$this->filtrosCompletos()) {
            $this->horariosGuardados = collect();
            $this->traslapesHorario = [];
            return;
        }

        $horarios = HorarioModel::query()
            ->with([
                'asignacionMateria.materia',
                'asignacionMateria.profesor',
                'profesorAsignado',
                'hora:id,hora_inicio,hora_fin',
                'dia:id,dia',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereNull('taller_sesion_id')
            ->whereHas('asignacionMateria', fn($query) => $query
                ->configurables())
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->get();

        $this->horariosGuardados = $horarios->keyBy(function ($horario) {
            return $horario->hora_id . '-' . $horario->dia_id;
        });

        $this->detectarTraslapesHorariosGuardados();
    }

    protected function cargarTalleresGuardados(): void
    {
        if (!$this->filtrosCompletos() || !$this->ciclo_escolar_id) {
            $this->talleresGuardados = collect();

            return;
        }

        $talleres = HorarioModel::query()
            ->with([
                'tallerSesion.taller:id,nivel_id,nombre,clave',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion.grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'tallerSesion.grupos.asignacionGrupo:id,nombre',
                'tallerSesion.grupos.grado:id,nombre,orden',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereNotNull('taller_sesion_id')
            ->whereHas('tallerSesion', fn($query) => $query
                ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA))
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->get();

        /*
         * groupBy() conserva Eloquent\Collection y coloca otras colecciones
         * dentro de ella. Livewire intenta serializar esas colecciones internas
         * como modelos y genera el error Collection::getMorphClass().
         *
         * toBase() convierte solamente la colección exterior en
         * Illuminate\Support\Collection.
         */
        $this->talleresGuardados = $talleres
            ->groupBy(function ($horario) {
                return $horario->hora_id . '-' . $horario->dia_id;
            })
            ->toBase();
    }

}
