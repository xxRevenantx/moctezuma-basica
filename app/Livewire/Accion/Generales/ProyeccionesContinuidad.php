<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\ProyeccionContinuidad;
use App\Services\CierreGeneracionContinuidadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

class ProyeccionesContinuidad extends Component
{
    private const MOTIVO_CANCELACION_PREDETERMINADO =
        'La familia confirmó que el alumno no continuará en la institución durante el ciclo escolar destino.';

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $ciclosDestino;

    public string $buscar = '';
    public string $filtro_estado = 'pendiente';
    public ?int $filtro_ciclo_destino_id = null;

    public array $seleccionados = [];
    public array $datos = [];

    public bool $modalConfirmar = false;
    public bool $modalCancelar = false;
    public string $motivo_confirmacion = '';
    public string $fecha_confirmacion = '';
    public string $password_confirmacion_proyeccion = '';
    public string $motivo_cancelacion = '';
    public string $password_cancelacion_proyeccion = '';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->fecha_confirmacion = now()->toDateString();
        $this->cargarCiclosDestino();
        $this->inicializarDatos();
    }

    public function updatedFiltroCicloDestinoId(): void
    {
        $this->filtro_ciclo_destino_id = filled($this->filtro_ciclo_destino_id)
            ? (int) $this->filtro_ciclo_destino_id
            : null;
        $this->seleccionados = [];
        $this->inicializarDatos();
    }

    public function updatedFiltroEstado(): void
    {
        $this->seleccionados = [];
    }

    #[On('proyecciones-actualizadas')]
    public function recargar(): void
    {
        $this->cargarCiclosDestino();
        $this->inicializarDatos();
    }

    public function seleccionarPendientesVisibles(): void
    {
        $this->seleccionados = $this->proyecciones
            ->where('estado', 'pendiente')
            ->pluck('id')
            ->map(fn($id): string => (string) $id)
            ->all();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function confirmarUna(int $proyeccionId): void
    {
        $this->seleccionados = [(string) $proyeccionId];
        $this->prepararConfirmacion();
    }

    public function cancelarUna(int $proyeccionId): void
    {
        $this->seleccionados = [(string) $proyeccionId];
        $this->prepararCancelacion();
    }

    public function prepararConfirmacion(): void
    {
        $pendientes = $this->proyeccionesSeleccionadasPendientes();
        if ($pendientes->isEmpty()) {
            $this->addError('seleccion_proyecciones', 'Selecciona al menos una proyección pendiente.');
            return;
        }

        foreach ($pendientes as $proyeccion) {
            $grupoId = (int) ($this->datos[$proyeccion->id]['grupo_destino_id'] ?? $proyeccion->grupo_destino_id ?? 0);
            if ($grupoId <= 0) {
                $this->addError(
                    "datos.{$proyeccion->id}.grupo_destino_id",
                    'Selecciona el grupo destino antes de confirmar.'
                );
                return;
            }
        }

        $this->motivo_confirmacion = 'Confirmación de reinscripción o continuidad en el ciclo escolar destino.';
        $this->fecha_confirmacion = now()->toDateString();
        $this->password_confirmacion_proyeccion = '';
        $this->modalConfirmar = true;
        $this->resetValidation();
    }

    public function confirmarSeleccionadas(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'seleccionados' => ['required', 'array', 'min:1'],
            'fecha_confirmacion' => ['required', 'date'],
            'motivo_confirmacion' => ['required', 'string', 'min:10', 'max:1500'],
            'password_confirmacion_proyeccion' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password_confirmacion_proyeccion, (string) auth()->user()?->password)) {
            $this->addError('password_confirmacion_proyeccion', 'La contraseña no es correcta.');
            return;
        }

        $cantidad = $service->confirmarProyecciones(
            $this->seleccionados,
            $this->datos,
            trim($this->motivo_confirmacion),
            $this->fecha_confirmacion,
            (int) auth()->id(),
        );

        $this->modalConfirmar = false;
        $this->resetOperacion();
        $this->inicializarDatos();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Proyección confirmada',
            'text' => "{$cantidad} alumno(s) quedaron activos en la ubicación académica del ciclo destino.",
            'position' => 'top-end',
        ]);
    }

    public function prepararCancelacion(): void
    {
        if ($this->proyeccionesSeleccionadasPendientes()->isEmpty()) {
            $this->addError('seleccion_proyecciones', 'Selecciona al menos una proyección pendiente.');
            return;
        }

        $this->motivo_cancelacion = self::MOTIVO_CANCELACION_PREDETERMINADO;
        $this->password_cancelacion_proyeccion = '';
        $this->modalCancelar = true;
        $this->resetValidation();
    }

    public function cancelarSeleccionadas(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'seleccionados' => ['required', 'array', 'min:1'],
            'motivo_cancelacion' => ['required', 'string', 'min:10', 'max:1500'],
            'password_cancelacion_proyeccion' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password_cancelacion_proyeccion, (string) auth()->user()?->password)) {
            $this->addError('password_cancelacion_proyeccion', 'La contraseña no es correcta.');
            return;
        }

        $cantidad = $service->cancelarProyecciones(
            $this->seleccionados,
            trim($this->motivo_cancelacion),
            (int) auth()->id(),
        );

        $this->modalCancelar = false;
        $this->resetOperacion();
        $this->inicializarDatos();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Proyección cancelada',
            'text' => "{$cantidad} proyección(es) fueron canceladas. Se conservó el resultado académico del ciclo de origen y no se registró una baja en el destino.",
            'position' => 'top-end',
        ]);
    }

    public function getProyeccionesProperty(): Collection
    {
        return app(CierreGeneracionContinuidadService::class)->proyeccionesPorNivelOrigen(
            $this->nivel->id,
            $this->filtro_ciclo_destino_id,
            filled($this->filtro_estado) ? $this->filtro_estado : null,
            $this->buscar,
        );
    }

    public function getConteosProperty(): array
    {
        $conteos = ProyeccionContinuidad::query()
            ->whereHas('inscripcionCicloOrigen', fn($query) => $query->where('nivel_id', $this->nivel->id))
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'pendiente' => (int) ($conteos['pendiente'] ?? 0),
            'confirmada' => (int) ($conteos['confirmada'] ?? 0),
            'cancelada' => (int) ($conteos['cancelada'] ?? 0),
        ];
    }

    public function getGruposDisponiblesProperty(): array
    {
        $service = app(CierreGeneracionContinuidadService::class);
        $resultado = [];

        foreach ($this->proyecciones->where('estado', 'pendiente') as $proyeccion) {
            $resultado[$proyeccion->id] = $service->gruposParaProyeccion($proyeccion)->all();
        }

        return $resultado;
    }

    private function proyeccionesSeleccionadasPendientes(): Collection
    {
        $ids = collect($this->seleccionados)->map(fn($id): int => (int) $id)->filter()->unique();

        return ProyeccionContinuidad::query()
            ->whereIn('id', $ids)
            ->where('estado', 'pendiente')
            ->get();
    }

    private function inicializarDatos(): void
    {
        $pendientes = app(CierreGeneracionContinuidadService::class)->proyeccionesPorNivelOrigen(
            $this->nivel->id,
            $this->filtro_ciclo_destino_id,
            'pendiente',
            '',
        );

        $service = app(CierreGeneracionContinuidadService::class);

        foreach ($pendientes as $proyeccion) {
            $grupos = $service->gruposParaProyeccion($proyeccion);
            $grupoActual = $this->datos[$proyeccion->id]['grupo_destino_id']
                ?? $proyeccion->grupo_destino_id;

            if (blank($grupoActual) && $grupos->count() === 1) {
                $grupoActual = (int) $grupos->first()->id;
            }

            $this->datos[$proyeccion->id] = [
                'grupo_destino_id' => filled($grupoActual) ? (int) $grupoActual : null,
                'matricula' => $this->datos[$proyeccion->id]['matricula']
                    ?? $proyeccion->matricula_sugerida
                    ?? '',
            ];
        }
    }

    private function cargarCiclosDestino(): void
    {
        $ids = ProyeccionContinuidad::query()
            ->whereHas('inscripcionCicloOrigen', fn($query) => $query->where('nivel_id', $this->nivel->id))
            ->pluck('ciclo_destino_id')
            ->unique()
            ->values();

        $this->ciclosDestino = CicloEscolar::query()
            ->whereIn('id', $ids)
            ->orderByDesc('inicio_anio')
            ->get();
    }

    private function resetOperacion(): void
    {
        $this->seleccionados = [];
        $this->motivo_confirmacion = '';
        $this->password_confirmacion_proyeccion = '';
        $this->motivo_cancelacion = '';
        $this->password_cancelacion_proyeccion = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.accion.generales.proyecciones-continuidad');
    }
}
