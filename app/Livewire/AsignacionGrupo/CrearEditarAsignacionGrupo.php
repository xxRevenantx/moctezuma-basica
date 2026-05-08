<?php

namespace App\Livewire\AsignacionGrupo;

use App\Models\AsignacionGrupo;
use Livewire\Attributes\On;
use Livewire\Component;

class CrearEditarAsignacionGrupo extends Component
{
    public $asignacion_grupo_id = null;
    public string $nombre = '';

    public bool $modoEdicion = false;

    public function resetFormulario()
    {
        $this->reset([
            'asignacion_grupo_id',
            'nombre',
            'modoEdicion',
        ]);

        $this->resetErrorBag();
    }

    public function guardarAsignacionGrupo()
    {
        $this->validate([
            'nombre' => 'required|string|max:20|unique:asignacion_grupos,nombre,' . $this->asignacion_grupo_id,
        ], [
            'nombre.required' => 'Escribe el nombre del grupo.',
            'nombre.unique' => 'Este grupo ya está registrado.',
            'nombre.max' => 'El nombre no debe superar los 20 caracteres.',
        ]);

        AsignacionGrupo::updateOrCreate(
            [
                'id' => $this->asignacion_grupo_id,
            ],
            [
                'nombre' => mb_strtoupper(trim($this->nombre)),
            ]
        );

        $this->dispatch('swal', [
            'title' => $this->modoEdicion
                ? '¡Grupo actualizado correctamente!'
                : '¡Grupo creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshAsignacionGrupos');

        $this->resetFormulario();
    }

    public function editarAsignacionGrupo($id)
    {
        $grupo = AsignacionGrupo::find($id);

        if (!$grupo) {
            $this->dispatch('swal', [
                'title' => 'El grupo no existe.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return;
        }

        $this->asignacion_grupo_id = $grupo->id;
        $this->nombre = $grupo->nombre;
        $this->modoEdicion = true;
    }

    public function eliminarAsignacionGrupo($id)
    {
        $grupo = AsignacionGrupo::withCount('grupos')->find($id);

        if (!$grupo) {
            return;
        }

        if ($grupo->grupos_count > 0) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar.',
                'text' => 'Este grupo ya está relacionado con registros existentes.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $grupo->delete();

        $this->dispatch('swal', [
            'title' => '¡Grupo eliminado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshAsignacionGrupos');

        $this->resetFormulario();
    }

    #[On('editarModalAsignacionGrupo')]
    public function render()
    {
        return view('livewire.asignacion-grupo.crear-editar-asignacion-grupo', [
            'asignacionGrupos' => AsignacionGrupo::query()
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
