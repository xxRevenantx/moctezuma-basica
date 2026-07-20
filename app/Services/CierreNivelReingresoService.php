<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\CambioAcademico;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MatriculaAlumno;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use App\Support\CalificacionBachillerato;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreNivelReingresoService
{
    /**
     * Reactiva un registro existente sin crear una segunda inscripción.
     * El historial queda en cambios_academicos y movimientos_alumnos.
     */
    public function reingresarExalumno(
        Inscripcion $alumno,
        string $tipo,
        array $destino,
        array $procedencia,
        ?int $usuarioId = null
    ): Inscripcion {
        if (! in_array($tipo, ['reingreso', 'reincorporacion'], true)) {
            throw ValidationException::withMessages([
                'tipo_retorno' => 'El tipo de retorno no es válido.',
            ]);
        }

        return DB::transaction(function () use ($alumno, $tipo, $destino, $procedencia, $usuarioId): Inscripcion {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para registrar el movimiento.');

            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($alumno->id);
            $fecha = Carbon::parse($destino['fecha_ingreso'] ?? now())->startOfDay();
            $estatusAnterior = $this->normalizarEstatus((string) $alumno->estatus);

            $permitidos = $tipo === 'reingreso'
                ? ['egresado']
                : ['traslado', 'baja_temporal', 'baja_definitiva', 'inactivo', 'suspendido'];

            if (! in_array($estatusAnterior, $permitidos, true) || (bool) $alumno->activo) {
                throw ValidationException::withMessages([
                    'tipo_retorno' => 'El estado actual del alumno no corresponde al tipo de retorno seleccionado.',
                ]);
            }

            $this->validarDestino($destino);

            if ($tipo === 'reincorporacion' && (int) $alumno->nivel_id !== (int) $destino['nivel_id']) {
                throw ValidationException::withMessages([
                    'nivel_destino_id' => 'La reincorporación debe realizarse en el mismo nivel. Usa reingreso para cambiar de nivel.',
                ]);
            }

            $ultimaFecha = collect([$alumno->fecha_estatus, $alumno->fecha_baja])
                ->filter()
                ->map(fn ($valor) => Carbon::parse($valor)->startOfDay())
                ->sortDesc()
                ->first();

            if ($ultimaFecha && $fecha->lt($ultimaFecha)) {
                throw ValidationException::withMessages([
                    'fecha_ingreso' => 'La fecha de retorno no puede ser anterior al último movimiento del alumno.',
                ]);
            }

            $antes = $this->snapshot($alumno);
            $cambioNivel = (int) $alumno->nivel_id !== (int) $destino['nivel_id'];
            $matricula = $this->asegurarMatriculaNivel(
                $alumno,
                (int) $destino['nivel_id'],
                $destino['matricula'] ?? null,
                $fecha,
                $usuarioId,
                $tipo,
                $cambioNivel,
                (int) $destino['generacion_id']
            );

            if ($alumno->trashed()) {
                $alumno->restore();
            }

            $motivo = trim((string) ($destino['justificacion'] ?? ''));
            if ($motivo === '') {
                $motivo = $tipo === 'reingreso'
                    ? 'Reingreso de exalumno.'
                    : 'Reincorporación del alumno.';
            }

            $alumno->forceFill([
                'matricula' => $matricula->matricula,
                'nivel_id' => (int) $destino['nivel_id'],
                'grado_id' => (int) $destino['grado_id'],
                'generacion_id' => (int) $destino['generacion_id'],
                'grupo_id' => (int) $destino['grupo_id'],
                'semestre_id' => filled($destino['semestre_id'] ?? null)
                    ? (int) $destino['semestre_id']
                    : null,
                'ciclo_id' => (int) $destino['ciclo_id'],
                'ciclo_escolar_id' => (int) $destino['ciclo_escolar_id'],
                'activo' => true,
                'estatus' => $tipo === 'reingreso' ? 'reingreso' : 'activo',
                'fecha_estatus' => $fecha,
                'motivo_estatus' => $motivo,
                'indicador_reingreso' => true,
                'tipo_ultimo_ingreso' => $tipo,
                'fecha_ultimo_ingreso' => $fecha->toDateString(),
                'documentacion_reingreso_pendiente' => (bool) ($procedencia['documentacion_pendiente'] ?? false),
                'usuario_acceso_activo' => $destino['usuario_acceso_activo'] ?? null,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'deleted_at' => null,
            ])->save();

            $alumno->refresh();
            $despues = $this->snapshot($alumno);
            $despues['ciclo_escolar_id'] = (int) $destino['ciclo_escolar_id'];
            $despues['procedencia'] = $this->procedenciaLimpia($procedencia);

            CambioAcademico::query()->create([
                'inscripcion_id' => $alumno->id,
                'generacion_id' => $alumno->generacion_id,
                'tipo' => $tipo,
                'motivo' => $motivo,
                'datos_anteriores' => $antes,
                'datos_nuevos' => $despues,
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            MovimientoAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => (int) $destino['ciclo_escolar_id'],
                'ciclo_id' => (int) $destino['ciclo_id'],
                'nivel_anterior_id' => $antes['nivel_id'] ?? null,
                'nivel_nuevo_id' => $alumno->nivel_id,
                'resultado_continuidad' => $tipo,
                'usuario_acceso_activo' => $destino['usuario_acceso_activo'] ?? null,
                'tipo' => $tipo,
                'fecha' => $fecha->toDateString(),
                'motivo' => $motivo,
                'observaciones' => $this->textoProcedencia($procedencia),
                'estado_anterior' => $antes,
                'estado_nuevo' => $despues,
                'registrado_por' => $usuarioId,
            ]);

            return $alumno->fresh([
                'nivel', 'grado', 'generacion', 'grupo.asignacionGrupo', 'semestre',
            ]);
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
            $alumno,
            $asignacionMateriaId,
            $periodoId,
            $calificacion,
            $documentoRespaldoId,
            $escuelaProcedencia,
            $observacion,
            $usuarioId
        ): Calificacion {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para validar la equivalencia.');

            $alumno = Inscripcion::query()->lockForUpdate()->findOrFail($alumno->id);
            abort_unless($alumno->activo, 422, 'El alumno debe estar activo para integrar equivalencias.');

            $asignacion = AsignacionMateria::query()
                ->whereKey($asignacionMateriaId)
                ->where('nivel_id', $alumno->nivel_id)
                ->where('grado_id', $alumno->grado_id)
                ->where('generacion_id', $alumno->generacion_id)
                ->where('grupo_id', $alumno->grupo_id)
                ->when(
                    $alumno->semestre_id,
                    fn ($query) => $query->where('semestre_id', $alumno->semestre_id),
                    fn ($query) => $query->whereNull('semestre_id')
                )
                ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
                ->firstOrFail();

            $periodo = Periodos::query()
                ->whereKey($periodoId)
                ->where('ciclo_escolar_id', $asignacion->ciclo_escolar_id)
                ->where('nivel_id', $alumno->nivel_id)
                ->where('generacion_id', $alumno->generacion_id)
                ->when(
                    $alumno->semestre_id,
                    fn ($query) => $query->where('semestre_id', $alumno->semestre_id),
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

            if ($numerica && ((float) $normalizada < 5 || (float) $normalizada > 10)) {
                throw ValidationException::withMessages([
                    'calificacion_externa' => 'La calificación numérica debe estar entre 5 y 10.',
                ]);
            }

            if ($numerica && ReglasMateriaBachillerato::esBachillerato($alumno->nivel_id)) {
                $normalizada = CalificacionBachillerato::formatearEntero($normalizada, '');
            }

            if (! $numerica && ! in_array($normalizada, ['AC', 'NP'], true)) {
                throw ValidationException::withMessages([
                    'calificacion_externa' => 'Usa una calificación de 5 a 10, AC o NP.',
                ]);
            }

            return Calificacion::query()->updateOrCreate(
                [
                    'periodo_id' => $periodo->id,
                    'inscripcion_id' => $alumno->id,
                    'asignacion_materia_id' => $asignacion->id,
                ],
                [
                    'nivel_id' => $alumno->nivel_id,
                    'grado_id' => $alumno->grado_id,
                    'grupo_id' => $alumno->grupo_id,
                    'ciclo_escolar_id' => $asignacion->ciclo_escolar_id,
                    'generacion_id' => $alumno->generacion_id,
                    'semestre_id' => $alumno->semestre_id,
                    'calificacion' => $normalizada,
                    'valor_numerico' => $numerica ? (float) $normalizada : null,
                    'es_numerica' => $numerica,
                    'clave_especial' => $numerica ? null : $normalizada,
                    'observacion' => filled($observacion) ? trim($observacion) : null,
                    'fuente' => 'externa',
                    'escuela_procedencia' => filled($escuelaProcedencia) ? trim($escuelaProcedencia) : null,
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
        return ! Inscripcion::query()
            ->where('generacion_id', $generacion->id)
            ->where('activo', true)
            ->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])
            ->exists();
    }

    private function validarDestino(array $destino): void
    {
        foreach (['ciclo_escolar_id', 'ciclo_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id'] as $campo) {
            if (! filled($destino[$campo] ?? null)) {
                throw ValidationException::withMessages([$campo => 'Completa la ubicación académica de destino.']);
            }
        }

        CicloEscolar::query()->findOrFail((int) $destino['ciclo_escolar_id']);
        Ciclo::query()->findOrFail((int) $destino['ciclo_id']);
        Nivel::query()->findOrFail((int) $destino['nivel_id']);

        $grado = Grado::query()->findOrFail((int) $destino['grado_id']);
        if ((int) $grado->nivel_id !== (int) $destino['nivel_id']) {
            throw ValidationException::withMessages(['grado_destino_id' => 'El grado no pertenece al nivel seleccionado.']);
        }

        $generacion = Generacion::query()->findOrFail((int) $destino['generacion_id']);
        if ((int) $generacion->nivel_id !== (int) $destino['nivel_id'] || ! $generacion->status) {
            throw ValidationException::withMessages(['generacion_destino_id' => 'La generación no pertenece al nivel o se encuentra cerrada.']);
        }

        $semestreId = filled($destino['semestre_id'] ?? null) ? (int) $destino['semestre_id'] : null;
        if ($semestreId) {
            $semestre = Semestre::query()->findOrFail($semestreId);
            if ((int) $semestre->grado_id !== (int) $grado->id) {
                throw ValidationException::withMessages(['semestre_destino_id' => 'El semestre no pertenece al grado seleccionado.']);
            }
        }

        $cicloEscolar = CicloEscolar::query()->findOrFail((int) $destino['ciclo_escolar_id']);
        $nivel = Nivel::query()->findOrFail((int) $destino['nivel_id']);
        $semestre = $semestreId ? Semestre::query()->findOrFail($semestreId) : null;
        $justificacion = trim((string) ($destino['justificacion'] ?? ''));

        $esGeneracionEsperada = app(AsignacionEscolarService::class)->esGeneracionEsperada(
            $cicloEscolar,
            $nivel,
            $generacion,
            $grado,
            $semestre,
        );

        if (! $esGeneracionEsperada && mb_strlen($justificacion) < 10) {
            throw ValidationException::withMessages([
                'justificacion' => 'La generación seleccionada es excepcional para el ciclo y grado. Escribe una justificación de al menos 10 caracteres.',
            ]);
        }

        try {
            app(AsignacionEscolarService::class)->validarAsignacion([
                'grupo_id' => (int) $destino['grupo_id'],
                'ciclo_escolar_id' => (int) $destino['ciclo_escolar_id'],
                'nivel_id' => (int) $destino['nivel_id'],
                'grado_id' => (int) $destino['grado_id'],
                'generacion_id' => (int) $destino['generacion_id'],
                'semestre_id' => $semestreId,
            ], permitirGeneracionExcepcional: ! $esGeneracionEsperada);
        } catch (ValidationException $exception) {
            $errores = [];
            foreach ($exception->errors() as $mensajes) {
                $errores = array_merge($errores, $mensajes);
            }

            throw ValidationException::withMessages([
                'grupo_destino_id' => $errores ?: ['El grupo no corresponde al ciclo escolar y ubicación seleccionados.'],
            ]);
        }
    }

    private function asegurarMatriculaNivel(
        Inscripcion $alumno,
        int $nivelId,
        ?string $matriculaSolicitada,
        Carbon $fecha,
        int $usuarioId,
        string $origen,
        bool $cambioNivel,
        ?int $generacionId
    ): MatriculaAlumno {
        $existenteNivel = MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('nivel_id', $nivelId)
            ->latest('fecha_asignacion')
            ->first();

        $solicitada = filled($matriculaSolicitada)
            ? mb_strtoupper(trim((string) $matriculaSolicitada))
            : null;

        if ($solicitada && $this->matriculaOcupada($solicitada, $alumno->id, $existenteNivel?->id)) {
            throw ValidationException::withMessages([
                'matricula' => 'La matrícula capturada ya pertenece a otro alumno.',
            ]);
        }

        MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('vigente', true)
            ->when($existenteNivel, fn ($query) => $query->where('id', '!=', $existenteNivel->id))
            ->update(['vigente' => false, 'fecha_fin' => $fecha->toDateString()]);

        if ($existenteNivel) {
            $existenteNivel->update([
                'matricula' => $solicitada ?: $existenteNivel->matricula,
                'vigente' => true,
                'fecha_fin' => null,
                'origen' => $origen,
                'registrado_por' => $usuarioId,
            ]);

            return $existenteNivel->refresh();
        }

        $matricula = $solicitada ?: $this->generarMatricula($alumno, $nivelId, $generacionId);

        return MatriculaAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'nivel_id' => $nivelId,
            'matricula' => $matricula,
            'fecha_asignacion' => $fecha->toDateString(),
            'fecha_fin' => null,
            'vigente' => true,
            'origen' => $cambioNivel ? $origen.'_cambio_nivel' : $origen,
            'registrado_por' => $usuarioId,
        ]);
    }

    private function generarMatricula(Inscripcion $alumno, int $nivelId, ?int $generacionId): string
    {
        $nivel = Nivel::query()->findOrFail($nivelId);
        $generacion = $generacionId ? Generacion::query()->find($generacionId) : null;
        $anio = (string) ($generacion?->anio_ingreso ?: now()->year);
        $slug = mb_strtolower((string) ($nivel->slug ?: $nivel->nombre));

        $codigo = match (true) {
            str_contains($slug, 'preescolar') => 'PRE',
            str_contains($slug, 'primaria') => 'PRIM',
            str_contains($slug, 'secundaria') => 'SEC',
            str_contains($slug, 'bachillerato') => 'BACHI',
            default => 'NIV',
        };

        $curp4 = str_pad(mb_substr(mb_strtoupper((string) $alumno->curp), 0, 4), 4, 'X');

        for ($intento = 0; $intento < 200; $intento++) {
            $matricula = $anio.$codigo.$curp4.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! $this->matriculaOcupada($matricula, $alumno->id)) {
                return $matricula;
            }
        }

        throw ValidationException::withMessages([
            'matricula' => 'No fue posible generar una matrícula única. Intenta nuevamente.',
        ]);
    }

    private function matriculaOcupada(string $matricula, int $inscripcionId, ?int $matriculaAlumnoId = null): bool
    {
        $enHistorial = MatriculaAlumno::query()
            ->where('matricula', $matricula)
            ->when($matriculaAlumnoId, fn ($query) => $query->where('id', '!=', $matriculaAlumnoId))
            ->exists();

        $enInscripciones = Inscripcion::withTrashed()
            ->where('matricula', $matricula)
            ->where('id', '!=', $inscripcionId)
            ->exists();

        return $enHistorial || $enInscripciones;
    }

    private function snapshot(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
            'matricula',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'grupo_id',
            'semestre_id',
            'ciclo_id',
            'ciclo_escolar_id',
            'estatus',
            'activo',
            'fecha_estatus',
            'motivo_estatus',
            'fecha_baja',
            'motivo_baja',
            'indicador_reingreso',
            'tipo_ultimo_ingreso',
            'fecha_ultimo_ingreso',
            'documentacion_reingreso_pendiente',
            'usuario_acceso_activo',
            'deleted_at',
        ]);
    }

    private function normalizarEstatus(string $estatus): string
    {
        return match (mb_strtolower(trim($estatus))) {
            'trasladado' => 'traslado',
            default => mb_strtolower(trim($estatus)),
        };
    }

    private function procedenciaLimpia(array $procedencia): array
    {
        return collect($procedencia)
            ->map(fn ($valor) => is_string($valor) ? trim($valor) : $valor)
            ->filter(fn ($valor) => $valor !== null && $valor !== '')
            ->all();
    }

    private function textoProcedencia(array $procedencia): ?string
    {
        $partes = collect([
            filled($procedencia['escuela_procedencia'] ?? null)
                ? 'Escuela: '.trim((string) $procedencia['escuela_procedencia'])
                : null,
            filled($procedencia['cct_procedencia'] ?? null)
                ? 'CCT: '.trim((string) $procedencia['cct_procedencia'])
                : null,
            filled($procedencia['ciclo_procedencia'] ?? null)
                ? 'Ciclo: '.trim((string) $procedencia['ciclo_procedencia'])
                : null,
            filled($procedencia['ultimo_grado_procedencia'] ?? null)
                ? 'Último grado: '.trim((string) $procedencia['ultimo_grado_procedencia'])
                : null,
            filled($procedencia['observaciones_procedencia'] ?? null)
                ? trim((string) $procedencia['observaciones_procedencia'])
                : null,
        ])->filter()->implode(' · ');

        return $partes !== '' ? $partes : null;
    }
}
