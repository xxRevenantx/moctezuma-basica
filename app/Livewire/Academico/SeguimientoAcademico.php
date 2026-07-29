<?php

namespace App\Livewire\Academico;

use App\Models\AlertaAcademica;
use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\RiesgoAcademicoConfiguracion;
use App\Models\RiesgoAcademicoEvaluacion;
use App\Models\RiesgoAcademicoRegla;
use App\Models\SeguimientoAcademicoAccion;
use App\Models\SeguimientoAcademicoCaso;
use App\Models\User;
use App\Services\RiesgoAcademicoService;
use App\Services\SeguimientoAcademicoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SeguimientoAcademico extends Component
{
    use WithPagination;

    public Collection $ciclos;
    public Collection $niveles;
    public Collection $usuarios;

    public string $buscar = '';
    public string $ciclo_escolar_id = '';
    public string $nivel_id = '';
    public string $nivel_riesgo = 'todos';
    public string $estado_seguimiento = 'todos';
    public string $responsable_id = '';
    public string $orden = 'riesgo_desc';

    public bool $modalDetalle = false;
    public bool $modalSeguimiento = false;
    public bool $modalReglas = false;
    public bool $modalEditarRegla = false;
    public ?int $evaluacionSeleccionadaId = null;
    public ?int $casoSeleccionadoId = null;

    public string $motivo_apertura = '';
    public string $resumen_caso = '';
    public string $prioridad_caso = 'alta';
    public string $responsable_caso_id = '';
    public string $proxima_revision_at = '';

    public string $plan_nombre = '';
    public string $plan_objetivo = '';
    public string $plan_fecha_fin = '';
    public string $plan_responsable_id = '';

    public string $accion_tipo = 'regularizacion';
    public string $accion_descripcion = '';
    public string $accion_fecha_limite = '';
    public string $accion_responsable_id = '';
    public string $accion_plan_id = '';
    public array $acciones_estado = [];
    public array $acciones_resultado = [];
    public array $acciones_evidencia = [];

    public string $nota_titulo = '';
    public string $nota_descripcion = '';
    public string $motivo_cierre = '';
    public string $motivo_reapertura = '';

    public ?int $reglaEditandoId = null;
    public string $regla_nombre = '';
    public bool $regla_activa = true;
    public string $regla_peso = '10';
    public string $regla_max_puntos = '20';
    public string $regla_parametros_json = '{}';
    public string $umbral_moderado = '20';
    public string $umbral_alto = '40';
    public string $umbral_critico = '70';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('seguimiento.consultar'), 403);

        $this->ciclos = CicloEscolar::query()->orderByDesc('es_actual')->orderByDesc('inicio_anio')->get();
        $this->niveles = Nivel::query()->orderBy('id')->get();
        $this->usuarios = User::query()->where('activo', true)->orderBy('name')->get(['id', 'name']);
        $this->ciclo_escolar_id = (string) ($this->ciclos->firstWhere('es_actual', true)?->id ?? '');
        $this->cargarUmbrales();
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['buscar', 'ciclo_escolar_id', 'nivel_id', 'nivel_riesgo', 'estado_seguimiento', 'responsable_id', 'orden'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->nivel_id = '';
        $this->nivel_riesgo = 'todos';
        $this->estado_seguimiento = 'todos';
        $this->responsable_id = '';
        $this->orden = 'riesgo_desc';
        $this->resetPage();
    }

    public function evaluarAhora(RiesgoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $resultado = $service->evaluarLote([
            'ciclo_escolar_id' => $this->ciclo_escolar_id !== '' ? (int) $this->ciclo_escolar_id : null,
            'nivel_id' => $this->nivel_id !== '' ? (int) $this->nivel_id : null,
        ], (int) auth()->id(), true);

        $this->dispatch('notify', type: $resultado['errores'] ? 'warning' : 'success', message: "Evaluados: {$resultado['evaluados']} · Casos nuevos: {$resultado['casos_abiertos']} · Alertas: {$resultado['alertas']}");
    }

    public function verEvaluacion(int $evaluacionId): void
    {
        $this->evaluacionSeleccionadaId = $evaluacionId;
        $evaluacion = $this->evaluacionSeleccionada;
        $this->casoSeleccionadoId = $evaluacion?->casos?->first()?->id
            ?? SeguimientoAcademicoCaso::query()->where('inscripcion_ciclo_id', $evaluacion?->inscripcion_ciclo_id)->latest('id')->value('id');
        $this->cargarFormularioCaso();
        $this->modalDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->modalDetalle = false;
    }

    public function abrirFormularioSeguimiento(): void
    {
        $this->autorizarGestion();
        $evaluacion = $this->evaluacionSeleccionada;
        if (! $evaluacion) {
            return;
        }
        $this->motivo_apertura = 'Atender los factores detectados por el semáforo de riesgo académico.';
        $this->resumen_caso = collect($evaluacion->factores ?? [])->pluck('detalle')->filter()->implode(' · ');
        $this->prioridad_caso = $evaluacion->nivel_riesgo === 'critico' ? 'critica' : 'alta';
        $this->responsable_caso_id = '';
        $this->proxima_revision_at = now()->addDays(14)->toDateString();
        $this->modalSeguimiento = true;
    }

    public function guardarSeguimiento(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $datos = $this->validate([
            'motivo_apertura' => ['required', 'string', 'min:10', 'max:2000'],
            'resumen_caso' => ['nullable', 'string', 'max:3000'],
            'prioridad_caso' => ['required', Rule::in(['moderada', 'alta', 'critica'])],
            'responsable_caso_id' => ['nullable', 'exists:users,id'],
            'proxima_revision_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $evaluacion = $this->evaluacionSeleccionada;
        abort_unless($evaluacion, 404);
        $caso = $service->abrirDesdeEvaluacion($evaluacion, [
            'motivo_apertura' => $datos['motivo_apertura'],
            'resumen' => $datos['resumen_caso'] ?: null,
            'prioridad' => $datos['prioridad_caso'],
            'responsable_id' => $datos['responsable_caso_id'] !== '' ? (int) $datos['responsable_caso_id'] : null,
            'proxima_revision_at' => $datos['proxima_revision_at'] ?: null,
        ], (int) auth()->id());

        $this->casoSeleccionadoId = $caso->id;
        $this->modalSeguimiento = false;
        $this->cargarFormularioCaso();
        $this->dispatch('notify', type: 'success', message: 'Seguimiento académico abierto correctamente.');
    }

    public function guardarDatosCaso(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $caso = $this->casoSeleccionado;
        abort_unless($caso, 404);
        $datos = $this->validate([
            'resumen_caso' => ['nullable', 'string', 'max:3000'],
            'prioridad_caso' => ['required', Rule::in(['moderada', 'alta', 'critica'])],
            'responsable_caso_id' => ['nullable', 'exists:users,id'],
            'proxima_revision_at' => ['nullable', 'date'],
        ]);
        $service->actualizarCaso($caso, [
            'resumen' => $datos['resumen_caso'] ?: null,
            'prioridad' => $datos['prioridad_caso'],
            'responsable_id' => $datos['responsable_caso_id'] !== '' ? (int) $datos['responsable_caso_id'] : null,
            'proxima_revision_at' => $datos['proxima_revision_at'] ?: null,
        ], (int) auth()->id());
        $this->dispatch('notify', type: 'success', message: 'Datos del seguimiento actualizados.');
    }

    public function crearPlan(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $caso = $this->casoSeleccionado;
        abort_unless($caso, 404);
        $datos = $this->validate([
            'plan_nombre' => ['required', 'string', 'min:4', 'max:255'],
            'plan_objetivo' => ['required', 'string', 'min:10', 'max:3000'],
            'plan_fecha_fin' => ['nullable', 'date', 'after_or_equal:today'],
            'plan_responsable_id' => ['nullable', 'exists:users,id'],
        ]);
        $service->crearPlan($caso, [
            'nombre' => $datos['plan_nombre'],
            'objetivo' => $datos['plan_objetivo'],
            'fecha_inicio' => today(),
            'fecha_fin_prevista' => $datos['plan_fecha_fin'] ?: null,
            'responsable_id' => $datos['plan_responsable_id'] !== '' ? (int) $datos['plan_responsable_id'] : null,
        ], (int) auth()->id());
        $this->reset(['plan_nombre', 'plan_objetivo', 'plan_fecha_fin', 'plan_responsable_id']);
        $this->dispatch('notify', type: 'success', message: 'Plan de intervención creado.');
    }

    public function crearAccion(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $caso = $this->casoSeleccionado;
        abort_unless($caso, 404);
        $datos = $this->validate([
            'accion_tipo' => ['required', Rule::in(['regularizacion', 'tutoria', 'asistencia', 'orientacion', 'contacto_tutor', 'documentacion', 'otra'])],
            'accion_descripcion' => ['required', 'string', 'min:8', 'max:2000'],
            'accion_fecha_limite' => ['nullable', 'date'],
            'accion_responsable_id' => ['nullable', 'exists:users,id'],
            'accion_plan_id' => ['nullable', 'exists:seguimiento_academico_planes,id'],
        ]);
        $service->crearAccion($caso, [
            'plan_id' => $datos['accion_plan_id'] !== '' ? (int) $datos['accion_plan_id'] : null,
            'tipo' => $datos['accion_tipo'],
            'descripcion' => $datos['accion_descripcion'],
            'responsable_id' => $datos['accion_responsable_id'] !== '' ? (int) $datos['accion_responsable_id'] : null,
            'fecha_limite' => $datos['accion_fecha_limite'] ?: null,
        ], (int) auth()->id());
        $this->reset(['accion_descripcion', 'accion_fecha_limite', 'accion_responsable_id', 'accion_plan_id']);
        $this->accion_tipo = 'regularizacion';
        $this->dispatch('notify', type: 'success', message: 'Acción de intervención agregada.');
    }

    public function guardarAccion(int $accionId, SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $accion = SeguimientoAcademicoAccion::query()->findOrFail($accionId);
        abort_unless((int) $accion->seguimiento_caso_id === (int) $this->casoSeleccionadoId, 403);
        $estado = $this->acciones_estado[$accionId] ?? $accion->estado;
        $service->actualizarAccion($accion, [
            'estado' => $estado,
            'resultado' => $this->acciones_resultado[$accionId] ?? null,
            'evidencia' => $this->acciones_evidencia[$accionId] ?? null,
        ], (int) auth()->id());
        $this->dispatch('notify', type: 'success', message: 'Acción actualizada.');
    }

    public function registrarNota(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $caso = $this->casoSeleccionado;
        abort_unless($caso, 404);
        $datos = $this->validate([
            'nota_titulo' => ['required', 'string', 'min:4', 'max:255'],
            'nota_descripcion' => ['required', 'string', 'min:8', 'max:3000'],
        ]);
        $service->registrarNota($caso, $datos['nota_titulo'], $datos['nota_descripcion'], (int) auth()->id());
        $this->reset(['nota_titulo', 'nota_descripcion']);
        $this->dispatch('notify', type: 'success', message: 'Nota registrada en la evolución del alumno.');
    }

    public function cerrarCaso(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $this->validate(['motivo_cierre' => ['required', 'string', 'min:10', 'max:2000']]);
        try {
            $service->cerrarCaso($this->casoSeleccionado, $this->motivo_cierre, (int) auth()->id());
            $this->motivo_cierre = '';
            $this->dispatch('notify', type: 'success', message: 'Seguimiento cerrado.');
        } catch (\DomainException $e) {
            $this->addError('motivo_cierre', $e->getMessage());
        }
    }

    public function reabrirCaso(SeguimientoAcademicoService $service): void
    {
        $this->autorizarGestion();
        $this->validate(['motivo_reapertura' => ['required', 'string', 'min:10', 'max:2000']]);
        $service->reabrirCaso($this->casoSeleccionado, $this->motivo_reapertura, (int) auth()->id());
        $this->motivo_reapertura = '';
        $this->dispatch('notify', type: 'success', message: 'Seguimiento reabierto.');
    }

    public function atenderAlerta(int $alertaId): void
    {
        $this->autorizarGestion();
        AlertaAcademica::query()->whereKey($alertaId)->update([
            'estado' => 'atendida', 'atendida_at' => now(), 'atendida_por' => auth()->id(),
        ]);
    }

    public function abrirReglas(): void
    {
        $this->autorizarGestion();
        $this->cargarUmbrales();
        $this->modalReglas = true;
    }

    public function editarRegla(int $reglaId): void
    {
        $this->autorizarGestion();
        $regla = RiesgoAcademicoRegla::query()->findOrFail($reglaId);
        $this->reglaEditandoId = $regla->id;
        $this->regla_nombre = $regla->nombre;
        $this->regla_activa = $regla->activo;
        $this->regla_peso = (string) $regla->peso;
        $this->regla_max_puntos = (string) $regla->max_puntos;
        $this->regla_parametros_json = json_encode($regla->parametros ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $this->modalEditarRegla = true;
    }

    public function guardarRegla(): void
    {
        $this->autorizarGestion();
        $datos = $this->validate([
            'regla_nombre' => ['required', 'string', 'max:255'],
            'regla_activa' => ['boolean'],
            'regla_peso' => ['required', 'numeric', 'min:0', 'max:100'],
            'regla_max_puntos' => ['required', 'numeric', 'min:0', 'max:100'],
            'regla_parametros_json' => ['required', 'json'],
        ]);
        RiesgoAcademicoRegla::query()->whereKey($this->reglaEditandoId)->update([
            'nombre' => $datos['regla_nombre'],
            'activo' => $datos['regla_activa'],
            'peso' => $datos['regla_peso'],
            'max_puntos' => $datos['regla_max_puntos'],
            'parametros' => json_decode($datos['regla_parametros_json'], true),
            'actualizado_por' => auth()->id(),
            'updated_at' => now(),
        ]);
        $this->modalEditarRegla = false;
        $this->dispatch('notify', type: 'success', message: 'Regla de riesgo actualizada. Ejecuta una nueva evaluación para aplicar el cambio.');
    }

    public function guardarUmbrales(): void
    {
        $this->autorizarGestion();
        $datos = $this->validate([
            'umbral_moderado' => ['required', 'integer', 'min:1', 'max:99'],
            'umbral_alto' => ['required', 'integer', 'gt:umbral_moderado', 'max:99'],
            'umbral_critico' => ['required', 'integer', 'gt:umbral_alto', 'max:100'],
        ]);
        RiesgoAcademicoConfiguracion::query()->updateOrCreate(['clave' => 'umbrales'], [
            'valor' => ['moderado' => (int) $datos['umbral_moderado'], 'alto' => (int) $datos['umbral_alto'], 'critico' => (int) $datos['umbral_critico']],
            'descripcion' => 'Puntajes mínimos para clasificar el semáforo de riesgo.',
            'actualizado_por' => auth()->id(),
        ]);
        $this->dispatch('notify', type: 'success', message: 'Umbrales del semáforo actualizados.');
    }

    public function verTrayectoria(): void
    {
        $evaluacion = $this->evaluacionSeleccionada;
        if ($evaluacion) {
            $this->dispatch('abrir-linea-tiempo-academica', alumnoId: $evaluacion->inscripcion_id);
        }
    }

    public function getEvaluacionSeleccionadaProperty(): ?RiesgoAcademicoEvaluacion
    {
        if (! $this->evaluacionSeleccionadaId) {
            return null;
        }
        return RiesgoAcademicoEvaluacion::query()->with([
            'inscripcion', 'nivel', 'grado', 'grupo.asignacionGrupo', 'generacion', 'semestre', 'cicloEscolar',
            'casos' => fn ($q) => $q->latest('id'),
        ])->find($this->evaluacionSeleccionadaId);
    }

    public function getEvolucionSeleccionadaProperty(): Collection
    {
        $evaluacion = $this->evaluacionSeleccionada;
        if (! $evaluacion) {
            return collect();
        }

        return RiesgoAcademicoEvaluacion::query()
            ->where('inscripcion_ciclo_id', $evaluacion->inscripcion_ciclo_id)
            ->orderBy('evaluado_at')
            ->orderBy('id')
            ->get();
    }

    public function getCasoSeleccionadoProperty(): ?SeguimientoAcademicoCaso
    {
        if (! $this->casoSeleccionadoId) {
            return null;
        }
        return SeguimientoAcademicoCaso::query()->with([
            'inscripcion', 'responsable', 'planes.responsable', 'acciones.responsable', 'acciones.plan',
            'eventos.usuario', 'alertas' => fn ($q) => $q->latest('generada_at'),
        ])->find($this->casoSeleccionadoId);
    }

    public function getResumenProperty(): array
    {
        $base = RiesgoAcademicoEvaluacion::query()->actuales()
            ->when($this->ciclo_escolar_id !== '', fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->nivel_id !== '', fn (Builder $q) => $q->where('nivel_id', $this->nivel_id));

        $conteos = (clone $base)->selectRaw('nivel_riesgo, COUNT(*) total')->groupBy('nivel_riesgo')->pluck('total', 'nivel_riesgo');

        return [
            'bajo' => (int) ($conteos['bajo'] ?? 0),
            'moderado' => (int) ($conteos['moderado'] ?? 0),
            'alto' => (int) ($conteos['alto'] ?? 0),
            'critico' => (int) ($conteos['critico'] ?? 0),
            'casos_abiertos' => SeguimientoAcademicoCaso::query()->activos()->when($this->ciclo_escolar_id !== '', fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))->count(),
            'acciones_vencidas' => SeguimientoAcademicoAccion::query()->whereIn('estado', ['pendiente', 'en_proceso'])->whereDate('fecha_limite', '<', today())->count(),
            'alertas' => AlertaAcademica::query()->where('estado', 'pendiente')->count(),
        ];
    }

    public function getReglasProperty(): Collection
    {
        return RiesgoAcademicoRegla::query()->with('nivel')->orderBy('orden')->get();
    }

    private function cargarFormularioCaso(): void
    {
        $caso = $this->casoSeleccionado;
        if (! $caso) {
            return;
        }
        $this->resumen_caso = (string) $caso->resumen;
        $this->prioridad_caso = (string) $caso->prioridad;
        $this->responsable_caso_id = (string) ($caso->responsable_id ?? '');
        $this->proxima_revision_at = $caso->proxima_revision_at?->toDateString() ?? '';
        foreach ($caso->acciones as $accion) {
            $this->acciones_estado[$accion->id] = $accion->estado;
            $this->acciones_resultado[$accion->id] = (string) $accion->resultado;
            $this->acciones_evidencia[$accion->id] = (string) $accion->evidencia;
        }
    }

    private function cargarUmbrales(): void
    {
        $umbrales = RiesgoAcademicoConfiguracion::query()->where('clave', 'umbrales')->value('valor') ?? [];
        if (is_string($umbrales)) {
            $umbrales = json_decode($umbrales, true) ?: [];
        }
        $this->umbral_moderado = (string) ($umbrales['moderado'] ?? 20);
        $this->umbral_alto = (string) ($umbrales['alto'] ?? 40);
        $this->umbral_critico = (string) ($umbrales['critico'] ?? 70);
    }

    private function autorizarGestion(): void
    {
        abort_unless(auth()->user()?->canAccess('seguimiento.gestionar'), 403);
    }

    public function render()
    {
        $query = RiesgoAcademicoEvaluacion::query()->actuales()->with([
            'inscripcion', 'nivel', 'grado', 'grupo.asignacionGrupo', 'semestre', 'cicloEscolar',
            'casos' => fn ($q) => $q->latest('id'),
        ])
            ->when($this->buscar !== '', function (Builder $q): void {
                $termino = '%'.trim($this->buscar).'%';
                $q->whereHas('inscripcion', function (Builder $i) use ($termino): void {
                    $i->where('nombre', 'like', $termino)
                        ->orWhere('apellido_paterno', 'like', $termino)
                        ->orWhere('apellido_materno', 'like', $termino)
                        ->orWhere('matricula', 'like', $termino)
                        ->orWhere('curp', 'like', $termino);
                });
            })
            ->when($this->ciclo_escolar_id !== '', fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->nivel_id !== '', fn (Builder $q) => $q->where('nivel_id', $this->nivel_id))
            ->when($this->nivel_riesgo !== 'todos', fn (Builder $q) => $q->where('nivel_riesgo', $this->nivel_riesgo))
            ->when($this->estado_seguimiento === 'sin_caso', fn (Builder $q) => $q->whereDoesntHave('casos', fn (Builder $c) => $c->activos()))
            ->when($this->estado_seguimiento !== 'todos' && $this->estado_seguimiento !== 'sin_caso', fn (Builder $q) => $q->whereHas('casos', fn (Builder $c) => $c->where('estado', $this->estado_seguimiento)))
            ->when($this->responsable_id !== '', fn (Builder $q) => $q->whereHas('casos', fn (Builder $c) => $c->where('responsable_id', $this->responsable_id)));

        match ($this->orden) {
            'nombre' => $query->join('inscripciones as ri', 'ri.id', '=', 'riesgo_academico_evaluaciones.inscripcion_id')->orderBy('ri.apellido_paterno')->orderBy('ri.apellido_materno')->orderBy('ri.nombre')->select('riesgo_academico_evaluaciones.*'),
            'reciente' => $query->orderByDesc('evaluado_at'),
            'riesgo_asc' => $query->orderByRaw("FIELD(nivel_riesgo, 'bajo','moderado','alto','critico')")->orderBy('puntaje'),
            default => $query->orderByRaw("FIELD(nivel_riesgo, 'critico','alto','moderado','bajo')")->orderByDesc('puntaje'),
        };

        return view('livewire.academico.seguimiento-academico', [
            'evaluaciones' => $query->paginate(20),
            'resumen' => $this->resumen,
            'evaluacionSeleccionada' => $this->evaluacionSeleccionada,
            'casoSeleccionado' => $this->casoSeleccionado,
            'evolucion' => $this->evolucionSeleccionada,
            'reglas' => $this->reglas,
            'puedeGestionar' => (bool) auth()->user()?->canAccess('seguimiento.gestionar'),
        ])->layout('components.layouts.app');
    }
}
