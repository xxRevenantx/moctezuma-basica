<?php

namespace App\Livewire\PersonaNivel;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaRole;
use App\Models\PlantillaPersonalNivel;
use App\Models\RolePersona;
use App\Models\Semestre;
use App\Services\PlantillaPersonalCicloService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CrearPersonaNivel extends Component
{
    public ?int $ciclo_escolar_id = null;
    public ?int $persona_id = null;
    public string $buscar_persona = '';
    public string $nombre_persona_seleccionada = '';
    public ?int $persona_role_id = null;
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;
    public ?string $rolSlugSeleccionado = null;
    public bool $rolRequiereGrupo = false;
    public bool $rolPermiteGrupo = false;
    public bool $fechasExistentes = false;
    public string $generacionTexto = '';
    public string $plantillaEstado = 'borrador';

    public Collection $rolesPersona;
    public Collection $niveles;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public function mount(PlantillaPersonalCicloService $service): void
    {
        $this->ciclo_escolar_id = $service->cicloActual()?->id;
        $this->niveles = Nivel::query()->select('id', 'nombre', 'slug')->orderBy('id')->get();
        $this->rolesPersona = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->actualizarEstadoPlantilla();
    }

    #[On('ciclo-plantilla-cambiado')]
    public function cambiarCiclo(int $cicloId): void
    {
        $this->ciclo_escolar_id = $cicloId;
        $this->limpiarContextoAcademico(false);
        $this->actualizarEstadoPlantilla();
    }

    #[On('plantilla-personal-actualizada')]
    public function refrescarPlantilla(): void
    {
        $this->actualizarEstadoPlantilla();
        if ($this->grado_id || $this->semestre_id) {
            $this->cargarGrupos();
        }
    }

    public function updatedBuscarPersona(): void
    {
        if (blank($this->buscar_persona)) {
            $this->limpiarPersona();
        }
    }

    public function seleccionarPersona(int $id): void
    {
        $persona = Persona::query()->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno')->find($id);
        if (!$persona) {
            $this->addError('persona_id', 'La persona seleccionada no es válida.');
            return;
        }

        $this->persona_id = $persona->id;
        $this->nombre_persona_seleccionada = trim(collect([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' '));
        $this->buscar_persona = $this->nombre_persona_seleccionada;
        $this->updatedPersonaId($persona->id);
        $this->resetErrorBag('persona_id');
    }

    public function limpiarPersona(): void
    {
        $this->persona_id = null;
        $this->buscar_persona = '';
        $this->nombre_persona_seleccionada = '';
        $this->persona_role_id = null;
        $this->rolSlugSeleccionado = null;
        $this->rolRequiereGrupo = false;
        $this->rolPermiteGrupo = false;
        $this->rolesPersona = collect();
        $this->fechasExistentes = false;
        $this->ingreso_seg = $this->ingreso_sep = $this->ingreso_ct = null;
    }

    public function getPersonalFiltradoProperty()
    {
        $buscar = trim($this->buscar_persona);

        return Persona::query()
            ->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", ["%{$buscar}%"]);
                });
            })
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')
            ->limit(30)->get();
    }

    public function updatedPersonaId($value): void
    {
        $this->persona_role_id = null;
        $this->rolesPersona = $value
            ? PersonaRole::query()->with('rolePersona')->where('persona_id', (int) $value)->orderBy('role_persona_id')->get()
            : collect();
        $this->cargarFechasLaborales();
    }

    public function seleccionarRol(int $personaRoleId): void
    {
        $this->persona_role_id = $personaRoleId;
        $this->updatedPersonaRoleId($personaRoleId);
    }

    public function updatedPersonaRoleId($value): void
    {
        $personaRol = $value ? PersonaRole::query()->with('rolePersona')->find((int) $value) : null;
        $rol = $personaRol?->rolePersona;
        $this->rolSlugSeleccionado = $rol?->slug;
        $this->rolRequiereGrupo = (bool) $rol?->requiere_grupo;
        $this->rolPermiteGrupo = (bool) ($rol?->requiere_grupo || $rol?->permite_grupo);

        if (!$this->rolPermiteGrupo) {
            $this->grado_id = $this->semestre_id = $this->grupo_id = null;
            $this->grupos = collect();
            $this->generacionTexto = '';
        } else {
            // El nivel puede haberse seleccionado antes de elegir la función.
            // Recargamos los catálogos para evitar un selector de grado vacío.
            $this->cargarCatalogosAcademicos();
            $this->cargarGrupos();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = filled($value) ? (int) $value : null;
        unset($this->gradosDisponibles, $this->semestresDisponibles, $this->gruposDisponibles);
        $this->grado_id = $this->semestre_id = $this->grupo_id = null;
        $this->grupos = collect();
        $this->generacionTexto = '';
        $this->cargarCatalogosAcademicos();
        $this->cargarFechasLaborales();
        $this->actualizarEstadoPlantilla();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = filled($value) ? (int) $value : null;
        unset($this->gruposDisponibles);
        $this->grupo_id = null;
        $this->generacionTexto = '';
        $this->cargarGrupos();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = filled($value) ? (int) $value : null;
        unset($this->gruposDisponibles);
        $this->grupo_id = null;
        $this->generacionTexto = '';
        $this->cargarGrupos();
    }

    public function updatedGrupoId($value): void
    {
        unset($this->gruposDisponibles);
        $grupo = $value ? $this->gruposDisponibles->firstWhere('id', (int) $value) : null;
        $this->generacionTexto = $grupo?->generacion?->etiqueta ?? '';
        if ($grupo && $this->esBachillerato()) {
            $this->grado_id = $grupo->grado_id;
        }
    }

    #[Computed]
    public function gradosDisponibles(): Collection
    {
        if (!$this->nivel_id || $this->esBachillerato()) {
            return collect();
        }

        return Grado::query()
            ->select('id', 'nivel_id', 'nombre', 'orden')
            ->where('nivel_id', (int) $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function semestresDisponibles(): Collection
    {
        if (!$this->nivel_id || !$this->esBachillerato()) {
            return collect();
        }

        return Semestre::query()
            ->with('grado:id,nivel_id,nombre')
            ->whereHas('grado', fn($q) => $q->where('nivel_id', (int) $this->nivel_id))
            ->orderByRaw('COALESCE(orden_global, 255)')
            ->orderBy('numero')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function gruposDisponibles(): Collection
    {
        if (!$this->ciclo_escolar_id || !$this->nivel_id || !$this->rolPermiteGrupo) {
            return collect();
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return collect();
        }

        if (!$this->esBachillerato() && !$this->grado_id) {
            return collect();
        }

        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso,nombre',
                'grado:id,nombre',
                'semestre:id,grado_id,numero,orden_global',
            ])
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('estado', 'activo')
            ->whereNull('deleted_at')
            ->when(
                $this->esBachillerato(),
                fn($q) => $q->where('semestre_id', (int) $this->semestre_id),
                fn($q) => $q->where('grado_id', (int) $this->grado_id)->whereNull('semestre_id')
            )
            ->orderBy('asignacion_grupo_id')
            ->orderBy('id')
            ->get();
    }

    private function cargarCatalogosAcademicos(): void
    {
        unset($this->gradosDisponibles, $this->semestresDisponibles, $this->gruposDisponibles);

        if (!$this->nivel_id) {
            $this->grados = $this->semestres = collect();
            return;
        }

        // Se sincronizan también las colecciones públicas para conservar
        // compatibilidad con cualquier código existente del componente.
        $this->grados = $this->gradosDisponibles;
        $this->semestres = $this->semestresDisponibles;
    }

    private function cargarGrupos(): void
    {
        unset($this->gruposDisponibles);
        $this->grupos = $this->gruposDisponibles;
    }

    private function cargarFechasLaborales(): void
    {
        $this->fechasExistentes = false;
        if (!$this->persona_id || !$this->nivel_id) {
            return;
        }

        $cabecera = PersonaNivel::query()
            ->where('persona_id', $this->persona_id)
            ->where('nivel_id', $this->nivel_id)
            ->latest('id')->first();

        if ($cabecera) {
            $this->fechasExistentes = true;
            $this->ingreso_seg = optional($cabecera->ingreso_seg)->format('Y-m-d');
            $this->ingreso_sep = optional($cabecera->ingreso_sep)->format('Y-m-d');
            $this->ingreso_ct = optional($cabecera->ingreso_ct)->format('Y-m-d');
        }
    }

    private function actualizarEstadoPlantilla(): void
    {
        if (!$this->ciclo_escolar_id || !$this->nivel_id) {
            $this->plantillaEstado = 'borrador';
            return;
        }

        $this->plantillaEstado = app(PlantillaPersonalCicloService::class)
            ->plantilla($this->ciclo_escolar_id, $this->nivel_id)?->estado ?? 'borrador';
    }

    private function esBachillerato(): bool
    {
        $nivel = $this->niveles->firstWhere('id', (int) $this->nivel_id);
        return $nivel?->slug === 'bachillerato';
    }

    private function limpiarContextoAcademico(bool $conNivel = true): void
    {
        if ($conNivel) {
            $this->nivel_id = null;
        }
        $this->grado_id = $this->semestre_id = $this->grupo_id = null;
        $this->grados = $this->semestres = $this->grupos = collect();
        $this->generacionTexto = '';
    }

    public function asignarPersonalNivel(PlantillaPersonalCicloService $service): void
    {
        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'persona_role_id' => ['required', 'integer', 'exists:persona_role,id'],
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'ingreso_seg' => ['nullable', 'date'],
            'ingreso_sep' => ['nullable', 'date'],
            'ingreso_ct' => ['nullable', 'date'],
        ], [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_role_id.required' => 'Selecciona una función.',
            'nivel_id.required' => 'Selecciona un nivel.',
        ]);

        $personaRole = PersonaRole::query()->with('rolePersona')
            ->whereKey($this->persona_role_id)
            ->where('persona_id', $this->persona_id)
            ->first();
        if (!$personaRole?->rolePersona) {
            $this->addError('persona_role_id', 'La función no pertenece a la persona seleccionada.');
            return;
        }

        $plantilla = $service->plantilla($this->ciclo_escolar_id, $this->nivel_id);
        $plantilla->loadMissing(['nivel', 'cicloEscolar']);
        $grupo = $service->validarAsignacion($plantilla, $personaRole->rolePersona, $this->grado_id, $this->grupo_id);

        DB::transaction(function () use ($service, $plantilla, $personaRole, $grupo) {
            $cabecera = PersonaNivel::query()->firstOrCreate([
                'persona_id' => $this->persona_id,
                'nivel_id' => $this->nivel_id,
            ], [
                'fecha_inicio' => now()->toDateString(),
                'estado' => PersonaNivel::ESTADO_ACTIVO,
                'orden' => ((int) PersonaNivel::query()->where('nivel_id', $this->nivel_id)->max('orden')) + 1,
            ]);

            // SEG, SEP y C.T. se conservan por persona + nivel, no por ciclo.
            $cabecera->update([
                'ingreso_seg' => $this->ingreso_seg ?: $cabecera->ingreso_seg,
                'ingreso_sep' => $this->ingreso_sep ?: $cabecera->ingreso_sep,
                'ingreso_ct' => $this->ingreso_ct ?: $cabecera->ingreso_ct,
                'estado' => PersonaNivel::ESTADO_ACTIVO,
            ]);

            $membresia = $service->membresia($plantilla, $cabecera);
            $service->bloquearDuplicado($membresia, $personaRole->id, $grupo?->grado_id, $grupo?->id);

            PersonaNivelDetalle::query()->create([
                'persona_nivel_id' => $cabecera->id,
                'persona_nivel_ciclo_id' => $membresia->id,
                'persona_role_id' => $personaRole->id,
                'grado_id' => $grupo?->grado_id,
                'grupo_id' => $grupo?->id,
                'fecha_inicio' => now()->toDateString(),
                'estado' => PersonaNivelDetalle::ESTADO_ACTIVO,
                'confirmado' => true,
                'orden' => ((int) PersonaNivelDetalle::query()
                    ->whereHas('cicloAsignacion', fn($q) => $q->where('plantilla_personal_nivel_id', $plantilla->id))
                    ->max('orden')) + 1,
            ]);

            $service->actualizarDiagnostico($plantilla);
        });

        $this->dispatch('swal', ['title' => 'Asignación guardada en el ciclo seleccionado.', 'icon' => 'success', 'position' => 'top-end']);
        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('plantilla-personal-actualizada');

        $this->persona_role_id = $this->grado_id = $this->semestre_id = $this->grupo_id = null;
        $this->rolSlugSeleccionado = null;
        $this->rolRequiereGrupo = $this->rolPermiteGrupo = false;
        $this->generacionTexto = '';
        $this->grupos = collect();
    }

    public function render()
    {
        // Los catálogos dependen del estado actual. Se recalculan en cada render
        // para soportar cambios desde Flux UI, eventos del padre y rehidratación.
        $this->cargarCatalogosAcademicos();
        $this->cargarGrupos();

        return view('livewire.persona-nivel.crear-persona-nivel', [
            'ciclos' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
            'rolesPersona' => $this->rolesPersona,
        ]);
    }
}
