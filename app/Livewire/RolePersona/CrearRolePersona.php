<?php

namespace App\Livewire\RolePersona;

use App\Models\Persona;
use App\Models\RolePersona;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class CrearRolePersona extends Component
{
    public $persona_id = null;
    public $role_persona_id = null;

    public string $buscar_persona = '';
    public string $nombre_persona_seleccionada = '';

    public function resetSeleccion()
    {
        $this->reset([
            'persona_id',
            'role_persona_id',
            'buscar_persona',
            'nombre_persona_seleccionada',
        ]);

        $this->resetErrorBag();
    }

    public function updatedBuscarPersona()
    {
        if (blank($this->buscar_persona)) {
            $this->persona_id = null;
            $this->nombre_persona_seleccionada = '';
        }
    }

    public function seleccionarPersona($id)
    {
        $persona = Persona::query()
            ->where('id', $id)
            ->first();

        if (!$persona) {
            $this->persona_id = null;
            $this->buscar_persona = '';
            $this->nombre_persona_seleccionada = '';

            $this->addError('persona_id', 'La persona seleccionada no es válida.');
            return;
        }

        $nombreCompleto = trim(
            ($persona->nombre ?? '') . ' ' .
                ($persona->apellido_paterno ?? '') . ' ' .
                ($persona->apellido_materno ?? '')
        );

        $this->persona_id = $persona->id;
        $this->buscar_persona = $nombreCompleto;
        $this->nombre_persona_seleccionada = $nombreCompleto;

        $this->resetErrorBag('persona_id');
    }

    public function limpiarPersona()
    {
        $this->persona_id = null;
        $this->buscar_persona = '';
        $this->nombre_persona_seleccionada = '';

        $this->resetErrorBag('persona_id');
    }

    public function getPersonalFiltradoProperty()
    {
        $buscar = trim($this->buscar_persona);

        return Persona::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%')
                        ->orWhere('apellido_paterno', 'like', '%' . $buscar . '%')
                        ->orWhere('apellido_materno', 'like', '%' . $buscar . '%');
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(30)
            ->get();
    }

    public function asignarRol()
    {
        $this->validate([
            'persona_id' => 'required|integer|exists:personas,id',
            'role_persona_id' => 'required|integer|exists:role_personas,id',
        ], [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_id.exists' => 'La persona seleccionada no es válida.',
            'role_persona_id.required' => 'Selecciona un rol.',
            'role_persona_id.exists' => 'El rol seleccionado no es válido.',
        ]);

        DB::table('persona_role')->updateOrInsert(
            [
                'persona_id' => $this->persona_id,
                'role_persona_id' => $this->role_persona_id,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->dispatch('swal', [
            'title' => '¡Rol asignado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshRolePersona');

        $this->resetSeleccion();
    }

    #[On('rolCargadoEliminado')]
    public function render()
    {
        $personaRoles = RolePersona::query()
            ->orderBy('nombre')
            ->get();

        return view('livewire.role-persona.crear-role-persona', [
            'personalFiltrado' => $this->personalFiltrado,
            'personaRoles' => $personaRoles,
        ]);
    }
}
