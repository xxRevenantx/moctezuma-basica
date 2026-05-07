<?php

namespace App\Livewire;

use App\Models\Grado;
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
    public ?int $promediar_semestre_id = null;
    public ?int $promediar_numero_materias = null;

    // =========================
    // Catálogos
    // =========================
    public array $promediar_grados = [];
    public array $promediar_semestres = [];

    // =========================
    // Listado
    // =========================
    public $configuracionesPromedio;

    public ?string $slug_nivel = null;
    public ?string $nombre_nivel = null;

    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    public function mount(?string $slug_nivel = null): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->configuracionesPromedio = collect();

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
        return (int) $this->promediar_nivel_id === 4 || $this->slug_nivel === 'bachillerato';
    }

    // =========================
    // Eventos reactivos
    // =========================
    public function updatedPromediarGradoId($value): void
    {
        $this->promediar_grado_id = filled($value) ? (int) $value : null;
        $this->promediar_semestre_id = null;
        $this->promediar_numero_materias = null;
        $this->promediar_semestres = [];

        if ($this->esBachillerato) {
            $this->cargarSemestresPromediar();
        }

        $this->cargarRegistroExistente();
    }

    public function updatedPromediarSemestreId($value): void
    {
        $this->promediar_semestre_id = filled($value) ? (int) $value : null;
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
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre ?? $item->grado ?? 'Grado ' . $item->id,
                ];
            })
            ->toArray();
    }

    public function cargarSemestresPromediar(): void
    {
        if (!$this->esBachillerato || !$this->promediar_grado_id) {
            $this->promediar_semestres = [];
            return;
        }

        $query = Semestre::query();

        // Si tu tabla semestres tiene grado_id, se filtra por grado.
        // Si no lo tiene, se cargan todos los semestres.
        if ($this->columnaExiste('semestres', 'grado_id')) {
            $query->where('grado_id', $this->promediar_grado_id);
        }

        $this->promediar_semestres = $query
            ->orderBy('numero')
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'numero' => $item->numero ?? null,
                    'nombre' => $item->nombre ?? null,
                    'semestre' => $item->semestre ?? null,
                ];
            })
            ->toArray();
    }

    public function cargarConfiguraciones(): void
    {
        if (!$this->promediar_nivel_id) {
            $this->configuracionesPromedio = collect();
            return;
        }

        $this->configuracionesPromedio = MateriaPromediarModel::query()
            ->with([
                'grado',
                'semestre',
            ])
            ->where('nivel_id', $this->promediar_nivel_id)
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->get();
    }

    // =========================
    // Cargar registro existente
    // =========================
    public function cargarRegistroExistente(): void
    {
        if (!$this->promediar_nivel_id || !$this->promediar_grado_id) {
            $this->promediar_numero_materias = null;
            return;
        }

        if ($this->esBachillerato && !$this->promediar_semestre_id) {
            $this->promediar_numero_materias = null;
            return;
        }

        $registro = MateriaPromediarModel::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $this->promediar_grado_id)
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
            'promediar_nivel_id' => [
                'required',
                'integer',
                'exists:niveles,id',
            ],
            'promediar_grado_id' => [
                'required',
                'integer',
                'exists:grados,id',
            ],
            'promediar_numero_materias' => [
                'required',
                'integer',
                'min:1',
            ],
        ];

        if ($this->esBachillerato) {
            $rules['promediar_semestre_id'] = [
                'required',
                'integer',
                'exists:semestres,id',
            ];
        } else {
            $rules['promediar_semestre_id'] = [
                'nullable',
            ];
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

        $this->dispatch('swal', [
            'title' => 'Configuración eliminada correctamente',
            'position' => 'top-end',
            'icon' => 'success',
        ]);
    }

    public function limpiarFormularioPromediar(): void
    {
        $this->reset([
            'promediar_grado_id',
            'promediar_semestre_id',
            'promediar_numero_materias',
        ]);

        $this->promediar_semestres = [];

        $this->resetValidation();
    }

    // =========================
    // Verificación simple de columnas
    // =========================
    private function columnaExiste(string $tabla, string $columna): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($tabla, $columna);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function render()
    {
        return view('livewire.materia-promediar');
    }
}
