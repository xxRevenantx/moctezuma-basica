<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\InscripcionCiclo;
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
    public Collection $ciclosEscolares;

    public ?int $ciclo_escolar_id = null;

    public array $generacionesSeleccionadas = [];
    public string $estatus = 'egresado';
    public ?int $grupo_id = null;
    public bool $incluir_archivados = false;
    public string $salida = 'unico';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_escolar_id = (int) ($this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id
            ?? 0) ?: null;
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


    public function updatedCicloEscolarId(): void
    {
        $this->grupo_id = null;
        $this->cargarGrupos();
        unset($this->resumenSeleccion);
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

        $grupoIds = InscripcionCiclo::query()
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('generacion_id', $ids)
            ->when($this->ciclo_escolar_id, fn (Builder $query) => $query->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id');

        $this->grupos = Grupo::query()
            ->with([
                'generacion:id,anio_ingreso,anio_egreso,nombre',
                'grado:id,nombre,orden',
                'semestre',
                'asignacionGrupo:id,nombre',
                'cicloEscolar:id,inicio_anio,fin_anio',
            ])
            ->whereIn('id', $grupoIds)
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

        $query = InscripcionCiclo::query()
            ->with('inscripcion:id,genero,deleted_at')
            ->where('nivel_id', $this->nivel->id)
            ->whereIn('generacion_id', $ids)
            ->when($this->ciclo_escolar_id, fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->grupo_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_id));

        if ($this->estatus !== 'todos') {
            match ($this->estatus) {
                'egresado' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'egresado')->orWhere('estatus_actual_ciclo', 'egresado')),
                'activo' => $query->where('estado', 'en_curso')->where('estatus_actual_ciclo', 'activo'),
                'reingreso' => $query->where('estatus_actual_ciclo', 'reingreso'),
                'no_promovido' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'no_promovido')->orWhere('estatus_actual_ciclo', 'no_promovido')),
                'baja_temporal' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'baja_temporal_al_cierre')->orWhere('estatus_actual_ciclo', 'baja_temporal')),
                'baja_definitiva' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'baja_definitiva')->orWhere('estatus_actual_ciclo', 'baja_definitiva')),
                'trasladado' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'trasladado')->orWhereIn('estatus_actual_ciclo', ['trasladado', 'traslado'])),
                default => $query->where('estatus_actual_ciclo', $this->estatus),
            };
        }

        $alumnos = $query->get()->filter(fn (InscripcionCiclo $ciclo) => $this->incluir_archivados || ! $ciclo->inscripcion?->trashed());
        $estatus = $alumnos->map(fn (InscripcionCiclo $ciclo) => $ciclo->resultado_final ?: $ciclo->estatus_actual_ciclo);

        return [
            'total' => $alumnos->count(),
            'hombres' => $alumnos->filter(fn (InscripcionCiclo $ciclo) => $ciclo->inscripcion?->genero === 'H')->count(),
            'mujeres' => $alumnos->filter(fn (InscripcionCiclo $ciclo) => $ciclo->inscripcion?->genero === 'M')->count(),
            'egresados' => $estatus->where('egresado')->count(),
            'bajas' => $estatus->filter(fn ($valor) => in_array($valor, ['baja_temporal', 'baja_temporal_al_cierre', 'baja_definitiva'], true))->count(),
            'trasladados' => $estatus->filter(fn ($valor) => in_array($valor, ['trasladado', 'traslado'], true))->count(),
            'archivados' => $alumnos->filter(fn (InscripcionCiclo $ciclo) => $ciclo->inscripcion?->trashed())->count(),
        ];
    }

    #[Computed]
    public function parametros(): array
    {
        return [
            'generacion_ids' => $this->idsSeleccionados()->all(),
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
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
