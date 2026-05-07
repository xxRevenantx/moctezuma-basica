<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\Semestre;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AsignacionMateria extends Component
{
    public string $slug_nivel = '';

    public $nivel = null;

    public $buscar = '';

    public $editandoId = null;

    public $generacion_id = '';

    public $grado_id = '';

    public $semestre = '';

    public $grupo_id = '';

    public $materia_id = '';

    public $profesor_id = '';

    public $ultimoRegistroId = null;

    public $ultimoMovimiento = '';

    public function mount($slug_nivel)
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();
    }

    public function getEsBachilleratoProperty()
    {
        return (int) $this->nivel?->id === 4;
    }

    public function getNivelesProperty()
    {
        return Nivel::query()
            ->orderBy('id')
            ->get();
    }

    public function getGeneracionesProperty()
    {
        return Generacion::query()
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->generacion ?? $item->nombre ?? 'Generación ' . $item->id,
                ];
            });
    }

    public function getGradosProperty()
    {
        if (blank($this->nivel?->id)) {
            return collect();
        }

        return Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? $item->grado ?? $item->numero . '°',
                ];
            });
    }

    public function getSemestresProperty()
    {
        if (!$this->esBachillerato) {
            return collect();
        }

        return Semestre::query()
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'numero' => $item->numero ?? $item->semestre ?? $item->id,
                ];
            });
    }

    public function getGruposProperty()
    {
        if (blank($this->nivel?->id) || blank($this->generacion_id) || blank($this->grado_id)) {
            return collect();
        }

        return Grupo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when($this->esBachillerato, function ($query) {
                $query->where('semestre_id', $this->semestre);
            })
            ->orderBy('nombre')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? $item->grupo ?? 'Grupo ' . $item->id,
                ];
            });
    }

    public function getProfesoresProperty()
    {
        return Persona::query()
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => trim(
                        ($item->nombre ?? '') . ' ' .
                        ($item->apellido_paterno ?? '') . ' ' .
                        ($item->apellido_materno ?? '')
                    ),
                ];
            });
    }

    public function getMateriasDisponiblesProperty()
    {
        if (blank($this->nivel?->id) || blank($this->grado_id)) {
            return collect();
        }

        return Materia::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->when($this->esBachillerato, function ($query) {
                $query->where('semestre_id', $this->semestre);
            })
            ->when(!$this->esBachillerato, function ($query) {
                $query->whereNull('semestre_id');
            })
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    public function getAsignacionesFiltradasProperty()
    {
        return AsignacionMateriaModel::query()
            ->with([
                'materia.nivel',
                'materia.grado',
                'materia.semestre',
                'grupo',
                'profesor',
            ])
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel->id);
            })
            ->when(filled($this->buscar), function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('materia', function ($sub) {
                        $sub->where('materia', 'like', '%' . $this->buscar . '%')
                            ->orWhere('slug', 'like', '%' . $this->buscar . '%')
                            ->orWhere('clave', 'like', '%' . $this->buscar . '%');
                    })
                        ->orWhereHas('grupo', function ($sub) {
                            $sub->where('nombre', 'like', '%' . $this->buscar . '%');
                        })
                        ->orWhereHas('profesor', function ($sub) {
                            $sub->where('nombre', 'like', '%' . $this->buscar . '%')
                                ->orWhere('apellido_paterno', 'like', '%' . $this->buscar . '%')
                                ->orWhere('apellido_materno', 'like', '%' . $this->buscar . '%');
                        });
                });
            })
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->materia?->grado_id ?? 0) <=> ($b->materia?->grado_id ?? 0),
                fn($a, $b) => ($a->materia?->semestre_id ?? 0) <=> ($b->materia?->semestre_id ?? 0),
                fn($a, $b) => ($a->orden ?? 0) <=> ($b->orden ?? 0),
                fn($a, $b) => ($a->materia?->orden ?? 0) <=> ($b->materia?->orden ?? 0),
            ])
            ->values();
    }

    protected function rules()
    {
        return [
            'generacion_id' => [
                'required',
                'integer',
                'exists:generaciones,id',
            ],
            'grado_id' => [
                'required',
                'integer',
                'exists:grados,id',
            ],
            'semestre' => [
                Rule::requiredIf($this->esBachillerato),
                'nullable',
                'integer',
                'exists:semestres,id',
            ],
            'grupo_id' => [
                'required',
                'integer',
                'exists:grupos,id',
            ],
            'materia_id' => [
                'required',
                'integer',
                'exists:materias,id',
            ],
            'profesor_id' => [
                'nullable',
                'integer',
                'exists:personas,id',
            ],
        ];
    }

    protected function messages()
    {
        return [
            'generacion_id.required' => 'Selecciona una generación.',
            'grado_id.required' => 'Selecciona un grado.',
            'semestre.required' => 'Selecciona un semestre.',
            'grupo_id.required' => 'Selecciona un grupo.',
            'materia_id.required' => 'Selecciona una materia.',
            'materia_id.exists' => 'La materia seleccionada no existe.',
            'profesor_id.exists' => 'El profesor seleccionado no existe.',
        ];
    }

    public function updatedGeneracionId()
    {
        $this->reset([
            'grado_id',
            'semestre',
            'grupo_id',
            'materia_id',
        ]);
    }

    public function updatedGradoId()
    {
        $this->reset([
            'semestre',
            'grupo_id',
            'materia_id',
        ]);
    }

    public function updatedSemestre()
    {
        $this->reset([
            'grupo_id',
            'materia_id',
        ]);
    }

    public function updatedGrupoId()
    {
        $this->reset([
            'materia_id',
        ]);
    }

    public function guardarMateria()
    {
        $this->validate();

        $materia = Materia::query()
            ->where('id', $this->materia_id)
            ->first();

        if (!$materia) {
            $this->addError('materia_id', 'La materia seleccionada no existe.');
            return;
        }

        if ((int) $materia->nivel_id !== (int) $this->nivel->id) {
            $this->addError('materia_id', 'La materia no pertenece al nivel actual.');
            return;
        }

        if ((int) $materia->grado_id !== (int) $this->grado_id) {
            $this->addError('materia_id', 'La materia no pertenece al grado seleccionado.');
            return;
        }

        if ($this->esBachillerato && (int) $materia->semestre_id !== (int) $this->semestre) {
            $this->addError('materia_id', 'La materia no pertenece al semestre seleccionado.');
            return;
        }

        if (!$this->esBachillerato && !blank($materia->semestre_id)) {
            $this->addError('materia_id', 'La materia no corresponde a básica.');
            return;
        }

        $grupo = Grupo::query()
            ->where('id', $this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->when($this->esBachillerato, function ($query) {
                $query->where('semestre_id', $this->semestre);
            })
            ->first();

        if (!$grupo) {
            $this->addError('grupo_id', 'El grupo seleccionado no coincide con los filtros.');
            return;
        }

        $yaExiste = AsignacionMateriaModel::query()
            ->where('grupo_id', $this->grupo_id)
            ->where('materia_id', $this->materia_id)
            ->when($this->editandoId, function ($query) {
                $query->where('id', '!=', $this->editandoId);
            })
            ->exists();

        if ($yaExiste) {
            $this->addError('materia_id', 'Esta materia ya está asignada a este grupo.');
            return;
        }

        $profesorId = (bool) $materia->receso ? null : $this->profesor_id;

        if (!$materia->receso && blank($profesorId)) {
            $this->addError('profesor_id', 'Selecciona un profesor para esta materia.');
            return;
        }

        if ($this->editandoId) {
            $asignacion = AsignacionMateriaModel::findOrFail($this->editandoId);

            $asignacion->update([
                'materia_id' => $this->materia_id,
                'grupo_id' => $this->grupo_id,
                'profesor_id' => $profesorId,
            ]);

            $this->ultimoMovimiento = 'actualizado';
        } else {
            $siguienteOrden = AsignacionMateriaModel::query()
                ->where('grupo_id', $this->grupo_id)
                ->max('orden');

            $asignacion = AsignacionMateriaModel::create([
                'materia_id' => $this->materia_id,
                'grupo_id' => $this->grupo_id,
                'profesor_id' => $profesorId,
                'orden' => ((int) $siguienteOrden) + 1,
            ]);

            $this->ultimoMovimiento = 'registrado';
        }

        $this->ultimoRegistroId = $asignacion->id;

        $this->limpiarFormulario();

        $this->dispatch('cerrar-formulario-materia');

        $this->dispatch('swal', [
            'title' => 'Asignación guardada correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function editar($id)
    {
        $asignacion = AsignacionMateriaModel::query()
            ->with([
                'materia',
                'grupo',
            ])
            ->findOrFail($id);

        $this->editandoId = $asignacion->id;

        $this->generacion_id = $asignacion->grupo?->generacion_id;
        $this->grado_id = $asignacion->materia?->grado_id;
        $this->semestre = $asignacion->materia?->semestre_id;
        $this->grupo_id = $asignacion->grupo_id;
        $this->materia_id = $asignacion->materia_id;
        $this->profesor_id = $asignacion->profesor_id;

        $this->dispatch('abrir-formulario-materia');
        $this->dispatch('scroll-editar-materia');
    }

    public function eliminar($id)
    {
        $asignacion = AsignacionMateriaModel::find($id);

        if (!$asignacion) {
            return;
        }

        $asignacion->delete();

        $this->dispatch('swal', [
            'title' => 'Asignación eliminada correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function limpiarFormulario()
    {
        $this->reset([
            'editandoId',
            'generacion_id',
            'grado_id',
            'semestre',
            'grupo_id',
            'materia_id',
            'profesor_id',
        ]);

        $this->resetValidation();
    }

    public function ordenarMateriasPorGradoJs($gradoId, $ids)
    {
        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $index => $id) {
            AsignacionMateriaModel::query()
                ->where('id', $id)
                ->whereHas('materia', function ($query) use ($gradoId) {
                    $query->where('grado_id', $gradoId);
                })
                ->update([
                    'orden' => $index + 1,
                ]);
        }

        $this->dispatch('swal', [
            'title' => 'Orden actualizado',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('livewire.accion.asignacion-materia', [
            'niveles' => $this->niveles,
            'generaciones' => $this->generaciones,
            'grados' => $this->grados,
            'semestres' => $this->semestres,
            'grupos' => $this->grupos,
            'profesores' => $this->profesores,
        ]);
    }
}
