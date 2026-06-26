<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use App\Models\ConstanciaPlantilla;
use App\Models\Escuela;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use App\Models\Oficio;
use App\Services\ExpedienteArchivoService;


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
        $contenidoPdf = $pdf->output();

        $this->archivarConstanciaEnExpediente($constancia, $contenidoPdf);

        return response($contenidoPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function archivarConstanciaEnExpediente(Constancia $constancia, string $contenidoPdf): void
    {
        if ($constancia->documento_alumno_id || !$constancia->alumno || !$constancia->plantilla) {
            return;
        }

        $identificador = Str::lower(trim(
            ($constancia->plantilla->clave ?? '') . ' ' .
                ($constancia->plantilla->titulo ?? '')
        ));

        $tipoSlug = match (true) {
            Str::contains($identificador, ['baja', 'traslado']) => 'constancia-baja-traslado',
            Str::contains($identificador, 'estudio') => 'constancia-estudios',
            default => null,
        };

        if (!$tipoSlug) {
            return;
        }

        $alumno = $constancia->alumno;
        $trayectoria = $alumno->trayectoriasAcademicas()->latest('id')->first();
        $movimiento = $tipoSlug === 'constancia-baja-traslado'
            ? $alumno->movimientos()
            ->whereIn('tipo', ['baja_definitiva', 'baja_temporal', 'traslado'])
            ->latest('fecha')
            ->latest('id')
            ->first()
            : null;

        $documento = app(ExpedienteArchivoService::class)->guardarPdfGenerado(
            $alumno,
            $tipoSlug,
            $contenidoPdf,
            [
                'nivel_id' => $trayectoria?->nivel_id ?? $alumno->nivel_id,
                'grado_id' => $trayectoria?->grado_id ?? $alumno->grado_id,
                'grupo_id' => $trayectoria?->grupo_id ?? $alumno->grupo_id,
                'ciclo_escolar_id' => $trayectoria?->ciclo_escolar_id,
                'trayectoria_academica_id' => $trayectoria?->id,
                'fecha_documento' => $constancia->fecha_expedicion?->format('Y-m-d'),
                'folio' => $constancia->folio,
                'estado' => $constancia->estado_documento === 'cancelada' ? 'cancelada' : 'emitida',
                'tipo_movimiento' => $movimiento?->tipo,
                'motivo' => $movimiento?->motivo ?? ($tipoSlug === 'constancia-baja-traslado' ? $alumno->motivo_baja : null),
            ]
        );

        $constancia->update(['documento_alumno_id' => $documento->id]);

        if ($movimiento) {
            $movimiento->update(['documento_alumno_id' => $documento->id]);
        }
    }

    /**
     * Genera constancias masivas. Las constancias de estudios se guardan
     * individualmente y también se archivan en el expediente de cada alumno.
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

        $esConstanciaEstudios = $this->esPlantillaConstanciaEstudios($plantilla);

        foreach ($alumnos as $alumno) {
            $alumnoArray = $this->formatearAlumno($alumno);

            $contenidoGenerado = $this->reemplazarVariablesConAlumno(
                $payload['contenido_html'],
                $alumnoArray,
                $payload
            );

            if ($esConstanciaEstudios) {
                $constancia = Constancia::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'constancia_plantilla_id' => $plantilla->id,
                    'folio' => $this->generarFolioPersistente(),
                    'fecha_expedicion' => $payload['fecha_expedicion'] ?? now()->toDateString(),
                    'dirigido_a' => $payload['dirigido_a'] ?? null,
                    'modo_descarga' => $payload['modo_descarga'] ?? 'masivo',
                    'periodos_calificaciones' => $payload['periodos_calificaciones'] ?? [],
                    'contenido_generado_html' => $contenidoGenerado,
                    'estado_documento' => 'emitida',
                ]);

                $constancia->setRelation('alumno', $alumno);
                $constancia->setRelation('plantilla', $plantilla);
                $constanciaParaPdf = $constancia;
            } else {
                $folioTemporal = $this->generarFolioTemporal($alumno->id);

                // Las constancias distintas a estudios conservan el comportamiento masivo previo.
                $constanciaParaPdf = (object) [
                    'id' => null,
                    'folio' => $folioTemporal,
                    'fecha_expedicion' => Carbon::parse($payload['fecha_expedicion']),
                    'dirigido_a' => $payload['dirigido_a'] ?? null,
                    'modo_descarga' => $payload['modo_descarga'] ?? 'masivo',
                    'periodos_calificaciones' => $payload['periodos_calificaciones'] ?? [],
                    'contenido_generado_html' => $contenidoGenerado,
                    'plantilla' => $plantilla,
                ];
            }

            $calificacionesConstancia = $this->obtenerCalificacionesConstancia(
                $alumno,
                $constanciaParaPdf,
                $plantilla
            );

            $pdf = Pdf::loadView('pdf.constancia_estudios_pdf', [
                'constancia' => $constanciaParaPdf,
                'alumno' => $alumno,
                'plantilla' => $plantilla,
                'calificacionesConstancia' => $calificacionesConstancia,
            ])->setPaper('letter', 'portrait');

            $contenidoPdf = $pdf->output();

            if ($esConstanciaEstudios && $constanciaParaPdf instanceof Constancia) {
                $this->archivarConstanciaEnExpediente($constanciaParaPdf, $contenidoPdf);
            }

            $nombreAlumno = trim(
                ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '') . ' ' .
                    ($alumno->nombre ?? '')
            );

            $nombreArchivo = Str::slug($constanciaParaPdf->folio . '_' . $nombreAlumno, '_') . '.pdf';

            file_put_contents($carpetaTemporal . '/' . $nombreArchivo, $contenidoPdf);
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

    private function esPlantillaConstanciaEstudios(ConstanciaPlantilla $plantilla): bool
    {
        $identificador = Str::lower(trim(($plantilla->clave ?? '') . ' ' . ($plantilla->titulo ?? '')));

        return Str::contains($identificador, 'estudio')
            && !Str::contains($identificador, ['baja', 'traslado']);
    }

    private function generarFolioPersistente(): string
    {
        $siguiente = (Constancia::query()->max('id') ?? 0) + 1;

        return 'CONST-' . now()->format('Y') . '-' . Str::padLeft((string) $siguiente, 5, '0');
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
            ->when($cicloEscolarId, fn ($consulta) => $consulta->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId))
            ->where('asignacion_materias.estado', '!=', \App\Models\AsignacionMateria::ESTADO_ARCHIVADA)
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

        $lema = Escuela::query()->value('lema') ?? '';



        $pdf = Pdf::loadView($vistaPdf, [
            'oficio' => $oficio,
            'alumno' => $oficio->alumno,
            'nivel' => $oficio->nivel ?? $oficio->alumno?->nivel,
            'director' => $oficio->director ?? $oficio->nivel?->director,
            'lema' => $lema,
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
            ->when($cicloEscolarId, fn ($consulta) => $consulta->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId))
            ->where('asignacion_materias.estado', '!=', \App\Models\AsignacionMateria::ESTADO_ARCHIVADA)
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
