<?php

namespace App\Livewire\Grupo;

use App\Models\AsignacionGrupo;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGrupo extends Component
{
    public $grupo_id = null;

    public $asignacion_grupo_id = '';
    public $nivel_id = '';
    public $grado_id = '';
    public $generacion_id = '';
    public $semestre_id = '';

    public string $grupo_nombre = '';
    public string $nivel_nombre = '';
    public string $grado_nombre = '';

    public $grados;
    public $generaciones;
    public $semestres;

    public bool $esBachillerato = false;

    public function mount()
    {
        $this->grados = collect();
        $this->generaciones = collect();
        $this->semestres = collect();
    }

    #[On('editarModal')]
    public function editarModal($id = null)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        $grupo = Grupo::query()
            ->with([
                'asignacionGrupo',
                'nivel',
                'grado',
                'generacion',
                'semestre',
            ])
            ->find($id);

        if (!$grupo) {
            $this->dispatch('swal', [
                'title' => 'El grupo no existe.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            $this->cerrarModal();
            return;
        }

        $this->grupo_id = $grupo->id;
        $this->asignacion_grupo_id = $grupo->asignacion_grupo_id;
        $this->nivel_id = $grupo->nivel_id;
        $this->grado_id = $grupo->grado_id;
        $this->generacion_id = $grupo->generacion_id;
        $this->semestre_id = $grupo->semestre_id;

        $this->esBachillerato = (int) $grupo->nivel_id === 4;

        $this->grupo_nombre = $grupo->asignacionGrupo?->nombre ?? 'No definido';
        $this->nivel_nombre = $grupo->nivel?->nombre ?? 'Nivel no seleccionado';
        $this->grado_nombre = $grupo->grado?->nombre ?? 'Grado no seleccionado';

        $this->cargarDatosPorNivel();

        $this->dispatch('editar-cargado');
    }

    public function updatedNivelId()
    {
        $this->grado_id = '';
        $this->generacion_id = '';
        $this->semestre_id = '';

        $this->esBachillerato = (int) $this->nivel_id === 4;

        $this->cargarDatosPorNivel();
    }

    public function cargarDatosPorNivel()
    {
        if (!$this->nivel_id) {
            $this->grados = collect();
            $this->generaciones = collect();
            $this->semestres = collect();
            $this->esBachillerato = false;

            return;
        }

        $this->grados = Grado::query()
            ->with('nivel')
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get();

        $this->generaciones = Generacion::query()
            ->with('nivel')
            ->where('nivel_id', $this->nivel_id)
            ->where(function ($query) {
                $query->where('status', true)
                    ->when($this->generacion_id, fn ($sub) => $sub->orWhereKey($this->generacion_id));
            })
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->semestres = $this->esBachillerato
            ? Semestre::query()
                ->orderBy('numero')
                ->get()
            : collect();
    }

    public function actualizarGrupo()
    {
        $this->validate([
            'grupo_id' => 'required|integer|exists:grupos,id',
            'asignacion_grupo_id' => 'required|integer|exists:asignacion_grupos,id',
            'nivel_id' => 'required|integer|exists:niveles,id',
            'grado_id' => 'required|integer|exists:grados,id',
            'generacion_id' => 'required|integer|exists:generaciones,id',
            'semestre_id' => $this->esBachillerato
                ? 'required|integer|exists:semestres,id'
                : 'nullable',
        ], [
            'asignacion_grupo_id.required' => 'Selecciona un grupo.',
            'asignacion_grupo_id.exists' => 'El grupo seleccionado no es válido.',
            'nivel_id.required' => 'Selecciona un nivel educativo.',
            'grado_id.required' => 'Selecciona un grado.',
            'generacion_id.required' => 'Selecciona una generación.',
            'semestre_id.required' => 'Selecciona un semestre para bachillerato.',
        ]);

        $existe = Grupo::query()
            ->where('id', '!=', $this->grupo_id)
            ->where('asignacion_grupo_id', $this->asignacion_grupo_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->when($this->esBachillerato, function ($query) {
                $query->where('semestre_id', $this->semestre_id);
            })
            ->when(!$this->esBachillerato, function ($query) {
                $query->whereNull('semestre_id');
            })
            ->exists();

        if ($existe) {
            $this->addError('asignacion_grupo_id', 'Ya existe un grupo con los mismos datos.');
            return;
        }

        $grupo = Grupo::query()->find($this->grupo_id);

        if (!$grupo) {
            $this->dispatch('swal', [
                'title' => 'El grupo no existe.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            $this->cerrarModal();
            return;
        }

        $grupo->update([
            'asignacion_grupo_id' => $this->asignacion_grupo_id,
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Grupo actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshGrupos');
        $this->dispatch('grupoActualizado');
        $this->dispatch('cerrar-modal-editar');

        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->reset([
            'grupo_id',
            'asignacion_grupo_id',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_nombre',
            'nivel_nombre',
            'grado_nombre',
            'esBachillerato',
        ]);

        $this->grados = collect();
        $this->generaciones = collect();
        $this->semestres = collect();

        $this->resetErrorBag();
    }

    #[On('refreshAsignacionGrupos')]
    public function render()
    {
        return view('livewire.grupo.editar-grupo', [
            'niveles' => Nivel::query()
                ->orderBy('id')
                ->get(),

            'asignacionGrupos' => AsignacionGrupo::query()
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
