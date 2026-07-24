<?php

namespace App\Http\Controllers;

use App\Models\DocumentoAlumno;
use App\Models\DocumentoAlumnoFuente;
use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DocumentoAlumnoFuenteController extends Controller
{
    public function preview(DocumentoAlumnoFuente $fuente): StreamedResponse
    {
        $this->autorizar('documentos.consultar');

        return $this->streamFuente($fuente, 'inline');
    }

    public function download(DocumentoAlumnoFuente $fuente): StreamedResponse
    {
        $this->autorizar('documentos.consultar');

        return $this->streamFuente($fuente, 'attachment');
    }

    public function page(
        Request $request,
        DocumentoAlumnoFuente $fuente,
        int $pagina,
        OrganizadorExpedienteService $service
    ): BinaryFileResponse {
        $this->autorizar('documentos.consultar');
        abort_unless($fuente->estado === 'activo' && ! $fuente->protegido, 404);
        $ruta = $service->rutaVistaPagina($fuente, $pagina, (int) $request->integer('rotacion', 0));

        return response()->file($ruta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pagina-' . $pagina . '.pdf"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function originals(DocumentoAlumno $documento): StreamedResponse|BinaryFileResponse
    {
        $this->autorizar('documentos.consultar');
        abort_unless($documento->organizacion_id, 404, 'El documento no proviene del organizador de páginas.');

        $documento->loadMissing('organizacion');
        $asignaciones = collect($documento->organizacion?->asignaciones ?? []);
        $contexto = implode('|', [
            $documento->tipo_documento_id,
            (int) ($documento->nivel_id ?? 0),
            (int) ($documento->grado_id ?? 0),
            (int) ($documento->grupo_id ?? 0),
            (int) ($documento->ciclo_escolar_id ?? 0),
        ]);
        $fuentesIds = $asignaciones
            ->where('contexto_clave', $contexto)
            ->pluck('fuente_id')
            ->unique()
            ->values();
        $fuentes = DocumentoAlumnoFuente::query()
            ->where('inscripcion_id', $documento->inscripcion_id)
            ->whereKey($fuentesIds->all())
            ->where('estado', 'activo')
            ->get();

        abort_if($fuentes->isEmpty(), 404, 'No se encontraron los archivos fuente de este documento.');

        if ($fuentes->count() === 1) {
            return $this->streamFuente($fuentes->first(), 'attachment');
        }

        $directorio = storage_path('app/temp/expedientes-organizador/descargas');
        File::ensureDirectoryExists($directorio);
        $rutaZip = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP de archivos originales.');
        $temporales = [];

        try {
            foreach ($fuentes as $indice => $fuente) {
                $disk = Storage::disk($fuente->disco);
                abort_unless($disk->exists($fuente->ruta_original ?: $fuente->ruta), 404, 'Uno de los archivos fuente ya no existe.');
                $ruta = $fuente->ruta_original ?: $fuente->ruta;
                try {
                    $local = $disk->path($ruta);
                } catch (\Throwable) {
                    $local = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.' . pathinfo($fuente->nombre_original, PATHINFO_EXTENSION);
                    File::put($local, $disk->get($ruta));
                    $temporales[] = $local;
                }
                $nombre = str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) . '_' . $this->nombreSeguro($fuente->nombre_original);
                $zip->addFile($local, $nombre);
            }
        } finally {
            $zip->close();
            foreach ($temporales as $temporal) {
                File::delete($temporal);
            }
        }

        return response()->download($rutaZip, 'archivos-originales-documento-' . $documento->id . '.zip')->deleteFileAfterSend(true);
    }

    protected function streamFuente(DocumentoAlumnoFuente $fuente, string $disposition): StreamedResponse
    {
        abort_unless($fuente->estado === 'activo', 404);
        $disk = Storage::disk($fuente->disco);
        $ruta = $fuente->ruta_original ?: $fuente->ruta;
        abort_unless($disk->exists($ruta), 404, 'El archivo fuente no existe.');
        $nombre = $this->nombreSeguro($fuente->nombre_original ?: basename($ruta));
        $mime = $fuente->mime_original ?: 'application/octet-stream';

        return response()->stream(function () use ($disk, $ruta): void {
            $stream = $disk->readStream($ruta);
            if (! is_resource($stream)) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition . '; filename="' . $nombre . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function nombreSeguro(string $nombre): string
    {
        return Str::of($nombre)
            ->replaceMatches('/[^\pL\pN._-]+/u', '_')
            ->trim('_')
            ->limit(220, '')
            ->toString() ?: 'archivo';
    }

    protected function autorizar(string $permiso): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess($permiso) || auth()->user()?->canAccess('documentos.organizar'), 403);
    }
}
