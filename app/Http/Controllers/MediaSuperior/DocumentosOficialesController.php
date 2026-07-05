<?php

namespace App\Http\Controllers\MediaSuperior;

use App\Exports\MediaSuperior\DocumentoOficialExport;
use App\Http\Controllers\Controller;
use App\Models\EmisionDocumentoMediaSuperior;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Services\MediaSuperior\DocumentosOficialesService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
            abort(422, $exception->getMessage());
        }

        $nombre = $this->nombreArchivo($tipo, $datos, $formato);
        if (! $request->boolean('preview')) {
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
    ): BinaryFileResponse {
        abort_unless(Auth::user()?->is_admin, 403);
        abort_unless(in_array($tipo, $this->tipos(), true), 404);

        $formato = $request->string('formato')->lower()->toString() ?: 'pdf';
        abort_unless(in_array($formato, ['pdf', 'word'], true), 422, 'El ZIP solo admite PDF o Word.');

        $lote = $this->construirLote($request, $tipo, $service);
        abort_if($lote === [], 422, 'No hay documentos disponibles con los filtros seleccionados.');

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
                $datos = $tipo === DocumentosOficialesService::TIPO_KARDEX
                    ? $service->kardex($alumno->id)
                    : $service->certificado($alumno->id, (string) ($filtros['modalidad'] ?? 'parcial'));
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

    private function crearWordTemporal(string $tipo, array $datos): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8);

        $sectionStyle = $tipo === DocumentosOficialesService::TIPO_REGISTRO
            ? ['pageSizeW' => 20160, 'pageSizeH' => 12240, 'orientation' => 'landscape', 'marginTop' => 360, 'marginBottom' => 360, 'marginLeft' => 360, 'marginRight' => 360]
            : ['pageSizeW' => 12240, 'pageSizeH' => 15840, 'marginTop' => 500, 'marginBottom' => 500, 'marginLeft' => 600, 'marginRight' => 600];
        $section = $phpWord->addSection($sectionStyle);

        $this->encabezadoWord($section, $datos['institucional'] ?? []);

        match ($tipo) {
            DocumentosOficialesService::TIPO_REGISTRO => $this->wordRegistro($section, $datos),
            DocumentosOficialesService::TIPO_ACTA => $this->wordActa($section, $datos),
            DocumentosOficialesService::TIPO_KARDEX => $this->wordKardex($section, $datos),
            DocumentosOficialesService::TIPO_CERTIFICADO => $this->wordCertificado($section, $datos),
            default => null,
        };

        $ruta = storage_path('app/temp/' . Str::uuid() . '.docx');
        File::ensureDirectoryExists(dirname($ruta));
        WordIOFactory::createWriter($phpWord, 'Word2007')->save($ruta);

        return $ruta;
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
        if (is_file($institucional['logo_plantel'] ?? '')) {
            $derecha->addImage($institucional['logo_plantel'], ['width' => 125, 'height' => 42]);
        }
        $section->addTextBreak(1);
    }

    private function wordRegistro($section, array $datos): void
    {
        $section->addText('REGISTRO DE ESCOLARIDAD', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
        $this->wordContexto($section, $datos);
        $tabla = $section->addTable(['borderSize' => 5, 'borderColor' => '333333', 'cellMargin' => 35]);
        $tabla->addRow();
        foreach (array_merge(['No.', 'Matrícula', 'Alumno', 'Sexo'], collect($datos['asignaciones'])->map(fn ($a) => $a->materia?->clave ?: $a->materia?->materia)->all(), ['No acr.', 'Situación']) as $titulo) {
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
    }

    private function wordCertificado($section, array $datos): void
    {
        $section->addText('CERTIFICADO ' . Str::upper($datos['modalidad_certificado']), ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $section->addText('FOLIO: ' . $datos['folio'], ['bold' => true, 'size' => 9], ['alignment' => Jc::RIGHT]);
        $this->wordAlumno($section, $datos['alumno']);
        foreach ($datos['semestres_certificados'] as $semestre) {
            $section->addText($semestre['numero'] . '° SEMESTRE · ' . ($semestre['ciclo']?->nombre ?: ''), ['bold' => true, 'size' => 9]);
            $this->wordTablaMaterias($section, $semestre['oficiales'], false);
            if (($datos['institucional']['mostrar_materias_extra'] ?? true) && $semestre['extras']->isNotEmpty()) {
                $section->addText('MATERIAS EXTRA INFORMATIVAS · NO PROMEDIAN', ['bold' => true, 'color' => '8A5A00']);
                $this->wordTablaMaterias($section, $semestre['extras'], true);
            }
        }
        $section->addText('PROMEDIO: ' . $datos['promedio_certificado'], ['bold' => true, 'size' => 11], ['alignment' => Jc::RIGHT]);
        $section->addText('SE EXPIDE EN ' . Str::upper($datos['institucional']['localidad_expedicion'] ?? '') . ', A ' . ($datos['fecha_documento_texto'] ?? ''), [], ['alignment' => Jc::RIGHT]);
        $this->firmasWord($section, Arr::only($datos['institucional']['firmantes'] ?? [], ['director', 'jefe_registro']));
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

        EmisionDocumentoMediaSuperior::query()->updateOrCreate(
            [
                'tipo' => $tipo,
                'clave_contexto' => $clave,
                'formato' => $formato,
            ],
            [
                'nivel_id' => $service->nivel()->id,
                'inscripcion_id' => $alumno?->id,
                'folio' => $alumno?->folio,
                'contexto' => $contexto,
                'hash_datos' => hash('sha256', json_encode([
                    'contexto' => $contexto,
                    'actualizado' => now()->format('Y-m-d H:i'),
                ], JSON_UNESCAPED_UNICODE)),
                'emitido_por' => Auth::id(),
                'emitido_at' => now(),
            ],
        );
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
        ];
    }

    private function conFechaDocumento(array $datos, array $filtros): array
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m-d', (string) ($filtros['fecha_documento'] ?? ''));
        } catch (\Throwable) {
            $fecha = now();
        }

        if (! $fecha) {
            $fecha = now();
        }

        $meses = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        $datos['fecha_documento'] = $fecha->copy()->startOfDay();
        $datos['fecha_documento_corta'] = $fecha->format('d/m/Y');
        $datos['fecha_documento_texto'] = sprintf(
            '%02d DE %s DE %04d',
            $fecha->day,
            $meses[$fecha->month],
            $fecha->year,
        );

        return $datos;
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
            DocumentosOficialesService::TIPO_CERTIFICADO,
        ];
    }
}
