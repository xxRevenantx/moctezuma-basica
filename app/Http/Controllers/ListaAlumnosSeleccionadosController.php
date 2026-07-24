<?php

namespace App\Http\Controllers;

use App\Models\Escuela;
use App\Models\Inscripcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ListaAlumnosSeleccionadosController extends Controller
{
    private const COLUMNAS = [
        'numero' => ['label' => '#', 'peso' => 4, 'align' => 'center'],
        'foto' => ['label' => 'Foto', 'peso' => 8, 'align' => 'center'],
        'matricula' => ['label' => 'Matrícula', 'peso' => 12, 'align' => 'center'],
        'folio' => ['label' => 'Folio', 'peso' => 10, 'align' => 'center'],
        'curp' => ['label' => 'CURP', 'peso' => 17, 'align' => 'center'],
        'nombre' => ['label' => 'Nombre completo', 'peso' => 24, 'align' => 'left'],
        'sexo' => ['label' => 'Sexo', 'peso' => 7, 'align' => 'center'],
        'nivel' => ['label' => 'Nivel', 'peso' => 11, 'align' => 'left'],
        'grado' => ['label' => 'Grado / semestre', 'peso' => 13, 'align' => 'center'],
        'grupo' => ['label' => 'Grupo', 'peso' => 8, 'align' => 'center'],
        'generacion' => ['label' => 'Generación', 'peso' => 11, 'align' => 'center'],
        'ciclo' => ['label' => 'Ciclo escolar', 'peso' => 11, 'align' => 'center'],
        'estatus' => ['label' => 'Estatus', 'peso' => 9, 'align' => 'center'],
        'firma' => ['label' => 'Firma', 'peso' => 15, 'align' => 'center'],
        'observaciones' => ['label' => 'Observaciones', 'peso' => 18, 'align' => 'left'],
    ];

    public function pdf(Request $request)
    {
        $datos = $this->prepararDatos($request);
        $nombre = $datos['nombre_archivo'] . '.pdf';

        return Pdf::loadView('pdf.lista-alumnos-seleccionados', $datos)
            ->setPaper('letter', $datos['orientacion'])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->stream($nombre);
    }

    public function word(Request $request): BinaryFileResponse
    {
        $datos = $this->prepararDatos($request);
        $directorio = storage_path('app/temp/listas-alumnos');
        File::ensureDirectoryExists($directorio, 0775, true);

        $ruta = $directorio . DIRECTORY_SEPARATOR . $datos['nombre_archivo'] . '-' . Str::uuid() . '.docx';
        $this->guardarWord($datos, $ruta);

        return response()
            ->download($ruta, $datos['nombre_archivo'] . '.docx')
            ->deleteFileAfterSend(true);
    }

    private function prepararDatos(Request $request): array
    {
        $validados = $request->validate([
            'alumnos' => ['required', 'array', 'min:1', 'max:5000'],
            'alumnos.*' => ['required', 'integer', 'distinct', 'exists:inscripciones,id'],
            'titulo' => ['required', 'string', 'max:120'],
            'columnas' => ['required', 'array', 'min:1'],
            'columnas.*' => ['required', 'string', 'distinct', 'in:' . implode(',', array_keys(self::COLUMNAS))],
            'orientacion' => ['nullable', 'string', 'in:auto,portrait,landscape'],
            'agrupar' => ['nullable', 'boolean'],
            'estadisticas' => ['nullable', 'boolean'],
            'responsable' => ['nullable', 'string', 'max:120'],
        ], [
            'alumnos.required' => 'Selecciona al menos un alumno.',
            'columnas.required' => 'Selecciona al menos una columna para la lista.',
            'titulo.required' => 'Escribe el título del documento.',
        ]);

        $columnas = collect($validados['columnas'])
            ->filter(fn (string $columna) => array_key_exists($columna, self::COLUMNAS))
            ->unique()
            ->values()
            ->all();

        $alumnos = Inscripcion::withTrashed()
            ->with([
                'nivel:id,nombre,slug,color,cct',
                'grado:id,nombre,slug,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,grado_id,numero,orden_global',
                'ciclo:id,ciclo',
                'grupo' => fn ($query) => $query
                    ->select('id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id')
                    ->with('asignacionGrupo:id,nombre'),
            ])
            ->whereIn('id', $validados['alumnos'])
            ->get()
            ->sortBy(fn (Inscripcion $alumno) => implode('|', [
                Str::lower((string) ($alumno->nivel?->nombre ?? '')),
                str_pad((string) ($alumno->grado?->orden ?? 999), 3, '0', STR_PAD_LEFT),
                str_pad((string) ($alumno->semestre?->orden_global ?? 999), 3, '0', STR_PAD_LEFT),
                Str::lower((string) ($alumno->grupo?->asignacionGrupo?->nombre ?? '')),
                Str::lower((string) $alumno->apellido_paterno),
                Str::lower((string) $alumno->apellido_materno),
                Str::lower((string) $alumno->nombre),
            ]))
            ->values();

        abort_if($alumnos->isEmpty(), 404, 'No se encontraron alumnos para generar la lista.');

        $agrupar = $request->boolean('agrupar', true);
        $grupos = $this->agruparAlumnos($alumnos, $agrupar);
        $orientacion = $this->resolverOrientacion((string) ($validados['orientacion'] ?? 'auto'), $columnas);
        $escuela = Escuela::query()->first();

        return [
            'titulo' => trim($validados['titulo']),
            'responsable' => trim((string) ($validados['responsable'] ?? '')),
            'columnas' => $columnas,
            'config_columnas' => self::COLUMNAS,
            'grupos' => $grupos,
            'alumnos' => $alumnos,
            'escuela' => $escuela,
            'direccion_escuela' => $this->direccionEscuela($escuela),
            'logo' => $this->rutaLogo(),
            'orientacion' => $orientacion,
            'mostrar_estadisticas' => $request->boolean('estadisticas', true),
            'estadisticas' => $this->estadisticas($alumnos),
            'fecha_emision' => now()->format('d/m/Y'),
            'nombre_archivo' => $this->nombreArchivo($grupos),
        ];
    }

    private function agruparAlumnos(Collection $alumnos, bool $agrupar): Collection
    {
        if (! $agrupar) {
            return collect([[
                'clave' => 'general',
                'titulo' => 'Selección general de alumnos',
                'nivel' => null,
                'cct' => null,
                'grado' => null,
                'grupo' => null,
                'generacion' => null,
                'ciclo' => null,
                'alumnos' => $alumnos,
            ]]);
        }

        return $alumnos
            ->groupBy(fn (Inscripcion $alumno) => implode('|', [
                $alumno->nivel_id ?: 0,
                $alumno->grado_id ?: 0,
                $alumno->semestre_id ?: 0,
                $alumno->grupo_id ?: 0,
                $alumno->generacion_id ?: 0,
            ]))
            ->map(function (Collection $items, string $clave) {
                /** @var Inscripcion $primero */
                $primero = $items->first();
                $nivel = $primero->nivel?->nombre ?? 'Sin nivel';
                $grado = $this->textoGrado($primero);
                $grupo = $primero->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo';
                $generacion = $this->textoGeneracion($primero);

                return [
                    'clave' => $clave,
                    'titulo' => sprintf('%s · %s · Grupo %s', $nivel, $grado, $grupo),
                    'nivel' => $nivel,
                    'cct' => $primero->nivel?->cct,
                    'grado' => $grado,
                    'grupo' => $grupo,
                    'generacion' => $generacion,
                    'ciclo' => $primero->ciclo?->ciclo ?? '—',
                    'alumnos' => $items->values(),
                ];
            })
            ->values();
    }

    private function estadisticas(Collection $alumnos): array
    {
        return [
            'total' => $alumnos->count(),
            'hombres' => $alumnos->where('genero', 'H')->count(),
            'mujeres' => $alumnos->where('genero', 'M')->count(),
            'activos' => $alumnos
                ->filter(fn (Inscripcion $alumno) => ! $alumno->trashed()
                    && in_array($alumno->estatusNormalizado(), Inscripcion::ESTATUS_ACTIVOS, true))
                ->count(),
            'bajas' => $alumnos
                ->filter(fn (Inscripcion $alumno) => ! $alumno->trashed() && $alumno->esBajaAdministrativa())
                ->count(),
            'egresados' => $alumnos
                ->filter(fn (Inscripcion $alumno) => ! $alumno->trashed() && $alumno->esEgresado())
                ->count(),
        ];
    }

    private function resolverOrientacion(string $solicitada, array $columnas): string
    {
        if (in_array($solicitada, ['portrait', 'landscape'], true)) {
            return $solicitada;
        }

        $peso = collect($columnas)->sum(fn (string $columna) => self::COLUMNAS[$columna]['peso']);

        return $peso > 78 ? 'landscape' : 'portrait';
    }

    private function nombreArchivo(Collection $grupos): string
    {
        if ($grupos->count() === 1 && $grupos->first()['clave'] !== 'general') {
            $grupo = $grupos->first();
            $base = sprintf(
                'lista-alumnos-%s-%s-grupo-%s',
                $grupo['nivel'],
                $grupo['grado'],
                $grupo['grupo'],
            );
        } else {
            $base = 'lista-general-alumnos';
        }

        return Str::slug($base) . '-' . now()->format('Y-m-d');
    }

    private function rutaLogo(): ?string
    {
        foreach ([public_path('logo.png'), public_path('imagenes/logo-letra.png')] as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function guardarWord(array $datos, string $ruta): void
    {
        $directorioPhpWord = storage_path('app/temp/phpword');
        File::ensureDirectoryExists($directorioPhpWord, 0775, true);
        Settings::setTempDir($directorioPhpWord);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8);

        $info = $phpWord->getDocInfo();
        $info->setCreator('Centro Universitario Moctezuma');
        $info->setCompany('Centro Universitario Moctezuma');
        $info->setTitle($datos['titulo']);
        $info->setSubject('Lista de alumnos seleccionados');

        $phpWord->addTableStyle('EncabezadoLista', [
            'borderSize' => 0,
            'cellMargin' => 35,
            'alignment' => 'center',
        ]);
        $phpWord->addTableStyle('ResumenLista', [
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 55,
            'alignment' => 'center',
        ]);
        $phpWord->addTableStyle('TablaAlumnos', [
            'borderSize' => 4,
            'borderColor' => '94A3B8',
            'cellMargin' => 45,
            'alignment' => 'center',
        ]);

        $temporalesFotos = [];
        $consecutivo = 1;

        foreach ($datos['grupos'] as $grupo) {
            $section = $phpWord->addSection([
                'paperSize' => 'Letter',
                'orientation' => $datos['orientacion'],
                'marginTop' => 430,
                'marginBottom' => 430,
                'marginLeft' => 520,
                'marginRight' => 520,
            ]);

            $encabezado = $section->addTable('EncabezadoLista');
            $encabezado->addRow();
            $celdaLogo = $encabezado->addCell(1800, ['valign' => 'center']);
            if ($datos['logo'] && is_file($datos['logo'])) {
                $celdaLogo->addImage($datos['logo'], ['width' => 95, 'height' => 48, 'alignment' => 'center']);
            }

            $celdaTitulo = $encabezado->addCell(9000, ['valign' => 'center']);
            $celdaTitulo->addText(
                $datos['escuela']?->nombre ?: 'Centro Universitario Moctezuma',
                ['bold' => true, 'size' => 14, 'color' => '006492'],
                ['alignment' => 'center', 'spaceAfter' => 0]
            );
            $celdaTitulo->addText($datos['titulo'], ['bold' => true, 'size' => 11], ['alignment' => 'center', 'spaceAfter' => 0]);
            $celdaTitulo->addText($grupo['titulo'], ['bold' => true, 'size' => 8, 'color' => '88AC2E'], ['alignment' => 'center', 'spaceAfter' => 0]);
            if ($grupo['cct']) {
                $celdaTitulo->addText('C.C.T. ' . $grupo['cct'], ['bold' => true, 'size' => 7, 'color' => '475569'], ['alignment' => 'center', 'spaceAfter' => 0]);
            }
            if ($datos['direccion_escuela'] !== '') {
                $celdaTitulo->addText($datos['direccion_escuela'], ['size' => 6.5, 'color' => '64748B'], ['alignment' => 'center', 'spaceAfter' => 0]);
            }
            $celdaTitulo->addText('Fecha de emisión: ' . $datos['fecha_emision'], ['size' => 7, 'color' => '64748B'], ['alignment' => 'center']);

            if ($datos['mostrar_estadisticas']) {
                $resumen = $section->addTable('ResumenLista');
                $resumen->addRow();
                foreach ([
                    'Total' => $datos['estadisticas']['total'],
                    'Hombres' => $datos['estadisticas']['hombres'],
                    'Mujeres' => $datos['estadisticas']['mujeres'],
                    'Activos' => $datos['estadisticas']['activos'],
                    'Bajas' => $datos['estadisticas']['bajas'],
                    'Egresados' => $datos['estadisticas']['egresados'],
                ] as $etiqueta => $valor) {
                    $celda = $resumen->addCell(1500, ['bgColor' => 'F8FAFC', 'valign' => 'center']);
                    $celda->addText($etiqueta, ['bold' => true, 'size' => 7, 'color' => '64748B'], ['alignment' => 'center', 'spaceAfter' => 0]);
                    $celda->addText((string) $valor, ['bold' => true, 'size' => 11, 'color' => '0F172A'], ['alignment' => 'center']);
                }
            }

            $section->addTextBreak(1);
            $tabla = $section->addTable('TablaAlumnos');
            $tabla->addRow(360, ['tblHeader' => true, 'cantSplit' => true]);

            $anchos = $this->anchosWord($datos['columnas'], $datos['orientacion']);
            foreach ($datos['columnas'] as $columna) {
                $celda = $tabla->addCell($anchos[$columna], ['bgColor' => '006492', 'valign' => 'center']);
                $celda->addText(
                    self::COLUMNAS[$columna]['label'],
                    ['bold' => true, 'size' => 7, 'color' => 'FFFFFF'],
                    ['alignment' => 'center', 'spaceAfter' => 0]
                );
            }

            foreach ($grupo['alumnos'] as $alumno) {
                $tabla->addRow(in_array('foto', $datos['columnas'], true) ? 650 : 430, ['cantSplit' => true]);

                foreach ($datos['columnas'] as $columna) {
                    $celda = $tabla->addCell($anchos[$columna], ['valign' => 'center']);
                    $alineacion = self::COLUMNAS[$columna]['align'];

                    if ($columna === 'foto') {
                        $foto = $this->rutaFotoWord($alumno, $directorioPhpWord, $temporalesFotos);
                        if ($foto) {
                            $celda->addImage($foto, ['width' => 28, 'height' => 35, 'alignment' => 'center']);
                        }
                        continue;
                    }

                    $valor = $this->valorColumna($columna, $alumno, $consecutivo);
                    $celda->addText($valor, ['size' => 7], ['alignment' => $alineacion, 'spaceAfter' => 0]);
                }

                $consecutivo++;
            }

            if ($datos['responsable'] !== '') {
                $section->addTextBreak(2);
                $section->addText('________________________________________', ['size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $section->addText($datos['responsable'], ['bold' => true, 'size' => 8], ['alignment' => 'center', 'spaceAfter' => 0]);
                $section->addText('Responsable', ['size' => 7, 'color' => '64748B'], ['alignment' => 'center']);
            }
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($ruta);
        File::delete($temporalesFotos);
    }

    private function anchosWord(array $columnas, string $orientacion): array
    {
        $totalDisponible = $orientacion === 'landscape' ? 14200 : 9300;
        $pesoTotal = collect($columnas)->sum(fn (string $columna) => self::COLUMNAS[$columna]['peso']);

        return collect($columnas)->mapWithKeys(fn (string $columna) => [
            $columna => max(450, (int) round($totalDisponible * self::COLUMNAS[$columna]['peso'] / max(1, $pesoTotal))),
        ])->all();
    }

    private function valorColumna(string $columna, Inscripcion $alumno, int $consecutivo): string
    {
        return match ($columna) {
            'numero' => (string) $consecutivo,
            'matricula' => $alumno->matricula ?: '—',
            'folio' => $alumno->folio ?: '—',
            'curp' => $alumno->curp ?: '—',
            'nombre' => $this->nombreCompleto($alumno),
            'sexo' => match ($alumno->genero) { 'H' => 'Hombre', 'M' => 'Mujer', default => '—' },
            'nivel' => $alumno->nivel?->nombre ?? '—',
            'grado' => $this->textoGrado($alumno),
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? '—',
            'generacion' => $this->textoGeneracion($alumno),
            'ciclo' => $alumno->ciclo?->ciclo ?? '—',
            'estatus' => $this->textoEstatus($alumno),
            'firma', 'observaciones', 'foto' => '',
            default => '',
        };
    }


    private function textoEstatus(Inscripcion $alumno): string
    {
        return $alumno->trashed() ? 'Archivado' : $alumno->etiqueta_estatus;
    }

    private function direccionEscuela(?Escuela $escuela): string
    {
        if (! $escuela) {
            return '';
        }

        $calleNumero = trim(implode(' ', array_filter([
            $escuela->calle,
            $escuela->no_exterior ? '#' . $escuela->no_exterior : null,
        ])));

        return collect([
            $calleNumero,
            $escuela->colonia ? 'Col. ' . $escuela->colonia : null,
            $escuela->ciudad,
            $escuela->municipio,
            $escuela->estado,
            $escuela->codigo_postal ? 'C.P. ' . $escuela->codigo_postal : null,
        ])->filter()->implode(', ');
    }

    private function nombreCompleto(Inscripcion $alumno): string
    {
        return mb_strtoupper(trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ]))));
    }

    private function textoGrado(Inscripcion $alumno): string
    {
        if ($alumno->semestre) {
            return $alumno->semestre->numero . '° semestre';
        }

        return $alumno->grado?->nombre ?? 'Sin grado';
    }

    private function textoGeneracion(Inscripcion $alumno): string
    {
        if (! $alumno->generacion) {
            return '—';
        }

        return $alumno->generacion->anio_ingreso . '-' . $alumno->generacion->anio_egreso;
    }

    private function rutaFotoWord(Inscripcion $alumno, string $directorio, array &$temporales): ?string
    {
        if (! $alumno->foto_ruta) {
            return null;
        }

        try {
            $disco = Storage::disk((string) config('filesystems.fotos_disk', 'public'));
            if (! $disco->exists($alumno->foto_ruta)) {
                return null;
            }

            try {
                $ruta = $disco->path($alumno->foto_ruta);
                if (is_file($ruta)) {
                    return $ruta;
                }
            } catch (Throwable) {
                // El disco remoto no ofrece una ruta local; se crea una copia temporal.
            }

            $extension = strtolower(pathinfo($alumno->foto_ruta, PATHINFO_EXTENSION)) ?: 'jpg';
            $rutaTemporal = $directorio . DIRECTORY_SEPARATOR . 'foto-' . $alumno->id . '-' . Str::uuid() . '.' . $extension;
            File::put($rutaTemporal, $disco->get($alumno->foto_ruta));
            $temporales[] = $rutaTemporal;

            return $rutaTemporal;
        } catch (Throwable) {
            return null;
        }
    }
}
