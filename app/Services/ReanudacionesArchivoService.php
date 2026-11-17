<?php

namespace App\Services;

use App\Models\ReanudacionLaboral;
use App\Services\Documentos\ReanudacionWordExporter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ReanudacionesArchivoService
{
    public function __construct(private readonly ReanudacionesService $documentos)
    {
    }

    /** @param array<string,mixed> $documento */
    public function guardarPdf(ReanudacionLaboral $registro, array $documento): ReanudacionLaboral
    {
        $ruta = $this->rutaBase($registro) . '/reanudacion-' . Str::slug($registro->persona_nombre) . '-' . $registro->id . '.pdf';
        $contenido = $this->documentos->renderPdf([$documento])->output();
        Storage::disk('local')->put($ruta, $contenido);

        $registro->forceFill(['archivo_pdf_path' => $ruta])->save();

        return $registro->refresh();
    }

    public function asegurarPdf(ReanudacionLaboral $registro): string
    {
        if ($registro->archivo_pdf_path && Storage::disk('local')->exists($registro->archivo_pdf_path)) {
            return Storage::disk('local')->path($registro->archivo_pdf_path);
        }

        $documento = $this->documentos->documentoDesdeRegistro($registro);
        $registro = $this->guardarPdf($registro, $documento);

        return Storage::disk('local')->path((string) $registro->archivo_pdf_path);
    }

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function crearZip(Collection $registros): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZIP de PHP no está disponible.');
        }

        $ruta = storage_path('app/temp/reanudaciones-' . Str::uuid() . '.zip');
        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No fue posible crear el archivo ZIP.');
        }

        foreach ($registros->values() as $indice => $registro) {
            $pdf = $this->asegurarPdf($registro);
            $nombre = sprintf(
                '%s/%s-%s.pdf',
                Str::slug($registro->nivel_nombre ?: 'nivel'),
                str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
                Str::slug($registro->persona_nombre)
            );
            $zip->addFile($pdf, $nombre);
        }

        $zip->close();

        return $ruta;
    }

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function crearWordMasivo(Collection $registros): string
    {
        return app(ReanudacionWordExporter::class)->exportar($registros);
    }

    public function eliminar(ReanudacionLaboral $registro): void
    {
        if ($registro->archivo_pdf_path) {
            Storage::disk('local')->delete($registro->archivo_pdf_path);
        }
    }

    /** @param Collection<int,ReanudacionLaboral> $registros */
    public function eliminarLote(Collection $registros): void
    {
        $registros->each(fn (ReanudacionLaboral $registro) => $this->eliminar($registro));

        $primero = $registros->first();
        if ($primero) {
            Storage::disk('local')->deleteDirectory($this->rutaBase($primero));
        }
    }

    private function rutaBase(ReanudacionLaboral $registro): string
    {
        return 'reanudaciones-laborales/' . Str::slug($registro->ciclo_nombre ?: 'sin-ciclo') . '/' . $registro->lote_uuid;
    }

}
