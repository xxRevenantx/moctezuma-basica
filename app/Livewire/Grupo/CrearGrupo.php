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

class CrearGrupo extends Component
{
    public $asignacion_grupo_id = '';
    public $nivel_id = '';
    public $grado_id = '';
    public $generacion_id = '';
    public $semestre_id = '';

    public $grados = [];
    public $generaciones = [];
    public $semestres = [];

    public bool $esBachillerato = false;

    public function updatedNivelId()
    {
        $this->reset([
            'grado_id',
            'generacion_id',
            'semestre_id',
        ]);

        $this->esBachillerato = (int) $this->nivel_id === 4;

        $this->cargarDatosPorNivel();
    }

    public function cargarDatosPorNivel()
    {
        if (!$this->nivel_id) {
            $this->grados = [];
            $this->generaciones = [];
            $this->semestres = [];
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get();

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('status', true)
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->semestres = $this->esBachillerato
            ? Semestre::query()->orderBy('numero')->get()
            : collect();
    }

    public function guardarGrupo()
    {
        $this->validate([
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
            $this->addError('asignacion_grupo_id', 'Este grupo ya está registrado con los mismos datos.');
            return;
        }

        Grupo::create([
            'asignacion_grupo_id' => $this->asignacion_grupo_id,
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Grupo guardado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshGrupos');

        $this->reset([
            'asignacion_grupo_id',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grados',
            'generaciones',
            'semestres',
            'esBachillerato',
        ]);

        $this->resetErrorBag();
    }

    #[On('refreshAsignacionGrupos')]
    public function render()
    {
        return view('livewire.grupo.crear-grupo', [
            'niveles' => Nivel::query()->orderBy('id')->get(),

            'asignacionGrupos' => AsignacionGrupo::query()
                ->orderBy('nombre')
                ->get(),
        ]);
    }
}
