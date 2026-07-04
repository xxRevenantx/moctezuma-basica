<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\ListaGeneracionesHistoricasService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ListasGeneracionesHistoricas extends Component
{
    public string $slug_nivel = '';
    public Nivel $nivel;
    public Collection $generacionesActivas;
    public Collection $generacionesEgresadas;
    public Collection $grupos;

    public array $generacionesSeleccionadas = [];
    public string $estatus = 'egresado';
    public ?int $grupo_id = null;
    public bool $incluir_archivados = false;
    public string $salida = 'unico';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->whereNull('deleted_at')
            ->orderByDesc('status')
            ->orderByDesc('anio_egreso')
            ->orderByDesc('anio_ingreso')
            ->get([
                'id',
                'nivel_id',
                'anio_ingreso',
                'anio_egreso',
                'nombre',
                'status',
                'motivo_desactivacion',
                'fecha_termino',
            ]);

        $this->generacionesActivas = $generaciones->where('status', true)->values();
        $this->generacionesEgresadas = $generaciones->where('status', false)->values();
        $this->grupos = collect();

        $predeterminada = $this->generacionesEgresadas->first()
            ?? $this->generacionesActivas->first();

        if ($predeterminada) {
            $this->generacionesSeleccionadas = [(string) $predeterminada->id];
            $this->cargarGrupos();
        }
    }

    public function updatedGeneracionesSeleccionadas(): void
    {
        $this->generacionesSeleccionadas = collect($this->generacionesSeleccionadas)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (string) ((int) $id))
            ->unique()
            ->values()
            ->all();

        $this->grupo_id = null;
        $this->cargarGrupos();
        unset($this->resumenSeleccion);
    }

    public function updatedEstatus(): void
    {
        unset($this->resumenSeleccion);
    }

    public function updatedGrupoId(): void
    {
        unset($this->resumenSeleccion);
    }

    public function updatedIncluirArchivados(): void
    {
        $this->grupo_id = null;
        $this->cargarGrupos();
        unset($this->resumenSeleccion);
    }

    public function seleccionarEgresadas(): void
    {
        $this->generacionesSeleccionadas = $this->generacionesEgresadas
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->grupo_id = null;
        $this->cargarGrupos();
        unset($this->resumenSeleccion);
    }

    public function seleccionarTodas(): void
    {
        $this->generacionesSeleccionadas = $this->generacionesEgresadas
            ->concat($this->generacionesActivas)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $this->grupo_id = null;
        $this->cargarGrupos();
        unset($this->resumenSeleccion);
    }

    public function limpiarSeleccion(): void
    {
        $this->generacionesSeleccionadas = [];
        $this->grupo_id = null;
        $this->grupos = collect();
        unset($this->resumenSeleccion);
    }

    public function cargarGrupos(): void
    {
        $ids = $this->idsSeleccionados();

        if ($ids->isEmpty()) {
            $this->grupos = collect();

            return;
        }

        $this->grupos = Grupo::query()
            ->with([
                'generacion:id,anio_ingreso,anio_egreso,nombre',
                'grado:id,nombre,orden',
                'semestre',
                'asignacionGrupo:id,nombre',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('generacion_id', $ids)
            ->whereHas('inscripciones', function (Builder $query): void {
                if ($this->incluir_archivados) {
                    $query->withTrashed();
                }
            })
            ->get()
            ->sortBy(fn (Grupo $grupo) => sprintf(
                '%04d|%04d|%04d|%s',
                (int) ($grupo->generacion?->anio_ingreso ?? 0),
                (int) ($grupo->grado?->orden ?? 9999),
                (int) ($grupo->semestre?->numero ?? $grupo->semestre_id ?? 0),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? 'zzzz'))
            ))
            ->values();
    }

    #[Computed]
    public function puedeGenerar(): bool
    {
        return $this->idsSeleccionados()->isNotEmpty();
    }

    #[Computed]
    public function resumenSeleccion(): array
    {
        $ids = $this->idsSeleccionados();

        if ($ids->isEmpty()) {
            return $this->resumenVacio();
        }

        $query = Inscripcion::query()
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('generacion_id', $ids)
            ->when($this->grupo_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_id))
            ->when($this->estatus !== 'todos', fn (Builder $q) => $q->where('estatus', $this->estatus));

        if ($this->incluir_archivados) {
            $query->withTrashed();
        }

        $alumnos = $query->get(['id', 'genero', 'estatus', 'deleted_at']);

        return [
            'total' => $alumnos->count(),
            'hombres' => $alumnos->where('genero', 'H')->count(),
            'mujeres' => $alumnos->where('genero', 'M')->count(),
            'egresados' => $alumnos->where('estatus', 'egresado')->count(),
            'bajas' => $alumnos->whereIn('estatus', ['baja_temporal', 'baja_definitiva'])->count(),
            'trasladados' => $alumnos->where('estatus', 'trasladado')->count(),
            'archivados' => $alumnos->whereNotNull('deleted_at')->count(),
        ];
    }

    #[Computed]
    public function parametros(): array
    {
        return [
            'generacion_ids' => $this->idsSeleccionados()->all(),
            'estatus' => $this->estatus,
            'grupo_id' => $this->grupo_id,
            'incluir_archivados' => $this->incluir_archivados ? 1 : 0,
            'salida' => $this->salida,
        ];
    }

    #[Computed]
    public function urlVistaPdf(): ?string
    {
        if (!$this->puedeGenerar || $this->salida !== 'unico') {
            return null;
        }

        return route('generales.generaciones-historicas', array_merge([
            'slug_nivel' => $this->slug_nivel,
            'formato' => 'pdf',
            'descargar' => 0,
        ], $this->parametros));
    }

    #[Computed]
    public function urlPdf(): ?string
    {
        if (!$this->puedeGenerar) {
            return null;
        }

        return route('generales.generaciones-historicas', array_merge([
            'slug_nivel' => $this->slug_nivel,
            'formato' => 'pdf',
            'descargar' => 1,
        ], $this->parametros));
    }

    #[Computed]
    public function urlWord(): ?string
    {
        if (!$this->puedeGenerar) {
            return null;
        }

        return route('generales.generaciones-historicas', array_merge([
            'slug_nivel' => $this->slug_nivel,
            'formato' => 'word',
        ], $this->parametros));
    }

    public function etiquetaGeneracion(Generacion $generacion): string
    {
        return $generacion->nombre ?: $generacion->anio_ingreso . '-' . $generacion->anio_egreso;
    }

    public function etiquetaGrupo(Grupo $grupo): string
    {
        $partes = [$this->etiquetaGeneracion($grupo->generacion)];

        if ($grupo->grado?->nombre) {
            $partes[] = $grupo->grado->nombre . '°';
        }

        if ($grupo->semestre) {
            $partes[] = 'Sem. ' . ($grupo->semestre->numero ?? $grupo->semestre_id);
        }

        $partes[] = 'Grupo ' . ($grupo->asignacionGrupo?->nombre ?? 'Sin asignar');

        return implode(' · ', $partes);
    }

    private function idsSeleccionados(): Collection
    {
        return collect($this->generacionesSeleccionadas)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'hombres' => 0,
            'mujeres' => 0,
            'egresados' => 0,
            'bajas' => 0,
            'trasladados' => 0,
            'archivados' => 0,
        ];
    }

    public function render()
    {
        return view('livewire.accion.generales.listas-generaciones-historicas', [
            'estatusDisponibles' => [
                'egresado' => 'Egresados',
                'todos' => 'Todos los estatus',
                'activo' => 'Activos',
                'reingreso' => 'Reingresos',
                'no_promovido' => 'No promovidos',
                'baja_temporal' => 'Baja temporal',
                'baja_definitiva' => 'Baja definitiva',
                'trasladado' => 'Trasladados',
                'suspendido' => 'Suspendidos',
                'inactivo' => 'Inactivos',
            ],
        ]);
    }
}
