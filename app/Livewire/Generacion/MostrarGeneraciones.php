<?php

namespace App\Livewire\Generacion;

use App\Models\Generacion;
use App\Models\Nivel;
use App\Services\GestionAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGeneraciones extends Component
{
    use WithPagination;

    public string $search = '';
    public string $nivel_id = '';
    public string $estado = '';
    public string $anio_ingreso = '';
    public string $anio_egreso = '';
    public string $orden = 'recientes';

    public bool $modalDesactivar = false;
    public ?int $generacionSeleccionada = null;
    public string $motivo = '';
    public bool $egresar_activos = true;

    public bool $modalReactivar = false;
    public ?int $generacionReactivarId = null;
    public string $motivo_reactivacion = '';
    public bool $reactivar_egresados = true;

    protected $queryString = [
        'search' => ['as' => 'buscar', 'except' => ''],
        'nivel_id' => ['as' => 'nivel', 'except' => ''],
        'estado' => ['as' => 'estado', 'except' => ''],
        'anio_ingreso' => ['as' => 'ingreso', 'except' => ''],
        'anio_egreso' => ['as' => 'egreso', 'except' => ''],
        'orden' => ['as' => 'orden', 'except' => 'recientes'],
    ];

    /**
     * Regresa a la primera página cuando cambia cualquier filtro.
     */
    public function updated(string $property): void
    {
        if (in_array($property, [
            'search',
            'nivel_id',
            'estado',
            'anio_ingreso',
            'anio_egreso',
            'orden',
        ], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'search',
            'nivel_id',
            'estado',
            'anio_ingreso',
            'anio_egreso',
        ]);

        $this->orden = 'recientes';
        $this->resetPage();
    }

    public function prepararDesactivacion(int $id): void
    {
        $this->generacionSeleccionada = $id;
        $this->motivo = '';
        $this->egresar_activos = true;
        $this->modalDesactivar = true;
    }

    public function prepararReactivacion(int $id): void
    {
        $generacion = Generacion::query()->findOrFail($id);

        if ($generacion->status) {
            $this->dispatch('swal', [
                'title' => 'La generación ya está activa',
                'icon' => 'info',
                'position' => 'top-end',
            ]);

            return;
        }

        $this->generacionReactivarId = $generacion->id;
        $this->motivo_reactivacion = '';
        $this->reactivar_egresados = true;
        $this->modalReactivar = true;
    }

    public function desactivar(GestionAcademicaService $service): void
    {
        $this->validate([
            'generacionSeleccionada' => ['required', 'exists:generaciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
            'egresar_activos' => ['boolean'],
        ]);

        $generacion = Generacion::query()->findOrFail($this->generacionSeleccionada);
        $afectados = $service->desactivarGeneracion(
            $generacion,
            trim($this->motivo),
            $this->egresar_activos,
            auth()->id()
        );

        $egresoActivos = $this->egresar_activos;

        $this->modalDesactivar = false;
        $this->reset(['generacionSeleccionada', 'motivo']);
        $this->egresar_activos = true;

        $this->dispatch('swal', [
            'title' => 'Generación desactivada',
            'text' => $egresoActivos
                ? "Se marcaron {$afectados} alumno(s) como egresados."
                : 'Los alumnos conservaron su estatus individual.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function reactivar(GestionAcademicaService $service): void
    {
        $datos = $this->validate([
            'generacionReactivarId' => ['required', 'exists:generaciones,id'],
            'motivo_reactivacion' => ['required', 'string', 'min:5', 'max:1000'],
            'reactivar_egresados' => ['boolean'],
        ], [
            'motivo_reactivacion.required' => 'Escribe el motivo de la reapertura.',
            'motivo_reactivacion.min' => 'El motivo debe contener al menos 5 caracteres.',
        ]);

        $generacion = Generacion::query()
            ->where('status', false)
            ->findOrFail($datos['generacionReactivarId']);

        $afectados = $service->reactivarGeneracion(
            $generacion,
            trim($datos['motivo_reactivacion']),
            auth()->id(),
            (bool) $datos['reactivar_egresados']
        );

        $reactivoEgresados = (bool) $datos['reactivar_egresados'];

        $this->modalReactivar = false;
        $this->reset(['generacionReactivarId', 'motivo_reactivacion']);
        $this->reactivar_egresados = true;

        $this->dispatch('swal', [
            'title' => 'Generación reabierta',
            'text' => $reactivoEgresados
                ? "La generación quedó activa y {$afectados} alumno(s) egresado(s) fueron reactivados para correcciones."
                : 'La generación quedó activa; los alumnos conservaron su estatus individual.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function aplicarFiltroEstado(Builder $query): void
    {
        match ($this->estado) {
            'activa' => $query->where('status', true),
            'cerrada' => $query->where('status', false),
            'en_proceso' => $query->where('estado_cierre', 'en_proceso'),
            'egresada' => $query->where('estado_cierre', 'egresada'),
            'archivada' => $query->where('estado_cierre', 'archivada'),
            default => null,
        };
    }

    private function aplicarOrden(Builder $query): void
    {
        match ($this->orden) {
            'antiguas' => $query->orderBy('anio_ingreso')->orderBy('nivel_id'),
            'nivel' => $query->orderBy('nivel_id')->orderByDesc('anio_ingreso'),
            'nombre' => $query->orderBy('nombre')->orderByDesc('anio_ingreso'),
            'alumnos' => $query->orderByDesc('alumnos_total_count')->orderByDesc('anio_ingreso'),
            default => $query->orderByDesc('anio_ingreso')->orderBy('nivel_id'),
        };
    }

    #[On('refreshGeneraciones')]
    public function render()
    {
        $query = Generacion::query()
            ->with(['nivel', 'cicloEscolarInicio', 'cicloEscolarFin'])
            ->withCount([
                'inscripciones as alumnos_total_count',
                'inscripciones as alumnos_activos_count' => fn ($q) => $q->whereIn('estatus', ['activo', 'reingreso', 'no_promovido']),
                'inscripciones as alumnos_egresados_count' => fn ($q) => $q->where('estatus', 'egresado'),
                'inscripciones as alumnos_bajas_count' => fn ($q) => $q->whereIn('estatus', ['baja_temporal', 'baja_definitiva', 'trasladado', 'suspendido', 'inactivo']),
            ])
            ->when($this->nivel_id !== '', fn ($q) => $q->where('nivel_id', (int) $this->nivel_id))
            ->when($this->anio_ingreso !== '', fn ($q) => $q->where('anio_ingreso', (int) $this->anio_ingreso))
            ->when($this->anio_egreso !== '', fn ($q) => $q->where('anio_egreso', (int) $this->anio_egreso))
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';

                $q->where(fn ($sub) => $sub
                    ->where('nombre', 'like', $term)
                    ->orWhere('anio_ingreso', 'like', $term)
                    ->orWhere('anio_egreso', 'like', $term)
                    ->orWhereHas('nivel', fn ($nivel) => $nivel->where('nombre', 'like', $term))
                    ->orWhereHas('cicloEscolarInicio', fn ($ciclo) => $ciclo
                        ->where('inicio_anio', 'like', $term)
                        ->orWhere('fin_anio', 'like', $term)
                        ->orWhereRaw("CONCAT(inicio_anio, '-', fin_anio) LIKE ?", [$term]))
                    ->orWhereHas('cicloEscolarFin', fn ($ciclo) => $ciclo
                        ->where('inicio_anio', 'like', $term)
                        ->orWhere('fin_anio', 'like', $term)
                        ->orWhereRaw("CONCAT(inicio_anio, '-', fin_anio) LIKE ?", [$term])));
            });

        $this->aplicarFiltroEstado($query);
        $this->aplicarOrden($query);

        $generaciones = $query->paginate(12);

        $niveles = Nivel::query()
            ->whereHas('generaciones')
            ->orderBy('id')
            ->get(['id', 'nombre']);

        $aniosIngreso = Generacion::query()
            ->whereNotNull('anio_ingreso')
            ->distinct()
            ->orderByDesc('anio_ingreso')
            ->pluck('anio_ingreso');

        $aniosEgreso = Generacion::query()
            ->whereNotNull('anio_egreso')
            ->distinct()
            ->orderByDesc('anio_egreso')
            ->pluck('anio_egreso');

        $hayFiltrosActivos = $this->search !== ''
            || $this->nivel_id !== ''
            || $this->estado !== ''
            || $this->anio_ingreso !== ''
            || $this->anio_egreso !== ''
            || $this->orden !== 'recientes';

        $generacionReactivar = $this->generacionReactivarId
            ? Generacion::query()
                ->with('nivel')
                ->withCount([
                    'inscripciones as egresados_count' => fn ($q) => $q->where('estatus', 'egresado'),
                ])
                ->find($this->generacionReactivarId)
            : null;

        return view('livewire.generacion.mostrar-generaciones', compact(
            'generaciones',
            'generacionReactivar',
            'niveles',
            'aniosIngreso',
            'aniosEgreso',
            'hayFiltrosActivos'
        ));
    }
}
