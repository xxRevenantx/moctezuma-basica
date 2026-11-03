<?php

namespace App\Http\Controllers;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Persona;
use App\Models\TallerSesion;
use App\Services\ListaAcademicaService;
use App\Services\Documentos\ConcatenadorPdfDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

/** Exportación docente PRO: no duplica las plantillas PDF ni altera las listas históricas. */
class ProfesorListasZipController extends Controller
{
    private const MAX_DOCS = 800;
    private const MAX_DOCENTES = 250;

    public function descargar(Request $request, ProfesorPdfController $pdf, ListaAcademicaService $listas)
    {
        // Un ZIP puede contener datos personales de muchos grupos: solamente administración.
        abort_unless($request->user()?->is_admin, 403);
        abort_unless(class_exists(ZipArchive::class), 503, 'Activa la extensión PHP zip (ZipArchive) para exportar listas.');

        $datos = $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'fecha_corte' => ['required', 'date_format:Y-m-d'],
            'fecha_inicio_asistencia' => ['required', 'date_format:Y-m-d'],
            'alcance' => ['required', 'in:actual,seleccion,nivel,todos'],
            'profesor_id' => ['nullable', 'integer', 'exists:personas,id'],
            'profesores' => ['nullable', 'array', 'max:250'],
            'profesores.*' => ['integer', 'distinct', 'exists:personas,id'],
            'nivel_id' => ['nullable', 'integer', 'exists:niveles,id'],
            'formato' => ['nullable', 'in:consolidado,individual'],
            'incluir_asistencia' => ['nullable', 'boolean'],
            'incluir_evaluacion' => ['nullable', 'boolean'],
            'solo_visibles' => ['nullable', 'boolean'],
            'cargas_visibles' => ['nullable', 'array', 'max:300'],
            'cargas_visibles.*' => ['string', 'max:80', 'regex:/^[mt]_[0-9]+(?:_[0-9]+)?$/'],
            'periodos' => ['nullable', 'array', 'max:300'],
            'periodos.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $formato = $datos['formato'] ?? 'consolidado';
        $asistencia = $request->boolean('incluir_asistencia');
        $evaluacion = $request->boolean('incluir_evaluacion');
        if (! $asistencia && ! $evaluacion) {
            throw ValidationException::withMessages(['tipos' => 'Selecciona asistencia, evaluación o ambas.']);
        }

        $ciclo = CicloEscolar::query()->findOrFail((int) $datos['ciclo_escolar_id']);
        $alcance = $datos['alcance'];
        $nivelId = $alcance === 'nivel' ? (int) ($datos['nivel_id'] ?? 0) : null;
        if ($alcance === 'nivel' && ! $nivelId) {
            throw ValidationException::withMessages(['nivel_id' => 'Selecciona un nivel académico.']);
        }
        $docentes = $this->docentes($alcance, (int) $ciclo->id, $datos, $nivelId);
        if ($docentes->isEmpty()) {
            throw ValidationException::withMessages(['profesores' => 'No hay profesores con carga académica para exportar.']);
        }
        if ($docentes->count() > self::MAX_DOCENTES) {
            abort(422, 'Hay demasiados profesores en esta exportación. Filtra por nivel o selecciona profesores.');
        }

        // Las selecciones específicas de la tabla solo son aplicables al docente actual.
        $visibles = $alcance === 'actual' && $request->boolean('solo_visibles')
            ? collect((array) ($datos['cargas_visibles'] ?? []))->flip()
            : null;
        $periodosManuales = (array) ($datos['periodos'] ?? []);
        $cargas = collect();
        foreach ($docentes as $docente) {
            $cargas = $cargas->concat($this->cargasDeDocente((int) $docente->id, (int) $ciclo->id, $nivelId));
        }
        $cargas = $cargas->filter(fn (array $c) => $visibles === null || $visibles->has($c['clave']))
            ->unique(fn (array $c) => $c['profesor_id'] . ':' . $c['clave'])
            ->values();
        if ($cargas->isEmpty()) {
            throw ValidationException::withMessages(['cargas' => 'No existen cargas que coincidan con la selección.']);
        }

        $estimados = $cargas->sum(fn (array $c) => (int) $asistencia + (int) ($evaluacion && $c['calificable']));
        abort_if($estimados > self::MAX_DOCS, 422,
            'La selección supera el límite de ' . self::MAX_DOCS . ' PDF por descarga. Divide la exportación por nivel o profesores.');

        // En la modalidad consolidada cada docente tiene como máximo dos PDF,
        // sin separar por nivel; se ordenan las cargas para que sea fácil ubicarlas.
        $cargas = $cargas->sortBy(fn (array $c) => sprintf('%08d_%s_%s_%s_%08d_%08d',
            $c['profesor_id'], $c['nivel_nombre'], $c['grado_nombre'],
            $c['grupo_nombre'], $c['carga_id'], $c['grupo_id']
        ))->values();

        $archivoTemporal = tempnam(sys_get_temp_dir(), 'listas_docentes_');
        abort_unless($archivoTemporal !== false, 500, 'No se pudo preparar el archivo temporal.');
        $zip = new ZipArchive();
        if ($zip->open($archivoTemporal, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($archivoTemporal);
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        $incidencias = [];
        $totalPdf = 0;
        $totalPaginas = 0;
        $totalListas = 0;
        $profesoresIncluidos = [];
        $temporalesFinales = [];
        $errores = [];

        try {
            // Cada profesor se procesa de forma independiente. Esto evita
            // combinar listas de docentes distintos en un mismo PDF.
            foreach ($cargas->groupBy('profesor_id') as $profesorId => $cargasDocente) {
                $profesor = $docentes->get((int) $profesorId);
                if (! $profesor) {
                    continue;
                }
                $nombre = trim(implode(' ', array_filter([
                    $profesor->titulo, $profesor->nombre,
                    $profesor->apellido_paterno, $profesor->apellido_materno,
                ])));
                $carpetaConsolidada = implode('/', [
                    'Ciclo_' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio,
                    'Profesores',
                    $this->nombreSeguro($nombre) . '_P' . (int) $profesor->id,
                ]);

                foreach (['asistencia' => $asistencia, 'evaluacion' => $evaluacion] as $tipo => $habilitado) {
                    if (! $habilitado) {
                        continue;
                    }

                    $concatenador = $formato === 'consolidado' ? new ConcatenadorPdfDocente() : null;
                    $paginasDelTipo = 0;
                    $listasDelTipo = 0;
                    try {
                        foreach ($cargasDocente as $carga) {
                            // Los talleres y las materias no calificables no
                            // producen listas de evaluación.
                            if ($tipo === 'evaluacion' && ! $carga['calificable']) {
                                continue;
                            }

                            $identificacion = $nombre . ' · ' . $carga['nivel_nombre'] . ' · '
                                . $carga['nombre'] . ' · ' . $carga['grado_nombre']
                                . ' · ' . $carga['grupo_nombre'];
                            $periodoId = $this->periodoParaCarga(
                                $carga, (int) $ciclo->id, $datos['fecha_corte'],
                                $periodosManuales[$carga['clave']] ?? null
                            );
                            if (! $periodoId) {
                                // La incidencia se anota una sola vez por carga,
                                // no se duplica entre asistencia y evaluación.
                                if ($tipo === 'asistencia' || ! $asistencia) {
                                    $incidencias[] = 'SIN PERIODO: ' . $identificacion
                                        . ' (elige el periodo/parcial o configura sus fechas).';
                                }
                                continue;
                            }

                            try {
                                $contenido = $pdf->renderizarCargaParaZip(
                                    $tipo, $profesor, $ciclo, $carga['tipo'], $carga['carga_id'],
                                    $carga['grupo_id'], $periodoId, $datos['fecha_corte'],
                                    $datos['fecha_inicio_asistencia'], $listas
                                );
                                if ($formato === 'consolidado') {
                                    $paginasDelTipo += $concatenador->agregar($contenido);
                                    $listasDelTipo++;
                                } else {
                                    $carpeta = implode('/', [
                                        'Ciclo_' . $ciclo->inicio_anio . '-' . $ciclo->fin_anio,
                                        $this->nombreSeguro($carga['nivel_nombre']),
                                        $this->nombreSeguro($nombre) . '_P' . (int) $profesor->id,
                                        $this->nombreSeguro($carga['grado_nombre'] . '_Grupo_' . $carga['grupo_nombre']),
                                        $this->nombreSeguro($carga['nombre']) . '_C' . $carga['carga_id']
                                            . ($carga['tipo'] === 'taller' ? '_G' . $carga['grupo_id'] : ''),
                                    ]);
                                    if (! $zip->addFromString(
                                        $carpeta . '/' . ucfirst($tipo) . '_Periodo_' . $periodoId . '.pdf',
                                        $contenido
                                    )) {
                                        throw new \RuntimeException('No se pudo incluir el PDF individual en el ZIP.');
                                    }
                                    $totalPdf++;
                                    $totalListas++;
                                    $profesoresIncluidos[$profesor->id] = true;
                                }
                                unset($contenido);
                            } catch (Throwable $error) {
                                report($error);
                                $incidencias[] = 'ERROR AL GENERAR ' . strtoupper($tipo) . ': ' . $identificacion
                                    . ' (revisa la plantilla y los datos del periodo).';
                            }
                        }

                        if ($concatenador && $concatenador->documentos() > 0) {
                            $rutaConsolidada = tempnam(sys_get_temp_dir(), 'listas_unificadas_');
                            if ($rutaConsolidada === false) {
                                throw new \RuntimeException('No se pudo crear el PDF consolidado temporal.');
                            }
                            $temporalesFinales[] = $rutaConsolidada;
                            $concatenador->guardar($rutaConsolidada);
                            $nombreTipo = $tipo === 'asistencia' ? 'Asistencias' : 'Evaluaciones';
                            $nombrePdf = $nombreTipo . '_Consolidadas_'
                                . $this->nombreSeguro($nombre) . '_P' . (int) $profesor->id . '.pdf';
                            if (! $zip->addFile($rutaConsolidada, $carpetaConsolidada . '/' . $nombrePdf)) {
                                throw new \RuntimeException('No se pudo agregar el PDF consolidado al ZIP.');
                            }
                            $totalPdf++;
                            $totalListas += $listasDelTipo;
                            $totalPaginas += $paginasDelTipo;
                            $profesoresIncluidos[$profesor->id] = true;
                        }
                    } catch (Throwable $error) {
                        report($error);
                        $incidencias[] = 'ERROR AL CONSOLIDAR ' . strtoupper($tipo) . ': ' . $nombre
                            . ' (no se pudo completar este PDF).';
                    } finally {
                        $concatenador?->limpiar();
                        unset($concatenador);
                    }
                }
            }

            $cicloTexto = $ciclo->inicio_anio . '-' . $ciclo->fin_anio;
            $estructura = $formato === 'consolidado'
                ? 'Ciclo / Profesores / Docente / Asistencias_Consolidadas.pdf y Evaluaciones_Consolidadas.pdf'
                : 'Ciclo / Nivel / Profesor / Grado-Grupo / Materia / PDF';
            $resumen = "CENTRO UNIVERSITARIO MOCTEZUMA - LISTAS DOCENTES PRO 2.1\r\n"
                . "Ciclo escolar: {$cicloTexto}\r\n"
                . "Fecha de corte: {$datos['fecha_corte']}\r\n"
                . "Inicio de asistencia: {$datos['fecha_inicio_asistencia']}\r\n"
                . "Formato: {$formato}\r\n"
                . 'Generado: ' . now()->format('d/m/Y H:i:s') . "\r\n"
                . "Documentos PDF incluidos: {$totalPdf}\r\n"
                . "Listas académicas incluidas: {$totalListas}\r\n"
                . "Páginas consolidadas: {$totalPaginas}\r\n"
                . 'Profesores incluidos: ' . count($profesoresIncluidos) . "\r\n"
                . 'Incidencias: ' . count($incidencias) . "\r\n\r\n"
                . "Organización: {$estructura}\r\n"
                . "Las listas se calculan según la matrícula histórica a la fecha de corte.\r\n"
                . "Los periodos se validan por carga; no se inventan periodos faltantes.\r\n"
                . "En consolidado, las materias se mezclan entre niveles SOLO dentro de cada docente.\r\n";
            $zip->addFromString('RESUMEN_EXPORTACION.txt', $resumen);
            if ($incidencias !== []) {
                $zip->addFromString('INCIDENCIAS.txt', implode("\r\n", $incidencias) . "\r\n");
            }
        } catch (Throwable $error) {
            $errores[] = $error;
        } finally {
            if (! $zip->close()) {
                $errores[] = new \RuntimeException('No se pudo cerrar correctamente el archivo ZIP.');
            }
            // ZipArchive::addFile lee los originales al cerrar el ZIP.
            foreach ($temporalesFinales as $archivo) {
                @unlink($archivo);
            }
        }
        if ($errores !== []) {
            @unlink($archivoTemporal);
            throw $errores[0];
        }

        if ($totalPdf === 0) {
            @unlink($archivoTemporal);
            throw ValidationException::withMessages([
                'periodos' => 'No se generaron listas. Verifica que las cargas tengan periodos válidos en la fecha seleccionada.',
            ]);
        }

        Log::info('Exportación masiva de listas docentes', [
            'usuario_id' => $request->user()->id,
            'ciclo_escolar_id' => $ciclo->id,
            'alcance' => $alcance,
            'profesores' => count($profesoresIncluidos),
            'pdf' => $totalPdf,
            'listas' => $totalListas,
            'paginas' => $totalPaginas,
            'formato' => $formato,
            'incidencias' => count($incidencias),
        ]);

        $nombreZip = ($formato === 'consolidado' ? 'Listas_Docentes_Consolidadas_' : 'Listas_Docentes_') . $ciclo->inicio_anio . '-' . $ciclo->fin_anio
            . '_' . now()->format('Ymd_His') . '.zip';

        return response()->download($archivoTemporal, $nombreZip, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function docentes(string $alcance, int $cicloId, array $datos, ?int $nivelId)
    {
        $ids = match ($alcance) {
            'actual' => [(int) ($datos['profesor_id'] ?? 0)],
            'seleccion' => array_map('intval', (array) ($datos['profesores'] ?? [])),
            default => array_values(array_unique(array_merge(
                AsignacionMateria::query()
                    ->where('ciclo_escolar_id', $cicloId)
                    ->whereIn('estado', [AsignacionMateria::ESTADO_ACTIVA, AsignacionMateria::ESTADO_CERRADA])
                    ->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))
                    ->whereHas('materia', fn ($q) => $q->where(fn ($s) => $s->whereNull('receso')->orWhere('receso', false)))
                    ->pluck('profesor_id')->all(),
                TallerSesion::query()
                    ->where('ciclo_escolar_id', $cicloId)
                    ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
                    ->when($nivelId, fn ($q) => $q->whereHas('grupos', fn ($g) => $g->where('nivel_id', $nivelId)))
                    ->pluck('profesor_id')->all(),
            ))),
        };
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return collect();
        }

        return Persona::query()->whereIn('id', $ids)
            ->get(['id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->keyBy('id');
    }

    private function cargasDeDocente(int $profesorId, int $cicloId, ?int $nivelId)
    {
        $materias = AsignacionMateria::query()
            ->with(['materia:id,materia,clave,calificable,receso', 'nivel:id,nombre',
                'grado:id,nombre', 'grupo:id,asignacion_grupo_id', 'grupo.asignacionGrupo:id,nombre'])
            ->where('profesor_id', $profesorId)->where('ciclo_escolar_id', $cicloId)
            ->whereIn('estado', [AsignacionMateria::ESTADO_ACTIVA, AsignacionMateria::ESTADO_CERRADA])
            ->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))
            ->whereHas('materia', fn ($q) => $q->where(fn ($s) => $s->whereNull('receso')->orWhere('receso', false)))
            ->get()->map(fn ($carga) => [
                'clave' => 'm_' . $carga->id,
                'tipo' => 'materia',
                'profesor_id' => $profesorId,
                'carga_id' => (int) $carga->id,
                'grupo_id' => (int) $carga->grupo_id,
                'nombre' => $carga->materia?->materia ?? 'Materia',
                'calificable' => (bool) $carga->materia?->calificable,
                'nivel_id' => (int) $carga->nivel_id,
                'nivel_nombre' => $carga->nivel?->nombre ?? 'Nivel',
                'grado_nombre' => $carga->grado?->nombre ?? 'Grado',
                'grupo_nombre' => $carga->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo',
                'generacion_id' => $carga->generacion_id,
                'semestre_id' => $carga->semestre_id,
            ]);

        $talleres = TallerSesion::query()
            ->with(['taller:id,nombre,clave', 'grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupos.nivel:id,nombre', 'grupos.grado:id,nombre', 'grupos.asignacionGrupo:id,nombre'])
            ->where('profesor_id', $profesorId)->where('ciclo_escolar_id', $cicloId)
            ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
            ->get()->flatMap(fn ($sesion) => $sesion->grupos
                ->filter(fn ($grupo) => ! $nivelId || (int) $grupo->nivel_id === $nivelId)
                ->map(fn ($grupo) => [
                    'clave' => 't_' . $sesion->id . '_' . $grupo->id,
                    'tipo' => 'taller',
                    'profesor_id' => $profesorId,
                    'carga_id' => (int) $sesion->id,
                    'grupo_id' => (int) $grupo->id,
                    'nombre' => $sesion->taller?->nombre ?? 'Taller',
                    'calificable' => false,
                    'nivel_id' => (int) $grupo->nivel_id,
                    'nivel_nombre' => $grupo->nivel?->nombre ?? 'Nivel',
                    'grado_nombre' => $grupo->grado?->nombre ?? 'Grado',
                    'grupo_nombre' => $grupo->asignacionGrupo?->nombre ?? 'Sin grupo',
                    'generacion_id' => $grupo->generacion_id,
                    'semestre_id' => $grupo->semestre_id,
                ]));

        return $materias->concat($talleres);
    }

    private function periodoParaCarga(array $carga, int $cicloId, string $fechaCorte, mixed $manual): ?int
    {
        $query = DB::table('periodos')->where('ciclo_escolar_id', $cicloId)
            ->where('nivel_id', $carga['nivel_id']);
        if ($carga['nivel_id'] === 4) {
            $query->whereNotNull('parcial_bachillerato_id')
                ->when($carga['generacion_id'], fn ($q) => $q->where('generacion_id', $carga['generacion_id']))
                ->when($carga['semestre_id'], fn ($q) => $q->where('semestre_id', $carga['semestre_id']));
        } else {
            $query->whereNotNull('periodo_basica_id');
        }
        $candidatos = $query->get(['id', 'fecha_inicio', 'fecha_fin']);
        if ($manual && $candidatos->contains('id', (int) $manual)) {
            return (int) $manual;
        }
        // Si hay varios periodos, solo elegir automáticamente uno inequívoco.
        $coincidentes = $candidatos->filter(fn ($p) => $p->fecha_inicio && $p->fecha_fin
            && $p->fecha_inicio <= $fechaCorte && $p->fecha_fin >= $fechaCorte);
        if ($coincidentes->count() === 1) {
            return (int) $coincidentes->first()->id;
        }
        return $candidatos->count() === 1 ? (int) $candidatos->first()->id : null;
    }

    private function nombreSeguro(string $nombre): string
    {
        $nombre = Str::ascii($nombre);
        $nombre = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nombre) ?? 'Sin_nombre';
        $nombre = trim($nombre, '_-.');
        return substr($nombre ?: 'Sin_nombre', 0, 70);
    }
}
