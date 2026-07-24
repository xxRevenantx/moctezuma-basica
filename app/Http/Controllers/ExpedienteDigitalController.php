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
        $this->autorizar();
        return view('documentos.expedientes-digitales', [
            'inscripcionId' => null,
        ]);
    }

    public function show(Inscripcion $inscripcion)
    {
        $this->autorizar();
        return view('documentos.expedientes-digitales', [
            'inscripcionId' => $inscripcion->id,
        ]);
    }

    public function preview(DocumentoAlumno $documento)
    {
        $this->autorizar();
        abort_if($documento->es_fuente, 404);
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

    public function download(DocumentoAlumno $documento)
    {
        $this->autorizar();
        abort_if($documento->es_fuente, 404);
        $this->asegurarArchivoExiste($documento);

        return Storage::disk($documento->disco)->download(
            $documento->ruta,
            $this->nombreDescarga($documento),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function zip(Inscripcion $inscripcion)
    {
        $this->autorizar();
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        $inscripcion->load([
            'nivel:id,nombre,slug',
            'grado:id,nombre,orden',
            'semestre:id,numero',
            'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status',
            'grupo:id,asignacion_grupo_id',
            'grupo.asignacionGrupo:id,nombre',
            'documentos.tipoDocumento:id,nombre,slug,orden',
            'documentos.nivel:id,nombre,slug',
            'documentos.grado:id,nombre,orden',
            'documentos.cicloEscolar:id,inicio_anio,fin_anio',
            'matriculasAlumno.nivel:id,nombre,slug',
            'cambiosAcademicos' => fn ($query) => $query
                ->with([
                    'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status',
                    'usuario:id,name',
                ])
                ->orderBy('realizado_at')
                ->orderBy('id'),
            'movimientos' => fn ($query) => $query
                ->with([
                    'cicloEscolar:id,inicio_anio,fin_anio',
                    'ciclo:id,ciclo',
                    'nivelAnterior:id,nombre,slug',
                    'nivelNuevo:id,nombre,slug',
                    'usuario:id,name',
                ])
                ->orderBy('fecha')
                ->orderBy('id'),
        ]);

        $documentos = $inscripcion->documentos
            ->filter(fn (DocumentoAlumno $documento) => ! $documento->es_fuente && $documento->archivo_existe);

        abort_if(
            $documentos->isEmpty()
                && $inscripcion->cambiosAcademicos->isEmpty()
                && $inscripcion->movimientos->isEmpty()
                && $inscripcion->matriculasAlumno->isEmpty(),
            404,
            'El alumno todavía no tiene documentos ni movimientos administrativos para descargar.'
        );

        $directorioTemporal = storage_path('app/private/expedientes-temporales');
        File::ensureDirectoryExists($directorioTemporal);

        $rutaZip = $directorioTemporal . DIRECTORY_SEPARATOR . Str::uuid() . '.zip';
        $zip = new ZipArchive();

        abort_unless(
            $zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true,
            500,
            'No fue posible crear el ZIP.'
        );

        $archivosTemporales = [];

        foreach ($documentos as $documento) {
            $rutaFisica = $this->rutaFisicaParaZip($documento, $directorioTemporal, $archivosTemporales);
            $zip->addFile($rutaFisica, $this->nombreDentroZip($documento));
        }

        $zip->addFromString(
            '00_Informacion_academica/Asignacion_actual_y_bitacora.txt',
            $this->contenidoAcademico($inscripcion)
        );

        if ($inscripcion->movimientos->isNotEmpty()) {
            $zip->addFromString(
                '06_Bajas_traslados_y_reingresos/Historial_de_movimientos.txt',
                $this->contenidoMovimientos($inscripcion)
            );
        }

        $zip->close();

        foreach ($archivosTemporales as $archivoTemporal) {
            File::delete($archivoTemporal);
        }

        $nombreAlumno = Str::slug(trim(
            $inscripcion->apellido_paterno . ' ' .
            $inscripcion->apellido_materno . ' ' .
            $inscripcion->nombre
        ));

        $nombreZip = 'expediente-' . ($nombreAlumno ?: $inscripcion->id) . '.zip';

        return response()->download($rutaZip, $nombreZip)->deleteFileAfterSend(true);
    }

    private function rutaFisicaParaZip(DocumentoAlumno $documento, string $directorioTemporal, array &$archivosTemporales): string
    {
        $disco = Storage::disk($documento->disco);

        try {
            $rutaFisica = $disco->path($documento->ruta);

            if (is_file($rutaFisica)) {
                return $rutaFisica;
            }
        } catch (\Throwable) {
            // Los discos remotos no siempre ofrecen una ruta física local.
        }

        $contenido = $disco->get($documento->ruta);
        $extension = $documento->extension ?: 'bin';
        $rutaTemporal = $directorioTemporal . DIRECTORY_SEPARATOR . Str::uuid() . '.' . $extension;
        File::put($rutaTemporal, $contenido);
        $archivosTemporales[] = $rutaTemporal;

        return $rutaTemporal;
    }

    private function contenidoAcademico(Inscripcion $inscripcion): string
    {
        $grupo = $inscripcion->grupo?->asignacionGrupo?->nombre ?? '—';
        $generacion = $inscripcion->generacion?->etiqueta ?? '—';
        $estatus = Str::headline($inscripcion->estatus ?: ($inscripcion->activo ? 'activo' : 'inactivo'));

        $lineas = [
            'INFORMACIÓN ACADÉMICA Y BITÁCORA ADMINISTRATIVA',
            'Alumno: ' . trim($inscripcion->nombre . ' ' . $inscripcion->apellido_paterno . ' ' . $inscripcion->apellido_materno),
            'Matrícula vigente: ' . ($inscripcion->matricula ?: '—'),
            'CURP: ' . ($inscripcion->curp ?: '—'),
            str_repeat('=', 105),
            '',
            'ASIGNACIÓN ACTUAL',
            str_repeat('-', 105),
            'Nivel: ' . ($inscripcion->nivel?->nombre ?? '—'),
            'Generación: ' . $generacion,
            'Grado: ' . ($inscripcion->grado?->nombre ?? '—'),
            'Semestre: ' . ($inscripcion->semestre?->numero ?? '—'),
            'Grupo: ' . $grupo,
            'Estatus: ' . $estatus,
            'Fecha de ingreso al plantel: ' . ($inscripcion->fecha_inscripcion?->format('d/m/Y') ?? '—'),
            'Fecha del estatus actual: ' . ($inscripcion->fecha_estatus?->format('d/m/Y H:i') ?? '—'),
            'Motivo del estatus: ' . ($inscripcion->motivo_estatus ?: '—'),
            '',
            'MATRÍCULAS POR NIVEL',
            str_repeat('-', 105),
        ];

        if ($inscripcion->matriculasAlumno->isEmpty()) {
            $lineas[] = 'Sin historial de matrículas por nivel.';
        } else {
            foreach ($inscripcion->matriculasAlumno->sortBy('fecha_asignacion') as $matricula) {
                $lineas[] = sprintf(
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

        $lineas[] = '';
        $lineas[] = 'BITÁCORA DE CAMBIOS ACADÉMICOS';
        $lineas[] = str_repeat('-', 105);

        if ($inscripcion->cambiosAcademicos->isEmpty()) {
            $lineas[] = 'Sin cambios académicos registrados.';
        } else {
            foreach ($inscripcion->cambiosAcademicos as $cambio) {
                $lineas[] = sprintf(
                    '%s | %s | Generación: %s | Motivo: %s | Registró: %s',
                    $cambio->realizado_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    Str::headline($cambio->tipo),
                    $cambio->generacion?->etiqueta ?? '—',
                    $cambio->motivo ?: '—',
                    $cambio->usuario?->name ?? 'Sistema'
                );

                $lineas[] = '  Antes: ' . $this->formatearDatos($cambio->datos_anteriores);
                $lineas[] = '  Después: ' . $this->formatearDatos($cambio->datos_nuevos);
            }
        }

        return implode(PHP_EOL, $lineas);
    }

    private function contenidoMovimientos(Inscripcion $inscripcion): string
    {
        $lineas = [
            'HISTORIAL DE BAJAS, TRASLADOS, REINGRESOS Y EGRESOS',
            'Alumno: ' . trim($inscripcion->nombre . ' ' . $inscripcion->apellido_paterno . ' ' . $inscripcion->apellido_materno),
            'Matrícula: ' . ($inscripcion->matricula ?: '—'),
            str_repeat('-', 90),
        ];

        foreach ($inscripcion->movimientos as $movimiento) {
            $ciclo = $movimiento->cicloEscolar
                ? $movimiento->cicloEscolar->inicio_anio . '-' . $movimiento->cicloEscolar->fin_anio
                : 'Sin ciclo de referencia';
            $corte = $movimiento->ciclo?->ciclo ?? 'Sin corte';
            $nivel = $movimiento->nivelNuevo?->nombre
                ?? $movimiento->nivelAnterior?->nombre
                ?? $inscripcion->nivel?->nombre
                ?? 'Sin nivel';

            $lineas[] = sprintf(
                '%s | %s | %s · %s | %s | Motivo: %s | Observaciones: %s | Registró: %s',
                $movimiento->fecha?->format('d/m/Y') ?? 'Sin fecha',
                Str::headline($movimiento->tipo),
                $ciclo,
                $corte,
                $nivel,
                $movimiento->motivo ?: '—',
                $movimiento->observaciones ?: '—',
                $movimiento->usuario?->name ?? 'Sistema'
            );
        }

        return implode(PHP_EOL, $lineas);
    }

    private function formatearDatos(?array $datos): string
    {
        if (empty($datos)) {
            return '—';
        }

        return collect($datos)
            ->map(fn ($valor, $clave) => Str::headline((string) $clave) . ': ' . $this->valorLegible($valor))
            ->implode(' | ');
    }

    private function valorLegible(mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_array($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return filled($valor) ? (string) $valor : '—';
    }

    private function autorizar(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->is_admin || auth()->user()->canAccess('documentos.organizar')),
            403,
            'No tienes permiso para administrar expedientes digitales.'
        );
    }

    private function asegurarArchivoExiste(DocumentoAlumno $documento): void
    {
        abort_unless(
            $documento->archivo_existe,
            404,
            'El archivo ya no se encuentra en el almacenamiento privado. Vuelve a cargarlo desde el expediente.'
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

        $extension = $documento->extension ?: 'bin';

        return $tipo . $nivel . $grado . $ciclo . $folio . '_v' . $documento->version . '_' . $documento->id . '.' . $extension;
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

        if (in_array($slug, ['certificado-estudios', 'certificado-terminacion'], true)) {
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
