<?php

namespace App\Livewire\Accion;

use App\Models\BitacoraCalificacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Periodos;
use App\Models\Semestre;
use Livewire\Component;
use Livewire\WithPagination;

class BitacoraCalificaciones extends Component
{
    use WithPagination;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;
    public ?int $generacion_id = null;
    public ?int $periodo_id = null;

    public bool $esBachillerato = false;

    public $grados = [];
    public $grupos = [];
    public $semestres = [];
    public $periodos = [];

    public string $buscar_alumno = '';
    public string $buscar_materia = '';
    public string $buscar_usuario = '';
    public string $buscar_general = '';
    public string $accion = '';
    public string $tipo_valor = '';

    public int $porPagina = 10;

    protected $paginationTheme = 'tailwind';

    public function mount(
        ?int $nivel_id = null,
        ?int $grado_id = null,
        ?int $grupo_id = null,
        ?int $semestre_id = null,
        ?int $generacion_id = null,
        ?int $periodo_id = null,
        bool $esBachillerato = false
    ): void {
        $this->nivel_id = $nivel_id;
        $this->grado_id = $grado_id;
        $this->grupo_id = $grupo_id;
        $this->semestre_id = $semestre_id;
        $this->generacion_id = $generacion_id;
        $this->periodo_id = $periodo_id;
        $this->esBachillerato = $esBachillerato;

        $this->cargarCatalogos();
    }

    public function updatedBuscarAlumno(): void
    {
        $this->resetPage();
    }

    public function updatedBuscarMateria(): void
    {
        $this->resetPage();
    }

    public function updatedBuscarUsuario(): void
    {
        $this->resetPage();
    }

    public function updatedBuscarGeneral(): void
    {
        $this->resetPage();
    }

    public function updatedAccion(): void
    {
        $this->resetPage();
    }

    public function updatedTipoValor(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->buscar_alumno = '';
        $this->buscar_materia = '';
        $this->buscar_usuario = '';
        $this->buscar_general = '';
        $this->accion = '';
        $this->tipo_valor = '';

        $this->resetPage();
    }

    private function cargarCatalogos(): void
    {
        $this->grados = [];
        $this->grupos = [];
        $this->semestres = [];
        $this->periodos = [];

        if (!$this->nivel_id) {
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $this->grupos = Grupo::query()
            ->when($this->nivel_id, fn($q) => $q->where('nivel_id', $this->nivel_id))
            ->when($this->grado_id, fn($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->generacion_id, fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->when(
                $this->esBachillerato && $this->semestre_id,
                fn($q) => $q->where('semestre_id', $this->semestre_id)
            )
            ->when(
                !$this->esBachillerato,
                fn($q) => $q->whereNull('semestre_id')
            )
            ->orderBy('nombre')
            ->get();

        if ($this->esBachillerato && $this->grado_id) {
            $this->semestres = Semestre::query()
                ->where('grado_id', $this->grado_id)
                ->orderBy('numero')
                ->get();
        }

        $query = Periodos::query()
            ->with('cicloEscolar')
            ->where('nivel_id', $this->nivel_id);

        if ($this->esBachillerato) {
            if ($this->generacion_id) {
                $query->where('generacion_id', $this->generacion_id);
            }

            if ($this->semestre_id) {
                $query->where('semestre_id', $this->semestre_id);
            }
        }

        $this->periodos = $query
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(function ($item) {
                $inicio = $item->fecha_inicio ? date('d/m/Y', strtotime($item->fecha_inicio)) : 'Sin inicio';
                $fin = $item->fecha_fin ? date('d/m/Y', strtotime($item->fecha_fin)) : 'Sin fin';

                return [
                    'id' => (int) $item->id,
                    'etiqueta' => $inicio . ' - ' . $fin,
                    'ciclo_escolar' => $item->cicloEscolar
                        ? $item->cicloEscolar->inicio_anio . '-' . $item->cicloEscolar->fin_anio
                        : 'Sin ciclo escolar',
                ];
            })
            ->values()
            ->toArray();
    }

    private function consultaBase()
    {
        return BitacoraCalificacion::query()
            ->with([
                'usuario:id,name,email',
                'inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id',
                'asignacionMateria.materia:id,materia,clave,slug',
                'grado:id,nombre',
                'grupo:id,nombre',
                'semestre:id,numero',
            ])
            ->when($this->nivel_id, fn($q) => $q->where('nivel_id', $this->nivel_id))
            ->when($this->grado_id, fn($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id, fn($q) => $q->where('grupo_id', $this->grupo_id))
            ->when($this->periodo_id, fn($q) => $q->where('periodo_id', $this->periodo_id))
            ->when($this->generacion_id, fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->esBachillerato && $this->semestre_id, fn($q) => $q->where('semestre_id', $this->semestre_id))
            ->when(!$this->esBachillerato, fn($q) => $q->whereNull('semestre_id'))
            ->when($this->accion !== '', fn($q) => $q->where('accion', $this->accion))
            ->when($this->tipo_valor !== '', fn($q) => $q->where('tipo_valor', $this->tipo_valor))
            ->when(trim($this->buscar_alumno) !== '', function ($q) {
                $buscar = trim($this->buscar_alumno);

                $q->whereHas('inscripcion', function ($sub) use ($buscar) {
                    $sub->where('matricula', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->when(trim($this->buscar_materia) !== '', function ($q) {
                $buscar = trim($this->buscar_materia);

                $q->whereHas('asignacionMateria.materia', function ($sub) use ($buscar) {
                    $sub->where('materia', 'like', "%{$buscar}%")
                        ->orWhere('clave', 'like', "%{$buscar}%")
                        ->orWhere('slug', 'like', "%{$buscar}%");
                });
            })
            ->when(trim($this->buscar_usuario) !== '', function ($q) {
                $buscar = trim($this->buscar_usuario);

                $q->whereHas('usuario', function ($sub) use ($buscar) {
                    $sub->where('name', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when(trim($this->buscar_general) !== '', function ($q) {
                $buscar = trim($this->buscar_general);

                $q->where(function ($sub) use ($buscar) {
                    $sub->where('calificacion_anterior', 'like', "%{$buscar}%")
                        ->orWhere('calificacion_nueva', 'like', "%{$buscar}%")
                        ->orWhere('tipo_valor', 'like', "%{$buscar}%")
                        ->orWhere('observacion', 'like', "%{$buscar}%")
                        ->orWhere('motivo', 'like', "%{$buscar}%")
                        ->orWhere('ip', 'like', "%{$buscar}%")
                        ->orWhereHas('asignacionMateria.materia', function ($materiaQuery) use ($buscar) {
                            $materiaQuery->where('materia', 'like', "%{$buscar}%")
                                ->orWhere('clave', 'like', "%{$buscar}%")
                                ->orWhere('slug', 'like', "%{$buscar}%");
                        });
                });
            });
    }

    public function getTotalMovimientosProperty(): int
    {
        return (clone $this->consultaBase())->count();
    }

    public function getTotalCreacionesProperty(): int
    {
        return (clone $this->consultaBase())->where('accion', 'crear')->count();
    }

    public function getTotalEdicionesProperty(): int
    {
        return (clone $this->consultaBase())->where('accion', 'editar')->count();
    }

    public function getTotalEliminacionesProperty(): int
    {
        return (clone $this->consultaBase())->where('accion', 'eliminar')->count();
    }

    public function getTotalEspecialesProperty(): int
    {
        return (clone $this->consultaBase())->where('tipo_valor', 'especial')->count();
    }

    public function getTotalNumericasProperty(): int
    {
        return (clone $this->consultaBase())->where('tipo_valor', 'numerico')->count();
    }

    public function getTotalReprobatoriasProperty(): int
    {
        return (clone $this->consultaBase())
            ->whereNotNull('valor_nuevo_numerico')
            ->where('valor_nuevo_numerico', '<', 6)
            ->count();
    }

    public function claseAccion(?string $accion): string
    {
        return match ($accion) {
            'crear' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
            'editar' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
            'eliminar' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
            default => 'bg-slate-50 text-slate-700 dark:bg-neutral-800 dark:text-slate-300',
        };
    }

    public function render()
    {
        $rows = $this->consultaBase()
            ->latest()
            ->paginate($this->porPagina);

        return view('livewire.accion.bitacora-calificaciones', [
            'rows' => $rows,
        ]);
    }
}
