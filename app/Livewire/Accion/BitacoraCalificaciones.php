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
            ->when($this->esBachillerato && $this->semestre_id, fn($q) => $q->where('semestre_id', $this->semestre_id))
            ->orderBy('nombre')
            ->get();

        if ($this->esBachillerato && $this->grado_id) {
            $idsSemestres = Grupo::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('grado_id', $this->grado_id)
                ->whereNotNull('semestre_id')
                ->distinct()
                ->pluck('semestre_id')
                ->filter()
                ->values();

            if ($idsSemestres->isNotEmpty()) {
                $this->semestres = Semestre::query()
                    ->whereIn('id', $idsSemestres)
                    ->orderBy('numero')
                    ->get();
            }
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
                'asignacionMateria:id,materia',
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

                $q->whereHas('asignacionMateria', function ($sub) use ($buscar) {
                    $sub->where('materia', 'like', "%{$buscar}%");
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
                        ->orWhere('ip', 'like', "%{$buscar}%");
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
            'crear' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/40',
            'editar' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40',
            'eliminar' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/40',
            default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-100 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
        };
    }

    public function claseTipoValor(?string $tipo): string
    {
        return match ($tipo) {
            'numerico' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/40',
            'especial' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-100 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/40',
            'vacio' => 'bg-slate-50 text-slate-700 ring-1 ring-slate-100 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
            'invalido' => 'bg-red-50 text-red-700 ring-1 ring-red-100 dark:bg-red-950/30 dark:text-red-300 dark:ring-red-900/40',
            default => 'bg-neutral-50 text-neutral-700 ring-1 ring-neutral-100 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-700',
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
