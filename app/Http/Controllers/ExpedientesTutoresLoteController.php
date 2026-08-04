<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTutor;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ExpedientesTutoresLoteController extends Controller
{
    public function grupo(Grupo $grupo): BinaryFileResponse
    {
        return $this->generar(
            Inscripcion::withTrashed()->where('grupo_id', $grupo->id)->pluck('id'),
            'grupo-' . ($grupo->id),
            'Expedientes de responsables por grupo'
        );
    }

    public function generacion(Generacion $generacion): BinaryFileResponse
    {
        return $this->generar(
            Inscripcion::withTrashed()->where('generacion_id', $generacion->id)->pluck('id'),
            'generacion-' . ($generacion->id),
            'Expedientes de responsables por generación'
        );
    }

    private function generar(Collection $inscripcionesIds, string $sufijo, string $titulo): BinaryFileResponse
    {
        $this->autorizar();
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');
        abort_if($inscripcionesIds->isEmpty(), 404, 'No hay alumnos en la selección indicada.');

        $tutores = Tutor::query()
            ->whereHas('relacionesActivas', fn (Builder $query) => $query->whereIn('inscripcion_id', $inscripcionesIds))
            ->with([
                'documentos' => fn ($query) => $query
                    ->where('es_actual', true)
                    ->where('es_fuente', false)
                    ->whereNotIn('estado', ['rechazado', 'reemplazado', 'cancelado'])
                    ->with('tipoDocumento:id,nombre,slug,orden'),
                'relacionesActivas' => fn ($query) => $query
                    ->whereIn('inscripcion_id', $inscripcionesIds)
                    ->with(['inscripcion' => fn ($subquery) => $subquery
                        ->withTrashed()
                        ->select('inscripciones.id', 'nombre', 'apellido_paterno', 'apellido_materno', 'matricula')]),
            ])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        abort_if($tutores->isEmpty(), 404, 'No hay responsables relacionados con los alumnos seleccionados.');

        $directorio = storage_path('app/private/expedientes-temporales/tutores-lote');
        File::ensureDirectoryExists($directorio);
        $rutaZip = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP.');
        $temporales = [];
        $documentosAgregados = 0;

        try {
            foreach ($tutores as $tutor) {
                $carpeta = $this->nombreSeguro(trim(
                    $tutor->apellido_paterno . '_' . $tutor->apellido_materno . '_' . $tutor->nombre
                )) ?: 'responsable_' . $tutor->id;

                $lineas = [
                    'RESPONSABLE: ' . $tutor->nombre_completo,
                    'ALUMNOS RELACIONADOS EN ESTA SELECCIÓN:',
                ];
                foreach ($tutor->relacionesActivas as $relacion) {
                    $alumno = $relacion->inscripcion;
                    $lineas[] = '- ' . trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? ''))
                        . ' | ' . ($alumno?->matricula ?: 'Sin matrícula')
                        . ' | ' . ($relacion->parentesco ?: 'Responsable');
                }
                $zip->addFromString($carpeta . '/00_Alumnos_relacionados.txt', implode(PHP_EOL, $lineas));

                foreach ($tutor->documentos->filter(fn (DocumentoTutor $documento): bool => $documento->archivo_existe) as $documento) {
                    $local = $this->rutaFisica($documento, $directorio, $temporales);
                    $tipo = $this->nombreSeguro($documento->tipoDocumento?->nombre ?? 'documento');
                    $nombre = $tipo . '_v' . $documento->version . '_' . $documento->id . '.' . ($documento->extension ?: 'pdf');
                    $zip->addFile($local, $carpeta . '/Documentos/' . $nombre);
                    $documentosAgregados++;
                }
            }

            $zip->addFromString('00_Resumen.txt', implode(PHP_EOL, [
                strtoupper($titulo),
                'Fecha: ' . now()->format('d/m/Y H:i'),
                'Responsables incluidos: ' . $tutores->count(),
                'Documentos incluidos: ' . $documentosAgregados,
                'Los responsables duplicados se incluyen una sola vez.',
            ]));
        } finally {
            $zip->close();
            foreach ($temporales as $temporal) {
                File::delete($temporal);
            }
        }

        return response()->download($rutaZip, 'expedientes-responsables-' . $sufijo . '.zip')->deleteFileAfterSend(true);
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
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('documentos.consultar')
                || auth()->user()?->canAccess('alumnos.editar'),
            403
        );
    }
}
