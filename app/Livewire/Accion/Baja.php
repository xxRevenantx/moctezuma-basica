<?php

namespace App\Livewire\Accion;

use App\Models\Inscripcion;
use App\Models\Nivel;
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
        $this->esBachillerato = (int) $this->nivel_id === 4
            || $this->slug_nivel === 'bachillerato';

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

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    public function getTotalProperty(): int
    {
        return $this->baseQuery()->count();
    }

    public function getTotalBajasProperty(): int
    {
        return $this->bajasQuery()->count();
    }

    public function getHombresProperty(): int
    {
        return $this->baseQuery()
            ->where('genero', 'H')
            ->count();
    }

    public function getMujeresProperty(): int
    {
        return $this->baseQuery()
            ->where('genero', 'M')
            ->count();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->selected = [];
        $this->selectPage = false;

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function aplicarBaja(): void
    {
        $this->validate();

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
        return $this->baseQuery()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function bajasRows(): LengthAwarePaginator
    {
        return $this->bajasQuery()
            ->orderByDesc('fecha_baja')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(10, ['*'], 'bajasPage');
    }

    private function baseQuery(): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'generacion',
                'grado',
                'grupo.asignacionGrupo',
                'semestre',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->whereNull('motivo_baja')
            ->whereNull('observaciones_baja')
            ->when(Schema::hasColumn('inscripciones', 'status'), function (Builder $query) {
                $query->where(function (Builder $subquery) {
                    $subquery->whereNull('status')
                        ->orWhereNotIn('status', [
                            'Baja',
                            'BAJA',
                            'baja',
                            'Inactivo',
                            'INACTIVO',
                            'inactivo',
                        ]);
                });
            });

        return $this->applySearch($query);
    }

    private function bajasQuery(): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'generacion',
                'grado',
                'grupo.asignacionGrupo',
                'semestre',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where(function (Builder $query) {
                $query->where('activo', false)
                    ->orWhereNotNull('fecha_baja')
                    ->orWhereNotNull('motivo_baja')
                    ->orWhereNotNull('observaciones_baja');
            });

        return $this->applySearch($query);
    }

    private function applySearch(Builder $query): Builder
    {
        $termino = preg_replace('/\s+/', ' ', trim($this->search));

        if (blank($termino)) {
            return $query;
        }

        $buscar = "%{$termino}%";

        return $query->where(function (Builder $subquery) use ($buscar) {
            $subquery
                ->where('matricula', 'like', $buscar)
                ->orWhere('folio', 'like', $buscar)
                ->orWhere('curp', 'like', $buscar)
                ->orWhere('nombre', 'like', $buscar)
                ->orWhere('apellido_paterno', 'like', $buscar)
                ->orWhere('apellido_materno', 'like', $buscar)
                ->orWhereRaw(
                    "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                    [$buscar]
                )
                ->orWhereRaw(
                    "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                    [$buscar]
                )
                ->orWhereHas('generacion', function (Builder $query) use ($buscar) {
                    $query->where('anio_ingreso', 'like', $buscar)
                        ->orWhere('anio_egreso', 'like', $buscar)
                        ->orWhereRaw(
                            "CONCAT(anio_ingreso, ' - ', anio_egreso) LIKE ?",
                            [$buscar]
                        );
                })
                ->orWhereHas('grado', fn(Builder $query) => $query->where('nombre', 'like', $buscar))
                ->orWhereHas(
                    'grupo.asignacionGrupo',
                    fn(Builder $query) => $query->where('nombre', 'like', $buscar)
                )
                ->orWhereHas('semestre', fn(Builder $query) => $query->where('numero', 'like', $buscar));
        });
    }

    public function render()
    {
        return view('livewire.accion.baja', [
            'rows' => $this->rows(),
            'bajasRows' => $this->bajasRows(),
            'total' => $this->total,
            'totalBajas' => $this->totalBajas,
            'hombres' => $this->hombres,
            'mujeres' => $this->mujeres,
        ]);
    }
}
