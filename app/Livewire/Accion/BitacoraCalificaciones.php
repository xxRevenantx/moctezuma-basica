<?php

namespace App\Livewire\Accion;

use App\Models\BitacoraCalificacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public array $periodos = [];

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

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();

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
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->periodos = [];

        if (!$this->nivel_id) {
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $this->grupos = $this->consultaGruposBase()
            ->when($this->grupo_id, function ($query) {
                $query->where('grupos.id', $this->grupo_id);
            })
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

    private function consultaGruposBase(): Builder
    {
        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->when($this->nivel_id, function ($query) {
                $query->where('grupos.nivel_id', $this->nivel_id);
            })
            ->when($this->grado_id, function ($query) {
                $query->where('grupos.grado_id', $this->grado_id);
            })
            ->when($this->generacion_id, function ($query) {
                $query->where('grupos.generacion_id', $this->generacion_id);
            })
            ->when($this->esBachillerato && $this->semestre_id, function ($query) {
                $query->where('grupos.semestre_id', $this->semestre_id);
            })
            ->when(!$this->esBachillerato, function ($query) {
                $query->whereNull('grupos.semestre_id');
            })
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id');
    }

    private function consultaBase(): Builder
    {
        return BitacoraCalificacion::query()
            ->with([
                'usuario:id,name,email',
                'inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id',
                'asignacionMateria.materia:id,materia,clave,slug',
                'asignacionMateria.grupo' => function ($query) {
                    $query->select('id', 'asignacion_grupo_id')
                        ->with('asignacionGrupo:id,nombre');
                },
                'grado:id,nombre',
                'grupo' => function ($query) {
                    $query->select('id', 'asignacion_grupo_id')
                        ->with('asignacionGrupo:id,nombre');
                },
                'semestre:id,numero',
            ])
            ->when($this->nivel_id, function ($query) {
                $query->where('nivel_id', $this->nivel_id);
            })
            ->when($this->grado_id, function ($query) {
                $query->where('grado_id', $this->grado_id);
            })
            ->when($this->grupo_id, function ($query) {
                $query->where('grupo_id', $this->grupo_id);
            })
            ->when($this->periodo_id, function ($query) {
                $query->where('periodo_id', $this->periodo_id);
            })
            ->when($this->generacion_id, function ($query) {
                $query->where('generacion_id', $this->generacion_id);
            })
            ->when($this->esBachillerato && $this->semestre_id, function ($query) {
                $query->where('semestre_id', $this->semestre_id);
            })
            ->when(!$this->esBachillerato, function ($query) {
                $query->whereNull('semestre_id');
            })
            ->when($this->accion !== '', function ($query) {
                $query->where('accion', $this->accion);
            })
            ->when($this->tipo_valor !== '', function ($query) {
                $query->where('tipo_valor', $this->tipo_valor);
            })
            ->when(trim($this->buscar_alumno) !== '', function ($query) {
                $buscar = trim($this->buscar_alumno);

                $query->whereHas('inscripcion', function ($subquery) use ($buscar) {
                    $subquery->where('matricula', 'like', '%' . $buscar . '%')
                        ->orWhere('nombre', 'like', '%' . $buscar . '%')
                        ->orWhere('apellido_paterno', 'like', '%' . $buscar . '%')
                        ->orWhere('apellido_materno', 'like', '%' . $buscar . '%')
                        ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', IFNULL(apellido_materno, '')) LIKE ?", ['%' . $buscar . '%'])
                        ->orWhereRaw("CONCAT(apellido_paterno, ' ', IFNULL(apellido_materno, ''), ' ', nombre) LIKE ?", ['%' . $buscar . '%']);
                });
            })
            ->when(trim($this->buscar_materia) !== '', function ($query) {
                $buscar = trim($this->buscar_materia);

                $query->whereHas('asignacionMateria.materia', function ($subquery) use ($buscar) {
                    $subquery->where('materia', 'like', '%' . $buscar . '%')
                        ->orWhere('clave', 'like', '%' . $buscar . '%')
                        ->orWhere('slug', 'like', '%' . $buscar . '%');
                });
            })
            ->when(trim($this->buscar_usuario) !== '', function ($query) {
                $buscar = trim($this->buscar_usuario);

                $query->whereHas('usuario', function ($subquery) use ($buscar) {
                    $subquery->where('name', 'like', '%' . $buscar . '%')
                        ->orWhere('email', 'like', '%' . $buscar . '%');
                });
            })
            ->when(trim($this->buscar_general) !== '', function ($query) {
                $buscar = trim($this->buscar_general);

                $query->where(function ($subquery) use ($buscar) {
                    $subquery->where('calificacion_anterior', 'like', '%' . $buscar . '%')
                        ->orWhere('calificacion_nueva', 'like', '%' . $buscar . '%')
                        ->orWhere('tipo_valor', 'like', '%' . $buscar . '%')
                        ->orWhere('observacion', 'like', '%' . $buscar . '%')
                        ->orWhere('motivo', 'like', '%' . $buscar . '%')
                        ->orWhere('ip', 'like', '%' . $buscar . '%')
                        ->orWhereHas('asignacionMateria.materia', function ($materiaQuery) use ($buscar) {
                            $materiaQuery->where('materia', 'like', '%' . $buscar . '%')
                                ->orWhere('clave', 'like', '%' . $buscar . '%')
                                ->orWhere('slug', 'like', '%' . $buscar . '%');
                        })
                        ->orWhereHas('grupo.asignacionGrupo', function ($grupoQuery) use ($buscar) {
                            $grupoQuery->where('nombre', 'like', '%' . $buscar . '%');
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

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return 'No seleccionado';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    public function textoMateria($row): string
    {
        return $row->asignacionMateria?->materia?->materia ?? 'Sin materia';
    }

    public function textoAlumno($row): string
    {
        $inscripcion = $row->inscripcion;

        if (!$inscripcion) {
            return 'Sin alumno';
        }

        return trim(
            ($inscripcion->nombre ?? '') . ' ' .
            ($inscripcion->apellido_paterno ?? '') . ' ' .
            ($inscripcion->apellido_materno ?? '')
        ) ?: 'Sin alumno';
    }

    public function claseAccion(?string $accion): string
    {
        return match ($accion) {
            'crear' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
            'editar' => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',
            'eliminar' => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
            default => 'bg-slate-50 text-slate-700 ring-slate-100 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
        };
    }

    public function claseTipoValor(?string $tipo): string
    {
        return match ($tipo) {
            'numerico' => 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50',
            'especial' => 'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/50',
            'vacio' => 'bg-slate-50 text-slate-600 ring-slate-100 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
            default => 'bg-neutral-50 text-neutral-700 ring-neutral-100 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-700',
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
