<?php

namespace App\Livewire\Admin;

use App\Models\IntegridadAcademicaCaso;
use App\Models\IntegridadAcademicaCorreccion;
use App\Models\IntegridadAcademicaAnalisis;
use App\Models\User;
use App\Services\IntegridadAcademicaCorreccionService;
use App\Services\IntegridadAcademicaService;
use App\Services\SystemAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CentroIntegridadAcademica extends Component
{
    use WithPagination;

    public string $buscar = '';
    public string $estado = 'abiertos';
    public string $severidad = '';
    public string $categoria = '';
    public string $orden = 'prioridad';

    public ?int $casoSeleccionadoId = null;
    public ?int $asignadoA = null;
    public string $motivo = '';
    public string $motivoIgnorar = '';
    public string $motivoResolucion = '';
    public string $motivoReversion = '';
    public string $confirmacionCorreccion = '';
    public string $confirmacionReversion = '';

    public bool $analizando = false;

    public function mount(IntegridadAcademicaService $service): void
    {
        abort_unless(auth()->user()?->canAccess('integridad.consultar'), 403);

        if (Schema::hasTable('integridad_academica_analisis')
            && ! IntegridadAcademicaAnalisis::query()->exists()) {
            try {
                $service->ejecutar(auth()->id(), 'inicial');
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function updatedBuscar(): void { $this->resetPage(); }
    public function updatedEstado(): void { $this->resetPage(); }
    public function updatedSeveridad(): void { $this->resetPage(); }
    public function updatedCategoria(): void { $this->resetPage(); }
    public function updatedOrden(): void { $this->resetPage(); }

    public function analizar(IntegridadAcademicaService $service, SystemAuditService $audit): void
    {
        $this->autorizarGestion();
        $this->analizando = true;

        try {
            $resultado = $service->ejecutar(auth()->id(), 'manual');
            $audit->record('academic_integrity_scan', 'integridad', $resultado);
            $this->resetPage();
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Análisis terminado',
                'text' => sprintf(
                    'Se detectaron %d casos: %d nuevos, %d reabiertos y %d resueltos automáticamente.',
                    (int) ($resultado['detectados'] ?? 0),
                    (int) ($resultado['nuevos'] ?? 0),
                    (int) ($resultado['reabiertos'] ?? 0),
                    (int) ($resultado['resueltos_automaticamente'] ?? 0),
                ),
                'position' => 'top-end',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No fue posible completar el análisis',
                'text' => $exception->getMessage(),
                'position' => 'top-end',
            ]);
        } finally {
            $this->analizando = false;
        }
    }

    public function seleccionarCaso(int $casoId): void
    {
        abort_unless(auth()->user()?->canAccess('integridad.consultar'), 403);
        $caso = IntegridadAcademicaCaso::query()->findOrFail($casoId);
        $this->casoSeleccionadoId = $caso->id;
        $this->asignadoA = $caso->asignado_a;
        $this->reset(['motivo', 'motivoIgnorar', 'motivoResolucion', 'motivoReversion', 'confirmacionCorreccion', 'confirmacionReversion']);
    }

    public function cerrarDetalle(): void
    {
        $this->casoSeleccionadoId = null;
        $this->reset(['motivo', 'motivoIgnorar', 'motivoResolucion', 'motivoReversion', 'confirmacionCorreccion', 'confirmacionReversion']);
    }

    public function iniciarRevision(IntegridadAcademicaService $service): void
    {
        $this->autorizarGestion();
        $caso = $this->casoSeleccionado();
        $anterior = $caso->only(['estado', 'revision_iniciada_at', 'revision_iniciada_por']);
        $caso->update([
            'estado' => IntegridadAcademicaCaso::ESTADO_REVISION,
            'revision_iniciada_at' => now(),
            'revision_iniciada_por' => auth()->id(),
            'asignado_a' => $caso->asignado_a ?: auth()->id(),
        ]);
        $service->registrarEvento($caso, 'revision_iniciada', 'El caso fue tomado para revisión.', auth()->id(), $anterior, $caso->only(['estado', 'revision_iniciada_at', 'revision_iniciada_por', 'asignado_a']));
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Caso en revisión', 'position' => 'top-end']);
    }

    public function guardarAsignacion(IntegridadAcademicaService $service): void
    {
        $this->autorizarGestion();
        $this->validate(['asignadoA' => ['nullable', 'integer', 'exists:users,id']]);
        $caso = $this->casoSeleccionado();
        $anterior = ['asignado_a' => $caso->asignado_a];
        $caso->update(['asignado_a' => $this->asignadoA]);
        $service->registrarEvento($caso, 'responsable_asignado', 'Se actualizó el responsable del caso.', auth()->id(), $anterior, ['asignado_a' => $caso->asignado_a]);
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Responsable actualizado', 'position' => 'top-end']);
    }

    public function marcarResuelto(IntegridadAcademicaService $service): void
    {
        $this->autorizarGestion();
        $this->validate(['motivoResolucion' => ['required', 'string', 'min:10', 'max:2000']]);
        $caso = $this->casoSeleccionado();
        $anterior = $caso->only(['estado', 'resuelto_at', 'motivo_resolucion']);
        $caso->update([
            'estado' => IntegridadAcademicaCaso::ESTADO_RESUELTO,
            'resuelto_at' => now(),
            'resuelto_por' => auth()->id(),
            'motivo_resolucion' => trim($this->motivoResolucion),
        ]);
        $service->registrarEvento($caso, 'resuelto_manualmente', 'El caso se cerró después de una revisión manual.', auth()->id(), $anterior, $caso->only(['estado', 'resuelto_at', 'motivo_resolucion']));
        $this->motivoResolucion = '';
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Caso marcado como resuelto', 'position' => 'top-end']);
    }

    public function ignorar(IntegridadAcademicaService $service): void
    {
        $this->autorizarGestion();
        $this->validate(['motivoIgnorar' => ['required', 'string', 'min:15', 'max:2000']]);
        $caso = $this->casoSeleccionado();
        $anterior = $caso->only(['estado', 'ignorado_at', 'motivo_ignorado']);
        $caso->update([
            'estado' => IntegridadAcademicaCaso::ESTADO_IGNORADO,
            'ignorado_at' => now(),
            'ignorado_por' => auth()->id(),
            'motivo_ignorado' => trim($this->motivoIgnorar),
        ]);
        $service->registrarEvento($caso, 'ignorado_justificadamente', 'La inconsistencia se aceptó como una excepción documentada.', auth()->id(), $anterior, $caso->only(['estado', 'ignorado_at', 'motivo_ignorado']));
        $this->motivoIgnorar = '';
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Excepción documentada', 'position' => 'top-end']);
    }

    public function reabrir(IntegridadAcademicaService $service): void
    {
        $this->autorizarGestion();
        $caso = $this->casoSeleccionado();
        $anterior = $caso->only(['estado', 'resuelto_at', 'ignorado_at']);
        $caso->update([
            'estado' => IntegridadAcademicaCaso::ESTADO_PENDIENTE,
            'resuelto_at' => null,
            'resuelto_por' => null,
            'motivo_resolucion' => null,
            'ignorado_at' => null,
            'ignorado_por' => null,
            'motivo_ignorado' => null,
        ]);
        $service->registrarEvento($caso, 'reabierto_manualmente', 'El caso fue reabierto para una nueva revisión.', auth()->id(), $anterior, ['estado' => $caso->estado]);
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Caso reabierto', 'position' => 'top-end']);
    }

    public function aplicarCorreccion(IntegridadAcademicaCorreccionService $correcciones): void
    {
        $this->autorizarGestion();
        $this->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
            'confirmacionCorreccion' => ['required', 'in:CORREGIR'],
        ], [
            'confirmacionCorreccion.in' => 'Escribe CORREGIR para confirmar.',
        ]);

        try {
            $correccion = $correcciones->aplicar($this->casoSeleccionado(), $this->motivo, (int) auth()->id());
            $this->reset(['motivo', 'confirmacionCorreccion']);
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Corrección aplicada',
                'text' => 'Se creó el respaldo firmado #'.$correccion->id.'. La acción puede revertirse mientras no existan cambios posteriores.',
                'position' => 'top-end',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('motivo', $exception->getMessage());
        }
    }

    public function revertirCorreccion(int $correccionId, IntegridadAcademicaCorreccionService $correcciones): void
    {
        $this->autorizarGestion();
        $this->validate([
            'motivoReversion' => ['required', 'string', 'min:10', 'max:2000'],
            'confirmacionReversion' => ['required', 'in:REVERTIR'],
        ], [
            'confirmacionReversion.in' => 'Escribe REVERTIR para confirmar.',
        ]);

        try {
            $correccion = IntegridadAcademicaCorreccion::query()->where('caso_id', $this->casoSeleccionado()->id)->findOrFail($correccionId);
            $correcciones->revertir($correccion, $this->motivoReversion, (int) auth()->id());
            $this->reset(['motivoReversion', 'confirmacionReversion']);
            $this->dispatch('swal', ['icon' => 'success', 'title' => 'Corrección revertida', 'text' => 'Los valores anteriores fueron restaurados desde el respaldo firmado.', 'position' => 'top-end']);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('motivoReversion', $exception->getMessage());
        }
    }

    public function verTrayectoria(int $inscripcionId): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);
        $this->dispatch('abrir-linea-tiempo-academica', alumnoId: $inscripcionId);
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['buscar', 'severidad', 'categoria', 'orden']);
        $this->estado = 'abiertos';
        $this->resetPage();
    }

    public function render(IntegridadAcademicaService $service)
    {
        $query = IntegridadAcademicaCaso::query()
            ->with(['inscripcion', 'historial.cicloEscolar', 'cicloEscolar', 'nivel', 'asignado', 'ultimoAnalisis'])
            ->withCount('eventos');

        $this->aplicarFiltros($query);

        $casos = match ($this->orden) {
            'recientes' => $query->orderByDesc('ultima_deteccion_at'),
            'antiguos' => $query->orderBy('primera_deteccion_at'),
            'alumno' => $query->orderBy('inscripcion_id')->orderByDesc('ultima_deteccion_at'),
            default => $query->orderByRaw("FIELD(severidad, 'critico', 'advertencia', 'informativo')")
                ->orderByRaw("FIELD(estado, 'pendiente', 'en_revision', 'ignorado', 'resuelto')")
                ->orderByDesc('ultima_deteccion_at'),
        };

        $seleccionado = $this->casoSeleccionadoId
            ? IntegridadAcademicaCaso::query()->with([
                'inscripcion', 'historial.cicloEscolar', 'cicloEscolar', 'nivel', 'asignado',
                'eventos.usuario', 'correcciones.usuarioAplico', 'correcciones.usuarioRevirtio',
            ])->find($this->casoSeleccionadoId)
            : null;

        return view('livewire.admin.centro-integridad-academica', [
            'casos' => $casos->paginate(15),
            'resumen' => $service->resumenActual(),
            'seleccionado' => $seleccionado,
            'ultimoAnalisis' => Schema::hasTable('integridad_academica_analisis')
                ? IntegridadAcademicaAnalisis::query()->latest('iniciado_at')->first()
                : null,
            'usuarios' => User::query()->where('activo', true)->orderBy('name')->get(['id', 'name']),
            'puedeGestionar' => $this->puedeGestionar(),
            'categorias' => IntegridadAcademicaCaso::query()->select('categoria')->distinct()->orderBy('categoria')->pluck('categoria'),
        ])->layout('components.layouts.app');
    }

    private function aplicarFiltros(Builder $query): void
    {
        $query->when($this->estado === 'abiertos', fn ($q) => $q->abiertos())
            ->when($this->estado && $this->estado !== 'abiertos' && $this->estado !== 'todos', fn ($q) => $q->where('estado', $this->estado))
            ->when($this->severidad, fn ($q) => $q->where('severidad', $this->severidad))
            ->when($this->categoria, fn ($q) => $q->where('categoria', $this->categoria))
            ->when(trim($this->buscar) !== '', function ($q): void {
                $texto = '%'.trim($this->buscar).'%';
                $q->where(function ($sub) use ($texto): void {
                    $sub->where('folio', 'like', $texto)
                        ->orWhere('titulo', 'like', $texto)
                        ->orWhere('descripcion', 'like', $texto)
                        ->orWhereHas('inscripcion', function ($alumno) use ($texto): void {
                            $alumno->where('matricula', 'like', $texto)
                                ->orWhere('curp', 'like', $texto)
                                ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$texto]);
                        });
                });
            });
    }

    private function casoSeleccionado(): IntegridadAcademicaCaso
    {
        abort_unless($this->casoSeleccionadoId, 404);
        return IntegridadAcademicaCaso::query()->findOrFail($this->casoSeleccionadoId);
    }

    private function puedeGestionar(): bool
    {
        return (bool) (auth()->user()?->is_admin || auth()->user()?->canAccess('integridad.gestionar'));
    }

    private function autorizarGestion(): void
    {
        abort_unless($this->puedeGestionar(), 403);
    }
}
