<?php

namespace App\Livewire\Academico;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Hora;
use App\Models\HorarioAsignacionRegla;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\HorarioDocenteDisponibilidad;
use App\Models\HorarioDocenteExcepcion;
use App\Models\HorarioRegla;
use App\Models\HorarioVersion;
use App\Models\HorarioVersionDetalle;
use App\Models\Nivel;
use App\Models\Persona;
use App\Services\HorarioOptimizadorService;
use App\Services\HorarioVersionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PlanificadorHorarios extends Component
{
    public string $slugNivel;
    public ?int $cicloEscolarId = null;
    public ?int $nivelId = null;

    public string $seccion = 'propuestas';
    public bool $conservarHorarioActual = true;

    public ?int $profesorId = null;
    public array $disponibilidad = [];
    public int $maxGruposSimultaneos = 2;
    public int $maxHorasDiarias = 6;
    public int $maxHorasConsecutivas = 3;
    public int $minDescansoBloques = 0;
    public int $maxHuecosDiarios = 2;
    public ?int $primeraHoraId = null;
    public ?int $ultimaHoraId = null;
    public bool $permitirMultigrado = true;
    public bool $permitirMateriasDistintas = false;
    public bool $requiereMotivoTraslape = true;

    public string $excepcionFecha = '';
    public string $excepcionHoraId = '';
    public string $excepcionEstado = 'no_disponible';
    public string $excepcionMotivo = '';

    public array $reglasActivas = [];
    public array $reglasPesos = [];
    public array $cargas = [];

    public ?int $versionSeleccionadaId = null;
    public string $editorGrupoId = '';
    public ?int $detalleIntercambioId = null;

    public bool $modalCoensenanza = false;
    public ?int $coensenanzaDetalleId = null;
    public string $coensenanzaProfesorId = '';
    public string $coensenanzaMotivo = '';

    public bool $modalCambiarProfesor = false;
    public ?int $cambioProfesorDetalleId = null;
    public string $cambioProfesorId = '';
    public string $cambioProfesorMotivo = '';

    public bool $modalClasificar = false;
    public string $clasificarTipo = 'compartida';
    public string $clasificarMotivo = '';
    public ?int $clasificarProfesorId = null;
    public ?int $clasificarDiaId = null;
    public ?int $clasificarHoraId = null;

    public string $publicacionMotivo = '';
    public string $publicacionFecha = '';
    public string $publicacionPassword = '';
    public string $publicacionConfirmacion = '';
    public bool $aceptarAdvertencias = false;

    public string $mensajeMotor = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('horarios.consultar'), 403);

        $nivel = Nivel::query()->where('slug', $this->slugNivel)->firstOrFail();
        $this->nivelId = (int) $nivel->id;
        $this->cicloEscolarId ??= CicloEscolar::query()->where('es_actual', true)->value('id')
            ?? CicloEscolar::query()->orderByDesc('inicio_anio')->value('id');
        $this->publicacionFecha = now()->format('Y-m-d\TH:i');
        $this->excepcionFecha = today()->toDateString();

        $this->recargarContexto();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->versionSeleccionadaId = null;
        $this->profesorId = null;
        $this->editorGrupoId = '';
        $this->recargarContexto();
    }


    public function updatedProfesorId(): void
    {
        $this->cargarDisponibilidad();
    }

    public function updatedVersionSeleccionadaId(): void
    {
        if ($this->versionSeleccionadaId) {
            $this->seleccionarVersion((int) $this->versionSeleccionadaId);
        }
    }

    public function cambiarSeccion(string $seccion): void
    {
        abort_unless(in_array($seccion, ['disponibilidad', 'reglas', 'propuestas', 'editor', 'versiones'], true), 422);
        $this->seccion = $seccion;
    }

    public function seleccionarProfesor(?int $profesorId): void
    {
        $this->profesorId = $profesorId;
        $this->cargarDisponibilidad();
    }

    public function ciclarDisponibilidad(int $diaId, int $horaId): void
    {
        $this->autorizarEdicion();
        $clave = $diaId.'-'.$horaId;
        $estados = ['disponible', 'preferido', 'autorizacion', 'no_disponible'];
        $actual = $this->disponibilidad[$clave] ?? 'disponible';
        $indice = array_search($actual, $estados, true);
        $this->disponibilidad[$clave] = $estados[(($indice === false ? 0 : $indice) + 1) % count($estados)];
    }

    public function guardarDisponibilidad(): void
    {
        $this->autorizarEdicion();
        $this->validate([
            'profesorId' => ['required', 'exists:personas,id'],
            'maxGruposSimultaneos' => ['required', 'integer', 'min:1', 'max:6'],
            'maxHorasDiarias' => ['required', 'integer', 'min:1', 'max:20'],
            'maxHorasConsecutivas' => ['required', 'integer', 'min:1', 'max:12'],
            'minDescansoBloques' => ['required', 'integer', 'min:0', 'max:6'],
            'maxHuecosDiarios' => ['required', 'integer', 'min:0', 'max:12'],
            'primeraHoraId' => ['nullable', 'exists:horas,id'],
            'ultimaHoraId' => ['nullable', 'exists:horas,id'],
        ]);

        $config = HorarioDocenteConfiguracion::query()->updateOrCreate([
            'persona_id' => $this->profesorId,
            'ciclo_escolar_id' => $this->cicloEscolarId,
            'nivel_id' => $this->nivelId,
        ], [
            'max_grupos_simultaneos' => $this->maxGruposSimultaneos,
            'max_horas_diarias' => $this->maxHorasDiarias,
            'max_horas_consecutivas' => $this->maxHorasConsecutivas,
            'min_descanso_bloques' => $this->minDescansoBloques,
            'max_huecos_diarios' => $this->maxHuecosDiarios,
            'primera_hora_id' => $this->primeraHoraId,
            'ultima_hora_id' => $this->ultimaHoraId,
            'permitir_multigrado' => $this->permitirMultigrado,
            'permitir_materias_distintas' => $this->permitirMateriasDistintas,
            'requiere_motivo_traslape' => $this->requiereMotivoTraslape,
            'activo' => true,
            'actualizado_por' => auth()->id(),
        ]);

        $dias = Dia::query()->where('nivel_id', $this->nivelId)->pluck('id');
        $horas = Hora::query()->where('nivel_id', $this->nivelId)->pluck('id');
        foreach ($dias as $diaId) {
            foreach ($horas as $horaId) {
                $estado = $this->disponibilidad[$diaId.'-'.$horaId] ?? 'disponible';
                if ($estado === 'disponible') {
                    HorarioDocenteDisponibilidad::query()
                        ->where('configuracion_id', $config->id)
                        ->where('dia_id', $diaId)
                        ->where('hora_id', $horaId)
                        ->delete();
                    continue;
                }
                HorarioDocenteDisponibilidad::query()->updateOrCreate([
                    'configuracion_id' => $config->id,
                    'dia_id' => $diaId,
                    'hora_id' => $horaId,
                ], ['estado' => $estado]);
            }
        }

        $this->dispatch('notify', type: 'success', message: 'Disponibilidad y límites del docente guardados.');
    }

    public function crearExcepcion(): void
    {
        $this->autorizarEdicion();
        $datos = $this->validate([
            'profesorId' => ['required', 'exists:personas,id'],
            'excepcionFecha' => ['required', 'date'],
            'excepcionHoraId' => ['nullable', 'exists:horas,id'],
            'excepcionEstado' => ['required', Rule::in(['preferido', 'disponible', 'autorizacion', 'no_disponible'])],
            'excepcionMotivo' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        HorarioDocenteExcepcion::query()->create([
            'persona_id' => $this->profesorId,
            'ciclo_escolar_id' => $this->cicloEscolarId,
            'nivel_id' => $this->nivelId,
            'fecha' => $datos['excepcionFecha'],
            'hora_id' => $datos['excepcionHoraId'] !== '' ? (int) $datos['excepcionHoraId'] : null,
            'estado' => $datos['excepcionEstado'],
            'motivo' => $datos['excepcionMotivo'],
            'registrado_por' => auth()->id(),
        ]);
        $this->reset(['excepcionHoraId', 'excepcionMotivo']);
        $this->excepcionEstado = 'no_disponible';
        $this->dispatch('notify', type: 'success', message: 'Excepción de disponibilidad registrada.');
    }

    public function eliminarExcepcion(int $id): void
    {
        $this->autorizarEdicion();
        HorarioDocenteExcepcion::query()
            ->where('persona_id', $this->profesorId)
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->findOrFail($id)
            ->delete();
    }

    public function guardarReglasGlobales(): void
    {
        $this->autorizarEdicion();
        foreach (HorarioRegla::query()->get() as $regla) {
            $regla->update([
                'activa' => (bool) ($this->reglasActivas[$regla->id] ?? false),
                'peso' => max(0, min(100, (int) ($this->reglasPesos[$regla->id] ?? $regla->peso))),
                'actualizado_por' => auth()->id(),
            ]);
        }
        $this->dispatch('notify', type: 'success', message: 'Reglas globales actualizadas.');
    }

    public function guardarCargas(): void
    {
        $this->autorizarEdicion();
        foreach ($this->cargas as $asignacionId => $datos) {
            HorarioAsignacionRegla::query()->updateOrCreate(
                ['asignacion_materia_id' => (int) $asignacionId],
                [
                    'sesiones_semanales' => max(1, min(20, (int) ($datos['sesiones'] ?? 1))),
                    'max_sesiones_dia' => max(1, min(10, (int) ($datos['max_dia'] ?? 1))),
                    'permitir_bloques_consecutivos' => (bool) ($datos['consecutivos'] ?? false),
                    'max_bloques_consecutivos' => max(1, min(6, (int) ($datos['max_consecutivos'] ?? 2))),
                    'dias_minimos' => max(1, min(6, (int) ($datos['dias_minimos'] ?? 1))),
                    'preferencia_horaria' => in_array(($datos['preferencia'] ?? ''), ['cualquiera', 'primeras', 'ultimas'], true) ? $datos['preferencia'] : 'cualquiera',
                    'permitir_multigrado' => (bool) ($datos['multigrado'] ?? true),
                    'bloqueada' => (bool) ($datos['bloqueada'] ?? false),
                    'actualizado_por' => auth()->id(),
                ]
            );
        }
        $this->dispatch('notify', type: 'success', message: 'Cargas semanales y preferencias guardadas.');
    }

    public function verificarMotor(HorarioOptimizadorService $service): void
    {
        $estado = $service->disponibilidadMotor();
        $this->mensajeMotor = $estado['mensaje'];
        $this->dispatch('notify', type: $estado['ok'] ? 'success' : 'warning', message: $estado['mensaje']);
    }

    public function generarPropuestas(HorarioOptimizadorService $service): void
    {
        $this->autorizarEdicion();
        try {
            $resultado = $service->generarPropuestas(
                (int) $this->cicloEscolarId,
                (int) $this->nivelId,
                $this->conservarHorarioActual,
                (int) auth()->id()
            );
            $this->versionSeleccionadaId = collect($resultado['propuestas'])->last()?->id;
            $this->seccion = 'propuestas';
            $mensaje = 'Se generaron '.count($resultado['propuestas']).' propuestas con '.$resultado['engine'].'.';
            if ($resultado['advertencias'] !== []) {
                $mensaje .= ' Se utilizó el generador de respaldo porque OR-Tools no estuvo disponible.';
            }
            $this->dispatch('notify', type: $resultado['advertencias'] === [] ? 'success' : 'warning', message: $mensaje);
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());
        }
    }

    public function crearBorradorActual(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $version = $service->crearBorradorDesdeHorarioActual(
            (int) $this->cicloEscolarId,
            (int) $this->nivelId,
            (int) auth()->id()
        );
        $this->seleccionarVersion($version->id);
        $this->seccion = 'editor';
    }

    public function seleccionarVersion(int $versionId): void
    {
        $version = HorarioVersion::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->findOrFail($versionId);
        $this->versionSeleccionadaId = $version->id;
        $primerGrupo = $version->detalles()->orderBy('grupo_id')->value('grupo_id');
        $this->editorGrupoId = $primerGrupo ? (string) $primerGrupo : '';
        $this->detalleIntercambioId = null;
    }

    public function abrirVersionEditor(int $versionId): void
    {
        $this->seleccionarVersion($versionId);
        $this->seccion = 'editor';
    }

    public function convertirEnBorrador(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $version = $this->versionSeleccionada;
        abort_unless($version, 404);
        $service->convertirPropuestaEnBorrador($version, (int) auth()->id());
        $this->seccion = 'editor';
    }

    public function moverDetalle(int $detalleId, int $diaId, int $horaId, HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        try {
            $detalle = HorarioVersionDetalle::query()->where('horario_version_id', $this->versionSeleccionadaId)->findOrFail($detalleId);
            $service->moverDetalle($detalle, $diaId, $horaId, (int) auth()->id());
            $this->dispatch('notify', type: 'success', message: 'Clase movida. El diagnóstico fue recalculado.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function seleccionarIntercambio(int $detalleId, HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        if (! $this->detalleIntercambioId) {
            $this->detalleIntercambioId = $detalleId;
            $this->dispatch('notify', type: 'info', message: 'Selecciona la segunda clase para intercambiar posiciones.');
            return;
        }
        if ($this->detalleIntercambioId === $detalleId) {
            $this->detalleIntercambioId = null;
            return;
        }
        try {
            $primero = HorarioVersionDetalle::query()->where('horario_version_id', $this->versionSeleccionadaId)->findOrFail($this->detalleIntercambioId);
            $segundo = HorarioVersionDetalle::query()->where('horario_version_id', $this->versionSeleccionadaId)->findOrFail($detalleId);
            $service->intercambiarDetalles($primero, $segundo, (int) auth()->id());
            $this->detalleIntercambioId = null;
            $this->dispatch('notify', type: 'success', message: 'Clases intercambiadas.');
        } catch (\Throwable $exception) {
            $this->detalleIntercambioId = null;
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function alternarBloqueo(int $detalleId, HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        try {
            $detalle = HorarioVersionDetalle::query()->where('horario_version_id', $this->versionSeleccionadaId)->findOrFail($detalleId);
            $service->alternarBloqueo($detalle, (int) auth()->id());
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function alternarBloqueoDia(int $diaId, HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $version = $this->versionSeleccionada;
        abort_unless($version, 404);
        $detallesDia = $version->detalles->where('dia_id', $diaId);
        $bloquear = ! ($detallesDia->isNotEmpty() && $detallesDia->every(fn ($detalle) => $detalle->bloqueado));
        $service->alternarBloqueoDia($version, $diaId, $bloquear, (int) auth()->id());
        $this->dispatch('notify', type: 'success', message: $bloquear ? 'Día fijado para la regeneración.' : 'Día liberado.');
    }

    public function regenerarNoBloqueados(HorarioOptimizadorService $service): void
    {
        $this->autorizarEdicion();
        $version = $this->versionSeleccionada;
        abort_unless($version, 404);
        try {
            $resultado = $service->regenerarNoBloqueados($version, (int) auth()->id());
            $nueva = collect($resultado['propuestas'])->last();
            if ($nueva) {
                $this->seleccionarVersion($nueva->id);
                $this->seccion = 'propuestas';
            }
            $this->dispatch('notify', type: $resultado['advertencias'] === [] ? 'success' : 'warning', message: 'Se regeneraron '.count($resultado['propuestas']).' propuestas conservando los bloques fijados.');
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function abrirCambioProfesor(int $detalleId): void
    {
        $this->autorizarEdicion();
        $detalle = HorarioVersionDetalle::query()
            ->where('horario_version_id', $this->versionSeleccionadaId)
            ->findOrFail($detalleId);
        $this->cambioProfesorDetalleId = $detalle->id;
        $this->cambioProfesorId = (string) ($detalle->profesor_id ?? '');
        $this->cambioProfesorMotivo = 'Ajuste de docente dentro del borrador del horario.';
        $this->modalCambiarProfesor = true;
    }

    public function guardarCambioProfesor(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $datos = $this->validate([
            'cambioProfesorId' => ['nullable', 'exists:personas,id'],
            'cambioProfesorMotivo' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        try {
            $detalle = HorarioVersionDetalle::query()
                ->where('horario_version_id', $this->versionSeleccionadaId)
                ->findOrFail($this->cambioProfesorDetalleId);
            $service->cambiarProfesorDetalle(
                $detalle,
                $datos['cambioProfesorId'] !== '' ? (int) $datos['cambioProfesorId'] : null,
                $datos['cambioProfesorMotivo'],
                (int) auth()->id()
            );
            $this->modalCambiarProfesor = false;
            $this->dispatch('notify', type: 'success', message: 'Docente del bloque actualizado y validado.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function abrirCoensenanza(int $detalleId): void
    {
        $this->autorizarEdicion();
        $this->coensenanzaDetalleId = $detalleId;
        $this->coensenanzaProfesorId = '';
        $this->coensenanzaMotivo = 'Sesión colaborativa con docente de apoyo para el mismo grupo.';
        $this->modalCoensenanza = true;
    }

    public function guardarCoensenanza(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $datos = $this->validate([
            'coensenanzaProfesorId' => ['required', 'exists:personas,id'],
            'coensenanzaMotivo' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        try {
            $detalle = HorarioVersionDetalle::query()
                ->where('horario_version_id', $this->versionSeleccionadaId)
                ->findOrFail($this->coensenanzaDetalleId);
            $service->agregarCoensenanza(
                $detalle,
                (int) $datos['coensenanzaProfesorId'],
                $datos['coensenanzaMotivo'],
                (int) auth()->id()
            );
            $this->modalCoensenanza = false;
            $this->dispatch('notify', type: 'success', message: 'Docente de apoyo agregado como coenseñanza.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function abrirClasificacion(int $profesorId, int $diaId, int $horaId): void
    {
        $this->autorizarEdicion();
        $this->clasificarProfesorId = $profesorId;
        $this->clasificarDiaId = $diaId;
        $this->clasificarHoraId = $horaId;
        $this->clasificarTipo = 'compartida';
        $this->clasificarMotivo = 'Sesión compartida entre varios grados o grupos.';
        $this->modalClasificar = true;
    }

    public function guardarClasificacion(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $this->validate([
            'clasificarTipo' => ['required', Rule::in(['compartida', 'excepcional'])],
            'clasificarMotivo' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        try {
            $service->clasificarSimultaneidad(
                $this->versionSeleccionada,
                (int) $this->clasificarProfesorId,
                (int) $this->clasificarDiaId,
                (int) $this->clasificarHoraId,
                $this->clasificarTipo,
                $this->clasificarMotivo,
                (int) auth()->id()
            );
            $this->modalClasificar = false;
            $this->dispatch('notify', type: 'success', message: 'Simultaneidad clasificada y auditada.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function recalcularDiagnostico(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        if ($this->versionSeleccionada) {
            $service->actualizarDiagnostico($this->versionSeleccionada);
        }
    }

    public function enviarRevision(HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        try {
            $service->solicitarRevision($this->versionSeleccionada, (int) auth()->id());
            $this->dispatch('notify', type: 'success', message: 'Versión enviada a revisión.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function publicarVersion(HorarioVersionService $service): void
    {
        $this->autorizarPublicacion();
        $datos = $this->validate([
            'publicacionMotivo' => ['required', 'string', 'min:10', 'max:2000'],
            'publicacionFecha' => ['required', 'date'],
            'publicacionPassword' => ['required', 'string'],
            'publicacionConfirmacion' => ['required', 'in:PUBLICAR'],
            'aceptarAdvertencias' => ['boolean'],
        ]);
        if (! Hash::check($datos['publicacionPassword'], (string) auth()->user()->password)) {
            $this->addError('publicacionPassword', 'La contraseña no es correcta.');
            return;
        }
        try {
            $fecha = Carbon::parse($datos['publicacionFecha']);
            $version = $service->programarPublicacion(
                $this->versionSeleccionada,
                $fecha,
                $datos['publicacionMotivo'],
                (int) auth()->id(),
                (bool) $datos['aceptarAdvertencias']
            );
            $this->publicacionPassword = '';
            $this->publicacionConfirmacion = '';
            $this->dispatch('notify', type: 'success', message: $version->estado === 'publicada' ? 'Horario publicado correctamente.' : 'Publicación programada.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'warning', message: $exception->getMessage());
        }
    }

    public function crearRestauracion(int $versionId, HorarioVersionService $service): void
    {
        $this->autorizarEdicion();
        $origen = HorarioVersion::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->findOrFail($versionId);
        $version = $service->crearRestauracion($origen, (int) auth()->id());
        $this->seleccionarVersion($version->id);
        $this->seccion = 'editor';
        $this->dispatch('notify', type: 'success', message: 'Se creó un borrador de restauración; la versión publicada no fue sobrescrita.');
    }

    public function getVersionSeleccionadaProperty(): ?HorarioVersion
    {
        if (! $this->versionSeleccionadaId) {
            return null;
        }
        return HorarioVersion::query()->with([
            'detalles.dia', 'detalles.hora', 'detalles.grado', 'detalles.semestre',
            'detalles.grupo.asignacionGrupo', 'detalles.asignacionMateria.materia',
            'detalles.profesor', 'eventos.usuario',
        ])->find($this->versionSeleccionadaId);
    }

    public function getGruposEditorProperty(): Collection
    {
        $version = $this->versionSeleccionada;
        if (! $version) {
            return collect();
        }
        return $version->detalles->pluck('grupo')->filter()->unique('id')->sortBy(fn ($grupo) => ($grupo->grado?->nombre ?? '').($grupo->asignacionGrupo?->nombre ?? ''))->values();
    }

    public function getDetallesEditorProperty(): Collection
    {
        $version = $this->versionSeleccionada;
        if (! $version) {
            return collect();
        }
        return $version->detalles
            ->when($this->editorGrupoId !== '', fn (Collection $items) => $items->where('grupo_id', (int) $this->editorGrupoId))
            ->values();
    }

    public function getSimultaneidadesEditorProperty(): Collection
    {
        $version = $this->versionSeleccionada;
        if (! $version) {
            return collect();
        }
        return $version->detalles->whereNotNull('profesor_id')
            ->groupBy(fn ($detalle) => $detalle->profesor_id.'-'.$detalle->dia_id.'-'.$detalle->hora_id)
            ->filter(fn (Collection $items) => $items->pluck('grupo_id')->unique()->count() > 1)
            ->map(function (Collection $items): array {
                $primero = $items->first();
                return [
                    'profesor_id' => $primero->profesor_id,
                    'dia_id' => $primero->dia_id,
                    'hora_id' => $primero->hora_id,
                    'docente' => $this->nombrePersona($primero->profesor),
                    'dia' => $primero->dia?->dia,
                    'hora' => ($primero->hora?->hora_inicio ?? '').' - '.($primero->hora?->hora_fin ?? ''),
                    'grupos' => $items->map(fn ($detalle) => trim(($detalle->grado?->nombre ?? '').' '.($detalle->grupo?->asignacionGrupo?->nombre ?? '')))->implode(', '),
                    'clasificada' => $items->every(fn ($detalle) => $detalle->sesion_compartida || $detalle->traslape_excepcional),
                ];
            })->values();
    }

    private function recargarContexto(): void
    {
        $this->cargarReglas();
        $this->cargarCargas();
        $this->cargarProfesores();
        $version = HorarioVersion::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->orderByRaw("FIELD(estado, 'borrador','propuesta','en_revision','programada','publicada','sustituida','archivada')")
            ->orderByDesc('numero')
            ->first();
        if ($version) {
            $this->seleccionarVersion($version->id);
        }
    }

    private function cargarProfesores(): void
    {
        $ids = AsignacionMateria::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->configurables()
            ->whereNotNull('profesor_id')
            ->pluck('profesor_id')->unique();
        $this->profesorId = $this->profesorId && $ids->contains($this->profesorId) ? $this->profesorId : $ids->first();
        $this->cargarDisponibilidad();
    }

    private function cargarDisponibilidad(): void
    {
        $this->disponibilidad = [];
        $config = null;
        if ($this->profesorId) {
            $config = HorarioDocenteConfiguracion::query()->with('disponibilidades')
                ->where('persona_id', $this->profesorId)
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('nivel_id', $this->nivelId)
                ->first();
        }
        $this->maxGruposSimultaneos = (int) ($config?->max_grupos_simultaneos ?? 2);
        $this->maxHorasDiarias = (int) ($config?->max_horas_diarias ?? 6);
        $this->maxHorasConsecutivas = (int) ($config?->max_horas_consecutivas ?? 3);
        $this->minDescansoBloques = (int) ($config?->min_descanso_bloques ?? 0);
        $this->maxHuecosDiarios = (int) ($config?->max_huecos_diarios ?? 2);
        $this->primeraHoraId = $config?->primera_hora_id;
        $this->ultimaHoraId = $config?->ultima_hora_id;
        $this->permitirMultigrado = (bool) ($config?->permitir_multigrado ?? true);
        $this->permitirMateriasDistintas = (bool) ($config?->permitir_materias_distintas ?? false);
        $this->requiereMotivoTraslape = (bool) ($config?->requiere_motivo_traslape ?? true);
        foreach ($config?->disponibilidades ?? [] as $disp) {
            $this->disponibilidad[$disp->dia_id.'-'.$disp->hora_id] = $disp->estado;
        }
    }

    private function cargarReglas(): void
    {
        foreach (HorarioRegla::query()->orderBy('orden')->get() as $regla) {
            $this->reglasActivas[$regla->id] = (bool) $regla->activa;
            $this->reglasPesos[$regla->id] = (int) $regla->peso;
        }
    }

    private function cargarCargas(): void
    {
        $asignaciones = AsignacionMateria::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->configurables()
            ->withCount('horarios')
            ->get();
        foreach ($asignaciones as $asignacion) {
            $regla = HorarioAsignacionRegla::query()->firstOrCreate(
                ['asignacion_materia_id' => $asignacion->id],
                [
                    'sesiones_semanales' => max(1, (int) $asignacion->horarios_count),
                    'max_sesiones_dia' => 1,
                    'permitir_bloques_consecutivos' => false,
                    'max_bloques_consecutivos' => 2,
                    'dias_minimos' => max(1, min(5, (int) $asignacion->horarios_count)),
                    'preferencia_horaria' => 'cualquiera',
                    'permitir_multigrado' => true,
                    'bloqueada' => false,
                ]
            );
            $this->cargas[$asignacion->id] = [
                'sesiones' => (int) $regla->sesiones_semanales,
                'max_dia' => (int) $regla->max_sesiones_dia,
                'consecutivos' => (bool) $regla->permitir_bloques_consecutivos,
                'max_consecutivos' => (int) $regla->max_bloques_consecutivos,
                'dias_minimos' => (int) $regla->dias_minimos,
                'preferencia' => $regla->preferencia_horaria,
                'multigrado' => (bool) $regla->permitir_multigrado,
                'bloqueada' => (bool) $regla->bloqueada,
            ];
        }
    }

    private function autorizarEdicion(): void
    {
        abort_unless(auth()->user()?->canAccess('horarios.editar'), 403);
    }

    private function autorizarPublicacion(): void
    {
        abort_unless(auth()->user()?->canAccess('horarios.publicar'), 403);
    }

    private function nombrePersona(?Persona $persona): string
    {
        return $persona
            ? trim(collect([$persona->nombre, $persona->apellido_paterno, $persona->apellido_materno])->filter()->implode(' '))
            : 'Sin docente';
    }

    public function render()
    {
        $ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get();
        $dias = Dia::query()->where('nivel_id', $this->nivelId)->orderBy('orden')->get();
        $horas = Hora::query()->where('nivel_id', $this->nivelId)->orderBy('orden')->get();
        $profesores = Persona::query()
            ->whereIn('id', AsignacionMateria::query()
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('nivel_id', $this->nivelId)
                ->whereNotNull('profesor_id')
                ->pluck('profesor_id'))
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')->get();
        $asignaciones = AsignacionMateria::query()->with(['materia', 'grupo.grado', 'grupo.asignacionGrupo', 'profesor'])
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->configurables()
            ->orderBy('grupo_id')->orderBy('orden')->get();
        $reglas = HorarioRegla::query()->orderBy('orden')->get();
        $versiones = HorarioVersion::query()->withCount('detalles')
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->orderByDesc('numero')->get();
        $excepciones = $this->profesorId
            ? HorarioDocenteExcepcion::query()->with('hora')->where('persona_id', $this->profesorId)
                ->where('ciclo_escolar_id', $this->cicloEscolarId)->where('nivel_id', $this->nivelId)
                ->orderByDesc('fecha')->limit(20)->get()
            : collect();

        return view('livewire.academico.planificador-horarios', [
            'ciclos' => $ciclos,
            'dias' => $dias,
            'horas' => $horas,
            'profesores' => $profesores,
            'asignaciones' => $asignaciones,
            'reglas' => $reglas,
            'versiones' => $versiones,
            'excepciones' => $excepciones,
            'versionSeleccionada' => $this->versionSeleccionada,
            'gruposEditor' => $this->gruposEditor,
            'detallesEditor' => $this->detallesEditor,
            'simultaneidadesEditor' => $this->simultaneidadesEditor,
            'puedeEditar' => (bool) auth()->user()?->canAccess('horarios.editar'),
            'puedePublicar' => (bool) auth()->user()?->canAccess('horarios.publicar'),
        ]);
    }
}
