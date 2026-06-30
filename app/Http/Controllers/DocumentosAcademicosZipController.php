<?php

namespace App\Http\Controllers;

use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\LugarPreescolar;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\CalificacionOficialPrimariaService;
use App\Services\PromedioBachilleratoService;
use App\Services\PromedioSecundariaService;
use App\Support\PromedioExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use ZipArchive;

class DocumentosAcademicosZipController extends Controller
{
    public function descargar(
        Request $request,
        string $slug_nivel,
        string $tipo,
        PDFController $pdfController
    ): BinaryFileResponse {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');
        abort_unless(in_array($tipo, ['reconocimientos', 'diplomas'], true), 404);

        $datos = $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'fecha' => ['nullable', 'date'],
        ]);

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        abort_if(
            $nivel->slug === 'preescolar',
            422,
            'Las descargas de preescolar se generan desde el módulo Lugares Preescolar.'
        );

        $grado = Grado::query()
            ->whereKey((int) $datos['grado_id'])
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        $esBachillerato = $nivel->slug === 'bachillerato' || (int) $nivel->id === 4;

        if ($tipo === 'reconocimientos' && ! $esBachillerato) {
            abort_unless(
                in_array($nivel->slug, ['primaria', 'secundaria'], true),
                403,
                'La descarga masiva de reconocimientos solo está habilitada para primaria, secundaria y bachillerato.'
            );
        }

        $semestreSeleccionado = null;

        if ($esBachillerato) {
            abort_unless(
                ! empty($datos['semestre_id']),
                422,
                'Debes seleccionar el semestre de bachillerato.'
            );

            $semestreSeleccionado = Semestre::query()
                ->whereKey((int) $datos['semestre_id'])
                ->where('grado_id', $grado->id)
                ->first();

            abort_unless(
                $semestreSeleccionado,
                404,
                'El semestre seleccionado no pertenece al grado indicado.'
            );
        }

        if ($tipo === 'diplomas') {
            $this->validarGradoTerminal($nivel, $grado);

            if ($esBachillerato) {
                abort_unless(
                    (int) $semestreSeleccionado?->numero === 6,
                    403,
                    'Los diplomas de bachillerato solo están disponibles para sexto semestre.'
                );
            }
        }

        $grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'semestre:id,grado_id,numero',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->when(
                !empty($datos['generacion_id']),
                fn ($query) => $query->where('generacion_id', (int) $datos['generacion_id'])
            )
            ->when(
                $esBachillerato && $semestreSeleccionado,
                fn ($query) => $query->where('semestre_id', $semestreSeleccionado->id)
            )
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ]);

        abort_if($grupos->isEmpty(), 404, 'No se encontraron grupos para el grado seleccionado.');

        $filasBachilleratoElegibles = collect();

        if ($esBachillerato) {
            $reporteBachillerato = app(PromedioBachilleratoService::class)->reporteSemestral(
                nivelId: (int) $nivel->id,
                cicloEscolarId: (int) $datos['ciclo_escolar_id'],
                generacionId: ! empty($datos['generacion_id']) ? (int) $datos['generacion_id'] : null,
                gradoId: (int) $grado->id,
                grupoId: null,
                semestreId: (int) $semestreSeleccionado->id,
            );

            $filasBachilleratoElegibles = collect($reporteBachillerato['alumnos'] ?? [])
                ->filter(fn (array $fila) => $tipo === 'reconocimientos'
                    ? ($fila['reconocimiento_disponible'] ?? false) === true
                    : ($fila['diploma_disponible'] ?? false) === true)
                ->values();
        }

        if ($esBachillerato) {
            /*
             * El contexto del documento se toma del reporte semestral, no de
             * los campos actuales de la inscripción. Así también se generan
             * reconocimientos de un semestre ya concluido aunque el alumno
             * actualmente esté registrado en el semestre siguiente.
             */
            $alumnosPorId = Inscripcion::withTrashed()
                ->where('nivel_id', $nivel->id)
                ->whereIn(
                    'id',
                    $filasBachilleratoElegibles
                        ->pluck('inscripcion_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->all()
                )
                ->get()
                ->keyBy('id');

            $gruposPorId = $grupos->keyBy('id');

            $documentos = $filasBachilleratoElegibles
                ->map(function (array $fila) use ($alumnosPorId, $gruposPorId): ?array {
                    $alumno = $alumnosPorId->get((int) $fila['inscripcion_id']);
                    $grupo = $gruposPorId->get((int) $fila['grupo_id']);

                    if (! $alumno || ! $grupo) {
                        return null;
                    }

                    return [
                        'alumno' => $alumno,
                        'grupo' => $grupo,
                        'generacion_id' => (int) $fila['generacion_id'],
                        'grado_id' => (int) $fila['grado_id'],
                        'grupo_id' => (int) $fila['grupo_id'],
                        'semestre_id' => (int) $fila['semestre_id'],
                        'lugar' => isset($fila['lugar']) ? (int) $fila['lugar'] : null,
                    ];
                })
                ->filter()
                ->values();
        } else {
            $lugaresPorAlumno = $tipo === 'reconocimientos'
                ? $this->lugaresReconocimientoBasica(
                    nivel: $nivel,
                    grupos: $grupos,
                    cicloEscolarId: (int) $datos['ciclo_escolar_id'],
                    generacionId: ! empty($datos['generacion_id'])
                        ? (int) $datos['generacion_id']
                        : null,
                    gradoId: (int) $grado->id,
                )
                : collect();

            $alumnos = Inscripcion::query()
                ->with([
                    'grupo.asignacionGrupo:id,nombre',
                    'grupo.semestre:id,grado_id,numero',
                ])
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->whereIn('grupo_id', $grupos->pluck('id'))
                ->where('activo', true)
                ->when(
                    ! empty($datos['generacion_id']),
                    fn ($query) => $query->where('generacion_id', (int) $datos['generacion_id'])
                )
                ->orderBy('grupo_id')
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre')
                ->get();

            $documentos = $alumnos
                ->map(function (Inscripcion $alumno) use ($lugaresPorAlumno): ?array {
                    if (! $alumno->grupo || ! $alumno->generacion_id) {
                        return null;
                    }

                    return [
                        'alumno' => $alumno,
                        'grupo' => $alumno->grupo,
                        'generacion_id' => (int) $alumno->generacion_id,
                        'grado_id' => (int) $alumno->grado_id,
                        'grupo_id' => (int) $alumno->grupo_id,
                        'semestre_id' => null,
                        'lugar' => $lugaresPorAlumno->get((int) $alumno->id),
                    ];
                })
                ->filter()
                ->values();
        }

        abort_if(
            $documentos->isEmpty(),
            404,
            $esBachillerato
                ? 'No hay alumnos con el documento académico habilitado en el semestre seleccionado.'
                : 'No se encontraron alumnos activos para el grado seleccionado.'
        );

        $zipPath = $this->crearRutaTemporalZip();
        $zip = new ZipArchive();

        abort_unless(
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true,
            500,
            'No fue posible crear el archivo ZIP.'
        );

        $documentosAgregados = 0;

        try {
            foreach ($documentos as $documento) {
                /** @var Inscripcion $alumno */
                $alumno = $documento['alumno'];
                /** @var Grupo $grupo */
                $grupo = $documento['grupo'];

                $parametros = [
                    'generacion_id' => (int) $documento['generacion_id'],
                    'grado_id' => (int) $documento['grado_id'],
                    'grupo_id' => (int) $documento['grupo_id'],
                    'inscripcion_id' => (int) $alumno->id,
                    'ciclo_escolar_id' => (int) $datos['ciclo_escolar_id'],
                    'fecha' => $datos['fecha'] ?? null,
                ];

                if ($esBachillerato) {
                    $semestreId = (int) $documento['semestre_id'];

                    if ($semestreId <= 0) {
                        continue;
                    }

                    $parametros['semestre_id'] = $semestreId;
                }

                $requestPdf = Request::create('/', 'GET', $parametros);
                $requestPdf->setUserResolver(fn () => $request->user());

                try {
                    $respuestaPdf = $pdfController->boletareconocimientoPromedioPdf(
                        $requestPdf,
                        $nivel->slug,
                        $tipo === 'reconocimientos' ? 'reconocimiento' : 'diploma'
                    );
                } catch (HttpExceptionInterface $exception) {
                    if (in_array($exception->getStatusCode(), [403, 422], true)) {
                        continue;
                    }

                    throw $exception;
                }

                $contenido = $respuestaPdf->getContent();

                if (!is_string($contenido) || $contenido === '') {
                    continue;
                }

                if ($zip->addFromString(
                    $this->rutaDocumentoZip(
                        $alumno,
                        $tipo,
                        $grupo,
                        isset($documento['lugar']) ? (int) $documento['lugar'] : null,
                    ),
                    $contenido
                )) {
                    $documentosAgregados++;
                }
            }
        } catch (Throwable $exception) {
            $zip->close();
            File::delete($zipPath);
            throw $exception;
        }

        $zip->close();

        if ($documentosAgregados === 0) {
            File::delete($zipPath);

            abort(
                404,
                $tipo === 'reconocimientos'
                    ? ($esBachillerato
                        ? 'No hay alumnos con reconocimiento habilitado en el semestre seleccionado.'
                        : 'No hay alumnos con reconocimiento habilitado en el grado seleccionado.')
                    : ($esBachillerato
                        ? 'No hay alumnos con diploma habilitado en sexto semestre.'
                        : 'No hay alumnos con diploma habilitado en el grado seleccionado.')
            );
        }

        $nombreZip = $this->nombreZip(
            $nivel,
            $grado,
            $tipo,
            (int) $datos['ciclo_escolar_id'],
            $semestreSeleccionado
        );

        return response()
            ->download($zipPath, $nombreZip)
            ->deleteFileAfterSend(true);
    }

    public function descargarPreescolar(
        Request $request,
        string $tipo,
        LugarPreescolarPDFController $pdfController
    ): BinaryFileResponse {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');
        abort_unless(in_array($tipo, ['reconocimientos', 'diplomas'], true), 404);

        $datos = $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'tipo_reconocimiento' => ['nullable', 'in:periodo,anual'],
            'periodo' => ['nullable', 'integer', 'in:0,1,2,3'],
            'fecha' => ['nullable', 'date'],
        ]);

        $nivel = Nivel::query()
            ->where('slug', 'preescolar')
            ->firstOrFail();

        $grado = Grado::query()
            ->whereKey((int) $datos['grado_id'])
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        if ($tipo === 'diplomas') {
            $this->validarGradoTerminal($nivel, $grado);
        }

        $zipPath = $this->crearRutaTemporalZip();
        $zip = new ZipArchive();

        abort_unless(
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true,
            500,
            'No fue posible crear el archivo ZIP.'
        );

        $documentosAgregados = 0;

        try {
            if ($tipo === 'reconocimientos') {
                $tipoReconocimiento = (string) ($datos['tipo_reconocimiento'] ?? 'periodo');
                $periodo = $tipoReconocimiento === 'anual'
                    ? 0
                    : (int) ($datos['periodo'] ?? 1);

                $registros = LugarPreescolar::query()
                    ->with([
                        'alumno.grupo.asignacionGrupo:id,nombre',
                        'alumno.nivel.director',
                        'alumno.nivel.supervisor',
                        'alumno.grado',
                        'alumno.generacion',
                        'cicloEscolar',
                    ])
                    ->where('nivel_id', $nivel->id)
                    ->where('grado_id', $grado->id)
                    ->where('ciclo_escolar_id', (int) $datos['ciclo_escolar_id'])
                    ->where('tipo_reconocimiento', $tipoReconocimiento)
                    ->where('periodo', $periodo)
                    ->when(
                        !empty($datos['generacion_id']),
                        fn ($query) => $query->where('generacion_id', (int) $datos['generacion_id'])
                    )
                    ->whereHas('alumno', fn ($query) => $query->where('activo', true))
                    ->orderBy('grupo_id')
                    ->orderBy('inscripcion_id')
                    ->get();

                foreach ($registros as $registro) {
                    $alumno = $registro->alumno;

                    if (!$alumno) {
                        continue;
                    }

                    $requestPdf = Request::create('/', 'GET', [
                        'fecha' => $datos['fecha'] ?? null,
                    ]);
                    $requestPdf->setUserResolver(fn () => $request->user());

                    $respuestaPdf = $pdfController->show($requestPdf, $registro);
                    $contenido = $respuestaPdf->getContent();

                    if (!is_string($contenido) || $contenido === '') {
                        continue;
                    }

                    if ($zip->addFromString(
                        $this->rutaDocumentoZip(
                            $alumno,
                            $tipo,
                            null,
                            $registro->lugar !== null ? (int) $registro->lugar : null,
                        ),
                        $contenido
                    )) {
                        $documentosAgregados++;
                    }
                }
            } else {
                $alumnos = Inscripcion::query()
                    ->with([
                        'grupo.asignacionGrupo:id,nombre',
                        'nivel.director',
                        'nivel.supervisor',
                        'grado',
                        'generacion',
                    ])
                    ->where('nivel_id', $nivel->id)
                    ->where('grado_id', $grado->id)
                    ->where('activo', true)
                    ->when(
                        !empty($datos['generacion_id']),
                        fn ($query) => $query->where('generacion_id', (int) $datos['generacion_id'])
                    )
                    ->orderBy('grupo_id')
                    ->orderBy('apellido_paterno')
                    ->orderBy('apellido_materno')
                    ->orderBy('nombre')
                    ->get();

                foreach ($alumnos as $alumno) {
                    $requestPdf = Request::create('/', 'GET', [
                        'ciclo_escolar_id' => (int) $datos['ciclo_escolar_id'],
                        'fecha' => $datos['fecha'] ?? null,
                    ]);
                    $requestPdf->setUserResolver(fn () => $request->user());

                    $respuestaPdf = $pdfController->diploma($requestPdf, $alumno);
                    $contenido = $respuestaPdf->getContent();

                    if (!is_string($contenido) || $contenido === '') {
                        continue;
                    }

                    if ($zip->addFromString(
                        $this->rutaDocumentoZip($alumno, $tipo),
                        $contenido
                    )) {
                        $documentosAgregados++;
                    }
                }
            }
        } catch (Throwable $exception) {
            $zip->close();
            File::delete($zipPath);
            throw $exception;
        }

        $zip->close();

        if ($documentosAgregados === 0) {
            File::delete($zipPath);

            abort(
                404,
                $tipo === 'reconocimientos'
                    ? 'No hay reconocimientos guardados para el grado seleccionado.'
                    : 'No hay alumnos activos con diploma habilitado en el grado seleccionado.'
            );
        }

        $nombreZip = $this->nombreZip($nivel, $grado, $tipo, (int) $datos['ciclo_escolar_id']);

        return response()
            ->download($zipPath, $nombreZip)
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, Grupo>  $grupos
     * @return Collection<int, int>
     */
    private function lugaresReconocimientoBasica(
        Nivel $nivel,
        Collection $grupos,
        int $cicloEscolarId,
        ?int $generacionId,
        int $gradoId,
    ): Collection {
        $lugaresPorAlumno = collect();

        foreach ($grupos as $grupo) {
            $reporte = match ($nivel->slug) {
                'primaria' => app(CalificacionOficialPrimariaService::class)->reporteAnual(
                    nivelId: (int) $nivel->id,
                    cicloEscolarId: $cicloEscolarId,
                    generacionId: $generacionId,
                    gradoId: $gradoId,
                    grupoId: (int) $grupo->id,
                ),
                'secundaria' => app(PromedioSecundariaService::class)->reporteAnual(
                    nivelId: (int) $nivel->id,
                    cicloEscolarId: $cicloEscolarId,
                    generacionId: $generacionId,
                    gradoId: $gradoId,
                    grupoId: (int) $grupo->id,
                ),
                default => ['alumnos' => collect()],
            };

            $filasElegibles = collect($reporte['alumnos'] ?? [])
                ->filter(function (array $fila) use ($nivel): bool {
                    if (
                        ! ($fila['completo'] ?? false)
                        || ! is_numeric($fila['promedio_general_preciso'] ?? null)
                    ) {
                        return false;
                    }

                    if ($nivel->slug === 'primaria') {
                        return ($fila['promocion_sugerida'] ?? false) === true;
                    }

                    if ($nivel->slug === 'secundaria') {
                        return empty($fila['materias_reprobadas'] ?? []);
                    }

                    return false;
                })
                ->values();

            $promediosUnicos = $filasElegibles
                ->pluck('promedio_general_preciso')
                ->sortDesc()
                ->map(fn ($promedio) => PromedioExcel::claveComparacion($promedio))
                ->filter()
                ->unique()
                ->values();

            foreach ($filasElegibles as $fila) {
                $clavePromedio = PromedioExcel::claveComparacion($fila['promedio_general_preciso']);
                $indice = $clavePromedio !== null
                    ? $promediosUnicos->search($clavePromedio)
                    : false;

                if ($indice !== false) {
                    $lugaresPorAlumno->put(
                        (int) $fila['inscripcion_id'],
                        ((int) $indice) + 1,
                    );
                }
            }
        }

        return $lugaresPorAlumno;
    }

    private function validarGradoTerminal(Nivel $nivel, Grado $grado): void
    {
        $gradoTerminalId = Grado::query()
            ->where('nivel_id', $nivel->id)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');

        abort_unless(
            $gradoTerminalId !== null && (int) $grado->id === (int) $gradoTerminalId,
            403,
            'Los diplomas solo están disponibles para el último grado del nivel.'
        );
    }

    private function crearRutaTemporalZip(): string
    {
        $directorio = storage_path('app/temp');
        File::ensureDirectoryExists($directorio);

        return $directorio . DIRECTORY_SEPARATOR . 'documentos_academicos_' . Str::uuid() . '.zip';
    }

    private function rutaDocumentoZip(
        Inscripcion $alumno,
        string $tipo,
        ?Grupo $grupoContexto = null,
        ?int $lugar = null,
    ): string {
        $grupo = $grupoContexto ?: $alumno->grupo;
        $grupoNombre = trim((string) ($grupo?->asignacionGrupo?->nombre ?? 'SIN_GRUPO'));
        $grupoNombre = preg_replace('/^grupo\s+/iu', '', $grupoNombre) ?: $grupoNombre;
        $carpeta = 'Grupo_' . $this->segmentoArchivo($grupoNombre);
        $prefijo = $tipo === 'reconocimientos' ? 'RECONOCIMIENTO' : 'DIPLOMA';
        $segmentoLugar = $tipo === 'reconocimientos' && $lugar !== null && $lugar > 0
            ? '_' . $lugar . '_LUGAR'
            : '';
        $matricula = $this->segmentoArchivo((string) ($alumno->matricula ?: 'SIN_MATRICULA'));
        $nombre = $this->segmentoArchivo($this->nombreAlumno($alumno));

        return $carpeta . '/' . $prefijo . $segmentoLugar . '_' . $matricula . '_' . $nombre . '_' . $alumno->id . '.pdf';
    }

    private function nombreZip(
        Nivel $nivel,
        Grado $grado,
        string $tipo,
        int $cicloEscolarId,
        ?Semestre $semestre = null,
    ): string {
        return mb_strtoupper($tipo)
            . '_' . $this->segmentoArchivo($grado->nombre)
            . ($semestre ? '_SEMESTRE_' . $semestre->numero : '')
            . '_' . $this->segmentoArchivo($nivel->nombre)
            . '_CICLO_' . $cicloEscolarId
            . '.zip';
    }

    private function nombreAlumno(Inscripcion $alumno): string
    {
        $nombre = trim(collect([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])->filter()->implode(' '));

        return $nombre !== '' ? $nombre : 'ALUMNO';
    }

    private function segmentoArchivo(string $valor): string
    {
        $segmento = Str::of($valor)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return $segmento !== '' ? $segmento : 'SIN_DATO';
    }
}
