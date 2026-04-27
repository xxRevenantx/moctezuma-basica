<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Listas extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderBy('anio_ingreso', 'desc')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->semestres = $this->esBachillerato()
            ? Semestre::query()
                ->orderBy('id')
                ->get(['id', 'semestre'])
            : collect();

        $this->grupos = collect();
    }

    public function updated($property): void
    {
        if ($property === 'generacion_id') {
            $this->grupo_id = null;
            $this->cargarGrupos();
        }

        if ($property === 'grado_id') {
            $this->grupo_id = null;
            $this->cargarGrupos();
        }

        if ($property === 'semestre_id') {
            $this->grupo_id = null;
            $this->cargarGrupos();
        }
    }

    public function cargarGrupos(): void
    {
        $query = Grupo::query()
            ->where('nivel_id', $this->nivel->id);

        if ($this->generacion_id && Schema::hasColumn('grupos', 'generacion_id')) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if ($this->grado_id && Schema::hasColumn('grupos', 'grado_id')) {
            $query->where('grado_id', $this->grado_id);
        }

        if ($this->esBachillerato() && $this->semestre_id && Schema::hasColumn('grupos', 'semestre_id')) {
            $query->where('semestre_id', $this->semestre_id);
        }

        $this->grupos = $query
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre']);
    }

    public function limpiarFiltros(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->semestre_id = null;

        $this->grupos = collect();
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (!$this->generacion_id) {
            return false;
        }

        if (!$this->grupo_id) {
            return false;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return false;
        }

        return true;
    }

    #[Computed]
    public function urlPdf(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('accion.generales.listas.pdf', [
            'slug_nivel' => $this->slug_nivel,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'semestre_id' => $this->semestre_id,
        ]);
    }

    public function esBachillerato(): bool
    {
        return (int) $this->nivel->id === 4 || $this->nivel->slug === 'bachillerato';
    }

    public function render()
    {
        return view('livewire.accion.generales.listas');
    }
}
