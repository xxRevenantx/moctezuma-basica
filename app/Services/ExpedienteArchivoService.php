<?php

namespace App\Services;

use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use App\Models\TipoDocumento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ExpedienteArchivoService
{
    /** Guarda un PDF generado dentro del expediente privado del alumno. */
    public function guardarPdfGenerado(
        Inscripcion $alumno,
        string $tipoSlug,
        string $contenidoPdf,
        array $metadatos = []
    ): DocumentoAlumno {
        $tipo = TipoDocumento::query()->where('slug', $tipoSlug)->where('activo', true)->firstOrFail();
        $usuarioId = auth()->id();
        if (! $usuarioId) throw new RuntimeException('No hay un usuario autenticado para registrar el documento generado.');

        $rutaGuardada = null;
        $discoExpedientes = config('filesystems.expedientes_disk', 'local');

        try {
            return DB::transaction(function () use ($alumno, $tipo, $contenidoPdf, $metadatos, $usuarioId, $discoExpedientes, &$rutaGuardada) {
                $folio = trim((string) ($metadatos['folio'] ?? '')) ?: null;
                $nombreBase = $folio ?: ($tipo->slug . '-' . now()->format('Ymd-His'));
                $directorio = 'expedientes/' . $alumno->id . '/' . $tipo->slug . '/generados/' . now()->format('Y');
                $rutaGuardada = $directorio . '/' . Str::uuid() . '.pdf';

                $disco = Storage::disk($discoExpedientes);

                if (! $disco->put($rutaGuardada, $contenidoPdf)) {
                    throw new RuntimeException('No fue posible guardar el PDF generado.');
                }

                try {
                    $archivoConfirmado = $disco->exists($rutaGuardada);
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        'El PDF se intentó guardar, pero no fue posible comprobarlo en el almacenamiento.',
                        previous: $e
                    );
                }

                if (! $archivoConfirmado) {
                    throw new RuntimeException('El almacenamiento no confirmó la existencia del PDF generado.');
                }

                return DocumentoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'tipo_documento_id' => $tipo->id,
                    'nivel_id' => $metadatos['nivel_id'] ?? $alumno->nivel_id,
                    'grado_id' => $metadatos['grado_id'] ?? $alumno->grado_id,
                    'grupo_id' => $metadatos['grupo_id'] ?? $alumno->grupo_id,
                    'ciclo_escolar_id' => $metadatos['ciclo_escolar_id'] ?? null,
                    'fecha_documento' => $metadatos['fecha_documento'] ?? now()->toDateString(),
                    'folio' => $folio,
                    'origen' => 'generado',
                    'tipo_movimiento' => $metadatos['tipo_movimiento'] ?? null,
                    'motivo' => $metadatos['motivo'] ?? null,
                    'disco' => $discoExpedientes,
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
                try {
                    Storage::disk($discoExpedientes)->delete($rutaGuardada);
                } catch (Throwable $limpiezaError) {
                    report($limpiezaError);
                }
            }

            throw $e;
        }
    }
}
