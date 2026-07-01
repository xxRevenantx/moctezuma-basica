<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Inscripcion;
use App\Models\MatriculaAlumno;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrayectoriaAcademicaService
{
    public function registrarInscripcionEnContexto(
        Inscripcion $alumno,
        array $datos,
        ?int $usuarioId = null,
        string $origen = 'inscripcion'
    ): TrayectoriaAcademica {
        return DB::transaction(function () use ($alumno, $datos, $usuarioId, $origen) {
            $usuarioId = $this->resolverUsuario($usuarioId);
            $fecha = $this->fecha($datos['fecha_inscripcion'] ?? now());
            $hacerActual = array_key_exists('hacer_actual', $datos)
                ? (bool) $datos['hacer_actual']
                : true;

            $existente = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('ciclo_escolar_id', (int) $datos['ciclo_escolar_id'])
                ->where('ciclo_id', (int) $datos['ciclo_id'])
                ->where('vigente_en_corte', true)
                ->lockForUpdate()
                ->latest('numero_estancia')
                ->first();

            if ($existente) {
                $cambioAcademico = collect([
                    'nivel_id',
                    'grado_id',
                    'generacion_id',
                    'grupo_id',
                    'semestre_id',
                ])->contains(function (string $campo) use ($existente, $datos) {
                    return (int) ($existente->{$campo} ?? 0) !== (int) ($datos[$campo] ?? 0);
                });

                if (!$cambioAcademico) {
                    $existente->update([
                        'activo' => true,
                        'estatus' => 'activo',
                        'fecha_inscripcion' => $fecha,
                        'fecha_inicio' => $existente->fecha_inicio ?: $fecha,
                        'fecha_baja' => null,
                        'motivo_baja' => null,
                        'observaciones_baja' => null,
                    ]);

                    if ($hacerActual) {
                        $this->asegurarMatriculaNivel(
                            $alumno,
                            (int) $datos['nivel_id'],
                            $datos['matricula'] ?? $alumno->matricula,
                            $fecha,
                            $usuarioId,
                            $origen,
                            false,
                            (int) $datos['generacion_id']
                        );
                    } else {
                        $this->asegurarMatriculaHistoricaNivel(
                            $alumno,
                            (int) $datos['nivel_id'],
                            $datos['matricula'] ?? null,
                            $fecha,
                            $usuarioId,
                            $origen,
                            (int) $datos['generacion_id']
                        );
                    }

                    return $existente->refresh();
                }

                return $this->crearCorreccionDesdeTrayectoria(
                    $existente,
                    $datos,
                    $usuarioId,
                    'Corrección durante la inscripción o importación.',
                    $fecha,
                    $origen
                );
            }

            if ($hacerActual) {
                TrayectoriaAcademica::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->where('es_actual', true)
                    ->update(['es_actual' => false]);
            }

            $trayectoria = TrayectoriaAcademica::query()->create([
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => (int) $datos['ciclo_escolar_id'],
                'ciclo_id' => (int) $datos['ciclo_id'],
                'nivel_id' => (int) $datos['nivel_id'],
                'grado_id' => (int) $datos['grado_id'],
                'generacion_id' => (int) $datos['generacion_id'],
                'grupo_id' => (int) $datos['grupo_id'],
                'semestre_id' => filled($datos['semestre_id'] ?? null) ? (int) $datos['semestre_id'] : null,
                'activo' => true,
                'estatus' => 'activo',
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'fecha_inscripcion' => $fecha,
                'fecha_inicio' => $fecha,
                'fecha_fin' => null,
                'numero_estancia' => $this->siguienteEstancia(
                    $alumno->id,
                    (int) $datos['ciclo_escolar_id'],
                    (int) $datos['ciclo_id']
                ),
                'vigente_en_corte' => true,
                'es_actual' => $hacerActual,
                'origen' => $origen,
                'tipo_ingreso' => $datos['tipo_ingreso'] ?? $origen,
                'datos_reconstruidos' => (bool) ($datos['datos_reconstruidos'] ?? false),
                'promovido' => null,
                'fecha_promocion' => null,
                'trayectoria_origen_id' => $datos['trayectoria_origen_id'] ?? null,
            ]);

            $matricula = $hacerActual
                ? $this->asegurarMatriculaNivel(
                    $alumno,
                    (int) $datos['nivel_id'],
                    $datos['matricula'] ?? $alumno->matricula,
                    $fecha,
                    $usuarioId,
                    $origen,
                    false,
                    (int) $datos['generacion_id']
                )
                : $this->asegurarMatriculaHistoricaNivel(
                    $alumno,
                    (int) $datos['nivel_id'],
                    $datos['matricula'] ?? null,
                    $fecha,
                    $usuarioId,
                    $origen,
                    (int) $datos['generacion_id']
                );

            if ($hacerActual) {
                $alumno->update([
                    'matricula' => $matricula->matricula,
                    'nivel_id' => $trayectoria->nivel_id,
                    'grado_id' => $trayectoria->grado_id,
                    'generacion_id' => $trayectoria->generacion_id,
                    'grupo_id' => $trayectoria->grupo_id,
                    'semestre_id' => $trayectoria->semestre_id,
                    'ciclo_id' => $trayectoria->ciclo_id,
                    'activo' => true,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'observaciones_baja' => null,
                ]);
            }

            $this->registrarMovimiento(
                $trayectoria,
                'inscripcion',
                $fecha,
                'Inscripción del alumno en el contexto académico seleccionado.',
                null,
                null,
                $this->snapshot($trayectoria),
                $usuarioId
            );

            return $trayectoria;
        });
    }

    public function corregirAsignacion(
        array|Collection $inscripcionIds,
        int $cicloEscolarId,
        int $cicloId,
        array $destino,
        ?int $usuarioId = null,
        ?string $motivo = null,
        CarbonInterface|string|null $fecha = null
    ): int {
        return DB::transaction(function () use (
            $inscripcionIds,
            $cicloEscolarId,
            $cicloId,
            $destino,
            $usuarioId,
            $motivo,
            $fecha
        ) {
            $usuarioId = $this->resolverUsuario($usuarioId);
            $fecha = $this->fecha($fecha ?? now());
            $ids = collect($inscripcionIds)->map(fn($id) => (int) $id)->filter()->unique()->values();
            $actualizados = 0;

            foreach ($ids as $inscripcionId) {
                $origen = TrayectoriaAcademica::query()
                    ->where('inscripcion_id', $inscripcionId)
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('ciclo_id', $cicloId)
                    ->where('vigente_en_corte', true)
                    ->lockForUpdate()
                    ->latest('numero_estancia')
                    ->first();

                if (!$origen) {
                    continue;
                }

                $this->crearCorreccionDesdeTrayectoria(
                    $origen,
                    $destino,
                    $usuarioId,
                    $motivo ?: 'Corrección administrativa de grado, grupo, generación o semestre.',
                    $fecha,
                    'correccion'
                );

                $actualizados++;
            }

            return $actualizados;
        });
    }

    public function aplicarBaja(
        Inscripcion $alumno,
        int $cicloEscolarId,
        int $cicloId,
        string $tipo,
        CarbonInterface|string $fecha,
        ?string $motivo,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): TrayectoriaAcademica {
        if (!in_array($tipo, ['baja_temporal', 'baja_definitiva', 'traslado', 'inactivo', 'suspendido'], true)) {
            throw ValidationException::withMessages([
                'tipo_movimiento' => 'El tipo de movimiento no es válido.',
            ]);
        }

        return DB::transaction(function () use (
            $alumno,
            $cicloEscolarId,
            $cicloId,
            $tipo,
            $fecha,
            $motivo,
            $observaciones,
            $usuarioId
        ) {
            $usuarioId = $this->resolverUsuario($usuarioId);
            $fecha = $this->fecha($fecha);
            $motivoNormalizado = filled($motivo) ? trim((string) $motivo) : null;

            $origen = $this->trayectoriaVigente($alumno->id, $cicloEscolarId, $cicloId, true);
            $estadoAnterior = $this->snapshot($origen);
            $eraActual = (bool) $origen->es_actual;

            $origen->update([
                'vigente_en_corte' => false,
                'es_actual' => false,
                'fecha_fin' => $fecha,
            ]);

            $baja = $this->clonarEtapa($origen, [
                'activo' => false,
                'estatus' => $tipo,
                'fecha_baja' => $fecha,
                'motivo_baja' => $motivoNormalizado,
                'observaciones_baja' => filled($observaciones) ? trim($observaciones) : null,
                'fecha_inicio' => $fecha,
                'fecha_fin' => null,
                'vigente_en_corte' => true,
                'es_actual' => $eraActual,
                'origen' => $tipo,
                'promovido' => null,
                'fecha_promocion' => null,
                'trayectoria_origen_id' => $origen->id,
            ]);

            if ($eraActual) {
                $alumno->update([
                    'activo' => false,
                    'fecha_baja' => $fecha,
                    'motivo_baja' => $motivoNormalizado,
                    'observaciones_baja' => filled($observaciones) ? trim($observaciones) : null,
                ]);
            }

            $this->registrarMovimiento(
                $baja,
                $tipo,
                $fecha,
                $motivoNormalizado,
                $observaciones,
                $estadoAnterior,
                $this->snapshot($baja),
                $usuarioId,
                $origen->id
            );

            return $baja;
        });
    }

    public function reingresar(
        Inscripcion $alumno,
        int $cicloEscolarId,
        int $cicloId,
        CarbonInterface|string $fecha,
        ?string $motivo = null,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): TrayectoriaAcademica {
        return DB::transaction(function () use (
            $alumno,
            $cicloEscolarId,
            $cicloId,
            $fecha,
            $motivo,
            $observaciones,
            $usuarioId
        ) {
            $usuarioId = $this->resolverUsuario($usuarioId);
            $fecha = $this->fecha($fecha);

            $origen = $this->trayectoriaVigente($alumno->id, $cicloEscolarId, $cicloId, true);

            if (!in_array($origen->estatus, ['baja_temporal', 'baja_definitiva', 'traslado', 'inactivo', 'suspendido'], true)) {
                throw ValidationException::withMessages([
                    'reingreso' => 'El alumno seleccionado no tiene un estado no activo vigente en este ciclo y corte.',
                ]);
            }

            $estadoAnterior = $this->snapshot($origen);
            $eraActual = (bool) $origen->es_actual;

            $origen->update([
                'vigente_en_corte' => false,
                'es_actual' => false,
                'fecha_fin' => $fecha,
            ]);

            $reingreso = $this->clonarEtapa($origen, [
                'activo' => true,
                'estatus' => 'reingreso',
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'fecha_inscripcion' => $fecha,
                'fecha_inicio' => $fecha,
                'fecha_fin' => null,
                'vigente_en_corte' => true,
                'es_actual' => $eraActual,
                'origen' => 'reingreso',
                'promovido' => null,
                'fecha_promocion' => null,
                'trayectoria_origen_id' => $origen->id,
            ]);

            if ($eraActual) {
                $alumno->update([
                    'nivel_id' => $reingreso->nivel_id,
                    'grado_id' => $reingreso->grado_id,
                    'generacion_id' => $reingreso->generacion_id,
                    'grupo_id' => $reingreso->grupo_id,
                    'semestre_id' => $reingreso->semestre_id,
                    'ciclo_id' => $reingreso->ciclo_id,
                    'activo' => true,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'observaciones_baja' => null,
                ]);
            }

            $this->registrarMovimiento(
                $reingreso,
                'reingreso',
                $fecha,
                $motivo ?: 'Reingreso del alumno.',
                $observaciones,
                $estadoAnterior,
                $this->snapshot($reingreso),
                $usuarioId,
                $origen->id
            );

            return $reingreso;
        });
    }

    public function promover(
        TrayectoriaAcademica $origen,
        array $destino,
        string $resultado = 'promovido',
        CarbonInterface|string|null $fecha = null,
        ?int $usuarioId = null
    ): TrayectoriaAcademica {
        if (!in_array($resultado, ['promovido', 'no_promovido'], true)) {
            throw ValidationException::withMessages([
                'resultado_promocion' => 'El resultado de promoción no es válido.',
            ]);
        }

        return DB::transaction(function () use ($origen, $destino, $resultado, $fecha, $usuarioId) {
            $usuarioId = $this->resolverUsuario($usuarioId);
            $fecha = $this->fecha($fecha ?? now());
            $origen = TrayectoriaAcademica::query()->lockForUpdate()->findOrFail($origen->id);
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($origen->inscripcion_id);

            $yaExiste = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('ciclo_escolar_id', (int) $destino['ciclo_escolar_id'])
                ->where('ciclo_id', (int) $destino['ciclo_id'])
                ->where('vigente_en_corte', true)
                ->exists();

            if ($yaExiste) {
                throw ValidationException::withMessages([
                    'seleccionados' => "{$alumno->matricula}: ya existe una trayectoria vigente en el ciclo y corte destino.",
                ]);
            }

            $estadoAnterior = $this->snapshot($origen);

            TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('es_actual', true)
                ->update(['es_actual' => false]);

            $origen->update([
                'promovido' => $resultado === 'promovido',
                'fecha_promocion' => $fecha,
                'estatus' => $resultado,
                'es_actual' => false,
            ]);

            $trayectoriaDestino = TrayectoriaAcademica::query()->create([
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => (int) $destino['ciclo_escolar_id'],
                'ciclo_id' => (int) $destino['ciclo_id'],
                'nivel_id' => (int) $destino['nivel_id'],
                'grado_id' => (int) $destino['grado_id'],
                'generacion_id' => (int) $destino['generacion_id'],
                'grupo_id' => (int) $destino['grupo_id'],
                'semestre_id' => filled($destino['semestre_id'] ?? null) ? (int) $destino['semestre_id'] : null,
                'activo' => true,
                'estatus' => $resultado === 'no_promovido' ? 'no_promovido' : 'activo',
                'promovido' => null,
                'fecha_promocion' => null,
                'trayectoria_origen_id' => $origen->id,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'fecha_inscripcion' => $fecha,
                'fecha_inicio' => $fecha,
                'fecha_fin' => null,
                'numero_estancia' => $this->siguienteEstancia(
                    $alumno->id,
                    (int) $destino['ciclo_escolar_id'],
                    (int) $destino['ciclo_id']
                ),
                'vigente_en_corte' => true,
                'es_actual' => true,
                'origen' => 'promocion',
                'tipo_ingreso' => $resultado === 'no_promovido' ? 'repeticion' : 'promocion',
                'datos_reconstruidos' => false,
            ]);

            $cambioNivel = (int) $origen->nivel_id !== (int) $trayectoriaDestino->nivel_id;
            $matricula = $this->asegurarMatriculaNivel(
                $alumno,
                $trayectoriaDestino->nivel_id,
                null,
                $fecha,
                $usuarioId,
                'promocion',
                $cambioNivel,
                $trayectoriaDestino->generacion_id
            );

            $alumno->restore();
            $alumno->update([
                'matricula' => $matricula->matricula,
                'nivel_id' => $trayectoriaDestino->nivel_id,
                'grado_id' => $trayectoriaDestino->grado_id,
                'generacion_id' => $trayectoriaDestino->generacion_id,
                'grupo_id' => $trayectoriaDestino->grupo_id,
                'semestre_id' => $trayectoriaDestino->semestre_id,
                'ciclo_id' => $trayectoriaDestino->ciclo_id,
                'activo' => true,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
            ]);

            $this->registrarMovimiento(
                $trayectoriaDestino,
                $resultado,
                $fecha,
                $resultado === 'promovido'
                    ? 'Promoción al ciclo escolar destino.'
                    : 'Alumno no promovido; continúa inscrito en el ciclo destino.',
                null,
                $estadoAnterior,
                $this->snapshot($trayectoriaDestino),
                $usuarioId,
                $origen->id
            );

            return $trayectoriaDestino;
        });
    }

    public function egresar(
        TrayectoriaAcademica $origen,
        CarbonInterface|string|null $fecha = null,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): TrayectoriaAcademica {
        return DB::transaction(function () use ($origen, $fecha, $observaciones, $usuarioId) {
            $usuarioId = $this->resolverUsuario($usuarioId);
            $fecha = $this->fecha($fecha ?? now());
            $origen = TrayectoriaAcademica::query()->lockForUpdate()->findOrFail($origen->id);
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($origen->inscripcion_id);
            $estadoAnterior = $this->snapshot($origen);

            TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('es_actual', true)
                ->where('id', '!=', $origen->id)
                ->update(['es_actual' => false]);

            $origen->update([
                'activo' => false,
                'estatus' => 'egresado',
                'fecha_fin' => $fecha,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => filled($observaciones) ? trim($observaciones) : null,
                'vigente_en_corte' => true,
                'es_actual' => true,
                'promovido' => true,
                'fecha_promocion' => $fecha,
            ]);

            $alumno->restore();
            $alumno->update([
                'activo' => false,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => filled($observaciones) ? trim($observaciones) : null,
            ]);

            $this->registrarMovimiento(
                $origen,
                'egresado',
                $fecha,
                'Conclusión del último grado o semestre del nivel educativo.',
                $observaciones,
                $estadoAnterior,
                $this->snapshot($origen),
                $usuarioId,
                $origen->trayectoria_origen_id
            );

            return $origen->refresh();
        });
    }

    public function asegurarMatriculaNivel(
        Inscripcion $alumno,
        int $nivelId,
        ?string $matriculaSolicitada,
        CarbonInterface|string|null $fecha,
        ?int $usuarioId,
        string $origen,
        bool $forzarNuevaPorCambioNivel,
        ?int $generacionId = null
    ): MatriculaAlumno {
        $usuarioId = $this->resolverUsuario($usuarioId);
        $fecha = $this->fecha($fecha ?? now());

        $existenteNivel = MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('nivel_id', $nivelId)
            ->latest('fecha_asignacion')
            ->first();

        if ($existenteNivel && !$forzarNuevaPorCambioNivel) {
            MatriculaAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('id', '!=', $existenteNivel->id)
                ->where('vigente', true)
                ->update(['vigente' => false, 'fecha_fin' => $fecha->toDateString()]);

            $cambios = ['vigente' => true, 'fecha_fin' => null];
            $solicitada = filled($matriculaSolicitada)
                ? mb_strtoupper(trim((string) $matriculaSolicitada))
                : null;

            if ($solicitada && $solicitada !== $existenteNivel->matricula) {
                $ocupada = MatriculaAlumno::query()
                    ->where('matricula', $solicitada)
                    ->where('id', '!=', $existenteNivel->id)
                    ->exists();

                if ($ocupada) {
                    throw ValidationException::withMessages([
                        'matricula' => 'La matrícula capturada ya pertenece a otro alumno.',
                    ]);
                }

                $cambios['matricula'] = $solicitada;
            }

            $existenteNivel->update($cambios);

            return $existenteNivel->refresh();
        }

        if ($existenteNivel && $forzarNuevaPorCambioNivel) {
            // Si vuelve al mismo nivel, se reutiliza su matrícula histórica de ese nivel.
            MatriculaAlumno::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('vigente', true)
                ->where('id', '!=', $existenteNivel->id)
                ->update(['vigente' => false, 'fecha_fin' => $fecha->toDateString()]);

            $existenteNivel->update(['vigente' => true, 'fecha_fin' => null]);

            return $existenteNivel;
        }

        MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('vigente', true)
            ->update(['vigente' => false, 'fecha_fin' => $fecha->toDateString()]);

        $matricula = filled($matriculaSolicitada)
            ? mb_strtoupper(trim((string) $matriculaSolicitada))
            : $this->generarMatricula($alumno, $nivelId, $generacionId);

        $ocupada = MatriculaAlumno::query()
            ->where('matricula', $matricula)
            ->exists();

        if ($ocupada) {
            $matricula = $this->generarMatricula($alumno, $nivelId, $generacionId);
        }

        return MatriculaAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'nivel_id' => $nivelId,
            'matricula' => $matricula,
            'fecha_asignacion' => $fecha->toDateString(),
            'fecha_fin' => null,
            'vigente' => true,
            'origen' => $origen,
            'registrado_por' => $usuarioId,
        ]);
    }

    public function asegurarMatriculaHistoricaNivel(
        Inscripcion $alumno,
        int $nivelId,
        ?string $matriculaSolicitada,
        CarbonInterface|string|null $fecha,
        ?int $usuarioId,
        string $origen,
        ?int $generacionId = null
    ): MatriculaAlumno {
        $usuarioId = $this->resolverUsuario($usuarioId);
        $fecha = $this->fecha($fecha ?? now());

        $existente = MatriculaAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('nivel_id', $nivelId)
            ->latest('fecha_asignacion')
            ->first();

        $solicitada = filled($matriculaSolicitada)
            ? mb_strtoupper(trim((string) $matriculaSolicitada))
            : null;

        if ($existente) {
            if ($solicitada && $solicitada !== $existente->matricula) {
                $ocupada = MatriculaAlumno::query()
                    ->where('matricula', $solicitada)
                    ->where('id', '!=', $existente->id)
                    ->exists();

                if ($ocupada) {
                    throw ValidationException::withMessages([
                        'matricula' => 'La matrícula capturada ya pertenece a otro alumno.',
                    ]);
                }

                $existente->update(['matricula' => $solicitada]);
            }

            return $existente->refresh();
        }

        $matricula = $solicitada ?: $this->generarMatricula($alumno, $nivelId, $generacionId);

        $ocupada = MatriculaAlumno::query()
            ->where('matricula', $matricula)
            ->exists();

        if ($ocupada) {
            $matricula = $this->generarMatricula($alumno, $nivelId, $generacionId);
        }

        return MatriculaAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'nivel_id' => $nivelId,
            'matricula' => $matricula,
            'fecha_asignacion' => $fecha->toDateString(),
            'fecha_fin' => $fecha->toDateString(),
            'vigente' => false,
            'origen' => $origen,
            'registrado_por' => $usuarioId,
        ]);
    }

    public function generarMatricula(Inscripcion $alumno, int $nivelId, ?int $generacionId = null): string
    {
        $nivel = Nivel::query()->findOrFail($nivelId);
        $generacionId = $generacionId ?: $alumno->generacion_id;
        $generacion = $generacionId
            ? Generacion::query()->find($generacionId)
            : null;

        $anio = (string) ($generacion?->anio_ingreso ?: now()->year);
        $slug = mb_strtolower((string) ($nivel->slug ?: $nivel->nombre));

        $codigo = match (true) {
            str_contains($slug, 'preescolar') => 'PRE',
            str_contains($slug, 'primaria') => 'PRIM',
            str_contains($slug, 'secundaria') => 'SEC',
            str_contains($slug, 'bachillerato') => 'BACHI',
            default => 'NIV',
        };

        $curp4 = mb_substr(mb_strtoupper((string) $alumno->curp), 0, 4);
        $curp4 = str_pad($curp4, 4, 'X');

        for ($i = 0; $i < 200; $i++) {
            $consecutivo = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$codigo}{$curp4}{$consecutivo}";

            $existe = MatriculaAlumno::query()->where('matricula', $matricula)->exists()
                || Inscripcion::withTrashed()->where('matricula', $matricula)->exists();

            if (!$existe) {
                return $matricula;
            }
        }

        throw ValidationException::withMessages([
            'matricula' => 'No fue posible generar una matrícula única. Intenta nuevamente.',
        ]);
    }

    private function crearCorreccionDesdeTrayectoria(
        TrayectoriaAcademica $origen,
        array $destino,
        int $usuarioId,
        string $motivo,
        CarbonInterface $fecha,
        string $origenRegistro
    ): TrayectoriaAcademica {
        $estadoAnterior = $this->snapshot($origen);
        $eraActual = (bool) $origen->es_actual;

        $origen->update([
            'vigente_en_corte' => false,
            'es_actual' => false,
            'fecha_fin' => $fecha,
        ]);

        $nueva = $this->clonarEtapa($origen, [
            'nivel_id' => (int) ($destino['nivel_id'] ?? $origen->nivel_id),
            'grado_id' => (int) ($destino['grado_id'] ?? $origen->grado_id),
            'generacion_id' => (int) ($destino['generacion_id'] ?? $origen->generacion_id),
            'grupo_id' => (int) ($destino['grupo_id'] ?? $origen->grupo_id),
            'semestre_id' => filled($destino['semestre_id'] ?? null)
                ? (int) $destino['semestre_id']
                : null,
            'fecha_inscripcion' => array_key_exists('fecha_inscripcion', $destino)
                ? $this->fecha($destino['fecha_inscripcion'])
                : $origen->fecha_inscripcion,
            'fecha_inicio' => $fecha,
            'fecha_fin' => null,
            'vigente_en_corte' => true,
            'es_actual' => $eraActual,
            'origen' => $origenRegistro,
            'trayectoria_origen_id' => $origen->id,
        ]);

        $alumno = Inscripcion::withTrashed()->findOrFail($origen->inscripcion_id);

        if ($eraActual) {
            $cambioNivel = (int) $origen->nivel_id !== (int) $nueva->nivel_id;
            $matricula = $this->asegurarMatriculaNivel(
                $alumno,
                $nueva->nivel_id,
                $destino['matricula'] ?? null,
                $fecha,
                $usuarioId,
                $origenRegistro,
                $cambioNivel,
                $nueva->generacion_id
            );

            $alumno->update([
                'matricula' => $matricula->matricula,
                'nivel_id' => $nueva->nivel_id,
                'grado_id' => $nueva->grado_id,
                'generacion_id' => $nueva->generacion_id,
                'grupo_id' => $nueva->grupo_id,
                'semestre_id' => $nueva->semestre_id,
                'ciclo_id' => $nueva->ciclo_id,
                'activo' => $nueva->activo,
            ]);
        } else {
            $this->asegurarMatriculaHistoricaNivel(
                $alumno,
                $nueva->nivel_id,
                $destino['matricula'] ?? null,
                $fecha,
                $usuarioId,
                $origenRegistro,
                $nueva->generacion_id
            );
        }

        $this->registrarMovimiento(
            $nueva,
            'correccion_administrativa',
            $fecha,
            $motivo,
            null,
            $estadoAnterior,
            $this->snapshot($nueva),
            $usuarioId,
            $origen->id
        );

        return $nueva;
    }

    private function clonarEtapa(TrayectoriaAcademica $origen, array $cambios): TrayectoriaAcademica
    {
        return TrayectoriaAcademica::query()->create(array_merge([
            'inscripcion_id' => $origen->inscripcion_id,
            'ciclo_escolar_id' => $origen->ciclo_escolar_id,
            'ciclo_id' => $origen->ciclo_id,
            'nivel_id' => $origen->nivel_id,
            'grado_id' => $origen->grado_id,
            'generacion_id' => $origen->generacion_id,
            'grupo_id' => $origen->grupo_id,
            'semestre_id' => $origen->semestre_id,
            'activo' => $origen->activo,
            'estatus' => $origen->estatus,
            'fecha_baja' => $origen->fecha_baja,
            'motivo_baja' => $origen->motivo_baja,
            'observaciones_baja' => $origen->observaciones_baja,
            'fecha_inscripcion' => $origen->fecha_inscripcion,
            'fecha_inicio' => now(),
            'fecha_fin' => null,
            'numero_estancia' => $this->siguienteEstancia(
                $origen->inscripcion_id,
                $origen->ciclo_escolar_id,
                $origen->ciclo_id
            ),
            'vigente_en_corte' => true,
            'es_actual' => false,
            'origen' => 'registro',
            'tipo_ingreso' => $origen->tipo_ingreso,
            'continuidad' => $origen->continuidad,
            'escuela_procedencia' => $origen->escuela_procedencia,
            'cct_procedencia' => $origen->cct_procedencia,
            'ciclo_procedencia' => $origen->ciclo_procedencia,
            'ultimo_grado_procedencia' => $origen->ultimo_grado_procedencia,
            'observaciones_procedencia' => $origen->observaciones_procedencia,
            'documentacion_pendiente' => $origen->documentacion_pendiente,
            'datos_reconstruidos' => $origen->datos_reconstruidos,
            'promovido' => $origen->promovido,
            'fecha_promocion' => $origen->fecha_promocion,
            'trayectoria_origen_id' => $origen->id,
        ], $cambios));
    }

    private function trayectoriaVigente(
        int $inscripcionId,
        int $cicloEscolarId,
        int $cicloId,
        bool $bloquear = false
    ): TrayectoriaAcademica {
        $query = TrayectoriaAcademica::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('ciclo_id', $cicloId)
            ->where('vigente_en_corte', true)
            ->latest('numero_estancia');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        $trayectoria = $query->first();

        if (!$trayectoria) {
            throw ValidationException::withMessages([
                'trayectoria' => 'No existe una trayectoria vigente para el alumno en el ciclo escolar y corte seleccionados.',
            ]);
        }

        return $trayectoria;
    }

    private function registrarMovimiento(
        TrayectoriaAcademica $trayectoria,
        string $tipo,
        CarbonInterface $fecha,
        ?string $motivo,
        ?string $observaciones,
        ?array $estadoAnterior,
        ?array $estadoNuevo,
        int $usuarioId,
        ?int $trayectoriaOrigenId = null
    ): MovimientoAlumno {
        return MovimientoAlumno::query()->create([
            'inscripcion_id' => $trayectoria->inscripcion_id,
            'trayectoria_academica_id' => $trayectoria->id,
            'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
            'ciclo_id' => $trayectoria->ciclo_id,
            'trayectoria_origen_id' => $trayectoriaOrigenId,
            'tipo' => $tipo,
            'fecha' => $fecha->toDateString(),
            'motivo' => filled($motivo) ? trim((string) $motivo) : null,
            'observaciones' => filled($observaciones) ? trim((string) $observaciones) : null,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'registrado_por' => $usuarioId,
        ]);
    }

    private function snapshot(TrayectoriaAcademica $trayectoria): array
    {
        return [
            'trayectoria_id' => $trayectoria->id,
            'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
            'ciclo_id' => $trayectoria->ciclo_id,
            'nivel_id' => $trayectoria->nivel_id,
            'grado_id' => $trayectoria->grado_id,
            'generacion_id' => $trayectoria->generacion_id,
            'grupo_id' => $trayectoria->grupo_id,
            'semestre_id' => $trayectoria->semestre_id,
            'estatus' => $trayectoria->estatus,
            'activo' => (bool) $trayectoria->activo,
            'numero_estancia' => (int) $trayectoria->numero_estancia,
            'vigente_en_corte' => (bool) $trayectoria->vigente_en_corte,
            'es_actual' => (bool) $trayectoria->es_actual,
        ];
    }

    private function siguienteEstancia(int $inscripcionId, int $cicloEscolarId, int $cicloId): int
    {
        return ((int) TrayectoriaAcademica::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('ciclo_id', $cicloId)
            ->max('numero_estancia')) + 1;
    }

    private function resolverUsuario(?int $usuarioId): int
    {
        $usuarioId ??= auth()->id();
        $usuarioId ??= User::query()->where('is_admin', true)->value('id');
        $usuarioId ??= User::query()->value('id');

        if (!$usuarioId) {
            throw new \RuntimeException('No existe un usuario disponible para registrar el movimiento académico.');
        }

        return (int) $usuarioId;
    }

    private function fecha(CarbonInterface|string|null $fecha): CarbonInterface
    {
        if ($fecha instanceof CarbonInterface) {
            return $fecha;
        }

        return Carbon::parse($fecha ?: now());
    }
}
