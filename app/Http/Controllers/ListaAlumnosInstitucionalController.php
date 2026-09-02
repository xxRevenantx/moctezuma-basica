<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use App\Models\PersonaNivelDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListaAlumnosInstitucionalController extends Controller
{
    private const FILAS_POR_PAGINA = 30;

    public function pdf(Request $request, string $slug_nivel)
    {
        abort_unless((bool) (auth()->user()?->is_admin ?? false), 403);

        $datos = $this->construirDocumento($request, $slug_nivel);

        $pdf = Pdf::loadView('pdf.lista-alumnos-institucional', $datos)
            ->setPaper('letter', 'portrait');

        return $pdf->download($this->nombreArchivo($datos, 'pdf'));
    }

    public function word(Request $request, string $slug_nivel): BinaryFileResponse
    {
        abort_unless((bool) (auth()->user()?->is_admin ?? false), 403);

        $datos = $this->construirDocumento($request, $slug_nivel);

        $directorio = storage_path('app/temp/listas-alumnos-institucionales');
        $directorioPhpWord = storage_path('app/temp/phpword');

        File::ensureDirectoryExists($directorio, 0775, true);
        File::ensureDirectoryExists($directorioPhpWord, 0775, true);

        putenv('TMP=' . $directorioPhpWord);
        putenv('TEMP=' . $directorioPhpWord);
        putenv('TMPDIR=' . $directorioPhpWord);
        Settings::setTempDir($directorioPhpWord);

        $ruta = $directorio . DIRECTORY_SEPARATOR . Str::uuid() . '.docx';

        $this->crearWord($datos, $ruta);

        return response()
            ->download($ruta, $this->nombreArchivo($datos, 'docx'))
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function construirDocumento(Request $request, string $slugNivel): array
    {
        $datosValidados = $request->validate([
            'modo_descarga' => ['required', 'in:grupo,seleccionados,nivel'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupo_id' => ['nullable', 'integer', 'exists:grupos,id'],
            'alumnos' => ['nullable', 'string'],
        ]);

        $nivel = Nivel::query()
            ->with(['director', 'supervisor'])
            ->where('slug', $slugNivel)
            ->firstOrFail();

        $ciclo = CicloEscolar::query()->findOrFail((int) $datosValidados['ciclo_escolar_id']);
        $modo = (string) $datosValidados['modo_descarga'];

        $idsSeleccionados = $this->idsSeleccionados((string) ($datosValidados['alumnos'] ?? ''));

        if ($modo === 'seleccionados' && $idsSeleccionados === []) {
            abort(422, 'Selecciona al menos un alumno activo para generar la lista institucional.');
        }

        if ($modo === 'nivel') {
            $grupos = $this->gruposDelNivel($nivel, $ciclo);
        } else {
            foreach (['generacion_id', 'grado_id', 'grupo_id'] as $campo) {
                if (blank($datosValidados[$campo] ?? null)) {
                    abort(422, 'Selecciona generación, grado y grupo para generar la lista institucional.');
                }
            }

            if ($nivel->slug === 'bachillerato' && blank($datosValidados['semestre_id'] ?? null)) {
                abort(422, 'Selecciona el semestre para generar la lista institucional de Bachillerato.');
            }

            $grupo = $this->grupoDelContexto(
                nivel: $nivel,
                ciclo: $ciclo,
                generacionId: (int) $datosValidados['generacion_id'],
                gradoId: (int) $datosValidados['grado_id'],
                semestreId: filled($datosValidados['semestre_id'] ?? null)
                    ? (int) $datosValidados['semestre_id']
                    : null,
                grupoId: (int) $datosValidados['grupo_id'],
            );

            $grupos = collect([$grupo]);
        }

        if ($grupos->isEmpty()) {
            abort(404, 'No se encontraron grupos disponibles para el nivel y ciclo seleccionados.');
        }

        $bloques = $grupos
            ->map(function (Grupo $grupo) use ($nivel, $ciclo, $modo, $idsSeleccionados): array {
                $alumnos = $this->alumnosActivosDelGrupo(
                    grupo: $grupo,
                    ciclo: $ciclo,
                    nivel: $nivel,
                    idsSeleccionados: $modo === 'seleccionados' ? $idsSeleccionados : [],
                );

                return $this->bloqueGrupo($nivel, $ciclo, $grupo, $alumnos);
            })
            ->values();

        if ($modo === 'seleccionados' && $bloques->sum(fn (array $bloque): int => $bloque['alumnos']->count()) === 0) {
            abort(422, 'Los alumnos seleccionados ya no pertenecen a la matrícula vigente del grupo.');
        }

        $paginas = collect();

        foreach ($bloques as $bloque) {
            /** @var Collection<int, Inscripcion> $alumnos */
            $alumnos = $bloque['alumnos'];
            $partes = $alumnos->isEmpty()
                ? collect([collect()])
                : $alumnos->chunk(self::FILAS_POR_PAGINA)->values();

            foreach ($partes as $indice => $parte) {
                $filas = $parte->values();

                while ($filas->count() < self::FILAS_POR_PAGINA) {
                    $filas->push(null);
                }

                $paginas->push(array_merge($bloque, [
                    'filas' => $filas,
                    'pagina_grupo' => $indice + 1,
                    'paginas_grupo' => $partes->count(),
                ]));
            }
        }

        return [
            'nivel' => $nivel,
            'ciclo' => $ciclo,
            'modo' => $modo,
            'bloques' => $bloques,
            'paginas' => $paginas,
            'logo_seg' => public_path('imagenes/logo-edu.png'),
            'generado_en' => now(),
        ];
    }

    private function gruposDelNivel(Nivel $nivel, CicloEscolar $ciclo): Collection
    {
        return Grupo::query()
            ->with([
                'grado:id,nivel_id,nombre,orden',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,grado_id,numero,orden_global',
                'asignacionGrupo:id,nombre',
            ])
            ->where('ciclo_escolar_id', $ciclo->id)
            ->where('nivel_id', $nivel->id)
            ->where('estado', 'activo')
            ->whereNull('archivado_at')
            ->orderBy('grado_id')
            ->orderByRaw('COALESCE(semestre_id, 0)')
            ->orderBy('asignacion_grupo_id')
            ->orderBy('id')
            ->get();
    }

    private function grupoDelContexto(
        Nivel $nivel,
        CicloEscolar $ciclo,
        int $generacionId,
        int $gradoId,
        ?int $semestreId,
        int $grupoId,
    ): Grupo {
        return Grupo::query()
            ->with([
                'grado:id,nivel_id,nombre,orden',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,grado_id,numero,orden_global',
                'asignacionGrupo:id,nombre',
            ])
            ->whereKey($grupoId)
            ->where('ciclo_escolar_id', $ciclo->id)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacionId)
            ->where('grado_id', $gradoId)
            ->when(
                $nivel->slug === 'bachillerato',
                fn ($query) => $query->where('semestre_id', $semestreId),
                fn ($query) => $query->whereNull('semestre_id'),
            )
            ->where('estado', 'activo')
            ->whereNull('archivado_at')
            ->firstOrFail();
    }

    /**
     * Fuente de verdad de este formato: matrícula vigente del ciclo.
     * Un historial cerrado/anulado o un alumno no visible nunca se imprime.
     *
     * @param array<int, int> $idsSeleccionados
     * @return Collection<int, Inscripcion>
     */
    private function alumnosActivosDelGrupo(
        Grupo $grupo,
        CicloEscolar $ciclo,
        Nivel $nivel,
        array $idsSeleccionados = [],
    ): Collection {
        $historiales = InscripcionCiclo::query()
            ->with(['inscripcion'])
            ->where('ciclo_escolar_id', $ciclo->id)
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $grupo->generacion_id)
            ->where('grado_id', $grupo->grado_id)
            ->where('grupo_id', $grupo->id)
            ->when(
                $nivel->slug === 'bachillerato',
                fn ($query) => $query->where('semestre_id', $grupo->semestre_id),
                fn ($query) => $query->whereNull('semestre_id'),
            )
            ->where('estado', InscripcionCiclo::ESTADO_EN_CURSO)
            ->where('estatus_actual_ciclo', Inscripcion::ESTATUS_VISIBLE_LISTAS)
            ->when($idsSeleccionados !== [], fn ($query) => $query->whereIn('inscripcion_id', $idsSeleccionados))
            ->whereHas('inscripcion', fn ($query) => $query->visiblesEnListas())
            ->get();

        return $historiales
            ->map(fn (InscripcionCiclo $historial) => $historial->inscripcion)
            ->filter(fn ($alumno): bool => $alumno instanceof Inscripcion && $alumno->visibleEnListas())
            ->unique('id')
            ->sortBy(fn (Inscripcion $alumno): string => mb_strtolower(trim(implode(' ', array_filter([
                $alumno->apellido_paterno,
                $alumno->apellido_materno,
                $alumno->nombre,
            ])))))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function bloqueGrupo(
        Nivel $nivel,
        CicloEscolar $ciclo,
        Grupo $grupo,
        Collection $alumnos,
    ): array {
        $docente = $this->titularGrupo($grupo, $ciclo);
        $director = $nivel->director && (bool) $nivel->director->status ? $nivel->director : null;
        $supervisor = $nivel->supervisor && (bool) $nivel->supervisor->status ? $nivel->supervisor : null;

        $hombres = $alumnos->filter(fn (Inscripcion $alumno): bool => mb_strtoupper(trim((string) $alumno->genero)) === 'H')->count();
        $mujeres = $alumnos->filter(fn (Inscripcion $alumno): bool => mb_strtoupper(trim((string) $alumno->genero)) === 'M')->count();

        return [
            'grupo' => $grupo,
            'alumnos' => $alumnos,
            'escuela' => $this->nombreEscuela($nivel),
            'cct' => (string) ($nivel->cct ?: ''),
            'grado' => (string) ($grupo->grado?->nombre ?: ''),
            'semestre' => $grupo->semestre?->numero,
            'grupo_nombre' => (string) ($grupo->asignacionGrupo?->nombre ?: $grupo->clave ?: '—'),
            'docente_cargo' => $this->cargoDocente($nivel),
            'docente_nombre' => $this->nombrePersona($docente?->cabecera?->persona),
            'director_cargo' => mb_strtoupper((string) ($director?->cargo ?: 'DIRECTOR(A)')),
            'director_nombre' => $this->nombreDirectivo($director),
            'supervisor_cargo' => $this->cargoSupervisor($supervisor),
            'supervisor_nombre' => $this->nombreDirectivo($supervisor),
            'hombres' => $hombres,
            'mujeres' => $mujeres,
            'total' => $alumnos->count(),
        ];
    }

    private function titularGrupo(Grupo $grupo, CicloEscolar $ciclo): ?PersonaNivelDetalle
    {
        return PersonaNivelDetalle::query()
            ->vigenteEnCiclo((int) $ciclo->id)
            ->titularReconocido()
            ->where('grupo_id', $grupo->id)
            ->with(['cabecera.persona'])
            ->orderByDesc('es_titular_principal')
            ->orderByDesc('es_titular')
            ->orderBy('orden')
            ->orderBy('id')
            ->first();
    }

    private function nombreEscuela(Nivel $nivel): string
    {
        return match ($nivel->slug) {
            'preescolar' => 'J.N. CENTRO UNIVERSITARIO MOCTEZUMA',
            'primaria' => 'ESCUELA PRIMARIA CENTRO UNIVERSITARIO MOCTEZUMA',
            'secundaria' => 'ESCUELA SECUNDARIA CENTRO UNIVERSITARIO MOCTEZUMA',
            'bachillerato' => 'BACHILLERATO CENTRO UNIVERSITARIO MOCTEZUMA A.C.',
            default => 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.',
        };
    }

    private function cargoDocente(Nivel $nivel): string
    {
        return match ($nivel->slug) {
            'preescolar' => 'EDUCADORA',
            'primaria' => 'DOCENTE TITULAR',
            'secundaria' => 'TUTOR(A) / DOCENTE TITULAR',
            'bachillerato' => 'TUTOR(A) DE GRUPO',
            default => 'DOCENTE TITULAR',
        };
    }

    private function cargoSupervisor($supervisor): string
    {
        $cargo = mb_strtoupper(trim((string) ($supervisor?->cargo ?: 'SUPERVISOR(A)')));
        $zona = trim((string) ($supervisor?->zona_escolar ?: ''));

        return $zona !== '' ? $cargo . ' · ZONA ' . $zona : $cargo;
    }

    private function nombrePersona($persona): string
    {
        if (!$persona) {
            return '';
        }

        return mb_strtoupper(trim(implode(' ', array_filter([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ]))));
    }

    private function nombreDirectivo($directivo): string
    {
        if (!$directivo) {
            return '';
        }

        return mb_strtoupper(trim(implode(' ', array_filter([
            $directivo->titulo ?? null,
            $directivo->nombre ?? null,
            $directivo->apellido_paterno ?? null,
            $directivo->apellido_materno ?? null,
        ]))));
    }

    /** @return array<int, int> */
    private function idsSeleccionados(string $valor): array
    {
        if (trim($valor) === '') {
            return [];
        }

        return collect(explode(',', $valor))
            ->map(fn ($id): int => (int) trim((string) $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function crearWord(array $datos, string $ruta): void
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8);
        $phpWord->getDocInfo()
            ->setCreator('Centro Universitario Moctezuma')
            ->setCompany('Centro Universitario Moctezuma A.C.')
            ->setTitle('Lista de alumnos institucional')
            ->setSubject('Lista institucional de matrícula vigente');

        $phpWord->addTableStyle('ListaAlumnosInstitucional', [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 35,
            'alignment' => 'center',
        ]);

        $logo = (string) ($datos['logo_seg'] ?? '');

        foreach ($datos['paginas'] as $pagina) {
            $section = $phpWord->addSection([
                'paperSize' => 'Letter',
                'orientation' => 'portrait',
                'marginTop' => 300,
                'marginBottom' => 300,
                'marginLeft' => 430,
                'marginRight' => 430,
                'headerHeight' => 0,
                'footerHeight' => 0,
            ]);

            $encabezado = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'alignment' => 'center',
            ]);
            $encabezado->addRow(850);

            $celdaLogo = $encabezado->addCell(3100, ['borderSize' => 0, 'valign' => 'center']);
            if ($logo !== '' && is_file($logo)) {
                $celdaLogo->addImage($logo, [
                    'width' => 175,
                    'height' => 60,
                    'alignment' => 'left',
                ]);
            }

            $celdaTitulo = $encabezado->addCell(8000, ['borderSize' => 0, 'valign' => 'center']);
            $celdaTitulo->addText('LISTA DE ALUMNOS', [
                'name' => 'Times New Roman',
                'size' => 12,
                'bold' => true,
            ], [
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]);
            $celdaTitulo->addText((string) $datos['ciclo']->nombre, [
                'name' => 'Times New Roman',
                'size' => 10,
                'bold' => true,
            ], [
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]);

            $contexto = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'alignment' => 'center',
            ]);
            $contexto->addRow(330);

            $celdaEscuela = $contexto->addCell(7900, ['borderSize' => 0, 'valign' => 'center']);
            $textoEscuela = $celdaEscuela->addTextRun([
                'alignment' => 'center',
                'spaceAfter' => 0,
            ]);
            $textoEscuela->addText((string) $pagina['escuela'] . ' ', ['size' => 8, 'bold' => true]);
            $textoEscuela->addText('C.C.T.: ', ['size' => 8, 'bold' => true]);
            $textoEscuela->addText((string) $pagina['cct'], ['size' => 8, 'bold' => true, 'underline' => 'single']);

            $celdaGrupo = $contexto->addCell(3200, ['borderSize' => 0, 'valign' => 'center']);
            $runGrupo = $celdaGrupo->addTextRun([
                'alignment' => 'right',
                'spaceAfter' => 0,
            ]);
            $runGrupo->addText('GRADO: ', ['size' => 8, 'bold' => true]);
            $runGrupo->addText((string) $pagina['grado'], ['size' => 8, 'bold' => true, 'underline' => 'single']);

            if (filled($pagina['semestre'])) {
                $runGrupo->addText('  SEM.: ', ['size' => 8, 'bold' => true]);
                $runGrupo->addText((string) $pagina['semestre'], ['size' => 8, 'bold' => true, 'underline' => 'single']);
            }

            $runGrupo->addText('  GRUPO: ', ['size' => 8, 'bold' => true]);
            $runGrupo->addText((string) $pagina['grupo_nombre'], ['size' => 8, 'bold' => true, 'underline' => 'single']);

            $section->addTextBreak(0);

            $tabla = $section->addTable('ListaAlumnosInstitucional');
            $tabla->addRow(360, ['tblHeader' => true]);

            $encabezados = [
                ['NP', 600],
                ['APELLIDO P.', 1850],
                ['APELLIDO M.', 1850],
                ['NOMBRE', 2050],
                ['CURP', 2900],
                ["FECHA DE\nNAC.", 1850],
            ];

            foreach ($encabezados as [$texto, $ancho]) {
                $celda = $tabla->addCell($ancho, [
                    'valign' => 'center',
                    'bgColor' => 'F2F2F2',
                ]);
                foreach (explode("\n", $texto) as $linea) {
                    $celda->addText($linea, [
                        'name' => 'Arial',
                        'size' => 7,
                        'bold' => true,
                    ], [
                        'alignment' => 'center',
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                    ]);
                }
            }

            $numeroBase = ((int) $pagina['pagina_grupo'] - 1) * self::FILAS_POR_PAGINA;

            foreach ($pagina['filas'] as $indice => $alumno) {
                $tabla->addRow(255, ['exactHeight' => true]);

                $valores = $alumno instanceof Inscripcion
                    ? [
                        (string) ($numeroBase + $indice + 1),
                        mb_strtoupper((string) $alumno->apellido_paterno),
                        mb_strtoupper((string) $alumno->apellido_materno),
                        mb_strtoupper((string) $alumno->nombre),
                        mb_strtoupper((string) $alumno->curp),
                        $alumno->fecha_nacimiento?->format('d/m/Y') ?? '',
                    ]
                    : ['', '', '', '', '', ''];

                $anchos = [600, 1850, 1850, 2050, 2900, 1850];

                foreach ($valores as $columna => $valor) {
                    $celda = $tabla->addCell($anchos[$columna], ['valign' => 'center']);
                    $celda->addText((string) $valor, [
                        'name' => 'Arial',
                        'size' => 7,
                    ], [
                        'alignment' => $columna === 0 || $columna === 5 ? 'center' : 'left',
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                    ]);
                }
            }

            $section->addTextBreak(1);

            $firmas = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'alignment' => 'center',
            ]);
            $firmas->addRow();
            $this->agregarFirmaWord(
                $firmas->addCell(5550, ['borderSize' => 0]),
                (string) $pagina['docente_cargo'],
                (string) $pagina['docente_nombre'],
            );
            $this->agregarFirmaWord(
                $firmas->addCell(5550, ['borderSize' => 0]),
                (string) $pagina['director_cargo'],
                (string) $pagina['director_nombre'],
            );

            $section->addTextBreak(1);

            $pie = $section->addTable([
                'borderSize' => 0,
                'cellMargin' => 0,
                'alignment' => 'center',
            ]);
            $pie->addRow();

            $celdaSupervisor = $pie->addCell(7600, ['borderSize' => 0]);
            $this->agregarFirmaWord(
                $celdaSupervisor,
                (string) $pagina['supervisor_cargo'],
                (string) $pagina['supervisor_nombre'],
            );

            $celdaTotales = $pie->addCell(3500, ['borderSize' => 0, 'valign' => 'center']);
            $resumen = $celdaTotales->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 35,
                'alignment' => 'right',
            ]);
            $resumen->addRow(260);
            foreach (['H', 'M', 'TOTAL'] as $encabezadoResumen) {
                $resumen->addCell($encabezadoResumen === 'TOTAL' ? 1100 : 650, ['valign' => 'center'])
                    ->addText($encabezadoResumen, ['size' => 7, 'bold' => true], [
                        'alignment' => 'center',
                        'spaceAfter' => 0,
                    ]);
            }
            $resumen->addRow(260);
            foreach ([$pagina['hombres'], $pagina['mujeres'], $pagina['total']] as $indice => $valor) {
                $resumen->addCell($indice === 2 ? 1100 : 650, ['valign' => 'center'])
                    ->addText((string) $valor, ['size' => 8, 'bold' => true], [
                        'alignment' => 'center',
                        'spaceAfter' => 0,
                    ]);
            }
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
    }

    private function agregarFirmaWord($celda, string $cargo, string $nombre): void
    {
        $celda->addText($cargo, [
            'name' => 'Arial',
            'size' => 7,
            'bold' => true,
        ], [
            'alignment' => 'center',
            'spaceAfter' => 0,
        ]);

        $celda->addTextBreak(1);
        $celda->addText('________________________________', [
            'name' => 'Arial',
            'size' => 7,
        ], [
            'alignment' => 'center',
            'spaceAfter' => 0,
        ]);
        $celda->addText($nombre !== '' ? $nombre : ' ', [
            'name' => 'Arial',
            'size' => 7,
            'bold' => true,
        ], [
            'alignment' => 'center',
            'spaceAfter' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function nombreArchivo(array $datos, string $extension): string
    {
        /** @var Nivel $nivel */
        $nivel = $datos['nivel'];
        /** @var CicloEscolar $ciclo */
        $ciclo = $datos['ciclo'];
        $bloques = $datos['bloques'];

        if ($datos['modo'] === 'nivel') {
            $base = sprintf(
                'LISTAS_ALUMNOS_%s_%s',
                mb_strtoupper($nivel->slug),
                $ciclo->nombre,
            );
        } else {
            $primero = $bloques->first();
            $grado = Str::upper(Str::slug((string) ($primero['grado'] ?? ''), '_'));
            $grupo = Str::upper(Str::slug((string) ($primero['grupo_nombre'] ?? ''), '_'));
            $base = sprintf(
                'LISTA_ALUMNOS_%s_%s_%s_%s',
                mb_strtoupper($nivel->slug),
                $grado ?: 'GRADO',
                $grupo ?: 'GRUPO',
                $ciclo->nombre,
            );
        }

        return $base . '.' . $extension;
    }
}
