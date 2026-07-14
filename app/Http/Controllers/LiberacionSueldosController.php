<?php

namespace App\Http\Controllers;

use App\Models\LiberacionSueldo;
use App\Services\LiberacionSueldosArchivoService;
use App\Services\LiberacionSueldosDocumentoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class LiberacionSueldosController extends Controller
{
    public function preview(Request $request, string $token, LiberacionSueldosDocumentoService $documentos)
    {
        abort_unless((int) session("liberacion_sueldos_preview.{$token}.usuario_id") === (int) $request->user()?->id, 403);
        $datos = session("liberacion_sueldos_preview.{$token}.documentos", []);
        abort_if(empty($datos), 404, 'La vista previa expiró. Genérala nuevamente.');

        $coleccion = collect($datos)->map(fn(array $item) => $documentos->aArray($item));

        return Pdf::loadView('pdf.liberacion-sueldos', ['documentos' => $coleccion])
            ->setPaper('letter', 'portrait')
            ->stream('vista-previa-liberacion-sueldos.pdf');
    }

    public function descargar(
        Request $request,
        string $formato,
        LiberacionSueldosDocumentoService $documentos,
        LiberacionSueldosArchivoService $archivos,
    ) {
        abort_unless(in_array($formato, ['pdf', 'word', 'zip'], true), 404);

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 422, 'No se seleccionaron liberaciones.');

        $liberaciones = LiberacionSueldo::query()
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . $ids->implode(',') . ')')
            ->get();

        abort_if($liberaciones->isEmpty(), 404);

        if ($liberaciones->count() === 1 && in_array($formato, ['pdf', 'word'], true)) {
            $liberacion = $liberaciones->first();
            $campo = $formato === 'pdf' ? 'archivo_pdf_path' : 'archivo_word_path';
            $path = $liberacion->{$campo};

            if (! $path || ! Storage::disk('local')->exists($path)) {
                $liberacion = $archivos->guardar($liberacion);
                $path = $liberacion->{$campo};
            }

            $extension = $formato === 'pdf' ? 'pdf' : 'docx';
            $nombre = 'liberacion-sueldos-' . Str::slug($liberacion->trabajador_nombre) . '.' . $extension;

            return Storage::disk('local')->download($path, $nombre);
        }

        return match ($formato) {
            'pdf' => $this->pdf($liberaciones, $documentos),
            'word' => $this->word($liberaciones, $documentos),
            'zip' => $this->zip($liberaciones, $documentos),
        };
    }

    /** @param Collection<int, LiberacionSueldo> $liberaciones */
    private function pdf(Collection $liberaciones, LiberacionSueldosDocumentoService $documentos)
    {
        $datos = $liberaciones->map(fn(LiberacionSueldo $item) => $documentos->aArray($item));
        $nombre = $liberaciones->count() === 1
            ? 'liberacion-sueldos-' . Str::slug($liberaciones->first()->trabajador_nombre) . '.pdf'
            : 'liberaciones-sueldos-' . now()->format('Ymd-His') . '.pdf';

        return Pdf::loadView('pdf.liberacion-sueldos', ['documentos' => $datos])
            ->setPaper('letter', 'portrait')
            ->download($nombre);
    }

    /** @param Collection<int, LiberacionSueldo> $liberaciones */
    private function word(Collection $liberaciones, LiberacionSueldosDocumentoService $documentos): BinaryFileResponse
    {
        $ruta = storage_path('app/temp/liberaciones-sueldos-' . Str::uuid() . '.docx');
        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $documentos->crearWord($liberaciones, $ruta);

        return response()->download($ruta, 'liberaciones-sueldos-' . now()->format('Ymd-His') . '.docx')->deleteFileAfterSend(true);
    }

    /** @param Collection<int, LiberacionSueldo> $liberaciones */
    private function zip(Collection $liberaciones, LiberacionSueldosDocumentoService $documentos): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP no está habilitada en PHP.');

        $ruta = storage_path('app/temp/liberaciones-sueldos-' . Str::uuid() . '.zip');
        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $zip = new ZipArchive();
        abort_unless($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el archivo ZIP.');

        foreach ($liberaciones as $liberacion) {
            $datos = collect([$documentos->aArray($liberacion)]);
            $contenido = Pdf::loadView('pdf.liberacion-sueldos', ['documentos' => $datos])
                ->setPaper('letter', 'portrait')
                ->output();
            $nombre = 'liberacion-' . Str::slug($liberacion->trabajador_nombre) . '-' . $liberacion->id . '.pdf';
            $zip->addFromString($nombre, $contenido);
        }

        $zip->close();

        return response()->download($ruta, 'liberaciones-sueldos-individuales-' . now()->format('Ymd-His') . '.zip')->deleteFileAfterSend(true);
    }
}
