<?php

namespace App\Livewire\Grupo;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\QueryException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGrupos extends Component
{
    use WithPagination;

    public string $search = '';

    public $ciclo_escolar_id = '';
    public $nivel_id = '';
    public $generacion_id = '';
    public $grado_id = '';
    public $semestre_id = '';

    public $grados;
    public $generaciones;
    public $semestres;

    public bool $esBachillerato = false;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['as' => 'buscar', 'except' => ''],
        'ciclo_escolar_id' => ['as' => 'ciclo', 'except' => ''],
        'nivel_id' => ['as' => 'nivel', 'except' => ''],
        'generacion_id' => ['as' => 'generacion', 'except' => ''],
        'grado_id' => ['as' => 'grado', 'except' => ''],
        'semestre_id' => ['as' => 'semestre', 'except' => ''],
    ];

    public function mount()
    {
        $this->grados = collect();
        $this->ciclo_escolar_id = $this->ciclo_escolar_id ?: (CicloEscolar::query()->where('es_actual', true)->value('id') ?: '');
        $this->generaciones = collect();
        $this->semestres = collect();

        if (filled($this->nivel_id)) {
            $this->esBachillerato = Nivel::query()->whereKey($this->nivel_id)->value('slug') === 'bachillerato';
            $this->cargarFiltrosPorNivel();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->resetPage();
        $this->generacion_id = '';
        $this->cargarFiltrosPorNivel();
    }

    public function updatedNivelId()
    {
        $this->resetPage();

        $this->grado_id = '';
        $this->generacion_id = '';
        $this->semestre_id = '';

        $this->esBachillerato = Nivel::query()->whereKey($this->nivel_id)->value('slug') === 'bachillerato';

        $this->cargarFiltrosPorNivel();
    }

    public function updatedGeneracionId()
    {
        $this->resetPage();
    }

    public function updatedGradoId()
    {
        $this->resetPage();
    }

    public function updatedSemestreId()
    {
        $this->resetPage();
    }

    public function cargarFiltrosPorNivel()
    {
        if (!$this->nivel_id) {
            $this->grados = collect();
            $this->generaciones = collect();
            $this->semestres = collect();
            $this->esBachillerato = false;

            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get();

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->when($this->ciclo_escolar_id, fn ($query) => $query->whereHas(
                'grupos',
                fn ($grupo) => $grupo->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ))
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->semestres = $this->esBachillerato
            ? Semestre::query()
                ->orderBy('numero')
                ->get()
            : collect();
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'search',
            'ciclo_escolar_id',
            'nivel_id',
            'generacion_id',
            'grado_id',
            'semestre_id',
            'esBachillerato',
        ]);

        $this->grados = collect();
        $this->generaciones = collect();
        $this->semestres = collect();

        $this->ciclo_escolar_id = CicloEscolar::query()->where('es_actual', true)->value('id') ?: '';
        $this->resetPage();
    }

    public function eliminar($id)
    {
        $grupo = Grupo::query()->find($id);

        if (!$grupo) {
            $this->dispatch('swal', [
                'title' => 'El grupo no existe.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return;
        }

        if ($grupo->inscripciones()->withTrashed()->exists()) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El grupo tiene alumnos relacionados. Cámbialo a inactivo desde Editar grupo.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        try {
            $grupo->delete();

            $this->dispatch('swal', [
                'title' => '¡Grupo eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);

            $this->dispatch('refreshGrupos');
        } catch (QueryException $e) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar.',
                'text' => 'Este grupo ya tiene información relacionada en el sistema.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
        }
    }

    #[On('refreshGrupos')]
    #[On('grupoActualizado')]
    public function render()
    {
        $niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        if ($this->nivel_id && $this->grados->isEmpty() && $this->generaciones->isEmpty()) {
            $this->esBachillerato = Nivel::query()->whereKey($this->nivel_id)->value('slug') === 'bachillerato';
            $this->cargarFiltrosPorNivel();
        }

        $query = Grupo::query()
            ->with([
                'asignacionGrupo',
                'cicloEscolar',
                'nivel',
                'grado',
                'generacion',
                'semestre',
            ])
            ->leftJoin('asignacion_grupos', 'grupos.asignacion_grupo_id', '=', 'asignacion_grupos.id')
            ->select('grupos.*')
            ->when($this->ciclo_escolar_id, function ($query) {
                $query->where('grupos.ciclo_escolar_id', $this->ciclo_escolar_id);
            })
            ->when(trim($this->search) !== '', function ($query) {
                $buscar = trim($this->search);

                $query->whereHas('asignacionGrupo', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%');
                });
            })
            ->when($this->nivel_id, function ($query) {
                $query->where('grupos.nivel_id', $this->nivel_id);
            })
            ->when($this->generacion_id, function ($query) {
                $query->where('grupos.generacion_id', $this->generacion_id);
            })
            ->when($this->grado_id && !$this->esBachillerato, function ($query) {
                $query->where('grupos.grado_id', $this->grado_id);
            })
            ->when($this->semestre_id && $this->esBachillerato, function ($query) {
                $query->where('grupos.semestre_id', $this->semestre_id);
            })
            ->orderBy('grupos.nivel_id', 'asc')
            ->orderBy('grupos.grado_id', 'asc')
            ->orderBy('grupos.semestre_id', 'asc')
            ->orderBy('grupos.generacion_id', 'asc')
            ->orderBy('asignacion_grupos.nombre', 'asc');

        $totalNiveles = (clone $query)
            ->whereNotNull('grupos.nivel_id')
            ->distinct()
            ->count('grupos.nivel_id');

        $gruposSinNivel = (clone $query)
            ->whereNull('grupos.nivel_id')
            ->count();

        $grupos = $query->paginate(12);

        $groupedByNivel = $grupos->getCollection()
            ->groupBy(function ($grupo) {
                return $grupo->nivel?->nombre ?? 'Sin nivel asignado';
            });

        return view('livewire.grupo.mostrar-grupos', [
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
            'niveles' => $niveles,
            'grupos' => $grupos,
            'groupedByNivel' => $groupedByNivel,
            'totalGrupos' => $grupos->total(),
            'totalNiveles' => $totalNiveles,
            'gruposSinNivel' => $gruposSinNivel,
        ]);
    }
}
