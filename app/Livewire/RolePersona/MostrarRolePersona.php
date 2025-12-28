<?php

namespace App\Livewire\RolePersona;

use App\Models\Persona;
use App\Models\PersonaRole;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarRolePersona extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function eliminarRol(int $personaRoleId): void
    {
        PersonaRole::whereKey($personaRoleId)->delete();
    }

    #[On('refreshRolePersona')]
    public function render()
    {
        $s = trim($this->search);

        $personas = Persona::query()
            ->with(['personaRoles.rolePersona']) // trae pivote + rol
            ->when($s !== '', function ($q) use ($s) {
            $q->where(function ($qq) use ($s) {
                // buscar por persona
                $qq->where('nombre', 'like', "%{$s}%")
                ->orWhere('apellido_paterno', 'like', "%{$s}%")
                ->orWhere('apellido_materno', 'like', "%{$s}%")

                // buscar por rol (vía pivote)
                ->orWhereHas('personaRoles.rolePersona', function ($rq) use ($s) {
                    $rq->where('nombre', 'like', "%{$s}%")
                       ->orWhere('slug', 'like', "%{$s}%");
                });
            });
            })
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->paginate(10);

        return view('livewire.role-persona.mostrar-role-persona', compact('personas'));
    }
}
