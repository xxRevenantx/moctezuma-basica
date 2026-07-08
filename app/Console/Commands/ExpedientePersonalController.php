<?php

namespace App\Http\Controllers;

use App\Models\DocumentoPersonal;
use App\Models\Persona;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExpedientePersonalController extends Controller
{
    public function index()
    {
        return view('documentos.expedientes-personal', [
            'personaId' => null,
        ]);
    }

    public function show(Persona $persona)
    {
        return view('documentos.expedientes-personal', [
            'personaId' => $persona->id,
        ]);
    }

    public function preview(DocumentoPersonal $documento)
    {
        $this->asegurarArchivoExiste($documento);

        return Storage::disk($documento->disco)->response(
            $documento->ruta,
            $this->nombreDescarga($documento),
            [
                'Content-Type' => $documento->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . $this->nombreDescarga($documento) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function download(DocumentoPersonal $documento)
    {
        $this->asegurarArchivoExiste($documento);

        return Storage::disk($documento->disco)->download(
            $documento->ruta,
            $this->nombreDescarga($documento),
            ['Content-Type' => $documento->mime_type ?: 'application/octet-stream']
        );
    }

    public function zip(Persona $persona)
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        $persona->load([
            'documentosPersonal.tipoDocumento:id,nombre,slug,categoria,orden',
            'movimientosLaborales.usuario:id,name',
            'rolesPersona:id,nombre,slug',
        ]);

        $documentos = $persona->documentosPersonal
            ->filter(fn(DocumentoPersonal $documento) => $documento->archivo_existe);

        abort_if($documentos->isEmpty() && $persona->movimientosLaborales->isEmpty(), 404, 'El personal todavía no tiene archivos o movimientos para descargar.');

        $directorioTemporal = storage_path('app/private/expedientes-personal-temporales');
        File::ensureDirectoryExists($directorioTemporal);

        $rutaZip = $directorioTemporal . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();

        abort_unless(
            $zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true,
            500,
            'No fue posible crear el archivo ZIP.'
        );

        $archivosTemporales = [];

        foreach ($documentos as $documento) {
            $rutaFisica = $this->rutaFisicaParaZip($documento, $directorioTemporal, $archivosTemporales);
            $zip->addFile($rutaFisica, $this->nombreDentroZip($documento));
        }

        $resumen = [
            'EXPEDIENTE DIGITAL DEL PERSONAL',
            'Nombre: ' . $this->nombreCompleto($persona),
            'CURP: ' . ($persona->curp ?: '—'),
            'RFC: ' . ($persona->rfc ?: '—'),
            'Estado laboral: ' . Str::headline($persona->estado_laboral ?: 'activo'),
            'Roles: ' . ($persona->rolesPersona->pluck('nombre')->implode(', ') ?: 'Sin rol registrado'),
            'Generado: ' . now()->format('d/m/Y H:i'),
            str_repeat('-', 72),
            'Los archivos incluidos son copias administrativas del expediente digital.',
        ];

        $zip->addFromString('00_Resumen_del_expediente.txt', implode(PHP_EOL, $resumen));

        if ($persona->movimientosLaborales->isNotEmpty()) {
            $lineas = [
                'HISTORIAL LABORAL',
                'Personal: ' . $this->nombreCompleto($persona),
                str_repeat('-', 72),
            ];

            foreach ($persona->movimientosLaborales->sortBy(['fecha', 'id']) as $movimiento) {
                $lineas[] = sprintf(
                    '%s | %s | Motivo: %s | Observaciones: %s | Registró: %s',
                    $movimiento->fecha?->format('d/m/Y') ?? 'Sin fecha',
                    Str::headline($movimiento->tipo),
                    $movimiento->motivo ?: '—',
                    $movimiento->observaciones ?: '—',
                    $movimiento->usuario?->name ?? 'Usuario no disponible'
                );
            }

            $zip->addFromString(
                '04_Historial_laboral/Historial_de_movimientos.txt',
                implode(PHP_EOL, $lineas)
            );
        }

        $zip->close();

        foreach ($archivosTemporales as $archivoTemporal) {
            File::delete($archivoTemporal);
        }

        $nombre = Str::slug($this->nombreCompleto($persona), '-');
        $nombreZip = 'expediente-personal-' . ($nombre ?: $persona->id) . '.zip';

        return response()->download($rutaZip, $nombreZip)->deleteFileAfterSend(true);
    }

    private function rutaFisicaParaZip(DocumentoPersonal $documento, string $directorioTemporal, array &$archivosTemporales): string
    {
        $disco = Storage::disk($documento->disco);

        if (method_exists($disco, 'path')) {
            try {
                $rutaFisica = $disco->path($documento->ruta);

                if (is_file($rutaFisica)) {
                    return $rutaFisica;
                }
            } catch (\Throwable) {
                // Algunos discos remotos, como S3, no tienen ruta física local.
            }
        }

        $contenido = $disco->get($documento->ruta);
        $rutaTemporal = $directorioTemporal . DIRECTORY_SEPARATOR . Str::uuid() . '.' . $documento->extension;
        File::put($rutaTemporal, $contenido);
        $archivosTemporales[] = $rutaTemporal;

        return $rutaTemporal;
    }

    private function asegurarArchivoExiste(DocumentoPersonal $documento): void
    {
        abort_unless(
            $documento->archivo_existe,
            404,
            'El archivo ya no se encuentra en el almacenamiento privado.'
        );
    }

    private function nombreDescarga(DocumentoPersonal $documento): string
    {
        $tipo = Str::slug($documento->tipoDocumento?->nombre ?? 'documento', '_');
        $detalle = Str::slug($documento->etiqueta_detalle, '_');

        return $tipo
            . ($detalle && $detalle !== $tipo ? '_' . $detalle : '')
            . '_v' . $documento->version
            . '_' . $documento->id
            . '.' . $documento->extension;
    }

    private function nombreDentroZip(DocumentoPersonal $documento): string
    {
        $nombre = $this->nombreDescarga($documento);

        if (!$documento->es_actual || $documento->estado === 'reemplazado') {
            return '03_Historial_de_versiones/' . $nombre;
        }

        return match ($documento->tipoDocumento?->slug) {
            'titulo-profesional' => '02_Documentos_academicos/Titulos/' . $nombre,
            'cedula-profesional' => '02_Documentos_academicos/Cedulas/' . $nombre,
            default => '01_Documentos_personales/' . $nombre,
        };
    }

    private function nombreCompleto(Persona $persona): string
    {
        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])));
    }
}
