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
    public ?int $persona_id = null;

    public string $buscar_persona = '';

    public string $nombre_persona_seleccionada = '';

    public ?int $persona_role_id = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    public $personas = [];
    public $rolesPersona;

    public $niveles = [];
    public $grados;
    public $grupos;

    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;

    public ?string $rolSlugSeleccionado = null;

    public bool $bloquearFechasSecundaria = false;
    public bool $existeCabecera = false;

    public function mount(): void
    {
        $this->personas = collect();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        $this->rolesPersona = collect();
        $this->grados = collect();
        $this->grupos = collect();
    }

    public function updatedBuscarPersona(): void
    {
        if (blank($this->buscar_persona)) {
            $this->persona_id = null;
            $this->nombre_persona_seleccionada = '';
            $this->rolesPersona = collect();
            $this->persona_role_id = null;
            $this->rolSlugSeleccionado = null;

            $this->resetBloqueoFechas();
        }
    }

    public function seleccionarPersona(int $id): void
    {
        $persona = Persona::query()
            ->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno')
            ->find($id);

        if (!$persona) {
            $this->persona_id = null;
            $this->buscar_persona = '';
            $this->nombre_persona_seleccionada = '';

            $this->addError('persona_id', 'La persona seleccionada no es válida.');
            return;
        }

        $nombreCompleto = trim(
            ($persona->titulo ?? '') . ' ' .
            ($persona->nombre ?? '') . ' ' .
            ($persona->apellido_paterno ?? '') . ' ' .
            ($persona->apellido_materno ?? '')
        );

        $this->persona_id = $persona->id;
        $this->buscar_persona = $nombreCompleto;
        $this->nombre_persona_seleccionada = $nombreCompleto;

        $this->resetErrorBag('persona_id');

        $this->updatedPersonaId($persona->id);
    }

    public function limpiarPersona(): void
    {
        $this->persona_id = null;
        $this->buscar_persona = '';
        $this->nombre_persona_seleccionada = '';
        $this->persona_role_id = null;
        $this->rolSlugSeleccionado = null;
        $this->rolesPersona = collect();

        $this->resetErrorBag('persona_id');
        $this->resetBloqueoFechas();
    }

    public function getPersonalFiltradoProperty()
    {
        $buscar = trim($this->buscar_persona);

        return Persona::query()
            ->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%')
                        ->orWhere('apellido_paterno', 'like', '%' . $buscar . '%')
                        ->orWhere('apellido_materno', 'like', '%' . $buscar . '%')
                        ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ['%' . $buscar . '%']);
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(30)
            ->get();
    }

    public function esBachilleratoSeleccionado(): bool
    {
        return $this->esBachillerato();
    }

    private function esBachillerato(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = $this->niveles instanceof \Illuminate\Support\Collection
            ? $this->niveles->firstWhere('id', (int) $this->nivel_id)
            : null;

        return (int) $this->nivel_id === 4 || ($nivel?->slug === 'bachillerato');
    }

    private function esSecundaria(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = $this->niveles instanceof \Illuminate\Support\Collection
            ? $this->niveles->firstWhere('id', (int) $this->nivel_id)
            : null;

        return (int) $this->nivel_id === 3 || ($nivel?->slug === 'secundaria');
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

        if ($this->nivel_id && !$this->esBachillerato() && $this->grados->isEmpty()) {
            $this->cargarGrados();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grados = collect();
        $this->grupos = collect();

        if (!$value) {
            $this->resetBloqueoFechas();
            return;
        }

        if ($this->esBachillerato()) {
            $this->ingreso_seg = null;
            $this->ingreso_sep = null;
            $this->resetValidation(['grado_id', 'grupo_id', 'ingreso_seg', 'ingreso_sep']);
            $this->evaluarBloqueoFechas();
            return;
        }

        $this->cargarGrados();
        $this->resetValidation(['grado_id', 'grupo_id']);
        $this->evaluarBloqueoFechas();
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;
        $this->grupos = collect();

        if (!$value || $this->esBachillerato()) {
            return;
        }

        $this->cargarGruposPorGrado((int) $value);
        $this->resetValidation(['grupo_id']);
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

    private function cargarGruposPorGrado(int $gradoId): void
    {
        $this->grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
            ])
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->where('grupos.nivel_id', $this->nivel_id)
            ->where('grupos.grado_id', $gradoId)
            ->whereNull('grupos.semestre_id')
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.generacion_id')
            ->get();
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return 'Sin grupo';
        }

        $nombre = $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';

        if ($grupo->generacion) {
            $generacion = trim(($grupo->generacion->anio_ingreso ?? '') . ' - ' . ($grupo->generacion->anio_egreso ?? ''));

            if ($generacion !== '-') {
                return $nombre . ' / ' . $generacion;
            }
        }

        return $nombre;
    }

    private function evaluarBloqueoFechas(): void
    {
        $this->bloquearFechasSecundaria = false;
        $this->existeCabecera = false;

        if (!$this->persona_id || !$this->nivel_id) {
            return;
        }

        if ($this->esBachillerato()) {
            $this->ingreso_seg = null;
            $this->ingreso_sep = null;
            return;
        }

        $cabecera = PersonaNivel::query()
            ->where('persona_id', $this->persona_id)
            ->where('nivel_id', $this->nivel_id)
            ->first();

        $this->existeCabecera = (bool) $cabecera;

        if ($this->esSecundaria() && $cabecera) {
            $this->bloquearFechasSecundaria = true;
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
        if ($this->esBachillerato()) {
            $this->grado_id = null;
            $this->grupo_id = null;
            $this->ingreso_seg = null;
            $this->ingreso_sep = null;
        }

        $this->validate([
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'],
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'grado_id' => [
                'nullable',
                'integer',
                'exists:grados,id',
                'required_with:grupo_id',
                function ($attribute, $value, $fail) {
                    if ($this->esBachillerato() && !empty($value)) {
                        $fail('En bachillerato no se debe seleccionar grado.');
                    }
                },
            ],
            'grupo_id' => [
                'nullable',
                'integer',
                'exists:grupos,id',
                function ($attribute, $value, $fail) {
                    if (!$this->esBachillerato() && !empty($this->grado_id) && empty($value)) {
                        $fail('Si seleccionas Grado, también debes seleccionar Grupo.');
                    }

                    if (!$this->esBachillerato() && empty($this->grado_id) && !empty($value)) {
                        $fail('Si seleccionas Grupo, también debes seleccionar Grado.');
                    }

                    if ($this->esBachillerato() && !empty($value)) {
                        $fail('En bachillerato no se debe seleccionar grupo.');
                    }
                },
            ],
            'ingreso_seg' => ['nullable', 'date'],
            'ingreso_sep' => ['nullable', 'date'],
            'ingreso_ct' => ['nullable', 'date'],
        ], [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_role_id.required' => 'Selecciona una función.',
            'nivel_id.required' => 'Selecciona un nivel.',
            'grado_id.required_with' => 'Si seleccionas Grupo, también debes seleccionar Grado.',
        ]);

        $prOk = PersonaRole::query()
            ->where('id', $this->persona_role_id)
            ->where('persona_id', $this->persona_id)
            ->exists();

        if (!$prOk) {
            $this->addError('persona_role_id', 'La función seleccionada no pertenece a esta persona.');
            return;
        }

        if (!$this->esBachillerato() && $this->grado_id) {
            $gradoOk = Grado::query()
                ->where('id', $this->grado_id)
                ->where('nivel_id', $this->nivel_id)
                ->exists();

            if (!$gradoOk) {
                $this->addError('grado_id', 'El grado no pertenece al nivel.');
                return;
            }
        }

        if (!$this->esBachillerato() && $this->grupo_id) {
            $grupoOk = Grupo::query()
                ->where('id', $this->grupo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('grado_id', $this->grado_id)
                ->whereNull('semestre_id')
                ->exists();

            if (!$grupoOk) {
                $this->addError('grupo_id', 'El grupo no pertenece al grado o nivel seleccionado.');
                return;
            }
        }

        try {
            DB::transaction(function () {
                $cabecera = PersonaNivel::firstOrCreate(
                    [
                        'persona_id' => $this->persona_id,
                        'nivel_id' => $this->nivel_id,
                    ],
                    [
                        'ingreso_seg' => $this->esBachillerato() ? null : $this->ingreso_seg,
                        'ingreso_sep' => $this->esBachillerato() ? null : $this->ingreso_sep,
                        'ingreso_ct' => $this->ingreso_ct,
                    ]
                );

                if (!$this->bloquearFechasSecundaria) {
                    $updates = [];

                    if (!$this->esBachillerato() && empty($cabecera->ingreso_seg) && !empty($this->ingreso_seg)) {
                        $updates['ingreso_seg'] = $this->ingreso_seg;
                    }

                    if (!$this->esBachillerato() && empty($cabecera->ingreso_sep) && !empty($this->ingreso_sep)) {
                        $updates['ingreso_sep'] = $this->ingreso_sep;
                    }

                    if (empty($cabecera->ingreso_ct) && !empty($this->ingreso_ct)) {
                        $updates['ingreso_ct'] = $this->ingreso_ct;
                    }

                    if (!empty($updates)) {
                        $cabecera->update($updates);
                    }
                }

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

        $this->reset(['persona_role_id', 'grado_id', 'grupo_id']);

        if (!$this->bloquearFechasSecundaria) {
            $this->reset(['ingreso_seg', 'ingreso_sep', 'ingreso_ct']);
        }

        $this->rolSlugSeleccionado = null;
        $this->grados = $this->esBachillerato() ? collect() : $this->grados;
        $this->grupos = collect();

        $this->evaluarBloqueoFechas();
    }

    public function render()
    {
        return view('livewire.persona-nivel.crear-persona-nivel', [
            'rolesPersona' => $this->rolesPersona,
        ]);
    }
}
