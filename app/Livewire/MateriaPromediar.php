<?php

namespace App\Livewire;

use App\Models\Grado;
use App\Models\Materia;
use App\Models\MateriaPromediar as MateriaPromediarModel;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class MateriaPromediar extends Component
{
    public ?int $promediar_nivel_id = null;
    public ?int $promediar_grado_id = null;
    public ?int $promediar_semestre_id = null;
    public ?int $promediar_numero_materias = null;

    public array $promediar_grados = [];
    public array $promediar_semestres = [];

    public $configuracionesPromedio;

    public ?string $slug_nivel = null;
    public ?string $nombre_nivel = null;

    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    public function mount(?string $slug_nivel = null): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->configuracionesPromedio = collect();

        $nivelActual = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->when($slug_nivel, fn ($query) => $query->where('slug', $slug_nivel))
            ->first();

        $this->promediar_nivel_id = $nivelActual?->id;
        $this->nombre_nivel = $nivelActual?->nombre;
        $this->slug_nivel = $nivelActual?->slug;

        $this->cargarGradosPromediar();
        $this->cargarConfiguraciones();
    }

    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->promediar_nivel_id === 4 || $this->slug_nivel === 'bachillerato';
    }

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
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'nombre' => $item->nombre ?? $item->grado ?? 'Grado ' . $item->id,
            ])
            ->toArray();
    }

    public function cargarSemestresPromediar(): void
    {
        if (!$this->esBachillerato || !$this->promediar_grado_id) {
            $this->promediar_semestres = [];
            return;
        }

        $idsDesdeMaterias = Materia::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $this->promediar_grado_id)
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id');

        $query = Semestre::query();

        if ($idsDesdeMaterias->isNotEmpty()) {
            $query->whereIn('id', $idsDesdeMaterias);
        } elseif ($this->columnaExiste('semestres', 'grado_id')) {
            $query->where('grado_id', $this->promediar_grado_id);
        }

        $this->promediar_semestres = $query
            ->orderBy('orden_global')
            ->orderBy('numero')
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'numero' => $item->numero ?? null,
                'nombre' => $item->nombre ?? null,
                'semestre' => $item->semestre ?? null,
            ])
            ->toArray();
    }

    public function cargarConfiguraciones(): void
    {
        if (!$this->promediar_nivel_id) {
            $this->configuracionesPromedio = collect();
            return;
        }

        $this->configuracionesPromedio = MateriaPromediarModel::query()
            ->with(['grado', 'semestre'])
            ->where('nivel_id', $this->promediar_nivel_id)
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->get();
    }

    private function consultaConfiguracionSeleccionada()
    {
        if (!$this->promediar_nivel_id || !$this->promediar_grado_id) {
            return null;
        }

        if ($this->esBachillerato && !$this->promediar_semestre_id) {
            return null;
        }

        return MateriaPromediarModel::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $this->promediar_grado_id)
            ->when(
                $this->esBachillerato,
                fn ($query) => $query->where('semestre_id', $this->promediar_semestre_id),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->first();
    }

    private function contarMateriasCalificables(?int $gradoId, ?int $semestreId = null): int
    {
        if (!$this->promediar_nivel_id || !$gradoId) {
            return 0;
        }

        $query = Materia::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->where('grado_id', $gradoId);

        if ($this->esBachillerato) {
            $query->where('semestre_id', $semestreId);
            ReglasMateriaBachillerato::aplicarPromediables($query, '');
        } else {
            $query->where('calificable', true)
                ->whereNull('semestre_id');
        }

        return $query->count();
    }

    public function getMateriasCalificablesDetectadasProperty(): int
    {
        if ($this->esBachillerato && !$this->promediar_semestre_id) {
            return 0;
        }

        return $this->contarMateriasCalificables(
            $this->promediar_grado_id,
            $this->promediar_semestre_id
        );
    }

    public function getConfiguracionSeleccionadaProperty(): ?MateriaPromediarModel
    {
        return $this->consultaConfiguracionSeleccionada();
    }

    public function getNumeroMateriasEfectivoProperty(): int
    {
        $configurado = (int) ($this->configuracionSeleccionada?->numero_materias ?? 0);

        if ($configurado > 0) {
            return $configurado;
        }

        return $this->esBachillerato
            ? $this->materiasCalificablesDetectadas
            : 0;
    }

    public function getFuenteNumeroMateriasProperty(): string
    {
        if ($this->configuracionSeleccionada) {
            return 'configurada';
        }

        return $this->esBachillerato ? 'automatica' : 'pendiente';
    }

    public function getCoberturaPromedioProperty(): Collection
    {
        if (!$this->promediar_nivel_id) {
            return collect();
        }

        $grados = Grado::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $configuraciones = $this->configuracionesPromedio
            ->keyBy(fn ($item) => (int) $item->grado_id . '|' . (int) ($item->semestre_id ?: 0));

        return $grados->flatMap(function ($grado) use ($configuraciones) {
            if (!$this->esBachillerato) {
                $clave = (int) $grado->id . '|0';
                $configuracion = $configuraciones->get($clave);
                $automaticas = $this->contarMateriasCalificables((int) $grado->id);

                return [[
                    'grado' => $grado->nombre ?? '—',
                    'semestre' => null,
                    'detectadas' => $automaticas,
                    'efectivas' => (int) ($configuracion?->numero_materias ?: 0),
                    'fuente' => $configuracion ? 'Configurada' : 'Pendiente',
                    'configuracion_id' => $configuracion?->id,
                ]];
            }

            $semestresIds = Materia::query()
                ->where('nivel_id', $this->promediar_nivel_id)
                ->where('grado_id', $grado->id)
                ->whereNotNull('semestre_id')
                ->distinct()
                ->pluck('semestre_id');

            $semestres = Semestre::query()
                ->whereIn('id', $semestresIds)
                ->orderBy('orden_global')
                ->orderBy('numero')
                ->get();

            return $semestres->map(function ($semestre) use ($grado, $configuraciones) {
                $clave = (int) $grado->id . '|' . (int) $semestre->id;
                $configuracion = $configuraciones->get($clave);
                $automaticas = $this->contarMateriasCalificables((int) $grado->id, (int) $semestre->id);

                return [
                    'grado' => $grado->nombre ?? '—',
                    'semestre' => $semestre->numero ? $semestre->numero . '° semestre' : 'Semestre',
                    'detectadas' => $automaticas,
                    'efectivas' => (int) ($configuracion?->numero_materias ?: $automaticas),
                    'fuente' => $configuracion ? 'Configurada' : 'Automática',
                    'configuracion_id' => $configuracion?->id,
                ];
            });
        })->values();
    }

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

        $registro = $this->consultaConfiguracionSeleccionada();

        $this->promediar_numero_materias = $registro?->numero_materias
            ?? ($this->esBachillerato ? ($this->materiasCalificablesDetectadas ?: null) : null);
    }

    protected function rules(): array
    {
        $rules = [
            'promediar_nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'promediar_grado_id' => ['required', 'integer', 'exists:grados,id'],
            'promediar_numero_materias' => ['required', 'integer', 'min:1', 'max:100'],
        ];

        $rules['promediar_semestre_id'] = $this->esBachillerato
            ? ['required', 'integer', 'exists:semestres,id']
            : ['nullable'];

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'promediar_nivel_id.required' => 'No se encontró el nivel actual.',
            'promediar_grado_id.required' => 'Selecciona un grado.',
            'promediar_semestre_id.required' => 'Selecciona un semestre.',
            'promediar_numero_materias.required' => 'Ingresa el número de materias.',
            'promediar_numero_materias.integer' => 'El número de materias debe ser numérico.',
            'promediar_numero_materias.min' => 'El número de materias debe ser mayor a 0.',
            'promediar_numero_materias.max' => 'El número de materias no puede ser mayor a 100.',
        ];
    }

    public function guardarMateriasPromediar(): void
    {
        if (!$this->esBachillerato) {
            $this->promediar_semestre_id = null;
        }

        $this->validate();

        $registroExistente = $this->consultaConfiguracionSeleccionada();
        $fueActualizacion = (bool) $registroExistente;

        if ($registroExistente) {
            $registroExistente->update([
                'numero_materias' => $this->promediar_numero_materias,
            ]);
            $registro = $registroExistente->fresh();
        } else {
            $registro = MateriaPromediarModel::query()->create([
                'nivel_id' => $this->promediar_nivel_id,
                'grado_id' => $this->promediar_grado_id,
                'semestre_id' => $this->promediar_semestre_id,
                'numero_materias' => $this->promediar_numero_materias,
            ]);
        }

        $this->ultimoRegistroId = $registro->id;
        $this->ultimoMovimiento = $fueActualizacion ? 'actualizado' : 'guardado';

        $this->cargarConfiguraciones();
        $this->cargarRegistroExistente();

        $this->dispatch('swal', [
            'title' => $fueActualizacion ? 'Configuración actualizada' : 'Configuración guardada',
            'text' => 'Este número tendrá prioridad. Si se elimina, en bachillerato se usarán automáticamente solo las materias calificables que no sean extra ni receso.',
            'position' => 'top-end',
            'icon' => 'success',
        ]);
    }

    public function eliminarConfiguracionPromedio(int $id): void
    {
        $registro = MateriaPromediarModel::query()
            ->where('nivel_id', $this->promediar_nivel_id)
            ->findOrFail($id);

        $registro->delete();

        if ($this->ultimoRegistroId === $id) {
            $this->ultimoRegistroId = null;
            $this->ultimoMovimiento = '';
        }

        $this->cargarConfiguraciones();
        $this->cargarRegistroExistente();

        $this->dispatch('swal', [
            'title' => 'Configuración eliminada',
            'text' => 'Ahora se utilizará automáticamente el total de materias calificables de bachillerato, excluyendo materias extra y recesos.',
            'position' => 'top-end',
            'icon' => 'success',
        ]);
    }

    public function restablecerAutomatico(): void
    {
        $registro = $this->consultaConfiguracionSeleccionada();

        if ($registro) {
            $registro->delete();
        }

        $this->ultimoRegistroId = null;
        $this->ultimoMovimiento = '';
        $this->cargarConfiguraciones();
        $this->cargarRegistroExistente();

        $this->dispatch('swal', [
            'title' => 'Cálculo automático activado',
            'text' => 'En bachillerato se tomarán únicamente materias calificables que no sean extra ni receso.',
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

    private function columnaExiste(string $tabla, string $columna): bool
    {
        try {
            return Schema::hasColumn($tabla, $columna);
        } catch (\Throwable) {
            return false;
        }
    }

    public function render()
    {
        return view('livewire.materia-promediar');
    }
}
