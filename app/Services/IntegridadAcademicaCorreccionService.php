<?php

namespace App\Services;

use App\Models\IntegridadAcademicaCaso;
use App\Models\IntegridadAcademicaCorreccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class IntegridadAcademicaCorreccionService
{
    public function __construct(
        private readonly IntegridadAcademicaDetectorService $detector,
        private readonly IntegridadAcademicaService $integridad,
        private readonly SystemAuditService $auditoria,
    ) {
    }

    public function aplicar(IntegridadAcademicaCaso $caso, string $motivo, int $usuarioId): IntegridadAcademicaCorreccion
    {
        if (mb_strlen(trim($motivo)) < 10) {
            throw new RuntimeException('Registra un motivo de al menos 10 caracteres.');
        }

        $detectadoActual = collect($this->detector->detectar($caso->inscripcion_id))
            ->first(fn (array $detectado): bool => $detectado['fingerprint'] === $caso->fingerprint);

        if (! $detectadoActual) {
            $caso->update([
                'estado' => IntegridadAcademicaCaso::ESTADO_RESUELTO,
                'resuelto_at' => now(),
                'motivo_resolucion' => 'La inconsistencia dejó de existir antes de aplicar la corrección.',
            ]);
            throw new RuntimeException('La información cambió y el caso ya no está vigente. Ejecuta nuevamente el análisis.');
        }

        $sugerencia = (array) ($detectadoActual['correccion_sugerida'] ?? []);
        $clave = (string) data_get($sugerencia, 'clave');
        $parametros = (array) data_get($sugerencia, 'parametros', []);

        if (! in_array($clave, $this->clavesPermitidas(), true)) {
            throw new RuntimeException('El caso no tiene una corrección asistida disponible.');
        }

        $caso->forceFill([
            'evidencia' => $detectadoActual['evidencia'] ?? $caso->evidencia,
            'correccion_sugerida' => $sugerencia,
        ])->save();

        return DB::transaction(function () use ($caso, $clave, $parametros, $motivo, $usuarioId): IntegridadAcademicaCorreccion {
            $caso = IntegridadAcademicaCaso::query()->lockForUpdate()->findOrFail($caso->id);
            if (! in_array($caso->estado, [IntegridadAcademicaCaso::ESTADO_PENDIENTE, IntegridadAcademicaCaso::ESTADO_REVISION], true)) {
                throw new RuntimeException('El caso ya no está disponible para corrección.');
            }

            $respaldo = $this->snapshot($clave, $parametros, true);
            $this->ejecutar($clave, $parametros, $motivo, $usuarioId);
            $resultado = $this->snapshot($clave, $parametros, false);

            $uuid = (string) Str::uuid();
            $firma = $this->firmar([
                'uuid' => $uuid,
                'caso_id' => $caso->id,
                'clave' => $clave,
                'parametros' => $parametros,
                'respaldo' => $respaldo,
                'resultado' => $resultado,
            ]);

            $correccion = IntegridadAcademicaCorreccion::query()->create([
                'uuid' => $uuid,
                'caso_id' => $caso->id,
                'clave' => $clave,
                'estado' => 'aplicada',
                'motivo' => trim($motivo),
                'parametros' => $parametros,
                'respaldo_anterior' => $respaldo,
                'resultado_aplicado' => $resultado,
                'firma' => $firma,
                'aplicada_por' => $usuarioId,
                'aplicada_at' => now(),
            ]);

            $estadoAnterior = $caso->only(['estado', 'evidencia']);
            $caso->update([
                'estado' => IntegridadAcademicaCaso::ESTADO_RESUELTO,
                'resuelto_at' => now(),
                'resuelto_por' => $usuarioId,
                'motivo_resolucion' => 'Corrección asistida aplicada: '.trim($motivo),
            ]);

            $this->integridad->registrarEvento(
                $caso,
                'correccion_aplicada',
                'Se aplicó una corrección asistida con respaldo firmado.',
                $usuarioId,
                $estadoAnterior,
                ['estado' => $caso->estado, 'correccion_id' => $correccion->id, 'clave' => $clave],
            );
            $this->auditoria->record('academic_integrity_correction_applied', 'integridad', [
                'caso_id' => $caso->id,
                'folio' => $caso->folio,
                'correccion_id' => $correccion->id,
                'clave' => $clave,
            ]);

            return $correccion;
        });
    }

    public function revertir(IntegridadAcademicaCorreccion $correccion, string $motivo, int $usuarioId): IntegridadAcademicaCorreccion
    {
        if (mb_strlen(trim($motivo)) < 10) {
            throw new RuntimeException('Registra un motivo de reversión de al menos 10 caracteres.');
        }

        return DB::transaction(function () use ($correccion, $motivo, $usuarioId): IntegridadAcademicaCorreccion {
            $correccion = IntegridadAcademicaCorreccion::query()->lockForUpdate()->with('caso')->findOrFail($correccion->id);
            if (! $correccion->puede_revertirse) {
                throw new RuntimeException('La corrección ya fue revertida o no está disponible.');
            }

            $esperada = $this->firmar([
                'uuid' => $correccion->uuid,
                'caso_id' => $correccion->caso_id,
                'clave' => $correccion->clave,
                'parametros' => $correccion->parametros,
                'respaldo' => $correccion->respaldo_anterior,
                'resultado' => $correccion->resultado_aplicado,
            ]);
            if (! hash_equals($esperada, (string) $correccion->firma)) {
                $correccion->update(['bloqueo_reversion' => 'La firma del respaldo no coincide.']);
                throw new RuntimeException('No se puede revertir porque el respaldo no superó la verificación de integridad.');
            }

            $parametros = (array) ($correccion->parametros ?? []);
            $actual = $this->snapshot($correccion->clave, $parametros, false);
            if ($this->normalizar($actual) !== $this->normalizar($correccion->resultado_aplicado ?? [])) {
                $correccion->update(['bloqueo_reversion' => 'Los registros fueron modificados después de la corrección.']);
                throw new RuntimeException('Los datos cambiaron después de la corrección. La reversión se bloqueó para no sobrescribir trabajo posterior.');
            }

            $this->restaurar($correccion->clave, $correccion->respaldo_anterior ?? [], $usuarioId);

            $correccion->update([
                'estado' => 'revertida',
                'revertida_por' => $usuarioId,
                'revertida_at' => now(),
                'motivo_reversion' => trim($motivo),
                'bloqueo_reversion' => null,
            ]);

            $caso = $correccion->caso;
            if ($caso) {
                $caso->update([
                    'estado' => IntegridadAcademicaCaso::ESTADO_PENDIENTE,
                    'resuelto_at' => null,
                    'resuelto_por' => null,
                    'motivo_resolucion' => null,
                ]);
                $this->integridad->registrarEvento(
                    $caso,
                    'correccion_revertida',
                    'La corrección fue revertida desde su respaldo firmado.',
                    $usuarioId,
                    ['correccion_id' => $correccion->id, 'estado' => 'aplicada'],
                    ['correccion_id' => $correccion->id, 'estado' => 'revertida'],
                );
            }

            $this->auditoria->record('academic_integrity_correction_reverted', 'integridad', [
                'caso_id' => $correccion->caso_id,
                'correccion_id' => $correccion->id,
                'clave' => $correccion->clave,
            ]);

            return $correccion->fresh();
        });
    }

    /** @return array<int,string> */
    private function clavesPermitidas(): array
    {
        return [
            'conservar_asignacion_actual_mas_reciente',
            'alinear_inscripcion_con_historial',
            'normalizar_estatus_egresado',
            'vincular_calificaciones_con_historial',
            'alinear_matricula_vigente',
        ];
    }

    /** @return array<string,mixed> */
    private function snapshot(string $clave, array $parametros, bool $antes): array
    {
        return match ($clave) {
            'conservar_asignacion_actual_mas_reciente' => [
                'asignaciones' => DB::table('inscripcion_ciclo_asignaciones')
                    ->whereIn('id', array_values(array_unique([
                        (int) data_get($parametros, 'asignacion_principal_id'),
                        ...array_map('intval', (array) data_get($parametros, 'asignaciones_cerrar', [])),
                    ])))
                    ->orderBy('id')
                    ->get(['id', 'es_actual', 'fecha_fin', 'motivo', 'updated_at'])
                    ->map(fn ($fila) => (array) $fila)->all(),
            ],
            'alinear_inscripcion_con_historial' => [
                'inscripcion' => (array) DB::table('inscripciones')
                    ->where('id', (int) data_get($parametros, 'inscripcion_id'))
                    ->first(['id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id', 'ciclo_escolar_id', 'updated_at']),
            ],
            'normalizar_estatus_egresado' => [
                'inscripcion' => (array) DB::table('inscripciones')
                    ->where('id', (int) data_get($parametros, 'inscripcion_id'))
                    ->first(['id', 'estatus', 'activo', 'fecha_estatus', 'motivo_estatus', 'updated_at']),
            ],
            'vincular_calificaciones_con_historial' => [
                'calificaciones' => DB::table('calificaciones')
                    ->whereIn('id', array_map('intval', array_keys((array) data_get($parametros, 'mapeos', []))))
                    ->orderBy('id')
                    ->get(['id', 'inscripcion_ciclo_id', 'updated_at'])
                    ->map(fn ($fila) => (array) $fila)->all(),
            ],
            'alinear_matricula_vigente' => [
                'inscripcion' => (array) DB::table('inscripciones')
                    ->where('id', (int) data_get($parametros, 'inscripcion_id'))
                    ->first(['id', 'matricula', 'updated_at']),
            ],
            default => throw new RuntimeException('Tipo de corrección no soportado.'),
        };
    }

    private function ejecutar(string $clave, array $parametros, string $motivo, int $usuarioId): void
    {
        match ($clave) {
            'conservar_asignacion_actual_mas_reciente' => $this->conservarAsignacion($parametros, $motivo),
            'alinear_inscripcion_con_historial' => $this->alinearInscripcion($parametros),
            'normalizar_estatus_egresado' => $this->normalizarEgresado($parametros, $motivo),
            'vincular_calificaciones_con_historial' => $this->vincularCalificaciones($parametros),
            'alinear_matricula_vigente' => $this->alinearMatricula($parametros),
            default => throw new RuntimeException('Tipo de corrección no soportado.'),
        };
    }

    private function conservarAsignacion(array $parametros, string $motivo): void
    {
        $principalId = (int) data_get($parametros, 'asignacion_principal_id');
        $principal = DB::table('inscripcion_ciclo_asignaciones')->lockForUpdate()->find($principalId);
        if (! $principal) {
            throw new RuntimeException('La asignación principal ya no existe.');
        }

        $cerrar = array_map('intval', (array) data_get($parametros, 'asignaciones_cerrar', []));
        $anteriores = DB::table('inscripcion_ciclo_asignaciones')->whereIn('id', $cerrar)->lockForUpdate()->get(['id', 'motivo']);
        foreach ($anteriores as $anterior) {
            $detalle = trim(implode(' | ', array_filter([(string) $anterior->motivo, 'Integridad: '.trim($motivo)])));
            DB::table('inscripcion_ciclo_asignaciones')->where('id', $anterior->id)->update([
                'es_actual' => false,
                'fecha_fin' => $principal->fecha_inicio,
                'motivo' => $detalle,
                'updated_at' => now(),
            ]);
        }
        DB::table('inscripcion_ciclo_asignaciones')->where('id', $principalId)->update([
            'es_actual' => true,
            'fecha_fin' => null,
            'updated_at' => now(),
        ]);
    }

    private function alinearInscripcion(array $parametros): void
    {
        $id = (int) data_get($parametros, 'inscripcion_id');
        $esperados = array_intersect_key((array) data_get($parametros, 'valores_esperados', []), array_flip([
            'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id', 'ciclo_escolar_id',
        ]));
        DB::table('inscripciones')->where('id', $id)->lockForUpdate()->first();
        DB::table('inscripciones')->where('id', $id)->update([...$esperados, 'updated_at' => now()]);
    }

    private function normalizarEgresado(array $parametros, string $motivo): void
    {
        $id = (int) data_get($parametros, 'inscripcion_id');
        DB::table('inscripciones')->where('id', $id)->lockForUpdate()->first();
        DB::table('inscripciones')->where('id', $id)->update([
            'estatus' => 'egresado',
            'activo' => false,
            'fecha_estatus' => now(),
            'motivo_estatus' => trim($motivo),
            'updated_at' => now(),
        ]);
    }

    private function vincularCalificaciones(array $parametros): void
    {
        foreach ((array) data_get($parametros, 'mapeos', []) as $calificacionId => $historialId) {
            $calificacion = DB::table('calificaciones')->where('id', (int) $calificacionId)->lockForUpdate()->first(['id', 'inscripcion_id', 'ciclo_escolar_id']);
            $historial = DB::table('inscripcion_ciclos')->where('id', (int) $historialId)->first(['id', 'inscripcion_id', 'ciclo_escolar_id']);
            $valido = $calificacion && $historial
                && (int) $calificacion->inscripcion_id === (int) $historial->inscripcion_id
                && (int) $calificacion->ciclo_escolar_id === (int) $historial->ciclo_escolar_id;
            if (! $valido) {
                throw new RuntimeException("La calificación #{$calificacionId} ya no coincide con el historial propuesto.");
            }
            DB::table('calificaciones')->where('id', (int) $calificacionId)->update([
                'inscripcion_ciclo_id' => (int) $historialId,
                'updated_at' => now(),
            ]);
        }
    }

    private function alinearMatricula(array $parametros): void
    {
        $id = (int) data_get($parametros, 'inscripcion_id');
        $matricula = trim((string) data_get($parametros, 'matricula'));
        $ocupada = DB::table('inscripciones')->where('matricula', $matricula)->where('id', '!=', $id)->exists();
        if ($ocupada) {
            throw new RuntimeException('La matrícula propuesta ya pertenece a otro alumno.');
        }
        DB::table('inscripciones')->where('id', $id)->lockForUpdate()->first();
        DB::table('inscripciones')->where('id', $id)->update(['matricula' => $matricula, 'updated_at' => now()]);
    }

    private function restaurar(string $clave, array $respaldo, int $usuarioId): void
    {
        match ($clave) {
            'conservar_asignacion_actual_mas_reciente' => $this->restaurarFilas('inscripcion_ciclo_asignaciones', (array) data_get($respaldo, 'asignaciones', []), ['es_actual', 'fecha_fin', 'motivo', 'updated_at']),
            'alinear_inscripcion_con_historial' => $this->restaurarFila('inscripciones', (array) data_get($respaldo, 'inscripcion', []), ['nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id', 'ciclo_escolar_id', 'updated_at']),
            'normalizar_estatus_egresado' => $this->restaurarFila('inscripciones', (array) data_get($respaldo, 'inscripcion', []), ['estatus', 'activo', 'fecha_estatus', 'motivo_estatus', 'updated_at']),
            'vincular_calificaciones_con_historial' => $this->restaurarFilas('calificaciones', (array) data_get($respaldo, 'calificaciones', []), ['inscripcion_ciclo_id', 'updated_at']),
            'alinear_matricula_vigente' => $this->restaurarFila('inscripciones', (array) data_get($respaldo, 'inscripcion', []), ['matricula', 'updated_at']),
            default => throw new RuntimeException('Tipo de corrección no soportado.'),
        };
    }

    private function restaurarFilas(string $tabla, array $filas, array $campos): void
    {
        foreach ($filas as $fila) {
            $this->restaurarFila($tabla, (array) $fila, $campos);
        }
    }

    private function restaurarFila(string $tabla, array $fila, array $campos): void
    {
        $id = (int) ($fila['id'] ?? 0);
        if (! $id) {
            throw new RuntimeException('El respaldo no contiene el identificador del registro.');
        }
        $valores = array_intersect_key($fila, array_flip($campos));
        DB::table($tabla)->where('id', $id)->update($valores);
    }

    private function firmar(array $datos): string
    {
        return hash_hmac(
            'sha256',
            json_encode($this->normalizar($datos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) config('app.key')
        );
    }

    private function normalizar(array $datos): array
    {
        if (array_is_list($datos)) {
            return array_map(fn ($item) => is_array($item) ? $this->normalizar($item) : $item, $datos);
        }
        ksort($datos);
        foreach ($datos as $clave => $valor) {
            if (is_array($valor)) {
                $datos[$clave] = $this->normalizar($valor);
            }
        }
        return $datos;
    }
}
