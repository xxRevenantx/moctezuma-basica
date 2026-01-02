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

    // ✅ Filtros: Generación + Grupo
    public ?int $generacion_id = null;
    public ?int $grupo_id = null;

    public string $search = '';

    // ✅ Mostrar grado(s) asociado(s) a la generación seleccionada
    public ?string $gradoGeneracionLabel = null;

    // =========================
    // Selección de filas
    // =========================
    public int $perPage = 12;

    /** @var array<int> */
    public array $selected = [];

    public bool $selectPage = false;

    // =========================
    // Cambiar grado (dropdown bajo tabla)
    // =========================
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

        // ✅ resetea el dropdown
        $this->nuevo_grado_id = null;
    }

    public function updatedSelected(): void
    {
        // Si se desmarca y ya no coincide el "select page", lo bajamos
        if ($this->selectPage && $this->selectedCount < $this->perPage) {
            $this->selectPage = false;
        }

        // ✅ si ya no hay seleccionados, limpia dropdown
        if ($this->selectedCount === 0) {
            $this->nuevo_grado_id = null;
        }
    }

    public function updatedSelectPage($value): void
    {
        if (!$value) {
            $this->resetSelection();
            return;
        }

        if (!$this->generacion_id) {
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
        $this->search = '';
        $this->gradoGeneracionLabel = null;

        $this->resetPage();
        $this->resetSelection();

        if (!$this->generacion_id || !$this->nivel) return;

        // ✅ Grado(s) de la generación: desde GRUPOS (generacion_id + grado_id)
        $grados = Grado::query()
            ->select('grados.nombre')
            ->join('grupos', 'grupos.grado_id', '=', 'grados.id')
            ->whereNull('grupos.deleted_at')
            ->where('grupos.nivel_id', $this->nivel->id)
            ->where('grupos.generacion_id', $this->generacion_id)
            ->distinct()
            ->orderBy('grados.nombre')
            ->pluck('grados.nombre');

        if ($grados->count() === 1) {
            $this->gradoGeneracionLabel = $grados->first();
        } elseif ($grados->count() > 1) {
            $this->gradoGeneracionLabel = $grados->implode(', ');
        } else {
            $this->gradoGeneracionLabel = null;
        }
    }

    public function updatedGrupoId(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function clearFilters(): void
    {
        $this->reset(['generacion_id', 'grupo_id', 'search']);
        $this->gradoGeneracionLabel = null;

        $this->resetPage();
        $this->resetSelection();
    }

    private function baseQuery(): Builder
    {
        // ✅ REGLA: si no hay generación seleccionada, NO mostramos nada
        if (!$this->generacion_id || !$this->nivel) {
            return Inscripcion::query()->whereRaw('1=0');
        }

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
                'generacion_id',
                'grupo_id',
                'activo',
            ])
            ->with([
                'grado:id,nombre,nivel_id',
                'grupo:id,nombre,nivel_id,grado_id,generacion_id',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso',
            ])
            ->where('activo', 1)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
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
    // Cambiar grado (aplicar desde dropdown bajo tabla)
    // =========================
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
                    // 'grupo_id' => null, // recomendado
                ]);
        });

        $this->resetSelection();

        $this->dispatch('toast', type: 'success', message: 'Grado cambiado correctamente.');
    }

    public function render()
    {
        // ✅ Generaciones del nivel
        $generaciones = Generacion::query()
            ->select('id', 'anio_ingreso', 'anio_egreso', 'status')
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->get()
            ->map(function ($gen) {
                // grados únicos de esa generación (vía grupos)
                $grados = Grado::query()
                    ->select('grados.nombre')
                    ->join('grupos', 'grupos.grado_id', '=', 'grados.id')
                    ->whereNull('grupos.deleted_at')
                    ->where('grupos.nivel_id', $this->nivel->id)
                    ->where('grupos.generacion_id', $gen->id)
                    ->distinct()
                    ->orderBy('grados.nombre')
                    ->pluck('grados.nombre');

                $gradoLabel = $grados->isEmpty() ? 'Sin grado' : $grados->implode(', ');

                // ✅ label final para el option
                $gen->label = "{$gen->anio_ingreso} - {$gen->anio_egreso} · {$gradoLabel}";

                return $gen;
            });

        // ✅ Grupos dependen de generación
        $grupos = collect();
        if ($this->generacion_id) {
            $grupos = Grupo::query()
                ->select('id', 'nombre')
                ->where('nivel_id', $this->nivel->id)
                ->where('generacion_id', $this->generacion_id)
                ->orderBy('nombre')
                ->get();
        }

        // ✅ Grados del nivel (para el dropdown bajo tabla)
        $grados = Grado::query()
            ->select('id', 'nombre')
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('nombre')
            ->get();

        $rows = $this->baseQuery()
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        // ✅ Stats
        if (!$this->generacion_id) {
            $total = 0;
            $hombres = 0;
            $mujeres = 0;
        } else {
            $statsQuery = (clone $this->baseQuery());
            $total = (clone $statsQuery)->count();
            $hombres = (clone $statsQuery)->where('genero', 'H')->count();
            $mujeres = (clone $statsQuery)->where('genero', 'M')->count();
        }

        return view('livewire.accion.matricula', [
            'nivel' => $this->nivel,
            'generaciones' => $generaciones,
            'grupos' => $grupos,
            'grados' => $grados,
            'rows' => $rows,
            'total' => $total,
            'hombres' => $hombres,
            'mujeres' => $mujeres,
        ]);
    }
}
