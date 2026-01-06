<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;        // CABECERA
use App\Models\PersonaNivelDetalle; // DETALLE
use App\Models\PersonaRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPersonaNivel extends Component
{
    // =========================
    // Identificador del DETALLE
    // =========================
    public ?int $detalleId = null;

    // =========================
    // Paso 1 / Paso 2
    // =========================
    public ?int $persona_id = null;
    public ?int $persona_role_id = null;

    // =========================
    // Selecciones
    // =========================
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    // =========================
    // Colecciones
    // =========================
    public Collection $rolesPersona;
    public Collection $grados;
    public Collection $grupos;

    // =========================
    // Fechas (CABECERA)
    // =========================
    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;

    // =========================
    // UI / Estado
    // =========================
    public bool $open = false;

    // Lógica grado/grupo
    public bool $requiereGradoGrupo = false;        // por rol
    public bool $forzarGradoGrupoPorNivel = false; // por nivel (Secundaria)
    public ?string $rolSlugSeleccionado = null;

    // Roles que NUNCA usan grado/grupo
    public array $rolesSinGradoGrupo = [
        'director_sin_grupo',
    ];

    // Informativos
    public ?string $nombrePersona = null;
    public ?string $nombreNivel = null;
    public ?string $nombreGrado = null;
    public ?string $nombreGrupo = null;

    // =========================
    // Lifecycle
    // =========================
    public function mount(): void
    {
        $this->rolesPersona = collect();
        $this->grados = collect();
        $this->grupos = collect();
    }

    // =========================
    // Helper: ¿nivel es Secundaria?
    // =========================
    public function esSecundaria(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = Nivel::query()->select('id', 'nombre', 'slug')->find($this->nivel_id);
        $slugOrName = strtolower(trim(($nivel?->slug ?? '') ?: ($nivel?->nombre ?? '')));

        return str_contains($slugOrName, 'secundaria') || $slugOrName === 'sec';
    }

    // =========================
    // Helper: mostrar grado/grupo
    // =========================
    public function debeMostrarGradoGrupo(): bool
    {
        // Exclusión total
        if ($this->rolSlugSeleccionado && in_array($this->rolSlugSeleccionado, $this->rolesSinGradoGrupo, true)) {
            return false;
        }

        return $this->requiereGradoGrupo || $this->forzarGradoGrupoPorNivel;
    }

    // =========================
    // Abrir modal (carga DETALLE)
    // =========================
    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $this->dispatch('abrir-modal-editar');

        $detalle = PersonaNivelDetalle::query()
            ->with([
                'cabecera.persona',
                'cabecera.nivel',
                'personaRole.rolePersona',
                'grado',
                'grupo',
            ])
            ->findOrFail($id);

        $cab = $detalle->cabecera;

        $this->detalleId = (int) $detalle->id;

        // Paso 1
        $this->persona_id = (int) ($cab?->persona_id ?? 0) ?: null;

        // Paso 2
        $this->persona_role_id = (int) ($detalle->persona_role_id ?? 0) ?: null;

        // Nivel / Grado / Grupo
        $this->nivel_id = (int) ($cab?->nivel_id ?? 0) ?: null;
        $this->grado_id = $detalle->grado_id ? (int) $detalle->grado_id : null;
        $this->grupo_id = $detalle->grupo_id ? (int) $detalle->grupo_id : null;

        // Fechas (CABECERA) -> se cargan, pero SOLO se editan si NO es secundaria (control en Blade + Guardado)
        $this->ingreso_seg = $cab?->ingreso_seg;
        $this->ingreso_sep = $cab?->ingreso_sep;
        $this->ingreso_ct = $cab?->ingreso_ct;

        // Roles disponibles (como Crear)
        $this->rolesPersona = $this->persona_id
            ? PersonaRole::query()
                ->with('rolePersona')
                ->where('persona_id', $this->persona_id)
                ->orderBy('role_persona_id')
                ->get()
            : collect();

        // Resolver slug del rol seleccionado
        $pr = $this->persona_role_id
            ? $this->rolesPersona->firstWhere('id', (int) $this->persona_role_id)
            : null;

        $this->rolSlugSeleccionado = $pr?->rolePersona?->slug;

        // Roles que normalmente requieren grado/grupo
        $this->requiereGradoGrupo = in_array($this->rolSlugSeleccionado, ['maestro_frente_a_grupo', 'docente'], true);

        // Forzar por nivel (Secundaria)
        $this->resolverForzarPorNivel();

        // Cargar grados/grupos (si aplica)
        if ($this->debeMostrarGradoGrupo()) {
            $this->cargarGrados();
            $this->cargarGrupos();
        } else {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->grupos = collect();
        }

        // Informativos
        $p = $cab?->persona;
        $this->nombrePersona = $p
            ? trim(($p->nombre ?? '') . ' ' . ($p->apellido_paterno ?? '') . ' ' . ($p->apellido_materno ?? ''))
            : null;

        $this->nombreNivel = $cab?->nivel?->nombre;
        $this->nombreGrado = $detalle->grado?->nombre;
        $this->nombreGrupo = $detalle->grupo?->nombre;

        $this->open = true;

        $this->dispatch('editar-cargado');
    }

    // =========================
    // Updates (misma lógica que Crear)
    // =========================
    public function updatedPersonaId($value): void
    {
        $this->persona_role_id = null;
        $this->rolSlugSeleccionado = null;
        $this->requiereGradoGrupo = false;

        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grupos = collect();

        if (!$value) {
            $this->rolesPersona = collect();
            return;
        }

        $this->rolesPersona = PersonaRole::query()
            ->with('rolePersona')
            ->where('persona_id', (int) $value)
            ->orderBy('role_persona_id')
            ->get();

        $this->resetValidation(['persona_role_id']);
    }

    public function seleccionarRol(int $personaRoleId): void
    {
        $this->persona_role_id = $personaRoleId;
        $this->updatedPersonaRoleId($personaRoleId);
    }

    public function updatedPersonaRoleId($value): void
    {
        $this->rolSlugSeleccionado = null;

        $pr = $value
            ? PersonaRole::with('rolePersona')->find((int) $value)
            : null;

        $this->rolSlugSeleccionado = $pr?->rolePersona?->slug;

        // roles que normalmente requieren grado/grupo
        $this->requiereGradoGrupo = in_array($this->rolSlugSeleccionado, ['maestro_frente_a_grupo', 'docente'], true);

        // Exclusión total
        if ($this->rolSlugSeleccionado && in_array($this->rolSlugSeleccionado, $this->rolesSinGradoGrupo, true)) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->grupos = collect();
            $this->resetValidation(['grado_id', 'grupo_id']);
            return;
        }

        if (!$this->debeMostrarGradoGrupo()) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->grupos = collect();
            $this->resetValidation(['grado_id', 'grupo_id']);
            return;
        }

        if ($this->nivel_id) {
            $this->cargarGrados();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grupos = collect();

        if (!$value) {
            $this->grados = collect();
            $this->forzarGradoGrupoPorNivel = false;
            return;
        }

        $this->resolverForzarPorNivel();

        // Si el rol está excluido, nunca mostrar grado/grupo
        if ($this->rolSlugSeleccionado && in_array($this->rolSlugSeleccionado, $this->rolesSinGradoGrupo, true)) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->grupos = collect();
            return;
        }

        if ($this->debeMostrarGradoGrupo()) {
            $this->cargarGrados();
        } else {
            $this->grados = collect();
        }

        $this->resetValidation(['grado_id', 'grupo_id']);
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;

        if (!$value) {
            $this->grupos = collect();
            return;
        }

        $this->cargarGrupos();
        $this->resetValidation(['grupo_id']);
    }

    // =========================
    // Helpers: nivel/grado/grupo
    // =========================
    private function resolverForzarPorNivel(): void
    {
        $this->forzarGradoGrupoPorNivel = $this->esSecundaria();
    }

    private function cargarGrados(): void
    {
        $this->grados = $this->nivel_id
            ? Grado::query()->where('nivel_id', $this->nivel_id)->orderBy('nombre')->get()
            : collect();
    }

    private function cargarGrupos(): void
    {
        $this->grupos = $this->grado_id
            ? Grupo::query()->where('grado_id', $this->grado_id)->orderBy('nombre')->get()
            : collect();
    }

    // =========================
    // Guardar (editando DETALLE)
    // =========================
    public function actualizarPersonal(): void
    {
        $rules = [
            'detalleId' => ['required', 'integer', 'exists:persona_nivel_detalles,id'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'], // ajusta si tu tabla es persona_roles
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],

            'grado_id' => [
                $this->debeMostrarGradoGrupo() ? 'required' : 'nullable',
                'integer',
                'exists:grados,id',
            ],
            'grupo_id' => [
                $this->debeMostrarGradoGrupo() ? 'required' : 'nullable',
                'integer',
                'exists:grupos,id',
            ],
        ];

        // ✅ SOLO si NO es Secundaria: validar fechas (y permitir edición)
        if (!$this->esSecundaria()) {
            $rules['ingreso_seg'] = ['nullable', 'date'];
            $rules['ingreso_sep'] = ['nullable', 'date'];
            $rules['ingreso_ct'] = ['nullable', 'date'];
        }

        $this->validate($rules, [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_role_id.required' => 'Selecciona una función.',
            'nivel_id.required' => 'Selecciona un nivel.',
            'grado_id.required' => 'Selecciona un grado.',
            'grupo_id.required' => 'Selecciona un grupo.',
        ]);

        // Seguridad: persona_role pertenece a la persona
        $prOk = PersonaRole::where('id', $this->persona_role_id)
            ->where('persona_id', $this->persona_id)
            ->exists();

        if (!$prOk) {
            $this->addError('persona_role_id', 'La función seleccionada no pertenece a esta persona.');
            return;
        }

        // pertenencia grado->nivel y grupo->grado
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

        $esSec = $this->esSecundaria();

        try {
            DB::transaction(function () use ($esSec) {
                $detalle = PersonaNivelDetalle::with('cabecera')->lockForUpdate()->findOrFail($this->detalleId);

                $oldCabeceraId = (int) $detalle->persona_nivel_id;
                $oldNivelId = (int) ($detalle->cabecera?->nivel_id ?? 0);
                $newNivelId = (int) $this->nivel_id;

                // 1) CABECERA destino (persona + nivel)
                $cabecera = PersonaNivel::firstOrCreate(
                    [
                        'persona_id' => $this->persona_id,
                        'nivel_id' => $this->nivel_id,
                    ]
                );

                // ✅ SOLO si NO es Secundaria: actualizar fechas
                if (!$esSec) {
                    $cabecera->update([
                        'ingreso_seg' => $this->ingreso_seg,
                        'ingreso_sep' => $this->ingreso_sep,
                        'ingreso_ct' => $this->ingreso_ct,
                    ]);
                }

                // 2) Evitar duplicado exacto en DETALLES
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
                    ->where('id', '!=', $detalle->id)
                    ->exists();

                if ($dup) {
                    throw new \RuntimeException('DUPLICADO');
                }

                // 3) Orden: si cambia de NIVEL, mandar al final del nuevo nivel
                $newOrden = (int) $detalle->orden;

                if ($oldNivelId !== $newNivelId) {
                    $maxOrden = PersonaNivelDetalle::query()
                        ->whereHas('cabecera', fn($q) => $q->where('nivel_id', $newNivelId))
                        ->max('orden');

                    $newOrden = ((int) ($maxOrden ?? 0)) + 1;
                }

                // 4) Update del DETALLE
                $detalle->update([
                    'persona_nivel_id' => $cabecera->id,
                    'persona_role_id' => $this->persona_role_id,
                    'grado_id' => $this->debeMostrarGradoGrupo() ? $this->grado_id : null,
                    'grupo_id' => $this->debeMostrarGradoGrupo() ? $this->grupo_id : null,
                    'orden' => $newOrden,
                ]);

                // 5) Si cabecera anterior queda vacía, borrarla
                if ($oldCabeceraId !== (int) $cabecera->id) {
                    $tieneMas = PersonaNivelDetalle::where('persona_nivel_id', $oldCabeceraId)->exists();
                    if (!$tieneMas) {
                        PersonaNivel::whereKey($oldCabeceraId)->delete();
                    }
                }

                // 6) Normalizar orden por nivel (viejo y nuevo)
                $nivelesAjustar = array_values(array_unique(array_filter([$oldNivelId, $newNivelId])));

                foreach ($nivelesAjustar as $nivelId) {
                    $all = PersonaNivelDetalle::query()
                        ->whereHas('cabecera', fn($q) => $q->where('nivel_id', $nivelId))
                        ->orderBy('orden')
                        ->orderBy('id')
                        ->pluck('id')
                        ->all();

                    $i = 1;
                    foreach ($all as $pid) {
                        PersonaNivelDetalle::whereKey($pid)->update(['orden' => $i++]);
                    }
                }
            });

            $this->dispatch('swal', [
                'title' => 'Asignación actualizada correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);

            $this->dispatch('refreshPersonaNivelList');
            $this->dispatch('cerrar-modal-editar');
            $this->cerrarModal();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICADO') {
                $this->addError('persona_role_id', 'Ya existe esa asignación (rol/grado/grupo) en el nivel destino.');
                return;
            }

            throw $e;
        }
    }

    // =========================
    // UI helpers
    // =========================
    public function cerrarModal(): void
    {
        $this->reset([
            'open',
            'detalleId',
            'persona_id',
            'persona_role_id',
            'nivel_id',
            'grado_id',
            'grupo_id',
            'ingreso_seg',
            'ingreso_sep',
            'ingreso_ct',
            'requiereGradoGrupo',
            'forzarGradoGrupoPorNivel',
            'rolSlugSeleccionado',
            'nombrePersona',
            'nombreNivel',
            'nombreGrado',
            'nombreGrupo',
        ]);

        $this->rolesPersona = collect();
        $this->grados = collect();
        $this->grupos = collect();

        $this->resetValidation();
    }

    public function render()
    {
        $personas = Persona::query()
            ->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        return view('livewire.persona-nivel.editar-persona-nivel', [
            'personas' => $personas,
            'niveles' => $niveles,
            'rolesPersona' => $this->rolesPersona,
        ]);
    }
}
