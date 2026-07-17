<?php

namespace App\Livewire;

use App\Models\Ciclo;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\ExpedienteDigitalService;
use App\Services\ObservacionInscripcionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
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

    /** @var array<int, int|string> */
    public array $seleccionados = [];

    public bool $modalObservaciones = false;
    public ?array $detalleObservaciones = null;

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
            ->orderByDesc('id')
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

        $this->actualizarVista();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value ? (int) $value : null;

        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->cargarSemestres();
        $this->cargarGrupos();

        $this->actualizarVista();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;

        $this->grupo_id = null;

        $this->cargarGrupos();

        $this->actualizarVista();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;

        $this->grupo_id = null;

        $this->cargarGrupos();

        $this->actualizarVista();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;
        $this->actualizarVista();
    }

    public function updatedCicloId($value): void
    {
        $this->ciclo_id = $value ? (int) $value : null;
        $this->actualizarVista();
    }

    public function updatedBuscar(): void
    {
        $this->actualizarVista();
    }

    public function updatedGenero(): void
    {
        $this->actualizarVista();
    }

    public function updatedEstatus(): void
    {
        $this->actualizarVista();
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
        $this->nivel_id = null;
        $this->grado_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->ciclo_id = null;

        $this->buscar = '';
        $this->genero = '';
        $this->estatus = 'activos';
        $this->orden = 'apellidos';
        $this->perPage = 25;

        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->cargarGeneraciones();

        $this->actualizarVista();
    }

    public function verObservaciones(int $alumnoId): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);

        $alumno = Inscripcion::query()
            ->select(['id', 'matricula', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->with([
                'observacionesInscripcion' => fn ($query) => $query
                    ->with([
                        'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                        'actualizador:id,name',
                        'historial' => fn ($historial) => $historial
                            ->with('usuario:id,name')
                            ->latest('created_at'),
                    ])
                    ->orderByDesc('ciclo_escolar_id'),
            ])
            ->findOrFail($alumnoId);

        $observacionesService = app(ObservacionInscripcionService::class);

        $this->detalleObservaciones = [
            'id' => $alumno->id,
            'matricula' => $alumno->matricula,
            'nombre' => $this->nombreCompleto($alumno),
            'ciclos' => $alumno->observacionesInscripcion
                ->map(function ($observacion) use ($observacionesService): array {
                    return [
                        'id' => $observacion->id,
                        'ciclo' => $observacion->cicloEscolar
                            ? $observacion->cicloEscolar->inicio_anio.'-'.$observacion->cicloEscolar->fin_anio
                            : 'Ciclo no disponible',
                        'es_actual' => (bool) ($observacion->cicloEscolar?->es_actual ?? false),
                        'contenido' => $observacionesService->sanitizar($observacion->contenido),
                        'actualizado_por' => $observacion->actualizador?->name ?? 'Sistema',
                        'actualizado_at' => optional($observacion->updated_at)->format('d/m/Y H:i'),
                        'historial' => $observacion->historial
                            ->take(20)
                            ->map(fn ($item): array => [
                                'id' => $item->id,
                                'origen' => str($item->origen)->replace('_', ' ')->title()->toString(),
                                'usuario' => $item->usuario?->name ?? 'Sistema',
                                'fecha' => optional($item->created_at)->format('d/m/Y H:i'),
                                'anterior' => $observacionesService->sanitizar($item->contenido_anterior),
                                'nuevo' => $observacionesService->sanitizar($item->contenido_nuevo),
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];

        $this->modalObservaciones = true;
    }

    public function cerrarObservaciones(): void
    {
        $this->modalObservaciones = false;
        $this->detalleObservaciones = null;
    }

    public function eliminarAlumno(int $alumnoId): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.eliminar'), 403);

        $alumno = Inscripcion::query()->find($alumnoId);

        if (!$alumno) {
            $this->dispatch('notify', type: 'error', message: 'El alumno no existe o ya fue eliminado.');
            return;
        }

        $alumno->delete();
        $this->seleccionados = array_values(array_filter(
            $this->seleccionados,
            fn ($id) => (int) $id !== $alumnoId,
        ));

        $this->dispatch('notify', type: 'success', message: 'Alumno eliminado correctamente.');

        $this->actualizarVista();
    }

    public function alternarSeleccionPagina(): void
    {
        $idsPagina = $this->idsPaginaActual();

        if (empty($idsPagina)) {
            return;
        }

        $seleccionados = collect($this->seleccionados)->map(fn ($id) => (int) $id);
        $todosSeleccionados = collect($idsPagina)->every(fn (int $id) => $seleccionados->contains($id));

        $this->seleccionados = $todosSeleccionados
            ? $seleccionados->reject(fn (int $id) => in_array($id, $idsPagina, true))->values()->all()
            : $seleccionados->merge($idsPagina)->unique()->values()->all();
    }

    public function seleccionarTodosResultados(): void
    {
        $ids = $this->consultaBase(false, true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->seleccionados = collect($this->seleccionados)
            ->map(fn ($id) => (int) $id)
            ->merge($ids)
            ->unique()
            ->values()
            ->all();

        $this->dispatch('notify', type: 'success', message: count($ids) . ' alumno(s) filtrado(s) seleccionados.');
    }

    public function invertirSeleccion(): void
    {
        $idsFiltrados = $this->consultaBase(false, true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $seleccionados = collect($this->seleccionados)->map(fn ($id) => (int) $id);
        $fueraDelFiltro = $seleccionados->reject(fn (int $id) => $idsFiltrados->contains($id));
        $invertidos = $idsFiltrados->reject(fn (int $id) => $seleccionados->contains($id));

        $this->seleccionados = $fueraDelFiltro
            ->merge($invertidos)
            ->unique()
            ->values()
            ->all();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function prepararGenerador(): void
    {
        if (empty($this->seleccionados)) {
            $this->dispatch('mostrar-alerta-sin-seleccion');
            return;
        }

        $this->dispatch('abrir-generador-lista');
    }

    public function estaSeleccionado(int $alumnoId): bool
    {
        return collect($this->seleccionados)
            ->map(fn ($id) => (int) $id)
            ->contains($alumnoId);
    }

    private function idsPaginaActual(): array
    {
        $pagina = max(1, Paginator::resolveCurrentPage('page'));

        return $this->consultaBase(false, true)
            ->forPage($pagina, $this->perPage)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function actualizarVista(): void
    {
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
            ->select('id', 'nivel_id', 'nombre', 'slug', 'orden')
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
            ->select([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato()) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $this->grupos = $consulta
            ->get()
            ->sortBy(fn($grupo) => $grupo->asignacionGrupo?->nombre ?? '')
            ->values();
    }

    private function consultaBase(bool $conRelaciones = true, bool $conOrden = true): Builder
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
                'motivo_baja',
                'observaciones_baja',
                'fecha_inscripcion',
                'created_at',
            ]);

        if ($conRelaciones) {
            $relaciones = [
                'nivel:id,nombre,slug,color',
                'grado:id,nombre,slug,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,grado_id,numero,orden_global',
                'ciclo:id,ciclo',
                'grupo' => function ($query) {
                    $query->select([
                        'id',
                        'asignacion_grupo_id',
                        'nivel_id',
                        'grado_id',
                        'generacion_id',
                        'semestre_id',
                    ])->with('asignacionGrupo:id,nombre');
                },
            ];

            if (auth()->user()?->is_admin) {
                $relaciones[] = 'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden';
                $relaciones[] = 'documentos.nivel:id,nombre,slug,color';
            }

            $consulta->with($relaciones);
        }

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
            $buscar = trim($this->buscar);

            $consulta->where(function ($query) use ($buscar) {
                $query->where('matricula', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%")
                    ->orWhere('folio', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%");
            });
        }

        if ($conOrden) {
            $this->aplicarOrden($consulta);
        }

        return $consulta;
    }

    private function aplicarOrden(Builder $consulta): void
    {
        match ($this->orden) {
            'recientes' => $consulta->orderByDesc('created_at'),

            'matricula' => $consulta
                ->orderBy('matricula')
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre'),

            'nivel' => $consulta
                ->orderBy(
                    Nivel::query()
                        ->select('nombre')
                        ->whereColumn('niveles.id', 'inscripciones.nivel_id')
                        ->limit(1)
                )
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre'),

            default => $consulta
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre'),
        };
    }

    private function recalcularResumen(): void
    {
        $base = $this->consultaBase(false, false);

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

        if (!$nivel) {
            return false;
        }

        return Str::contains(Str::lower($nivel->slug . ' ' . $nivel->nombre), 'bachillerato');
    }

    public function nombreCompleto($alumno): string
    {
        return trim(
            ($alumno->apellido_paterno ?? '') . ' ' .
            ($alumno->apellido_materno ?? '') . ' ' .
            ($alumno->nombre ?? '')
        );
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre ?? '—';
    }

    public function textoGeneracion($generacion): string
    {
        if (!$generacion) {
            return '—';
        }

        return $generacion->anio_ingreso . '-' . $generacion->anio_egreso;
    }

    public function textoGenero(?string $genero): string
    {
        return match ($genero) {
            'H' => 'Hombre',
            'M' => 'Mujer',
            default => '—',
        };
    }

    public function render()
    {
        $alumnos = $this->consultaBase()
            ->paginate($this->perPage);

        if (auth()->user()?->is_admin) {
            $servicio = app(ExpedienteDigitalService::class);

            $alumnos->getCollection()->transform(function (Inscripcion $alumno) use ($servicio) {
                $alumno->setAttribute('resumen_documental', $servicio->resumen($alumno));

                return $alumno;
            });
        }

        $idsPagina = $alumnos->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        $seleccionados = collect($this->seleccionados)->map(fn ($id) => (int) $id);

        return view('livewire.alumnos-generales', [
            'alumnos' => $alumnos,
            'todosPaginaSeleccionados' => $idsPagina->isNotEmpty()
                && $idsPagina->every(fn (int $id) => $seleccionados->contains($id)),
            'totalSeleccionados' => $seleccionados->unique()->count(),
        ]);
    }
}
