<?php

namespace App\Livewire\Accion;

use App\Models\Accion;
use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\Grupo;
use App\Models\Grado;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\Semestre;
use Illuminate\Support\Str;
use Livewire\Component;

class AsignacionMateria extends Component
{
    // =========================
    // Formulario principal
    // =========================
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre = null;
    public ?int $profesor_id = null;

    public string $materia = '';
    public ?string $clave = null;
    public string $slug = '';
    public bool $calificable = true;

    // =========================
    // Campos extras
    // =========================
    public ?int $numero_materias_promediar = null;
    public string $materia_para_calificaciones = 'si';

    // =========================
    // Control visual
    // =========================
    public string $buscar = '';
    public bool $mostrarModal = false;
    public ?int $editandoId = null;
    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    // =========================
    // Datos
    // =========================
    public array $profesores = [];
    public $asignaciones;

    public $nivel;
    public $niveles;
    public array $grados = [];
    public array $grupos = [];
    public array $semestres = [];

    public string $slug_nivel;
    public string $slug_accion_actual;

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->get();

        $accionActual = Accion::query()
            ->where('slug', 'asignacion-de-materias')
            ->first();

        $this->slug_accion_actual = $accionActual?->slug ?? 'asignacion-de-materias';

        // El nivel se toma del nav actual y se usa para guardar en BD
        $this->nivel_id = $this->nivel->id;

        $this->cargarGrados();
        $this->cargarProfesores();
        $this->cargarAsignaciones();
    }

    // =========================
    // Propiedad calculada
    // =========================
    public function getEsBachilleratoProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'bachillerato';
    }

    // =========================
    // Cargar profesores por nivel
    // =========================
    public function cargarProfesores(): void
    {
        if (!$this->nivel_id) {
            $this->profesores = [];
            return;
        }

        $this->profesores = PersonaNivel::query()
            ->with('persona')
            ->where('nivel_id', $this->nivel_id)
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
                    'nombre' => mb_strtoupper($nombreCompleto),
                ];
            })
            ->unique('id')
            ->sortBy('nombre')
            ->values()
            ->toArray();
    }

    // =========================
    // Cargar grados
    // =========================
    public function cargarGrados(): void
    {
        if (!$this->nivel_id) {
            $this->grados = [];
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    // =========================
    // Cargar grupos
    // =========================
    public function cargarGrupos(): void
    {
        if (!$this->nivel_id || !$this->grado_id) {
            $this->grupos = [];
            return;
        }

        $this->grupos = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    // =========================
    // Cargar semestres por grado
    // =========================
    public function cargarSemestres(): void
    {
        if (!$this->esBachillerato || !$this->grado_id) {
            $this->semestres = [];
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get()
            ->toArray();
    }

    // =========================
    // Cargar asignaciones desde BD
    // =========================
    public function cargarAsignaciones(): void
    {
        $this->asignaciones = AsignacionMateriaModel::query()
            ->with(['nivel', 'grado', 'grupo', 'semestre', 'profesor'])
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('semestre')
            ->orderBy('materia')
            ->get();
    }

    // =========================
    // Eventos reactivos
    // =========================
    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
        $this->semestre = null;

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function updatedMateria($value): void
    {
        if (!$this->editandoId) {
            $this->slug = Str::slug($value);
        }
    }

    // =========================
    // Validaciones
    // =========================
    protected function rules(): array
    {
        $rules = [
            'nivel_id' => 'required|integer|exists:niveles,id',
            'grado_id' => 'required|integer|exists:grados,id',
            'grupo_id' => 'required|integer|exists:grupos,id',
            'profesor_id' => 'nullable|integer',
            'materia' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'calificable' => 'required|boolean',
            'materia_para_calificaciones' => 'required|in:si,no',
            'numero_materias_promediar' => 'nullable|integer|min:1',
        ];

        if ($this->esBachillerato) {
            $rules['semestre'] = 'required|integer|exists:semestres,id';
            $rules['clave'] = 'required|string|max:255';
        } else {
            $rules['semestre'] = 'nullable';
            $rules['clave'] = 'nullable';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'nivel_id.required' => 'No se encontró el nivel actual.',
            'nivel_id.exists' => 'El nivel actual no es válido.',

            'grado_id.required' => 'Selecciona un grado.',
            'grado_id.exists' => 'El grado seleccionado no es válido.',

            'grupo_id.required' => 'Selecciona un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no es válido.',

            'semestre.required' => 'Selecciona un semestre.',
            'semestre.exists' => 'El semestre seleccionado no es válido.',

            'profesor_id.integer' => 'El profesor seleccionado no es válido.',

            'materia.required' => 'La materia es obligatoria.',
            'clave.required' => 'La clave es obligatoria para bachillerato.',
            'slug.required' => 'El slug es obligatorio.',
            'calificable.required' => 'Debes indicar si la materia es calificable.',
            'materia_para_calificaciones.required' => 'Selecciona si la materia aplica para calificaciones.',
        ];
    }

    // =========================
    // Guardar / actualizar materia
    // =========================
    public function guardarMateria(): void
    {
        if (!$this->esBachillerato) {
            $this->semestre = null;
            $this->clave = null;
        }

        $this->validate();

        $datos = [
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'semestre' => $this->semestre,
            'profesor_id' => $this->profesor_id,
            'materia' => $this->materia,
            'clave' => $this->clave,
            'slug' => $this->slug,
            'calificable' => $this->calificable,
            'numero_materias_promediar' => $this->numero_materias_promediar,
            'materia_para_calificaciones' => $this->materia_para_calificaciones,
        ];

        if ($this->editandoId) {
            $registro = AsignacionMateriaModel::findOrFail($this->editandoId);
            $registro->update($datos);

            $this->ultimoRegistroId = $registro->id;
            $this->ultimoMovimiento = 'actualizado';

            $titulo = '¡Materia actualizada!';
        } else {
            $registro = AsignacionMateriaModel::create($datos);

            $this->ultimoRegistroId = $registro->id;
            $this->ultimoMovimiento = 'guardado';

            $titulo = '¡Materia asignada!';
        }

        $this->cargarAsignaciones();
        $this->limpiarFormulario();

        $this->dispatch('swal', [
            'title' => $titulo,
            'position' => 'top-end',
            'icon' => 'success',
        ]);
    }

    // =========================
    // Editar
    // =========================
    public function editar(int $id): void
    {
        $registro = AsignacionMateriaModel::findOrFail($id);

        $this->editandoId = $registro->id;
        $this->nivel_id = $registro->nivel_id;
        $this->grado_id = $registro->grado_id;

        $this->cargarGrupos();
        $this->cargarSemestres();

        $this->grupo_id = $registro->grupo_id;
        $this->semestre = $registro->semestre;
        $this->profesor_id = $registro->profesor_id;
        $this->materia = $registro->materia;
        $this->clave = $registro->clave;
        $this->slug = $registro->slug;
        $this->calificable = (bool) $registro->calificable;
        $this->numero_materias_promediar = $registro->numero_materias_promediar;
        $this->materia_para_calificaciones = $registro->materia_para_calificaciones ?? 'si';

        $this->resetErrorBag();

        $this->dispatch('abrir-formulario-materia');
    }

    // =========================
    // Eliminar
    // =========================
    public function eliminar(int $id): void
    {
        AsignacionMateriaModel::findOrFail($id)->delete();

        if ($this->editandoId === $id) {
            $this->limpiarFormulario();
        }

        if ($this->ultimoRegistroId === $id) {
            $this->ultimoRegistroId = null;
            $this->ultimoMovimiento = '';
        }

        $this->cargarAsignaciones();

        session()->flash('success', 'Asignación eliminada correctamente.');
    }

    // =========================
    // Utilidades
    // =========================
    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
        $this->editandoId = null;
        $this->limpiarFormulario();
        $this->resetValidation();
    }

    public function limpiarFormulario(): void
    {
        $this->editandoId = null;
        $this->nivel_id = $this->nivel->id ?? null;
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->semestre = null;
        $this->profesor_id = null;

        $this->materia = '';
        $this->clave = null;
        $this->slug = '';
        $this->calificable = true;

        $this->numero_materias_promediar = null;
        $this->materia_para_calificaciones = 'si';

        $this->cargarGrados();
        $this->grupos = [];
        $this->semestres = [];
        $this->cargarProfesores();
        $this->resetErrorBag();
    }

    public function getAsignacionesFiltradasProperty()
    {
        if (!$this->asignaciones) {
            return collect();
        }

        if (trim($this->buscar) === '') {
            return $this->asignaciones;
        }

        $buscar = mb_strtolower($this->buscar);

        return $this->asignaciones->filter(function ($item) use ($buscar) {
            return str_contains(mb_strtolower($item->materia ?? ''), $buscar)
                || str_contains(mb_strtolower($item->profesor?->nombre ?? ''), $buscar)
                || str_contains(mb_strtolower($item->grado?->nombre ?? ''), $buscar)
                || str_contains(mb_strtolower($item->grupo?->nombre ?? ''), $buscar)
                || str_contains(mb_strtolower($item->clave ?? ''), $buscar)
                || str_contains(mb_strtolower($item->slug ?? ''), $buscar);
        })->values();
    }

    public function render()
    {
        return view('livewire.accion.asignacion-materia');
    }
}
