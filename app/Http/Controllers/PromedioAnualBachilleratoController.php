<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Escuela;
use App\Models\Generacion;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\PromedioAnualBachilleratoService;
use App\Support\PromedioExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PromedioAnualBachilleratoController extends Controller
{
    public function __construct(
        private readonly PromedioAnualBachilleratoService $service,
    ) {
    }

    public function reconocimiento(Request $request, int $inscripcion)
    {
        $datos = $this->validarSolicitud($request);
        $contexto = $this->contextoDocumento($datos);
        $fila = collect($contexto['reporte']['alumnos'])->firstWhere('inscripcion_id', $inscripcion);

        abort_unless($fila, 404, 'No se encontró al alumno en el promedio anual seleccionado.');
        abort_unless(
            ($fila['reconocimiento_disponible'] ?? false) === true,
            422,
            'El reconocimiento anual solo está disponible para los tres primeros lugares con ambos semestres completos y todas las materias acreditadas.'
        );

        $data = $this->datosReconocimiento($contexto, $fila, $datos['fecha'] ?? null);
        $nombre = 'RECONOCIMIENTO_ANUAL_' . $this->nombreSeguro($fila['alumno'] ?? 'ALUMNO') . '.pdf';

        return Pdf::loadView('pdf.reconocimiento_promedio_pdf', $data)
            ->setPaper('letter', 'landscape')
            ->stream($nombre);
    }

    public function reconocimientosZip(Request $request): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        $datos = $this->validarSolicitud($request);
        $contexto = $this->contextoDocumento($datos);
        $filas = collect($contexto['reporte']['alumnos'])
            ->where('reconocimiento_disponible', true)
            ->values();

        abort_if(
            $filas->isEmpty(),
            404,
            'No hay alumnos con reconocimiento anual disponible para la generación y ciclo seleccionados.'
        );

        $directorio = storage_path('app/temp');
        File::ensureDirectoryExists($directorio, 0775, true);

        $zipPath = $directorio . DIRECTORY_SEPARATOR . 'reconocimientos_anuales_' . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No se pudo crear el archivo ZIP.');

        foreach ($filas as $fila) {
            $contenido = Pdf::loadView(
                'pdf.reconocimiento_promedio_pdf',
                $this->datosReconocimiento($contexto, $fila, $datos['fecha'] ?? null)
            )
                ->setPaper('letter', 'landscape')
                ->output();

            $nombre = sprintf(
                '%s_LUGAR_%s_%s.pdf',
                str_pad((string) ($fila['lugar'] ?? 0), 2, '0', STR_PAD_LEFT),
                (string) ($fila['lugar'] ?? 0),
                $this->nombreSeguro($fila['alumno'] ?? 'ALUMNO')
            );

            $zip->addFromString($nombre, $contenido);
        }

        $zip->close();

        $generacion = $contexto['generacion'];
        $nombreZip = 'RECONOCIMIENTOS_ANUALES_BACHILLERATO_GENERACION_'
            . $generacion->anio_ingreso . '-' . $generacion->anio_egreso
            . '_CICLO_' . $contexto['ciclo']->inicio_anio . '-' . $contexto['ciclo']->fin_anio
            . '.zip';

        return response()
            ->download($zipPath, $nombreZip)
            ->deleteFileAfterSend(true);
    }

    public function lista(Request $request, string $formato)
    {
        abort_unless(in_array($formato, ['pdf', 'word'], true), 404);

        $datos = $this->validarSolicitud($request);
        $contexto = $this->contextoDocumento($datos);

        abort_if(
            collect($contexto['reporte']['alumnos'])->isEmpty(),
            404,
            'No hay alumnos con información anual para la generación y ciclo seleccionados.'
        );

        return $formato === 'pdf'
            ? $this->listaPdf($contexto, $datos['fecha'] ?? null)
            : $this->listaWord($contexto, $datos['fecha'] ?? null);
    }

    private function validarSolicitud(Request $request): array
    {
        return $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'integer', 'exists:generaciones,id'],
            'fecha' => ['nullable', 'date'],
        ]);
    }

    private function contextoDocumento(array $datos): array
    {
        $nivel = Nivel::query()
            ->where(function ($query): void {
                $query->where('slug', 'bachillerato')->orWhere('id', 4);
            })
            ->with(['director', 'supervisor'])
            ->firstOrFail();

        $generacion = Generacion::query()
            ->whereKey((int) $datos['generacion_id'])
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        $ciclo = CicloEscolar::query()->findOrFail((int) $datos['ciclo_escolar_id']);

        $reporte = $this->service->reporteAnual(
            nivelId: (int) $nivel->id,
            cicloEscolarId: (int) $ciclo->id,
            generacionId: (int) $generacion->id,
        );

        abort_unless(
            (bool) data_get($reporte, 'contexto.valido', false),
            422,
            implode(' ', data_get($reporte, 'contexto.errores', ['No fue posible determinar los semestres del ciclo.']))
        );

        return [
            'nivel' => $nivel,
            'generacion' => $generacion,
            'ciclo' => $ciclo,
            'reporte' => $reporte,
            'escuela' => Escuela::query()->first(),
        ];
    }

    private function datosReconocimiento(array $contexto, array $fila, ?string $fecha): array
    {
        $nivel = $contexto['nivel'];
        $reporte = $contexto['reporte'];
        $numerosSemestre = data_get($reporte, 'contexto.numeros_semestre', []);
        $detalle = collect($fila['semestres_detalle'] ?? [])->filter();
        $filaReferencia = $detalle->last() ?: $detalle->first() ?: [];

        $inscripcion = Inscripcion::withTrashed()->findOrFail((int) $fila['inscripcion_id']);
        $grupo = ! empty($filaReferencia['grupo_id'])
            ? \App\Models\Grupo::query()->with('asignacionGrupo:id,nombre')->find($filaReferencia['grupo_id'])
            : null;
        $grado = ! empty($filaReferencia['grado_id'])
            ? \App\Models\Grado::query()->find($filaReferencia['grado_id'])
            : null;

        return [
            'titulo' => 'RECONOCIMIENTO ANUAL',
            'tipo' => 'reconocimiento',
            'escuela' => $contexto['escuela'],
            'nivel' => $nivel,
            'grado' => $grado,
            'grupo' => $grupo,
            'semestre' => null,
            'generacion' => $contexto['generacion'],
            'cicloEscolar' => $contexto['ciclo'],
            'cicloEscolarTexto' => $contexto['ciclo']->inicio_anio . ' - ' . $contexto['ciclo']->fin_anio,
            'inscripcion' => $inscripcion,
            'nombreAlumno' => $fila['alumno'],
            'alumno' => [
                'inscripcion_id' => $fila['inscripcion_id'],
                'matricula' => $fila['matricula'],
                'nombre_completo' => $fila['alumno'],
                'promedio_final' => $fila['promedio_final'],
                'lugar' => $fila['lugar'],
                'texto_lugar' => $fila['texto_lugar'],
            ],
            'promedio' => PromedioExcel::formatear($fila['promedio_final'], 1, '—'),
            'promedioNumero' => $fila['promedio_final'],
            'lugarAlumno' => $fila['lugar'],
            'textoLugarAlumno' => $fila['texto_lugar'],
            'esBachillerato' => true,
            'esSecundaria' => false,
            'mostrarSoloDirector' => true,
            'mostrarDocenteTitular' => false,
            'director' => $nivel,
            'docente' => null,
            'fechaPdf' => $this->fechaDocumento($fecha),
            'logo_izquierdo' => $this->imagenBase64(public_path('imagenes/logo-letra.png')),
            'logo_derecho' => $this->imagenBase64(public_path('imagenes/seg.png')),
            'reconocimientoAnualBachillerato' => true,
            'semestresAnuales' => $numerosSemestre,
            'nombreAnioBachillerato' => data_get($reporte, 'contexto.nombre_anio', 'Año académico'),
        ];
    }

    private function listaPdf(array $contexto, ?string $fecha)
    {
        $nombre = $this->nombreLista($contexto, 'pdf');

        return Pdf::loadView('pdf.lista-promedio-anual-bachillerato', [
            ...$contexto,
            'fecha_documento' => $this->fechaDocumento($fecha),
        ])
            ->setPaper('letter', 'landscape')
            ->stream($nombre);
    }

    private function listaWord(array $contexto, ?string $fecha): BinaryFileResponse
    {
        $directorio = storage_path('app/temp');
        $directorioPhpWord = storage_path('app/temp/phpword');
        File::ensureDirectoryExists($directorio, 0775, true);
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
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 15840,
            'pageSizeH' => 12240,
            'marginTop' => 620,
            'marginBottom' => 620,
            'marginLeft' => 620,
            'marginRight' => 620,
        ]);

        $generacion = $contexto['generacion'];
        $ciclo = $contexto['ciclo'];
        $reporte = $contexto['reporte'];
        $numeros = data_get($reporte, 'contexto.numeros_semestre', []);

        $section->addText('CENTRO UNIVERSITARIO MOCTEZUMA', [
            'bold' => true,
            'size' => 15,
            'color' => '006492',
        ], ['alignment' => 'center']);
        $section->addText('PROMEDIO ANUAL DE BACHILLERATO', [
            'bold' => true,
            'size' => 13,
            'color' => '0F172A',
        ], ['alignment' => 'center', 'spaceAfter' => 80]);
        $section->addText(
            'Generación ' . $generacion->anio_ingreso . '-' . $generacion->anio_egreso
                . ' · Ciclo escolar ' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio
                . ' · Semestres ' . ($numeros[0] ?? '—') . ' y ' . ($numeros[1] ?? '—'),
            ['bold' => true, 'size' => 9, 'color' => '475569'],
            ['alignment' => 'center', 'spaceAfter' => 120]
        );

        $phpWord->addTableStyle('ResumenAnual', [
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 60,
        ]);
        $phpWord->addTableStyle('TablaAnual', [
            'borderSize' => 5,
            'borderColor' => '64748B',
            'cellMargin' => 50,
        ]);

        $resumen = $section->addTable('ResumenAnual');
        $resumen->addRow();
        foreach ([
            'Alumnos: ' . data_get($reporte, 'resumen.total_alumnos', 0),
            'Promedio general: ' . data_get($reporte, 'resumen.promedio_general', '—'),
            'Completos: ' . data_get($reporte, 'diagnostico.completos', 0),
            'Incompletos: ' . data_get($reporte, 'diagnostico.incompletos', 0),
            'Reconocimientos: ' . data_get($reporte, 'resumen.con_reconocimiento', 0),
        ] as $texto) {
            $resumen->addCell(2750, ['bgColor' => 'EEF6FA'])->addText($texto, ['bold' => true, 'size' => 8]);
        }

        $section->addTextBreak(1);
        $tabla = $section->addTable('TablaAnual');
        $tabla->addRow(360, ['tblHeader' => true]);
        $encabezados = [
            ['N.º', 550],
            ['LUGAR', 900],
            ['ALUMNO', 4100],
            ['MATRÍCULA', 1700],
            ['SEM. ' . ($numeros[0] ?? '—'), 1300],
            ['SEM. ' . ($numeros[1] ?? '—'), 1300],
            ['PROMEDIO ANUAL', 1500],
            ['SITUACIÓN', 2400],
        ];

        foreach ($encabezados as [$texto, $ancho]) {
            $celda = $tabla->addCell($ancho, ['bgColor' => '006492', 'valign' => 'center']);
            $celda->addText($texto, ['bold' => true, 'size' => 7.5, 'color' => 'FFFFFF'], ['alignment' => 'center']);
        }

        foreach (collect($reporte['alumnos']) as $indice => $fila) {
            $tabla->addRow();
            $valores = [
                $indice + 1,
                $fila['texto_lugar'] ?? 'Pendiente',
                $fila['alumno'] ?? 'Sin nombre',
                $fila['matricula'] ?? '—',
                PromedioExcel::formatear(data_get($fila, 'periodos.1'), 2, '—'),
                PromedioExcel::formatear(data_get($fila, 'periodos.2'), 2, '—'),
                PromedioExcel::formatear($fila['promedio_final'] ?? $fila['promedio_provisional'] ?? null, 2, '—'),
                $fila['estatus'] ?? 'Pendiente',
            ];

            foreach ($valores as $posicion => $valor) {
                $ancho = $encabezados[$posicion][1];
                $celda = $tabla->addCell($ancho, ['valign' => 'center']);
                $celda->addText((string) $valor, [
                    'bold' => in_array($posicion, [1, 2, 6], true),
                    'size' => 7.5,
                ], [
                    'alignment' => $posicion === 2 ? 'left' : 'center',
                ]);
            }
        }

        $section->addTextBreak(1);
        $section->addText(
            'Fórmula: promedio de cada materia calificable = (P1 + P2) / 2; promedio semestral = promedio de materias calificables; promedio anual = (semestre ' . ($numeros[0] ?? '—') . ' + semestre ' . ($numeros[1] ?? '—') . ') / 2. Fecha: ' . $this->fechaDocumento($fecha) . '.',
            ['size' => 7.5, 'italic' => true, 'color' => '475569']
        );

        $path = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return response()
            ->download($path, $this->nombreLista($contexto, 'docx'))
            ->deleteFileAfterSend(true);
    }

    private function nombreLista(array $contexto, string $extension): string
    {
        return 'LISTA_PROMEDIO_ANUAL_BACHILLERATO_GENERACION_'
            . $contexto['generacion']->anio_ingreso . '-' . $contexto['generacion']->anio_egreso
            . '_CICLO_' . $contexto['ciclo']->inicio_anio . '-' . $contexto['ciclo']->fin_anio
            . '.' . $extension;
    }

    private function fechaDocumento(?string $fecha): string
    {
        try {
            return Carbon::parse($fecha ?: now())
                ->locale('es')
                ->translatedFormat('d \\d\\e F \\d\\e Y');
        } catch (\Throwable) {
            return now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y');
        }
    }

    private function imagenBase64(string $ruta): ?string
    {
        if (! is_file($ruta) || ! is_readable($ruta)) {
            return null;
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta));
    }

    private function nombreSeguro(string $nombre): string
    {
        return Str::upper(Str::slug($nombre, '_')) ?: 'ALUMNO';
    }
}
