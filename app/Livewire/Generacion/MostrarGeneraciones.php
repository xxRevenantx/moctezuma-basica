<?php

namespace App\Livewire\Generacion;

use App\Models\Generacion;
use App\Services\GestionAcademicaService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarGeneraciones extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $incluir_inactivas = true;

    public bool $modalDesactivar = false;
    public ?int $generacionSeleccionada = null;
    public string $motivo = '';
    public bool $egresar_activos = true;

    public bool $modalReactivar = false;
    public ?int $generacionReactivarId = null;
    public string $motivo_reactivacion = '';
    public bool $reactivar_egresados = true;

    public function updatingSearch(): void
    {
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

    #[On('refreshGeneraciones')]
    public function render()
    {
        $query = Generacion::query()
            ->with(['nivel', 'cicloEscolarInicio', 'cicloEscolarFin'])
            ->withCount([
                'inscripciones as alumnos_total_count',
                'inscripciones as alumnos_activos_count' => fn($q) => $q->whereIn('estatus', ['activo', 'reingreso', 'no_promovido']),
                'inscripciones as alumnos_egresados_count' => fn($q) => $q->where('estatus', 'egresado'),
                'inscripciones as alumnos_bajas_count' => fn($q) => $q->whereIn('estatus', ['baja_temporal', 'baja_definitiva', 'trasladado', 'suspendido', 'inactivo']),
            ])
            ->when(!$this->incluir_inactivas, fn($q) => $q->where('status', true))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn($sub) => $sub->where('nombre', 'like', $term)
                    ->orWhere('anio_ingreso', 'like', $term)
                    ->orWhere('anio_egreso', 'like', $term)
                    ->orWhereHas('nivel', fn($nivel) => $nivel->where('nombre', 'like', $term)));
            })
            ->orderBy('nivel_id')
            ->orderByDesc('anio_ingreso');

        $generaciones = $query->paginate(12);

        $generacionReactivar = $this->generacionReactivarId
            ? Generacion::query()
                ->with('nivel')
                ->withCount([
                    'inscripciones as egresados_count' => fn($q) => $q->where('estatus', 'egresado'),
                ])
                ->find($this->generacionReactivarId)
            : null;

        return view('livewire.generacion.mostrar-generaciones', compact(
            'generaciones',
            'generacionReactivar'
        ));
    }
}
