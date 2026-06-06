<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use App\Models\ConstanciaPlantilla;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use App\Models\Oficio;


class DocumentosPDFController extends Controller
{
    /**
     * Genera una constancia individual guardada en la base de datos.
     */
    public function constanciaPdf(Constancia $constancia)
    {
        $constancia->load([
            'alumno.nivel.director',
            'alumno.grado',
            'alumno.generacion',
            'alumno.grupo.asignacionGrupo',
            'alumno.ciclo',
            'plantilla',
        ]);

        $calificacionesConstancia = $this->obtenerCalificacionesConstancia(
            $constancia->alumno,
            $constancia,
            $constancia->plantilla
        );

        $pdf = Pdf::loadView('pdf.constancia_estudios_pdf', [
            'constancia' => $constancia,
            'alumno' => $constancia->alumno,
            'plantilla' => $constancia->plantilla,
            'calificacionesConstancia' => $calificacionesConstancia,
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = Str::slug($constancia->folio, '_') . '.pdf';

        return $pdf->stream($nombreArchivo);
    }

    /**
     * Genera constancias masivas sin guardarlas en la base de datos.
     */
    public function constanciasZip()
    {
        $payload = session()->pull('constancias_zip_payload');

        if (!$payload || empty($payload['alumno_ids'])) {
            abort(404, 'No hay constancias para descargar.');
        }

        $plantilla = ConstanciaPlantilla::query()
            ->find($payload['plantilla_id']);

        if (!$plantilla) {
            abort(404, 'No se encontró la plantilla de constancia.');
        }

        $alumnos = Inscripcion::query()
            ->with([
                'nivel.director',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ])
            ->whereIn('id', $payload['alumno_ids'])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        if ($alumnos->isEmpty()) {
            abort(404, 'No se encontraron alumnos para generar constancias.');
        }

        $carpetaBase = storage_path('app/temp');

        if (!File::exists($carpetaBase)) {
            File::makeDirectory($carpetaBase, 0755, true);
        }

        $nombreCarpeta = 'constancias_' . now()->format('Ymd_His');
        $carpetaTemporal = $carpetaBase . '/' . $nombreCarpeta;

        if (!File::exists($carpetaTemporal)) {
            File::makeDirectory($carpetaTemporal, 0755, true);
        }

        foreach ($alumnos as $alumno) {
            $alumnoArray = $this->formatearAlumno($alumno);

            $contenidoGenerado = $this->reemplazarVariablesConAlumno(
                $payload['contenido_html'],
                $alumnoArray,
                $payload
            );

            $folioTemporal = $this->generarFolioTemporal($alumno->id);

            // Objeto temporal. No se guarda en la base de datos.
            $constanciaTemporal = (object) [
                'id' => null,
                'folio' => $folioTemporal,
                'fecha_expedicion' => Carbon::parse($payload['fecha_expedicion']),
                'dirigido_a' => $payload['dirigido_a'] ?? null,
                'modo_descarga' => $payload['modo_descarga'] ?? 'masivo',
                'periodos_calificaciones' => $payload['periodos_calificaciones'] ?? [],
                'contenido_generado_html' => $contenidoGenerado,
                'plantilla' => $plantilla,
            ];

            $calificacionesConstancia = $this->obtenerCalificacionesConstancia(
                $alumno,
                $constanciaTemporal,
                $plantilla
            );

            $pdf = Pdf::loadView('pdf.constancia_estudios_pdf', [
                'constancia' => $constanciaTemporal,
                'alumno' => $alumno,
                'plantilla' => $plantilla,
                'calificacionesConstancia' => $calificacionesConstancia,
            ])->setPaper('letter', 'portrait');

            $nombreAlumno = trim(
                ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '') . ' ' .
                    ($alumno->nombre ?? '')
            );

            $nombreArchivo = Str::slug($folioTemporal . '_' . $nombreAlumno, '_') . '.pdf';

            file_put_contents($carpetaTemporal . '/' . $nombreArchivo, $pdf->output());
        }

        $nombreZip = 'CONSTANCIAS_' . Str::upper($payload['modo_descarga'] ?? 'MASIVO') . '_' . now()->format('Ymd_His') . '.zip';
        $rutaZip = $carpetaBase . '/' . $nombreZip;

        $zip = new ZipArchive();

        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($carpetaTemporal);

            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach (File::files($carpetaTemporal) as $archivo) {
            $zip->addFile($archivo->getRealPath(), $archivo->getFilename());
        }

        $zip->close();

        File::deleteDirectory($carpetaTemporal);

        return response()
            ->download($rutaZip, $nombreZip)
            ->deleteFileAfterSend(true);
    }

    /**
     * Da formato a los datos del alumno para reemplazar variables.
     */
    private function formatearAlumno(Inscripcion $alumno): array
    {
        $generacion = '';

        if ($alumno->generacion) {
            $generacion = trim(
                ($alumno->generacion->anio_ingreso ?? '') .
                    '-' .
                    ($alumno->generacion->anio_egreso ?? '')
            );
        }

        return [
            'id' => $alumno->id,
            'nombre_completo' => trim(
                ($alumno->nombre ?? '') . ' ' .
                    ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '')
            ),
            'curp' => $alumno->curp,
            'matricula' => $alumno->matricula,
            'genero' => $alumno->genero,
            'nivel' => $alumno->nivel?->nombre,
            'cct' => $alumno->nivel?->cct,
            'grado' => $alumno->grado?->nombre,
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre,
            'generacion' => $generacion,
            'ciclo' => $alumno->ciclo?->ciclo,
        ];
    }

    /**
     * Reemplaza las variables de la plantilla con datos del alumno.
     */
    private function reemplazarVariablesConAlumno(string $contenido, array $alumno, array $payload): string
    {
        $genero = mb_strtolower(trim((string) ($alumno['genero'] ?? '')));

        $esMujer = in_array($genero, [
            'f',
            'm',
            'mujer',
            'femenino',
            'femenina',
            'alumna',
        ], true);

        $sexo = $esMujer ? 'La alumna' : 'El alumno';

        $descripcion = $esMujer
            ? 'se encuentra inscrita'
            : 'se encuentra inscrito';

        $variables = [
            '@alumno' => $alumno['nombre_completo'] ?? '',
            '@nombre' => $alumno['nombre_completo'] ?? '',
            '@curp' => $alumno['curp'] ?? '',
            '@matricula' => $alumno['matricula'] ?? '',
            '@grado' => $alumno['grado'] ?? '',
            '@nivel' => $alumno['nivel'] ?? '',
            '@grupo' => $alumno['grupo'] ?? '',
            '@generacion' => $alumno['generacion'] ?? '',
            '@ciclo' => $alumno['ciclo'] ?? '',
            '@cct' => $alumno['cct'] ?? '',
            '@sexo' => $sexo,
            '@descripcion' => $descripcion,
            '@fecha' => Carbon::parse($payload['fecha_expedicion'])->translatedFormat('d \d\e F \d\e Y'),
            '@dirigido' => $payload['dirigido_a'] ?: 'A QUIEN CORRESPONDA',
        ];

        return str_replace(array_keys($variables), array_values($variables), $contenido);
    }

    /**
     * Obtiene las calificaciones que se mostrarán en la constancia de estudios.
     */
    private function obtenerCalificacionesConstancia(?Inscripcion $alumno, object $constancia, ?ConstanciaPlantilla $plantilla): array
    {
        if (!$alumno || !$plantilla) {
            return [];
        }

        $textoPlantilla = mb_strtolower(
            trim(($plantilla->clave ?? '') . ' ' . ($plantilla->titulo ?? ''))
        );

        // Las calificaciones solo se muestran en constancia de estudios.
        if (str_contains($textoPlantilla, 'relaciones') || str_contains($textoPlantilla, 'conducta')) {
            return [];
        }

        $periodosSeleccionados = $this->obtenerPeriodosSeleccionadosConstancia(
            $constancia->periodos_calificaciones ?? []
        );

        if (empty($periodosSeleccionados)) {
            return [];
        }

        $cicloEscolarId = DB::table('calificaciones')
            ->where('inscripcion_id', $alumno->id)
            ->whereNotNull('ciclo_escolar_id')
            ->orderByDesc('id')
            ->value('ciclo_escolar_id');

        $periodos = DB::table('periodos')
            ->join('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->where('periodos.nivel_id', $alumno->nivel_id)
            ->whereIn('periodos.periodo_basica_id', array_keys($periodosSeleccionados))
            ->when($cicloEscolarId, function ($consulta) use ($cicloEscolarId) {
                $consulta->where('periodos.ciclo_escolar_id', $cicloEscolarId);
            })
            ->select(
                'periodos.id',
                'periodos.periodo_basica_id',
                'periodos_basica.periodo',
                'periodos_basica.descripcion'
            )
            ->orderBy('periodos_basica.periodo')
            ->get()
            ->keyBy('periodo_basica_id');

        if ($periodos->isEmpty()) {
            return [];
        }

        $materias = DB::table('asignacion_materias')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('asignacion_materias.grupo_id', $alumno->grupo_id)
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->select(
                'asignacion_materias.id as asignacion_materia_id',
                'materias.materia',
                'asignacion_materias.orden'
            )
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        if ($materias->isEmpty()) {
            return [];
        }

        $periodoIds = $periodos->pluck('id')->values()->toArray();

        $calificaciones = DB::table('calificaciones')
            ->where('inscripcion_id', $alumno->id)
            ->whereIn('periodo_id', $periodoIds)
            ->select(
                'periodo_id',
                'asignacion_materia_id',
                'calificacion',
                'valor_numerico',
                'es_numerica'
            )
            ->get()
            ->groupBy(function ($calificacion) {
                return $calificacion->periodo_id . '_' . $calificacion->asignacion_materia_id;
            });

        $filas = [];

        foreach ($periodosSeleccionados as $periodoBasicaId => $nombrePeriodo) {
            $periodo = $periodos->get($periodoBasicaId);

            if (!$periodo) {
                continue;
            }

            $valores = [];
            $numericas = [];

            foreach ($materias as $materia) {
                $llave = $periodo->id . '_' . $materia->asignacion_materia_id;
                $calificacion = $calificaciones->get($llave)?->first();

                $valor = $calificacion?->calificacion ?? '';
                $valores[$materia->asignacion_materia_id] = $valor;

                if ($calificacion && (bool) $calificacion->es_numerica && is_numeric($calificacion->valor_numerico)) {
                    $numericas[] = (float) $calificacion->valor_numerico;
                }
            }

            $promedio = count($numericas) > 0
                ? floor((array_sum($numericas) / count($numericas)) * 10) / 10
                : 0;

            $filas[] = [
                'periodo' => $nombrePeriodo,
                'valores' => $valores,
                'promedio' => number_format($promedio, 1, '.', ''),
            ];
        }

        return [
            'materias' => $materias,
            'filas' => $filas,
        ];
    }

    /**
     * Convierte los checkboxes guardados en la constancia a periodos básicos.
     */
    private function obtenerPeriodosSeleccionadosConstancia(mixed $periodos): array
    {
        if (is_string($periodos)) {
            $periodos = json_decode($periodos, true) ?: [];
        }

        if (is_object($periodos)) {
            $periodos = (array) $periodos;
        }

        if (!is_array($periodos)) {
            $periodos = [];
        }

        $seleccionados = [];

        if ((bool) ($periodos['primer_periodo'] ?? false)) {
            $seleccionados[1] = '1° PERIODO';
        }

        if ((bool) ($periodos['segundo_periodo'] ?? false)) {
            $seleccionados[2] = '2° PERIODO';
        }

        if ((bool) ($periodos['tercer_periodo'] ?? false)) {
            $seleccionados[3] = '3° PERIODO';
        }

        return $seleccionados;
    }

    /**
     * Crea un folio temporal para descargas masivas sin guardar en BD.
     */
    private function generarFolioTemporal(int $alumnoId): string
    {
        return 'CONST-' . now()->format('YmdHis') . '-' . Str::padLeft((string) $alumnoId, 5, '0');
    }


    public function oficioPdf(Oficio $oficio)
    {
        $oficio->load([
            'alumno.nivel.director',
            'alumno.grado',
            'alumno.generacion',
            'alumno.grupo.asignacionGrupo',
            'alumno.ciclo',
            'nivel.director',
            'director',
        ]);

        $nivelNombre = mb_strtolower($oficio->nivel?->nombre ?? $oficio->alumno?->nivel?->nombre ?? '');

        $vistaPdf = str_contains($nivelNombre, 'preescolar')
            ? 'pdf.oficio_preescolar_pdf'
            : 'pdf.oficio_general_pdf';

        $pdf = Pdf::loadView($vistaPdf, [
            'oficio' => $oficio,
            'alumno' => $oficio->alumno,
            'nivel' => $oficio->nivel ?? $oficio->alumno?->nivel,
            'director' => $oficio->director ?? $oficio->nivel?->director,
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = Str::slug($oficio->folio, '_') . '.pdf';

        return $pdf->stream($nombreArchivo);
    }
    private function obtenerCalificacionesOficio(?Inscripcion $alumno, Oficio $oficio): array
    {
        if (!$alumno) {
            return [];
        }

        $periodosSeleccionados = $this->obtenerPeriodosSeleccionadosOficio($oficio->periodos_calificaciones ?? []);

        if (empty($periodosSeleccionados)) {
            return [];
        }

        $cicloEscolarId = DB::table('calificaciones')
            ->where('inscripcion_id', $alumno->id)
            ->whereNotNull('ciclo_escolar_id')
            ->orderByDesc('id')
            ->value('ciclo_escolar_id');

        $periodos = DB::table('periodos')
            ->join('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->where('periodos.nivel_id', $alumno->nivel_id)
            ->whereIn('periodos.periodo_basica_id', array_keys($periodosSeleccionados))
            ->when($cicloEscolarId, function ($consulta) use ($cicloEscolarId) {
                $consulta->where('periodos.ciclo_escolar_id', $cicloEscolarId);
            })
            ->select(
                'periodos.id',
                'periodos.periodo_basica_id',
                'periodos_basica.periodo',
                'periodos_basica.descripcion'
            )
            ->orderBy('periodos_basica.periodo')
            ->get()
            ->keyBy('periodo_basica_id');

        if ($periodos->isEmpty()) {
            return [];
        }

        $materias = DB::table('asignacion_materias')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('asignacion_materias.grupo_id', $alumno->grupo_id)
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->select(
                'asignacion_materias.id as asignacion_materia_id',
                'materias.materia',
                'asignacion_materias.orden'
            )
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        if ($materias->isEmpty()) {
            return [];
        }

        $periodoIds = $periodos->pluck('id')->values()->toArray();

        $calificaciones = DB::table('calificaciones')
            ->where('inscripcion_id', $alumno->id)
            ->whereIn('periodo_id', $periodoIds)
            ->select(
                'periodo_id',
                'asignacion_materia_id',
                'calificacion',
                'valor_numerico',
                'es_numerica'
            )
            ->get()
            ->groupBy(function ($calificacion) {
                return $calificacion->periodo_id . '_' . $calificacion->asignacion_materia_id;
            });

        $filas = [];

        foreach ($periodosSeleccionados as $periodoBasicaId => $nombrePeriodo) {
            $periodo = $periodos->get($periodoBasicaId);

            if (!$periodo) {
                continue;
            }

            $valores = [];
            $numericas = [];

            foreach ($materias as $materia) {
                $llave = $periodo->id . '_' . $materia->asignacion_materia_id;
                $calificacion = $calificaciones->get($llave)?->first();

                $valor = $calificacion?->calificacion ?? '';
                $valores[$materia->asignacion_materia_id] = $valor;

                if ($calificacion && (bool) $calificacion->es_numerica && is_numeric($calificacion->valor_numerico)) {
                    $numericas[] = (float) $calificacion->valor_numerico;
                }
            }

            $promedio = count($numericas) > 0
                ? floor((array_sum($numericas) / count($numericas)) * 10) / 10
                : 0;

            $filas[] = [
                'periodo' => $nombrePeriodo,
                'valores' => $valores,
                'promedio' => number_format($promedio, 1, '.', ''),
            ];
        }

        return [
            'materias' => $materias,
            'filas' => $filas,
        ];
    }

    private function obtenerPeriodosSeleccionadosOficio(mixed $periodos): array
    {
        if (is_string($periodos)) {
            $periodos = json_decode($periodos, true) ?: [];
        }

        if (is_object($periodos)) {
            $periodos = (array) $periodos;
        }

        if (!is_array($periodos)) {
            $periodos = [];
        }

        $seleccionados = [];

        if ((bool) ($periodos['primer_periodo'] ?? false)) {
            $seleccionados[1] = '1° PERIODO';
        }

        if ((bool) ($periodos['segundo_periodo'] ?? false)) {
            $seleccionados[2] = '2° PERIODO';
        }

        if ((bool) ($periodos['tercer_periodo'] ?? false)) {
            $seleccionados[3] = '3° PERIODO';
        }

        return $seleccionados;
    }
}
