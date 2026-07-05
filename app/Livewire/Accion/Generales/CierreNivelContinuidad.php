<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Generacion;
use App\Models\Nivel;
use App\Services\GestionAcademicaService;
use Illuminate\Support\Collection;
use Livewire\Component;

class CierreNivelContinuidad extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $generaciones;

    public ?int $generacion_id = null;
    public string $motivo = '';
    public bool $egresar_activos = true;

    public bool $modalReactivar = false;
    public ?int $generacionReactivarId = null;
    public string $motivo_reactivacion = '';
    public bool $reactivar_egresados = true;

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->refrescarGeneraciones();
    }

    public function desactivar(GestionAcademicaService $service): void
    {
        $datos = $this->validate([
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
            'egresar_activos' => ['boolean'],
        ]);

        $generacion = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($datos['generacion_id']);

        if (!$generacion->status) {
            $this->addError('generacion_id', 'La generación ya está desactivada.');

            return;
        }

        $afectados = $service->desactivarGeneracion(
            $generacion,
            trim($datos['motivo']),
            (bool) $datos['egresar_activos'],
            auth()->id()
        );

        $egresoActivos = (bool) $datos['egresar_activos'];

        $this->reset(['generacion_id', 'motivo']);
        $this->egresar_activos = true;
        $this->refrescarGeneraciones();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Generación cerrada',
            'text' => $egresoActivos
                ? $afectados . ' alumno(s) fueron marcados como egresados.'
                : 'La generación fue cerrada sin modificar los estatus individuales.',
            'position' => 'top-end',
        ]);
    }

    public function prepararReactivacion(int $generacionId): void
    {
        $generacion = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', false)
            ->findOrFail($generacionId);

        $this->generacionReactivarId = $generacion->id;
        $this->motivo_reactivacion = '';
        $this->reactivar_egresados = true;
        $this->modalReactivar = true;
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
            ->where('nivel_id', $this->nivel->id)
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
        $this->refrescarGeneraciones();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Generación reabierta',
            'text' => $reactivoEgresados
                ? $afectados . ' alumno(s) egresado(s) fueron reactivados para correcciones.'
                : 'La generación quedó activa sin modificar los estatus de los alumnos.',
            'position' => 'top-end',
        ]);
    }

    private function refrescarGeneraciones(): void
    {
        $this->generaciones = Generacion::query()
            ->withCount([
                'inscripciones',
                'inscripciones as activos_count' => fn($query) => $query->whereIn('estatus', ['activo', 'reingreso', 'no_promovido']),
                'inscripciones as egresados_count' => fn($query) => $query->where('estatus', 'egresado'),
            ])
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    public function render()
    {
        $generacionReactivar = $this->generacionReactivarId
            ? $this->generaciones->firstWhere('id', $this->generacionReactivarId)
            : null;

        return view('livewire.accion.generales.cierre-nivel-continuidad', compact(
            'generacionReactivar'
        ));
    }
}
