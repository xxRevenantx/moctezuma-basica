<?php

namespace App\Livewire\PersonaRole;

use App\Models\RolePersona;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;

class CrearEditarPersonaRole extends Component
{
    public bool $open = false;

    public ?int $roleId = null;

    public string $nombre = '';
    public string $slug = '';
    public ?string $descripcion = null;

    public bool $slugManual = false; // ✅ NUEVO

    public $roles = [];

    #[On('editarModal')]
    public function editarModal(): void
    {
        $this->roles = RolePersona::orderBy('nombre')->get();

        $this->reset(['roleId', 'nombre', 'slug', 'descripcion', 'slugManual']);
        $this->resetValidation();

        $this->open = true;
        $this->dispatch('editar-cargado');
    }

    public function updatedRoleId($value): void
    {
        $this->resetValidation();

        if (! $value) {
            $this->setCrear();
            return;
        }

        $rol = RolePersona::find((int) $value);

        if (! $rol) {
            $this->setCrear();
            return;
        }

        $this->roleId = $rol->id;
        $this->nombre = $rol->nombre ?? '';
        $this->slug = $rol->slug ?? '';
        $this->descripcion = $rol->descripcion ?? null;

        // ✅ al cargar un rol, asumimos que slug no fue editado manualmente aún
        $this->slugManual = false;
    }

    // ✅ Si el usuario toca el slug, ya no lo pisamos automáticamente
    public function updatedSlug(): void
    {
        $this->slugManual = true;
    }

    // ✅ Actualiza slug siempre al cambiar nombre (crear o editar),
    // pero SOLO si el usuario no lo modificó manualmente.
    public function updatedNombre($value): void
    {
        if (! $this->slugManual) {
            $this->slug = Str::slug($value, '_');
        }
    }

    public function setCrear(): void
    {
        $this->roleId = null;
        $this->nombre = '';
        $this->slug = '';
        $this->descripcion = null;

        $this->slugManual = false;
    }

    public function save(): void
    {
        // ✅ si no fue manual (o viene vacío), siempre se recalcula al guardar (crear/editar)
        if (! $this->slugManual || trim($this->slug) === '') {
            $this->slug = Str::slug($this->nombre, '_');
        }

        $this->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('role_personas', 'slug')->ignore($this->roleId),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $rol = RolePersona::updateOrCreate(
            ['id' => $this->roleId],
            [
                'nombre' => trim($this->nombre),
                'slug' => trim($this->slug),
                'descripcion' => $this->descripcion ? trim($this->descripcion) : null,
            ]
        );

        $this->roles = RolePersona::orderBy('nombre')->get();

        $this->dispatch('persona-role:saved', id: $rol->id);
        $this->dispatch('persona-role:select', id: $rol->id);

        $this->open = false;
        $this->cerrarModal();
    }

    public function eliminarRol(): void
{
    $this->resetValidation();

    if (! $this->roleId) {
        $this->addError('roleId', 'Selecciona un rol para eliminar.');
        return;
    }

    $rol = RolePersona::find($this->roleId);

    if (! $rol) {
        $this->addError('roleId', 'El rol ya no existe.');
        $this->setCrear();
        $this->roles = RolePersona::orderBy('nombre')->get();
        return;
    }

    // ✅ Si tienes tabla pivote (ej: persona_roles) y NO quieres permitir borrar si está asignado:
    if (method_exists($rol, 'personaRoles') && $rol->personaRoles()->exists()) {
        $this->addError('roleId', 'No se puede eliminar: este rol está asignado a personal.');
        return;
    }

    // ✅ Si SÍ quieres borrar asignaciones antes (descomenta):
    // if (method_exists($rol, 'personaRoles')) {
    //     $rol->personaRoles()->delete(); // o ->detach() si es belongsToMany
    // }

    $deletedId = $rol->id;
    $rol->delete();

    // refrescar lista
    $this->roles = RolePersona::orderBy('nombre')->get();

    // limpiar formulario (modo crear)
    $this->setCrear();

    // avisar a otros componentes
    $this->dispatch('persona-role:deleted', id: $deletedId);

    // opcional: cerrar
    // $this->open = false;
    // $this->cerrarModal();
}



    public function cerrarModal(): void
    {
        $this->reset(['roleId', 'nombre', 'slug', 'descripcion', 'slugManual']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.persona-role.crear-editar-persona-role');
    }
}
