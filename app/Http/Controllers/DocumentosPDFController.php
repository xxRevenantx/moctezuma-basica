<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use App\Models\ConstanciaPlantilla;
use App\Models\Escuela;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Oficio;
use App\Services\ExpedienteArchivoService;
use App\Support\PromedioExcel;


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

        try {
            $this->archivarConstanciaEnExpediente($constancia, $contenidoPdf);
        } catch (\Throwable $e) {
            report($e);
        }

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
                'nivel_id' => $alumno->nivel_id,
                'grado_id' => $alumno->grado_id,
                'grupo_id' => $alumno->grupo_id,
                'ciclo_escolar_id' => $movimiento?->ciclo_escolar_id,
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
     * Genera un solo PDF con una constancia por página.
     * La generación masiva no crea registros ni archiva documentos en expedientes.
     */
    public function constanciasMasivasPdf()
    {
        $payload = session()->pull('constancias_masivas_payload');

        if (!$payload || empty($payload['alumno_ids'])) {
            abort(404, 'No hay constancias para mostrar. Vuelve a generarlas desde el módulo de constancias.');
        }

        $plantilla = ConstanciaPlantilla::query()->find($payload['plantilla_id']);

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

        $documentos = $alumnos->map(function (Inscripcion $alumno) use ($payload, $plantilla) {
            $alumnoArray = $this->formatearAlumno($alumno);
            $contenidoGenerado = $this->reemplazarVariablesConAlumno(
                $payload['contenido_html'],
                $alumnoArray,
                $payload
            );

            $constanciaTemporal = (object) [
                'id' => null,
                'folio' => $this->generarFolioTemporal($alumno->id),
                'fecha_expedicion' => Carbon::parse(
                    $payload['fecha_expedicion'] ?? now()->toDateString()
                ),
                'dirigido_a' => $payload['dirigido_a'] ?? null,
                'modo_descarga' => $payload['modo_descarga'] ?? 'masivo',
                'periodos_calificaciones' => $payload['periodos_calificaciones'] ?? [],
                'contenido_generado_html' => $contenidoGenerado,
                'plantilla' => $plantilla,
            ];

            return [
                'constancia' => $constanciaTemporal,
                'alumno' => $alumno,
                'plantilla' => $plantilla,
                'calificacionesConstancia' => $this->obtenerCalificacionesConstancia(
                    $alumno,
                    $constanciaTemporal,
                    $plantilla
                ),
            ];
        })->values()->all();

        $pdf = Pdf::loadView('pdf.constancia_estudios_pdf', [
            'constanciasMasivas' => $documentos,
        ])->setPaper('letter', 'portrait');

        $nombreArchivo = 'CONSTANCIAS_' .
            Str::upper($payload['modo_descarga'] ?? 'MASIVO') . '_' .
            now()->format('Ymd_His') . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

        $sexoOriginal = strtoupper(trim((string) ($alumno->sexo ?? $alumno->genero ?? '')));

        return [
            'id' => $alumno->id,
            'nombre_completo' => trim(
                ($alumno->nombre ?? '') . ' ' .
                    ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '')
            ),
            'curp' => $alumno->curp ?? '',
            'matricula' => $alumno->matricula ?? '',
            'nivel' => $alumno->nivel?->nombre ?? '',
            'cct' => $alumno->nivel?->cct ?? '',
            'grado' => $alumno->grado?->nombre ?? '',
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? '',
            'generacion' => $generacion,
            'ciclo' => $alumno->ciclo?->ciclo ?? '',
            'sexo_original' => $sexoOriginal,
        ];
    }

    /**
     * Reemplaza las variables de la plantilla con datos del alumno.
     * Mantiene el mismo texto usado en la generación individual.
     */
    private function reemplazarVariablesConAlumno(string $contenido, array $alumno, array $payload): string
    {
        $sexoOriginal = strtoupper(trim((string) ($alumno['sexo_original'] ?? '')));

        $esMasculino = str_contains($sexoOriginal, 'MASCULINO')
            || $sexoOriginal === 'H'
            || $sexoOriginal === 'HOMBRE';

        $sexo = $esMasculino ? 'Que el alumno:' : 'Que la alumna:';
        $descripcion = $esMasculino ? 'regularmente inscrito' : 'regularmente inscrita';

        $fechaExpedicion = $payload['fecha_expedicion'] ?? now()->toDateString();
        $dirigidoA = trim((string) ($payload['dirigido_a'] ?? ''));

        $variables = [
            '@sexo' => $sexo,
            '@nombre' => $alumno['nombre_completo'] ?? '',
            '@alumno' => $alumno['nombre_completo'] ?? '',
            '@curp' => $alumno['curp'] ?? '',
            '@matricula' => $alumno['matricula'] ?? '',
            '@grado' => $alumno['grado'] ?? '',
            '@nivel_minuscula' => Str::lower(trim((string) ($alumno['nivel'] ?? ''))),
            '@nivel' => $alumno['nivel'] ?? '',
            '@grupo' => $alumno['grupo'] ?? '',
            '@generacion' => $alumno['generacion'] ?? '',
            '@ciclo' => $alumno['ciclo'] ?? '',
            '@cct' => $alumno['cct'] ?? '',
            '@descripcion' => $descripcion,
            '@fecha' => Carbon::parse($fechaExpedicion)->translatedFormat('d \d\e F \d\e Y'),
            '@dirigido' => $dirigidoA !== '' ? $dirigidoA : 'A QUIEN CORRESPONDA',
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

        $textoPlantilla = Str::lower(trim(($plantilla->clave ?? '') . ' ' . ($plantilla->titulo ?? '')));

        if (
            !Str::contains($textoPlantilla, 'estudio')
            || Str::contains($textoPlantilla, ['relaciones', 'conducta', 'baja', 'traslado'])
        ) {
            return [];
        }

        $configuracionPeriodos = $constancia->periodos_calificaciones ?? [];

        if (is_string($configuracionPeriodos)) {
            $configuracionPeriodos = json_decode($configuracionPeriodos, true) ?: [];
        }

        if (!(bool) ($configuracionPeriodos['incluir_calificaciones'] ?? false)) {
            return [];
        }

        $nivelSlug = Str::lower((string) ($alumno->nivel?->slug ?? ''));

        // Preescolar trabaja con fichas descriptivas, no con calificaciones numéricas.
        if (Str::contains($nivelSlug, 'preescolar')) {
            return [];
        }

        $periodosSeleccionados = $this->obtenerPeriodosSeleccionadosConstancia($configuracionPeriodos);

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
            ->when($cicloEscolarId, fn($consulta) => $consulta->where('periodos.ciclo_escolar_id', $cicloEscolarId))
            ->select('periodos.id', 'periodos.periodo_basica_id', 'periodos_basica.periodo', 'periodos_basica.descripcion')
            ->orderBy('periodos_basica.periodo')
            ->get()
            ->keyBy('periodo_basica_id');

        if ($periodos->isEmpty()) {
            return [];
        }

        if (Str::contains($nivelSlug, 'primaria')) {
            return $this->obtenerCamposFormativosPrimaria($alumno, $periodosSeleccionados, $periodos, $cicloEscolarId);
        }

        return $this->obtenerMateriasCalificables($alumno, $periodosSeleccionados, $periodos, $cicloEscolarId);
    }

    private function obtenerCamposFormativosPrimaria(
        Inscripcion $alumno,
        array $periodosSeleccionados,
        $periodos,
        ?int $cicloEscolarId
    ): array {
        $campos = DB::table('campos_formativos')
            ->where('activo', true)
            ->whereIn('slug', [
                'lenguajes',
                'saberes-pensamiento-cientifico',
                'etica-naturaleza-sociedades',
                'humano-comunitario',
            ])
            ->select('id', 'nombre', 'orden')
            ->orderBy('orden')
            ->get();

        if ($campos->isEmpty()) {
            return [];
        }

        $periodoIds = $periodos->pluck('id')->values()->all();

        $oficiales = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('calificaciones_campos_formativos')) {
            $oficiales = DB::table('calificaciones_campos_formativos')
                ->where('inscripcion_id', $alumno->id)
                ->whereIn('periodo_id', $periodoIds)
                ->select('periodo_id', 'campo_formativo_id', 'calificacion_oficial', 'confirmada', 'id')
                ->orderBy('id')
                ->get()
                ->groupBy(fn($fila) => $fila->periodo_id . '_' . $fila->campo_formativo_id)
                ->map(fn($items) => $items->last());
        }

        $internas = DB::table('calificaciones')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('calificaciones.inscripcion_id', $alumno->id)
            ->whereIn('calificaciones.periodo_id', $periodoIds)
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->whereNotNull('materias.campo_formativo_id')
            ->where('calificaciones.es_numerica', true)
            ->whereNotNull('calificaciones.valor_numerico')
            ->select('calificaciones.periodo_id', 'materias.campo_formativo_id', 'materias.id as materia_id', 'calificaciones.valor_numerico', 'calificaciones.id')
            ->orderBy('calificaciones.id')
            ->get()
            ->groupBy(fn($fila) => $fila->periodo_id . '_' . $fila->campo_formativo_id);

        $columnas = $campos->map(fn($campo) => [
            'key' => 'campo_' . $campo->id,
            'label' => $campo->nombre,
        ])->values()->all();

        $filas = [];
        foreach ($periodosSeleccionados as $periodoBasicaId => $nombrePeriodo) {
            $periodo = $periodos->get($periodoBasicaId);
            if (!$periodo) {
                continue;
            }

            $valores = [];
            $numericas = [];

            foreach ($campos as $campo) {
                $llave = $periodo->id . '_' . $campo->id;
                $oficial = $oficiales->get($llave);
                $valorNumerico = null;

                if ($oficial && is_numeric($oficial->calificacion_oficial)) {
                    $valorNumerico = (float) $oficial->calificacion_oficial;
                } else {
                    $materiasCampo = collect($internas->get($llave, collect()))
                        ->groupBy('materia_id')
                        ->map(fn($items) => $items->last());
                    $valorNumerico = PromedioExcel::calcular($materiasCampo->pluck('valor_numerico'));
                }

                $key = 'campo_' . $campo->id;
                $valores[$key] = $valorNumerico === null ? '' : PromedioExcel::formatear($valorNumerico, 1, '');

                if ($valorNumerico !== null) {
                    $numericas[] = $valorNumerico;
                }
            }

            $promedio = PromedioExcel::calcular($numericas);
            $filas[] = [
                'periodo' => $nombrePeriodo,
                'valores' => $valores,
                'promedio' => PromedioExcel::formatear($promedio, 1, '0.0'),
            ];
        }

        return ['columnas' => $columnas, 'filas' => $filas, 'tipo' => 'campos'];
    }

    private function obtenerMateriasCalificables(
        Inscripcion $alumno,
        array $periodosSeleccionados,
        $periodos,
        ?int $cicloEscolarId
    ): array {
        $materias = DB::table('asignacion_materias')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('asignacion_materias.grupo_id', $alumno->grupo_id)
            ->when($cicloEscolarId, fn($consulta) => $consulta->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId))
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->select('asignacion_materias.id as asignacion_materia_id', 'materias.materia', 'asignacion_materias.orden')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        if ($materias->isEmpty()) {
            return [];
        }

        $periodoIds = $periodos->pluck('id')->values()->all();
        $calificaciones = DB::table('calificaciones')
            ->where('inscripcion_id', $alumno->id)
            ->whereIn('periodo_id', $periodoIds)
            ->select('periodo_id', 'asignacion_materia_id', 'calificacion', 'valor_numerico', 'es_numerica', 'id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn($fila) => $fila->periodo_id . '_' . $fila->asignacion_materia_id)
            ->map(fn($items) => $items->last());

        $columnas = $materias->map(fn($materia) => [
            'key' => 'materia_' . $materia->asignacion_materia_id,
            'label' => $materia->materia,
        ])->values()->all();

        $filas = [];
        foreach ($periodosSeleccionados as $periodoBasicaId => $nombrePeriodo) {
            $periodo = $periodos->get($periodoBasicaId);
            if (!$periodo) {
                continue;
            }

            $valores = [];
            $numericas = [];
            foreach ($materias as $materia) {
                $calificacion = $calificaciones->get($periodo->id . '_' . $materia->asignacion_materia_id);
                $key = 'materia_' . $materia->asignacion_materia_id;
                $valores[$key] = $calificacion?->calificacion ?? '';

                if ($calificacion && (bool) $calificacion->es_numerica && is_numeric($calificacion->valor_numerico)) {
                    $numericas[] = (float) $calificacion->valor_numerico;
                }
            }

            $promedio = PromedioExcel::calcular($numericas);
            $filas[] = [
                'periodo' => $nombrePeriodo,
                'valores' => $valores,
                'promedio' => PromedioExcel::formatear($promedio, 1, '0.0'),
            ];
        }

        return ['columnas' => $columnas, 'filas' => $filas, 'tipo' => 'materias'];
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
            ->when($cicloEscolarId, fn($consulta) => $consulta->where('asignacion_materias.ciclo_escolar_id', $cicloEscolarId))
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

            $promedio = PromedioExcel::calcular($numericas);

            $filas[] = [
                'periodo' => $nombrePeriodo,
                'valores' => $valores,
                'promedio' => PromedioExcel::formatear($promedio, 1, '0.0'),
                'promedio_calculo' => $promedio,
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
