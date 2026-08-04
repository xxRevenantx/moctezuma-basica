<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTutor;
use App\Models\Tutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ExpedienteTutorController extends Controller
{
    public function preview(DocumentoTutor $documento): StreamedResponse
    {
        $this->autorizar();
        abort_if($documento->es_fuente, 404);

        return $this->streamDocumento($documento, 'inline');
    }

    public function download(DocumentoTutor $documento): StreamedResponse
    {
        $this->autorizar();
        abort_if($documento->es_fuente, 404);

        return $this->streamDocumento($documento, 'attachment');
    }

    public function zip(Tutor $tutor): BinaryFileResponse
    {
        $this->autorizar();
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        $tutor->load([
            'documentos' => fn ($query) => $query
                ->where('es_actual', true)
                ->where('es_fuente', false)
                ->whereNotIn('estado', ['rechazado', 'reemplazado', 'cancelado'])
                ->with('tipoDocumento:id,nombre,slug,orden')
                ->orderBy('tipo_documento_tutor_id'),
            'relacionesActivas.inscripcion' => fn ($query) => $query
                ->withTrashed()
                ->select('inscripciones.id', 'nombre', 'apellido_paterno', 'apellido_materno', 'matricula'),
        ]);

        $documentos = $tutor->documentos->filter(fn (DocumentoTutor $documento): bool => $documento->archivo_existe);
        abort_if($documentos->isEmpty(), 404, 'El responsable todavía no tiene documentos disponibles para descargar.');

        $directorio = storage_path('app/private/expedientes-temporales/tutores');
        File::ensureDirectoryExists($directorio);
        $rutaZip = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP.');

        $temporales = [];
        try {
            foreach ($documentos as $indice => $documento) {
                $local = $this->rutaFisica($documento, $directorio, $temporales);
                $tipo = $documento->tipoDocumento?->nombre ?? 'Documento';
                $nombre = str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT)
                    . '_' . $this->nombreSeguro($tipo)
                    . '_v' . $documento->version
                    . '.' . ($documento->extension ?: 'pdf');
                $zip->addFile($local, 'Documentos_vigentes/' . $nombre);
            }

            $lineas = [
                'EXPEDIENTE DOCUMENTAL DEL RESPONSABLE',
                'Nombre: ' . $tutor->nombre_completo,
                'Identificador: ' . $tutor->identidad_protegida,
                'Fecha de exportación: ' . now()->format('d/m/Y H:i'),
                '',
                'ALUMNOS RELACIONADOS',
            ];
            foreach ($tutor->relacionesActivas as $relacion) {
                $alumno = $relacion->inscripcion;
                $lineas[] = '- ' . trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? ''))
                    . ' | Matrícula: ' . ($alumno?->matricula ?: '—')
                    . ' | Parentesco: ' . ($relacion->parentesco ?: 'Responsable');
            }
            if ($tutor->relacionesActivas->isEmpty()) {
                $lineas[] = '- Sin alumnos relacionados actualmente.';
            }
            $zip->addFromString('00_Informacion/Resumen.txt', implode(PHP_EOL, $lineas));
        } finally {
            $zip->close();
            foreach ($temporales as $temporal) {
                File::delete($temporal);
            }
        }

        $nombre = 'expediente-responsable-' . ($this->nombreSeguro($tutor->nombre_completo) ?: $tutor->id) . '.zip';

        return response()->download($rutaZip, $nombre)->deleteFileAfterSend(true);
    }

    private function streamDocumento(DocumentoTutor $documento, string $disposition): StreamedResponse
    {
        abort_unless($documento->archivo_existe, 404, 'El archivo físico ya no está disponible.');
        $disk = Storage::disk($documento->disco);
        $nombre = $this->nombreSeguro(
            ($documento->tipoDocumento?->nombre ?? 'documento') . '_v' . $documento->version . '.' . ($documento->extension ?: 'pdf')
        );

        return response()->stream(function () use ($disk, $documento): void {
            $stream = $disk->readStream($documento->ruta);
            if (! is_resource($stream)) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $documento->mime_type ?: 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $nombre . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function rutaFisica(DocumentoTutor $documento, string $directorio, array &$temporales): string
    {
        $disk = Storage::disk($documento->disco);
        try {
            $local = $disk->path($documento->ruta);
            if (is_file($local)) {
                return $local;
            }
        } catch (\Throwable) {
        }

        $local = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.' . ($documento->extension ?: 'pdf');
        File::put($local, $disk->get($documento->ruta));
        $temporales[] = $local;

        return $local;
    }

    private function nombreSeguro(string $nombre): string
    {
        return Str::of($nombre)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '_')
            ->trim('_')
            ->limit(180, '')
            ->toString() ?: 'archivo';
    }

    private function autorizar(): void
    {
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('documentos.consultar')
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('alumnos.consultar')
                || auth()->user()?->canAccess('alumnos.editar'),
            403
        );
    }
}
