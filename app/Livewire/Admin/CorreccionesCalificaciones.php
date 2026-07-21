<?php

namespace App\Livewire\Admin;

use App\Models\CalificacionCorreccion;
use App\Services\CalificacionCorreccionService;
use App\Services\SystemAuditService;
use Livewire\Component;
use Livewire\WithPagination;

class CorreccionesCalificaciones extends Component
{
    use WithPagination;

    public string $estado = 'solicitada';

    /** @var array<int,string> */
    public array $observaciones = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function autorizar(int $id, CalificacionCorreccionService $service, SystemAuditService $audit): void
    {
        $correccion = CalificacionCorreccion::query()->findOrFail($id);
        $observacion = trim((string) ($this->observaciones[$id] ?? 'Autorizada después de revisar la solicitud y el periodo histórico.'));
        $service->autorizar($correccion, $observacion, auth()->id());
        $audit->record('grade_correction_authorized', 'calificaciones', ['correccion_id' => $id, 'observacion' => $observacion]);
        unset($this->observaciones[$id]);
        $this->dispatch('swal', icon: 'success', title: 'Corrección autorizada', text: 'Todavía debes aplicarla para modificar la calificación.');
    }

    public function rechazar(int $id, SystemAuditService $audit): void
    {
        $observacion = trim((string) ($this->observaciones[$id] ?? ''));
        if (mb_strlen($observacion) < 10) {
            $this->addError("observaciones.{$id}", 'Escribe el motivo del rechazo.');
            return;
        }

        $correccion = CalificacionCorreccion::query()->findOrFail($id);
        $correccion->update([
            'estado' => CalificacionCorreccion::RECHAZADA,
            'autorizada_por' => auth()->id(),
            'autorizada_at' => now(),
            'observacion_autorizacion' => $observacion,
        ]);
        $audit->record('grade_correction_rejected', 'calificaciones', ['correccion_id' => $id, 'observacion' => $observacion]);
        unset($this->observaciones[$id]);
        $this->dispatch('swal', icon: 'success', title: 'Corrección rechazada');
    }

    public function aplicar(int $id, CalificacionCorreccionService $service, SystemAuditService $audit): void
    {
        $correccion = CalificacionCorreccion::query()->findOrFail($id);
        $service->aplicar($correccion, auth()->id());
        $audit->record('grade_correction_applied', 'calificaciones', ['correccion_id' => $id]);
        $this->dispatch('swal', icon: 'success', title: 'Corrección aplicada', text: 'El cambio quedó registrado en el flujo de correcciones históricas.');
    }

    public function render()
    {
        $correcciones = CalificacionCorreccion::query()
            ->with([
                'inscripcion:id,nombre,apellido_paterno,apellido_materno,matricula',
                'periodo.cicloEscolar',
                'periodo.nivel',
                'solicitante:id,name',
                'autorizador:id,name',
                'calificacion.asignacionMateria.materia',
            ])
            ->when($this->estado !== '', fn ($query) => $query->where('estado', $this->estado))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.correcciones-calificaciones', compact('correcciones'));
    }
}
