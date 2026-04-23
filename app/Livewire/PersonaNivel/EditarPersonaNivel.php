<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
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
    public bool $requiereGradoGrupo = false;
    public bool $forzarGradoGrupoPorNivel = false;
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
    // Helper: limpiar fecha
    // Si viene vacía, se guarda como null
    // =========================
    private function limpiarFecha(?string $fecha): ?string
    {
        $fecha = is_string($fecha) ? trim($fecha) : $fecha;

        return $fecha === '' ? null : $fecha;
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
    // Helper: ¿nivel es Bachillerato?
    // =========================
    public function esBachillerato(): bool
    {
        return (int) $this->nivel_id === 4;
    }

    // =========================
    // Helper: mostrar grado/grupo
    // =========================
    public function debeMostrarGradoGrupo(): bool
    {
        // Exclusión total por rol
        if ($this->rolSlugSeleccionado && in_array($this->rolSlugSeleccionado, $this->rolesSinGradoGrupo, true)) {
            return false;
        }

        // En bachillerato sí se muestra el bloque,
        // pero grado y grupo quedan null al guardar
        if ($this->esBachillerato()) {
            return true;
        }

        return $this->requiereGradoGrupo || $this->forzarGradoGrupoPorNivel;
    }

    // =========================
    // Abrir modal
    // =========================
    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $detalle = PersonaNivelDetalle::query()
            ->with([
                'cabecera.nivel:id,nombre,slug',
                'cabecera.persona:id,titulo,nombre,apellido_paterno,apellido_materno',
                'grado:id,nombre,nivel_id',
                'grupo:id,nombre,grado_id',
            ])
            ->findOrFail($id);

        $cab = $detalle->cabecera;
        $persona = $cab?->persona;

        $this->detalleId = $detalle->id;

        $this->persona_id = (int) ($cab?->persona_id ?? 0) ?: null;
        $this->persona_role_id = (int) ($detalle->persona_role_id ?? 0) ?: null;
        $this->nivel_id = (int) ($cab?->nivel_id ?? 0) ?: null;
        $this->grado_id = $detalle->grado_id ? (int) $detalle->grado_id : null;
        $this->grupo_id = $detalle->grupo_id ? (int) $detalle->grupo_id : null;

        // En bachillerato no se usará grupo
        if ($this->esBachillerato()) {
            $this->grupo_id = null;
        }

        $this->ingreso_seg = $cab?->ingreso_seg;
        $this->ingreso_sep = $cab?->ingreso_sep;
        $this->ingreso_ct = $cab?->ingreso_ct;

        $this->nombrePersona = trim(
            collect([
                $persona?->titulo,
                $persona?->nombre,
                $persona?->apellido_paterno,
                $persona?->apellido_materno,
            ])->filter()->implode(' ')
        );

        $this->nombreNivel = $cab?->nivel?->nombre;
        $this->nombreGrado = $detalle->grado?->nombre;
        $this->nombreGrupo = $detalle->grupo?->nombre;

        $this->cargarRolesPersona();
        $this->resolverRequierePorRol();
        $this->resolverForzarPorNivel();

        if ($this->debeMostrarGradoGrupo()) {
            $this->cargarGrados();

            if ($this->grado_id && !$this->esBachillerato()) {
                $this->cargarGrupos();
            } else {
                $this->grupos = collect();
            }
        } else {
            $this->grados = collect();
            $this->grupos = collect();
        }

        $this->open = true;

        $this->dispatch('abrir-modal-editar');
        $this->dispatch('editar-cargado');
    }

    // =========================
    // Cambios reactivos
    // =========================
    public function updatedPersonaId($value): void
    {
        $this->persona_role_id = null;
        $this->rolSlugSeleccionado = null;
        $this->requiereGradoGrupo = false;

        if (!$value) {
            $this->rolesPersona = collect();
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->grupos = collect();
            return;
        }

        $this->cargarRolesPersona();

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

    public function seleccionarRol(int $personaRoleId): void
    {
        $this->persona_role_id = $personaRoleId;
        $this->updatedPersonaRoleId($personaRoleId);
    }

    public function updatedPersonaRoleId($value): void
    {
        $pr = $value
            ? PersonaRole::query()->with('rolePersona')->find((int) $value)
            : null;

        $this->rolSlugSeleccionado = $pr?->rolePersona?->slug;
        $this->resolverRequierePorRol();

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

            if ($this->esBachillerato()) {
                $this->grado_id = null;
                $this->grupo_id = null;
                $this->grupos = collect();
            }
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

        // En bachillerato no se usa grupo y grado puede quedar null
        if ((int) $value === 4) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->grupos = collect();
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

        // En bachillerato no cargar grupos
        if ($this->esBachillerato()) {
            $this->grupos = collect();
            return;
        }

        $this->cargarGrupos();
        $this->resetValidation(['grupo_id']);
    }

    // =========================
    // Helpers internos
    // =========================
    private function cargarRolesPersona(): void
    {
        $this->rolesPersona = $this->persona_id
            ? PersonaRole::query()
            ->with('rolePersona')
            ->where('persona_id', $this->persona_id)
            ->orderBy('role_persona_id')
            ->get()
            : collect();
    }

    private function resolverRequierePorRol(): void
    {
        $this->requiereGradoGrupo = false;

        if (!$this->persona_role_id) {
            return;
        }

        $pr = PersonaRole::query()
            ->with('rolePersona:id,nombre,slug')
            ->find($this->persona_role_id);

        $slug = $pr?->rolePersona?->slug;
        $this->rolSlugSeleccionado = $slug;

        if (!$slug) {
            $this->requiereGradoGrupo = false;
            return;
        }

        // Ajusta aquí los slugs que sí requieren grado/grupo
        $rolesQueRequieren = [
            'director_con_grupo',
            'docente',
            'docente_titular',
            'docente_grupo',
            'prefecto',
            'coordinador_grupo',
        ];

        $this->requiereGradoGrupo = in_array($slug, $rolesQueRequieren, true);
    }

    private function resolverForzarPorNivel(): void
    {
        // Solo secundaria fuerza grado/grupo
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
        // En bachillerato grado y grupo deben quedar null
        if ($this->esBachillerato()) {
            $this->grado_id = null;
            $this->grupo_id = null;
        }

        // Limpiar fechas vacías para que se guarden como null
        $this->ingreso_seg = $this->limpiarFecha($this->ingreso_seg);
        $this->ingreso_sep = $this->limpiarFecha($this->ingreso_sep);
        $this->ingreso_ct = $this->limpiarFecha($this->ingreso_ct);

        $rules = [
            'detalleId' => ['required', 'integer', 'exists:persona_nivel_detalles,id'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'],
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],

            'grado_id' => [
                ($this->debeMostrarGradoGrupo() && !$this->esBachillerato()) ? 'required' : 'nullable',
                'integer',
                'exists:grados,id',
            ],

            'grupo_id' => [
                'nullable',
                'integer',
                'exists:grupos,id',
                function ($attribute, $value, $fail) {
                    if (!$this->debeMostrarGradoGrupo()) {
                        return;
                    }

                    if ($this->esBachillerato() && !is_null($value)) {
                        $fail('En bachillerato no se debe seleccionar grupo.');
                        return;
                    }

                    if (!$this->esBachillerato() && !empty($this->grado_id) && empty($value)) {
                        $fail('Selecciona un grupo.');
                    }
                },
            ],
        ];

        // Solo si no es secundaria: validar fechas
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
        ]);

        // Seguridad: persona_role pertenece a la persona
        $prOk = PersonaRole::where('id', $this->persona_role_id)
            ->where('persona_id', $this->persona_id)
            ->exists();

        if (!$prOk) {
            $this->addError('persona_role_id', 'La función seleccionada no pertenece a esta persona.');
            return;
        }

        // pertenencia grado -> nivel
        if ($this->grado_id) {
            $gradoOk = Grado::where('id', $this->grado_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (!$gradoOk) {
                $this->addError('grado_id', 'El grado no pertenece al nivel.');
                return;
            }
        }

        // pertenencia grupo -> grado
        if (!$this->esBachillerato() && $this->grupo_id) {
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
                $detalle = PersonaNivelDetalle::query()
                    ->with('cabecera')
                    ->findOrFail($this->detalleId);

                $oldCabeceraId = (int) $detalle->persona_nivel_id;
                $oldNivelId = (int) ($detalle->cabecera?->nivel_id ?? 0);
                $newNivelId = (int) $this->nivel_id;

                $ingresoSeg = $this->limpiarFecha($this->ingreso_seg);
                $ingresoSep = $this->limpiarFecha($this->ingreso_sep);
                $ingresoCt = $this->limpiarFecha($this->ingreso_ct);

                // Buscar o crear nueva cabecera
                $cabecera = PersonaNivel::firstOrCreate(
                    [
                        'persona_id' => $this->persona_id,
                        'nivel_id' => $this->nivel_id,
                    ],
                    [
                        'ingreso_seg' => $ingresoSeg,
                        'ingreso_sep' => $ingresoSep,
                        'ingreso_ct' => $ingresoCt,
                    ]
                );

                // Si no es secundaria, actualizar fechas de cabecera
                if (!$this->esSecundaria()) {
                    $cabecera->update([
                        'ingreso_seg' => $ingresoSeg,
                        'ingreso_sep' => $ingresoSep,
                        'ingreso_ct' => $ingresoCt,
                    ]);
                }

                // Evitar duplicado
                $dup = PersonaNivelDetalle::query()
                    ->where('persona_nivel_id', $cabecera->id)
                    ->where('persona_role_id', $this->persona_role_id)
                    ->when(
                        $this->debeMostrarGradoGrupo()
                            ? $this->grado_id === null
                            : true,
                        fn($q) => $q->whereNull('grado_id'),
                        fn($q) => $q->where('grado_id', $this->grado_id)
                    )
                    ->when(
                        ($this->debeMostrarGradoGrupo() && !$this->esBachillerato())
                            ? $this->grupo_id === null
                            : true,
                        fn($q) => $q->whereNull('grupo_id'),
                        fn($q) => $q->where('grupo_id', $this->grupo_id)
                    )
                    ->where('id', '!=', $detalle->id)
                    ->exists();

                if ($dup) {
                    throw new \RuntimeException('DUPLICADO');
                }

                // Si cambia de nivel, mandar al final del nuevo nivel
                $newOrden = (int) $detalle->orden;

                if ($oldNivelId !== $newNivelId) {
                    $maxOrden = PersonaNivelDetalle::query()
                        ->whereHas('cabecera', fn($q) => $q->where('nivel_id', $newNivelId))
                        ->max('orden');

                    $newOrden = ((int) ($maxOrden ?? 0)) + 1;
                }

                // Update del detalle
                $detalle->update([
                    'persona_nivel_id' => $cabecera->id,
                    'persona_role_id' => $this->persona_role_id,
                    'grado_id' => ($this->debeMostrarGradoGrupo() && !$this->esBachillerato()) ? $this->grado_id : null,
                    'grupo_id' => ($this->debeMostrarGradoGrupo() && !$this->esBachillerato()) ? $this->grupo_id : null,
                    'orden' => $newOrden,
                ]);

                // Si la cabecera anterior queda vacía, borrarla
                if ($oldCabeceraId !== (int) $cabecera->id) {
                    $tieneMas = PersonaNivelDetalle::where('persona_nivel_id', $oldCabeceraId)->exists();

                    if (!$tieneMas) {
                        PersonaNivel::whereKey($oldCabeceraId)->delete();
                    }
                }

                // Normalizar orden por nivel
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
