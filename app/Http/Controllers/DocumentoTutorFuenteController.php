<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTutorFuente;
use App\Services\Expedientes\ExpedienteTutorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoTutorFuenteController extends Controller
{
    public function preview(DocumentoTutorFuente $fuente): StreamedResponse
    {
        $this->autorizar();

        return $this->streamFuente($fuente, 'inline');
    }

    public function download(DocumentoTutorFuente $fuente): StreamedResponse
    {
        $this->autorizar();

        return $this->streamFuente($fuente, 'attachment');
    }

    public function page(
        Request $request,
        DocumentoTutorFuente $fuente,
        int $pagina,
        ExpedienteTutorService $service
    ): BinaryFileResponse {
        $this->autorizar();
        abort_unless($fuente->estado === 'activo' && ! $fuente->protegido, 404);
        $ruta = $service->rutaVistaPagina($fuente, $pagina, (int) $request->integer('rotation', $request->integer('rotacion', 0)));

        return response()->file($ruta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pagina-' . $pagina . '.pdf"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function streamFuente(DocumentoTutorFuente $fuente, string $disposition): StreamedResponse
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

    private function nombreSeguro(string $nombre): string
    {
        return Str::of($nombre)
            ->replaceMatches('/[^\pL\pN._-]+/u', '_')
            ->trim('_')
            ->limit(220, '')
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
