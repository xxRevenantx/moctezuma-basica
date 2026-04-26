<?php

namespace App\Livewire\Accion;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Baja extends Component
{
    use WithPagination;

    public string $slug_nivel;

    public Collection $niveles;
    public ?Nivel $nivel = null;

    public ?int $nivel_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $search = '';

    public array $selected = [];
    public bool $selectPage = false;

    public ?string $motivo_baja = null;
    public ?string $fecha_baja = null;
    public ?string $observaciones_baja = null;

    public bool $esBachillerato = false;

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->nivel_id = $this->nivel->id;

        $this->esBachillerato = (int) $this->nivel_id === 4 || $this->slug_nivel === 'bachillerato';

        $this->fecha_baja = now()->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:inscripciones,id'],
            'motivo_baja' => ['required', 'string', 'max:255'],
            'fecha_baja' => ['required', 'date'],
            'observaciones_baja' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'selected.required' => 'Selecciona al menos un alumno para aplicar la baja.',
            'selected.min' => 'Selecciona al menos un alumno para aplicar la baja.',
            'motivo_baja.required' => 'Selecciona el motivo de la baja.',
            'motivo_baja.max' => 'El motivo de la baja es demasiado largo.',
            'fecha_baja.required' => 'Selecciona la fecha de baja.',
            'fecha_baja.date' => 'La fecha de baja no es válida.',
            'observaciones_baja.max' => 'Las observaciones no deben superar los 1000 caracteres.',
        ];
    }

    public function updatedGeneracionId(): void
    {
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedGradoId(): void
    {
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedSemestreId(): void
    {
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->grupo_id = null;
        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedGrupoId(): void
    {
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedSelectPage(bool $value): void
    {
        if (!$value) {
            $this->selected = [];
            return;
        }

        $this->selected = $this->rows()
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selected);
    }

    #[Computed]
    public function filtrosListos(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->semestre_id)
                && filled($this->grupo_id);
        }

        return filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id);
    }

    public function getGeneracionesProperty(): Collection
    {
        return Generacion::query()
            ->whereIn('id', function ($query) {
                $query->select('generacion_id')
                    ->from('grupos')
                    ->where('nivel_id', $this->nivel_id)
                    ->whereNotNull('generacion_id');
            })
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    public function getGradosProperty(): Collection
    {
        return Grado::query()
            ->whereIn('id', function ($query) {
                $query->select('grado_id')
                    ->from('grupos')
                    ->where('nivel_id', $this->nivel_id)
                    ->when($this->generacion_id, fn($q) => $q->where('generacion_id', $this->generacion_id))
                    ->whereNotNull('grado_id');
            })
            ->orderBy('id')
            ->get();
    }

    public function getSemestresProperty(): Collection
    {
        if (!$this->esBachillerato || !$this->generacion_id || !$this->grado_id) {
            return collect();
        }

        return Semestre::query()
            ->whereIn('id', function ($query) {
                $query->select('semestre_id')
                    ->from('grupos')
                    ->where('nivel_id', $this->nivel_id)
                    ->where('generacion_id', $this->generacion_id)
                    ->where('grado_id', $this->grado_id)
                    ->whereNotNull('semestre_id');
            })
            ->orderBy('numero')
            ->get();
    }

    public function getGruposProperty(): Collection
    {
        if (!$this->generacion_id || !$this->grado_id) {
            return collect();
        }

        if ($this->esBachillerato && !$this->semestre_id) {
            return collect();
        }

        return Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id)
            )
            ->orderBy('nombre')
            ->get();
    }

    public function getGeneracionGrupoLabelProperty(): ?string
    {
        if (!$this->generacion_id) {
            return null;
        }

        $generacion = $this->generaciones->firstWhere('id', $this->generacion_id);

        if (!$generacion) {
            return null;
        }

        return $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso;
    }

    public function getTotalProperty(): int
    {
        if (!$this->filtrosListos) {
            return 0;
        }

        return $this->baseQuery()->count();
    }

    public function getTotalBajasProperty(): int
    {
        if (!$this->filtrosListos) {
            return 0;
        }

        return $this->bajasQuery()->count();
    }

    public function getHombresProperty(): int
    {
        if (!$this->filtrosListos) {
            return 0;
        }

        return $this->baseQuery()
            ->where('genero', 'H')
            ->count();
    }

    public function getMujeresProperty(): int
    {
        if (!$this->filtrosListos) {
            return 0;
        }

        return $this->baseQuery()
            ->where('genero', 'M')
            ->count();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'search',
            'selected',
            'selectPage',
            'motivo_baja',
            'observaciones_baja',
        ]);

        $this->fecha_baja = now()->format('Y-m-d');

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function restaurarFiltrosBajas(array $filtros): void
    {
        if (($filtros['slug_nivel'] ?? null) !== $this->slug_nivel) {
            return;
        }

        $this->generacion_id = filled($filtros['generacion_id'] ?? null) ? (int) $filtros['generacion_id'] : null;
        $this->grado_id = filled($filtros['grado_id'] ?? null) ? (int) $filtros['grado_id'] : null;
        $this->semestre_id = filled($filtros['semestre_id'] ?? null) ? (int) $filtros['semestre_id'] : null;
        $this->grupo_id = filled($filtros['grupo_id'] ?? null) ? (int) $filtros['grupo_id'] : null;
        $this->search = (string) ($filtros['search'] ?? '');

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function aplicarBaja(): void
    {
        $this->validate();

        if (!$this->filtrosListos) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Completa los filtros antes de aplicar la baja.',
                'position' => 'top-end',
            ]);

            return;
        }

        $ids = collect($this->selected)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->addError('selected', 'Selecciona al menos un alumno.');
            return;
        }

        DB::transaction(function () use ($ids) {
            $datos = [
                'activo' => false,
                'fecha_baja' => $this->fecha_baja,
                'motivo_baja' => $this->motivo_baja,
                'observaciones_baja' => $this->observaciones_baja,
            ];

            if (Schema::hasColumn('inscripciones', 'status')) {
                $datos['status'] = 'Baja';
            }

            if (Schema::hasColumn('inscripciones', 'updated_at')) {
                $datos['updated_at'] = now();
            }

            $this->baseQuery()
                ->whereIn('id', $ids)
                ->update($datos);
        });

        $totalBajas = $ids->count();

        $this->selected = [];
        $this->selectPage = false;
        $this->motivo_baja = null;
        $this->observaciones_baja = null;
        $this->fecha_baja = now()->format('Y-m-d');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Baja aplicada correctamente',
            'text' => $totalBajas === 1
                ? 'Se dio de baja 1 alumno.'
                : "Se dieron de baja {$totalBajas} alumnos.",
            'position' => 'top-end',
        ]);

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function reactivarAlumno(int $inscripcionId): void
    {
        if (!$this->filtrosListos) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Completa los filtros antes de reactivar.',
                'position' => 'top-end',
            ]);

            return;
        }

        DB::transaction(function () use ($inscripcionId) {
            $datos = [
                'activo' => true,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
            ];

            if (Schema::hasColumn('inscripciones', 'status')) {
                $datos['status'] = 'Activo';
            }

            if (Schema::hasColumn('inscripciones', 'updated_at')) {
                $datos['updated_at'] = now();
            }

            $this->bajasQuery()
                ->where('id', $inscripcionId)
                ->update($datos);
        });

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Alumno reactivado',
            'text' => 'El alumno volvió a quedar como activo.',
            'position' => 'top-end',
        ]);

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function rows(): LengthAwarePaginator
    {
        if (!$this->filtrosListos) {
            return Inscripcion::query()
                ->whereRaw('1 = 0')
                ->paginate(10);
        }

        return $this->baseQuery()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function bajasRows(): LengthAwarePaginator
    {
        if (!$this->filtrosListos) {
            return Inscripcion::query()
                ->whereRaw('1 = 0')
                ->paginate(10, ['*'], 'bajasPage');
        }

        return $this->bajasQuery()
            ->orderByDesc('fecha_baja')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(10, ['*'], 'bajasPage');
    }

    private function baseQuery(): Builder
    {
        return Inscripcion::query()
            ->with([
                'generacion',
                'grado',
                'grupo',
                'semestre',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id)
            )
            ->where('grupo_id', $this->grupo_id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->whereNull('motivo_baja')
            ->whereNull('observaciones_baja')
            ->when(Schema::hasColumn('inscripciones', 'status'), function ($query) {
                $query->where(function ($subquery) {
                    $subquery->whereNull('status')
                        ->orWhereNotIn('status', ['Baja', 'BAJA', 'baja', 'Inactivo', 'INACTIVO', 'inactivo']);
                });
            })
            ->when(filled($this->search), function ($query) {
                $buscar = '%' . trim($this->search) . '%';

                $query->where(function ($subquery) use ($buscar) {
                    $subquery
                        ->where('matricula', 'like', $buscar)
                        ->orWhere('folio', 'like', $buscar)
                        ->orWhere('curp', 'like', $buscar)
                        ->orWhere('nombre', 'like', $buscar)
                        ->orWhere('apellido_paterno', 'like', $buscar)
                        ->orWhere('apellido_materno', 'like', $buscar)
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$buscar])
                        ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$buscar]);
                });
            });
    }

    private function bajasQuery(): Builder
    {
        return Inscripcion::query()
            ->with([
                'generacion',
                'grado',
                'grupo',
                'semestre',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id)
            )
            ->where('grupo_id', $this->grupo_id)
            ->where(function ($query) {
                $query->where('activo', false)
                    ->orWhereNotNull('fecha_baja')
                    ->orWhereNotNull('motivo_baja')
                    ->orWhereNotNull('observaciones_baja');
            })
            ->when(filled($this->search), function ($query) {
                $buscar = '%' . trim($this->search) . '%';

                $query->where(function ($subquery) use ($buscar) {
                    $subquery
                        ->where('matricula', 'like', $buscar)
                        ->orWhere('folio', 'like', $buscar)
                        ->orWhere('curp', 'like', $buscar)
                        ->orWhere('nombre', 'like', $buscar)
                        ->orWhere('apellido_paterno', 'like', $buscar)
                        ->orWhere('apellido_materno', 'like', $buscar)
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$buscar])
                        ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$buscar]);
                });
            });
    }

    public function render()
    {
        return view('livewire.accion.baja', [
            'rows' => $this->rows(),
            'bajasRows' => $this->bajasRows(),
            'generaciones' => $this->generaciones,
            'grados' => $this->grados,
            'semestres' => $this->semestres,
            'grupos' => $this->grupos,
            'generacionGrupoLabel' => $this->generacionGrupoLabel,
            'total' => $this->total,
            'totalBajas' => $this->totalBajas,
            'hombres' => $this->hombres,
            'mujeres' => $this->mujeres,
        ]);
    }
}
