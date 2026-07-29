<?php

namespace App\Services;

use App\Models\IntegridadAcademicaAnalisis;
use App\Models\IntegridadAcademicaCaso;
use App\Models\IntegridadAcademicaEvento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class IntegridadAcademicaService
{
    public function __construct(
        private readonly IntegridadAcademicaDetectorService $detector,
    ) {
    }

    /** @return array<string,mixed> */
    public function ejecutar(?int $usuarioId = null, string $origen = 'manual', ?int $inscripcionId = null): array
    {
        if (! Schema::hasTable('integridad_academica_analisis') || ! Schema::hasTable('integridad_academica_casos')) {
            return [
                'disponible' => false,
                'mensaje' => 'Ejecuta las migraciones para activar el Centro de integridad académica.',
            ];
        }

        $analisis = IntegridadAcademicaAnalisis::query()->create([
            'uuid' => (string) Str::uuid(),
            'origen' => $origen,
            'estado' => 'procesando',
            'filtros' => $inscripcionId ? ['inscripcion_id' => $inscripcionId] : null,
            'ejecutado_por' => $usuarioId,
            'iniciado_at' => now(),
        ]);

        try {
            $detectados = $this->detector->detectar($inscripcionId);
            $resultado = DB::transaction(function () use ($analisis, $detectados, $usuarioId, $inscripcionId): array {
                $nuevos = 0;
                $actualizados = 0;
                $reabiertos = 0;
                $activos = [];

                foreach ($detectados as $dato) {
                    $activos[] = $dato['fingerprint'];
                    $caso = IntegridadAcademicaCaso::query()
                        ->lockForUpdate()
                        ->where('fingerprint', $dato['fingerprint'])
                        ->first();

                    if (! $caso) {
                        $caso = IntegridadAcademicaCaso::query()->create([
                            ...$dato,
                            'folio' => $this->folio(),
                            'estado' => IntegridadAcademicaCaso::ESTADO_PENDIENTE,
                            'ultimo_analisis_id' => $analisis->id,
                            'ocurrencias' => 1,
                            'primera_deteccion_at' => now(),
                            'ultima_deteccion_at' => now(),
                        ]);

                        $this->evento($caso, 'detectado', 'La inconsistencia fue detectada por primera vez.', $usuarioId, null, $dato);
                        $nuevos++;
                        continue;
                    }

                    $estadoAnterior = $caso->estado;
                    $reabrir = $estadoAnterior === IntegridadAcademicaCaso::ESTADO_RESUELTO;

                    $caso->fill([
                        'regla' => $dato['regla'],
                        'categoria' => $dato['categoria'],
                        'severidad' => $dato['severidad'],
                        'inscripcion_id' => $dato['inscripcion_id'],
                        'inscripcion_ciclo_id' => $dato['inscripcion_ciclo_id'],
                        'ciclo_escolar_id' => $dato['ciclo_escolar_id'],
                        'nivel_id' => $dato['nivel_id'],
                        'titulo' => $dato['titulo'],
                        'descripcion' => $dato['descripcion'],
                        'evidencia' => $dato['evidencia'],
                        'correccion_sugerida' => $dato['correccion_sugerida'],
                        'metadata' => $dato['metadata'],
                        'ultimo_analisis_id' => $analisis->id,
                        'ultima_deteccion_at' => now(),
                        'ocurrencias' => $caso->ocurrencias + 1,
                    ]);

                    if ($reabrir) {
                        $caso->fill([
                            'estado' => IntegridadAcademicaCaso::ESTADO_PENDIENTE,
                            'resuelto_at' => null,
                            'resuelto_por' => null,
                            'motivo_resolucion' => null,
                        ]);
                        $reabiertos++;
                    }

                    $caso->save();

                    if ($reabrir) {
                        $this->evento($caso, 'reabierto', 'La inconsistencia volvió a aparecer en una auditoría posterior.', $usuarioId, ['estado' => $estadoAnterior], ['estado' => $caso->estado]);
                    }
                    $actualizados++;
                }

                $resolver = IntegridadAcademicaCaso::query()
                    ->lockForUpdate()
                    ->abiertos()
                    ->when($inscripcionId, fn ($query) => $query->where('inscripcion_id', $inscripcionId))
                    ->when($activos !== [], fn ($query) => $query->whereNotIn('fingerprint', $activos));

                $resueltos = 0;
                foreach ($resolver->get() as $caso) {
                    $anterior = ['estado' => $caso->estado, 'evidencia' => $caso->evidencia];
                    $caso->update([
                        'estado' => IntegridadAcademicaCaso::ESTADO_RESUELTO,
                        'resuelto_at' => now(),
                        'resuelto_por' => null,
                        'motivo_resolucion' => 'La inconsistencia ya no fue detectada por el análisis automático.',
                        'ultimo_analisis_id' => $analisis->id,
                    ]);
                    $this->evento($caso, 'resuelto_automaticamente', 'El análisis confirmó que la inconsistencia dejó de existir.', $usuarioId, $anterior, ['estado' => $caso->estado]);
                    $resueltos++;
                }

                $resumen = $this->resumenActual();
                $analisis->update([
                    'estado' => 'completado',
                    'resumen' => $resumen,
                    'total_detectados' => count($detectados),
                    'nuevos' => $nuevos,
                    'actualizados' => $actualizados,
                    'reabiertos' => $reabiertos,
                    'resueltos_automaticamente' => $resueltos,
                    'finalizado_at' => now(),
                ]);

                return [
                    'disponible' => true,
                    'analisis_id' => $analisis->id,
                    'uuid' => $analisis->uuid,
                    'detectados' => count($detectados),
                    'nuevos' => $nuevos,
                    'actualizados' => $actualizados,
                    'reabiertos' => $reabiertos,
                    'resueltos_automaticamente' => $resueltos,
                    'resumen' => $resumen,
                ];
            });

            return $resultado;
        } catch (Throwable $exception) {
            report($exception);
            $analisis->update([
                'estado' => 'fallido',
                'error' => mb_substr($exception->getMessage(), 0, 4000),
                'finalizado_at' => now(),
            ]);

            throw $exception;
        }
    }

    /** @return array<string,int> */
    public function resumenActual(): array
    {
        if (! Schema::hasTable('integridad_academica_casos')) {
            return [
                'abiertos' => 0, 'criticos' => 0, 'advertencias' => 0, 'informativos' => 0,
                'en_revision' => 0, 'resueltos' => 0, 'ignorados' => 0,
            ];
        }

        $base = IntegridadAcademicaCaso::query();

        return [
            'abiertos' => (clone $base)->abiertos()->count(),
            'criticos' => (clone $base)->abiertos()->where('severidad', 'critico')->count(),
            'advertencias' => (clone $base)->abiertos()->where('severidad', 'advertencia')->count(),
            'informativos' => (clone $base)->abiertos()->where('severidad', 'informativo')->count(),
            'en_revision' => (clone $base)->where('estado', IntegridadAcademicaCaso::ESTADO_REVISION)->count(),
            'resueltos' => (clone $base)->where('estado', IntegridadAcademicaCaso::ESTADO_RESUELTO)->count(),
            'ignorados' => (clone $base)->where('estado', IntegridadAcademicaCaso::ESTADO_IGNORADO)->count(),
        ];
    }

    public function registrarEvento(
        IntegridadAcademicaCaso $caso,
        string $tipo,
        ?string $descripcion,
        ?int $usuarioId,
        ?array $anteriores = null,
        ?array $nuevos = null,
        ?array $metadata = null,
    ): void {
        $this->evento($caso, $tipo, $descripcion, $usuarioId, $anteriores, $nuevos, $metadata);
    }

    private function evento(
        IntegridadAcademicaCaso $caso,
        string $tipo,
        ?string $descripcion,
        ?int $usuarioId,
        ?array $anteriores = null,
        ?array $nuevos = null,
        ?array $metadata = null,
    ): void {
        IntegridadAcademicaEvento::query()->create([
            'caso_id' => $caso->id,
            'user_id' => $usuarioId,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'valores_anteriores' => $anteriores,
            'valores_nuevos' => $nuevos,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function folio(): string
    {
        return 'INT-'.now()->format('Y').'-'.strtoupper(substr((string) Str::ulid(), -12));
    }
}
