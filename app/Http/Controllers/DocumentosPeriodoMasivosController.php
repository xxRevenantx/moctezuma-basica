<?php

namespace App\Http\Controllers;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Services\Documentos\BladeWordExporter;
use App\Services\ListaAcademicaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exporta el periodo vigente en un solo PDF o Word, sin cambiar la regla de
 * promedios/lugares: los datos provienen del PDFController individual.
 */
class DocumentosPeriodoMasivosController extends Controller
{
    public function __invoke(Request $request, string $tipo, string $formato, PDFController $individual): Response
    {
        abort_unless(in_array($tipo, ['boletas', 'reconocimientos'], true), 404);
        abort_unless(in_array($formato, ['pdf', 'word'], true), 404);
        abort_unless($request->user()?->canAccess('calificaciones.consultar'), 403);
        abort_if($request->user()?->isProfessor(), 403);

        $parametros = $request->validate([
            'slug_nivel' => ['required', 'string', 'max:60'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
        ]);

        $nivel = Nivel::query()->where('slug', $parametros['slug_nivel'])->firstOrFail();
        $generacion = Generacion::query()
            ->whereKey($parametros['generacion_id'])
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();
        $grado = Grado::query()
            ->whereKey($parametros['grado_id'])
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();
        $esBachillerato = $nivel->slug === 'bachillerato' || (int) $nivel->id === 4;
        $semestreId = (int) ($parametros['semestre_id'] ?? 0);
        abort_if($esBachillerato && $semestreId < 1, 422, 'Selecciona un semestre antes de exportar.');

        $grupo = Grupo::withTrashed()
            ->whereKey($parametros['grupo_id'])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('grado_id', $grado->id)
            ->where('ciclo_escolar_id', $parametros['ciclo_escolar_id'])
            ->when($esBachillerato,
                fn ($query) => $query->where('semestre_id', $semestreId),
                fn ($query) => $query->whereNull('semestre_id'))
            ->firstOrFail();

        $periodo = Periodos::query()
            ->with('cicloEscolar')
            ->whereKey($parametros['periodo_id'])
            ->where('nivel_id', $nivel->id)
            ->where('ciclo_escolar_id', $parametros['ciclo_escolar_id'])
            ->when($esBachillerato,
                fn ($query) => $query->where('semestre_id', $semestreId)->whereNotNull('parcial_bachillerato_id'),
                fn ($query) => $query->whereNotNull('periodo_basica_id'))
            ->firstOrFail();

        $alumnos = app(ListaAcademicaService::class)->alumnosPorContexto(
            cicloEscolarId: (int) $periodo->ciclo_escolar_id,
            grupoIds: [(int) $grupo->id],
            fechaCorte: $periodo->fecha_fin ?: $periodo->fecha_inicio,
            nivelId: (int) $nivel->id,
            gradoId: (int) $grado->id,
            generacionId: (int) $generacion->id,
            semestreId: $esBachillerato ? $semestreId : null,
            usarHistorialCiclo: true,
            incluirNoActivos: false,
            fechaInicio: $periodo->fecha_inicio,
            fechaFin: $periodo->fecha_fin,
            periodoId: (int) $periodo->id,
            usarActualComoRespaldo: (bool) $periodo->cicloEscolar?->es_actual
                && blank($periodo->cicloEscolar?->cerrado_at),
            incluirTodaGeneracionBachillerato: $esBachillerato,
        )->unique('id')->sortBy(function ($alumno) {
            return mb_strtolower(trim(($alumno->apellido_paterno ?? '') . ' '
                . ($alumno->apellido_materno ?? '') . ' ' . ($alumno->nombre ?? '')));
        })->values();

        abort_if($alumnos->isEmpty(), 422, 'No hay alumnos activos en el grupo y periodo seleccionados.');
        abort_if($alumnos->count() > 150, 422,
            'El lote supera 150 alumnos. Divide la exportación por grupos para evitar saturar el servidor.');

        $documentos = [];
        foreach ($alumnos as $alumno) {
            $solicitud = Request::create('/', 'GET', [
                'slug_nivel' => $nivel->slug,
                'ciclo_escolar_id' => (int) $periodo->ciclo_escolar_id,
                'generacion_id' => (int) $generacion->id,
                'grado_id' => (int) $grado->id,
                'grupo_id' => (int) $grupo->id,
                'periodo_id' => (int) $periodo->id,
                'inscripcion_id' => (int) $alumno->id,
                'semestre_id' => $esBachillerato ? $semestreId : null,
            ]);
            $solicitud->setUserResolver(fn () => $request->user());
            // No es un parámetro de entrada. Solo la llamada interna usa este atributo.
            $solicitud->attributes->set('documentos_periodo_masivos_datos', true);

            $documento = $tipo === 'boletas'
                ? $individual->boleta_calificaciones_pdf($solicitud)
                : $individual->reconocimiento_calificaciones_pdf($solicitud);

            // La regla existente del reconocimiento solo asigna 1.º, 2.º y 3.º.
            // Los empates comparten lugar; alumnos sin lugar NO reciben reconocimiento.
            if ($tipo === 'reconocimientos'
                && ! in_array((int) ($documento['datos']['lugarAlumno'] ?? 0), [1, 2, 3], true)) {
                continue;
            }

            $documentos[] = [
                'vista' => $documento['vista'],
                'datos' => $documento['datos'],
                'orientacion' => $documento['orientacion'],
                'lugar' => (int) ($documento['datos']['lugarAlumno'] ?? 0),
                'promedio' => (float) ($documento['datos']['promedioNumero'] ?? 0),
                'alumno' => mb_strtolower(trim(($alumno->apellido_paterno ?? '') . ' '
                    . ($alumno->apellido_materno ?? '') . ' ' . ($alumno->nombre ?? ''))),
            ];
        }

        abort_if($documentos === [], 422, $tipo === 'reconocimientos'
            ? 'No hay alumnos con primer, segundo o tercer lugar calculado para este periodo.'
            : 'No hay boletas disponibles para este contexto académico.');

        if ($tipo === 'reconocimientos') {
            usort($documentos, fn (array $a, array $b) => ($a['lugar'] <=> $b['lugar'])
                ?: ($b['promedio'] <=> $a['promedio']) ?: strcmp($a['alumno'], $b['alumno']));
        }

        $nombre = strtoupper($tipo) . '_' . strtoupper($nivel->slug) . '_GRUPO_' . (int) $grupo->id
            . '_PERIODO_' . (int) $periodo->id . '_' . count($documentos) . '_ALUMNOS';
        $nombre = preg_replace('/[^A-Z0-9_-]/', '_', $nombre) ?: 'DOCUMENTOS_ACADEMICOS';

        if ($formato === 'word') {
            return app(BladeWordExporter::class)->descargarLote(
                $documentos,
                $nombre . '.docx',
                $tipo === 'reconocimientos' ? 'landscape' : 'portrait'
            );
        }

        return $this->unirPdf($documentos, $nombre . '.pdf');
    }

    /** @param list<array{vista:string, datos:array, orientacion:string}> $documentos */
    private function unirPdf(array $documentos, string $nombre): Response
    {
        $directorio = storage_path('app/temp/documentos-periodo');
        File::ensureDirectoryExists($directorio, 0775, true);
        $rutasTemporales = [];

        try {
            $unido = new Fpdi();
            $unido->SetCreator('Moctezuma Básica', true);
            $unido->SetAuthor('Centro Universitario Moctezuma', true);
            $unido->SetTitle('Documentos académicos por periodo', true);
            $unido->SetAutoPageBreak(false);

            foreach ($documentos as $documento) {
                $contenido = Pdf::loadView($documento['vista'], $documento['datos'])
                    ->setPaper('letter', $documento['orientacion'])
                    ->output();

                $archivo = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.pdf';
                $rutasTemporales[] = $archivo;
                if (file_put_contents($archivo, $contenido) !== strlen($contenido)) {
                    throw new \RuntimeException('No fue posible preparar una página del reporte.');
                }
                $paginas = $unido->setSourceFile($archivo);
                for ($numero = 1; $numero <= $paginas; $numero++) {
                    $pagina = $unido->importPage($numero);
                    $tamano = $unido->getTemplateSize($pagina);
                    $unido->AddPage($tamano['orientation'], [$tamano['width'], $tamano['height']]);
                    $unido->useTemplate($pagina, 0, 0, $tamano['width'], $tamano['height']);
                }
            }

            $pdf = $unido->Output('S');
        } finally {
            foreach ($rutasTemporales as $ruta) {
                @unlink($ruta);
            }
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
