<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\PersonaRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CrearPersonaNivel extends Component
{
    // ✅ esto es persona_roles.id (porque el select manda $roles->id)
    public ?int $persona_role_id = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    public $PersonasRoles = [];
    public $niveles = [];

    public $grados;
    public $grupos;

    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct  = null;

    // ✅ UI + lógica
    public bool $requiereGradoGrupo = false;
    public ?string $rolSlugSeleccionado = null;

    public function mount(): void
    {
        // ✅ NO usar unique('persona_id') si quieres seleccionar el rol exacto
        $this->PersonasRoles = PersonaRole::with(['persona', 'rolePersona'])
            ->orderBy('persona_id')
            ->orderBy('role_persona_id')
            ->get();

        $this->niveles = Nivel::orderBy('id')->get();

        $this->grados = collect();
        $this->grupos = collect();
    }

    public function updatedPersonaRoleId($value): void
    {
        $this->rolSlugSeleccionado = null;

        $pr = $value
            ? PersonaRole::with('rolePersona')->find((int) $value)
            : null;

        $slug = $pr?->rolePersona?->slug;
        $this->rolSlugSeleccionado = $slug;

        // ✅ SOLO maestro_frente_a_grupo y docente REQUIEREN grado/grupo
        $this->requiereGradoGrupo = in_array($slug, ['maestro_frente_a_grupo', 'docente'], true);

        // ✅ si NO aplica, limpia y quita errores rojos
        if (! $this->requiereGradoGrupo) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grados   = collect();
            $this->grupos   = collect();

            $this->resetValidation(['grado_id', 'grupo_id']);
        }

        // Si ya había nivel seleccionado y ahora sí requiere, recarga grados
        if ($this->requiereGradoGrupo && $this->nivel_id) {
            $this->grados = Grado::where('nivel_id', $this->nivel_id)->orderBy('nombre')->get();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grupos = collect();

        if (! $value || ! $this->requiereGradoGrupo) {
            $this->grados = collect();
            return;
        }

        $this->grados = Grado::where('nivel_id', $value)
            ->orderBy('nombre')
            ->get();

        $this->resetValidation(['grado_id', 'grupo_id']);
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;

        if (! $value || ! $this->requiereGradoGrupo) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::where('grado_id', $value)
            ->orderBy('nombre')
            ->get();

        $this->resetValidation(['grupo_id']);
    }

    public function asignarPersonalNivel(): void
    {
        // ✅ recalcular SIEMPRE el slug real (no confiar en banderas viejas)
        $pr = $this->persona_role_id
            ? PersonaRole::with('rolePersona')->find((int) $this->persona_role_id)
            : null;

        $slug = $pr?->rolePersona?->slug;
        $requiereGradoGrupo = in_array($slug, ['maestro_frente_a_grupo', 'docente'], true);

        $this->validate([
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'],
            'nivel_id'        => ['required', 'integer', 'exists:niveles,id'],

            'grado_id'        => [
                Rule::requiredIf($requiereGradoGrupo),
                'nullable',
                'integer',
                'exists:grados,id',
            ],
            'grupo_id'        => [
                Rule::requiredIf($requiereGradoGrupo),
                'nullable',
                'integer',
                'exists:grupos,id',
            ],

            'ingreso_seg'     => ['nullable', 'date'],
            'ingreso_sep'     => ['nullable', 'date'],
            'ingreso_ct'      => ['nullable', 'date'],
        ], [
            'persona_role_id.required' => 'El campo Personal es obligatorio.',
            'persona_role_id.exists'   => 'El personal seleccionado no es válido.',
            'nivel_id.required'        => 'El campo Nivel es obligatorio.',
            'nivel_id.exists'          => 'El nivel seleccionado no es válido.',
            'grado_id.required'        => 'El campo Grado es obligatorio para este rol.',
            'grupo_id.required'        => 'El campo Grupo es obligatorio para este rol.',
        ]);

        // ✅ si NO requiere, fuerza null para evitar valores arrastrados
        if (! $requiereGradoGrupo) {
            $this->grado_id = null;
            $this->grupo_id = null;
        }

        // ✅ persona_id real para guardar en persona_nivel
        $personaRealId = (int) $pr->persona_id;

        // ✅ existe asignación (considerando NULLs)
        $qBase = PersonaNivel::query()
            ->where('persona_id', $personaRealId)
            ->where('nivel_id', $this->nivel_id)
            ->when($this->grado_id === null, fn ($q) => $q->whereNull('grado_id'), fn ($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id === null, fn ($q) => $q->whereNull('grupo_id'), fn ($q) => $q->where('grupo_id', $this->grupo_id));

        if ($qBase->exists()) {
            $this->dispatch('swal', [
                'title' => 'Esta persona ya está asignada a esta combinación (nivel/grado/grupo).',
                'icon' => 'warning',
                'position' => 'top',
            ]);
            return;
        }

        // ✅ existe otro en el mismo slot (considerando NULLs)
        $qSlot = PersonaNivel::query()
            ->where('nivel_id', $this->nivel_id)
            ->when($this->grado_id === null, fn ($q) => $q->whereNull('grado_id'), fn ($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id === null, fn ($q) => $q->whereNull('grupo_id'), fn ($q) => $q->whereNull('grupo_id'), fn ($q) => $q->where('grupo_id', $this->grupo_id))
            ->where('persona_id', '!=', $personaRealId);

        if ($qSlot->exists()) {
            $this->dispatch('swal', [
                'title' => 'Ya existe otra persona asignada a esa combinación (nivel/grado/grupo).',
                'icon' => 'warning',
                'position' => 'top',
            ]);
            return;
        }

        DB::transaction(function () use ($personaRealId) {
            $maxOrden = PersonaNivel::query()
                ->where('nivel_id', $this->nivel_id)
                ->max('orden');

            $nuevoOrden = ((int) ($maxOrden ?? 0)) + 1;

            PersonaNivel::create([
                'persona_id'  => $personaRealId,
                'nivel_id'    => $this->nivel_id,
                'grado_id'    => $this->grado_id,
                'grupo_id'    => $this->grupo_id,
                'ingreso_seg' => $this->ingreso_seg,
                'ingreso_sep' => $this->ingreso_sep,
                'ingreso_ct'  => $this->ingreso_ct,
                'orden'       => $nuevoOrden,
            ]);
        });

        $this->dispatch('swal', [
            'title' => 'Asignación exitosa',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonaNivelList');

        $this->reset([
            'persona_role_id', 'nivel_id', 'grado_id', 'grupo_id',
            'ingreso_seg', 'ingreso_sep', 'ingreso_ct',
        ]);

        $this->requiereGradoGrupo = false;
        $this->rolSlugSeleccionado = null;

        $this->grados = collect();
        $this->grupos = collect();
    }

    public function render()
    {
        return view('livewire.persona-nivel.crear-persona-nivel');
    }
}
