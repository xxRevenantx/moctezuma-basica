<?php

namespace App\Livewire\PersonaNivel;

use App\Models\ActividadAdministrativa;
use App\Models\AsignacionMateria;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaNivelHistorial;
use App\Models\TipoDocumentoPersonal;
use App\Services\CargaLaboralPersonaNivelService;
use App\Services\ExpedientePersonalResumenService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class GestionPersonaNivel extends Component
{
    public string $tab = 'plantilla';
    public string $search = '';
    public string $nivelFiltro = '';
    public string $estadoFiltro = 'todos';
    public ?int $reporteNivelId = null;
    public ?int $reporteGradoId = null;
    public ?int $reporteGrupoId = null;
    public ?int $reportePersonaId = null;

    /** @var array<int, int|string> */
    public array $seleccionados = [];

    public string $accionMasiva = '';
    public ?int $nivelDestinoId = null;
    public ?int $gradoDestinoId = null;
    public ?int $grupoDestinoId = null;
    public string $motivoMasivo = '';

    public ?int $detalleEditId = null;
    public ?string $editCabFechaInicio = null;
    public ?string $editCabFechaFin = null;
    public string $editCabEstado = 'activo';
    public string $editCabEstadoOriginal = 'activo';
    public $editCabHorasAdministrativas = 0;
    public $editCabLimiteHoras = 40;
    public string $editCabActividadAdministrativa = '';
    public string $editCabObservaciones = '';
    public ?string $editCabFechaBaja = null;
    public string $editCabMotivoBaja = '';
    public ?string $editFechaInicio = null;
    public ?string $editFechaFin = null;
    public string $editEstado = 'activo';
    public string $editEstadoOriginal = 'activo';
    public bool $editEsTitular = false;
    public bool $editEsTitularPrincipal = false;
    public ?int $editAsignacionMateriaId = null;
    public string $editMateriaManual = '';
    public $editAjusteHoras = 0;
    public $editHorasAdministrativas = 0;
    public ?int $editActividadAdministrativaId = null;
    public string $editActividadAdministrativaManual = '';
    public $editLimiteHoras = 40;
    public string $editObservaciones = '';
    public ?string $editFechaBaja = null;
    public string $editMotivoBaja = '';

    public Collection $asignacionesMateriaDisponibles;

    public function mount(): void
    {
        $this->asignacionesMateriaDisponibles = collect();
    }

    #[On('refreshPersonaNivelList')]
    public function refrescar(): void
    {
        $this->seleccionados = [];
    }

    public function updatedReporteNivelId(): void
    {
        $this->reporteGradoId = null;
        $this->reporteGrupoId = null;
    }

    public function updatedReporteGradoId(): void
    {
        $this->reporteGrupoId = null;
    }

    public function updatedNivelDestinoId(): void
    {
        $this->gradoDestinoId = null;
        $this->grupoDestinoId = null;
    }

    public function updatedGradoDestinoId(): void
    {
        $this->grupoDestinoId = null;
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function seleccionarVisibles(array $ids): void
    {
        $this->seleccionados = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    public function editarGestion(int $detalleId): void
    {
        $detalle = PersonaNivelDetalle::query()
            ->with(['cabecera.persona', 'cabecera.nivel'])
            ->findOrFail($detalleId);

        $this->detalleEditId = $detalle->id;
        $this->editCabFechaInicio = optional($detalle->cabecera?->fecha_inicio)->format('Y-m-d');
        $this->editCabFechaFin = optional($detalle->cabecera?->fecha_fin)->format('Y-m-d');
        $this->editCabEstado = $detalle->cabecera?->estado ?: PersonaNivel::ESTADO_ACTIVO;
        $this->editCabEstadoOriginal = $this->editCabEstado;
        $this->editCabHorasAdministrativas = (float) ($detalle->cabecera?->horas_administrativas ?? 0);
        $this->editCabLimiteHoras = (float) ($detalle->cabecera?->limite_horas_semanales ?? 40);
        $this->editCabActividadAdministrativa = (string) ($detalle->cabecera?->actividad_administrativa ?? '');
        $this->editCabObservaciones = (string) ($detalle->cabecera?->observaciones ?? '');
        $this->editCabFechaBaja = optional($detalle->cabecera?->fecha_baja)->format('Y-m-d');
        $this->editCabMotivoBaja = (string) ($detalle->cabecera?->motivo_baja ?? '');
        $this->editFechaInicio = optional($detalle->fecha_inicio)->format('Y-m-d');
        $this->editFechaFin = optional($detalle->fecha_fin)->format('Y-m-d');
        $this->editEstado = $detalle->estado ?: PersonaNivelDetalle::ESTADO_ACTIVO;
        $this->editEstadoOriginal = $this->editEstado;
        $this->editEsTitular = (bool) $detalle->es_titular;
        $this->editEsTitularPrincipal = (bool) $detalle->es_titular_principal;
        $this->editAsignacionMateriaId = $detalle->asignacion_materia_id;
        $this->editMateriaManual = (string) ($detalle->materia_manual ?? '');
        $this->editAjusteHoras = (float) ($detalle->ajuste_horas_frente_grupo ?? 0);
        $this->editHorasAdministrativas = (float) ($detalle->horas_administrativas ?? 0);
        $this->editActividadAdministrativaId = $detalle->actividad_administrativa_id;
        $this->editActividadAdministrativaManual = (string) ($detalle->actividad_administrativa_manual ?? '');
        $this->editLimiteHoras = (float) ($detalle->limite_horas_semanales ?? $detalle->cabecera?->limite_horas_semanales ?? 40);
        $this->editObservaciones = (string) ($detalle->observaciones ?? '');
        $this->editFechaBaja = optional($detalle->fecha_baja)->format('Y-m-d');
        $this->editMotivoBaja = (string) ($detalle->motivo_baja ?? '');

        $personaId = (int) $detalle->cabecera->persona_id;
        $nivelId = (int) $detalle->cabecera->nivel_id;

        $this->asignacionesMateriaDisponibles = AsignacionMateria::query()
            ->with(['materia:id,materia', 'grupo.asignacionGrupo:id,nombre'])
            ->where('profesor_id', $personaId)
            ->where(function (Builder $query) use ($nivelId) {
                $query->where('nivel_id', $nivelId)
                    ->orWhereHas('grupo', fn (Builder $grupo) => $grupo->where('nivel_id', $nivelId));
            })
            ->orderByDesc('id')
            ->get();

        $this->resetValidation();
        $this->dispatch('abrir-modal-gestion-persona-nivel');
    }

    public function guardarGestion(): void
    {
        $this->validate([
            'detalleEditId' => ['required', 'integer', 'exists:persona_nivel_detalles,id'],
            'editCabFechaInicio' => ['required', 'date'],
            'editCabFechaFin' => ['nullable', 'date', 'after_or_equal:editCabFechaInicio'],
            'editCabEstado' => ['required', 'in:activo,baja'],
            'editCabHorasAdministrativas' => ['required', 'numeric', 'between:0,100'],
            'editCabLimiteHoras' => ['required', 'numeric', 'between:1,100'],
            'editCabActividadAdministrativa' => ['nullable', 'string', 'max:255'],
            'editCabObservaciones' => ['nullable', 'string', 'max:3000'],
            'editCabFechaBaja' => ['nullable', 'date'],
            'editCabMotivoBaja' => ['nullable', 'string', 'max:2000'],
            'editFechaInicio' => ['required', 'date'],
            'editFechaFin' => ['nullable', 'date', 'after_or_equal:editFechaInicio'],
            'editEstado' => ['required', 'in:activo,baja'],
            'editAsignacionMateriaId' => ['nullable', 'integer', 'exists:asignacion_materias,id'],
            'editMateriaManual' => ['nullable', 'string', 'max:255'],
            'editAjusteHoras' => ['required', 'numeric', 'between:-80,80'],
            'editHorasAdministrativas' => ['required', 'numeric', 'between:0,80'],
            'editActividadAdministrativaId' => ['nullable', 'integer', 'exists:actividades_administrativas,id'],
            'editActividadAdministrativaManual' => ['nullable', 'string', 'max:255'],
            'editLimiteHoras' => ['required', 'numeric', 'between:1,100'],
            'editObservaciones' => ['nullable', 'string', 'max:3000'],
            'editFechaBaja' => ['nullable', 'date'],
            'editMotivoBaja' => ['nullable', 'string', 'max:2000'],
        ], [
            'editFechaInicio.required' => 'La fecha de inicio es obligatoria.',
            'editFechaFin.after_or_equal' => 'La fecha de término no puede ser anterior al inicio.',
        ]);

        if ($this->editCabEstadoOriginal === PersonaNivel::ESTADO_BAJA
            && $this->editCabEstado === PersonaNivel::ESTADO_ACTIVO) {
            $this->addError('editCabEstado', 'Una baja general no puede reactivarse. Crea una nueva asignación al nivel.');
        }

        if ($this->editEstadoOriginal === PersonaNivelDetalle::ESTADO_BAJA
            && $this->editEstado === PersonaNivelDetalle::ESTADO_ACTIVO) {
            $this->addError('editEstado', 'Una asignación dada de baja no puede reactivarse. Duplica o crea una asignación nueva.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $advertenciaTitular = false;

        DB::transaction(function () use (&$advertenciaTitular) {
            $detalle = PersonaNivelDetalle::query()
                ->with('cabecera')
                ->lockForUpdate()
                ->findOrFail($this->detalleEditId);

            if ($this->editAsignacionMateriaId) {
                $asignacionValida = AsignacionMateria::query()
                    ->whereKey($this->editAsignacionMateriaId)
                    ->where('profesor_id', $detalle->cabecera->persona_id)
                    ->exists();

                if (!$asignacionValida) {
                    $this->addError('editAsignacionMateriaId', 'La materia vinculada no pertenece a esta persona.');
                    return;
                }
            }

            if ($this->editEsTitularPrincipal && $detalle->grupo_id) {
                $advertenciaTitular = PersonaNivelDetalle::query()
                    ->where('grupo_id', $detalle->grupo_id)
                    ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
                    ->where('es_titular_principal', true)
                    ->where('id', '!=', $detalle->id)
                    ->exists();
            }

            $esBaja = $this->editEstado === PersonaNivelDetalle::ESTADO_BAJA;

            $detalle->cabecera->update([
                'fecha_inicio' => $this->editCabFechaInicio,
                'fecha_fin' => $this->editCabFechaFin,
                'estado' => $this->editCabEstado,
                'horas_administrativas' => $this->editCabHorasAdministrativas,
                'limite_horas_semanales' => $this->editCabLimiteHoras,
                'actividad_administrativa' => trim($this->editCabActividadAdministrativa) ?: null,
                'observaciones' => trim($this->editCabObservaciones) ?: null,
                'fecha_baja' => $this->editCabEstado === PersonaNivel::ESTADO_BAJA ? ($this->editCabFechaBaja ?: now()->toDateString()) : null,
                'motivo_baja' => $this->editCabEstado === PersonaNivel::ESTADO_BAJA ? (trim($this->editCabMotivoBaja) ?: 'Baja general desde Plantilla') : null,
            ]);

            $detalle->update([
                'fecha_inicio' => $this->editFechaInicio,
                'fecha_fin' => $this->editFechaFin ?: ($esBaja ? now()->toDateString() : null),
                'estado' => $this->editEstado,
                'es_titular' => $this->editEsTitular || $this->editEsTitularPrincipal,
                'es_titular_principal' => $this->editEsTitularPrincipal,
                'asignacion_materia_id' => $this->editAsignacionMateriaId,
                'materia_manual' => trim($this->editMateriaManual) ?: null,
                'ajuste_horas_frente_grupo' => $this->editAjusteHoras,
                'horas_administrativas' => $this->editHorasAdministrativas,
                'actividad_administrativa_id' => $this->editActividadAdministrativaId,
                'actividad_administrativa_manual' => trim($this->editActividadAdministrativaManual) ?: null,
                'limite_horas_semanales' => $this->editLimiteHoras,
                'observaciones' => trim($this->editObservaciones) ?: null,
                'fecha_baja' => $esBaja ? ($this->editFechaBaja ?: now()->toDateString()) : null,
                'motivo_baja' => $esBaja ? (trim($this->editMotivoBaja) ?: null) : null,
            ]);

        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->dispatch('cerrar-modal-gestion-persona-nivel');
        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('notify', [
            'type' => $advertenciaTitular ? 'warning' : 'success',
            'message' => $advertenciaTitular
                ? 'Guardado. El grupo ya tenía otro titular principal activo; revisa la asignación.'
                : 'Gestión de la asignación actualizada correctamente.',
        ]);

        $this->cerrarGestion();
    }

    public function cerrarGestion(): void
    {
        $this->reset([
            'detalleEditId', 'editCabFechaInicio', 'editCabFechaFin', 'editCabEstado', 'editCabEstadoOriginal',
            'editCabHorasAdministrativas', 'editCabLimiteHoras', 'editCabActividadAdministrativa',
            'editCabObservaciones', 'editCabFechaBaja', 'editCabMotivoBaja',
            'editFechaInicio', 'editFechaFin', 'editEstado', 'editEstadoOriginal',
            'editEsTitular', 'editEsTitularPrincipal', 'editAsignacionMateriaId',
            'editMateriaManual', 'editAjusteHoras', 'editHorasAdministrativas',
            'editActividadAdministrativaId', 'editActividadAdministrativaManual',
            'editLimiteHoras', 'editObservaciones', 'editFechaBaja', 'editMotivoBaja',
        ]);

        $this->editCabEstado = PersonaNivel::ESTADO_ACTIVO;
        $this->editCabEstadoOriginal = PersonaNivel::ESTADO_ACTIVO;
        $this->editCabLimiteHoras = 40;
        $this->editEstado = PersonaNivelDetalle::ESTADO_ACTIVO;
        $this->editEstadoOriginal = PersonaNivelDetalle::ESTADO_ACTIVO;
        $this->editLimiteHoras = 40;
        $this->asignacionesMateriaDisponibles = collect();
        $this->resetValidation();
    }

    public function ejecutarAccionMasiva(): void
    {
        $ids = collect($this->seleccionados)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Selecciona al menos una asignación.']);
            return;
        }

        switch ($this->accionMasiva) {
            case 'baja':
                $this->cambiarEstadoMasivo($ids, PersonaNivelDetalle::ESTADO_BAJA);
                break;
            case 'mover':
                $this->moverMasivo($ids);
                break;
            case 'duplicar':
                $this->duplicarMasivo($ids);
                break;
            case 'eliminar':
                $this->eliminarMasivo($ids);
                break;
            default:
                $this->dispatch('notify', ['type' => 'warning', 'message' => 'Selecciona una acción masiva.']);
        }
    }

    private function cambiarEstadoMasivo(Collection $ids, string $estado): void
    {
        DB::transaction(function () use ($ids, $estado) {
            $detalles = PersonaNivelDetalle::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            foreach ($detalles as $detalle) {
                $esBaja = $estado === PersonaNivelDetalle::ESTADO_BAJA;
                $detalle->update([
                    'estado' => $estado,
                    'fecha_fin' => $esBaja ? ($detalle->fecha_fin ?: now()->toDateString()) : null,
                    'fecha_baja' => $esBaja ? now()->toDateString() : null,
                    'motivo_baja' => $esBaja ? (trim($this->motivoMasivo) ?: 'Baja masiva desde Plantilla') : null,
                ]);
            }

            $detalles->pluck('persona_nivel_id')->unique()->each(fn ($id) => $this->sincronizarEstadoCabecera((int) $id));
        });

        $this->finalizarMasivo($estado === 'baja' ? 'Asignaciones dadas de baja.' : 'Asignaciones activadas.');
    }

    private function moverMasivo(Collection $ids): void
    {
        $this->validate([
            'nivelDestinoId' => ['required', 'integer', 'exists:niveles,id'],
            'gradoDestinoId' => ['nullable', 'integer', 'exists:grados,id'],
            'grupoDestinoId' => ['nullable', 'integer', 'exists:grupos,id'],
        ], ['nivelDestinoId.required' => 'Selecciona el nivel de destino.']);

        if ($this->grupoDestinoId) {
            $grupo = Grupo::query()->findOrFail($this->grupoDestinoId);
            if ((int) $grupo->nivel_id !== (int) $this->nivelDestinoId) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'El grupo no pertenece al nivel seleccionado.']);
                return;
            }
            if ($this->gradoDestinoId && (int) $grupo->grado_id !== (int) $this->gradoDestinoId) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'El grupo no pertenece al grado seleccionado.']);
                return;
            }
        }

        if ($this->gradoDestinoId) {
            $gradoValido = Grado::query()
                ->whereKey($this->gradoDestinoId)
                ->where('nivel_id', $this->nivelDestinoId)
                ->exists();

            if (!$gradoValido) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'El grado no pertenece al nivel seleccionado.']);
                return;
            }
        }

        $duplicados = 0;

        DB::transaction(function () use ($ids, &$duplicados) {
            $detalles = PersonaNivelDetalle::query()->with('cabecera')->whereIn('id', $ids)->lockForUpdate()->get();
            $cabecerasOrigen = $detalles->pluck('persona_nivel_id')->unique();

            foreach ($detalles as $detalle) {
                $origen = $detalle->cabecera;
                $cabeceraDestino = PersonaNivel::query()
                    ->where('persona_id', $origen->persona_id)
                    ->where('nivel_id', $this->nivelDestinoId)
                    ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                    ->latest('id')
                    ->first();

                if (!$cabeceraDestino) {
                    $cabeceraDestino = PersonaNivel::create([
                        'persona_id' => $origen->persona_id,
                        'nivel_id' => $this->nivelDestinoId,
                        'ingreso_seg' => $origen->ingreso_seg,
                        'ingreso_sep' => $origen->ingreso_sep,
                        'ingreso_ct' => $origen->ingreso_ct,
                        'fecha_inicio' => now()->toDateString(),
                        'estado' => PersonaNivel::ESTADO_ACTIVO,
                        'limite_horas_semanales' => $origen->limite_horas_semanales ?: 40,
                    ]);
                }

                $grado = $this->gradoDestinoId;
                $grupo = $this->grupoDestinoId;

                $duplicados += PersonaNivelDetalle::query()
                    ->where('persona_nivel_id', $cabeceraDestino->id)
                    ->where('persona_role_id', $detalle->persona_role_id)
                    ->where('grado_id', $grado)
                    ->where('grupo_id', $grupo)
                    ->where('id', '!=', $detalle->id)
                    ->exists() ? 1 : 0;

                $cambioNivel = (int) $origen->nivel_id !== (int) $this->nivelDestinoId;

                $detalle->update([
                    'persona_nivel_id' => $cabeceraDestino->id,
                    'grado_id' => $grado,
                    'grupo_id' => $grupo,
                    'asignacion_materia_id' => $cambioNivel ? null : $detalle->asignacion_materia_id,
                ]);
            }

            foreach ($cabecerasOrigen as $cabeceraId) {
                if (!PersonaNivelDetalle::query()->where('persona_nivel_id', $cabeceraId)->exists()) {
                    PersonaNivel::query()->whereKey($cabeceraId)->delete();
                }
            }
        });

        $mensaje = 'Asignaciones movidas correctamente.';
        if ($duplicados > 0) {
            $mensaje .= " Se detectaron {$duplicados} posibles duplicados; se conservaron como solicitaste.";
        }

        $this->finalizarMasivo($mensaje, $duplicados > 0 ? 'warning' : 'success');
    }

    private function duplicarMasivo(Collection $ids): void
    {
        $this->validate([
            'nivelDestinoId' => ['required', 'integer', 'exists:niveles,id'],
            'gradoDestinoId' => ['nullable', 'integer', 'exists:grados,id'],
            'grupoDestinoId' => ['nullable', 'integer', 'exists:grupos,id'],
        ], ['nivelDestinoId.required' => 'Selecciona el nivel de destino.']);

        $duplicados = 0;
        $creados = 0;

        DB::transaction(function () use ($ids, &$duplicados, &$creados) {
            $detalles = PersonaNivelDetalle::query()->with('cabecera')->whereIn('id', $ids)->lockForUpdate()->get();

            foreach ($detalles as $detalle) {
                $origen = $detalle->cabecera;
                $cabecera = PersonaNivel::query()
                    ->where('persona_id', $origen->persona_id)
                    ->where('nivel_id', $this->nivelDestinoId)
                    ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                    ->latest('id')
                    ->first();

                if (!$cabecera) {
                    $cabecera = PersonaNivel::create([
                        'persona_id' => $origen->persona_id,
                        'nivel_id' => $this->nivelDestinoId,
                        'ingreso_seg' => $origen->ingreso_seg,
                        'ingreso_sep' => $origen->ingreso_sep,
                        'ingreso_ct' => $origen->ingreso_ct,
                        'fecha_inicio' => now()->toDateString(),
                        'estado' => PersonaNivel::ESTADO_ACTIVO,
                        'limite_horas_semanales' => $origen->limite_horas_semanales ?: 40,
                    ]);
                }

                $grado = $this->gradoDestinoId;
                $grupo = $this->grupoDestinoId;

                $existe = PersonaNivelDetalle::query()
                    ->where('persona_nivel_id', $cabecera->id)
                    ->where('persona_role_id', $detalle->persona_role_id)
                    ->where('grado_id', $grado)
                    ->where('grupo_id', $grupo)
                    ->exists();

                $duplicados += $existe ? 1 : 0;

                $nuevo = $detalle->replicate([
                    'persona_nivel_id', 'grado_id', 'grupo_id', 'asignacion_materia_id',
                    'fecha_inicio', 'fecha_fin', 'estado', 'fecha_baja', 'motivo_baja', 'orden',
                ]);
                $nuevo->persona_nivel_id = $cabecera->id;
                $nuevo->grado_id = $grado;
                $nuevo->grupo_id = $grupo;
                $nuevo->asignacion_materia_id = null;
                $nuevo->fecha_inicio = now()->toDateString();
                $nuevo->fecha_fin = null;
                $nuevo->estado = PersonaNivelDetalle::ESTADO_ACTIVO;
                $nuevo->fecha_baja = null;
                $nuevo->motivo_baja = null;
                $nuevo->orden = ((int) PersonaNivelDetalle::query()->where('persona_nivel_id', $cabecera->id)->max('orden')) + 1;
                $nuevo->save();
                $creados++;
            }
        });

        $mensaje = "Se duplicaron {$creados} asignaciones.";
        if ($duplicados > 0) {
            $mensaje .= " {$duplicados} coinciden con asignaciones existentes y se conservaron.";
        }

        $this->finalizarMasivo($mensaje, $duplicados > 0 ? 'warning' : 'success');
    }

    private function eliminarMasivo(Collection $ids): void
    {
        DB::transaction(function () use ($ids) {
            $detalles = PersonaNivelDetalle::query()->whereIn('id', $ids)->lockForUpdate()->get();
            $cabeceras = $detalles->pluck('persona_nivel_id')->unique();

            foreach ($detalles as $detalle) {
                $detalle->delete();
            }

            foreach ($cabeceras as $cabeceraId) {
                if (!PersonaNivelDetalle::query()->where('persona_nivel_id', $cabeceraId)->exists()) {
                    PersonaNivel::query()->whereKey($cabeceraId)->delete();
                }
            }
        });

        $this->finalizarMasivo('Asignaciones eliminadas definitivamente.');
    }

    private function finalizarMasivo(string $mensaje, string $tipo = 'success'): void
    {
        $this->seleccionados = [];
        $this->accionMasiva = '';
        $this->motivoMasivo = '';
        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('notify', ['type' => $tipo, 'message' => $mensaje]);
    }

    private function sincronizarEstadoCabecera(int $cabeceraId): void
    {
        $cabecera = PersonaNivel::query()->find($cabeceraId);
        if (!$cabecera) {
            return;
        }

        $tieneActivas = PersonaNivelDetalle::query()
            ->where('persona_nivel_id', $cabeceraId)
            ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
            ->exists();

        $cabecera->update([
            'estado' => $tieneActivas ? PersonaNivel::ESTADO_ACTIVO : PersonaNivel::ESTADO_BAJA,
            'fecha_fin' => $tieneActivas ? null : ($cabecera->fecha_fin ?: now()->toDateString()),
            'fecha_baja' => $tieneActivas ? null : ($cabecera->fecha_baja ?: now()->toDateString()),
            'motivo_baja' => $tieneActivas ? null : ($cabecera->motivo_baja ?: 'Todas las asignaciones del nivel están dadas de baja.'),
        ]);
    }

    private function queryDetalles(): Builder
    {
        return PersonaNivelDetalle::query()
            ->with([
                'cabecera.persona.documentosPersonal.tipoDocumento',
                'cabecera.nivel',
                'personaRole.rolePersona',
                'grado',
                'grupo.asignacionGrupo',
                'asignacionMateria.materia',
                'asignacionMateria.horarios.hora',
                'actividadAdministrativa',
            ])
            ->when($this->search !== '', function (Builder $query) {
                $buscar = trim($this->search);
                $query->where(function (Builder $sub) use ($buscar) {
                    $sub->whereHas('cabecera.persona', function (Builder $persona) use ($buscar) {
                        $persona->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                            ->orWhere('especialidad', 'like', "%{$buscar}%")
                            ->orWhere('grado_estudios', 'like', "%{$buscar}%");
                    })
                        ->orWhereHas('personaRole.rolePersona', fn (Builder $rol) => $rol->where('nombre', 'like', "%{$buscar}%"))
                        ->orWhere('materia_manual', 'like', "%{$buscar}%")
                        ->orWhereHas('asignacionMateria.materia', fn (Builder $materia) => $materia->where('materia', 'like', "%{$buscar}%"));
                });
            })
            ->when($this->nivelFiltro !== '', fn (Builder $query) => $query->whereHas('cabecera', fn (Builder $cabecera) => $cabecera->where('nivel_id', $this->nivelFiltro)))
            ->when($this->estadoFiltro !== 'todos', fn (Builder $query) => $query->where('estado', $this->estadoFiltro))
            ->orderBy(
                PersonaNivel::query()->select('nivel_id')->whereColumn('persona_nivel.id', 'persona_nivel_detalles.persona_nivel_id')->limit(1)
            )
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function render()
    {
        $cargaService = app(CargaLaboralPersonaNivelService::class);
        $expedienteService = app(ExpedientePersonalResumenService::class);
        $detalles = $this->queryDetalles()->get();
        $tiposDocumento = TipoDocumentoPersonal::query()->where('activo', true)->orderBy('orden')->get(['id', 'nombre', 'es_obligatorio']);
        $cargasCabecera = $detalles
            ->groupBy('persona_nivel_id')
            ->map(fn (Collection $items) => $cargaService->calcularCabecera($items));
        $expedientesPorPersona = $detalles
            ->pluck('cabecera.persona_id')
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($personaId) => [
                (int) $personaId => $expedienteService->paraPersona((int) $personaId, $tiposDocumento),
            ]);

        $filas = $detalles->map(function (PersonaNivelDetalle $detalle) use ($cargaService, $cargasCabecera, $expedientesPorPersona) {
            $persona = $detalle->cabecera?->persona;
            $carga = $cargaService->calcular($detalle);
            $expediente = $persona
                ? $expedientesPorPersona->get((int) $persona->id)
                : ['porcentaje' => 0, 'total' => 0, 'completos' => 0, 'faltantes' => []];

            $cedulas = $persona?->documentosPersonal
                ?->where('es_actual', true)
                ->filter(fn ($doc) => $doc->tipoDocumento?->slug === 'cedula-profesional')
                ->pluck('numero_cedula')
                ->filter()
                ->values()
                ->all() ?? [];

            return [
                'modelo' => $detalle,
                'carga' => $carga,
                'carga_global' => $cargasCabecera->get($detalle->persona_nivel_id, $carga),
                'expediente' => $expediente,
                'cedulas' => $cedulas,
            ];
        });

        $personaIds = $detalles->pluck('cabecera.persona_id')->filter()->unique();
        $sobrecargas = $filas
            ->unique(fn ($fila) => $fila['modelo']->persona_nivel_id)
            ->filter(fn ($fila) => $fila['carga_global']['sobrecarga'])
            ->count();
        $expedientesIncompletos = $filas->unique(fn ($fila) => $fila['modelo']->cabecera?->persona_id)
            ->filter(fn ($fila) => $fila['expediente']['porcentaje'] < 100)
            ->count();

        $duplicados = PersonaNivelDetalle::query()
            ->selectRaw('persona_nivel_id, persona_role_id, COALESCE(grado_id, 0) grado_key, COALESCE(grupo_id, 0) grupo_key, COUNT(*) total')
            ->groupByRaw('persona_nivel_id, persona_role_id, COALESCE(grado_id, 0), COALESCE(grupo_id, 0)')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->sum(fn ($row) => (int) $row->total - 1);

        $gruposSinTitular = Grupo::query()
            ->whereDoesntHave('personaNivelDetalles', function (Builder $query) {
                $query->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
                    ->where('es_titular_principal', true);
            })
            ->count();

        $resumen = [
            'personas' => $personaIds->count(),
            'asignaciones_activas' => $detalles->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)->count(),
            'asignaciones_baja' => $detalles->where('estado', PersonaNivelDetalle::ESTADO_BAJA)->count(),
            'sobrecargas' => $sobrecargas,
            'expedientes_incompletos' => $expedientesIncompletos,
            'duplicados' => $duplicados,
            'grupos_sin_titular' => $gruposSinTitular,
        ];

        $historial = PersonaNivelHistorial::query()
            ->with(['persona', 'nivel', 'usuario'])
            ->latest('fecha')
            ->limit(150)
            ->get();

        $niveles = Nivel::query()->orderBy('id')->get();
        $gradosDestino = $this->nivelDestinoId
            ? Grado::query()->where('nivel_id', $this->nivelDestinoId)->orderBy('nombre')->get()
            : Grado::query()->orderBy('nivel_id')->orderBy('nombre')->get();
        $gruposDestino = $this->gradoDestinoId
            ? Grupo::query()->with('asignacionGrupo')->where('grado_id', $this->gradoDestinoId)->get()
            : collect();
        $actividades = ActividadAdministrativa::query()->where('activo', true)->orderBy('orden')->get();
        $gradosReporte = $this->reporteNivelId
            ? Grado::query()->where('nivel_id', $this->reporteNivelId)->orderBy('nombre')->get()
            : collect();
        $gruposReporte = $this->reporteGradoId
            ? Grupo::query()->with('asignacionGrupo')->where('grado_id', $this->reporteGradoId)->get()
            : collect();
        $personasReporte = Persona::query()
            ->whereHas('personaNiveles')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get(['id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno']);

        return view('livewire.persona-nivel.gestion-persona-nivel', compact(
            'filas', 'resumen', 'historial', 'niveles', 'gradosDestino', 'gruposDestino', 'actividades',
            'gradosReporte', 'gruposReporte', 'personasReporte'
        ));
    }
}
