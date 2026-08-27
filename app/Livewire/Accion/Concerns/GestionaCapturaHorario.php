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

trait GestionaCapturaHorario
{
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
        app(CicloNivelGateService::class)->asegurar(
            (int) $this->ciclo_escolar_id,
            (int) $this->nivel->id,
            'horarios'
        );

        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        if (filled($asignacionMateriaId)) {
            $hayTallerConjunto = HorarioModel::query()
                ->where('grupo_id', $this->grupo_id)
                ->where('dia_id', $diaId)
                ->where('hora_id', $horaId)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->whereNotNull('taller_sesion_id')
                ->whereHas('tallerSesion', fn($query) => $query
                    ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA))
                ->exists();

            if ($hayTallerConjunto) {
                $this->restaurarCeldaDesdeHorarioGuardado($claveCelda);
                $this->dispatch('swal', [
                    'title' => 'La celda contiene un taller conjunto',
                    'text' => 'Edita, cierra o archiva la sesión compartida antes de asignar una materia normal en este bloque.',
                    'icon' => 'warning',
                    'position' => 'top-end',
                ]);
                return;
            }
        }

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('hora_id', $horaId)
            ->where('dia_id', $diaId)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->whereNull('taller_sesion_id');

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

            $this->invalidarAnalisisHorarioIa();
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
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->configurables()
            ->where('grupo_id', $this->grupo_id)
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
            $this->alternativasConflicto = $this->buscarAlternativasViables(
                profesorId: (int) $asignacion->profesor_id,
                diaSolicitadoId: $diaId,
                horaSolicitadaId: $horaId,
                horarioActualId: $horarioExistente?->id
            );
            $this->analisisConflictoIa = null;
            $this->mostrarModalTraslapeProfesor = true;

            // La selección se restaura mientras el usuario revisa el detalle del traslape.
            // El modal muestra exactamente dónde está ocupado el docente y permite decidir.
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
        ?HorarioModel $horarioExistente = null,
        bool $sesionCompartida = false,
        ?string $claveSesionCompartida = null,
        ?string $motivoSesionCompartida = null,
        bool $traslapeExcepcional = false,
        ?string $motivoTraslapeExcepcional = null,
    ): void {
        $asignacion = AsignacionMateria::query()
            ->select(['id', 'profesor_id', 'grupo_id', 'ciclo_escolar_id', 'estado'])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $this->grupo_id)
            ->configurables()
            ->find($asignacionMateriaId);

        if (! $asignacion) {
            $this->dispatch('swal', [
                'title' => 'La carga ya no está disponible para programación',
                'text' => 'La materia fue cerrada, archivada o cambió de contexto. Actualiza la pantalla antes de continuar.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            $this->cargarHorariosGuardados();
            $this->sincronizarSeleccionesHorario();
            return;
        }

        $datosBusqueda = [
            'nivel_id' => $this->nivel->id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'grupo_id' => $this->grupo_id,
            'hora_id' => $horaId,
            'dia_id' => $diaId,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
        ];

        if ($horarioExistente) {
            $horarioExistente->update([
                'asignacion_materia_id' => $asignacionMateriaId,
                'profesor_id' => $asignacion?->profesor_id,
                'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
                'ciclo_escolar_id' => $this->ciclo_escolar_id,
                'sesion_compartida' => $sesionCompartida,
                'clave_sesion_compartida' => $sesionCompartida ? $claveSesionCompartida : null,
                'motivo_sesion_compartida' => $sesionCompartida ? $motivoSesionCompartida : null,
                'traslape_excepcional' => $traslapeExcepcional,
                'motivo_traslape_excepcional' => $traslapeExcepcional ? $motivoTraslapeExcepcional : null,
            ]);
        } else {
            HorarioModel::query()->create([
                ...$datosBusqueda,
                'asignacion_materia_id' => $asignacionMateriaId,
                'profesor_id' => $asignacion?->profesor_id,
                'sesion_compartida' => $sesionCompartida,
                'clave_sesion_compartida' => $sesionCompartida ? $claveSesionCompartida : null,
                'motivo_sesion_compartida' => $sesionCompartida ? $motivoSesionCompartida : null,
                'traslape_excepcional' => $traslapeExcepcional,
                'motivo_traslape_excepcional' => $traslapeExcepcional ? $motivoTraslapeExcepcional : null,
            ]);
        }

        $this->invalidarAnalisisHorarioIa();
        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->cargarTalleresGuardados();
        $this->sincronizarSeleccionesHorario();
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

}
