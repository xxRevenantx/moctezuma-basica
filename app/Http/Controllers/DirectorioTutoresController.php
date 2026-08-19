<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Escuela;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\DirectorioTutoresService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DirectorioTutoresController extends Controller
{
    public function __invoke(Request $request, string $formato, DirectorioTutoresService $servicio)
    {
        abort_unless(auth()->check() && (bool) auth()->user()->is_admin, 403);
        abort_unless(in_array($formato, ['pdf', 'word', 'zip-pdf', 'zip-word'], true), 404);

        $datos = $this->prepararDatos($request, $servicio);

        if (str_starts_with($formato, 'zip-')) {
            abort_unless($datos['vista'] === 'alumnos', 422, 'La descarga por grupos está disponible únicamente en la vista por alumnos.');
        }

        return match ($formato) {
            'pdf' => $this->descargarPdf($datos),
            'word' => $this->descargarWord($datos),
            'zip-pdf' => $this->descargarZip($datos, 'pdf'),
            'zip-word' => $this->descargarZip($datos, 'word'),
        };
    }

    private function prepararDatos(Request $request, DirectorioTutoresService $servicio): array
    {
        $validados = $request->validate([
            'nivel_id' => ['nullable', 'integer', 'exists:niveles,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'ciclo_escolar_id' => ['nullable', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'estado_alumno' => ['nullable', 'string', 'in:activos,egresados,no_reinscritos,todos'],
            'modo_responsables' => ['nullable', 'string', 'in:principal,todos'],
            'parentesco' => ['nullable', 'string', 'max:50'],
            'buscar' => ['nullable', 'string', 'max:120'],
            'orden' => ['nullable', 'string', 'in:academico_alumno,alumno,tutor'],
            'vista' => ['nullable', 'string', 'in:familias,alumnos'],
            'pestana' => ['nullable', 'string', 'in:todos,multinivel,duplicados,incompletos'],
            'tipo_familia' => ['nullable', 'string', 'in:todas,uno,varios,multinivel'],
            'filtro_rapido' => ['nullable', 'string', 'in:sin_tutor,sin_telefono,sin_domicilio,sin_curp,varios_hijos,multinivel,duplicados'],
            'salto_grupo' => ['nullable', 'boolean'],
        ]);

        $filtros = $servicio->normalizarFiltros($validados);
        $nivel = $filtros['nivel_id']
            ? Nivel::query()->with('director')->findOrFail($filtros['nivel_id'])
            : null;

        $this->validarDependenciasAcademicas($filtros, $nivel);

        $resultado = $servicio->directorio($filtros);
        $vista = $filtros['vista'];
        $filas = $vista === 'familias' ? $resultado['familias'] : $resultado['filas'];

        abort_if($filas->isEmpty(), 404, 'No hay registros que coincidan con los filtros seleccionados.');

        $secciones = $vista === 'familias'
            ? $servicio->seccionesFamilias($filas)
            : $servicio->secciones($filas);
        $secciones = $this->numerarSecciones($secciones);

        $escuela = Escuela::query()->first();
        $nivelNombre = $nivel?->nombre ?? 'Todos los niveles';
        $cct = $nivel?->cct ?: ($nivel ? 'SIN C.C.T.' : 'DIRECTORIO INSTITUCIONAL');
        $cargoDirector = $nivel ? 'Dirección del nivel' : 'Dirección General';

        return [
            'titulo' => 'Directorio de padres y tutores',
            'subtitulo' => $vista === 'familias' ? 'Directorio consolidado de familias' : 'Directorio por alumno',
            'vista' => $vista,
            'nivel' => $nivel,
            'nivel_nombre' => $nivelNombre,
            'cct' => $cct,
            'escuela' => $escuela,
            'direccion_escuela' => $this->direccionEscuela($escuela),
            'logo_institucional' => $this->logoInstitucional(),
            'logo_nivel' => $nivel ? $this->logoNivel($nivel) : $this->logoInstitucional(),
            'director' => $nivel ? $this->nombreDirector($nivel) : 'Dirección General',
            'cargo_director' => $cargoDirector,
            'elaborado_por' => auth()->user()->name ?: 'Responsable de control escolar',
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'resumen_filtros' => $this->resumenFiltros($filtros, $nivel),
            'filas' => $filas,
            'secciones' => $secciones,
            'metricas' => $resultado['metricas'],
            'duplicados' => $resultado['duplicados'],
            'salto_grupo' => $vista === 'alumnos' && (bool) $filtros['salto_grupo'],
            'nombre_archivo' => implode('-', [
                'directorio-padres-tutores',
                $nivel ? Str::slug($nivel->nombre) : 'todos-los-niveles',
                $vista,
                now()->format('Y-m-d'),
            ]),
        ];
    }

    private function descargarPdf(array $datos)
    {
        return Pdf::loadView('pdf.directorio-tutores', $datos)
            ->setPaper('letter', 'landscape')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->stream($datos['nombre_archivo'] . '.pdf');
    }

    private function descargarWord(array $datos): BinaryFileResponse
    {
        $directorio = storage_path('app/temp/directorio-tutores');
        File::ensureDirectoryExists($directorio, 0775, true);

        $ruta = $directorio . DIRECTORY_SEPARATOR . $datos['nombre_archivo'] . '-' . Str::uuid() . '.docx';
        $this->guardarWord($datos, $ruta);

        return response()
            ->download($ruta, $datos['nombre_archivo'] . '.docx')
            ->deleteFileAfterSend(true);
    }

    private function descargarZip(array $datos, string $tipo): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'La extensión ZIP de PHP no está habilitada.');

        $directorio = storage_path('app/temp/directorio-tutores');
        File::ensureDirectoryExists($directorio, 0775, true);

        $rutaZip = $directorio . DIRECTORY_SEPARATOR . $datos['nombre_archivo'] . '-' . $tipo . '-' . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'No fue posible crear el ZIP.');

        $temporales = [];
        $servicio = app(DirectorioTutoresService::class);

        foreach ($datos['secciones'] as $indice => $seccion) {
            $seccion = $this->renumerarSeccion($seccion);
            $datosGrupo = $datos;
            $datosGrupo['secciones'] = collect([$seccion]);
            $datosGrupo['filas'] = $seccion['filas'];
            $datosGrupo['metricas'] = $servicio->metricas($seccion['filas']);
            $datosGrupo['salto_grupo'] = false;

            $nombreBase = sprintf(
                '%02d-%s',
                $indice + 1,
                Str::slug($seccion['titulo']) ?: 'grupo-' . ($indice + 1)
            );

            if ($tipo === 'pdf') {
                $contenido = Pdf::loadView('pdf.directorio-tutores', $datosGrupo)
                    ->setPaper('letter', 'landscape')
                    ->setOption('isRemoteEnabled', false)
                    ->setOption('isHtml5ParserEnabled', true)
                    ->output();
                $zip->addFromString($nombreBase . '.pdf', $contenido);
                continue;
            }

            $rutaWord = $directorio . DIRECTORY_SEPARATOR . $nombreBase . '-' . Str::uuid() . '.docx';
            $this->guardarWord($datosGrupo, $rutaWord);
            $zip->addFile($rutaWord, $nombreBase . '.docx');
            $temporales[] = $rutaWord;
        }

        $zip->close();
        File::delete($temporales);

        $nombre = $datos['nombre_archivo'] . '-por-grupos-' . $tipo . '.zip';

        return response()->download($rutaZip, $nombre)->deleteFileAfterSend(true);
    }

    private function guardarWord(array $datos, string $ruta): void
    {
        $directorioPhpWord = storage_path('app/temp/phpword');
        File::ensureDirectoryExists($directorioPhpWord, 0775, true);
        Settings::setTempDir($directorioPhpWord);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(7);

        $info = $phpWord->getDocInfo();
        $info->setCreator('Centro Universitario Moctezuma');
        $info->setCompany('Centro Universitario Moctezuma');
        $info->setTitle($datos['titulo']);
        $info->setSubject('Directorio institucional de padres y tutores');

        $phpWord->addTableStyle('EncabezadoDirectorio', [
            'borderSize' => 0,
            'cellMargin' => 30,
            'alignment' => 'center',
        ]);
        $phpWord->addTableStyle('ResumenDirectorio', [
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 45,
            'alignment' => 'center',
        ]);
        $phpWord->addTableStyle('TablaDirectorio', [
            'borderSize' => 4,
            'borderColor' => '94A3B8',
            'cellMargin' => 30,
            'alignment' => 'center',
        ]);
        $phpWord->addTableStyle('FirmasDirectorio', [
            'borderSize' => 0,
            'cellMargin' => 25,
            'alignment' => 'center',
        ]);

        $sectionStyle = [
            'paperSize' => 'Letter',
            'orientation' => 'landscape',
            'marginTop' => 360,
            'marginBottom' => 360,
            'marginLeft' => 380,
            'marginRight' => 380,
        ];

        $secciones = collect($datos['secciones'])->values();
        $sectionActual = null;

        if ($datos['salto_grupo'] && $datos['vista'] === 'alumnos') {
            foreach ($secciones as $indice => $seccion) {
                $sectionActual = $phpWord->addSection($sectionStyle);
                $this->agregarEncabezadoWord($sectionActual, $datos);
                if ($indice === 0) {
                    $this->agregarResumenWord($sectionActual, $datos['metricas']);
                }
                $this->agregarTablaWord($sectionActual, $seccion, $datos['vista']);
                $this->agregarPieWord($sectionActual);
            }
        } else {
            $sectionActual = $phpWord->addSection($sectionStyle);
            $this->agregarEncabezadoWord($sectionActual, $datos);
            $this->agregarResumenWord($sectionActual, $datos['metricas']);

            foreach ($secciones as $indice => $seccion) {
                if ($indice > 0) {
                    $sectionActual->addTextBreak(1);
                }
                $this->agregarTablaWord($sectionActual, $seccion, $datos['vista']);
            }

            $this->agregarPieWord($sectionActual);
        }

        if ($sectionActual) {
            $this->agregarFirmasWord($sectionActual, $datos);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
    }

    private function agregarEncabezadoWord($section, array $datos): void
    {
        $tabla = $section->addTable('EncabezadoDirectorio');
        $tabla->addRow();

        $izquierda = $tabla->addCell(1700, ['valign' => 'center']);
        if ($datos['logo_institucional'] && is_file($datos['logo_institucional'])) {
            $izquierda->addImage($datos['logo_institucional'], [
                'width' => 95,
                'height' => 45,
                'alignment' => 'center',
            ]);
        }

        $centro = $tabla->addCell(9000, ['valign' => 'center']);
        $centro->addText(
            $datos['escuela']?->nombre ?: 'Centro Universitario Moctezuma',
            ['bold' => true, 'size' => 13, 'color' => '006492'],
            ['alignment' => 'center', 'spaceAfter' => 0]
        );
        $centro->addText($datos['titulo'], ['bold' => true, 'size' => 10], ['alignment' => 'center', 'spaceAfter' => 0]);
        $centro->addText($datos['subtitulo'], ['bold' => true, 'size' => 7.5, 'color' => '475569'], ['alignment' => 'center', 'spaceAfter' => 0]);
        $centro->addText(
            $datos['nivel_nombre'] . ' · ' . ($datos['nivel'] ? 'C.C.T. ' : '') . $datos['cct'],
            ['bold' => true, 'size' => 7.5, 'color' => '88AC2E'],
            ['alignment' => 'center', 'spaceAfter' => 0]
        );
        if ($datos['direccion_escuela'] !== '') {
            $centro->addText($datos['direccion_escuela'], ['size' => 6.5, 'color' => '64748B'], ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $centro->addText('Generado: ' . $datos['fecha_emision'], ['size' => 6.5, 'color' => '64748B'], ['alignment' => 'center']);

        $derecha = $tabla->addCell(1700, ['valign' => 'center']);
        if ($datos['logo_nivel'] && is_file($datos['logo_nivel'])) {
            $derecha->addImage($datos['logo_nivel'], [
                'width' => 72,
                'height' => 48,
                'alignment' => 'center',
            ]);
        }
    }

    private function agregarResumenWord($section, array $metricas): void
    {
        $tabla = $section->addTable('ResumenDirectorio');
        $tabla->addRow();

        foreach ([
            'Alumnos' => $metricas['alumnos'] ?? 0,
            'Familias' => $metricas['familias'] ?? 0,
            'Responsables' => $metricas['responsables'] ?? 0,
            'Varios hijos' => $metricas['varios_hijos'] ?? 0,
            'Multinivel' => $metricas['multinivel'] ?? 0,
            'Posibles duplicados' => $metricas['duplicados'] ?? 0,
        ] as $etiqueta => $valor) {
            $celda = $tabla->addCell(2000, ['bgColor' => 'F8FAFC', 'valign' => 'center']);
            $celda->addText($etiqueta, ['bold' => true, 'size' => 6.1, 'color' => '64748B'], ['alignment' => 'center', 'spaceAfter' => 0]);
            $celda->addText((string) $valor, ['bold' => true, 'size' => 8.5, 'color' => '0F172A'], ['alignment' => 'center']);
        }

        $section->addTextBreak(1);
    }

    private function agregarTablaWord($section, array $seccion, string $vista): void
    {
        $section->addText(
            $seccion['titulo'],
            ['bold' => true, 'size' => 8.5, 'color' => '006492'],
            ['alignment' => 'left', 'spaceAfter' => 70]
        );
        $section->addText(
            'Generación: ' . $seccion['generacion'] . ' · Ciclo escolar: ' . ($seccion['ciclo_escolar'] ?: 'Según filtros'),
            ['size' => 6.5, 'color' => '64748B'],
            ['alignment' => 'left', 'spaceAfter' => 70]
        );

        $tabla = $section->addTable('TablaDirectorio');
        $tabla->addRow(360, ['tblHeader' => true, 'cantSplit' => true]);

        if ($vista === 'familias') {
            $columnas = [
                ['label' => 'N.º', 'width' => 420],
                ['label' => 'Padre o tutor', 'width' => 1750],
                ['label' => 'Parentesco', 'width' => 900],
                ['label' => 'Teléfono', 'width' => 1250],
                ['label' => 'INE', 'width' => 1050],
                ['label' => 'Domicilio', 'width' => 2250],
                ['label' => 'Alumnos relacionados', 'width' => 3000],
                ['label' => 'Niveles', 'width' => 1250],
            ];
        } else {
            $columnas = [
                ['label' => 'N.º', 'width' => 420],
                ['label' => 'Padre o tutor', 'width' => 1600],
                ['label' => 'Parentesco', 'width' => 820],
                ['label' => 'Teléfono', 'width' => 1180],
                ['label' => 'INE', 'width' => 1000],
                ['label' => 'Domicilio', 'width' => 2200],
                ['label' => 'Alumno', 'width' => 1650],
                ['label' => 'Nivel', 'width' => 850],
                ['label' => 'Grado / semestre', 'width' => 1000],
                ['label' => 'Grupo', 'width' => 600],
            ];
        }

        foreach ($columnas as $columna) {
            $celda = $tabla->addCell($columna['width'], ['bgColor' => '006492', 'valign' => 'center']);
            $celda->addText($columna['label'], ['bold' => true, 'size' => 6.2, 'color' => 'FFFFFF'], ['alignment' => 'center', 'spaceAfter' => 0]);
        }

        foreach ($seccion['filas'] as $fila) {
            $tabla->addRow(430, ['cantSplit' => true]);

            if ($vista === 'familias') {
                $alumnos = collect($fila['alumnos'])->map(function (array $alumno): string {
                    $contexto = collect([
                        $alumno['nivel'] ?? null,
                        $alumno['grado'] ?? null,
                        $alumno['semestre'] ?? null,
                        ! empty($alumno['grupo']) ? 'Grupo ' . $alumno['grupo'] : null,
                    ])->filter()->join(' · ');

                    return ($alumno['nombre'] ?? '') . ($contexto !== '' ? ' (' . $contexto . ')' : '');
                })->join('; ');

                $niveles = $fila['niveles_texto'] ?: '';
                if ($fila['multinivel']) {
                    $niveles .= ($niveles !== '' ? ' · ' : '') . 'MULTINIVEL';
                }

                $valores = [
                    [(string) $fila['numero'], 'center'],
                    [$fila['responsable'], 'left'],
                    [$fila['parentesco'], 'center'],
                    [$fila['telefono'], 'left'],
                    [$fila['ine'] ?? '', 'center'],
                    [$fila['domicilio'], 'left'],
                    [$alumnos, 'left'],
                    [$niveles, 'center'],
                ];
            } else {
                $gradoSemestre = collect([$fila['grado'], $fila['semestre']])->filter()->join(' · ');
                $valores = [
                    [(string) $fila['numero'], 'center'],
                    [$fila['responsable'] . ($fila['es_principal'] ? ' (Principal)' : ''), 'left'],
                    [$fila['parentesco'], 'center'],
                    [$fila['telefono'], 'left'],
                    [$fila['ine'] ?? '', 'center'],
                    [$fila['domicilio'], 'left'],
                    [$fila['alumno'], 'left'],
                    [$fila['nivel'], 'center'],
                    [$gradoSemestre, 'center'],
                    [$fila['grupo'], 'center'],
                ];
            }

            foreach ($valores as $indice => [$texto, $alineacion]) {
                $celda = $tabla->addCell($columnas[$indice]['width'], ['valign' => 'center']);
                $celda->addText((string) $texto, ['size' => 6], ['alignment' => $alineacion, 'spaceAfter' => 0]);
            }
        }
    }

    private function agregarFirmasWord($section, array $datos): void
    {
        $section->addTextBreak(2);
        $tabla = $section->addTable('FirmasDirectorio');
        $tabla->addRow();

        $director = $tabla->addCell(6000, ['valign' => 'bottom']);
        $director->addText('____________________________________________', ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
        $director->addText($datos['director'], ['bold' => true, 'size' => 7.5], ['alignment' => 'center', 'spaceAfter' => 0]);
        $director->addText($datos['cargo_director'], ['size' => 6.5, 'color' => '64748B'], ['alignment' => 'center']);

        $elaboro = $tabla->addCell(6000, ['valign' => 'bottom']);
        $elaboro->addText('____________________________________________', ['size' => 7], ['alignment' => 'center', 'spaceAfter' => 0]);
        $elaboro->addText($datos['elaborado_por'], ['bold' => true, 'size' => 7.5], ['alignment' => 'center', 'spaceAfter' => 0]);
        $elaboro->addText('Elaboró', ['size' => 6.5, 'color' => '64748B'], ['alignment' => 'center']);
    }

    private function agregarPieWord($section): void
    {
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Documento administrativo confidencial · Página {PAGE} de {NUMPAGES}',
            ['size' => 6, 'color' => '64748B'],
            ['alignment' => 'center']
        );
    }

    private function numerarSecciones(Collection $secciones): Collection
    {
        $numero = 1;

        return $secciones->map(function (array $seccion) use (&$numero): array {
            $seccion['filas'] = $seccion['filas']->map(function (array $fila) use (&$numero): array {
                $fila['numero'] = $numero++;

                return $fila;
            })->values();

            return $seccion;
        })->values();
    }

    private function renumerarSeccion(array $seccion): array
    {
        $seccion['filas'] = $seccion['filas']->values()->map(function (array $fila, int $indice): array {
            $fila['numero'] = $indice + 1;

            return $fila;
        });

        return $seccion;
    }

    private function validarDependenciasAcademicas(array $filtros, ?Nivel $nivel): void
    {
        if (! $nivel) {
            abort_if(
                $filtros['generacion_id'] || $filtros['grado_id'] || $filtros['semestre_id'] || $filtros['grupo_id'],
                422,
                'Selecciona un nivel antes de aplicar generación, grado, semestre o grupo.'
            );

            return;
        }

        if ($filtros['generacion_id']) {
            abort_unless(Generacion::query()->whereKey($filtros['generacion_id'])->where('nivel_id', $nivel->id)->exists(), 422, 'La generación no pertenece al nivel seleccionado.');
        }

        if ($filtros['grado_id']) {
            abort_unless(Grado::query()->whereKey($filtros['grado_id'])->where('nivel_id', $nivel->id)->exists(), 422, 'El grado no pertenece al nivel seleccionado.');
        }

        if ($filtros['semestre_id'] && $filtros['grado_id']) {
            abort_unless(Semestre::query()->whereKey($filtros['semestre_id'])->where('grado_id', $filtros['grado_id'])->exists(), 422, 'El semestre no pertenece al grado seleccionado.');
        }

        if ($filtros['grupo_id']) {
            $grupo = Grupo::query()->findOrFail($filtros['grupo_id']);
            abort_unless((int) $grupo->nivel_id === (int) $nivel->id, 422, 'El grupo no pertenece al nivel seleccionado.');
            abort_if($filtros['grado_id'] && (int) $grupo->grado_id !== (int) $filtros['grado_id'], 422, 'El grupo no pertenece al grado seleccionado.');
            abort_if($filtros['semestre_id'] && (int) $grupo->semestre_id !== (int) $filtros['semestre_id'], 422, 'El grupo no pertenece al semestre seleccionado.');
            abort_if($filtros['generacion_id'] && (int) $grupo->generacion_id !== (int) $filtros['generacion_id'], 422, 'El grupo no pertenece a la generación seleccionada.');
            abort_if($filtros['ciclo_escolar_id'] && (int) $grupo->ciclo_escolar_id !== (int) $filtros['ciclo_escolar_id'], 422, 'El grupo no pertenece al ciclo escolar seleccionado.');
        }
    }

    private function resumenFiltros(array $filtros, ?Nivel $nivel): array
    {
        $generacion = $filtros['generacion_id'] ? Generacion::find($filtros['generacion_id']) : null;
        $ciclo = $filtros['ciclo_escolar_id'] ? CicloEscolar::find($filtros['ciclo_escolar_id']) : null;
        $grado = $filtros['grado_id'] ? Grado::find($filtros['grado_id']) : null;
        $semestre = $filtros['semestre_id'] ? Semestre::find($filtros['semestre_id']) : null;
        $grupo = $filtros['grupo_id'] ? Grupo::with('asignacionGrupo:id,nombre')->find($filtros['grupo_id']) : null;

        $estados = [
            'activos' => 'Activos',
            'egresados' => 'Egresados',
            'no_reinscritos' => 'No reinscritos / pendientes',
            'todos' => 'Todos',
        ];
        $tiposFamilia = [
            'todas' => 'Todas',
            'uno' => 'Un alumno',
            'varios' => 'Varios alumnos',
            'multinivel' => 'Multinivel',
        ];

        return [
            'Nivel' => $nivel?->nombre ?? 'Todos los niveles',
            'Ciclo escolar' => $ciclo ? $ciclo->inicio_anio . '-' . $ciclo->fin_anio : 'Todos',
            'Estado' => $estados[$filtros['estado_alumno']] ?? 'Activos',
            'Generación' => $generacion?->etiqueta ?? 'Todas',
            'Grado' => $grado?->nombre ?? 'Todos',
            'Semestre' => $semestre?->numero ? 'Semestre ' . $semestre->numero : 'Todos',
            'Grupo' => $grupo?->asignacionGrupo?->nombre ?? 'Todos',
            'Responsables' => $filtros['modo_responsables'] === 'todos' ? 'Todos los responsables activos' : 'Tutor principal',
            'Parentesco' => $filtros['parentesco'] !== '' ? Str::headline($filtros['parentesco']) : 'Todos',
            'Vista' => $filtros['vista'] === 'familias' ? 'Familias' : 'Alumnos',
            'Tipo de familia' => $tiposFamilia[$filtros['tipo_familia']] ?? 'Todas',
        ];
    }

    private function direccionEscuela(?Escuela $escuela): string
    {
        if (! $escuela) {
            return '';
        }

        $calle = trim(collect([
            $escuela->calle,
            $escuela->no_exterior ? 'Núm. ' . $escuela->no_exterior : null,
            $escuela->no_interior ? 'Int. ' . $escuela->no_interior : null,
        ])->filter()->join(' '));

        return collect([
            $calle !== '' ? $calle : null,
            $escuela->colonia ? 'Col. ' . $escuela->colonia : null,
            $escuela->codigo_postal ? 'C.P. ' . $escuela->codigo_postal : null,
            $escuela->ciudad,
            $escuela->municipio,
            $escuela->estado,
        ])->filter()->unique()->join(', ');
    }

    private function nombreDirector(Nivel $nivel): string
    {
        $director = $nivel->director;

        if (! $director) {
            return 'Dirección del nivel';
        }

        return trim(collect([
            $director->titulo,
            $director->nombre,
            $director->apellido_paterno,
            $director->apellido_materno,
        ])->filter()->join(' '));
    }

    private function logoInstitucional(): ?string
    {
        foreach ([
            public_path('imagenes/logo-letra.png'),
            public_path('imagenes/logo-oficial-cum.png'),
        ] as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function logoNivel(Nivel $nivel): ?string
    {
        $candidatos = [];

        if (trim((string) $nivel->logo) !== '') {
            $logo = ltrim((string) $nivel->logo, '/');
            $candidatos[] = public_path('storage/logos/' . basename($logo));
            $candidatos[] = public_path($logo);
            $candidatos[] = storage_path('app/public/logos/' . basename($logo));
        }

        $candidatos[] = public_path('imagenes/logo-edu.png');
        $candidatos[] = $this->logoInstitucional();

        foreach (array_filter($candidatos) as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }
}
