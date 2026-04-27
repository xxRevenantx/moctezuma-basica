<?php

namespace App\Livewire\Grupo;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGrupos extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $nivel_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingNivelId(): void
    {
        $this->resetPage();

        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
    }

    public function updatingGeneracionId(): void
    {
        $this->resetPage();
    }

    public function updatingGradoId(): void
    {
        $this->resetPage();
    }

    public function updatingSemestreId(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'search',
            'nivel_id',
            'generacion_id',
            'grado_id',
            'semestre_id',
        ]);

        $this->resetPage();
    }

    public function getEsBachilleratoProperty(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = Nivel::query()->find($this->nivel_id);

        if (!$nivel) {
            return false;
        }

        return str($nivel->slug ?? '')->contains('bachillerato')
            || str($nivel->nombre ?? '')->lower()->contains('bachillerato');
    }

    private function aplicarBusqueda($query): void
    {
        $busqueda = trim($this->search);

        if ($busqueda === '') {
            return;
        }

        $query->where(function ($q) use ($busqueda) {
            // Busco por nombre del grupo
            $q->where('nombre', 'like', '%' . $busqueda . '%')

                // Busco por nombre o slug del nivel
                ->orWhereHas('nivel', function ($nivelQuery) use ($busqueda) {
                    $nivelQuery->where('nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('slug', 'like', '%' . $busqueda . '%');
                })

                // Busco por generación: año ingreso, año egreso o texto completo
                ->orWhereHas('generacion', function ($generacionQuery) use ($busqueda) {
                    $generacionQuery->where('anio_ingreso', 'like', '%' . $busqueda . '%')
                        ->orWhere('anio_egreso', 'like', '%' . $busqueda . '%')
                        ->orWhereRaw("CONCAT(anio_ingreso, ' - ', anio_egreso) LIKE ?", [
                            '%' . $busqueda . '%',
                        ])
                        ->orWhereRaw("CONCAT(anio_ingreso, '-', anio_egreso) LIKE ?", [
                            '%' . $busqueda . '%',
                        ]);
                });
        });
    }

    private function aplicarFiltros($query): void
    {
        $this->aplicarBusqueda($query);

        $query
            ->when($this->nivel_id, function ($query) {
                $query->where('nivel_id', $this->nivel_id);
            })
            ->when($this->generacion_id, function ($query) {
                $query->where('generacion_id', $this->generacion_id);
            })
            ->when($this->grado_id && !$this->esBachillerato, function ($query) {
                $query->where('grado_id', $this->grado_id);
            })
            ->when($this->semestre_id && $this->esBachillerato, function ($query) {
                $query->where('semestre_id', $this->semestre_id);
            });
    }

    public function eliminar($id): void
    {
        $grupo = Grupo::query()->find($id);

        if ($grupo) {
            $grupo->delete();

            $this->dispatch('refreshGrupos');
        }
    }

    #[On('refreshGrupos')]
    public function render()
    {
        $niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $generaciones = Generacion::query()
            ->when($this->nivel_id, function ($query) {
                $query->where('nivel_id', $this->nivel_id);
            })
            ->orderBy('anio_ingreso', 'desc')
            ->orderBy('anio_egreso', 'desc')
            ->get();

        $grados = Grado::query()
            ->when($this->nivel_id, function ($query) {
                $query->where('nivel_id', $this->nivel_id);
            })
            ->orderBy('nombre')
            ->get();

        $semestres = Semestre::query()
            ->orderBy('numero')
            ->get();

        $gruposQuery = Grupo::query()
            ->with(['nivel', 'grado', 'generacion', 'semestre']);

        $this->aplicarFiltros($gruposQuery);

        $grupos = $gruposQuery
            ->orderBy('nivel_id', 'asc')
            ->orderBy('grado_id', 'asc')
            ->orderBy('semestre_id', 'asc')
            ->orderBy('generacion_id', 'asc')
            ->orderBy('nombre', 'asc')
            ->paginate(12);

        $collection = $grupos->getCollection();

        $groupedByNivel = $collection->groupBy(function ($g) {
            return optional($g->nivel)->nombre ?? 'Sin nivel asignado';
        });

        $totalGrupos = $grupos->total();

        $totalNivelesQuery = Grupo::query();

        $this->aplicarFiltros($totalNivelesQuery);

        $totalNiveles = $totalNivelesQuery
            ->whereNotNull('nivel_id')
            ->distinct('nivel_id')
            ->count('nivel_id');

        $gruposSinNivelQuery = Grupo::query();

        $this->aplicarFiltros($gruposSinNivelQuery);

        $gruposSinNivel = $gruposSinNivelQuery
            ->whereNull('nivel_id')
            ->count();

        return view('livewire.grupo.mostrar-grupos', [
            'grupos' => $grupos,
            'groupedByNivel' => $groupedByNivel,
            'totalGrupos' => $totalGrupos,
            'totalNiveles' => $totalNiveles,
            'gruposSinNivel' => $gruposSinNivel,
            'niveles' => $niveles,
            'generaciones' => $generaciones,
            'grados' => $grados,
            'semestres' => $semestres,
            'esBachillerato' => $this->esBachillerato,
        ]);
    }
}
