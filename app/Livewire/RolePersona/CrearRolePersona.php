<?php

namespace App\Livewire\RolePersona;

use Livewire\Component;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

class CrearRolePersona extends Component
{
    public $persona_id = null;
    public $role_persona_id = null;

    public function resetSeleccion()
    {
        $this->reset(['persona_id', 'role_persona_id']);
        $this->resetErrorBag();
    }

    public function asignarRol()
    {
        $this->validate([
            'persona_id' => 'required|integer|exists:personas,id',
            // Ajusta el exists si tu tabla se llama diferente:
            'role_persona_id' => 'required|integer|exists:role_personas,id',
        ]);

        // ==========================
        // OPCIÓN A (PIVOT) ✅
        // Tabla pivot sugerida: persona_role_persona
        // Campos: persona_id, role_persona_id
        // ==========================
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

        // ==========================
        // OPCIÓN B (COLUMNA EN PERSONAS) ✅
        // Si en tu tabla "personas" tienes una columna: role_persona_id
        // ==========================
        /*
        Persona::where('id', $this->persona_id)->update([
            'role_persona_id' => $this->role_persona_id,
        ]);
        */

        // Si quieres limpiar después de guardar:
        $this->resetSeleccion();


    }

    public function render()
    {
        $personal = Persona::orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')->get();

        // Si tu modelo se llama distinto, cámbialo aquí:
        $personaRoles = \App\Models\RolePersona::orderBy('nombre')->get();

        return view('livewire.role-persona.crear-role-persona', compact('personal', 'personaRoles'));
    }
}
