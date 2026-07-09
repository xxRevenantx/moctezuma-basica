<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use App\Models\TrayectoriaAcademica;
use App\Support\CalificacionBachillerato;
use App\Support\ReglasMateriaBachillerato;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreNivelReingresoService
{
    public const RESULTADOS_CIERRE = [
        'continua_institucion',
        'egresa_otra_escuela',
        'egresa_sin_destino',
        'traslado',
        'baja_definitiva',
        'repite',
    ];

    public function procesarCierre(
        TrayectoriaAcademica $origen,
        string $resultado,
        array $datos,
        ?int $usuarioId = null
    ): ?TrayectoriaAcademica {
        if (!in_array($resultado, self::RESULTADOS_CIERRE, true)) {
            throw ValidationException::withMessages(['resultado' => 'El resultado de cierre no es válido.']);
        }

        return DB::transaction(function () use ($origen, $resultado, $datos, $usuarioId) {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para registrar el movimiento.');

            $fecha = Carbon::parse($datos['fecha'] ?? now());
            $origen = TrayectoriaAcademica::query()->lockForUpdate()->findOrFail($origen->id);
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($origen->inscripcion_id);

            if (!$origen->activo || in_array($origen->estatus, ['egresado', 'traslado', 'baja_definitiva', 'archivado'], true)) {
                throw ValidationException::withMessages([
                    'seleccionados' => $alumno->matricula . ': la trayectoria ya fue cerrada.',
                ]);
            }

            if (in_array($resultado, ['continua_institucion', 'egresa_otra_escuela', 'egresa_sin_destino', 'repite'], true)
                && !$this->esUltimaEtapaDelNivel($origen)) {
                throw ValidationException::withMessages([
                    'seleccionados' => $alumno->matricula . ': esta decisión corresponde únicamente al último grado o semestre del nivel.',
                ]);
            }

            return match ($resultado) {
                'continua_institucion' => $this->continuarEnInstitucion($alumno, $origen, $datos, $fecha, $usuarioId),
                'egresa_otra_escuela' => $this->cerrarComoEgresado($alumno, $origen, 'otra_escuela', $datos, $fecha, $usuarioId),
                'egresa_sin_destino' => $this->cerrarComoEgresado($alumno, $origen, 'sin_destino', $datos, $fecha, $usuarioId),
                'traslado' => $this->cerrarComoSalida($alumno, $origen, 'traslado', $datos, $fecha, $usuarioId),
                'baja_definitiva' => $this->cerrarComoSalida($alumno, $origen, 'baja_definitiva', $datos, $fecha, $usuarioId),
                'repite' => $this->repetirGrado($alumno, $origen, $datos, $fecha, $usuarioId),
            };
        });
    }

    public function reingresarExalumno(
        Inscripcion $alumno,
        string $tipo,
        array $destino,
        array $procedencia,
        ?int $usuarioId = null
    ): TrayectoriaAcademica {
        if (!in_array($tipo, ['reingreso', 'reincorporacion'], true)) {
            throw ValidationException::withMessages(['tipo_retorno' => 'El tipo de retorno no es válido.']);
        }

        return DB::transaction(function () use ($alumno, $tipo, $destino, $procedencia, $usuarioId) {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para registrar el movimiento.');

            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);
            $fecha = Carbon::parse($destino['fecha_ingreso'] ?? now());
            $origen = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->orderByDesc('es_actual')
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$origen) {
                throw ValidationException::withMessages(['alumno' => 'El alumno no tiene una trayectoria anterior para reingresar.']);
            }

            $permitidos = $tipo === 'reingreso'
                ? ['egresado']
                : ['traslado', 'baja_temporal', 'baja_definitiva', 'inactivo', 'suspendido'];

            if (!in_array($origen->estatus, $permitidos, true)) {
                throw ValidationException::withMessages([
                    'tipo_retorno' => 'El estado anterior no corresponde al tipo de retorno seleccionado.',
                ]);
            }

            $this->validarSinTrayectoriaActiva($alumno->id);
            $this->validarDestino($destino);

            if ($origen->fecha_fin && $fecha->lt(Carbon::parse($origen->fecha_fin))) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de retorno no puede ser anterior al cierre de la trayectoria previa.',
                ]);
            }

            if ($tipo === 'reincorporacion' && (int) $origen->nivel_id !== (int) $destino['nivel_id']) {
                throw ValidationException::withMessages([
                    'nivel_destino_id' => 'La reincorporación debe realizarse en el mismo nivel. Usa reingreso para otro nivel.',
                ]);
            }

            TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('es_actual', true)
                ->update(['es_actual' => false]);

            $origen->update([
                'es_actual' => false,
                'vigente_en_corte' => false,
                'fecha_fin' => $origen->fecha_fin ?: $fecha,
            ]);

            $nueva = $this->crearTrayectoriaDestino(
                $alumno,
                $origen,
                $destino,
                $tipo,
                $fecha,
                [
                    'continuidad' => 'retorno',
                    'escuela_procedencia' => $procedencia['escuela_procedencia'] ?? null,
                    'cct_procedencia' => $procedencia['cct_procedencia'] ?? null,
                    'ciclo_procedencia' => $procedencia['ciclo_procedencia'] ?? null,
                    'ultimo_grado_procedencia' => $procedencia['ultimo_grado_procedencia'] ?? null,
                    'observaciones_procedencia' => $procedencia['observaciones_procedencia'] ?? null,
                    'documentacion_pendiente' => (bool) ($procedencia['documentacion_pendiente'] ?? false),
                ]
            );

            $matricula = app(TrayectoriaAcademicaService::class)->asegurarMatriculaNivel(
                $alumno,
                $nueva->nivel_id,
                $destino['matricula'] ?? null,
                $fecha,
                $usuarioId,
                $tipo,
                (int) $origen->nivel_id !== (int) $nueva->nivel_id,
                $nueva->generacion_id
            );

            $alumno->restore();
            $alumno->update([
                'matricula' => $matricula->matricula,
                'nivel_id' => $nueva->nivel_id,
                'grado_id' => $nueva->grado_id,
                'generacion_id' => $nueva->generacion_id,
                'grupo_id' => $nueva->grupo_id,
                'semestre_id' => $nueva->semestre_id,
                'ciclo_id' => $nueva->ciclo_id,
                'activo' => true,
                'indicador_reingreso' => true,
                'tipo_ultimo_ingreso' => $tipo,
                'fecha_ultimo_ingreso' => $fecha->toDateString(),
                'documentacion_reingreso_pendiente' => (bool) ($procedencia['documentacion_pendiente'] ?? false),
                'usuario_acceso_activo' => $destino['usuario_acceso_activo'] ?? null,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
            ]);

            $this->registrarMovimiento(
                $alumno,
                $origen,
                $nueva,
                $tipo,
                $fecha,
                $destino['justificacion'] ?? ($tipo === 'reingreso' ? 'Reingreso de exalumno.' : 'Reincorporación al nivel.'),
                $procedencia['observaciones_procedencia'] ?? null,
                $tipo,
                $destino['usuario_acceso_activo'] ?? null,
                $usuarioId
            );

            return $nueva->refresh();
        });
    }

    public function capturarCalificacionExterna(
        Inscripcion $alumno,
        int $asignacionMateriaId,
        int $periodoId,
        string $calificacion,
        ?int $documentoRespaldoId,
        ?string $escuelaProcedencia,
        ?string $observacion,
        ?int $usuarioId = null
    ): Calificacion {
        return DB::transaction(function () use (
            $alumno, $asignacionMateriaId, $periodoId, $calificacion,
            $documentoRespaldoId, $escuelaProcedencia, $observacion, $usuarioId
        ) {
            $usuarioId = $usuarioId ?: auth()->id();
            $trayectoria = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('es_actual', true)
                ->where('activo', true)
                ->firstOrFail();

            $asignacion = AsignacionMateria::query()
                ->with('grupo')
                ->whereKey($asignacionMateriaId)
                ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
                ->where('nivel_id', $trayectoria->nivel_id)
                ->where('grado_id', $trayectoria->grado_id)
                ->where('generacion_id', $trayectoria->generacion_id)
                ->where('grupo_id', $trayectoria->grupo_id)
                ->when($trayectoria->semestre_id,
                    fn ($query) => $query->where('semestre_id', $trayectoria->semestre_id),
                    fn ($query) => $query->whereNull('semestre_id')
                )
                ->firstOrFail();
            $periodo = Periodos::query()
                ->whereKey($periodoId)
                ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
                ->where('nivel_id', $trayectoria->nivel_id)
                ->where('generacion_id', $trayectoria->generacion_id)
                ->when($trayectoria->semestre_id,
                    fn ($query) => $query->where('semestre_id', $trayectoria->semestre_id),
                    fn ($query) => $query->whereNull('semestre_id')
                )
                ->firstOrFail();

            $existente = Calificacion::query()
                ->where('periodo_id', $periodo->id)
                ->where('inscripcion_id', $alumno->id)
                ->where('asignacion_materia_id', $asignacion->id)
                ->first();

            if ($existente && $existente->fuente !== 'externa') {
                throw ValidationException::withMessages([
                    'calificacion_externa' => 'Ya existe una calificación interna en esta materia y periodo. No se sobrescribió.',
                ]);
            }

            $normalizada = mb_strtoupper(trim($calificacion));
            $numerica = is_numeric($normalizada);
            $esBachillerato = ReglasMateriaBachillerato::esBachillerato($trayectoria->nivel_id);

            if ($numerica && ((float) $normalizada < 5 || (float) $normalizada > 10)) {
                throw ValidationException::withMessages(['calificacion_externa' => 'La calificación numérica debe estar entre 5 y 10.']);
            }

            if ($numerica && $esBachillerato) {
                $normalizada = CalificacionBachillerato::formatearEntero($normalizada, '');
            }

            if (!$numerica && !in_array($normalizada, ['AC', 'NP'], true)) {
                throw ValidationException::withMessages(['calificacion_externa' => 'Usa una calificación de 5 a 10, AC o NP.']);
            }

            return Calificacion::query()->updateOrCreate(
                [
                    'periodo_id' => $periodo->id,
                    'inscripcion_id' => $alumno->id,
                    'asignacion_materia_id' => $asignacion->id,
                ],
                [
                    'nivel_id' => $trayectoria->nivel_id,
                    'grado_id' => $trayectoria->grado_id,
                    'grupo_id' => $trayectoria->grupo_id,
                    'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                    'generacion_id' => $trayectoria->generacion_id,
                    'semestre_id' => $trayectoria->semestre_id,
                    'calificacion' => $normalizada,
                    'valor_numerico' => $numerica ? (float) $normalizada : null,
                    'es_numerica' => $numerica,
                    'clave_especial' => $numerica ? null : $normalizada,
                    'observacion' => $observacion,
                    'fuente' => 'externa',
                    'escuela_procedencia' => $escuelaProcedencia,
                    'documento_respaldo_id' => $documentoRespaldoId,
                    'equivalencia_autorizada' => true,
                    'fecha_validacion' => now(),
                    'validado_por' => $usuarioId,
                    'capturado_por' => $usuarioId,
                    'fecha_captura' => now(),
                    'ip_captura' => request()?->ip(),
                ]
            );
        });
    }

    public function generacionPuedeCerrar(Generacion $generacion): bool
    {
        return !TrayectoriaAcademica::query()
            ->where('generacion_id', $generacion->id)
            ->where('activo', true)
            ->where('es_actual', true)
            ->whereNotIn('estatus', ['egresado', 'traslado', 'baja_definitiva', 'archivado'])
            ->exists();
    }

    public function cerrarGeneracion(Generacion $generacion, ?int $usuarioId = null): Generacion
    {
        return DB::transaction(function () use ($generacion, $usuarioId) {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para cerrar la generación.');
            $generacion = Generacion::query()->lockForUpdate()->findOrFail($generacion->id);

            if (!$this->generacionPuedeCerrar($generacion)) {
                throw ValidationException::withMessages([
                    'generacion' => 'La generación todavía tiene alumnos activos o repetidores pendientes.',
                ]);
            }

            $generacion->update([
                'status' => false,
                'cerrada_at' => now(),
                'cerrada_por' => $usuarioId,
            ]);

            return $generacion->refresh();
        });
    }

    public function reactivarGeneracion(Generacion $generacion): Generacion
    {
        return DB::transaction(function () use ($generacion) {
            $generacion = Generacion::query()->lockForUpdate()->findOrFail($generacion->id);
            $generacion->update(['status' => true, 'cerrada_at' => null, 'cerrada_por' => null]);

            return $generacion->refresh();
        });
    }

    private function continuarEnInstitucion(
        Inscripcion $alumno,
        TrayectoriaAcademica $origen,
        array $datos,
        CarbonInterface $fecha,
        int $usuarioId
    ): TrayectoriaAcademica {
        $this->validarDestino($datos);
        $this->validarSinTrayectoriaActiva($alumno->id, $origen->id);

        $origenAnterior = $this->snapshot($origen);
        $origen->update([
            'activo' => false,
            'estatus' => 'egresado',
            'continuidad' => 'interna',
            'fecha_fin' => $fecha,
            'vigente_en_corte' => true,
            'es_actual' => false,
            'promovido' => true,
            'fecha_promocion' => $fecha,
        ]);

        $nueva = $this->crearTrayectoriaDestino($alumno, $origen, $datos, 'cambio_nivel', $fecha);
        $matricula = app(TrayectoriaAcademicaService::class)->asegurarMatriculaNivel(
            $alumno,
            $nueva->nivel_id,
            $datos['matricula'] ?? null,
            $fecha,
            $usuarioId,
            'cambio_nivel',
            (int) $origen->nivel_id !== (int) $nueva->nivel_id,
            $nueva->generacion_id
        );

        $alumno->restore();
        $alumno->update([
            'matricula' => $matricula->matricula,
            'nivel_id' => $nueva->nivel_id,
            'grado_id' => $nueva->grado_id,
            'generacion_id' => $nueva->generacion_id,
            'grupo_id' => $nueva->grupo_id,
            'semestre_id' => $nueva->semestre_id,
            'ciclo_id' => $nueva->ciclo_id,
            'activo' => true,
            'indicador_reingreso' => false,
            'tipo_ultimo_ingreso' => 'cambio_nivel',
            'fecha_ultimo_ingreso' => $fecha->toDateString(),
            'documentacion_reingreso_pendiente' => false,
            'usuario_acceso_activo' => $datos['usuario_acceso_activo'] ?? null,
            'fecha_baja' => null,
            'motivo_baja' => null,
            'observaciones_baja' => null,
        ]);

        $this->registrarMovimiento(
            $alumno,
            $origen,
            $nueva,
            'cambio_nivel',
            $fecha,
            'Egreso del nivel anterior y continuidad en el siguiente nivel dentro de la institución.',
            $datos['observaciones'] ?? null,
            'continua_institucion',
            $datos['usuario_acceso_activo'] ?? null,
            $usuarioId,
            $origenAnterior
        );

        return $nueva;
    }

    private function cerrarComoEgresado(
        Inscripcion $alumno,
        TrayectoriaAcademica $origen,
        string $continuidad,
        array $datos,
        CarbonInterface $fecha,
        int $usuarioId
    ): TrayectoriaAcademica {
        $anterior = $this->snapshot($origen);
        $origen->update([
            'activo' => false,
            'estatus' => 'egresado',
            'continuidad' => $continuidad,
            'fecha_fin' => $fecha,
            'vigente_en_corte' => true,
            'es_actual' => true,
            'promovido' => true,
            'fecha_promocion' => $fecha,
            'observaciones_baja' => $datos['observaciones'] ?? null,
        ]);

        $alumno->restore();
        $alumno->update([
            'activo' => false,
            'indicador_reingreso' => false,
            'usuario_acceso_activo' => $datos['usuario_acceso_activo'] ?? null,
            'fecha_baja' => null,
            'motivo_baja' => null,
            'observaciones_baja' => $datos['observaciones'] ?? null,
        ]);

        $this->registrarMovimiento(
            $alumno,
            $origen,
            null,
            'egreso',
            $fecha,
            $continuidad === 'otra_escuela' ? 'Egresó y continuará en otra institución.' : 'Egresó sin destino escolar especificado.',
            $datos['observaciones'] ?? null,
            $continuidad,
            $datos['usuario_acceso_activo'] ?? null,
            $usuarioId,
            $anterior
        );

        return $origen->refresh();
    }

    private function cerrarComoSalida(
        Inscripcion $alumno,
        TrayectoriaAcademica $origen,
        string $tipo,
        array $datos,
        CarbonInterface $fecha,
        int $usuarioId
    ): TrayectoriaAcademica {
        $resultado = app(TrayectoriaAcademicaService::class)->aplicarBaja(
            $alumno,
            $origen->ciclo_escolar_id,
            $origen->ciclo_id,
            $tipo,
            $fecha,
            $datos['motivo'] ?? null,
            $datos['observaciones'] ?? null,
            $usuarioId
        );

        $resultado->update([
            'continuidad' => $tipo === 'traslado' ? 'otra_escuela' : null,
        ]);
        $alumno->update([
            'indicador_reingreso' => false,
            'usuario_acceso_activo' => $datos['usuario_acceso_activo'] ?? null,
        ]);

        MovimientoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('trayectoria_academica_id', $resultado->id)
            ->latest('id')
            ->first()?->update([
                'nivel_anterior_id' => $origen->nivel_id,
                'resultado_continuidad' => $tipo === 'traslado' ? 'otra_escuela' : $tipo,
                'usuario_acceso_activo' => $datos['usuario_acceso_activo'] ?? null,
            ]);

        return $resultado->refresh();
    }

    private function repetirGrado(
        Inscripcion $alumno,
        TrayectoriaAcademica $origen,
        array $datos,
        CarbonInterface $fecha,
        int $usuarioId
    ): TrayectoriaAcademica {
        $destino = array_merge([
            'nivel_id' => $origen->nivel_id,
            'grado_id' => $origen->grado_id,
            'generacion_id' => $origen->generacion_id,
            'grupo_id' => $origen->grupo_id,
            'semestre_id' => $origen->semestre_id,
        ], $datos);
        $this->validarDestino($destino);

        $nueva = app(TrayectoriaAcademicaService::class)->promover(
            $origen,
            $destino,
            'no_promovido',
            $fecha,
            $usuarioId
        );

        $nueva->update([
            'origen' => 'repeticion',
            'tipo_ingreso' => 'repeticion',
        ]);
        $alumno->update([
            'indicador_reingreso' => false,
            'tipo_ultimo_ingreso' => 'repeticion',
            'fecha_ultimo_ingreso' => $fecha->toDateString(),
            'usuario_acceso_activo' => $datos['usuario_acceso_activo'] ?? null,
        ]);

        return $nueva->refresh();
    }

    private function crearTrayectoriaDestino(
        Inscripcion $alumno,
        TrayectoriaAcademica $origen,
        array $destino,
        string $tipoIngreso,
        CarbonInterface $fecha,
        array $extras = []
    ): TrayectoriaAcademica {
        $numeroEstancia = ((int) TrayectoriaAcademica::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('ciclo_escolar_id', (int) $destino['ciclo_escolar_id'])
            ->where('ciclo_id', (int) $destino['ciclo_id'])
            ->max('numero_estancia')) + 1;

        return TrayectoriaAcademica::query()->create(array_merge([
            'inscripcion_id' => $alumno->id,
            'ciclo_escolar_id' => (int) $destino['ciclo_escolar_id'],
            'ciclo_id' => (int) $destino['ciclo_id'],
            'nivel_id' => (int) $destino['nivel_id'],
            'grado_id' => (int) $destino['grado_id'],
            'generacion_id' => (int) $destino['generacion_id'],
            'grupo_id' => (int) $destino['grupo_id'],
            'semestre_id' => filled($destino['semestre_id'] ?? null) ? (int) $destino['semestre_id'] : null,
            'activo' => true,
            'estatus' => 'activo',
            'fecha_inscripcion' => $fecha,
            'fecha_inicio' => $fecha,
            'fecha_fin' => null,
            'numero_estancia' => $numeroEstancia,
            'vigente_en_corte' => true,
            'es_actual' => true,
            'origen' => $tipoIngreso,
            'tipo_ingreso' => $tipoIngreso,
            'datos_reconstruidos' => false,
            'promovido' => null,
            'fecha_promocion' => null,
            'trayectoria_origen_id' => $origen->id,
            'documentacion_pendiente' => false,
        ], $extras));
    }

    private function esUltimaEtapaDelNivel(TrayectoriaAcademica $trayectoria): bool
    {
        $nivel = Nivel::query()->find($trayectoria->nivel_id);
        $textoNivel = mb_strtolower(($nivel?->slug ?? '') . ' ' . ($nivel?->nombre ?? ''));
        $esBachillerato = str_contains($textoNivel, 'bachillerato');

        if ($esBachillerato) {
            $ultimoSemestre = (int) Semestre::query()
                ->whereHas('grado', fn ($query) => $query->where('nivel_id', $trayectoria->nivel_id))
                ->max('numero');

            return $ultimoSemestre > 0
                && (int) ($trayectoria->semestre?->numero ?? Semestre::query()->whereKey($trayectoria->semestre_id)->value('numero')) === $ultimoSemestre;
        }

        $ultimoOrden = (int) Grado::query()
            ->where('nivel_id', $trayectoria->nivel_id)
            ->max('orden');
        $ordenActual = (int) ($trayectoria->grado?->orden ?? Grado::query()->whereKey($trayectoria->grado_id)->value('orden'));

        return $ultimoOrden > 0 && $ordenActual === $ultimoOrden;
    }

    private function validarDestino(array $destino): void
    {
        foreach (['ciclo_escolar_id', 'ciclo_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id'] as $campo) {
            if (blank($destino[$campo] ?? null)) {
                throw ValidationException::withMessages([$campo => 'Completa todos los datos académicos de destino.']);
            }
        }

        $cicloEscolarId = (int) $destino['ciclo_escolar_id'];
        $cicloId = (int) $destino['ciclo_id'];
        $nivelId = (int) $destino['nivel_id'];
        $gradoId = (int) $destino['grado_id'];
        $generacionId = (int) $destino['generacion_id'];
        $grupoId = (int) $destino['grupo_id'];
        $semestreId = filled($destino['semestre_id'] ?? null) ? (int) $destino['semestre_id'] : null;

        if (!CicloEscolar::query()->whereKey($cicloEscolarId)->exists() || !Ciclo::query()->whereKey($cicloId)->exists()) {
            throw ValidationException::withMessages(['ciclo_escolar_id' => 'El ciclo escolar o corte seleccionado no existe.']);
        }

        if (!Grado::query()->whereKey($gradoId)->where('nivel_id', $nivelId)->exists()) {
            throw ValidationException::withMessages(['grado_id' => 'El grado no pertenece al nivel seleccionado.']);
        }

        if (!Generacion::query()->whereKey($generacionId)->where('nivel_id', $nivelId)->where('status', true)->exists()) {
            throw ValidationException::withMessages(['generacion_id' => 'La generación no está activa o no pertenece al nivel seleccionado.']);
        }

        if ($semestreId && !Semestre::query()->whereKey($semestreId)->where('grado_id', $gradoId)->exists()) {
            throw ValidationException::withMessages(['semestre_id' => 'El semestre no pertenece al grado seleccionado.']);
        }

        $grupoValido = Grupo::query()
            ->whereKey($grupoId)
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->where('generacion_id', $generacionId)
            ->when($semestreId,
                fn ($query) => $query->where('semestre_id', $semestreId),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->exists();

        if (!$grupoValido) {
            throw ValidationException::withMessages(['grupo_id' => 'El grupo no coincide con el nivel, grado, generación o semestre seleccionado.']);
        }
    }

    private function validarSinTrayectoriaActiva(int $inscripcionId, ?int $exceptoId = null): void
    {
        $query = TrayectoriaAcademica::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('activo', true)
            ->where('es_actual', true)
            ->whereNotIn('estatus', ['egresado', 'traslado', 'baja_definitiva', 'archivado']);

        if ($exceptoId) {
            $query->where('id', '!=', $exceptoId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'alumno' => 'El alumno ya tiene una trayectoria académica activa. Ciérrala o corrígela antes de continuar.',
            ]);
        }
    }

    private function registrarMovimiento(
        Inscripcion $alumno,
        TrayectoriaAcademica $origen,
        ?TrayectoriaAcademica $destino,
        string $tipo,
        CarbonInterface $fecha,
        ?string $motivo,
        ?string $observaciones,
        ?string $resultado,
        ?bool $usuarioAccesoActivo,
        int $usuarioId,
        ?array $estadoAnterior = null
    ): MovimientoAlumno {
        return MovimientoAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'trayectoria_academica_id' => $destino?->id ?: $origen->id,
            'ciclo_escolar_id' => $destino?->ciclo_escolar_id ?: $origen->ciclo_escolar_id,
            'ciclo_id' => $destino?->ciclo_id ?: $origen->ciclo_id,
            'trayectoria_origen_id' => $origen->id,
            'trayectoria_destino_id' => $destino?->id,
            'nivel_anterior_id' => $origen->nivel_id,
            'nivel_nuevo_id' => $destino?->nivel_id,
            'resultado_continuidad' => $resultado,
            'usuario_acceso_activo' => $usuarioAccesoActivo,
            'tipo' => $tipo,
            'fecha' => $fecha->toDateString(),
            'motivo' => $motivo,
            'observaciones' => $observaciones,
            'estado_anterior' => $estadoAnterior ?: $this->snapshot($origen),
            'estado_nuevo' => $destino ? $this->snapshot($destino) : $this->snapshot($origen),
            'registrado_por' => $usuarioId,
        ]);
    }

    private function snapshot(TrayectoriaAcademica $trayectoria): array
    {
        return [
            'trayectoria_id' => $trayectoria->id,
            'nivel_id' => $trayectoria->nivel_id,
            'grado_id' => $trayectoria->grado_id,
            'grupo_id' => $trayectoria->grupo_id,
            'generacion_id' => $trayectoria->generacion_id,
            'semestre_id' => $trayectoria->semestre_id,
            'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
            'ciclo_id' => $trayectoria->ciclo_id,
            'estatus' => $trayectoria->estatus,
            'activo' => (bool) $trayectoria->activo,
            'tipo_ingreso' => $trayectoria->tipo_ingreso,
            'continuidad' => $trayectoria->continuidad,
        ];
    }
}
