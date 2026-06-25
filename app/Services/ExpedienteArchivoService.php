<?php

namespace App\Services;

use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use App\Models\TipoDocumento;
use App\Models\TrayectoriaAcademica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ExpedienteArchivoService
{
    /**
     * Guarda un PDF generado por el sistema dentro del expediente privado.
     * Las constancias no reemplazan otras constancias: cada folio es un documento histórico independiente.
     */
    public function guardarPdfGenerado(
        Inscripcion $alumno,
        string $tipoSlug,
        string $contenidoPdf,
        array $metadatos = []
    ): DocumentoAlumno {
        $tipo = TipoDocumento::query()
            ->where('slug', $tipoSlug)
            ->where('activo', true)
            ->firstOrFail();

        $usuarioId = auth()->id();

        if (!$usuarioId) {
            throw new RuntimeException('No hay un usuario autenticado para registrar el documento generado.');
        }

        $rutaGuardada = null;

        try {
            return DB::transaction(function () use ($alumno, $tipo, $contenidoPdf, $metadatos, $usuarioId, &$rutaGuardada) {
                $nivelId = $metadatos['nivel_id'] ?? $alumno->nivel_id;
                $gradoId = $metadatos['grado_id'] ?? $alumno->grado_id;
                $grupoId = $metadatos['grupo_id'] ?? $alumno->grupo_id;
                $cicloEscolarId = $metadatos['ciclo_escolar_id'] ?? null;
                $trayectoriaId = $metadatos['trayectoria_academica_id'] ?? null;

                if (!$trayectoriaId) {
                    $trayectoria = TrayectoriaAcademica::query()
                        ->where('inscripcion_id', $alumno->id)
                        ->when($nivelId, fn(Builder $q) => $q->where('nivel_id', $nivelId))
                        ->latest('id')
                        ->first();

                    $trayectoriaId = $trayectoria?->id;
                    $cicloEscolarId ??= $trayectoria?->ciclo_escolar_id;
                    $gradoId ??= $trayectoria?->grado_id;
                    $grupoId ??= $trayectoria?->grupo_id;
                }

                $folio = trim((string) ($metadatos['folio'] ?? '')) ?: null;
                $nombreBase = $folio ?: ($tipo->slug . '-' . now()->format('Ymd-His'));
                $directorio = 'expedientes/' . $alumno->id . '/' . $tipo->slug . '/generados/' . now()->format('Y');
                $rutaGuardada = $directorio . '/' . Str::uuid() . '.pdf';

                if (!Storage::disk('local')->put($rutaGuardada, $contenidoPdf)) {
                    throw new RuntimeException('No fue posible guardar el PDF generado.');
                }

                return DocumentoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'tipo_documento_id' => $tipo->id,
                    'nivel_id' => $nivelId,
                    'grado_id' => $gradoId,
                    'grupo_id' => $grupoId,
                    'ciclo_escolar_id' => $cicloEscolarId,
                    'trayectoria_academica_id' => $trayectoriaId,
                    'fecha_documento' => $metadatos['fecha_documento'] ?? now()->toDateString(),
                    'folio' => $folio,
                    'origen' => 'generado',
                    'tipo_movimiento' => $metadatos['tipo_movimiento'] ?? null,
                    'motivo' => $metadatos['motivo'] ?? null,
                    'disco' => 'local',
                    'ruta' => $rutaGuardada,
                    'nombre_original' => Str::slug($nombreBase, '_') . '.pdf',
                    'mime_type' => 'application/pdf',
                    'tamano_bytes' => strlen($contenidoPdf),
                    'hash_sha256' => hash('sha256', $contenidoPdf),
                    'version' => 1,
                    'es_actual' => true,
                    'estado' => $metadatos['estado'] ?? 'emitida',
                    'observaciones' => $metadatos['observaciones'] ?? null,
                    'subido_por' => $usuarioId,
                ]);
            });
        } catch (Throwable $e) {
            if ($rutaGuardada) {
                Storage::disk('local')->delete($rutaGuardada);
            }

            throw $e;
        }
    }
}
