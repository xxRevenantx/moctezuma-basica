<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use Livewire\Component;

class AsignacionMateria extends Component
{
    public string $slug_nivel = '';

    public $nivel = null;

    public string $buscar = '';

    public $editandoId = null;

    public $grupo_id = '';

    public $materia_id = '';

    public $profesor_id = '';

    public string $buscarProfesor = '';

    public $ultimoRegistroId = null;

    public string $ultimoMovimiento = '';

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

    public function getGruposProperty()
    {
        if (blank($this->nivel?->id)) {
            return collect();
        }

        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,nivel_id',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('generacion', function ($query) {
                $query->where('status', 1);
            })
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->grado_id ?? 0) <=> ($b->grado_id ?? 0),
                fn ($a, $b) => ($a->semestre_id ?? 0) <=> ($b->semestre_id ?? 0),
                fn ($a, $b) => strcmp(
                    $a->asignacionGrupo?->nombre ?? '',
                    $b->asignacionGrupo?->nombre ?? ''
                ),
            ])
            ->map(function ($grupo) {
                $nombreGrupo = $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';

                $generacion = $grupo->generacion
                    ? $grupo->generacion->anio_ingreso . ' - ' . $grupo->generacion->anio_egreso
                    : 'Sin generación';

                $grado = $grupo->grado?->nombre ?? 'Sin grado';

                $semestre = $grupo->semestre
                    ? ' | ' . $grupo->semestre->numero . '° semestre'
                    : '';

                return [
                    'id' => $grupo->id,
                    'nombre' => $grado . ' | Grupo ' . $nombreGrupo . ' | Gen. ' . $generacion . $semestre,
                ];
            })
            ->values();
    }

    public function getGrupoSeleccionadoProperty()
    {
        if (blank($this->grupo_id)) {
            return null;
        }

        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,nivel_id',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero',
            ])
            ->where('id', $this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->first();
    }

    public function getMateriasDisponiblesProperty()
    {
        $grupo = $this->grupoSeleccionado;

        if (!$grupo) {
            return collect();
        }

        return Materia::query()
            ->where('nivel_id', $grupo->nivel_id)
            ->where('grado_id', $grupo->grado_id)
            ->when($this->esBachillerato, function ($query) use ($grupo) {
                $query->where('semestre_id', $grupo->semestre_id);
            })
            ->when(!$this->esBachillerato, function ($query) {
                $query->whereNull('semestre_id');
            })
            ->orderBy('orden')
            ->orderBy('materia')
            ->get();
    }

    public function getProfesoresProperty()
    {
        if (blank($this->nivel?->id)) {
            return collect();
        }

        return PersonaNivel::query()
            ->with('persona')
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('persona')
            ->get()
            ->map(function ($registro) {
                $persona = $registro->persona;

                $nombreCompleto = trim(
                    ($persona->titulo ?? '') . ' ' .
                    ($persona->nombre ?? '') . ' ' .
                    ($persona->apellido_paterno ?? '') . ' ' .
                    ($persona->apellido_materno ?? '')
                );

                return [
                    'id' => $persona->id,
                    'nombre' => $nombreCompleto,
                    'buscar' => mb_strtolower($nombreCompleto),
                ];
            })
            ->filter(fn ($item) => filled($item['nombre']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    public function getProfesoresFiltradosProperty()
    {
        $buscar = mb_strtolower(trim($this->buscarProfesor));

        if ($buscar === '') {
            return $this->profesores;
        }

        return $this->profesores
            ->filter(function ($item) use ($buscar) {
                return str_contains($item['buscar'], $buscar);
            })
            ->values();
    }

    public function getAsignacionesFiltradasProperty()
    {
        return AsignacionMateriaModel::query()
            ->with([
                'materia.nivel',
                'materia.grado',
                'materia.semestre',
                'grupo.asignacionGrupo',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'profesor',
            ])
            ->whereHas('grupo', function ($query) {
                $query->where('nivel_id', $this->nivel->id);
            })
            ->when(filled($this->buscar), function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('materia', function ($sub) {
                        $sub->where('materia', 'like', '%' . $this->buscar . '%')
                            ->orWhere('slug', 'like', '%' . $this->buscar . '%')
                            ->orWhere('clave', 'like', '%' . $this->buscar . '%');
                    })
                    ->orWhereHas('grupo.asignacionGrupo', function ($sub) {
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
                fn ($a, $b) => ($a->grupo?->grado_id ?? 0) <=> ($b->grupo?->grado_id ?? 0),
                fn ($a, $b) => ($a->grupo?->semestre_id ?? 0) <=> ($b->grupo?->semestre_id ?? 0),
                fn ($a, $b) => strcmp(
                    $a->grupo?->asignacionGrupo?->nombre ?? '',
                    $b->grupo?->asignacionGrupo?->nombre ?? ''
                ),
                fn ($a, $b) => ($a->orden ?? 0) <=> ($b->orden ?? 0),
                fn ($a, $b) => ($a->materia?->orden ?? 0) <=> ($b->materia?->orden ?? 0),
            ])
            ->values();
    }

    protected function rules()
    {
        return [
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
            'grupo_id.required' => 'Selecciona un grupo.',
            'materia_id.required' => 'Selecciona una materia.',
            'materia_id.exists' => 'La materia seleccionada no existe.',
            'profesor_id.exists' => 'El profesor seleccionado no existe.',
        ];
    }

    public function updatedGrupoId()
    {
        $this->reset([
            'materia_id',
        ]);

        $this->resetValidation([
            'grupo_id',
            'materia_id',
        ]);
    }

    public function updatedBuscarProfesor()
    {
        if (blank($this->buscarProfesor)) {
            $this->profesor_id = null;
        }
    }

    public function guardarMateria()
    {
        $this->validate();

        $grupo = Grupo::query()
            ->where('id', $this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->first();

        if (!$grupo) {
            $this->addError('grupo_id', 'El grupo seleccionado no pertenece al nivel actual.');
            return;
        }

        $materia = Materia::query()
            ->where('id', $this->materia_id)
            ->first();

        if (!$materia) {
            $this->addError('materia_id', 'La materia seleccionada no existe.');
            return;
        }

        if ((int) $materia->nivel_id !== (int) $grupo->nivel_id) {
            $this->addError('materia_id', 'La materia no pertenece al nivel del grupo.');
            return;
        }

        if ((int) $materia->grado_id !== (int) $grupo->grado_id) {
            $this->addError('materia_id', 'La materia no pertenece al grado del grupo.');
            return;
        }

        if ($this->esBachillerato && (int) $materia->semestre_id !== (int) $grupo->semestre_id) {
            $this->addError('materia_id', 'La materia no pertenece al semestre del grupo.');
            return;
        }

        if (!$this->esBachillerato && filled($materia->semestre_id)) {
            $this->addError('materia_id', 'La materia no corresponde a básica.');
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

        if (!$materia->receso) {
            $profesorRelacionado = PersonaNivel::query()
                ->where('persona_id', $profesorId)
                ->where('nivel_id', $this->nivel->id)
                ->exists();

            if (!$profesorRelacionado) {
                $this->addError('profesor_id', 'El profesor seleccionado no pertenece al nivel actual.');
                return;
            }
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
                'profesor',
            ])
            ->findOrFail($id);

        $this->editandoId = $asignacion->id;
        $this->grupo_id = $asignacion->grupo_id;
        $this->materia_id = $asignacion->materia_id;
        $this->profesor_id = $asignacion->profesor_id;

        $this->buscarProfesor = trim(
            ($asignacion->profesor?->titulo ?? '') . ' ' .
            ($asignacion->profesor?->nombre ?? '') . ' ' .
            ($asignacion->profesor?->apellido_paterno ?? '') . ' ' .
            ($asignacion->profesor?->apellido_materno ?? '')
        );

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
            'grupo_id',
            'materia_id',
            'profesor_id',
            'buscarProfesor',
        ]);

        $this->resetValidation();
    }

    public function ordenarMateriasPorGrupoJs($grupoId, $ids)
    {
        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $index => $id) {
            AsignacionMateriaModel::query()
                ->where('id', $id)
                ->where('grupo_id', $grupoId)
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
            'grupos' => $this->grupos,
            'profesores' => $this->profesores,
        ]);
    }
}
