<?php

namespace App\Services;

use App\Models\LiberacionSueldo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LiberacionSueldosArchivoService
{
    public function __construct(
        private readonly LiberacionSueldosDocumentoService $documentos,
    ) {
    }

    public function guardar(LiberacionSueldo $liberacion): LiberacionSueldo
    {
        $base = 'liberaciones-sueldos/' . $liberacion->anio . '/' . $liberacion->id;
        $pdfPath = $base . '/liberacion-' . Str::slug($liberacion->trabajador_nombre) . '.pdf';
        $wordPath = $base . '/liberacion-' . Str::slug($liberacion->trabajador_nombre) . '.docx';

        $datos = collect([$this->documentos->aArray($liberacion)]);
        $pdf = Pdf::loadView('pdf.liberacion-sueldos', ['documentos' => $datos])
            ->setPaper('letter', 'portrait')
            ->output();
        Storage::disk('local')->put($pdfPath, $pdf);

        $temp = storage_path('app/temp/liberacion-word-' . Str::uuid() . '.docx');
        File::ensureDirectoryExists(dirname($temp), 0775, true);
        $this->documentos->crearWord(collect([$liberacion]), $temp);
        Storage::disk('local')->put($wordPath, (string) file_get_contents($temp));
        File::delete($temp);

        $liberacion->forceFill([
            'archivo_pdf_path' => $pdfPath,
            'archivo_word_path' => $wordPath,
        ])->saveQuietly();

        return $liberacion->refresh();
    }

    public function eliminar(LiberacionSueldo $liberacion): void
    {
        foreach ([$liberacion->archivo_pdf_path, $liberacion->archivo_word_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }

        Storage::disk('local')->deleteDirectory('liberaciones-sueldos/' . $liberacion->anio . '/' . $liberacion->id);
    }
}
