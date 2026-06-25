<?php

namespace App\Http\Controllers;

use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExpedienteDigitalController extends Controller
{
    public function index()
    {
        return view('documentos.expedientes-digitales', [
            'inscripcionId' => null,
        ]);
    }

    public function show(Inscripcion $inscripcion)
    {
        return view('documentos.expedientes-digitales', [
            'inscripcionId' => $inscripcion->id,
        ]);
    }

    public function preview(DocumentoAlumno $documento)
    {
        $this->asegurarArchivoExiste($documento);

        return Storage::disk($documento->disco)->response(
            $documento->ruta,
            $this->nombreDescarga($documento),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $this->nombreDescarga($documento) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function download(DocumentoAlumno $documento)
    {
        $this->asegurarArchivoExiste($documento);

        return Storage::disk($documento->disco)->download(
            $documento->ruta,
            $this->nombreDescarga($documento),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function zip(Inscripcion $inscripcion)
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        $inscripcion->load([
            'documentos.tipoDocumento:id,nombre,slug,orden',
            'documentos.nivel:id,nombre,slug',
            'documentos.grado:id,nombre,orden',
            'documentos.cicloEscolar:id,inicio_anio,fin_anio',
            'movimientos.trayectoriaAcademica.nivel:id,nombre,slug',
            'movimientos.trayectoriaAcademica.grado:id,nombre,orden',
            'movimientos.usuario:id,name',
        ]);

        $documentos = $inscripcion->documentos
            ->filter(fn(DocumentoAlumno $documento) => Storage::disk($documento->disco)->exists($documento->ruta));

        abort_if($documentos->isEmpty(), 404, 'El alumno todavía no tiene archivos para descargar.');

        $directorioTemporal = storage_path('app/private/expedientes-temporales');
        File::ensureDirectoryExists($directorioTemporal);

        $rutaZip = $directorioTemporal . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();

        abort_unless($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP.');

        foreach ($documentos as $documento) {
            $rutaFisica = Storage::disk($documento->disco)->path($documento->ruta);
            $zip->addFile($rutaFisica, $this->nombreDentroZip($documento));
        }

        if ($inscripcion->movimientos->isNotEmpty()) {
            $lineas = [
                'HISTORIAL DE BAJAS, TRASLADOS Y REINGRESOS',
                'Alumno: ' . trim($inscripcion->nombre . ' ' . $inscripcion->apellido_paterno . ' ' . $inscripcion->apellido_materno),
                'Matrícula: ' . $inscripcion->matricula,
                str_repeat('-', 70),
            ];

            foreach ($inscripcion->movimientos->sortBy(['fecha', 'id']) as $movimiento) {
                $lineas[] = sprintf(
                    '%s | %s | %s %s | Motivo: %s | Observaciones: %s',
                    $movimiento->fecha?->format('d/m/Y') ?? 'Sin fecha',
                    Str::headline($movimiento->tipo),
                    $movimiento->trayectoriaAcademica?->nivel?->nombre ?? 'Sin nivel',
                    $movimiento->trayectoriaAcademica?->grado?->nombre ?? '',
                    $movimiento->motivo ?: '—',
                    $movimiento->observaciones ?: '—'
                );
            }

            $zip->addFromString(
                '06_Bajas_traslados_y_reingresos/Historial_de_movimientos.txt',
                implode(PHP_EOL, $lineas)
            );
        }

        $zip->close();

        $nombreAlumno = Str::slug(trim(
            $inscripcion->apellido_paterno . ' ' .
            $inscripcion->apellido_materno . ' ' .
            $inscripcion->nombre
        ));

        $nombreZip = 'expediente-' . ($nombreAlumno ?: $inscripcion->id) . '.zip';

        return response()->download($rutaZip, $nombreZip)->deleteFileAfterSend(true);
    }

    private function asegurarArchivoExiste(DocumentoAlumno $documento): void
    {
        abort_unless(
            Storage::disk($documento->disco)->exists($documento->ruta),
            404,
            'El archivo ya no se encuentra en el almacenamiento.'
        );
    }

    private function nombreDescarga(DocumentoAlumno $documento): string
    {
        $tipo = Str::slug($documento->tipoDocumento?->nombre ?? 'documento', '_');
        $nivel = $documento->nivel ? '_' . Str::slug($documento->nivel->nombre, '_') : '';
        $grado = $documento->grado ? '_' . Str::slug($documento->grado->nombre, '_') : '';
        $ciclo = $documento->cicloEscolar
            ? '_' . $documento->cicloEscolar->inicio_anio . '-' . $documento->cicloEscolar->fin_anio
            : '';
        $folio = $documento->folio ? '_' . Str::slug($documento->folio, '_') : '';

        return $tipo . $nivel . $grado . $ciclo . $folio . '_v' . $documento->version . '_' . $documento->id . '.pdf';
    }

    private function nombreDentroZip(DocumentoAlumno $documento): string
    {
        $slug = $documento->tipoDocumento?->slug;
        $nombre = $this->nombreDescarga($documento);

        if (in_array($slug, [
            'acta-nacimiento',
            'registro-nacimiento',
            'curp',
            'comprobante-domicilio',
            'ine-padre',
            'ine-madre',
            'ine-tutor',
        ], true)) {
            return '01_Documentos_personales/' . $nombre;
        }

        if ($slug === 'certificado-estudios') {
            return '02_Certificados/' . $nombre;
        }

        if ($slug === 'boleta-final-grado') {
            $textoNivel = Str::lower(trim(($documento->nivel?->slug ?? '') . ' ' . ($documento->nivel?->nombre ?? '')));
            $carpetaNivel = Str::contains($textoNivel, 'secundaria') ? '04_Secundaria' : '03_Primaria';
            $ciclo = $documento->cicloEscolar
                ? $documento->cicloEscolar->inicio_anio . '-' . $documento->cicloEscolar->fin_anio
                : 'sin-ciclo';
            $grado = Str::slug($documento->grado?->nombre ?? 'sin-grado', '_');

            return $carpetaNivel . '/' . $grado . '_' . $ciclo . '/' . $nombre;
        }

        if ($slug === 'constancia-estudios') {
            return '05_Constancias_de_estudios/' . $nombre;
        }

        if ($slug === 'constancia-baja-traslado') {
            return '06_Bajas_traslados_y_reingresos/' . $nombre;
        }

        return '07_Otros_documentos/' . $nombre;
    }
}
