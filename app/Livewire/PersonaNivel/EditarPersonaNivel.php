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
    public ?int $detalleId = null;

    public ?int $persona_id = null;
    public ?int $persona_role_id = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    public Collection $rolesPersona;
    public Collection $grados;
    public Collection $grupos;

    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;

    public bool $open = false;
    public bool $requiereGradoGrupo = false;
    public bool $forzarGradoGrupoPorNivel = false;
    public ?string $rolSlugSeleccionado = null;

    public array $rolesSinGradoGrupo = [
        'director_sin_grupo',
    ];

    public ?string $nombrePersona = null;
    public ?string $nombreNivel = null;
    public ?string $nombreGrado = null;
    public ?string $nombreGrupo = null;

    public function mount(): void
    {
        $this->rolesPersona = collect();
        $this->grados = collect();
        $this->grupos = collect();
    }

    private function limpiarFecha(?string $fecha): ?string
    {
        $fecha = is_string($fecha) ? trim($fecha) : $fecha;

        return $fecha === '' ? null : $fecha;
    }

    public function esSecundaria(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->find($this->nivel_id);

        $slugOrName = strtolower(trim(($nivel?->slug ?? '') ?: ($nivel?->nombre ?? '')));

        return str_contains($slugOrName, 'secundaria') || $slugOrName === 'sec';
    }

    public function esBachillerato(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->find($this->nivel_id);

        $slugOrName = strtolower(trim(($nivel?->slug ?? '') ?: ($nivel?->nombre ?? '')));

        return (int) $this->nivel_id === 4 || str_contains($slugOrName, 'bachillerato');
    }

    public function debeMostrarGradoGrupo(): bool
    {
        if ($this->esBachillerato()) {
            return false;
        }

        if ($this->rolSlugSeleccionado && in_array($this->rolSlugSeleccionado, $this->rolesSinGradoGrupo, true)) {
            return false;
        }

        return $this->requiereGradoGrupo || $this->forzarGradoGrupoPorNivel;
    }

    public function nombreGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $detalle = PersonaNivelDetalle::query()
            ->with([
                'cabecera.nivel:id,nombre,slug',
                'cabecera.persona:id,titulo,nombre,apellido_paterno,apellido_materno',
                'grado:id,nombre,nivel_id',
                'grupo' => function ($query) {
                    $query->select('id', 'asignacion_grupo_id', 'nivel_id', 'grado_id')
                        ->with('asignacionGrupo:id,nombre');
                },
            ])
            ->findOrFail($id);

        $cabecera = $detalle->cabecera;
        $persona = $cabecera?->persona;

        $this->detalleId = $detalle->id;
        $this->persona_id = (int) ($cabecera?->persona_id ?? 0) ?: null;
        $this->persona_role_id = (int) ($detalle->persona_role_id ?? 0) ?: null;
        $this->nivel_id = (int) ($cabecera?->nivel_id ?? 0) ?: null;
        $this->grado_id = $detalle->grado_id ? (int) $detalle->grado_id : null;
        $this->grupo_id = $detalle->grupo_id ? (int) $detalle->grupo_id : null;

        if ($this->esBachillerato()) {
            $this->grado_id = null;
            $this->grupo_id = null;
        }

        $this->ingreso_seg = $this->esBachillerato() ? null : $cabecera?->ingreso_seg;
        $this->ingreso_sep = $this->esBachillerato() ? null : $cabecera?->ingreso_sep;
        $this->ingreso_ct = $cabecera?->ingreso_ct;

        $this->nombrePersona = trim(
            collect([
                $persona?->titulo,
                $persona?->nombre,
                $persona?->apellido_paterno,
                $persona?->apellido_materno,
            ])->filter()->implode(' ')
        );

        $this->nombreNivel = $cabecera?->nivel?->nombre;
        $this->nombreGrado = $this->esBachillerato() ? null : $detalle->grado?->nombre;
        $this->nombreGrupo = $this->esBachillerato() ? null : $this->nombreGrupo($detalle->grupo);

        $this->cargarRolesPersona();
        $this->resolverRequierePorRol();
        $this->resolverForzarPorNivel();

        if ($this->debeMostrarGradoGrupo()) {
            $this->cargarGrados();

            if ($this->grado_id) {
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

    public function updatedPersonaId($value): void
    {
        $this->persona_role_id = null;
        $this->rolSlugSeleccionado = null;
        $this->requiereGradoGrupo = false;

        if (!$value) {
            $this->rolesPersona = collect();
            $this->limpiarGradoGrupo();
            return;
        }

        $this->cargarRolesPersona();

        if (!$this->debeMostrarGradoGrupo()) {
            $this->limpiarGradoGrupo();
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
        $personaRole = $value
            ? PersonaRole::query()->with('rolePersona')->find((int) $value)
            : null;

        $this->rolSlugSeleccionado = $personaRole?->rolePersona?->slug;
        $this->resolverRequierePorRol();

        if (!$this->debeMostrarGradoGrupo()) {
            $this->limpiarGradoGrupo();
            return;
        }

        if ($this->nivel_id) {
            $this->cargarGrados();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->limpiarGradoGrupo();
        $this->ingreso_seg = null;
        $this->ingreso_sep = null;

        if (!$value) {
            $this->forzarGradoGrupoPorNivel = false;
            return;
        }

        $nivel = Nivel::query()
            ->select('id', 'nombre')
            ->find((int) $value);

        $this->nombreNivel = $nivel?->nombre;

        $this->resolverForzarPorNivel();

        if ($this->esBachillerato()) {
            $this->limpiarGradoGrupo();
            $this->resetValidation(['grado_id', 'grupo_id', 'ingreso_seg', 'ingreso_sep']);
            return;
        }

        if ($this->debeMostrarGradoGrupo()) {
            $this->cargarGrados();
        }

        $this->resetValidation(['grado_id', 'grupo_id']);
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;
        $this->grupos = collect();

        if (!$value || $this->esBachillerato()) {
            return;
        }

        $this->cargarGrupos();
        $this->resetValidation(['grupo_id']);
    }

    private function limpiarGradoGrupo(): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grados = collect();
        $this->grupos = collect();
        $this->nombreGrado = null;
        $this->nombreGrupo = null;
        $this->resetValidation(['grado_id', 'grupo_id']);
    }

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

        $personaRole = PersonaRole::query()
            ->with('rolePersona:id,nombre,slug')
            ->find($this->persona_role_id);

        $slug = $personaRole?->rolePersona?->slug;
        $this->rolSlugSeleccionado = $slug;

        if (!$slug) {
            return;
        }

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
        $this->forzarGradoGrupoPorNivel = $this->esSecundaria();
    }

    private function cargarGrados(): void
    {
        if (!$this->nivel_id || $this->esBachillerato()) {
            $this->grados = collect();
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('nombre')
            ->get();
    }

    private function cargarGrupos(): void
    {
        if (!$this->grado_id || $this->esBachillerato()) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->where('grupos.nivel_id', $this->nivel_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->orderBy('asignacion_grupos.nombre')
            ->get();
    }

    public function actualizarPersonal(): void
    {
        if ($this->esBachillerato()) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->ingreso_seg = null;
            $this->ingreso_sep = null;
        }

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
                ($this->debeMostrarGradoGrupo() && !$this->esBachillerato()) ? 'required' : 'nullable',
                'integer',
                'exists:grupos,id',
                function ($attribute, $value, $fail) {
                    if ($this->esBachillerato() && !is_null($value)) {
                        $fail('En bachillerato no se debe seleccionar grupo.');
                        return;
                    }

                    if (!$this->debeMostrarGradoGrupo()) {
                        return;
                    }

                    if (!$this->esBachillerato() && !empty($this->grado_id) && empty($value)) {
                        $fail('Selecciona un grupo.');
                    }
                },
            ],
            'ingreso_ct' => ['nullable', 'date'],
        ];

        if (!$this->esSecundaria() && !$this->esBachillerato()) {
            $rules['ingreso_seg'] = ['nullable', 'date'];
            $rules['ingreso_sep'] = ['nullable', 'date'];
        }

        $this->validate($rules, [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_role_id.required' => 'Selecciona una función.',
            'nivel_id.required' => 'Selecciona un nivel.',
            'grado_id.required' => 'Selecciona un grado.',
            'grupo_id.required' => 'Selecciona un grupo.',
        ]);

        $personaRoleValido = PersonaRole::query()
            ->where('id', $this->persona_role_id)
            ->where('persona_id', $this->persona_id)
            ->exists();

        if (!$personaRoleValido) {
            $this->addError('persona_role_id', 'La función seleccionada no pertenece a esta persona.');
            return;
        }

        if ($this->grado_id) {
            $gradoValido = Grado::query()
                ->where('id', $this->grado_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (!$gradoValido) {
                $this->addError('grado_id', 'El grado no pertenece al nivel.');
                return;
            }
        }

        if (!$this->esBachillerato() && $this->grupo_id) {
            $grupoValido = Grupo::query()
                ->where('id', $this->grupo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('grado_id', $this->grado_id)
                ->exists();

            if (!$grupoValido) {
                $this->addError('grupo_id', 'El grupo no pertenece al nivel y grado seleccionados.');
                return;
            }
        }

        try {
            DB::transaction(function () {
                $detalle = PersonaNivelDetalle::query()
                    ->with('cabecera')
                    ->findOrFail($this->detalleId);

                $cabeceraAnteriorId = (int) $detalle->persona_nivel_id;
                $nivelAnteriorId = (int) ($detalle->cabecera?->nivel_id ?? 0);
                $nivelNuevoId = (int) $this->nivel_id;

                $ingresoSeg = $this->esBachillerato() ? null : $this->limpiarFecha($this->ingreso_seg);
                $ingresoSep = $this->esBachillerato() ? null : $this->limpiarFecha($this->ingreso_sep);
                $ingresoCt = $this->limpiarFecha($this->ingreso_ct);

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

                if (!$this->esSecundaria()) {
                    $cabecera->update([
                        'ingreso_seg' => $ingresoSeg,
                        'ingreso_sep' => $ingresoSep,
                        'ingreso_ct' => $ingresoCt,
                    ]);
                }

                $dup = PersonaNivelDetalle::query()
                    ->where('persona_nivel_id', $cabecera->id)
                    ->where('persona_role_id', $this->persona_role_id)
                    ->when(
                        ($this->debeMostrarGradoGrupo() && !$this->esBachillerato())
                            ? $this->grado_id === null
                            : true,
                        fn($query) => $query->whereNull('grado_id'),
                        fn($query) => $query->where('grado_id', $this->grado_id)
                    )
                    ->when(
                        ($this->debeMostrarGradoGrupo() && !$this->esBachillerato())
                            ? $this->grupo_id === null
                            : true,
                        fn($query) => $query->whereNull('grupo_id'),
                        fn($query) => $query->where('grupo_id', $this->grupo_id)
                    )
                    ->where('id', '!=', $detalle->id)
                    ->exists();

                if ($dup) {
                    throw new \RuntimeException('DUPLICADO');
                }

                $ordenNuevo = (int) $detalle->orden;

                if ($nivelAnteriorId !== $nivelNuevoId) {
                    $maxOrden = PersonaNivelDetalle::query()
                        ->whereHas('cabecera', fn($query) => $query->where('nivel_id', $nivelNuevoId))
                        ->max('orden');

                    $ordenNuevo = ((int) ($maxOrden ?? 0)) + 1;
                }

                $detalle->update([
                    'persona_nivel_id' => $cabecera->id,
                    'persona_role_id' => $this->persona_role_id,
                    'grado_id' => ($this->debeMostrarGradoGrupo() && !$this->esBachillerato()) ? $this->grado_id : null,
                    'grupo_id' => ($this->debeMostrarGradoGrupo() && !$this->esBachillerato()) ? $this->grupo_id : null,
                    'orden' => $ordenNuevo,
                ]);

                if ($cabeceraAnteriorId !== (int) $cabecera->id) {
                    $tieneMasDetalles = PersonaNivelDetalle::query()
                        ->where('persona_nivel_id', $cabeceraAnteriorId)
                        ->exists();

                    if (!$tieneMasDetalles) {
                        PersonaNivel::query()->whereKey($cabeceraAnteriorId)->delete();
                    }
                }

                $nivelesAjustar = array_values(array_unique(array_filter([$nivelAnteriorId, $nivelNuevoId])));

                foreach ($nivelesAjustar as $nivelId) {
                    $detalles = PersonaNivelDetalle::query()
                        ->whereHas('cabecera', fn($query) => $query->where('nivel_id', $nivelId))
                        ->orderBy('orden')
                        ->orderBy('id')
                        ->pluck('id')
                        ->all();

                    $orden = 1;

                    foreach ($detalles as $detalleOrdenId) {
                        PersonaNivelDetalle::query()
                            ->whereKey($detalleOrdenId)
                            ->update(['orden' => $orden++]);
                    }
                }
            });

            $this->dispatch('swal', [
                'title' => '¡Asignación actualizada correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);

            $this->dispatch('refreshPersonaNivelList');
            $this->dispatch('cerrar-modal-editar');
            $this->cerrarModal();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'DUPLICADO') {
                $this->addError('persona_role_id', 'Ya existe esa asignación en el nivel destino.');
                return;
            }

            throw $e;
        }
    }

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
