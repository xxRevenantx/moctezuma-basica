<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\ProyeccionContinuidad;
use App\Services\CierreGeneracionContinuidadService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    public ?int $ciclo_corte_id = null;
    public string $fecha_corte_continuidad = '';

    public array $seleccionados = [];
    public array $datos = [];

    public bool $modalConfirmar = false;
    public bool $modalCancelar = false;
    public bool $modalRetirar = false;
    public bool $modalReactivar = false;
    public string $motivo_confirmacion = '';
    public string $fecha_confirmacion = '';
    public string $password_confirmacion_proyeccion = '';
    public string $motivo_cancelacion = '';
    public string $password_cancelacion_proyeccion = '';

    public ?int $proyeccion_retiro_id = null;
    public string $fecha_retiro = '';
    public string $motivo_retiro = '';
    public string $password_retiro_proyeccion = '';
    public bool $confirmar_excepcion_corte = false;
    public array $diagnostico_retiro = [];

    public bool $modalRetirarMasivo = false;
    public string $fecha_retiro_masivo = '';
    public string $motivo_retiro_masivo = '';
    public string $password_retiro_masivo = '';
    public bool $confirmar_excepcion_corte_masivo = false;
    public array $diagnosticos_retiro_masivo = [];
    public array $retiro_masivo_ids = [];

    public ?int $proyeccion_reactivacion_id = null;
    public string $fecha_reactivacion = '';
    public string $motivo_reactivacion = '';
    public string $password_reactivacion_proyeccion = '';
    public array $datos_reactivacion = [];
    public array $grupos_reactivacion = [];
    public string $estado_reactivacion_origen = '';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('alumnos.editar'), 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $service = app(CierreGeneracionContinuidadService::class);
        $service->sincronizarAnulacionesAdministrativas(
            $this->nivel->id,
            (int) auth()->id()
        );
        $service->sincronizarContinuidadesHistoricasSinProyeccion(
            $this->nivel->id,
            (int) auth()->id()
        );

        $this->fecha_confirmacion = now()->toDateString();
        $this->cargarCiclosDestino();
        $this->inicializarCicloCorte();
        $this->inicializarDatos();
    }

    public function updatedFiltroCicloDestinoId(): void
    {
        $this->filtro_ciclo_destino_id = filled($this->filtro_ciclo_destino_id)
            ? (int) $this->filtro_ciclo_destino_id
            : null;
        $this->seleccionados = [];
        if ($this->filtro_ciclo_destino_id) {
            $this->ciclo_corte_id = $this->filtro_ciclo_destino_id;
            $this->cargarFechaCorteContinuidad();
        }
        $this->inicializarDatos();
    }

    public function updatedFiltroEstado(): void
    {
        $this->seleccionados = [];
    }

    public function updatedCicloCorteId(): void
    {
        $this->ciclo_corte_id = filled($this->ciclo_corte_id) ? (int) $this->ciclo_corte_id : null;
        $this->cargarFechaCorteContinuidad();
    }

    public function guardarFechaCorteContinuidad(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'ciclo_corte_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'fecha_corte_continuidad' => ['required', 'date'],
        ], [
            'ciclo_corte_id.required' => 'Selecciona el ciclo escolar al que se aplicará la fecha de corte.',
            'fecha_corte_continuidad.required' => 'Captura manualmente la fecha de corte de continuidad.',
        ]);

        $ciclo = $service->guardarFechaCorteContinuidad(
            (int) $this->ciclo_corte_id,
            $this->fecha_corte_continuidad,
            (int) auth()->id(),
        );

        $this->fecha_corte_continuidad = $ciclo->fecha_corte_continuidad?->toDateString() ?? '';
        $this->cargarCiclosDestino();
        $this->resetValidation('fecha_corte_continuidad');
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Fecha de corte guardada',
            'text' => 'La fecha fue configurada manualmente para el ciclo '.$ciclo->nombre.'. No se calcula ni cambia automáticamente.',
            'position' => 'top-end',
        ]);
    }

    #[On('proyecciones-actualizadas')]
    public function recargar(): void
    {
        $service = app(CierreGeneracionContinuidadService::class);
        $service->sincronizarAnulacionesAdministrativas(
            $this->nivel->id,
            (int) auth()->id()
        );
        $service->sincronizarContinuidadesHistoricasSinProyeccion(
            $this->nivel->id,
            (int) auth()->id()
        );

        $this->cargarCiclosDestino();
        $this->inicializarCicloCorte();
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

    public function seleccionarContinuaranVisibles(): void
    {
        $this->seleccionados = $this->proyecciones
            ->where('estado', 'confirmada')
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
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

    public function prepararReactivacion(int $proyeccionId, CierreGeneracionContinuidadService $service): void
    {
        $this->resetValidation();

        $proyeccion = ProyeccionContinuidad::query()
            ->whereKey($proyeccionId)
            ->whereIn('estado', ['cancelada', 'revertida'])
            ->whereHas('inscripcionCicloOrigen', fn ($query) => $query->where('nivel_id', $this->nivel->id))
            ->firstOrFail();

        if (
            $proyeccion->estado === 'revertida'
            && $proyeccion->tipo_reversion === 'anulacion_administrativa'
        ) {
            $this->addError(
                'reactivacion_proyeccion',
                'No puede reactivarse desde este módulo porque la inscripción destino fue anulada administrativamente.'
            );

            return;
        }

        $grupos = $service->gruposParaProyeccion($proyeccion)->values();
        $grupoActual = filled($proyeccion->grupo_destino_id)
            && $grupos->contains(fn ($grupo) => (int) data_get($grupo, 'id') === (int) $proyeccion->grupo_destino_id)
                ? (int) $proyeccion->grupo_destino_id
                : null;

        if (! $grupoActual && $grupos->count() === 1) {
            $grupoActual = (int) data_get($grupos->first(), 'id');
        }

        $this->proyeccion_reactivacion_id = $proyeccion->id;
        $this->fecha_reactivacion = now()->toDateString();
        $this->motivo_reactivacion = 'La familia confirmó que el alumno sí continuará en la institución durante el ciclo escolar destino.';
        $this->password_reactivacion_proyeccion = '';
        $this->datos_reactivacion = [
            'grupo_destino_id' => $grupoActual,
            'matricula' => (string) ($proyeccion->matricula_sugerida ?? ''),
        ];
        $this->grupos_reactivacion = $grupos->all();
        $this->estado_reactivacion_origen = (string) $proyeccion->estado;
        $this->modalReactivar = true;
    }

    public function reactivarComoContinuara(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'proyeccion_reactivacion_id' => ['required', 'integer', 'min:1'],
            'fecha_reactivacion' => ['required', 'date'],
            'motivo_reactivacion' => ['required', 'string', 'min:10', 'max:1500'],
            'password_reactivacion_proyeccion' => ['required', 'string'],
            'datos_reactivacion.grupo_destino_id' => ['required', 'integer', 'min:1'],
            'datos_reactivacion.matricula' => ['nullable', 'string', 'max:80'],
        ]);

        if (! Hash::check($this->password_reactivacion_proyeccion, (string) auth()->user()?->password)) {
            $this->addError('password_reactivacion_proyeccion', 'La contraseña no es correcta.');
            return;
        }

        $proyeccion = $service->reactivarProyeccionNoContinuara(
            (int) $this->proyeccion_reactivacion_id,
            $this->datos_reactivacion,
            trim($this->motivo_reactivacion),
            $this->fecha_reactivacion,
            (int) auth()->id(),
        );

        $this->modalReactivar = false;
        $this->filtro_estado = 'confirmada';
        $this->resetOperacion();
        $this->inicializarDatos();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Continuidad reactivada',
            'text' => 'El alumno quedó nuevamente marcado como Continuará y activo en el ciclo destino.',
            'position' => 'top-end',
        ]);
    }

    public function prepararRetiro(int $proyeccionId, CierreGeneracionContinuidadService $service): void
    {
        $this->resetValidation();
        $this->proyeccion_retiro_id = $proyeccionId;
        $this->fecha_retiro = now()->toDateString();
        $this->motivo_retiro = 'La familia confirmó que el alumno no continuará en la institución dentro del periodo administrativo de corrección de continuidad.';
        $this->password_retiro_proyeccion = '';
        $this->confirmar_excepcion_corte = false;
        $this->diagnostico_retiro = $service->diagnosticoRetiroProyeccion($proyeccionId);
        $this->modalRetirar = true;
    }

    public function retirarDelCicloDestino(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'proyeccion_retiro_id' => ['required', 'integer', 'min:1'],
            'fecha_retiro' => ['required', 'date', 'before_or_equal:today'],
            'motivo_retiro' => ['required', 'string', 'min:10', 'max:1500'],
            'password_retiro_proyeccion' => ['required', 'string'],
            'confirmar_excepcion_corte' => ['boolean'],
        ]);

        if (! Hash::check($this->password_retiro_proyeccion, (string) auth()->user()?->password)) {
            $this->addError('password_retiro_proyeccion', 'La contraseña no es correcta.');
            return;
        }

        $this->diagnostico_retiro = $service->diagnosticoRetiroProyeccion((int) $this->proyeccion_retiro_id);
        if (data_get($this->diagnostico_retiro, 'requiere_excepcion', false) && ! $this->confirmar_excepcion_corte) {
            $this->addError(
                'confirmar_excepcion_corte',
                'La fecha de corte ya venció. Debes autorizar expresamente la excepción administrativa para continuar.'
            );
            return;
        }

        $proyeccion = $service->retirarProyeccionConfirmada(
            (int) $this->proyeccion_retiro_id,
            trim($this->motivo_retiro),
            $this->fecha_retiro,
            (int) auth()->id(),
            $this->confirmar_excepcion_corte,
        );

        $estatus = $proyeccion->inscripcion?->estatus === 'egresado'
            ? 'egresado del nivel de origen'
            : 'no reinscrito en el último grado concluido';
        $fueExcepcion = (bool) data_get($proyeccion->snapshot_reversion, 'excepcion_fecha_corte', false);

        $this->modalRetirar = false;
        $this->filtro_estado = 'revertida';
        $this->resetOperacion();
        $this->inicializarDatos();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $fueExcepcion ? 'Excepción registrada' : 'Marcado como No continuará',
            'text' => ($fueExcepcion ? 'La excepción posterior al corte quedó auditada. ' : '')
                ."La activación fue anulada sin borrar el historial. El alumno quedó {$estatus}.",
            'position' => 'top-end',
        ]);
    }

    public function prepararRetiroMasivo(CierreGeneracionContinuidadService $service): void
    {
        $this->resetValidation();
        $ids = $this->proyeccionesSeleccionadasConfirmadas()->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        if ($ids === []) {
            $this->addError('seleccion_proyecciones', 'Selecciona al menos un alumno marcado como Continuará.');
            return;
        }

        $this->diagnosticos_retiro_masivo = $service->diagnosticosRetiroProyecciones($ids);
        $this->retiro_masivo_ids = collect($this->diagnosticos_retiro_masivo)
            ->filter(fn (array $diagnostico): bool => (bool) ($diagnostico['puede_retirar_con_excepcion'] ?? false))
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
        $this->fecha_retiro_masivo = now()->toDateString();
        $this->motivo_retiro_masivo = 'La familia confirmó que los alumnos seleccionados no continuarán en la institución dentro del periodo administrativo de corrección de continuidad.';
        $this->password_retiro_masivo = '';
        $this->confirmar_excepcion_corte_masivo = false;
        $this->modalRetirarMasivo = true;
    }

    public function retirarSeleccionadasDelCicloDestino(CierreGeneracionContinuidadService $service): void
    {
        $this->validate([
            'fecha_retiro_masivo' => ['required', 'date', 'before_or_equal:today'],
            'motivo_retiro_masivo' => ['required', 'string', 'min:10', 'max:1500'],
            'password_retiro_masivo' => ['required', 'string'],
            'confirmar_excepcion_corte_masivo' => ['boolean'],
        ]);

        if (! Hash::check($this->password_retiro_masivo, (string) auth()->user()?->password)) {
            $this->addError('password_retiro_masivo', 'La contraseña no es correcta.');
            return;
        }

        $idsSeleccionados = $this->proyeccionesSeleccionadasConfirmadas()->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        if ($idsSeleccionados === []) {
            $this->addError('retiro_masivo', 'Ya no hay alumnos confirmados en la selección. Recarga la lista e inténtalo de nuevo.');
            return;
        }

        $this->diagnosticos_retiro_masivo = $service->diagnosticosRetiroProyecciones($idsSeleccionados);
        $procesables = collect($this->diagnosticos_retiro_masivo)
            ->filter(fn (array $diagnostico): bool => (bool) ($diagnostico['puede_retirar_con_excepcion'] ?? false));
        $requiereExcepcion = $procesables->contains(fn (array $diagnostico): bool => (bool) ($diagnostico['requiere_excepcion'] ?? false));

        if ($procesables->isEmpty()) {
            $this->addError('retiro_masivo', 'Ningún alumno seleccionado puede cambiarse a No continuará. Revisa los bloqueos mostrados.');
            return;
        }

        if ($requiereExcepcion && ! $this->confirmar_excepcion_corte_masivo) {
            $this->addError(
                'confirmar_excepcion_corte_masivo',
                'Hay alumnos cuya fecha de corte venció. Autoriza expresamente la excepción administrativa para procesarlos.'
            );
            return;
        }

        $idsProcesables = $procesables->keys()->map(fn ($id): int => (int) $id)->values()->all();
        $cantidad = $service->retirarProyeccionesConfirmadas(
            $idsProcesables,
            trim($this->motivo_retiro_masivo),
            $this->fecha_retiro_masivo,
            (int) auth()->id(),
            $this->confirmar_excepcion_corte_masivo,
        );
        $omitidos = max(0, count($idsSeleccionados) - $cantidad);

        $this->modalRetirarMasivo = false;
        $this->filtro_estado = 'revertida';
        $this->resetOperacion();
        $this->inicializarDatos();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Cambios de continuidad aplicados',
            'text' => "{$cantidad} alumno(s) fueron marcados como No continuará."
                .($omitidos > 0 ? " {$omitidos} quedaron sin cambios por bloqueos detectados." : ''),
            'position' => 'top-end',
        ]);
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
        $service = app(CierreGeneracionContinuidadService::class);
        $filtrosDiagnostico = ['cambiables', 'bloqueados', 'excepciones'];

        if (! in_array($this->filtro_estado, $filtrosDiagnostico, true)) {
            return $service->proyeccionesPorNivelOrigen(
                $this->nivel->id,
                $this->filtro_ciclo_destino_id,
                filled($this->filtro_estado) ? $this->filtro_estado : null,
                $this->buscar,
            );
        }

        return $service->proyeccionesPorNivelOrigen(
            $this->nivel->id,
            $this->filtro_ciclo_destino_id,
            'confirmada',
            $this->buscar,
        )->filter(function (ProyeccionContinuidad $proyeccion) use ($service): bool {
            $diagnostico = $service->diagnosticoRetiroProyeccion((int) $proyeccion->id);

            return match ($this->filtro_estado) {
                'cambiables' => (bool) ($diagnostico['puede_retirar_normal'] ?? false),
                'excepciones' => (bool) ($diagnostico['requiere_excepcion'] ?? false)
                    && (bool) ($diagnostico['puede_retirar_con_excepcion'] ?? false),
                'bloqueados' => ! (bool) ($diagnostico['puede_retirar_con_excepcion'] ?? false),
                default => true,
            };
        })->values();
    }

    public function getResumenFechaCorteProperty(): array
    {
        $ciclo = $this->ciclo_corte_id ? CicloEscolar::query()->find($this->ciclo_corte_id) : null;

        if (! $ciclo) {
            return ['estado' => 'sin_ciclo', 'ciclo' => null, 'fecha' => null, 'fecha_texto' => null, 'dias_restantes' => null];
        }

        if (! Schema::hasColumn('ciclo_escolares', 'fecha_corte_continuidad')) {
            return ['estado' => 'sin_migracion', 'ciclo' => $ciclo->nombre, 'fecha' => null, 'fecha_texto' => null, 'dias_restantes' => null];
        }

        if (! $ciclo->fecha_corte_continuidad) {
            return ['estado' => 'sin_configurar', 'ciclo' => $ciclo->nombre, 'fecha' => null, 'fecha_texto' => null, 'dias_restantes' => null];
        }

        $fecha = CarbonImmutable::parse($ciclo->fecha_corte_continuidad)->startOfDay();
        $hoy = CarbonImmutable::today();
        $estado = $hoy->isAfter($fecha) ? 'vencido' : ($hoy->isSameDay($fecha) ? 'hoy' : 'abierto');

        return [
            'estado' => $estado,
            'ciclo' => $ciclo->nombre,
            'fecha' => $fecha->toDateString(),
            'fecha_texto' => $fecha->format('d/m/Y'),
            'dias_restantes' => $hoy->diffInDays($fecha, false),
            'configurada_at' => $ciclo->fecha_corte_continuidad_at?->format('d/m/Y H:i'),
        ];
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
            'revertida' => (int) ($conteos['revertida'] ?? 0),
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

    private function proyeccionesSeleccionadasConfirmadas(): Collection
    {
        $ids = collect($this->seleccionados)->map(fn ($id): int => (int) $id)->filter()->unique();

        return ProyeccionContinuidad::query()
            ->whereIn('id', $ids)
            ->where('estado', 'confirmada')
            ->whereHas('inscripcionCicloOrigen', fn ($query) => $query->where('nivel_id', $this->nivel->id))
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
                $grupoActual = (int) data_get($grupos->first(), 'id');
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

    private function inicializarCicloCorte(): void
    {
        if ($this->ciclosDestino->isEmpty()) {
            $this->ciclo_corte_id = null;
            $this->fecha_corte_continuidad = '';
            return;
        }

        if (! $this->ciclo_corte_id || ! $this->ciclosDestino->contains('id', $this->ciclo_corte_id)) {
            $this->ciclo_corte_id = (int) ($this->ciclosDestino->firstWhere('es_actual', true)?->id
                ?? $this->ciclosDestino->first()?->id);
        }

        $this->cargarFechaCorteContinuidad();
    }

    private function cargarFechaCorteContinuidad(): void
    {
        if (! $this->ciclo_corte_id || ! Schema::hasColumn('ciclo_escolares', 'fecha_corte_continuidad')) {
            $this->fecha_corte_continuidad = '';
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_corte_id);
        $this->fecha_corte_continuidad = $ciclo?->fecha_corte_continuidad?->toDateString() ?? '';
    }

    private function resetOperacion(): void
    {
        $this->seleccionados = [];
        $this->motivo_confirmacion = '';
        $this->password_confirmacion_proyeccion = '';
        $this->motivo_cancelacion = '';
        $this->password_cancelacion_proyeccion = '';
        $this->proyeccion_retiro_id = null;
        $this->fecha_retiro = '';
        $this->motivo_retiro = '';
        $this->password_retiro_proyeccion = '';
        $this->confirmar_excepcion_corte = false;
        $this->diagnostico_retiro = [];
        $this->fecha_retiro_masivo = '';
        $this->motivo_retiro_masivo = '';
        $this->password_retiro_masivo = '';
        $this->confirmar_excepcion_corte_masivo = false;
        $this->diagnosticos_retiro_masivo = [];
        $this->retiro_masivo_ids = [];
        $this->proyeccion_reactivacion_id = null;
        $this->fecha_reactivacion = '';
        $this->motivo_reactivacion = '';
        $this->password_reactivacion_proyeccion = '';
        $this->datos_reactivacion = [];
        $this->grupos_reactivacion = [];
        $this->estado_reactivacion_origen = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.accion.generales.proyecciones-continuidad');
    }
}
