<?php

namespace App\Livewire\Accion;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Matricula extends Component
{
    use WithPagination;

    public string $slug_nivel;

    public ?Nivel $nivel = null;

    // ✅ ahora filtramos por generación y grupo
    public ?int $generacion_id = null;
    public ?int $grupo_id = null;

    public string $search = '';

    // =========================
    // Selección de filas
    // =========================
    public int $perPage = 12;

    /** @var array<int> */
    public array $selected = [];

    public bool $selectPage = false;

    // =========================
    // Cambiar grado (modal)
    // =========================
    public bool $openCambiarGrado = false;
    public ?int $nuevo_grado_id = null;

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();
    }

    // =========================
    // Helpers selección
    // =========================
    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function resetSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedSelected(): void
    {
        if ($this->selectPage && $this->selectedCount < $this->perPage) {
            $this->selectPage = false;
        }
    }

    public function updatedSelectPage($value): void
    {
        if (!$value) {
            $this->resetSelection();
            return;
        }

        $paginator = $this->baseQuery()
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        $this->selected = $paginator->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    // =========================
    // Reset al cambiar filtros
    // =========================
    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedGrupoId(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function clearFilters(): void
    {
        $this->reset(['generacion_id', 'grupo_id', 'search']);
        $this->resetPage();
        $this->resetSelection();
    }

   private function baseQuery(): Builder
{
    return Inscripcion::query()
        ->select([
            'id',
            'curp',
            'matricula',
            'folio',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'genero',
            'nivel_id',
            'grado_id',
            'grupo_id',
            'generacion_id', // ✅ importante
            'activo',
        ])
        ->with([
            'grado:id,nombre,nivel_id',
            'grupo:id,nombre,nivel_id,grado_id',
            // ✅ Generación según tu tabla:
            'generacion:id,nivel_id,anio_ingreso,anio_egreso',
        ])
        ->where('activo', 1)
        ->where('nivel_id', $this->nivel->id)
        ->when($this->generacion_id, fn ($q) => $q->where('generacion_id', $this->generacion_id))
        ->when($this->grupo_id, fn ($q) => $q->where('grupo_id', $this->grupo_id))
        ->when($this->search !== '', function ($q) {
            $s = trim($this->search);

            $q->where(function ($qq) use ($s) {
                $qq->where('matricula', 'like', "%{$s}%")
                    ->orWhere('curp', 'like', "%{$s}%")
                    ->orWhere('nombre', 'like', "%{$s}%")
                    ->orWhere('apellido_paterno', 'like', "%{$s}%")
                    ->orWhere('apellido_materno', 'like', "%{$s}%");
            });
        });
}

    // =========================
    // Cambiar grado (se mantiene)
    // =========================
    public function openCambiarGradoModal(): void
    {
        if ($this->selectedCount === 0) return;

        $this->nuevo_grado_id = null;
        $this->openCambiarGrado = true;
    }

    public function cerrarCambiarGradoModal(): void
    {
        $this->openCambiarGrado = false;
        $this->nuevo_grado_id = null;
    }

    public function aplicarCambiarGrado(): void
    {
        if ($this->selectedCount === 0) return;

        $this->validate([
            'nuevo_grado_id' => [
                'required',
                'integer',
                function ($attr, $value, $fail) {
                    $ok = Grado::query()
                        ->where('id', $value)
                        ->where('nivel_id', $this->nivel->id)
                        ->exists();

                    if (!$ok) $fail('Selecciona un grado válido para este nivel.');
                },
            ],
        ]);

        DB::transaction(function () {
            Inscripcion::query()
                ->where('nivel_id', $this->nivel->id)
                ->whereIn('id', $this->selected)
                ->update([
                    'grado_id' => $this->nuevo_grado_id,
                    'grupo_id' => null,
                ]);
        });

        $this->cerrarCambiarGradoModal();
        $this->resetSelection();

        $this->dispatch('toast', type: 'success', message: 'Grado cambiado correctamente.');
    }

   public function render()
{
    // ✅ Generaciones disponibles por nivel, activas
    $generaciones = Generacion::query()
        ->select('id', 'anio_ingreso', 'anio_egreso')
        ->where('nivel_id', $this->nivel->id)
        ->where('status', 1)
        ->orderByDesc('anio_ingreso')
        ->get();

    // ✅ Grupos disponibles según inscripciones con filtros (nivel + generación)
    $grupoIds = Inscripcion::query()
        ->where('activo', 1)
        ->where('nivel_id', $this->nivel->id)
        ->when($this->generacion_id, fn ($q) => $q->where('generacion_id', $this->generacion_id))
        ->whereNotNull('grupo_id')
        ->distinct()
        ->pluck('grupo_id');

    $grupos = Grupo::query()
        ->select('id', 'nombre')
        ->whereIn('id', $grupoIds)
        ->orderBy('nombre')
        ->get();

    $rows = $this->baseQuery()
        ->orderBy('id', 'desc')
        ->paginate($this->perPage);

    $statsQuery = (clone $this->baseQuery());
    $total   = (clone $statsQuery)->count();
    $hombres = (clone $statsQuery)->where('genero', 'H')->count();
    $mujeres = (clone $statsQuery)->where('genero', 'M')->count();

    // modal cambiar grado (se mantiene)
    $grados = Grado::query()
        ->select('id', 'nombre')
        ->where('nivel_id', $this->nivel->id)
        ->orderBy('nombre')
        ->get();

    return view('livewire.accion.matricula', [
        'generaciones' => $generaciones,
        'grupos'       => $grupos,
        'rows'         => $rows,
        'total'        => $total,
        'hombres'      => $hombres,
        'mujeres'      => $mujeres,
        'grados'       => $grados,
    ]);
}

}
