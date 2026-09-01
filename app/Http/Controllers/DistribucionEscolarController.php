<?php

namespace App\Http\Controllers;

use App\Exports\Distribucion\DistribucionEscolarExport;
use App\Models\Nivel;
use App\Services\DistribucionEscolarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormato;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DistribucionEscolarController extends Controller
{
    public function pdf(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ) {
        abort_unless(auth()->user()?->is_admin, 403);

        [$nivel, $filtros] = $this->resolver(
            $request,
            $slug_nivel,
            $service
        );

        $bloques = $service->bloques($nivel, $filtros);
        $listado = $service->listadoCompleto($nivel, $filtros);

        abort_if(
            $bloques->isEmpty(),
            404,
            'No se encontraron datos para generar la distribución escolar.'
        );

        $nombreArchivo = $this->nombreBase($nivel, $filtros) . '.pdf';

        return $this->crearPdf(
            $nivel,
            $bloques,
            $listado,
            $filtros
        )->stream($nombreArchivo);
    }

public function word(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ): BinaryFileResponse {
        abort_unless(auth()->user()?->is_admin, 403);

        [$nivel, $filtros] = $this->resolver($request, $slug_nivel, $service);
        $bloques = $service->bloques($nivel, $filtros);

        abort_if(
            $bloques->isEmpty(),
            404,
            'No se encontraron datos para generar el archivo Word.'
        );

        $nombreArchivo = $this->nombreBase($nivel, $filtros) . '.docx';
        $ruta = storage_path('app/temp/' . $nombreArchivo);

        File::ensureDirectoryExists(dirname($ruta));

        $this->crearWord($nivel, $bloques, $filtros, $ruta);

        return response()
            ->download($ruta, $nombreArchivo)
            ->deleteFileAfterSend(true);
    }
    public function excel(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ): BinaryFileResponse {
        abort_unless(auth()->user()?->is_admin, 403);

        [$nivel, $filtros] = $this->resolver($request, $slug_nivel, $service);
        $bloques = $service->bloques($nivel, $filtros);
        $listado = $service->listadoCompleto($nivel, $filtros);

        abort_if($bloques->isEmpty(), 404, 'No se encontraron datos para generar el archivo Excel.');

        return Excel::download(
            new DistribucionEscolarExport($bloques, $listado),
            $this->nombreBase($nivel, $filtros) . '.xlsx'
        );
    }

    public function zip(
        Request $request,
        string $slug_nivel,
        DistribucionEscolarService $service
    ): BinaryFileResponse {
        abort_unless(auth()->user()?->is_admin, 403);
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        [$nivel, $filtros] = $this->resolver($request, $slug_nivel, $service);
        $bloques = $service->bloques($nivel, $filtros);
        $listado = $service->listadoCompleto($nivel, $filtros);

        abort_if($bloques->isEmpty(), 404, 'No se encontraron datos para generar el archivo ZIP.');

        $directorio = storage_path('app/temp/distribucion_' . Str::uuid());
        File::ensureDirectoryExists($directorio . '/Generaciones');

        $nombreBase = $this->nombreBase($nivel, $filtros);
        $pdfGeneral = $directorio . '/' . $nombreBase . '.pdf';
        $excelGeneral = $directorio . '/' . $nombreBase . '.xlsx';

        File::put(
            $pdfGeneral,
            $this->crearPdf($nivel, $bloques, $listado, $filtros)->output()
        );

        File::put(
            $excelGeneral,
            Excel::raw(
                new DistribucionEscolarExport($bloques, $listado),
                ExcelFormato::XLSX
            )
        );

        $generaciones = $bloques
            ->flatMap(fn(array $bloque) => collect($bloque['filas']))
            ->filter(fn(array $fila) => !empty($fila['generacion_id']))
            ->unique('generacion_id')
            ->sortBy('generacion_ingreso')
            ->values();

        foreach ($generaciones as $filaGeneracion) {
            $filtrosGeneracion = array_merge($filtros, [
                'generacion_id' => (int) $filaGeneracion['generacion_id'],
            ]);

            $bloquesGeneracion = $service->bloques($nivel, $filtrosGeneracion);
            $listadoGeneracion = $service->listadoCompleto($nivel, $filtrosGeneracion);

            if ($bloquesGeneracion->isEmpty()) {
                continue;
            }

            $slugGeneracion = Str::slug((string) $filaGeneracion['generacion'], '_');
            $carpetaGeneracion = $directorio . '/Generaciones/Generacion_' . $slugGeneracion;
            File::ensureDirectoryExists($carpetaGeneracion);

            File::put(
                $carpetaGeneracion . '/Distribucion_' . $slugGeneracion . '.pdf',
                $this->crearPdf(
                    $nivel,
                    $bloquesGeneracion,
                    $listadoGeneracion,
                    $filtrosGeneracion,
                    'Generación ' . $filaGeneracion['generacion']
                )->output()
            );

            File::put(
                $carpetaGeneracion . '/Listado_' . $slugGeneracion . '.xlsx',
                Excel::raw(
                    new DistribucionEscolarExport($bloquesGeneracion, $listadoGeneracion),
                    ExcelFormato::XLSX
                )
            );
        }

        $zipPath = storage_path('app/temp/' . $nombreBase . '_' . Str::random(8) . '.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        $resultado = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        abort_unless($resultado === true, 500, 'No fue posible crear el archivo ZIP.');

        foreach (File::allFiles($directorio) as $archivo) {
            $rutaRelativa = Str::after($archivo->getPathname(), $directorio . DIRECTORY_SEPARATOR);
            $zip->addFile($archivo->getPathname(), str_replace(DIRECTORY_SEPARATOR, '/', $rutaRelativa));
        }

        $zip->close();
        File::deleteDirectory($directorio);

        return response()
            ->download($zipPath, $nombreBase . '.zip')
            ->deleteFileAfterSend(true);
    }

    private function resolver(
        Request $request,
        string $slugNivel,
        DistribucionEscolarService $service
    ): array {
        $categorias = implode(',', array_keys($service->categorias()));

        $datos = $request->validate([
            'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'estado' => ['nullable', 'in:todos,' . $categorias],
            'solo_ya_no_estan' => ['nullable', 'boolean'],
        ]);

        $nivel = Nivel::query()->with('director')->where('slug', $slugNivel)->firstOrFail();

        return [$nivel, [
            'ciclo_escolar_id' => $datos['ciclo_escolar_id'] ?? null,
            'generacion_id' => $datos['generacion_id'] ?? null,
            'grado_id' => $datos['grado_id'] ?? null,
            'grupo_id' => $datos['grupo_id'] ?? null,
            'semestre_id' => $datos['semestre_id'] ?? null,
            'estado' => $datos['estado'] ?? 'todos',
            'solo_ya_no_estan' => (bool) ($datos['solo_ya_no_estan'] ?? false),
        ]];
    }

    private function crearPdf(
        Nivel $nivel,
        Collection $bloques,
        Collection $listado,
        array $filtros,
        ?string $subtitulo = null
    ) {
        return Pdf::loadView('pdf.distribucion-escolar-historica', [
            'nivel' => $nivel,
            'bloques' => $bloques,
            'listado' => $listado,
            'filtros' => $filtros,
            'subtitulo' => $subtitulo,
            'logo' => $this->imagenBase64(public_path('imagenes/logo-letra.png')),
            'generadoPor' => auth()->user()?->name ?: 'Administración',
            'generadoEn' => now(),
        ])->setPaper('letter', 'landscape');
    }

private function crearWord(
        Nivel $nivel,
        Collection $bloques,
        array $filtros,
        string $ruta
    ): void {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 15840,
            'pageSizeH' => 12240,
            'marginTop' => 600,
            'marginBottom' => 650,
            'marginLeft' => 650,
            'marginRight' => 650,
            'headerHeight' => 250,
            'footerHeight' => 250,
        ]);

        $headerTable = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 0,
        ]);

        $headerTable->addRow();

        $logoCell = $headerTable->addCell(2300);

        $logoPath = public_path('imagenes/logo-letra.png');

        if (is_file($logoPath)) {
            $logoCell->addImage($logoPath, [
                'width' => 120,
                'height' => 52,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            ]);
        } else {
            $logoCell->addText(
                'MOCTEZUMA',
                ['bold' => true, 'color' => '006492', 'size' => 12]
            );
        }

        $titleCell = $headerTable->addCell(7800);
        $titleCell->addText(
            'CENTRO UNIVERSITARIO MOCTEZUMA',
            ['bold' => true, 'color' => '006492', 'size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 40]
        );
        $titleCell->addText(
            'DISTRIBUCIÓN ESCOLAR',
            ['bold' => true, 'color' => '111827', 'size' => 17],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 40]
        );
        $titleCell->addText(
            mb_strtoupper((string) $nivel->nombre),
            ['bold' => true, 'color' => '88AC2E', 'size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $metaCell = $headerTable->addCell(2800);
        $metaCell->addText(
            'CCT: ' . ($nivel->cct ?: '—'),
            ['bold' => true, 'color' => '475569', 'size' => 8],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );
        $metaCell->addText(
            'Emisión: ' . now()->format('d/m/Y H:i'),
            ['color' => '64748B', 'size' => 7.5],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );
        $metaCell->addText(
            'Usuario: ' . (auth()->user()?->name ?: 'Administración'),
            ['color' => '64748B', 'size' => 7.5],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );

        $section->addLine([
            'weight' => 2,
            'width' => 100,
            'height' => 0,
            'color' => '006492',
        ]);

        $global = [
            'hombres' => (int) $bloques->sum(
                fn (array $bloque) => $bloque['totales']['hombres_vigentes'] ?? 0
            ),
            'mujeres' => (int) $bloques->sum(
                fn (array $bloque) => $bloque['totales']['mujeres_vigentes'] ?? 0
            ),
            'total' => (int) $bloques->sum(
                fn (array $bloque) => $bloque['totales']['total_historico'] ?? 0
            ),
            'activos' => (int) $bloques->sum(
                fn (array $bloque) => $bloque['totales']['activos'] ?? 0
            ),
            'no_vigentes' => (int) $bloques->sum(
                fn (array $bloque) => $bloque['totales']['no_vigentes'] ?? 0
            ),
        ];

        $grupos = $bloques
            ->sum(fn (array $bloque) => count($bloque['filas'] ?? []));

        $metricTable = $section->addTable([
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
            'borderSize' => 4,
            'borderColor' => 'DCE5EA',
            'cellMargin' => 60,
        ]);

        $metricTable->addRow();

        $metricas = [
            ['MATRÍCULA VIGENTE', $global['activos'], '006492', 'FFFFFF'],
            ['REGISTROS DEL CICLO', $global['total'], 'F8FAFC', '111827'],
            ['NO VIGENTES', $global['no_vigentes'], 'FFF7E6', '9A6700'],
            ['HOMBRES VIGENTES', $global['hombres'], 'F8FAFC', '111827'],
            ['MUJERES VIGENTES', $global['mujeres'], 'F8FAFC', '111827'],
            ['GRUPOS', $grupos, 'F4F8EC', '5F7D16'],
        ];

        foreach ($metricas as [$label, $value, $bg, $color]) {
            $cell = $metricTable->addCell(
                2100,
                ['bgColor' => $bg, 'valign' => 'center']
            );

            $cell->addText(
                $label,
                ['bold' => true, 'size' => 6.5, 'color' => $color],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 25]
            );

            $cell->addText(
                (string) $value,
                ['bold' => true, 'size' => 15, 'color' => $color],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }

        $section->addTextBreak(1);

        $esBachillerato = $nivel->slug === 'bachillerato';

        $phpWord->addTableStyle(
            'DistribucionCUM',
            [
                'borderSize' => 4,
                'borderColor' => 'D7E0E7',
                'cellMargin' => 45,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ],
            [
                'bgColor' => '101827',
            ]
        );

        foreach ($bloques as $bloque) {
            $band = $section->addTable([
                'width' => 100 * 50,
                'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
                'borderSize' => 0,
            ]);

            $band->addRow();

            $band->addCell(8000, ['bgColor' => '006492'])
                ->addText(
                    mb_strtoupper((string) ($bloque['ciclo'] ?? 'DISTRIBUCIÓN')),
                    ['bold' => true, 'color' => 'FFFFFF', 'size' => 8.5]
                );

            $band->addCell(4700, ['bgColor' => '006492'])
                ->addText(
                    'Vigentes '
                    . (int) ($bloque['totales']['activos'] ?? 0)
                    . '  |  No vigentes '
                    . (int) ($bloque['totales']['no_vigentes'] ?? 0)
                    . '  |  Total '
                    . (int) ($bloque['totales']['total_historico'] ?? 0),
                    ['bold' => true, 'color' => 'EAF8FC', 'size' => 6.8],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
                );

            $table = $section->addTable('DistribucionCUM');
            $table->addRow(null, ['tblHeader' => true]);

            $headers = ['Grado'];

            if ($esBachillerato) {
                $headers[] = 'Sem.';
            }

            $headers = array_merge(
                $headers,
                [
                    'Grupo',
                    'H',
                    'M',
                    'Vigentes',
                    'Inactivos',
                    'Bajas',
                    'Trasl.',
                    'Susp.',
                    'Egres.',
                    'Total ciclo',
                ]
            );

            foreach ($headers as $header) {
                $bg = $header === 'Total ciclo' ? '88AC2E' : '101827';

                $table->addCell(980, ['bgColor' => $bg, 'valign' => 'center'])
                    ->addText(
                        $header,
                        ['bold' => true, 'color' => 'FFFFFF', 'size' => 6.8],
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                    );
            }

            foreach ($bloque['filas'] as $index => $fila) {
                $table->addRow();

                $bg = $index % 2 === 0 ? 'FFFFFF' : 'F8FAFC';

                $values = [(string) ($fila['grado'] ?? '—')];

                if ($esBachillerato) {
                    $values[] = (string) ($fila['semestre'] ?? '—');
                }

                $values = array_merge(
                    $values,
                    [
                        (string) ($fila['grupo'] ?? '—'),
                        (string) ($fila['hombres_vigentes'] ?? 0),
                        (string) ($fila['mujeres_vigentes'] ?? 0),
                        (string) ($fila['activos'] ?? 0),
                        (string) ($fila['inactivos'] ?? 0),
                        (string) ($fila['bajas'] ?? 0),
                        (string) ($fila['traslados'] ?? 0),
                        (string) ($fila['suspendidos'] ?? 0),
                        (string) ($fila['egresados'] ?? 0),
                        (string) ($fila['total_historico'] ?? 0),
                    ]
                );

                foreach ($values as $column => $value) {
                    $isTotal = $column === count($values) - 1;

                    $table->addCell(
                        980,
                        [
                            'bgColor' => $isTotal ? 'EDF7DF' : $bg,
                            'valign' => 'center',
                        ]
                    )->addText(
                        $value,
                        [
                            'bold' => $isTotal || $column === 0,
                            'size' => 7.5,
                            'color' => $isTotal ? '42650A' : '172033',
                        ],
                        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                    );
                }
            }

            $tot = $bloque['totales'];

            $table->addRow();

            $totalValues = ['TOTALES'];

            if ($esBachillerato) {
                $totalValues[] = '—';
            }

            $totalValues = array_merge(
                $totalValues,
                [
                    count($bloque['filas']) . ' grupos',
                    (string) ($tot['hombres_vigentes'] ?? 0),
                    (string) ($tot['mujeres_vigentes'] ?? 0),
                    (string) ($tot['activos'] ?? 0),
                    (string) ($tot['inactivos'] ?? 0),
                    (string) ($tot['bajas'] ?? 0),
                    (string) ($tot['traslados'] ?? 0),
                    (string) ($tot['suspendidos'] ?? 0),
                    (string) ($tot['egresados'] ?? 0),
                    (string) ($tot['total_historico'] ?? 0),
                ]
            );

            foreach ($totalValues as $column => $value) {
                $isTotal = $column === count($totalValues) - 1;

                $table->addCell(
                    980,
                    ['bgColor' => $isTotal ? '88AC2E' : 'E9EEF2']
                )->addText(
                    $value,
                    [
                        'bold' => true,
                        'size' => 7.2,
                        'color' => $isTotal ? 'FFFFFF' : '172033',
                    ],
                    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
                );
            }

            $section->addTextBreak(1);
        }

        $section->addText(
            'Criterio de lectura: H + M corresponde a matrícula vigente. '
            . '“Registros del ciclo” conserva todos los historiales no anulados; '
            . 'por ello puede ser mayor que la matrícula vigente aun cuando no existan bajas.',
            ['size' => 7.3, 'color' => '475569'],
            [
                'spaceBefore' => 40,
                'spaceAfter' => 40,
                'borderLeftSize' => 8,
                'borderLeftColor' => '88AC2E',
                'indentation' => ['left' => 120],
            ]
        );

        $footer = $section->addFooter();
        $footer->addText(
            'CENTRO UNIVERSITARIO MOCTEZUMA · DISTRIBUCIÓN ESCOLAR INSTITUCIONAL',
            ['bold' => true, 'size' => 6.5, 'color' => '64748B'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
    }

    private function nombreBase(Nivel $nivel, array $filtros): string
    {
        $alcance = filled($filtros['generacion_id'] ?? null)
            ? 'generacion_' . $filtros['generacion_id']
            : 'todas_las_generaciones';

        return Str::slug(
            'distribucion_escolar_' . $nivel->slug . '_' . $alcance . '_' . now()->format('Ymd_His'),
            '_'
        );
    }

    private function imagenBase64(string $ruta): ?string
    {
        if (!is_file($ruta)) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta));
    }
}
