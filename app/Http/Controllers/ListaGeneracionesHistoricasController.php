<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Nivel;
use App\Services\ListaGeneracionesHistoricasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ListaGeneracionesHistoricasController extends Controller
{
    public function __construct(
        private readonly ListaGeneracionesHistoricasService $service,
    ) {
    }

    public function __invoke(Request $request, string $slug_nivel, string $formato)
    {
        abort_unless(in_array($formato, ['pdf', 'word'], true), 404);

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $validados = $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_ids' => ['required', 'array', 'min:1'],
            'generacion_ids.*' => ['required', 'integer', 'distinct', 'exists:generaciones,id'],
            'estatus' => ['required', 'string', 'in:' . implode(',', ListaGeneracionesHistoricasService::ESTATUS)],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'incluir_archivados' => ['nullable', 'boolean'],
            'salida' => ['nullable', 'in:unico,zip'],
            'descargar' => ['nullable', 'boolean'],
        ]);

        $generacionIds = collect($validados['generacion_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $cicloEscolarId = (int) $validados['ciclo_escolar_id'];
        $estatus = (string) $validados['estatus'];
        $grupoId = isset($validados['grupo_id']) ? (int) $validados['grupo_id'] : null;
        $incluirArchivados = $request->boolean('incluir_archivados');
        $salida = (string) ($validados['salida'] ?? 'unico');

        if ($salida === 'zip') {
            return $this->zip(
                nivel: $nivel,
                generacionIds: $generacionIds,
                formato: $formato,
                estatus: $estatus,
                grupoId: $grupoId,
                incluirArchivados: $incluirArchivados,
                cicloEscolarId: $cicloEscolarId,
            );
        }

        $datos = $this->service->generar(
            nivel: $nivel,
            generacionIds: $generacionIds,
            estatus: $estatus,
            grupoId: $grupoId,
            incluirArchivados: $incluirArchivados,
            cicloEscolarId: $cicloEscolarId,
        );

        return $formato === 'pdf'
            ? $this->pdf($datos, $request->boolean('descargar'))
            : $this->word($datos);
    }

    private function pdf(array $datos, bool $descargar)
    {
        $nombre = $this->service->nombreBase($datos) . '.pdf';

        $pdf = Pdf::loadView('pdf.lista-generaciones-historicas', $datos)
            ->setPaper('letter', 'landscape')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return $descargar
            ? $pdf->download($nombre)
            : $pdf->stream($nombre);
    }

    private function word(array $datos): BinaryFileResponse
    {
        $directorio = $this->directorioTemporal();
        $ruta = $directorio . DIRECTORY_SEPARATOR
            . $this->service->nombreBase($datos)
            . '-' . Str::uuid() . '.docx';

        $this->guardarWord($datos, $ruta);

        return response()
            ->download($ruta, $this->service->nombreBase($datos) . '.docx')
            ->deleteFileAfterSend(true);
    }

    private function zip(
        Nivel $nivel,
        array $generacionIds,
        string $formato,
        string $estatus,
        ?int $grupoId,
        bool $incluirArchivados,
        int $cicloEscolarId,
    ): BinaryFileResponse {
        $baseTemp = $this->directorioTemporal();
        $identificador = (string) Str::uuid();
        $directorioTrabajo = $baseTemp . DIRECTORY_SEPARATOR . 'listas-historicas-' . $identificador;
        File::ensureDirectoryExists($directorioTrabajo, 0775, true);

        $grupoGeneracionId = $grupoId
            ? Grupo::query()->whereKey($grupoId)->value('generacion_id')
            : null;

        $archivos = [];

        foreach ($generacionIds as $generacionId) {
            if ($grupoGeneracionId && (int) $grupoGeneracionId !== (int) $generacionId) {
                continue;
            }

            $datos = $this->service->generar(
                nivel: $nivel,
                generacionIds: [(int) $generacionId],
                estatus: $estatus,
                grupoId: $grupoId,
                incluirArchivados: $incluirArchivados,
                cicloEscolarId: $cicloEscolarId,
            );

            $nombreBase = $this->service->nombreBase($datos);
            $extension = $formato === 'pdf' ? 'pdf' : 'docx';
            $ruta = $directorioTrabajo . DIRECTORY_SEPARATOR . $nombreBase . '.' . $extension;

            if ($formato === 'pdf') {
                Pdf::loadView('pdf.lista-generaciones-historicas', $datos)
                    ->setPaper('letter', 'landscape')
                    ->setOption('isRemoteEnabled', false)
                    ->save($ruta);
            } else {
                $this->guardarWord($datos, $ruta);
            }

            $archivos[] = $ruta;
        }

        abort_if(empty($archivos), 404, 'No hay documentos que coincidan con los filtros seleccionados.');

        $nombreZip = sprintf(
            'Listas_Historicas_%s_%s.zip',
            Str::studly(Str::ascii($nivel->nombre)),
            mb_strtoupper($formato)
        );

        $rutaZip = $baseTemp . DIRECTORY_SEPARATOR . $identificador . '-' . $nombreZip;
        $zip = new ZipArchive();

        abort_unless(
            $zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true,
            500,
            'No se pudo crear el archivo ZIP.'
        );

        foreach ($archivos as $archivo) {
            $zip->addFile($archivo, basename($archivo));
        }

        $zip->close();
        File::deleteDirectory($directorioTrabajo);

        return response()
            ->download($rutaZip, $nombreZip)
            ->deleteFileAfterSend(true);
    }

    private function guardarWord(array $datos, string $ruta): void
    {
        $directorioPhpWord = $this->directorioTemporal() . DIRECTORY_SEPARATOR . 'phpword';
        File::ensureDirectoryExists($directorioPhpWord, 0775, true);

        putenv('TMP=' . $directorioPhpWord);
        putenv('TEMP=' . $directorioPhpWord);
        putenv('TMPDIR=' . $directorioPhpWord);

        $settingsClass = 'PhpOffice\\PhpWord\\Settings';

        if (class_exists($settingsClass) && method_exists($settingsClass, 'setTempDir')) {
            $settingsClass::setTempDir($directorioPhpWord);
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8);

        $info = $phpWord->getDocInfo();
        $info->setCreator('Centro Universitario Moctezuma');
        $info->setCompany('Centro Universitario Moctezuma');
        $info->setTitle($datos['titulo']);
        $info->setSubject('Listas históricas de alumnos por generación');
        $info->setDescription('Documento institucional editable generado desde el sistema escolar.');

        $phpWord->addTableStyle('EncabezadoInstitucional', [
            'borderSize' => 0,
            'cellMargin' => 25,
            'alignment' => 'center',
        ]);

        $phpWord->addTableStyle('ResumenHistorico', [
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 55,
            'alignment' => 'center',
        ]);

        $phpWord->addTableStyle('TablaHistorica', [
            'borderSize' => 5,
            'borderColor' => '64748B',
            'cellMargin' => 35,
            'alignment' => 'center',
        ]);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 15840,
            'pageSizeH' => 12240,
            'marginTop' => 520,
            'marginBottom' => 520,
            'marginLeft' => 540,
            'marginRight' => 540,
            'headerHeight' => 220,
            'footerHeight' => 220,
        ]);

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Centro Universitario Moctezuma · Página {PAGE} de {NUMPAGES}',
            ['name' => 'Arial', 'size' => 7, 'color' => '64748B'],
            ['alignment' => 'center']
        );

        foreach ($datos['generaciones'] as $indice => $generacion) {
            if ($indice > 0) {
                $section->addPageBreak();
            }

            $this->agregarEncabezadoWord($section, $datos, $generacion);
            $this->agregarResumenWord($section, $generacion['resumen']);

            if (collect($generacion['grupos'])->isEmpty()) {
                $section->addText(
                    'No se encontraron alumnos con los filtros seleccionados para esta generación.',
                    ['name' => 'Arial', 'size' => 10, 'italic' => true, 'color' => '64748B'],
                    ['alignment' => 'center', 'spaceBefore' => 260, 'spaceAfter' => 120]
                );

                continue;
            }

            $consecutivo = 1;

            foreach ($generacion['grupos'] as $grupo) {
                $section->addText(
                    $grupo['titulo'],
                    ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => '006492'],
                    ['spaceBefore' => 120, 'spaceAfter' => 45, 'keepNext' => true]
                );

                $tabla = $section->addTable('TablaHistorica');
                $encabezado = $tabla->addRow(340, ['tblHeader' => true, 'cantSplit' => true]);
                $titulos = [
                    ['No.', 430],
                    ['Matrícula', 1280],
                    ['Nombre completo', 3100],
                    ['CURP', 1900],
                    ['Género', 820],
                    ['Generación', 1050],
                    ['Grupo', 1300],
                    ['Estatus / fecha de egreso', 1850],
                ];

                foreach ($titulos as [$titulo, $ancho]) {
                    $encabezado->addCell($ancho, ['bgColor' => '006492', 'valign' => 'center'])
                        ->addText(
                            $titulo,
                            ['name' => 'Arial', 'size' => 6.5, 'bold' => true, 'color' => 'FFFFFF'],
                            ['alignment' => 'center', 'spaceAfter' => 0]
                        );
                }

                foreach ($grupo['alumnos'] as $alumno) {
                    $fila = $tabla->addRow(310, ['cantSplit' => true]);
                    $valores = [
                        (string) $consecutivo++,
                        $alumno['matricula'],
                        $alumno['nombre'],
                        $alumno['curp'],
                        $alumno['genero'],
                        $alumno['generacion'],
                        $alumno['grupo'],
                        $alumno['estatus'] . ($alumno['fecha_egreso'] !== '—' ? "\n" . $alumno['fecha_egreso'] : ''),
                    ];

                    foreach ($valores as $posicion => $valor) {
                        $alineacion = in_array($posicion, [0, 4, 5], true) ? 'center' : 'left';
                        $fila->addCell($titulos[$posicion][1], [
                            'valign' => 'center',
                            'bgColor' => $consecutivo % 2 === 0 ? 'F8FAFC' : 'FFFFFF',
                        ])->addText(
                            $valor,
                            ['name' => 'Arial', 'size' => 6.5, 'color' => '0F172A'],
                            ['alignment' => $alineacion, 'spaceAfter' => 0]
                        );
                    }
                }

                $section->addText(
                    'Subtotal: ' . $grupo['resumen']['total']
                        . ' alumnos · Hombres: ' . $grupo['resumen']['hombres']
                        . ' · Mujeres: ' . $grupo['resumen']['mujeres'],
                    ['name' => 'Arial', 'size' => 7, 'bold' => true, 'color' => '475569'],
                    ['alignment' => 'right', 'spaceBefore' => 35, 'spaceAfter' => 70]
                );
            }
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
    }

    private function agregarEncabezadoWord($section, array $datos, array $generacion): void
    {
        $tabla = $section->addTable('EncabezadoInstitucional');
        $fila = $tabla->addRow(800);
        $izquierda = $fila->addCell(1800, ['valign' => 'center']);
        $centro = $fila->addCell(9700, ['valign' => 'center']);
        $derecha = $fila->addCell(1800, ['valign' => 'center']);

        $this->agregarLogoWord($izquierda, $this->rutaLogoNivel($datos['nivel']));
        $this->agregarLogoWord($derecha, public_path('imagenes/logo-letra.png'));

        $centro->addText(
            'CENTRO UNIVERSITARIO MOCTEZUMA A.C.',
            ['name' => 'Arial', 'size' => 13, 'bold' => true, 'color' => '006492'],
            ['alignment' => 'center', 'spaceAfter' => 20]
        );
        $centro->addText(
            mb_strtoupper($datos['nivel']->nombre) . ' · C.C.T. ' . $datos['nivel']->cct,
            ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => '88AC2E'],
            ['alignment' => 'center', 'spaceAfter' => 20]
        );
        $centro->addText(
            $datos['titulo'],
            ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => '0F172A'],
            ['alignment' => 'center', 'spaceAfter' => 20]
        );
        $centro->addText(
            'Generación ' . $generacion['etiqueta']
                . ' · ' . $generacion['estado']
                . ' · Filtro: ' . $datos['estatus_etiqueta'],
            ['name' => 'Arial', 'size' => 8, 'color' => '475569'],
            ['alignment' => 'center', 'spaceAfter' => 0]
        );

        $section->addText(
            'Documento generado el ' . $datos['fecha_generacion']
                . ($datos['incluir_archivados'] ? ' · Incluye alumnos archivados' : ''),
            ['name' => 'Arial', 'size' => 7, 'italic' => true, 'color' => '64748B'],
            ['alignment' => 'center', 'spaceBefore' => 35, 'spaceAfter' => 75]
        );
    }

    private function agregarResumenWord($section, array $resumen): void
    {
        $tabla = $section->addTable('ResumenHistorico');
        $fila = $tabla->addRow(430);
        $datos = [
            ['TOTAL', $resumen['total'], 'E2E8F0', '0F172A'],
            ['HOMBRES', $resumen['hombres'], 'DBEAFE', '1D4ED8'],
            ['MUJERES', $resumen['mujeres'], 'FCE7F3', 'BE185D'],
            ['EGRESADOS', $resumen['egresados'], 'DCFCE7', '15803D'],
            ['BAJAS', $resumen['bajas'], 'FFEDD5', 'C2410C'],
            ['TRASLADADOS', $resumen['trasladados'], 'EDE9FE', '6D28D9'],
            ['ARCHIVADOS', $resumen['archivados'], 'F1F5F9', '475569'],
        ];

        foreach ($datos as [$etiqueta, $valor, $fondo, $color]) {
            $celda = $fila->addCell(1850, ['bgColor' => $fondo, 'valign' => 'center']);
            $celda->addText(
                $etiqueta,
                ['name' => 'Arial', 'size' => 6.5, 'bold' => true, 'color' => $color],
                ['alignment' => 'center', 'spaceAfter' => 0]
            );
            $celda->addText(
                (string) $valor,
                ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => $color],
                ['alignment' => 'center', 'spaceAfter' => 0]
            );
        }
    }

    private function agregarLogoWord($celda, ?string $ruta): void
    {
        if (!$ruta || !is_file($ruta) || !is_readable($ruta)) {
            return;
        }

        try {
            $celda->addImage($ruta, [
                'width' => 64,
                'height' => 64,
                'alignment' => 'center',
            ]);
        } catch (\Throwable) {
            // El documento continúa sin el logotipo si el archivo no es compatible.
        }
    }

    private function rutaLogoNivel(Nivel $nivel): ?string
    {
        if ($nivel->logo) {
            $ruta = storage_path('app/public/logos/' . $nivel->logo);

            if (is_file($ruta)) {
                return $ruta;
            }
        }

        $fallback = public_path('imagenes/logo-letra.png');

        return is_file($fallback) ? $fallback : null;
    }

    private function directorioTemporal(): string
    {
        $directorio = storage_path('app/temp/listas-historicas');
        File::ensureDirectoryExists($directorio, 0775, true);

        abort_unless(is_writable($directorio), 500, 'El directorio temporal no tiene permisos de escritura.');

        return $directorio;
    }
}
