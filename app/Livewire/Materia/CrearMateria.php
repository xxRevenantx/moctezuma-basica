<?php

namespace App\Livewire\Materia;

use App\Models\Grado;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CrearMateria extends Component
{
    public string $buscar = '';

    public ?int $filtro_nivel_id = null;
    public ?int $filtro_grado_id = null;
    public ?int $filtro_semestre_id = null;
    public string $filtro_tipo = '';

    public ?int $editandoId = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;

    public string $materia = '';
    public ?string $clave = null;
    public string $slug = '';

    public bool $calificable = true;
    public bool $extra = false;
    public bool $receso = false;

    public $niveles;
    public $gradosFormulario;
    public $semestresFormulario;

    public $gradosFiltro;
    public $semestresFiltro;

    public function mount(): void
    {
        $this->niveles = collect();
        $this->gradosFormulario = collect();
        $this->semestresFormulario = collect();

        $this->gradosFiltro = collect();
        $this->semestresFiltro = collect();

        $this->cargarCatalogos();
    }

    public function cargarCatalogos(): void
    {
        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->cargarGradosFormulario();
        $this->cargarSemestresFormulario();

        $this->cargarGradosFiltro();
        $this->cargarSemestresFiltro();
    }

    public function getEsBachilleratoFormularioProperty(): bool
    {
        return $this->esNivelBachillerato($this->nivel_id);
    }

    public function getEsBachilleratoFiltroProperty(): bool
    {
        return $this->esNivelBachillerato($this->filtro_nivel_id);
    }

    private function esNivelBachillerato(?int $nivelId): bool
    {
        if (!$nivelId) {
            return false;
        }

        $nivel = $this->niveles?->firstWhere('id', $nivelId);

        return (int) $nivelId === 4 || ($nivel?->slug === 'bachillerato');
    }

    public function updatedBuscar(): void
    {
        //
    }

    public function updatedFiltroNivelId($value): void
    {
        $this->filtro_nivel_id = $value ? (int) $value : null;
        $this->filtro_grado_id = null;
        $this->filtro_semestre_id = null;

        $this->cargarGradosFiltro();
        $this->cargarSemestresFiltro();
    }

    public function updatedFiltroGradoId($value): void
    {
        $this->filtro_grado_id = $value ? (int) $value : null;
        $this->filtro_semestre_id = null;

        $this->cargarSemestresFiltro();
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;
        $this->grado_id = null;
        $this->semestre_id = null;

        $this->cargarGradosFormulario();
        $this->cargarSemestresFormulario();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value ? (int) $value : null;
        $this->semestre_id = null;

        $this->cargarSemestresFormulario();
    }

    public function updatedMateria($value): void
    {
        if (!$this->editandoId || blank($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug($value): void
    {
        $this->slug = Str::slug($value);
    }

    public function updatedClave($value): void
    {
        $this->clave = filled($value) ? mb_strtoupper(trim($value)) : null;
    }

    public function updatedReceso($value): void
    {
        if ((bool) $value) {
            $this->calificable = false;
            $this->extra = true;

            if (blank($this->materia)) {
                $this->materia = 'Receso';
                $this->slug = 'receso';
            }
        }
    }

    private function cargarGradosFormulario(): void
    {
        if (!$this->nivel_id) {
            $this->gradosFormulario = collect();
            return;
        }

        $this->gradosFormulario = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function cargarSemestresFormulario(): void
    {
        if (!$this->grado_id || !$this->esBachilleratoFormulario) {
            $this->semestresFormulario = collect();
            return;
        }

        $this->semestresFormulario = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get();
    }

    private function cargarGradosFiltro(): void
    {
        if (!$this->filtro_nivel_id) {
            $this->gradosFiltro = collect();
            return;
        }

        $this->gradosFiltro = Grado::query()
            ->where('nivel_id', $this->filtro_nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function cargarSemestresFiltro(): void
    {
        if (!$this->filtro_grado_id || !$this->esBachilleratoFiltro) {
            $this->semestresFiltro = collect();
            return;
        }

        $this->semestresFiltro = Semestre::query()
            ->where('grado_id', $this->filtro_grado_id)
            ->orderBy('numero')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'nivel_id' => [
                'required',
                'integer',
                Rule::exists('niveles', 'id'),
            ],
            'grado_id' => [
                'required',
                'integer',
                Rule::exists('grados', 'id')->where('nivel_id', $this->nivel_id),
            ],
            'semestre_id' => [
                Rule::requiredIf($this->esBachilleratoFormulario),
                'nullable',
                'integer',
                Rule::exists('semestres', 'id')->where('grado_id', $this->grado_id),
            ],
            'materia' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],
            'clave' => [
                'nullable',
                'string',
                'max:50',
            ],
            'slug' => [
                'required',
                'string',
                'max:180',
                'alpha_dash',
            ],
            'calificable' => [
                'required',
                'boolean',
            ],
            'extra' => [
                'required',
                'boolean',
            ],
            'receso' => [
                'required',
                'boolean',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'nivel_id.required' => 'Selecciona un nivel.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',

            'grado_id.required' => 'Selecciona un grado.',
            'grado_id.exists' => 'El grado no pertenece al nivel seleccionado.',

            'semestre_id.required' => 'Selecciona un semestre para bachillerato.',
            'semestre_id.exists' => 'El semestre no pertenece al grado seleccionado.',

            'materia.required' => 'Escribe el nombre de la materia.',
            'materia.min' => 'El nombre de la materia debe tener al menos 2 caracteres.',
            'materia.max' => 'El nombre de la materia no debe pasar de 150 caracteres.',

            'clave.max' => 'La clave no debe pasar de 50 caracteres.',

            'slug.required' => 'El slug es obligatorio.',
            'slug.alpha_dash' => 'El slug solo puede llevar letras, números, guiones y guiones bajos.',
            'slug.max' => 'El slug no debe pasar de 180 caracteres.',

            'calificable.boolean' => 'El valor de calificable no es válido.',
            'extra.boolean' => 'El valor de extra no es válido.',
            'receso.boolean' => 'El valor de receso no es válido.',
        ];
    }

    public function guardarMateria(): void
    {
        $this->materia = trim($this->materia);
        $this->slug = Str::slug($this->slug ?: $this->materia);
        $this->clave = filled($this->clave) ? mb_strtoupper(trim($this->clave)) : null;

        if (!$this->esBachilleratoFormulario) {
            $this->semestre_id = null;
            $this->clave = null;
        }

        if ($this->receso) {
            $this->calificable = false;
            $this->extra = true;
        }

        $this->validate();

        if ($this->existeMateriaDuplicada()) {
            $this->addError('materia', 'Ya existe una materia con el mismo slug en este nivel, grado y semestre.');
            return;
        }

        if ($this->existeClaveDuplicada()) {
            $this->addError('clave', 'Ya existe una materia con la misma clave en este nivel, grado y semestre.');
            return;
        }

        $datos = [
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->esBachilleratoFormulario ? $this->semestre_id : null,
            'materia' => $this->materia,
            'clave' => $this->esBachilleratoFormulario ? $this->clave : null,
            'slug' => $this->slug,
            'calificable' => $this->calificable,
            'extra' => $this->extra,
            'receso' => $this->receso,
        ];

        if ($this->editandoId) {
            Materia::query()
                ->where('id', $this->editandoId)
                ->update($datos);

            $mensaje = '¡Materia actualizada correctamente!';
        } else {
            Materia::query()->create($datos);

            $mensaje = '¡Materia creada correctamente!';
        }

        $this->limpiarFormulario();

        $this->dispatch('cerrar-formulario-materia');

        $this->dispatch('swal', [
            'title' => $mensaje,
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function existeMateriaDuplicada(): bool
    {
        return Materia::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('slug', $this->slug)
            ->when(
                $this->esBachilleratoFormulario,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->when($this->editandoId, fn($query) => $query->where('id', '!=', $this->editandoId))
            ->exists();
    }

    private function existeClaveDuplicada(): bool
    {
        if (!$this->esBachilleratoFormulario || blank($this->clave)) {
            return false;
        }

        return Materia::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('semestre_id', $this->semestre_id)
            ->where('clave', $this->clave)
            ->when($this->editandoId, fn($query) => $query->where('id', '!=', $this->editandoId))
            ->exists();
    }

    public function editar(int $id): void
    {
        $materia = Materia::query()->findOrFail($id);

        $this->resetErrorBag();

        $this->editandoId = $materia->id;

        // Primero se carga el nivel para que el select de grados tenga contexto.
        $this->nivel_id = (int) $materia->nivel_id;

        // Después se cargan los grados pertenecientes a ese nivel.
        $this->cargarGradosFormulario();

        // Ya con los grados cargados, ahora sí se asigna el grado.
        $this->grado_id = (int) $materia->grado_id;

        // Si es bachillerato, se cargan los semestres del grado.
        $this->cargarSemestresFormulario();

        // Ya con los semestres cargados, se asigna el semestre.
        $this->semestre_id = $materia->semestre_id ? (int) $materia->semestre_id : null;

        $this->materia = $materia->materia;
        $this->clave = $materia->clave;
        $this->slug = $materia->slug;
        $this->calificable = (bool) $materia->calificable;
        $this->extra = (bool) $materia->extra;
        $this->receso = (bool) $materia->receso;

        $this->dispatch('abrir-formulario-materia');
    }

    public function eliminar(int $id): void
    {
        $materia = Materia::query()
            ->withCount('asignaciones')
            ->findOrFail($id);

        if ($materia->asignaciones_count > 0) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'La materia ya está asignada a uno o más grupos.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $materia->delete();

        $this->dispatch('swal', [
            'title' => '¡Materia eliminada correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function ordenarMateriasPorContextoJs(string $contexto, array $ids): void
    {
        $ids = collect($ids)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->toArray();

        if (empty($ids)) {
            return;
        }

        foreach ($ids as $index => $id) {
            Materia::query()
                ->where('id', $id)
                ->update([
                    'orden' => $index + 1,
                ]);
        }

        $this->dispatch('swal', [
            'title' => '¡Orden actualizado!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function limpiarFormulario(): void
    {
        $this->editandoId = null;

        $this->nivel_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;

        $this->materia = '';
        $this->clave = null;
        $this->slug = '';

        $this->calificable = true;
        $this->extra = false;
        $this->receso = false;

        $this->gradosFormulario = collect();
        $this->semestresFormulario = collect();

        $this->resetErrorBag();
    }

    public function getTotalMateriasProperty(): int
    {
        return Materia::query()->count();
    }

    public function getTotalCalificablesProperty(): int
    {
        return Materia::query()->where('calificable', true)->count();
    }

    public function getTotalExtrasProperty(): int
    {
        return Materia::query()->where('extra', true)->count();
    }

    public function getTotalRecesosProperty(): int
    {
        return Materia::query()->where('receso', true)->count();
    }

    private function consultaMaterias()
    {
        return Materia::query()
            ->with(['nivel', 'grado', 'semestre'])
            ->withCount('asignaciones')
            ->when(trim($this->buscar) !== '', function ($query) {
                $buscar = '%' . trim($this->buscar) . '%';

                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery->where('materia', 'like', $buscar)
                        ->orWhere('clave', 'like', $buscar)
                        ->orWhere('slug', 'like', $buscar)
                        ->orWhereHas('nivel', fn($nivelQuery) => $nivelQuery->where('nombre', 'like', $buscar))
                        ->orWhereHas('grado', fn($gradoQuery) => $gradoQuery->where('nombre', 'like', $buscar))
                        ->orWhereHas('semestre', fn($semestreQuery) => $semestreQuery->where('numero', 'like', $buscar));
                });
            })
            ->when($this->filtro_nivel_id, fn($query) => $query->where('nivel_id', $this->filtro_nivel_id))
            ->when($this->filtro_grado_id, fn($query) => $query->where('grado_id', $this->filtro_grado_id))
            ->when(
                $this->esBachilleratoFiltro && $this->filtro_semestre_id,
                fn($query) => $query->where('semestre_id', $this->filtro_semestre_id)
            )
            ->when(
                $this->filtro_nivel_id && !$this->esBachilleratoFiltro,
                fn($query) => $query->whereNull('semestre_id')
            )
            ->when($this->filtro_tipo === 'calificables', fn($query) => $query->where('calificable', true))
            ->when($this->filtro_tipo === 'extras', fn($query) => $query->where('extra', true))
            ->when($this->filtro_tipo === 'recesos', fn($query) => $query->where('receso', true))
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderByRaw('semestre_id IS NULL')
            ->orderBy('semestre_id')
            ->orderBy('orden')
            ->orderBy('materia');
    }

    public function render()
    {
        $materias = $this->consultaMaterias()->get();

        $materiasAgrupadas = $materias->groupBy(function ($item) {
            return implode('|', [
                $item->nivel_id,
                $item->grado_id,
                $item->semestre_id ?: 0,
            ]);
        });

        return view('livewire.materia.crear-materia', [
            'materias' => $materias,
            'materiasAgrupadas' => $materiasAgrupadas,
        ]);
    }
}
