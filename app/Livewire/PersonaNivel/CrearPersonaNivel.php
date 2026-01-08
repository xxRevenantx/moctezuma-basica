<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaRole;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CrearPersonaNivel extends Component
{
    // Paso 1
    public ?int $persona_id = null;

    // Paso 2 (chip seleccionado)
    public ?int $persona_role_id = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    public $personas = [];
    public $rolesPersona; // collection

    public $niveles = [];
    public $grados;
    public $grupos;

    // Fechas (CABECERA)
    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;

    // UI informativa
    public ?string $rolSlugSeleccionado = null;

    // ✅ NUEVO: bloquear fechas si ya existe cabecera en secundaria
    public bool $bloquearFechasSecundaria = false;
    public bool $existeCabecera = false;

    public function mount(): void
    {
        $this->personas = Persona::query()
            ->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno')
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->get();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        $this->rolesPersona = collect();
        $this->grados = collect();
        $this->grupos = collect();
    }

    public function updatedPersonaId($value): void
    {
        $this->persona_role_id = null;
        $this->rolSlugSeleccionado = null;

        if (!$value) {
            $this->rolesPersona = collect();
            $this->resetBloqueoFechas();
            return;
        }

        $this->rolesPersona = PersonaRole::query()
            ->with('rolePersona')
            ->where('persona_id', (int) $value)
            ->orderBy('role_persona_id')
            ->get();


        $this->resetValidation(['persona_role_id']);

        // ✅ reevaluar bloqueo si ya hay nivel
        $this->evaluarBloqueoFechas();
    }

    public function seleccionarRol(int $personaRoleId): void
    {
        $this->persona_role_id = $personaRoleId;
        $this->updatedPersonaRoleId($personaRoleId);
    }

    public function updatedPersonaRoleId($value): void
    {
        $pr = $value ? PersonaRole::with('rolePersona')->find((int) $value) : null;
        $this->rolSlugSeleccionado = $pr?->rolePersona?->slug;

        if ($this->nivel_id && $this->grados->isEmpty()) {
            $this->grados = Grado::where('nivel_id', $this->nivel_id)->orderBy('nombre')->get();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grupos = collect();

        if (!$value) {
            $this->grados = collect();
            $this->resetBloqueoFechas();
            return;
        }

        $this->grados = Grado::where('nivel_id', $value)
            ->orderBy('nombre')
            ->get();

        $this->resetValidation(['grado_id', 'grupo_id']);

        // ✅ evaluar bloqueo de fechas al cambiar nivel
        $this->evaluarBloqueoFechas();
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;

        if (!$value) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::where('grado_id', $value)
            ->orderBy('nombre')
            ->get();

        $this->resetValidation(['grupo_id']);
    }

    /**
     * ✅ Bloquea fechas SOLO si:
     * - nivel seleccionado es "secundaria"
     * - ya existe PersonaNivel (cabecera) para persona+nivel
     */
    private function evaluarBloqueoFechas(): void
    {
        $this->bloquearFechasSecundaria = false;
        $this->existeCabecera = false;

        if (!$this->persona_id || !$this->nivel_id) {
            return;
        }

        $nivel = Nivel::select('id', 'slug')->find($this->nivel_id);
        if (!$nivel)
            return;

        $cabecera = PersonaNivel::query()
            ->where('persona_id', $this->persona_id)
            ->where('nivel_id', $this->nivel_id)
            ->first();

        $this->existeCabecera = (bool) $cabecera;

        // Solo secundaria
        if ($nivel->slug === 'secundaria' && $cabecera) {
            $this->bloquearFechasSecundaria = true;

            // ✅ mostrar fechas existentes y evitar edición
            $this->ingreso_seg = $cabecera->ingreso_seg;
            $this->ingreso_sep = $cabecera->ingreso_sep;
            $this->ingreso_ct = $cabecera->ingreso_ct;
        }
    }

    private function resetBloqueoFechas(): void
    {
        $this->bloquearFechasSecundaria = false;
        $this->existeCabecera = false;
    }

    public function asignarPersonalNivel(): void
    {
        $this->validate([
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'], // ajusta a persona_roles si aplica
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],

            'grado_id' => ['nullable', 'integer', 'exists:grados,id', 'required_with:grupo_id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id', 'required_with:grado_id'],

            'ingreso_seg' => ['nullable', 'date'],
            'ingreso_sep' => ['nullable', 'date'],
            'ingreso_ct' => ['nullable', 'date'],
        ], [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_role_id.required' => 'Selecciona una función.',
            'nivel_id.required' => 'Selecciona un nivel.',
            'grado_id.required_with' => 'Si seleccionas Grupo, también debes seleccionar Grado.',
            'grupo_id.required_with' => 'Si seleccionas Grado, también debes seleccionar Grupo.',
        ]);

        $prOk = PersonaRole::where('id', $this->persona_role_id)
            ->where('persona_id', $this->persona_id)
            ->exists();

        if (!$prOk) {
            $this->addError('persona_role_id', 'La función seleccionada no pertenece a esta persona.');
            return;
        }

        if ($this->grado_id) {
            $gradoOk = Grado::where('id', $this->grado_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (!$gradoOk) {
                $this->addError('grado_id', 'El grado no pertenece al nivel.');
                return;
            }
        }

        if ($this->grupo_id) {
            $grupoOk = Grupo::where('id', $this->grupo_id)
                ->where('grado_id', $this->grado_id)
                ->exists();

            if (!$grupoOk) {
                $this->addError('grupo_id', 'El grupo no pertenece al grado.');
                return;
            }
        }

        try {
            DB::transaction(function () {
                // 1) CABECERA
                $cabecera = PersonaNivel::firstOrCreate(
                    [
                        'persona_id' => $this->persona_id,
                        'nivel_id' => $this->nivel_id,
                    ],
                    [
                        'ingreso_seg' => $this->ingreso_seg,
                        'ingreso_sep' => $this->ingreso_sep,
                        'ingreso_ct' => $this->ingreso_ct,
                    ]
                );

                // ✅ si NO está bloqueado, rellena fechas vacías
                if (!$this->bloquearFechasSecundaria) {
                    $updates = [];
                    if (empty($cabecera->ingreso_seg) && !empty($this->ingreso_seg))
                        $updates['ingreso_seg'] = $this->ingreso_seg;
                    if (empty($cabecera->ingreso_sep) && !empty($this->ingreso_sep))
                        $updates['ingreso_sep'] = $this->ingreso_sep;
                    if (empty($cabecera->ingreso_ct) && !empty($this->ingreso_ct))
                        $updates['ingreso_ct'] = $this->ingreso_ct;

                    if (!empty($updates))
                        $cabecera->update($updates);
                }

                // 2) DETALLE
                $dup = PersonaNivelDetalle::query()
                    ->where('persona_nivel_id', $cabecera->id)
                    ->where('persona_role_id', $this->persona_role_id)
                    ->when(
                        $this->grado_id === null,
                        fn($q) => $q->whereNull('grado_id'),
                        fn($q) => $q->where('grado_id', $this->grado_id)
                    )
                    ->when(
                        $this->grupo_id === null,
                        fn($q) => $q->whereNull('grupo_id'),
                        fn($q) => $q->where('grupo_id', $this->grupo_id)
                    )
                    ->exists();

                if ($dup) {
                    throw new \RuntimeException('DUPLICADO');
                }

                $maxOrden = PersonaNivelDetalle::query()
                    ->whereHas('cabecera', fn($q) => $q->where('nivel_id', $this->nivel_id))
                    ->max('orden');

                $nuevoOrden = ((int) ($maxOrden ?? 0)) + 1;

                PersonaNivelDetalle::create([
                    'persona_nivel_id' => $cabecera->id,
                    'persona_role_id' => $this->persona_role_id,
                    'grado_id' => $this->grado_id,
                    'grupo_id' => $this->grupo_id,
                    'orden' => $nuevoOrden,
                ]);
            });

        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICADO') {
                $this->dispatch('swal', [
                    'title' => 'Esta asignación ya existe.',
                    'icon' => 'warning',
                    'position' => 'top',
                ]);
                return;
            }
            throw $e;
        }

        $this->dispatch('swal', [
            'title' => 'Asignación exitosa',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonaNivelList');

        // UX: limpiar solo detalle (mantener persona/nivel para seguir agregando roles)
        $this->reset(['persona_role_id', 'grado_id', 'grupo_id']);

        // ✅ si fechas están bloqueadas, NO las limpies (para que sigan visibles)
        if (!$this->bloquearFechasSecundaria) {
            $this->reset(['ingreso_seg', 'ingreso_sep', 'ingreso_ct']);
        }

        $this->rolSlugSeleccionado = null;
        $this->grupos = collect();

        // Re-evaluar por si el firstOrCreate acaba de crear la cabecera
        $this->evaluarBloqueoFechas();
    }

    public function render()
    {
        return view('livewire.persona-nivel.crear-persona-nivel', [
            'rolesPersona' => $this->rolesPersona,
        ]);
    }
}
