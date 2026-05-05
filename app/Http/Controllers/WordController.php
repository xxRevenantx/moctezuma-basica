<?php

namespace App\Http\Controllers;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;

class WordController extends Controller
{
    public function lista_word(Request $request, string $slug_nivel)
    {
        $generacion_id = $request->integer('generacion_id');
        $grado_id = $request->integer('grado_id');
        $grupo_id = $request->integer('grupo_id');

        $tipo_descarga = $request->input('tipo_descarga', 'evaluacion');
        $opcion_descarga = $request->input('opcion_descarga', 'primer_periodo');

        /*
        |--------------------------------------------------------------------------
        | Nivel
        |--------------------------------------------------------------------------
        | Se obtiene el nivel por slug, por ejemplo: preescolar.
        */
        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Validación de seguridad
        |--------------------------------------------------------------------------
        | Solo se permite generar Word para evaluación de preescolar.
        */
        if ((int) $nivel->id !== 1 || $tipo_descarga !== 'evaluacion') {
            abort(404, 'Este documento solo está disponible en Word para evaluación de preescolar.');
        }

        /*
        |--------------------------------------------------------------------------
        | Generación
        |--------------------------------------------------------------------------
        */
        $generacion = Generacion::query()
            ->where('id', $generacion_id)
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Grado
        |--------------------------------------------------------------------------
        */
        $grado = Grado::query()
            ->where('id', $grado_id)
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Grupo
        |--------------------------------------------------------------------------
        | En preescolar no se usa semestre.
        */
        $grupo = Grupo::query()
            ->where('id', $grupo_id)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->whereNull('semestre_id')
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Alumnos inscritos
        |--------------------------------------------------------------------------
        | La tabla inscripciones ya contiene los datos del alumno.
        */
        $alumnos = Inscripcion::query()
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('grupo_id', $grupo->id)
            ->whereNull('semestre_id')
            ->where('activo', 1)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get([
                'id',
                'matricula',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'grupo_id',
                'semestre_id',
                'activo',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Carpeta temporal para PHPWord
        |--------------------------------------------------------------------------
        | Evita que PHPWord use C:\Windows\Temp.
        */
        $carpetaPhpWordTemp = storage_path('app/phpword-temp');

        if (!is_dir($carpetaPhpWordTemp)) {
            mkdir($carpetaPhpWordTemp, 0755, true);
        }

        Settings::setTempDir($carpetaPhpWordTemp);

        /*
        |--------------------------------------------------------------------------
        | Documento Word
        |--------------------------------------------------------------------------
        */
        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(7);

        /*
        |--------------------------------------------------------------------------
        | Estilos generales
        |--------------------------------------------------------------------------
        */
        $phpWord->addTableStyle('tablaPrincipal', [
            'borderSize' => 8,
            'borderColor' => '000000',
            'cellMargin' => 35,
            'alignment' => 'center',
        ]);

        $fuenteNormal = [
            'name' => 'Arial',
            'size' => 6.5,
            'color' => '000000',
        ];

        $fuenteNegrita = [
            'name' => 'Arial',
            'size' => 6.5,
            'bold' => true,
            'color' => '000000',
        ];

        $fuenteTitulo = [
            'name' => 'Arial',
            'size' => 7,
            'bold' => true,
            'color' => '000000',
        ];

        $parrafoCentro = [
            'alignment' => 'center',
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ];

        $parrafoIzquierda = [
            'alignment' => 'left',
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Textos de evaluación
        |--------------------------------------------------------------------------
        */
        $camposFormativos = [
            [
                'campo' => 'Lenguajes',
                'color' => 'F7C6F2',
                'texto' => 'Desarrolla habilidades de descripción y expresión oral a través de la interpretación de imágenes y narraciones sencillas, plasmándolas de manera gráfica o escrita. Fomenta la curiosidad y el cuestionamiento para mejorar la claridad y coherencia en sus conversaciones.',
            ],
            [
                'campo' => 'Saberes y pensamiento científico',
                'color' => 'CFE8FF',
                'texto' => 'Dice los números en orden, cuenta elementos de diversos conjuntos, menciona a simple vista donde hay más o menor cantidad. Identifica el nombre de diferentes figuras geométricas y las manipula para crear formas producto de su imaginación.',
            ],
            [
                'campo' => 'Ética, naturaleza y sociedades',
                'color' => 'C7F2A4',
                'texto' => 'Expresa algunas costumbres y tradiciones que comparte con su familia y encuentra similitudes con las de sus compañeros. Logra reconocer algunos trabajos que realizan las personas de su comunidad para apoyar y beneficiar a todos.',
            ],
            [
                'campo' => 'De lo humano y lo comunitario',
                'color' => 'FFE999',
                'texto' => 'Participa en las actividades de movimiento mostrando mayor disposición a relacionarse con los demás para ejercitarse. Le gusta correr, saltar, girar, lanzar, cachar y quedarse sin movimiento por periodos de tiempos cortos en juegos y rondas.',
            ],
        ];

        $recomendacion = 'Escribir su nombre completo con diversos propósitos, por ejemplo, para marcar sus pertenencias, identificar el nombre de sus familiares y mostrar las diferencias y semejanzas que tiene con el suyo.';

        /*
        |--------------------------------------------------------------------------
        | Rutas de imágenes
        |--------------------------------------------------------------------------
        | Coloca las imágenes en public/img o cambia las rutas.
        */
        $rutaLogo = public_path('img/logo-moctezuma.png');
        $rutaMoti = public_path('img/moti.png');
        $rutaMarcaAgua = public_path('img/marca-agua-moctezuma.png');

        /*
        |--------------------------------------------------------------------------
        | Si no hay alumnos, se genera una hoja vacía.
        |--------------------------------------------------------------------------
        */
        if ($alumnos->isEmpty()) {
            $alumnos = collect([
                (object) [
                    'matricula' => '',
                    'nombre' => '',
                    'apellido_paterno' => '',
                    'apellido_materno' => '',
                ],
            ]);
        }

        foreach ($alumnos as $alumno) {
            /*
            |--------------------------------------------------------------------------
            | Sección por alumno
            |--------------------------------------------------------------------------
            | Cada alumno tendrá una hoja.
            */
            $section = $phpWord->addSection([
                'paperSize' => 'Letter',
                'orientation' => 'portrait',
                'marginTop' => 350,
                'marginBottom' => 350,
                'marginLeft' => 850,
                'marginRight' => 850,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Marca de agua
            |--------------------------------------------------------------------------
            */
            if (file_exists($rutaMarcaAgua)) {
                $header = $section->addHeader();

                $header->addWatermark($rutaMarcaAgua, [
                    'width' => 500,
                    'height' => 500,
                    'marginTop' => 170,
                    'marginLeft' => 25,
                    'wrappingStyle' => 'behind',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Encabezado con logo y personaje
            |--------------------------------------------------------------------------
            */
            $tablaEncabezado = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'alignment' => 'center',
            ]);

            $tablaEncabezado->addRow();

            $celdaLogo = $tablaEncabezado->addCell(7600, [
                'borderSize' => 0,
                'valign' => 'center',
            ]);

            if (file_exists($rutaLogo)) {
                $celdaLogo->addImage($rutaLogo, [
                    'width' => 250,
                    'height' => 65,
                    'alignment' => 'left',
                ]);
            } else {
                $celdaLogo->addText('Centro Universitario Moctezuma', [
                    'bold' => true,
                    'size' => 16,
                    'color' => '006492',
                ], [
                    'alignment' => 'left',
                ]);
            }

            $celdaMoti = $tablaEncabezado->addCell(1900, [
                'borderSize' => 0,
                'valign' => 'center',
            ]);

            if (file_exists($rutaMoti)) {
                $celdaMoti->addImage($rutaMoti, [
                    'width' => 55,
                    'height' => 70,
                    'alignment' => 'center',
                ]);
            }

            $section->addTextBreak(1);

            /*
            |--------------------------------------------------------------------------
            | Nombre completo del alumno
            |--------------------------------------------------------------------------
            */
            $nombreCompleto = trim(
                ($alumno->nombre ?? '') . ' ' .
                ($alumno->apellido_paterno ?? '') . ' ' .
                ($alumno->apellido_materno ?? '')
            );

            /*
            |--------------------------------------------------------------------------
            | Tabla de datos generales
            |--------------------------------------------------------------------------
            */
            $tablaDatos = $section->addTable('tablaPrincipal');

            $tablaDatos->addRow(260);

            $tablaDatos->addCell(9500, [
                'gridSpan' => 4,
                'bgColor' => 'F7C6F2',
                'valign' => 'center',
            ])->addText(
                    'Nombre de la escuela:  Centro Universitario Moctezuma',
                    $fuenteNegrita,
                    $parrafoIzquierda
                );

            $tablaDatos->addRow(260);

            $tablaDatos->addCell(4700, [
                'gridSpan' => 2,
                'bgColor' => 'FFF2CC',
                'valign' => 'center',
            ])->addText(
                    'Nombre del alumno (a): ' . $nombreCompleto,
                    $fuenteNegrita,
                    $parrafoIzquierda
                );

            $tablaDatos->addCell(4800, [
                'gridSpan' => 2,
                'bgColor' => 'D9F600',
                'valign' => 'center',
            ])->addText(
                    'Grado y grupo:  ' . $grado->nombre . ' "' . $grupo->nombre . '"',
                    $fuenteNegrita,
                    $parrafoIzquierda
                );

            $tablaDatos->addRow(260);

            $tablaDatos->addCell(9500, [
                'gridSpan' => 4,
                'bgColor' => '92D050',
                'valign' => 'center',
            ])->addText(
                    'Educadora: ' . $request->input('educadora', 'María Guadalupe Millán Hilario'),
                    $fuenteNegrita,
                    $parrafoIzquierda
                );

            /*
            |--------------------------------------------------------------------------
            | Tabla de evaluación
            |--------------------------------------------------------------------------
            */
            $tablaEvaluacion = $section->addTable('tablaPrincipal');

            $tablaEvaluacion->addRow(320);

            $tablaEvaluacion->addCell(1500, [
                'bgColor' => 'F7C6F2',
                'valign' => 'center',
            ])->addText(
                    'CAMPO FORMATIVO',
                    $fuenteTitulo,
                    $parrafoCentro
                );

            $tablaEvaluacion->addCell(8000, [
                'bgColor' => 'F7C6F2',
                'valign' => 'center',
            ])->addText(
                    mb_strtoupper($this->formatearPeriodoEvaluacion($opcion_descarga)),
                    $fuenteTitulo,
                    $parrafoCentro
                );

            foreach ($camposFormativos as $campo) {
                $tablaEvaluacion->addRow(900);

                $tablaEvaluacion->addCell(1500, [
                    'bgColor' => $campo['color'],
                    'valign' => 'center',
                ])->addText(
                        $campo['campo'],
                        $fuenteNegrita,
                        $parrafoCentro
                    );

                $tablaEvaluacion->addCell(8000, [
                    'valign' => 'center',
                ])->addText(
                        $campo['texto'],
                        $fuenteNormal,
                        $parrafoIzquierda
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Recomendaciones
            |--------------------------------------------------------------------------
            */
            $tablaEvaluacion->addRow(230);

            $tablaEvaluacion->addCell(9500, [
                'gridSpan' => 2,
                'bgColor' => 'F7C6F2',
                'valign' => 'center',
            ])->addText(
                    'RECOMENDACIONES',
                    $fuenteTitulo,
                    $parrafoCentro
                );

            $tablaEvaluacion->addRow(400);

            $tablaEvaluacion->addCell(9500, [
                'gridSpan' => 2,
                'valign' => 'center',
            ])->addText(
                    $recomendacion,
                    $fuenteNormal,
                    $parrafoIzquierda
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Nombre del archivo
        |--------------------------------------------------------------------------
        */
        $nombreArchivo = 'evaluacion-diagnostica-preescolar-'
            . $this->limpiarNombreArchivo($grado->nombre)
            . '-grupo-'
            . $this->limpiarNombreArchivo($grupo->nombre)
            . '.docx';

        /*
        |--------------------------------------------------------------------------
        | Carpeta donde se guarda temporalmente el Word final
        |--------------------------------------------------------------------------
        */
        $carpetaTemporal = storage_path('app/temp');

        if (!is_dir($carpetaTemporal)) {
            mkdir($carpetaTemporal, 0755, true);
        }

        $rutaTemporal = $carpetaTemporal . DIRECTORY_SEPARATOR . $nombreArchivo;

        /*
        |--------------------------------------------------------------------------
        | Guardado del documento
        |--------------------------------------------------------------------------
        */
        $writer = IOFactory::createWriter($phpWord, 'Word2007');

        $writer->save($rutaTemporal);

        return response()
            ->download($rutaTemporal, $nombreArchivo)
            ->deleteFileAfterSend(true);
    }

    private function formatearPeriodoEvaluacion(?string $periodo): string
    {
        return match ($periodo) {
            'primer_periodo' => 'PRIMERA EVALUACIÓN DIAGNÓSTICA',
            'segundo_periodo' => 'SEGUNDA EVALUACIÓN DIAGNÓSTICA',
            'tercer_periodo' => 'TERCERA EVALUACIÓN DIAGNÓSTICA',
            default => mb_strtoupper(str_replace('_', ' ', $periodo ?? 'EVALUACIÓN DIAGNÓSTICA')),
        };
    }

    private function limpiarNombreArchivo(string $texto): string
    {
        $texto = mb_strtolower($texto);

        $texto = preg_replace('/[^a-z0-9áéíóúñü]+/iu', '-', $texto);

        $texto = trim($texto, '-');

        return $texto ?: 'documento';
    }
}
