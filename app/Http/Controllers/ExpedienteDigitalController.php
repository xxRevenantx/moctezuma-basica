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
            'trayectoriasAcademicas' => fn ($query) => $query
                ->with([
                    'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                    'ciclo:id,ciclo',
                    'nivel:id,nombre,slug',
                    'grado:id,nombre,orden',
                    'grupo:id,asignacion_grupo_id',
                    'grupo.asignacionGrupo:id,nombre',
                    'generacion:id,anio_ingreso,anio_egreso',
                    'semestre:id,numero',
                ])
                ->orderBy('ciclo_escolar_id')
                ->orderBy('ciclo_id')
                ->orderBy('numero_estancia')
                ->orderBy('id'),
            'matriculasAlumno.nivel:id,nombre,slug',
            'movimientos' => fn ($query) => $query
                ->with([
                    'cicloEscolar:id,inicio_anio,fin_anio',
                    'ciclo:id,ciclo',
                    'trayectoriaAcademica.nivel:id,nombre,slug',
                    'trayectoriaAcademica.grado:id,nombre,orden',
                    'trayectoriaAcademica.grupo:id,asignacion_grupo_id',
                    'trayectoriaAcademica.grupo.asignacionGrupo:id,nombre',
                    'usuario:id,name',
                ])
                ->orderBy('fecha')
                ->orderBy('id'),
        ]);

        $documentos = $inscripcion->documentos
            ->filter(fn(DocumentoAlumno $documento) => Storage::disk($documento->disco)->exists($documento->ruta));

        abort_if(
            $documentos->isEmpty()
                && $inscripcion->trayectoriasAcademicas->isEmpty()
                && $inscripcion->movimientos->isEmpty()
                && $inscripcion->matriculasAlumno->isEmpty(),
            404,
            'El alumno todavía no tiene documentos ni historial académico para descargar.'
        );

        $directorioTemporal = storage_path('app/private/expedientes-temporales');
        File::ensureDirectoryExists($directorioTemporal);

        $rutaZip = $directorioTemporal . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();

        abort_unless($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP.');

        foreach ($documentos as $documento) {
            $rutaFisica = Storage::disk($documento->disco)->path($documento->ruta);
            $zip->addFile($rutaFisica, $this->nombreDentroZip($documento));
        }

        if ($inscripcion->trayectoriasAcademicas->isNotEmpty() || $inscripcion->matriculasAlumno->isNotEmpty()) {
            $lineasTrayectoria = [
                'HISTORIAL ACADÉMICO DEL ALUMNO',
                'Alumno: ' . trim($inscripcion->nombre . ' ' . $inscripcion->apellido_paterno . ' ' . $inscripcion->apellido_materno),
                'Matrícula vigente: ' . ($inscripcion->matricula ?: '—'),
                'CURP: ' . ($inscripcion->curp ?: '—'),
                str_repeat('=', 105),
                '',
                'TRAYECTORIA POR CICLO Y CORTE',
                str_repeat('-', 105),
            ];

            foreach ($inscripcion->trayectoriasAcademicas as $trayectoria) {
                $ciclo = $trayectoria->cicloEscolar
                    ? $trayectoria->cicloEscolar->inicio_anio . '-' . $trayectoria->cicloEscolar->fin_anio
                    : 'Sin ciclo';
                $corte = $trayectoria->ciclo?->ciclo ?? 'Sin corte';
                $grupo = $trayectoria->grupo?->asignacionGrupo?->nombre ?? '—';
                $generacion = $trayectoria->generacion
                    ? $trayectoria->generacion->anio_ingreso . '-' . $trayectoria->generacion->anio_egreso
                    : '—';
                $inicio = $trayectoria->fecha_inicio?->format('d/m/Y')
                    ?? $trayectoria->fecha_inscripcion?->format('d/m/Y')
                    ?? '—';
                $fin = $trayectoria->fecha_fin?->format('d/m/Y')
                    ?? $trayectoria->fecha_baja?->format('d/m/Y')
                    ?? '—';

                $lineasTrayectoria[] = sprintf(
                    '%s | %s | %s | %s | Grupo %s | Generación %s | Semestre %s | Estancia #%s | %s | %s a %s%s',
                    $ciclo,
                    $corte,
                    $trayectoria->nivel?->nombre ?? 'Sin nivel',
                    $trayectoria->grado?->nombre ?? 'Sin grado',
                    $grupo,
                    $generacion,
                    $trayectoria->semestre?->numero ?? '—',
                    $trayectoria->numero_estancia ?? 1,
                    $trayectoria->etiqueta_estatus,
                    $inicio,
                    $fin,
                    $trayectoria->datos_reconstruidos ? ' | DATO RECONSTRUIDO' : ''
                );

                if ($trayectoria->motivo_baja) {
                    $lineasTrayectoria[] = '  Motivo: ' . $trayectoria->motivo_baja;
                }

                if ($trayectoria->observaciones_baja) {
                    $lineasTrayectoria[] = '  Observaciones: ' . $trayectoria->observaciones_baja;
                }
            }

            $lineasTrayectoria[] = '';
            $lineasTrayectoria[] = 'MATRÍCULAS POR NIVEL';
            $lineasTrayectoria[] = str_repeat('-', 105);

            if ($inscripcion->matriculasAlumno->isEmpty()) {
                $lineasTrayectoria[] = 'Sin historial de matrículas por nivel.';
            } else {
                foreach ($inscripcion->matriculasAlumno->sortBy('fecha_asignacion') as $matricula) {
                    $lineasTrayectoria[] = sprintf(
                        '%s | %s | Asignada: %s | Fin: %s | %s | Origen: %s',
                        $matricula->matricula,
                        $matricula->nivel?->nombre ?? 'Sin nivel',
                        $matricula->fecha_asignacion?->format('d/m/Y') ?? '—',
                        $matricula->fecha_fin?->format('d/m/Y') ?? '—',
                        $matricula->vigente ? 'VIGENTE' : 'HISTÓRICA',
                        Str::headline($matricula->origen ?? 'registro')
                    );
                }
            }

            $zip->addFromString(
                '00_Historial_academico/Trayectoria_academica.txt',
                implode(PHP_EOL, $lineasTrayectoria)
            );
        }

        if ($inscripcion->movimientos->isNotEmpty()) {
            $lineas = [
                'HISTORIAL DE BAJAS, TRASLADOS Y REINGRESOS',
                'Alumno: ' . trim($inscripcion->nombre . ' ' . $inscripcion->apellido_paterno . ' ' . $inscripcion->apellido_materno),
                'Matrícula: ' . $inscripcion->matricula,
                str_repeat('-', 70),
            ];

            foreach ($inscripcion->movimientos->sortBy(['fecha', 'id']) as $movimiento) {
                $cicloMovimiento = $movimiento->cicloEscolar
                    ? $movimiento->cicloEscolar->inicio_anio . '-' . $movimiento->cicloEscolar->fin_anio
                    : 'Sin ciclo';
                $corteMovimiento = $movimiento->ciclo?->ciclo ?? 'Sin corte';
                $grupoMovimiento = $movimiento->trayectoriaAcademica?->grupo?->asignacionGrupo?->nombre ?? '—';

                $lineas[] = sprintf(
                    '%s | %s | %s · %s | %s %s · Grupo %s | Motivo: %s | Observaciones: %s | Registró: %s',
                    $movimiento->fecha?->format('d/m/Y') ?? 'Sin fecha',
                    Str::headline($movimiento->tipo),
                    $cicloMovimiento,
                    $corteMovimiento,
                    $movimiento->trayectoriaAcademica?->nivel?->nombre ?? 'Sin nivel',
                    $movimiento->trayectoriaAcademica?->grado?->nombre ?? '',
                    $grupoMovimiento,
                    $movimiento->motivo ?: '—',
                    $movimiento->observaciones ?: '—',
                    $movimiento->usuario?->name ?? 'Sistema'
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
