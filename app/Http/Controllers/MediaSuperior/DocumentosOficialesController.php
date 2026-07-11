<?php

namespace App\Http\Controllers\MediaSuperior;

use App\Exports\MediaSuperior\DocumentoOficialExport;
use App\Http\Controllers\Controller;
use App\Models\EmisionDocumentoMediaSuperior;
use App\Models\FirmanteMediaSuperior;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Services\MediaSuperior\DocumentosOficialesService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;
use ZipArchive;

class DocumentosOficialesController extends Controller
{
    public function index(string $modulo = 'inicio')
    {
        abort_unless(Auth::user()?->is_admin, 403);

        return view('media-superior.documentos-oficiales.index', compact('modulo'));
    }

    public function configuracion()
    {
        abort_unless(Auth::user()?->is_admin, 403);

        return view('media-superior.documentos-oficiales.configuracion');
    }

    public function archivoFirmante(FirmanteMediaSuperior $firmante, string $tipo)
    {
        Gate::authorize('configurar-firmas-documentales');
        abort_unless(in_array($tipo, ['firma', 'sello'], true), 404);

        $campo = $tipo === 'firma' ? 'firma_path' : 'sello_path';
        $ruta = (string) $firmante->{$campo};
        abort_if($ruta === '' || ! Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->response($ruta, basename($ruta), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function descargar(
        Request $request,
        string $tipo,
        string $formato,
        DocumentosOficialesService $service,
    ) {
        abort_unless(Auth::user()?->is_admin, 403);
        $this->validarTipoFormato($tipo, $formato);

        try {
            $datos = $this->construirDatos($request, $tipo, $service);
        } catch (RuntimeException $exception) {
            return $this->redirigirConErrorDocumento($tipo, $exception->getMessage());
        }

        $nombre = $this->nombreArchivo($tipo, $datos, $formato);
        if (!$request->boolean('preview')) {
            $this->registrarEmision($request, $tipo, $formato, $datos, $service);
        }

        if ($formato === 'pdf') {
            $pdf = $this->crearPdf($tipo, $datos);

            return $request->boolean('preview')
                ? $pdf->stream($nombre)
                : $pdf->download($nombre);
        }

        if ($formato === 'word') {
            $ruta = $this->crearWordTemporal($tipo, $datos);

            return response()->download($ruta, $nombre)->deleteFileAfterSend(true);
        }

        return Excel::download(new DocumentoOficialExport($tipo, $datos), $nombre);
    }

    public function zip(
        Request $request,
        string $tipo,
        DocumentosOficialesService $service,
    ) {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_unless(in_array($tipo, $this->tipos(), true), 404);

        $formato = $request->string('formato')->lower()->toString() ?: 'pdf';
        if (!in_array($formato, ['pdf', 'word'], true)) {
            return $this->redirigirConErrorDocumento($tipo, 'El ZIP solo admite documentos en formato PDF o Word.');
        }

        try {
            $lote = $this->construirLote($request, $tipo, $service);
        } catch (RuntimeException $exception) {
            return $this->redirigirConErrorDocumento($tipo, $exception->getMessage());
        }

        if ($lote === []) {
            return $this->redirigirConErrorDocumento(
                $tipo,
                'No hay documentos disponibles con los filtros seleccionados.'
            );
        }

        $directorio = storage_path('app/temp');
        File::ensureDirectoryExists($directorio);
        $zipPath = $directorio . '/documentos-oficiales-' . $tipo . '-' . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP.');

        $errores = [];
        $temporalesWord = [];

        foreach ($lote as $item) {
            if (isset($item['datos']['__error'])) {
                $errores[] = ($item['etiqueta'] ?? 'Documento') . ': ' . $item['datos']['__error'];
                continue;
            }

            try {
                $datos = $item['datos'];
                $nombre = $this->nombreArchivo($tipo, $datos, $formato);

                if ($formato === 'pdf') {
                    $contenido = $this->crearPdf($tipo, $datos)->output();
                    $zip->addFromString($nombre, $contenido);
                } else {
                    $rutaWord = $this->crearWordTemporal($tipo, $datos);
                    $temporalesWord[] = $rutaWord;
                    $zip->addFile($rutaWord, $nombre);
                }

                $this->registrarEmision($request, $tipo, $formato, $datos, $service);
            } catch (\Throwable $exception) {
                $errores[] = ($item['etiqueta'] ?? 'Documento') . ': ' . $exception->getMessage();
            }
        }

        if ($errores !== []) {
            $zip->addFromString('DOCUMENTOS_NO_GENERADOS.txt', implode(PHP_EOL, $errores));
        }

        $zip->close();
        File::delete($temporalesWord);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function plantillaAsistencias(Request $request, DocumentosOficialesService $service)
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $datos = $service->actaResultados($this->filtros($request));
        $nombre = 'Plantilla_Asistencias_' . Str::slug($datos['asignacion']->materia?->materia ?: 'Materia', '_') . '.xlsx';

        return Excel::download(new DocumentoOficialExport('plantilla-asistencias', $datos), $nombre);
    }

    private function construirDatos(Request $request, string $tipo, DocumentosOficialesService $service): array
    {
        $filtros = $this->filtros($request);

        $datos = match ($tipo) {
            DocumentosOficialesService::TIPO_REGISTRO => $service->registroEscolaridad($filtros),
            DocumentosOficialesService::TIPO_ACTA => $service->actaResultados($filtros),
            DocumentosOficialesService::TIPO_KARDEX => $service->kardex((int) ($filtros['inscripcion_id'] ?? 0)),
            DocumentosOficialesService::TIPO_HISTORIAL => $service->historialAcademico(
                (int) ($filtros['inscripcion_id'] ?? 0),
                (string) ($filtros['historial_modo'] ?? 'completo'),
                (bool) ($filtros['historial_mostrar_foto'] ?? false),
                (bool) ($filtros['historial_incluir_firmas'] ?? true),
            ),
            DocumentosOficialesService::TIPO_CERTIFICADO => $service->certificado(
                (int) ($filtros['inscripcion_id'] ?? 0),
                (string) ($filtros['modalidad'] ?? 'parcial'),
            ),
            default => throw new RuntimeException('Tipo de documento no válido.'),
        };

        return $this->conFechaDocumento($datos, $filtros);
    }

    /** @return array<int, array{etiqueta:string, datos:array}> */
    private function construirLote(Request $request, string $tipo, DocumentosOficialesService $service): array
    {
        $filtros = $this->filtros($request);
        $salida = [];

        if ($tipo === DocumentosOficialesService::TIPO_REGISTRO) {
            $grupos = $service->grupos($filtros['generacion_id'], $filtros['semestre_id'], $filtros['ciclo_escolar_id']);
            foreach ($grupos as $grupo) {
                $local = array_merge($filtros, ['grupo_id' => $grupo->id]);
                $salida[] = [
                    'etiqueta' => 'Grupo ' . ($grupo->asignacionGrupo?->nombre ?: $grupo->id),
                    'datos' => $this->conFechaDocumento($service->registroEscolaridad($local), $local),
                ];
            }

            return $salida;
        }

        if ($tipo === DocumentosOficialesService::TIPO_ACTA) {
            $asignaciones = $service->asignaciones(
                (int) $filtros['ciclo_escolar_id'],
                (int) $filtros['grupo_id'],
                $filtros['semestre_id'],
                true,
            );
            foreach ($asignaciones as $asignacion) {
                $local = array_merge($filtros, ['asignacion_materia_id' => $asignacion->id]);
                $salida[] = [
                    'etiqueta' => $asignacion->materia?->materia ?: 'Materia',
                    'datos' => $this->conFechaDocumento($service->actaResultados($local), $local),
                ];
            }

            return $salida;
        }

        $alumnos = $service->alumnos($filtros['generacion_id'], $filtros['grupo_id']);
        foreach ($alumnos as $alumno) {
            try {
                $datos = match ($tipo) {
                    DocumentosOficialesService::TIPO_KARDEX => $service->kardex($alumno->id),
                    DocumentosOficialesService::TIPO_HISTORIAL => $service->historialAcademico(
                        $alumno->id,
                        (string) ($filtros['historial_modo'] ?? 'completo'),
                        (bool) ($filtros['historial_mostrar_foto'] ?? false),
                        (bool) ($filtros['historial_incluir_firmas'] ?? true),
                    ),
                    default => $service->certificado($alumno->id, (string) ($filtros['modalidad'] ?? 'parcial')),
                };
                $datos = $this->conFechaDocumento($datos, $filtros);
                $salida[] = [
                    'etiqueta' => $alumno->matricula . ' ' . $alumno->apellido_paterno,
                    'datos' => $datos,
                ];
            } catch (\Throwable $exception) {
                $salida[] = [
                    'etiqueta' => $alumno->matricula . ' ' . $alumno->apellido_paterno,
                    'datos' => ['__error' => $exception->getMessage()],
                ];
            }
        }

        return $salida;
    }

    private function crearPdf(string $tipo, array $datos)
    {
        $vista = 'pdf.media-superior.' . $tipo;

        if ($tipo === DocumentosOficialesService::TIPO_HISTORIAL) {
            $datos = $this->prepararImagenesParaPdf($datos);
        }

        $pdf = Pdf::loadView($vista, $datos)
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'Arial',
                'dpi' => 96,
            ]);

        if ($tipo === DocumentosOficialesService::TIPO_REGISTRO) {
            // Oficio horizontal exacto: 35.56 cm x 21.59 cm (1008 x 612 puntos).
            return $pdf->setPaper([0, 0, 1008, 612], 'portrait');
        }

        return $pdf->setPaper('letter', 'portrait');
    }

    /**
     * Dompdf puede fallar al resolver rutas locales absolutas, especialmente en
     * Windows o cuando los archivos están fuera de public. Para el PDF se
     * incrustan logos, fotografías, firmas y sellos como data URI.
     */
    private function prepararImagenesParaPdf(array $datos): array
    {
        $institucional = (array) ($datos['institucional'] ?? []);

        foreach (['logo_seg', 'logo_plantel', 'logo_certificado'] as $campo) {
            $institucional[$campo . '_pdf'] = $this->archivoImagenComoDataUri(
                isset($institucional[$campo]) ? (string) $institucional[$campo] : null,
            );
        }

        foreach (['director', 'jefe_registro'] as $rol) {
            $firmante = (array) data_get($institucional, 'firmantes.' . $rol, []);
            $firmante['firma_pdf'] = $this->archivoImagenComoDataUri(
                isset($firmante['firma_ruta']) ? (string) $firmante['firma_ruta'] : null,
            );
            $firmante['sello_pdf'] = $this->archivoImagenComoDataUri(
                isset($firmante['sello_ruta']) ? (string) $firmante['sello_ruta'] : null,
            );
            data_set($institucional, 'firmantes.' . $rol, $firmante);
        }

        $datos['institucional'] = $institucional;

        return $datos;
    }

    private function archivoImagenComoDataUri(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);

        if ($ruta === '') {
            return null;
        }

        if (Str::startsWith($ruta, 'data:image/')) {
            return $ruta;
        }

        if (Str::startsWith($ruta, 'file://')) {
            $ruta = rawurldecode((string) parse_url($ruta, PHP_URL_PATH));
        }

        if (! is_file($ruta) || ! is_readable($ruta)) {
            return null;
        }

        $contenido = @file_get_contents($ruta);
        if ($contenido === false || $contenido === '') {
            return null;
        }

        $mime = @mime_content_type($ruta);
        if (! is_string($mime) || ! Str::startsWith($mime, 'image/')) {
            $mime = match (strtolower(pathinfo($ruta, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => null,
            };
        }

        if (! $mime) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }

    private function crearWordTemporal(string $tipo, array $datos): string
    {
        $directorioSalida = storage_path('app/temp/documentos-oficiales');
        $directorioPhpWord = storage_path('app/temp/phpword');
        $directorioTrabajo = $directorioPhpWord . DIRECTORY_SEPARATOR . (string) Str::uuid();

        File::ensureDirectoryExists($directorioSalida, 0775, true);
        File::ensureDirectoryExists($directorioPhpWord, 0775, true);
        File::ensureDirectoryExists($directorioTrabajo, 0775, true);

        $this->validarDirectorioWordEscribible($directorioSalida, 'salida');
        $this->validarDirectorioWordEscribible($directorioPhpWord, 'temporal');
        $this->validarDirectorioWordEscribible($directorioTrabajo, 'de trabajo');

        // PHPWord 1.4 puede consultar tanto Settings como las variables TMP/TEMP.
        // Se evita así que intente trabajar dentro de C:\Windows\Temp.
        putenv('TMP=' . $directorioTrabajo);
        putenv('TEMP=' . $directorioTrabajo);
        putenv('TMPDIR=' . $directorioTrabajo);

        $_ENV['TMP'] = $directorioTrabajo;
        $_ENV['TEMP'] = $directorioTrabajo;
        $_ENV['TMPDIR'] = $directorioTrabajo;
        $_SERVER['TMP'] = $directorioTrabajo;
        $_SERVER['TEMP'] = $directorioTrabajo;
        $_SERVER['TMPDIR'] = $directorioTrabajo;

        Settings::setTempDir($directorioTrabajo);

        $ruta = $directorioSalida . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';

        try {
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(8);

            $sectionStyle = $tipo === DocumentosOficialesService::TIPO_REGISTRO
                ? ['pageSizeW' => 20160, 'pageSizeH' => 12240, 'orientation' => 'landscape', 'marginTop' => 360, 'marginBottom' => 360, 'marginLeft' => 360, 'marginRight' => 360]
                : ['pageSizeW' => 12240, 'pageSizeH' => 15840, 'marginTop' => 500, 'marginBottom' => 500, 'marginLeft' => 600, 'marginRight' => 600];
            $section = $phpWord->addSection($sectionStyle);

            if ($tipo !== DocumentosOficialesService::TIPO_CERTIFICADO) {
                $this->encabezadoWord($section, $datos['institucional'] ?? []);
            }

            match ($tipo) {
                DocumentosOficialesService::TIPO_REGISTRO => $this->wordRegistro($section, $datos),
                DocumentosOficialesService::TIPO_ACTA => $this->wordActa($section, $datos),
                DocumentosOficialesService::TIPO_KARDEX => $this->wordKardex($section, $datos),
                DocumentosOficialesService::TIPO_HISTORIAL => $this->wordHistorial($section, $datos),
                DocumentosOficialesService::TIPO_CERTIFICADO => $this->wordCertificado($phpWord, $section, $datos, $sectionStyle),
                default => throw new RuntimeException('Tipo de documento Word no válido.'),
            };

            $writer = WordIOFactory::createWriter($phpWord, 'Word2007');

            // Se configura también directamente en el writer porque es la capa
            // que crea y posteriormente recorre PHPWordWriter_* durante save().
            if (method_exists($writer, 'setTempDir')) {
                $writer->setTempDir($directorioTrabajo);
            }

            if (method_exists($writer, 'setUseDiskCaching')) {
                $writer->setUseDiskCaching(true, $directorioTrabajo);
            }

            $writer->save($ruta);

            if (! is_file($ruta) || filesize($ruta) === 0) {
                throw new RuntimeException('PHPWord no pudo crear correctamente el archivo Word.');
            }

            return $ruta;
        } catch (\Throwable $exception) {
            File::delete($ruta);

            throw new RuntimeException(
                'No fue posible generar el archivo Word. Verifica los permisos de storage/app/temp. Detalle: '
                . $exception->getMessage(),
                previous: $exception,
            );
        } finally {
            // Se restaura un directorio estable para cualquier otro exportador
            // que se ejecute durante la misma solicitud.
            putenv('TMP=' . $directorioPhpWord);
            putenv('TEMP=' . $directorioPhpWord);
            putenv('TMPDIR=' . $directorioPhpWord);
            $_ENV['TMP'] = $directorioPhpWord;
            $_ENV['TEMP'] = $directorioPhpWord;
            $_ENV['TMPDIR'] = $directorioPhpWord;
            $_SERVER['TMP'] = $directorioPhpWord;
            $_SERVER['TEMP'] = $directorioPhpWord;
            $_SERVER['TMPDIR'] = $directorioPhpWord;
            Settings::setTempDir($directorioPhpWord);

            File::deleteDirectory($directorioTrabajo);
        }
    }

    private function validarDirectorioWordEscribible(string $directorio, string $etiqueta): void
    {
        if (! is_dir($directorio)) {
            throw new RuntimeException("No existe el directorio {$etiqueta} de PHPWord: {$directorio}");
        }

        if (! is_writable($directorio)) {
            @chmod($directorio, 0775);
        }

        if (! is_writable($directorio)) {
            throw new RuntimeException("El directorio {$etiqueta} de PHPWord no tiene permisos de escritura: {$directorio}");
        }

        $prueba = $directorio . DIRECTORY_SEPARATOR . '.phpword-write-test-' . Str::uuid();

        if (@file_put_contents($prueba, 'ok') === false) {
            throw new RuntimeException("PHP no puede escribir en el directorio {$etiqueta} de PHPWord: {$directorio}");
        }

        @unlink($prueba);
    }

    private function encabezadoWord($section, array $institucional): void
    {
        $tabla = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'borderSize' => 0]);
        $tabla->addRow();
        $izquierda = $tabla->addCell(1800);
        if (is_file($institucional['logo_seg'] ?? '')) {
            $izquierda->addImage($institucional['logo_seg'], ['width' => 115, 'height' => 48]);
        }
        $centro = $tabla->addCell(5600);
        $centro->addText('SISTEMA EDUCATIVO ESTATAL', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $centro->addText(Str::upper($institucional['plantel'] ?? ''), ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $derecha = $tabla->addCell(1800);
        if (is_file($institucional['logo_certificado'] ?? '')) {
            $derecha->addImage($institucional['logo_certificado'], ['width' => 125, 'height' => 42]);
        }
        $section->addTextBreak(1);
    }

    private function wordRegistro($section, array $datos): void
    {
        $section->addText('REGISTRO DE ESCOLARIDAD', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $this->wordContexto($section, $datos);
        $tabla = $section->addTable(['borderSize' => 5, 'borderColor' => '333333', 'cellMargin' => 35]);
        $tabla->addRow();
        foreach (array_merge(['No.', 'Matrícula', 'Alumno', 'Sexo'], collect($datos['asignaciones'])->map(fn($a) => $a->materia?->clave ?: $a->materia?->materia)->all(), ['No acr.', 'Situación']) as $titulo) {
            $tabla->addCell(750)->addText((string) $titulo, ['bold' => true, 'size' => 6], ['alignment' => Jc::CENTER]);
        }
        foreach ($datos['filas'] as $fila) {
            $tabla->addRow();
            foreach (array_merge([$fila['numero'], $fila['matricula'], $fila['nombre'], $fila['sexo']], collect($fila['materias'])->pluck('valor')->all(), [$fila['asignaturas_no_acreditadas'], $fila['situacion_escolar']]) as $valor) {
                $tabla->addCell(750)->addText((string) $valor, ['size' => 6], ['alignment' => Jc::CENTER]);
            }
        }
        $this->firmasWord($section, Arr::only($datos['institucional']['firmantes'] ?? [], ['director', 'jefe_registro']));
    }

    private function wordActa($section, array $datos): void
    {
        $section->addText('ACTA DE RESULTADOS DE EVALUACIÓN', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $this->wordContexto($section, $datos);
        $section->addText('ASIGNATURA: ' . Str::upper($datos['asignacion']->materia?->materia ?: ''), ['bold' => true]);
        $tabla = $section->addTable(['borderSize' => 5, 'borderColor' => '333333', 'cellMargin' => 60]);
        $encabezados = ['No.', 'Matrícula', 'Nombre del alumno', 'Número', 'Letra', '% Asist.', 'Acreditado'];
        $tabla->addRow();
        foreach ($encabezados as $titulo) {
            $tabla->addCell()->addText($titulo, ['bold' => true], ['alignment' => Jc::CENTER]);
        }
        foreach ($datos['filas'] as $fila) {
            $tabla->addRow();
            foreach ([$fila['numero'], $fila['matricula'], $fila['nombre'], $fila['calificacion_numero'], $fila['calificacion_letra'], $fila['asistencia'], $fila['acreditado']] as $valor) {
                $tabla->addCell()->addText((string) $valor, [], ['alignment' => Jc::CENTER]);
            }
        }
        $section->addText(($datos['institucional']['localidad_expedicion'] ?? '') . ', A ' . ($datos['fecha_documento_texto'] ?? ''), [], ['alignment' => Jc::RIGHT]);
        $this->firmasWord($section, Arr::only($datos['institucional']['firmantes'] ?? [], ['control_escolar', 'director', 'profesor']));
    }

    private function wordKardex($section, array $datos): void
    {
        $section->addText('CERTIFICACIÓN DE ESTUDIOS', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addText('KARDEX DEL ALUMNO', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $this->wordAlumno($section, $datos['alumno']);
        foreach ($datos['semestres'] as $semestre) {
            $section->addText($semestre['numero'] . '° SEMESTRE · ' . ($semestre['ciclo']?->nombre ?: ''), ['bold' => true, 'size' => 9]);
            $this->wordTablaMaterias($section, $semestre['oficiales'], false);
            if (($datos['institucional']['mostrar_materias_extra'] ?? true) && $semestre['extras']->isNotEmpty()) {
                $section->addText('MATERIAS EXTRA INFORMATIVAS · NO PROMEDIAN', ['bold' => true, 'color' => '8A5A00']);
                $this->wordTablaMaterias($section, $semestre['extras'], true);
            }
        }
        $section->addText('PROMEDIO GENERAL: ' . $datos['promedio_general'], ['bold' => true, 'size' => 10], ['alignment' => Jc::RIGHT]);
        $this->firmasWord($section, Arr::only($datos['institucional']['firmantes'] ?? [], ['director', 'jefe_registro']));
    }


    private function wordHistorial($section, array $datos): void
    {
        $semestres = collect($datos['semestres_historial'] ?? []);
        $institucional = $datos['institucional'] ?? [];

        $section->addText('HISTORIAL ACADÉMICO', ['bold' => true, 'size' => 13, 'color' => '8C2A2A'], ['alignment' => Jc::CENTER]);
        $this->wordAlumno($section, $datos['alumno']);
        $this->wordSemestresHistorial($section, $semestres->whereIn('numero', [1, 2, 3, 4]), $institucional);

        // El documento editable conserva la misma distribución del PDF oficial:
        // cuatro semestres en la primera página y dos en la segunda.
        $section->addPageBreak();
        $this->encabezadoWord($section, $institucional);
        $section->addText('HISTORIAL ACADÉMICO', ['bold' => true, 'size' => 13, 'color' => '8C2A2A'], ['alignment' => Jc::CENTER]);
        $section->addText(
            trim(implode(' ', array_filter([
                $datos['alumno']->apellido_paterno ?? null,
                $datos['alumno']->apellido_materno ?? null,
                $datos['alumno']->nombre ?? null,
            ]))) . ' · ' . ($datos['alumno']->matricula ?? ''),
            ['bold' => true, 'size' => 8],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80],
        );
        $this->wordSemestresHistorial($section, $semestres->whereIn('numero', [5, 6]), $institucional);

        $resumen = $datos['resumen_historial'] ?? [];
        $section->addTextBreak(1);
        $section->addText('PROMEDIO GENERAL: ' . ($datos['promedio_general'] ?? '—'), ['bold' => true, 'size' => 10], ['alignment' => Jc::RIGHT]);
        $section->addText('SITUACIÓN: ' . ($resumen['situacion'] ?? 'SIN REGISTROS'), ['bold' => true, 'size' => 8], ['alignment' => Jc::RIGHT]);
        $this->firmasWordHistorial($section, Arr::only($institucional['firmantes'] ?? [], ['director', 'jefe_registro']), (bool) ($datos['incluir_firmas_digitales'] ?? true));
    }

    private function wordSemestresHistorial($section, iterable $semestres, array $institucional): void
    {
        foreach ($semestres as $semestre) {
            $section->addText(
                'SEMESTRE ' . $semestre['numero'] . ' · ' . ($semestre['ciclo_texto'] ?? '—'),
                ['bold' => true, 'size' => 8, 'color' => '8C2A2A'],
                ['spaceBefore' => 60, 'spaceAfter' => 20],
            );

            $tabla = $section->addTable(['borderSize' => 4, 'borderColor' => 'B7B7B7', 'cellMargin' => 20]);
            $tabla->addRow();
            foreach (['Clave', 'Asignatura', 'Calif.', 'Asist.', 'Regularización'] as $encabezado) {
                $tabla->addCell()->addText($encabezado, ['bold' => true, 'size' => 6], ['alignment' => Jc::CENTER]);
            }

            // El Historial académico solo muestra materias oficiales.
            // Las materias extra permanecen disponibles en el Kardex, pero no aquí.
            $materias = collect($semestre['oficiales'] ?? []);

            if ($materias->isEmpty()) {
                $tabla->addRow();
                $tabla->addCell(9000, ['gridSpan' => 5])->addText('SIN MATERIAS CONFIGURADAS', ['italic' => true, 'size' => 6], ['alignment' => Jc::CENTER]);
                continue;
            }

            foreach ($materias as $materia) {
                $tabla->addRow();
                foreach ([
                    $materia['clave'] ?? '',
                    $materia['nombre'] ?? '',
                    ($materia['valor'] ?? '') !== '' ? $materia['valor'] : '—',
                    ($materia['asistencia'] ?? null) !== null ? number_format((float) $materia['asistencia'], 0) . '%' : '',
                    '',
                ] as $indice => $valor) {
                    $tabla->addCell()->addText((string) $valor, ['size' => 6], ['alignment' => $indice === 1 ? Jc::LEFT : Jc::CENTER]);
                }
            }
        }
    }

    private function wordCertificado(PhpWord $phpWord, $section, array $datos, array $sectionStyle): void
    {
        $institucional = $datos['institucional'] ?? [];

        $encabezado = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'borderSize' => 0]);
        $encabezado->addRow();
        $izquierda = $encabezado->addCell(3400);
        if (is_file($institucional['logo_seg'] ?? '')) {
            $izquierda->addImage($institucional['logo_seg'], ['width' => 128, 'height' => 47]);
        }
        $encabezado->addCell(2800);
        $derecha = $encabezado->addCell(3400);
        if (is_file($institucional['logo_plantel'] ?? '')) {
            $derecha->addImage($institucional['logo_plantel'], ['width' => 150, 'height' => 46, 'alignment' => Jc::RIGHT]);
        }

        $section->addText('CERTIFICACIÓN DE ESTUDIOS', ['size' => 15], ['alignment' => Jc::CENTER, 'spaceBefore' => 120, 'spaceAfter' => 80]);
        $section->addText(Str::upper($institucional['plantel'] ?? ''), ['size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 220]);

        foreach (preg_split('/\R/u', (string) ($datos['texto_certificado_renderizado'] ?? '')) ?: [] as $linea) {
            $section->addText(trim($linea), ['size' => 6.5], ['alignment' => Jc::LEFT, 'spaceAfter' => 15]);
        }
        $section->addTextBreak(1);

        $tablaSemestres = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'borderSize' => 5,
            'borderColor' => '000000',
            'cellMargin' => 18,
        ]);
        $tablaSemestres->addRow();
        $celdaIzquierda = $tablaSemestres->addCell(4800, ['valign' => 'top']);
        $celdaDerecha = $tablaSemestres->addCell(4800, ['valign' => 'top']);
        $this->wordColumnaCertificado($celdaIzquierda, $datos['semestres_certificado_izquierda'] ?? []);
        $this->wordColumnaCertificado($celdaDerecha, $datos['semestres_certificado_derecha'] ?? []);

        $section->addTextBreak(1);
        $section->addText((string) ($datos['resumen_certificado'] ?? ''), ['size' => 6.5], ['alignment' => Jc::BOTH, 'spaceAfter' => 70]);
        $section->addText(
            'EXPEDIDO EN ' . Str::upper($institucional['localidad_expedicion'] ?? '')
            . ', A LOS ' . ($datos['fecha_documento_texto_letra'] ?? '') . '.',
            ['size' => 6.5],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 550],
        );

        $section->addText('______________________________', ['size' => 7], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($institucional['firmantes']['director']['nombre'] ?? 'SIN CONFIGURAR', ['size' => 6.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($institucional['firmantes']['director']['cargo'] ?? 'DIRECTOR(A) DEL PLANTEL', ['size' => 6.5], ['alignment' => Jc::CENTER]);

        $segunda = $phpWord->addSection(array_merge($sectionStyle, ['breakType' => 'nextPage']));
        $cajas = $segunda->addTable(['width' => 100 * 50, 'unit' => 'pct', 'borderSize' => 0, 'cellMargin' => 0]);
        $cajas->addRow();
        $izq = $cajas->addCell(4800, ['valign' => 'top']);
        $cajas->addCell(700);
        $der = $cajas->addCell(4800, ['valign' => 'top']);

        $cajaIzq = $izq->addTable(['borderSize' => 5, 'borderColor' => '000000', 'cellMargin' => 60]);
        $cajaIzq->addRow(2450, ['exactHeight' => true]);
        $celda = $cajaIzq->addCell(4500, ['valign' => 'top']);
        $celda->addText('REVISADO Y CONFRONTADO POR:', ['size' => 7], ['alignment' => Jc::CENTER]);
        $celda->addTextBreak(5);
        $celda->addText(Str::upper((string) ($datos['certificado_revisado_por'] ?? '')), ['size' => 6.5], ['alignment' => Jc::CENTER]);
        $celda->addText('FECHA:', ['size' => 6.5], ['alignment' => Jc::LEFT]);

        $cajaDer = $der->addTable(['borderSize' => 5, 'borderColor' => '000000', 'cellMargin' => 60]);
        $cajaDer->addRow(2450, ['exactHeight' => true]);
        $celda = $cajaDer->addCell(4500, ['valign' => 'top']);
        $celda->addText('JEFE DEL DEPARTAMENTO DE REGISTRO', ['size' => 7], ['alignment' => Jc::CENTER]);
        $celda->addText('Y CERTIFICACIÓN', ['size' => 7], ['alignment' => Jc::CENTER]);
        $celda->addTextBreak(5);
        $celda->addText(Str::upper((string) ($datos['certificado_jefe_registro_por'] ?? '')), ['size' => 6.5], ['alignment' => Jc::CENTER]);

        $segunda->addText('', [], ['spaceBefore' => 7600, 'spaceAfter' => 0]);
        $folioTabla = $segunda->addTable(['borderSize' => 5, 'borderColor' => '000000', 'cellMargin' => 45]);
        $folioTabla->addRow(650, ['exactHeight' => true]);
        $folioCelda = $folioTabla->addCell(1800, ['valign' => 'center']);
        $folioCelda->addText('FOLIO', ['size' => 8], ['alignment' => Jc::CENTER]);
        $folioCelda->addText((string) ($datos['folio'] ?? ''), ['bold' => true, 'size' => 8], ['alignment' => Jc::CENTER]);

        $footer = $segunda->addFooter();
        $footer->addText(
            Str::upper((string) ($institucional['leyenda_certificado'] ?? '')),
            ['size' => 4.5],
            ['alignment' => Jc::LEFT],
        );
    }

    private function wordColumnaCertificado($celda, iterable $semestres): void
    {
        $tabla = $celda->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'borderSize' => 4,
            'borderColor' => '000000',
            'cellMargin' => 18,
        ]);
        $tabla->addRow(280, ['exactHeight' => true]);
        $tabla->addCell(3000)->addText('ASIGNATURAS', ['bold' => true, 'size' => 5.5], ['alignment' => Jc::CENTER]);
        $tabla->addCell(700)->addText('CALIF. FINAL', ['bold' => true, 'size' => 5], ['alignment' => Jc::CENTER]);
        $tabla->addCell(1000)->addText('OBSERVACIONES', ['bold' => true, 'size' => 5], ['alignment' => Jc::CENTER]);

        $ordinales = [1 => 'PRIMER', 2 => 'SEGUNDO', 3 => 'TERCER', 4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];
        foreach ($semestres as $semestre) {
            $tabla->addRow(310, ['exactHeight' => true]);
            $titulo = $tabla->addCell(4700, ['gridSpan' => 3, 'valign' => 'center']);
            $texto = ($ordinales[$semestre['numero']] ?? $semestre['numero'] . '°') . ' SEMESTRE';
            if ($semestre['incluido']) {
                $texto .= ' - CICLO ESCOLAR ' . ($semestre['ciclo']?->nombre ?: '');
            }
            $titulo->addText($texto, ['bold' => true, 'size' => 5.2], ['alignment' => Jc::CENTER]);

            if (!$semestre['incluido']) {
                $tabla->addRow(1250, ['exactHeight' => true]);
                $vacia = $tabla->addCell(4700, ['gridSpan' => 3, 'valign' => 'center']);
                $vacia->addText('\\', ['size' => 46], ['alignment' => Jc::CENTER]);
                continue;
            }

            $materias = collect($semestre['oficiales'] ?? []);
            $tamano = $materias->count() > 10 ? 4.2 : ($materias->count() > 8 ? 4.8 : 5.3);
            foreach ($materias as $materia) {
                $tabla->addRow(120, ['exactHeight' => true]);
                $tabla->addCell(3000)->addText(Str::upper((string) $materia['nombre']), ['size' => $tamano]);
                $tabla->addCell(700)->addText((string) $materia['valor'], ['size' => $tamano], ['alignment' => Jc::CENTER]);
                $tabla->addCell(1000)->addText('', ['size' => $tamano]);
            }
        }
    }

    private function wordContexto($section, array $datos): void
    {
        $tabla = $section->addTable(['borderSize' => 4, 'borderColor' => '333333', 'cellMargin' => 50]);
        $tabla->addRow();
        $tabla->addCell()->addText('CCT: ' . ($datos['institucional']['cct'] ?? ''), ['bold' => true]);
        $tabla->addCell()->addText('CICLO: ' . ($datos['ciclo']->nombre ?? ''), ['bold' => true]);
        $tabla->addRow();
        $tabla->addCell()->addText('GENERACIÓN: ' . ($datos['generacion']->etiqueta ?? ''), ['bold' => true]);
        $tabla->addCell()->addText('SEMESTRE: ' . ($datos['semestre']->numero ?? '') . '° · GRUPO: ' . ($datos['grupo']->asignacionGrupo?->nombre ?? ''), ['bold' => true]);
        $section->addTextBreak(1);
    }

    private function wordAlumno($section, $alumno): void
    {
        $tabla = $section->addTable(['borderSize' => 4, 'borderColor' => '333333', 'cellMargin' => 50]);
        $tabla->addRow();
        $tabla->addCell()->addText('ALUMNO: ' . Str::upper(trim($alumno->apellido_paterno . ' ' . $alumno->apellido_materno . ' ' . $alumno->nombre)), ['bold' => true]);
        $tabla->addCell()->addText('MATRÍCULA: ' . $alumno->matricula, ['bold' => true]);
        $tabla->addRow();
        $tabla->addCell()->addText('CURP: ' . $alumno->curp);
        $tabla->addCell()->addText('GENERACIÓN: ' . ($alumno->generacion?->etiqueta ?? ''));
        $section->addTextBreak(1);
    }

    private function wordTablaMaterias($section, $materias, bool $extra): void
    {
        $tabla = $section->addTable(['borderSize' => 4, 'borderColor' => '444444', 'cellMargin' => 45]);
        $tabla->addRow();
        foreach (['Clave', 'Asignatura', 'Calificación final', '% Asist.', 'Regularización'] as $titulo) {
            $tabla->addCell()->addText($titulo, ['bold' => true, 'size' => 7], ['alignment' => Jc::CENTER]);
        }
        foreach ($materias as $materia) {
            $tabla->addRow();
            $asistencia = $materia['asistencia'] !== null
                ? number_format((float) $materia['asistencia'], 0) . '%'
                : '';
            foreach ([$materia['clave'], $materia['nombre'], $materia['valor'], $asistencia, ''] as $valor) {
                $tabla->addCell()->addText((string) $valor, ['size' => 7], ['alignment' => Jc::CENTER]);
            }
        }
    }

    private function firmasWord($section, array $firmantes): void
    {
        $section->addTextBreak(3);
        $tabla = $section->addTable(['width' => 100 * 50, 'unit' => 'pct', 'borderSize' => 0]);
        $tabla->addRow();
        foreach ($firmantes as $firmante) {
            $celda = $tabla->addCell();
            $celda->addText('______________________________', [], ['alignment' => Jc::CENTER]);
            $celda->addText($firmante['nombre'] ?? 'SIN CONFIGURAR', ['bold' => true, 'size' => 7], ['alignment' => Jc::CENTER]);
            $celda->addText($firmante['cargo'] ?? '', ['size' => 7], ['alignment' => Jc::CENTER]);
        }
    }


    private function firmasWordHistorial($section, array $firmantes, bool $incluirFirmas): void
    {
        $section->addTextBreak(1);
        $tabla = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'borderSize' => 0,
            'cellMargin' => 0,
        ]);
        $tabla->addRow(1750, ['exactHeight' => true]);

        foreach (['director', 'jefe_registro'] as $rol) {
            $firmante = $firmantes[$rol] ?? [];
            $celda = $tabla->addCell(4800, ['valign' => 'bottom']);

            if ($incluirFirmas) {
                $sello = (string) ($firmante['sello_ruta'] ?? '');
                $firma = (string) ($firmante['firma_ruta'] ?? '');

                if ($sello !== '' && is_file($sello)) {
                    $celda->addImage($sello, [
                        'width' => $rol === 'director' ? 78 : 105,
                        'height' => $rol === 'director' ? 78 : 65,
                        'alignment' => Jc::CENTER,
                    ]);
                }

                if ($firma !== '' && is_file($firma)) {
                    $celda->addImage($firma, [
                        'width' => 105,
                        'height' => 35,
                        'alignment' => Jc::CENTER,
                    ]);
                }
            }

            if (! $incluirFirmas || (! is_file((string) ($firmante['sello_ruta'] ?? '')) && ! is_file((string) ($firmante['firma_ruta'] ?? '')))) {
                $celda->addTextBreak(3);
                $celda->addText('______________________________', ['size' => 7], ['alignment' => Jc::CENTER]);
            }

            $celda->addText(
                Str::upper((string) ($firmante['nombre'] ?? 'SIN CONFIGURAR')),
                ['bold' => true, 'size' => 7],
                ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0],
            );
            $celda->addText(
                (string) ($firmante['cargo'] ?? ''),
                ['size' => 6.5],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0],
            );
        }
    }

    private function registrarEmision(
        Request $request,
        string $tipo,
        string $formato,
        array $datos,
        DocumentosOficialesService $service,
    ): void {
        $alumno = $datos['alumno'] ?? null;
        $modalidad = (string) ($datos['modalidad_certificado'] ?? '');
        $clave = $service->claveContexto($tipo, $datos, $modalidad);
        $contexto = $this->filtros($request);

        EmisionDocumentoMediaSuperior::query()->create([
            'tipo' => $tipo,
            'clave_contexto' => $clave,
            'formato' => $formato,
            'nivel_id' => $service->nivel()->id,
            'inscripcion_id' => $alumno?->id,
            'folio' => $alumno?->folio,
            'contexto' => $contexto,
            'hash_datos' => hash('sha256', json_encode([
                'contexto' => $contexto,
                'emitido_at' => now()->format('Y-m-d H:i:s.u'),
            ], JSON_UNESCAPED_UNICODE)),
            'emitido_por' => Auth::id(),
            'emitido_at' => now(),
        ]);
    }

    private function nombreArchivo(string $tipo, array $datos, string $formato): string
    {
        $extension = $formato === 'word' ? 'docx' : ($formato === 'excel' ? 'xlsx' : $formato);
        $sufijo = match ($tipo) {
            'registro-escolaridad' => collect([
                $datos['generacion']->etiqueta ?? null,
                ($datos['semestre']->numero ?? null) ? 'Sem' . $datos['semestre']->numero : null,
                $datos['grupo']->asignacionGrupo?->nombre ?? null,
            ])->filter()->implode('_'),
            'acta-resultados' => collect([
                $datos['asignacion']->materia?->clave ?: $datos['asignacion']->materia?->materia,
                $datos['grupo']->asignacionGrupo?->nombre ?? null,
            ])->filter()->implode('_'),
            'kardex' => collect([$datos['alumno']->matricula ?? null, $datos['alumno']->apellido_paterno ?? null])->filter()->implode('_'),
            'historial-academico' => collect([$datos['alumno']->matricula ?? null, $datos['alumno']->apellido_paterno ?? null, $datos['modo_historial'] ?? null])->filter()->implode('_'),
            'certificado' => collect([$datos['modalidad_certificado'] ?? null, $datos['folio'] ?? null, $datos['alumno']->matricula ?? null])->filter()->implode('_'),
            default => 'Documento',
        };

        return Str::slug(Str::headline($tipo) . '_' . $sufijo, '_') . '.' . $extension;
    }

    private function filtros(Request $request): array
    {
        return [
            'ciclo_escolar_id' => $request->integer('ciclo_escolar_id') ?: null,
            'generacion_id' => $request->integer('generacion_id') ?: null,
            'semestre_id' => $request->integer('semestre_id') ?: null,
            'grupo_id' => $request->integer('grupo_id') ?: null,
            'asignacion_materia_id' => $request->integer('asignacion_materia_id') ?: null,
            'inscripcion_id' => $request->integer('inscripcion_id') ?: null,
            'estatus' => $request->string('estatus')->toString() ?: 'todos',
            'modalidad' => $request->string('modalidad')->toString() ?: 'parcial',
            'fecha_documento' => $request->date('fecha_documento')?->format('Y-m-d') ?: now()->format('Y-m-d'),
            'certificado_revisado_por' => trim($request->string('certificado_revisado_por')->toString()),
            'certificado_jefe_registro_por' => trim($request->string('certificado_jefe_registro_por')->toString()),
            'historial_modo' => $request->string('historial_modo')->toString() === 'cursado' ? 'cursado' : 'completo',
            'historial_mostrar_foto' => $request->boolean('historial_mostrar_foto'),
            'historial_incluir_firmas' => $request->boolean('historial_incluir_firmas', true),
        ];
    }

    private function conFechaDocumento(array $datos, array $filtros): array
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m-d', (string) ($filtros['fecha_documento'] ?? ''));
        } catch (\Throwable) {
            $fecha = now();
        }

        if (!$fecha) {
            $fecha = now();
        }

        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        if (array_key_exists('modalidad_certificado', $datos)) {
            $revisadoPor = trim((string) ($filtros['certificado_revisado_por'] ?? ''));
            $jefeRegistro = trim((string) ($filtros['certificado_jefe_registro_por'] ?? ''));

            if ($revisadoPor === '' || $jefeRegistro === '') {
                throw new RuntimeException('Captura los nombres de Revisado y confrontado por y del Jefe del Departamento de Registro y Certificación.');
            }

            $datos['certificado_revisado_por'] = $revisadoPor;
            $datos['certificado_jefe_registro_por'] = $jefeRegistro;
        }

        $datos['fecha_documento'] = $fecha->copy()->startOfDay();
        $datos['fecha_documento_corta'] = $fecha->format('d/m/Y');
        $datos['fecha_documento_texto_letra'] = $this->fechaDocumentoEnLetras($fecha);
        $datos['fecha_documento_texto'] = sprintf(
            '%02d DE %s DE %04d',
            $fecha->day,
            $meses[$fecha->month],
            $fecha->year,
        );

        return $datos;
    }

    private function fechaDocumentoEnLetras(Carbon $fecha): string
    {
        $dias = [
            1 => 'UN',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ',
            11 => 'ONCE',
            12 => 'DOCE',
            13 => 'TRECE',
            14 => 'CATORCE',
            15 => 'QUINCE',
            16 => 'DIECISÉIS',
            17 => 'DIECISIETE',
            18 => 'DIECIOCHO',
            19 => 'DIECINUEVE',
            20 => 'VEINTE',
            21 => 'VEINTIÚN',
            22 => 'VEINTIDÓS',
            23 => 'VEINTITRÉS',
            24 => 'VEINTICUATRO',
            25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS',
            27 => 'VEINTISIETE',
            28 => 'VEINTIOCHO',
            29 => 'VEINTINUEVE',
            30 => 'TREINTA',
            31 => 'TREINTA Y UN',
        ];
        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        return sprintf(
            '%s DÍAS DEL MES DE %s DEL AÑO %s',
            $dias[$fecha->day] ?? $this->numeroEnLetras($fecha->day),
            $meses[$fecha->month],
            $this->numeroEnLetras($fecha->year),
        );
    }

    private function numeroEnLetras(int $numero): string
    {
        if ($numero === 0) {
            return 'CERO';
        }

        if ($numero >= 1000) {
            $miles = intdiv($numero, 1000);
            $resto = $numero % 1000;
            $prefijo = $miles === 1 ? 'MIL' : $this->numeroEnLetras($miles) . ' MIL';

            return trim($prefijo . ($resto ? ' ' . $this->numeroEnLetras($resto) : ''));
        }

        if ($numero >= 100) {
            if ($numero === 100) {
                return 'CIEN';
            }
            $centenas = [1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS', 6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS'];
            $centena = intdiv($numero, 100);
            $resto = $numero % 100;

            return trim($centenas[$centena] . ($resto ? ' ' . $this->numeroEnLetras($resto) : ''));
        }

        $especiales = [
            1 => 'UNO',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ',
            11 => 'ONCE',
            12 => 'DOCE',
            13 => 'TRECE',
            14 => 'CATORCE',
            15 => 'QUINCE',
            16 => 'DIECISÉIS',
            17 => 'DIECISIETE',
            18 => 'DIECIOCHO',
            19 => 'DIECINUEVE',
            20 => 'VEINTE',
            21 => 'VEINTIUNO',
            22 => 'VEINTIDÓS',
            23 => 'VEINTITRÉS',
            24 => 'VEINTICUATRO',
            25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS',
            27 => 'VEINTISIETE',
            28 => 'VEINTIOCHO',
            29 => 'VEINTINUEVE',
        ];

        if (isset($especiales[$numero])) {
            return $especiales[$numero];
        }

        $decenas = [3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA'];
        $decena = intdiv($numero, 10);
        $unidad = $numero % 10;

        return $decenas[$decena] . ($unidad ? ' Y ' . $this->numeroEnLetras($unidad) : '');
    }

    private function redirigirConErrorDocumento(string $tipo, string $mensaje)
    {
        return redirect()
            ->route('media-superior.documentos.modulo', ['modulo' => $tipo])
            ->with('documento_oficial_error', [
                'tipo' => 'error',
                'titulo' => 'No fue posible generar el documento',
                'mensaje' => $mensaje,
                'detalles' => [
                    'Corrige la información indicada y vuelve a intentar la descarga.',
                ],
            ]);
    }

    private function validarTipoFormato(string $tipo, string $formato): void
    {
        abort_unless(in_array($tipo, $this->tipos(), true), 404);
        abort_unless(in_array($formato, ['pdf', 'word', 'excel'], true), 404);
    }

    /** @return array<int, string> */
    private function tipos(): array
    {
        return [
            DocumentosOficialesService::TIPO_REGISTRO,
            DocumentosOficialesService::TIPO_ACTA,
            DocumentosOficialesService::TIPO_KARDEX,
            DocumentosOficialesService::TIPO_HISTORIAL,
            DocumentosOficialesService::TIPO_CERTIFICADO,
        ];
    }
}
