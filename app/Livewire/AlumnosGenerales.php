<?php

namespace App\Livewire;

use App\Models\Ciclo;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class AlumnosGenerales extends Component
{
    use WithPagination;

    public Collection $niveles;
    public Collection $ciclos;
    public Collection $grados;
    public Collection $generaciones;
    public Collection $semestres;
    public Collection $grupos;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public ?int $ciclo_id = null;

    public string $buscar = '';
    public string $genero = '';
    public string $estatus = 'activos';
    public string $orden = 'apellidos';
    public int $perPage = 25;

    public int $total = 0;
    public int $hombres = 0;
    public int $mujeres = 0;
    public int $activos = 0;
    public int $bajas = 0;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug', 'color')
            ->orderBy('id')
            ->get();

        $this->ciclos = Ciclo::query()
            ->select('id', 'ciclo')
            ->orderBy('id')
            ->get();

        $this->grados = collect();
        $this->generaciones = collect();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->cargarGeneraciones();
        $this->recalcularResumen();
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;

        $this->grado_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->cargarGrados();
        $this->cargarGeneraciones();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value ? (int) $value : null;

        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->cargarSemestres();
        $this->cargarGrupos();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;

        $this->grupo_id = null;

        $this->cargarGrupos();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;

        $this->grupo_id = null;

        $this->cargarGrupos();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedCicloId($value): void
    {
        $this->ciclo_id = $value ? (int) $value : null;

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGenero(): void
    {
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedEstatus(): void
    {
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedOrden(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_id',
            'ciclo_id',
            'buscar',
            'genero',
            'estatus',
            'orden',
        ]);

        $this->estatus = 'activos';
        $this->orden = 'apellidos';

        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->cargarGeneraciones();

        $this->resetPage();
        $this->recalcularResumen();
    }

    private function cargarGrados(): void
    {
        if (!$this->nivel_id) {
            $this->grados = collect();

            return;
        }

        $this->grados = Grado::query()
            ->select('id', 'nivel_id', 'nombre', 'orden')
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function cargarGeneraciones(): void
    {
        $consulta = Generacion::query()
            ->select('id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status')
            ->with('nivel:id,nombre')
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso');

        if ($this->nivel_id) {
            $consulta->where('nivel_id', $this->nivel_id);
        }

        $this->generaciones = $consulta->get();
    }

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato() || !$this->grado_id) {
            $this->semestres = collect();

            return;
        }

        $this->semestres = Semestre::query()
            ->select('id', 'grado_id', 'numero', 'orden_global')
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get();
    }

    private function cargarGrupos(): void
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) {
            $this->grupos = collect();

            return;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            $this->grupos = collect();

            return;
        }

        $consulta = Grupo::query()
            ->select('grupos.id', 'grupos.nivel_id', 'grupos.grado_id', 'grupos.generacion_id', 'grupos.semestre_id', 'grupos.asignacion_grupo_id')
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->where('grupos.nivel_id', $this->nivel_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->where('grupos.generacion_id', $this->generacion_id);

        if ($this->esBachillerato()) {
            $consulta->where('grupos.semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('grupos.semestre_id');
        }

        $this->grupos = $consulta
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get();
    }

    private function consultaBase(): Builder
    {
        $consulta = Inscripcion::query()
            ->select([
                'id',
                'curp',
                'matricula',
                'folio',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'fecha_nacimiento',
                'genero',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'grupo_id',
                'semestre_id',
                'ciclo_id',
                'foto_path',
                'activo',
                'fecha_baja',
                'fecha_inscripcion',
            ])
            ->with([
                'nivel:id,nombre,slug,color',
                'grado:id,nombre,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,grado_id',
                'ciclo:id,ciclo',
                'grupo' => function ($query) {
                    $query->select('id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id')
                        ->with('asignacionGrupo:id,nombre');
                },
            ]);

        if ($this->nivel_id) {
            $consulta->where('nivel_id', $this->nivel_id);
        }

        if ($this->grado_id) {
            $consulta->where('grado_id', $this->grado_id);
        }

        if ($this->generacion_id) {
            $consulta->where('generacion_id', $this->generacion_id);
        }

        if ($this->semestre_id) {
            $consulta->where('semestre_id', $this->semestre_id);
        }

        if ($this->grupo_id) {
            $consulta->where('grupo_id', $this->grupo_id);
        }

        if ($this->ciclo_id) {
            $consulta->where('ciclo_id', $this->ciclo_id);
        }

        if ($this->genero !== '') {
            $consulta->where('genero', $this->genero);
        }

        if ($this->estatus === 'activos') {
            $consulta->where('activo', true);
        }

        if ($this->estatus === 'bajas') {
            $consulta->where('activo', false);
        }

        if (trim($this->buscar) !== '') {
            $busqueda = trim($this->buscar);

            $consulta->where(function ($query) use ($busqueda) {
                $query->where('matricula', 'like', "%{$busqueda}%")
                    ->orWhere('curp', 'like', "%{$busqueda}%")
                    ->orWhere('folio', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_materno', 'like', "%{$busqueda}%");
            });
        }

        return $this->aplicarOrden($consulta);
    }

    private function aplicarOrden(Builder $consulta): Builder
    {
        return match ($this->orden) {
            'recientes' => $consulta->orderByDesc('created_at'),
            'matricula' => $consulta->orderBy('matricula'),
            'nivel' => $consulta
                ->join('niveles', 'niveles.id', '=', 'inscripciones.nivel_id')
                ->orderBy('niveles.nombre')
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre')
                ->select('inscripciones.*'),
            default => $consulta
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre'),
        };
    }

    private function recalcularResumen(): void
    {
        $base = $this->consultaBase();

        $this->total = (clone $base)->count();
        $this->hombres = (clone $base)->where('genero', 'H')->count();
        $this->mujeres = (clone $base)->where('genero', 'M')->count();
        $this->activos = (clone $base)->where('activo', true)->count();
        $this->bajas = (clone $base)->where('activo', false)->count();
    }

    public function esBachillerato(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        $nivel = $this->niveles->firstWhere('id', $this->nivel_id);

        return $nivel && str($nivel->slug)->contains('bachillerato');
    }

    public function nombreCompleto($alumno): string
    {
        return trim($alumno->apellido_paterno . ' ' . $alumno->apellido_materno . ' ' . $alumno->nombre);
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? '—';
    }

    public function textoGeneracion($generacion): string
    {
        if (!$generacion) {
            return '—';
        }

        return $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso;
    }

    public function render()
    {
        $alumnos = $this->consultaBase()
            ->paginate($this->perPage);

        return view('livewire.alumnos-generales', [
            'alumnos' => $alumnos,
        ]);
    }
}
