<?php

namespace App\Livewire;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\MateriaPromediar as MateriaPromediarModel;
use App\Models\Nivel;
use App\Models\Semestre;
use Livewire\Component;

class MateriaPromediar extends Component
{
    // =========================
    // Campos del formulario
    // =========================
    public ?int $promediar_nivel_id = null;
    public ?int $promediar_grado_id = null;
    public ?int $promediar_grupo_id = null;
    public ?int $promediar_semestre_id = null;
    public ?int $promediar_numero_materias = null;

    // =========================
    // Catálogos
    // =========================
    public array $promediar_grados = [];
    public array $promediar_grupos = [];
    public array $promediar_semestres = [];

    // =========================
    // Listado
    // =========================
    public $configuracionesPromedio = [];

    public ?string $slug_nivel = null;
    public ?string $nombre_nivel = null;

    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    public function mount(?string $slug_nivel = null): void
    {
        $this->slug_nivel = $slug_nivel;

        if ($this->slug_nivel) {
            $nivelActual = Nivel::query()
                ->select('id', 'nombre', 'slug')
                ->where('slug', $this->slug_nivel)
                ->first();

            $this->promediar_nivel_id = $nivelActual?->id;
            $this->nombre_nivel = $nivelActual?->nombre;
            $this->slug_nivel = $nivelActual?->slug;
        }

        $this->cargarGradosPromediar();
        $this->cargarConfiguraciones();
    }

    // =========================
    // Propiedad calculada
    // =========================
    public function getEsBachilleratoProperty(): bool
    {
        return $this->slug_nivel === 'bachillerato';
    }

    // =========================
    // Eventos reactivos
    // =========================
    public function updatedPromediarGradoId(): void
    {
        $this->promediar_grupo_id = null;
        $this->promediar_semestre_id = null;
        $this->promediar_numero_materias = null;

        $this->promediar_grupos = [];
        $this->promediar_semestres = [];

        $this->cargarGruposPromediar();

        if ($this->esBachillerato) {
            $this->cargarSemestresPromediar();
        }

        $this->cargarRegistroExistente();
    }

    public function updatedPromediarGrupoId(): void
    {
        $this->promediar_numero_materias = null;
        $this->cargarRegistroExistente();
    }

    public function updatedPromediarSemestreId(): void
    {
        $this->promediar_numero_materias = null;
        $this->cargarRegistroExistente();
    }

    // =========================
    // Cargas
    // =========================
    public function cargarGradosPromediar(): void
    {
        if (!$this->promediar_nivel_id) {
            $this->promediar_grados = [];
            return;
        }

        $this->promediar_grados = Grado::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    public function cargarGruposPromediar(): void
    {
        if (!$this->promediar_nivel_id || !$this->promediar_grado_id) {
            $this->promediar_grupos = [];
            return;
        }

        $this->promediar_grupos = Grupo::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $this->promediar_grado_id)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    public function cargarSemestresPromediar(): void
    {
        if (!$this->esBachillerato || !$this->promediar_grado_id) {
            $this->promediar_semestres = [];
            return;
        }

        $this->promediar_semestres = Semestre::query()
            ->where('grado_id', $this->promediar_grado_id)
            ->orderBy('numero')
            ->get()
            ->toArray();
    }

    public function cargarConfiguraciones(): void
    {
        if (!$this->promediar_nivel_id) {
            $this->configuracionesPromedio = collect();
            return;
        }

        $this->configuracionesPromedio = MateriaPromediarModel::query()
            ->with(['grado', 'grupo', 'semestre'])
            ->where('nivel_id', $this->promediar_nivel_id)
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('semestre_id')
            ->get();
    }

    // =========================
    // Cargar registro existente
    // =========================
    public function cargarRegistroExistente(): void
    {
        if (!$this->promediar_nivel_id || !$this->promediar_grado_id || !$this->promediar_grupo_id) {
            $this->promediar_numero_materias = null;
            return;
        }

        // Si es bachillerato, obligo a que exista semestre para buscar
        if ($this->esBachillerato && !$this->promediar_semestre_id) {
            $this->promediar_numero_materias = null;
            return;
        }

        $registro = MateriaPromediarModel::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $this->promediar_grado_id)
            ->where('grupo_id', $this->promediar_grupo_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->promediar_semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->first();

        $this->promediar_numero_materias = $registro?->numero_materias;
    }

    // =========================
    // Validaciones
    // =========================
    protected function rules(): array
    {
        $rules = [
            'promediar_nivel_id' => 'required|integer|exists:niveles,id',
            'promediar_grado_id' => 'required|integer|exists:grados,id',
            'promediar_grupo_id' => 'required|integer|exists:grupos,id',
            'promediar_numero_materias' => 'required|integer|min:1',
        ];

        if ($this->esBachillerato) {
            $rules['promediar_semestre_id'] = 'required|integer|exists:semestres,id';
        } else {
            $rules['promediar_semestre_id'] = 'nullable';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'promediar_nivel_id.required' => 'No se encontró el nivel actual.',
            'promediar_nivel_id.exists' => 'El nivel actual no es válido.',

            'promediar_grado_id.required' => 'Selecciona un grado.',
            'promediar_grado_id.exists' => 'El grado seleccionado no es válido.',

            'promediar_grupo_id.required' => 'Selecciona un grupo.',
            'promediar_grupo_id.exists' => 'El grupo seleccionado no es válido.',

            'promediar_semestre_id.required' => 'Selecciona un semestre.',
            'promediar_semestre_id.exists' => 'El semestre seleccionado no es válido.',

            'promediar_numero_materias.required' => 'Ingresa el número de materias.',
            'promediar_numero_materias.integer' => 'El número de materias debe ser numérico.',
            'promediar_numero_materias.min' => 'El número de materias debe ser mayor a 0.',
        ];
    }

    // =========================
    // Guardar o actualizar
    // =========================
    public function guardarMateriasPromediar(): void
    {
        if (!$this->esBachillerato) {
            $this->promediar_semestre_id = null;
        }

        $this->validate();

        $registroExistente = MateriaPromediarModel::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $this->promediar_grado_id)
            ->where('grupo_id', $this->promediar_grupo_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->promediar_semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->first();

        $fueActualizacion = (bool) $registroExistente;

        $registro = MateriaPromediarModel::updateOrCreate(
            [
                'nivel_id' => $this->promediar_nivel_id,
                'grado_id' => $this->promediar_grado_id,
                'grupo_id' => $this->promediar_grupo_id,
                'semestre_id' => $this->promediar_semestre_id,
            ],
            [
                'numero_materias' => $this->promediar_numero_materias,
            ]
        );

        $this->ultimoRegistroId = $registro->id;
        $this->ultimoMovimiento = $fueActualizacion ? 'actualizado' : 'guardado';

        $this->cargarConfiguraciones();
        $this->cargarRegistroExistente();

        $this->dispatch('swal', [
            'title' => $fueActualizacion ? '¡Configuración actualizada!' : '¡Configuración guardada!',
            'position' => 'top-end',
            'icon' => 'success',
        ]);
    }

    public function eliminarConfiguracionPromedio(int $id): void
    {
        MateriaPromediarModel::findOrFail($id)->delete();

        if ($this->ultimoRegistroId === $id) {
            $this->ultimoRegistroId = null;
            $this->ultimoMovimiento = '';
        }

        $this->cargarConfiguraciones();
        $this->cargarRegistroExistente();

        session()->flash('success', 'Configuración eliminada correctamente.');
    }

    public function limpiarFormularioPromediar(): void
    {
        $this->promediar_grado_id = null;
        $this->promediar_grupo_id = null;
        $this->promediar_semestre_id = null;
        $this->promediar_numero_materias = null;

        $this->promediar_grupos = [];
        $this->promediar_semestres = [];

        $this->cargarGradosPromediar();
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.materia-promediar');
    }
}
