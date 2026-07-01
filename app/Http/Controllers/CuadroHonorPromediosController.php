<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use App\Services\CuadroHonorPromediosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CuadroHonorPromediosController extends Controller
{
    public function __construct(
        private readonly CuadroHonorPromediosService $service,
    ) {
    }

    public function __invoke(Request $request, string $slug_nivel, string $formato)
    {
        abort_unless(in_array($formato, ['pdf', 'word'], true), 404);

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'tipo_reconocimiento' => ['nullable', 'in:periodo,anual'],
            'periodo' => ['nullable', 'integer', 'in:0,1,2,3'],
            'fecha' => ['nullable', 'date'],
        ]);

        $datos = $this->service->generar(
            nivel: $nivel,
            cicloEscolarId: $request->integer('ciclo_escolar_id'),
            gradoId: $request->integer('grado_id'),
            generacionId: $request->filled('generacion_id') ? $request->integer('generacion_id') : null,
            grupoId: $request->filled('grupo_id') ? $request->integer('grupo_id') : null,
            semestreId: $request->filled('semestre_id') ? $request->integer('semestre_id') : null,
            tipoReconocimiento: (string) $request->input('tipo_reconocimiento', 'anual'),
            periodo: $request->integer('periodo', 0),
            fecha: $request->input('fecha'),
        );

        return $formato === 'pdf'
            ? $this->pdf($datos)
            : $this->word($datos);
    }

    private function pdf(array $datos)
    {
        $nombre = $this->nombreArchivo($datos, 'pdf');

        $pdf = Pdf::loadView('pdf.cuadro-honor-promedios', $datos)
            ->setPaper('letter', 'portrait');

        return $pdf->stream($nombre);
    }

    private function word(array $datos): BinaryFileResponse
    {
        $directorioSalida = storage_path('app/temp');
        $directorioTemporalPhpWord = storage_path('app/temp/phpword');

        File::ensureDirectoryExists($directorioSalida, 0775, true);
        File::ensureDirectoryExists($directorioTemporalPhpWord, 0775, true);

        if (!is_writable($directorioSalida) || !is_writable($directorioTemporalPhpWord)) {
            abort(500, 'Los directorios temporales de Word no tienen permisos de escritura.');
        }

        /*
         * Evita que PHPWord use C:\Windows\Temp. En algunos entornos locales
         * ese directorio permite crear la carpeta, pero bloquea su lectura o
         * eliminación durante el cierre del documento.
         */
        putenv('TMP=' . $directorioTemporalPhpWord);
        putenv('TEMP=' . $directorioTemporalPhpWord);
        putenv('TMPDIR=' . $directorioTemporalPhpWord);

        $settingsClass = 'PhpOffice\\PhpWord\\Settings';

        if (class_exists($settingsClass) && method_exists($settingsClass, 'setTempDir')) {
            $settingsClass::setTempDir($directorioTemporalPhpWord);
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $propiedades = $phpWord->getDocInfo();
        $propiedades->setCreator('Centro Universitario Moctezuma');
        $propiedades->setCompany('Centro Universitario Moctezuma');
        $propiedades->setTitle($datos['titulo']);
        $propiedades->setSubject('Relación institucional de promedios y lugares por grupo');
        $propiedades->setDescription('Documento académico editable generado por el sistema escolar.');

        $phpWord->addTableStyle('EncabezadoInstitucional', [
            'borderSize' => 0,
            'cellMargin' => 20,
            'alignment' => 'center',
        ]);

        $phpWord->addTableStyle('DatosDocumento', [
            'borderSize' => 4,
            'borderColor' => 'C7D2E0',
            'cellMargin' => 65,
            'alignment' => 'center',
        ]);

        $phpWord->addTableStyle('TablaPromedios', [
            'borderSize' => 5,
            'borderColor' => '64748B',
            'cellMargin' => 55,
            'alignment' => 'center',
        ]);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'pageSizeW' => 12240,
            'pageSizeH' => 15840,
            'marginTop' => 850,
            'marginBottom' => 760,
            'marginLeft' => 650,
            'marginRight' => 650,
            'headerHeight' => 280,
            'footerHeight' => 250,
        ]);

        $this->agregarEncabezadoWord($section, $datos);

        $section->addText(
            $datos['titulo'],
            [
                'name' => 'Arial',
                'size' => 14,
                'bold' => true,
                'color' => '0F2747',
            ],
            [
                'alignment' => 'center',
                'spaceBefore' => 80,
                'spaceAfter' => 120,
            ]
        );

        $this->agregarDatosGeneralesWord($section, $datos);

        foreach ($datos['grupos'] as $indice => $grupo) {
            if ($indice > 0) {
                $section->addTextBreak(1);
            }

            $section->addText(
                mb_strtoupper($grupo['titulo']),
                [
                    'name' => 'Arial',
                    'size' => 10,
                    'bold' => true,
                    'color' => '006492',
                ],
                [
                    'spaceBefore' => 120,
                    'spaceAfter' => 55,
                    'keepNext' => true,
                ]
            );

            $section->addText(
                'Total de alumnos: ' . $grupo['total']
                    . ($datos['es_preescolar'] ? '' : ' · Promedio del grupo: ' . $grupo['promedio_grupo']),
                [
                    'name' => 'Arial',
                    'size' => 8,
                    'italic' => true,
                    'color' => '475569',
                ],
                [
                    'spaceAfter' => 65,
                    'keepNext' => true,
                ]
            );

            $tabla = $section->addTable('TablaPromedios');
            $tabla->addRow(360, ['tblHeader' => true]);

            $encabezados = [
                ['N.º', 500],
                ['LUGAR', 1000],
                ['ALUMNO', 3900],
                ['MATRÍCULA', 1900],
                ['GRUPO', 1050],
                ['PROMEDIO FINAL', 1500],
            ];

            foreach ($encabezados as [$texto, $ancho]) {
                $celda = $tabla->addCell($ancho, [
                    'bgColor' => '006492',
                    'valign' => 'center',
                ]);

                $celda->addText(
                    $texto,
                    [
                        'name' => 'Arial',
                        'size' => 7.5,
                        'bold' => true,
                        'color' => 'FFFFFF',
                    ],
                    [
                        'alignment' => 'center',
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                    ]
                );
            }

            foreach ($grupo['filas'] as $numero => $fila) {
                $tabla->addRow(330);
                $fondo = $numero % 2 === 0 ? 'F8FAFC' : 'FFFFFF';

                $valores = [
                    (string) ($numero + 1),
                    $this->textoLugar($fila),
                    mb_strtoupper((string) ($fila['alumno'] ?? '')),
                    (string) ($fila['matricula'] ?? ''),
                    (string) ($fila['grupo'] ?? ''),
                    $this->textoPromedio($fila, $datos['es_preescolar']),
                ];

                $alineaciones = ['center', 'center', 'left', 'center', 'center', 'center'];
                $anchos = [500, 1000, 3900, 1900, 1050, 1500];

                foreach ($valores as $posicion => $valor) {
                    $celda = $tabla->addCell($anchos[$posicion], [
                        'bgColor' => $fondo,
                        'valign' => 'center',
                    ]);

                    $celda->addText(
                        $valor,
                        [
                            'name' => 'Arial',
                            'size' => 7.5,
                            'bold' => $posicion === 1 && is_numeric($fila['lugar'] ?? null),
                            'color' => $posicion === 1 && is_numeric($fila['lugar'] ?? null)
                                ? '4338CA'
                                : '111827',
                        ],
                        [
                            'alignment' => $alineaciones[$posicion],
                            'spaceAfter' => 0,
                            'spaceBefore' => 0,
                        ]
                    );
                }
            }

            $this->agregarFirmasWord($section, $grupo, $datos);
        }

        $footer = $section->addFooter();
        $footer->addText(
            'Centro Universitario Moctezuma · Documento académico generado por el sistema',
            [
                'name' => 'Arial',
                'size' => 7,
                'color' => '64748B',
            ],
            [
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]
        );

        $nombre = $this->nombreArchivo($datos, 'docx');
        $ruta = $directorioSalida . DIRECTORY_SEPARATOR . Str::uuid() . '-' . $nombre;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');

        if (method_exists($writer, 'setTempDir')) {
            $writer->setTempDir($directorioTemporalPhpWord);
        }

        try {
            $writer->save($ruta);
        } catch (\Throwable $exception) {
            if (is_file($ruta)) {
                @unlink($ruta);
            }

            throw $exception;
        }

        return response()
            ->download($ruta, $nombre)
            ->deleteFileAfterSend(true);
    }

    private function agregarEncabezadoWord($section, array $datos): void
    {
        $header = $section->addHeader();
        $tabla = $header->addTable('EncabezadoInstitucional');
        $tabla->addRow(850);

        $izquierda = $tabla->addCell(2100, ['valign' => 'center']);
        $centro = $tabla->addCell(6100, ['valign' => 'center']);
        $derecha = $tabla->addCell(2100, ['valign' => 'center']);

        $logoInstitucion = public_path('imagenes/logo-letra.png');
        $logoEducacion = public_path('imagenes/logo-edu.png');

        if (is_file($logoEducacion)) {
            $izquierda->addImage($logoEducacion, [
                'width' => 115,
                'height' => 42,
                'alignment' => 'left',
            ]);
        }

        $centro->addText(
            $datos['nombre_escuela'],
            [
                'name' => 'Arial',
                'size' => 9,
                'bold' => true,
                'color' => '0F2747',
            ],
            [
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]
        );

        $centro->addText(
            'C.C.T. ' . ($datos['nivel']->cct ?: 'SIN C.C.T.'),
            [
                'name' => 'Arial',
                'size' => 8,
                'bold' => true,
                'color' => '006492',
            ],
            [
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]
        );

        $centro->addText(
            $datos['direccion'],
            [
                'name' => 'Arial',
                'size' => 6.5,
                'color' => '475569',
            ],
            [
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]
        );

        if (is_file($logoInstitucion)) {
            $derecha->addImage($logoInstitucion, [
                'width' => 105,
                'height' => 46,
                'alignment' => 'right',
            ]);
        }

        $header->addText(
            '________________________________________________________________________________',
            [
                'name' => 'Arial',
                'size' => 6,
                'color' => '88AC2E',
            ],
            [
                'alignment' => 'center',
                'spaceAfter' => 0,
                'spaceBefore' => 0,
            ]
        );
    }

    private function agregarDatosGeneralesWord($section, array $datos): void
    {
        $tabla = $section->addTable('DatosDocumento');

        $generacion = $datos['generacion']
            ? $datos['generacion']->anio_ingreso . '-' . $datos['generacion']->anio_egreso
            : 'Todas';

        $grupo = $datos['grupo_seleccionado']?->asignacionGrupo?->nombre ?? 'Todos los grupos';
        $semestre = $datos['es_bachillerato'] ? (string) ($datos['semestre']?->numero ?? '—') : 'No aplica';

        $filas = [
            [
                ['Ciclo escolar', $datos['ciclo']->inicio_anio . '-' . $datos['ciclo']->fin_anio, 2350],
                ['Nivel', $datos['nivel']->nombre, 2350],
                ['Grado', $datos['grado']->nombre, 2350],
                ['Grupo', $grupo, 2350],
            ],
            [
                ['Generación', $generacion, 2350],
                ['Semestre', $semestre, 2350],
                ['Fecha', $datos['fecha'], 4700],
            ],
        ];

        foreach ($filas as $campos) {
            $tabla->addRow(390);

            foreach ($campos as [$etiqueta, $valor, $ancho]) {
                $celda = $tabla->addCell($ancho, [
                    'bgColor' => 'F8FAFC',
                    'valign' => 'center',
                ]);

                $texto = $celda->addTextRun([
                    'alignment' => 'center',
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                ]);
                $texto->addText($etiqueta . ': ', [
                    'name' => 'Arial',
                    'size' => 7,
                    'bold' => true,
                    'color' => '334155',
                ]);
                $texto->addText((string) $valor, [
                    'name' => 'Arial',
                    'size' => 7,
                    'color' => '0F172A',
                ]);
            }
        }
    }

    private function agregarFirmasWord($section, array $grupo, array $datos): void
    {
        $section->addTextBreak(2);

        $tabla = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 35,
            'alignment' => 'center',
        ]);
        $tabla->addRow(850);

        $docente = $tabla->addCell(4800, ['valign' => 'bottom']);
        $director = $tabla->addCell(4800, ['valign' => 'bottom']);

        foreach ([
            [$docente, $grupo['docente'], $datos['es_preescolar'] ? 'EDUCADORA TITULAR' : 'DOCENTE TITULAR'],
            [$director, $datos['director'], $datos['cargo_director']],
        ] as [$celda, $nombre, $cargo]) {
            $celda->addText(
                '________________________________',
                ['size' => 8, 'color' => '475569'],
                ['alignment' => 'center', 'spaceAfter' => 0]
            );
            $celda->addText(
                $nombre,
                ['size' => 7.5, 'bold' => true, 'color' => '0F172A'],
                ['alignment' => 'center', 'spaceAfter' => 0]
            );
            $celda->addText(
                $cargo,
                ['size' => 7, 'color' => '475569'],
                ['alignment' => 'center', 'spaceAfter' => 0]
            );
        }
    }

    private function textoLugar(array $fila): string
    {
        if (is_numeric($fila['lugar'] ?? null)) {
            return (int) $fila['lugar'] . '° lugar';
        }

        return ($fila['completo'] ?? false) ? 'Sin lugar' : 'Pendiente';
    }

    private function textoPromedio(array $fila, bool $esPreescolar): string
    {
        if ($esPreescolar) {
            return 'No aplica';
        }

        if (! ($fila['completo'] ?? false)) {
            return 'Pendiente';
        }

        return is_numeric($fila['promedio'] ?? null)
            ? number_format((float) $fila['promedio'], 1, '.', '')
            : '—';
    }

    private function nombreArchivo(array $datos, string $extension): string
    {
        $segmentos = [
            'CUADRO_HONOR',
            $datos['nivel']->nombre,
            $datos['grado']->nombre,
        ];

        if ($datos['es_bachillerato'] && $datos['semestre']) {
            $segmentos[] = 'SEMESTRE_' . $datos['semestre']->numero;
        }

        if ($datos['grupo_seleccionado']) {
            $segmentos[] = 'GRUPO_' . ($datos['grupo_seleccionado']->asignacionGrupo?->nombre ?? 'SIN_GRUPO');
        }

        $segmentos[] = $datos['fecha_archivo'];

        $nombre = collect($segmentos)
            ->map(fn ($segmento) => Str::upper(Str::slug((string) $segmento, '_')))
            ->filter()
            ->implode('_');

        return $nombre . '.' . $extension;
    }
}
