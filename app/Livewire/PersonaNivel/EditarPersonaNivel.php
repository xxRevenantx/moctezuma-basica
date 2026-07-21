<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaRole;
use App\Models\Semestre;
use App\Services\PlantillaPersonalCicloService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPersonaNivel extends Component
{
    public bool $open = false;
    public ?int $detalleId = null;
    public ?int $cicloEscolarId = null;
    public ?int $plantillaId = null;
    public ?int $persona_id = null;
    public ?int $persona_role_id = null;
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;
    public string $motivoCambio = '';
    public string $nombrePersona = '';
    public string $nombreNivel = '';
    public string $generacionTexto = '';
    public bool $rolPermiteGrupo = false;
    public bool $rolRequiereGrupo = false;
    public string $plantillaEstado = '';

    public ?int $originalPersonaRoleId = null;
    public ?int $originalGradoId = null;
    public ?int $originalGrupoId = null;

    public Collection $rolesPersona;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public function mount(): void
    {
        $this->rolesPersona = $this->grados = $this->semestres = $this->grupos = collect();
    }

    #[On('editarPersonaNivel')]
    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $detalle = PersonaNivelDetalle::query()
            ->with([
                'cabecera.persona', 'cabecera.nivel', 'personaRole.rolePersona',
                'cicloAsignacion.plantilla.cicloEscolar', 'grado',
                'grupo.generacion', 'grupo.semestre', 'grupo.asignacionGrupo',
            ])->findOrFail($id);

        abort_unless($detalle->cicloAsignacion?->plantilla, 422, 'La asignación no pertenece a una plantilla por ciclo.');

        $cabecera = $detalle->cabecera;
        $plantilla = $detalle->cicloAsignacion->plantilla;
        $persona = $cabecera?->persona;

        $this->detalleId = $detalle->id;
        $this->plantillaId = $plantilla->id;
        $this->cicloEscolarId = $plantilla->ciclo_escolar_id;
        $this->plantillaEstado = $plantilla->estado;
        $this->persona_id = $cabecera?->persona_id;
        $this->nivel_id = $cabecera?->nivel_id;
        $this->persona_role_id = $detalle->persona_role_id;
        $this->grado_id = $detalle->grado_id;
        $this->semestre_id = $detalle->grupo?->semestre_id;
        $this->grupo_id = $detalle->grupo_id;
        $this->ingreso_seg = optional($cabecera?->ingreso_seg)->format('Y-m-d');
        $this->ingreso_sep = optional($cabecera?->ingreso_sep)->format('Y-m-d');
        $this->ingreso_ct = optional($cabecera?->ingreso_ct)->format('Y-m-d');
        $this->nombrePersona = trim(collect([$persona?->titulo, $persona?->nombre, $persona?->apellido_paterno, $persona?->apellido_materno])->filter()->implode(' '));
        $this->nombreNivel = (string) $cabecera?->nivel?->nombre;
        $this->generacionTexto = (string) ($detalle->grupo?->generacion?->etiqueta ?? '');
        $this->originalPersonaRoleId = $detalle->persona_role_id;
        $this->originalGradoId = $detalle->grado_id;
        $this->originalGrupoId = $detalle->grupo_id;
        $this->motivoCambio = $detalle->confirmado ? '' : 'Confirmación de la asignación copiada para el ciclo escolar.';

        $this->rolesPersona = PersonaRole::query()->with('rolePersona')->where('persona_id', $this->persona_id)->orderBy('role_persona_id')->get();
        $this->cargarReglasRol();
        $this->cargarCatalogos();
        $this->cargarGrupos();

        $this->open = true;
        $this->resetValidation();
        $this->dispatch('abrir-modal-editar');
    }

    public function updatedPersonaRoleId(): void
    {
        $this->cargarReglasRol();
        if (!$this->rolPermiteGrupo) {
            $this->grado_id = $this->semestre_id = $this->grupo_id = null;
            $this->generacionTexto = '';
            $this->grupos = collect();
        }
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
        $this->generacionTexto = '';
        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->generacionTexto = '';
        $this->cargarGrupos();
    }

    public function updatedGrupoId($value): void
    {
        $grupo = $value ? $this->grupos->firstWhere('id', (int) $value) : null;
        $this->generacionTexto = (string) ($grupo?->generacion?->etiqueta ?? '');
        if ($grupo && $this->esBachillerato()) {
            $this->grado_id = $grupo->grado_id;
        }
    }

    private function cargarReglasRol(): void
    {
        $personaRol = $this->persona_role_id
            ? $this->rolesPersona->firstWhere('id', (int) $this->persona_role_id)
            : null;
        $rol = $personaRol?->rolePersona;
        $this->rolRequiereGrupo = (bool) $rol?->requiere_grupo;
        $this->rolPermiteGrupo = (bool) ($rol?->requiere_grupo || $rol?->permite_grupo);
    }

    private function cargarCatalogos(): void
    {
        $this->grados = $this->nivel_id
            ? Grado::query()->where('nivel_id', $this->nivel_id)->orderBy('nombre')->get()
            : collect();
        $this->semestres = $this->esBachillerato()
            ? Semestre::query()->with('grado:id,nombre')->whereHas('grado', fn ($q) => $q->where('nivel_id', $this->nivel_id))
                ->orderByRaw('COALESCE(orden_global, 255)')->orderBy('numero')->get()
            : collect();
    }

    private function cargarGrupos(): void
    {
        if (!$this->rolPermiteGrupo || !$this->cicloEscolarId || !$this->nivel_id) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::query()
            ->with(['asignacionGrupo:id,nombre', 'generacion:id,anio_ingreso,anio_egreso,nombre', 'grado:id,nombre', 'semestre:id,grado_id,numero,orden_global'])
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivel_id)
            ->where('estado', 'activo')
            ->when($this->esBachillerato(), fn ($q) => $q->where('semestre_id', $this->semestre_id))
            ->when(!$this->esBachillerato(), fn ($q) => $q->where('grado_id', $this->grado_id)->whereNull('semestre_id'))
            ->orderBy('asignacion_grupo_id')->get();
    }

    private function esBachillerato(): bool
    {
        return Nivel::query()->whereKey($this->nivel_id)->value('slug') === 'bachillerato';
    }

    public function actualizar(PlantillaPersonalCicloService $service): void
    {
        $this->validate([
            'detalleId' => ['required', 'integer', 'exists:persona_nivel_detalles,id'],
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'ingreso_seg' => ['nullable', 'date'],
            'ingreso_sep' => ['nullable', 'date'],
            'ingreso_ct' => ['nullable', 'date'],
            'motivoCambio' => ['nullable', 'string', 'max:1000'],
        ]);

        $detalle = PersonaNivelDetalle::query()
            ->with(['cabecera', 'cicloAsignacion.plantilla.nivel', 'personaRole.rolePersona'])
            ->findOrFail($this->detalleId);
        $plantilla = $detalle->cicloAsignacion->plantilla;
        $personaRole = PersonaRole::query()->with('rolePersona')
            ->whereKey($this->persona_role_id)->where('persona_id', $this->persona_id)->firstOrFail();
        $grupo = $service->validarAsignacion($plantilla, $personaRole->rolePersona, $this->grado_id, $this->grupo_id, $detalle->id);
        $service->bloquearDuplicado($detalle->cicloAsignacion, $personaRole->id, $grupo?->grado_id, $grupo?->id, $detalle->id);

        $cambioAsignacion = (int) $this->originalPersonaRoleId !== (int) $personaRole->id
            || (int) ($this->originalGradoId ?? 0) !== (int) ($grupo?->grado_id ?? 0)
            || (int) ($this->originalGrupoId ?? 0) !== (int) ($grupo?->id ?? 0)
            || !$detalle->confirmado;

        $detalle->cabecera->update([
            'ingreso_seg' => $this->ingreso_seg ?: null,
            'ingreso_sep' => $this->ingreso_sep ?: null,
            'ingreso_ct' => $this->ingreso_ct ?: null,
        ]);

        if ($cambioAsignacion) {
            if (blank($this->motivoCambio)) {
                $this->addError('motivoCambio', 'El motivo es obligatorio cuando cambia la función, grado o grupo.');
                return;
            }

            $service->cerrarYCrearCambio($detalle, [
                'persona_role_id' => $personaRole->id,
                'grado_id' => $grupo?->grado_id,
                'grupo_id' => $grupo?->id,
                'orden' => $detalle->orden,
                'observaciones' => trim($this->motivoCambio),
            ], $this->motivoCambio);
        } else {
            $detalle->update(['confirmado' => true, 'pendiente_motivo' => null]);
        }

        $service->actualizarDiagnostico($plantilla);
        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('plantilla-personal-actualizada');
        $this->dispatch('cerrar-modal-editar');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Asignación actualizada conservando el historial.']);
        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset([
            'open', 'detalleId', 'cicloEscolarId', 'plantillaId', 'persona_id', 'persona_role_id',
            'nivel_id', 'grado_id', 'semestre_id', 'grupo_id', 'ingreso_seg', 'ingreso_sep', 'ingreso_ct',
            'motivoCambio', 'nombrePersona', 'nombreNivel', 'generacionTexto', 'rolPermiteGrupo',
            'rolRequiereGrupo', 'plantillaEstado', 'originalPersonaRoleId', 'originalGradoId', 'originalGrupoId',
        ]);
        $this->rolesPersona = $this->grados = $this->semestres = $this->grupos = collect();
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.persona-nivel.editar-persona-nivel');
    }
}
