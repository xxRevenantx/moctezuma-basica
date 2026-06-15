<?php

namespace App\Http\Controllers;

use App\Exports\FichaDescriptivaExport;
use App\Models\FichaDescriptiva;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\cicloEscolar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class FichaController extends Controller
{
    public const CAMPOS = [
        'lenguajes' => [
            'label' => 'Lenguajes',
            'descripcion' => 'Está orientado a que niñas y niños adquieran y desarrollen la expresión y la comunicación de sus formas de ser y estar en el mundo mediante la oralidad, escucha, lectura, escritura, sensorialidad, percepción y composición de diversas producciones orales, escritas, sonoras, visuales, corporales o hápticas.',
            'imagen' => 'imagenes/lenguajes.jpg',
        ],
        'saberes' => [
            'label' => 'Saberes y Pensamiento Científico',
            'descripcion' => 'Tiene como finalidad que los estudiantes logren la comprensión necesaria para explicar procesos y fenómenos naturales en su relación con lo social por medio de la indagación, interpretación, experimentación, sistematización, representación con modelos y argumentación.',
            'imagen' => 'imagenes/saberes.jpg',
        ],
        'etica' => [
            'label' => 'Ética, Naturaleza y Sociedades',
            'descripcion' => 'Aborda la relación del ser humano con la sociedad y la naturaleza desde la comprensión crítica de los procesos sociales, políticos, naturales y culturales en diversas comunidades situadas histórica y geográficamente.',
            'imagen' => 'imagenes/etica_naturaleza.jpg',
        ],
        'humano' => [
            'label' => 'De lo Humano y lo Comunitario',
            'descripcion' => 'Tiene como finalidad que niñas y niños construyan su identidad personal y desarrollen sus potencialidades afectivas, motrices, creativas, de interacción y solución de problemas.',
            'imagen' => 'imagenes/humano_comunitario.jpg',
        ],
        'recomendaciones' => [
            'label' => 'Recomendaciones',
            'descripcion' => 'Recomendaciones generales para fortalecer el aprendizaje, la convivencia, la autonomía y el desarrollo integral del alumno.',
            'imagen' => 'imagenes/recomendaciones.jpg',
        ],
    ];

    public function alumnoPdf(Request $request, Inscripcion $inscripcion)
    {
        $periodo = (int) $request->integer('periodo', 1);
        $cicloEscolarId = $request->integer('ciclo_escolar_id') ?: $this->cicloActual()?->id;

        $inscripcion->load([
            'nivel.director',
            'grado',
            'grupo.asignacionGrupo',
            'generacion',
        ]);

        $fichas = FichaDescriptiva::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->when($cicloEscolarId, fn ($q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
            ->where('periodo', $periodo)
            ->get()
            ->keyBy('campo');

        $educadoraNombre = $this->educadoraNombre($inscripcion, $cicloEscolarId);

        $pdf = Pdf::loadView('pdf.ficha_descriptiva_pdf', [
            'alumno' => $inscripcion,
            'periodo' => $periodo,
            'periodoNombre' => $this->nombrePeriodo($periodo),
            'cicloEscolar' => $cicloEscolarId ? cicloEscolar::find($cicloEscolarId) : null,
            'campos' => self::CAMPOS,
            'fichas' => $fichas,
            'fechaLugar' => $request->string('fecha_lugar')->toString(),
            'logoPrincipal' => $this->publicImagePath('logo.png'),
            'logoPenacho' => $this->publicImagePath('imagenes/personajes_preescolar.png') ?: $this->publicImagePath('penacho.jpg'),
            'marcaAgua' => $this->publicImagePath('imagenes/logo-letra.png') ?: $this->publicImagePath('logo.png'),
            'campoImagenes' => $this->campoImagenes(),
            'educadoraNombre' => $educadoraNombre,
            'directoraNombre' => $this->directorNombre($inscripcion),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('ficha-descriptiva-' . $inscripcion->id . '.pdf');
    }

    public function grupoPdf(Request $request)
    {
        $nivel = Nivel::query()->where('slug', 'preescolar')->firstOrFail();

        $periodo = (int) $request->integer('periodo', 1);
        $cicloEscolarId = $request->integer('ciclo_escolar_id') ?: $this->cicloActual()?->id;

        $alumnos = Inscripcion::query()
            ->with([
                'nivel.director',
                'grado',
                'grupo.asignacionGrupo',
                'generacion',
            ])
            ->where('nivel_id', $nivel->id)
            ->when($request->integer('generacion_id'), fn ($q, $id) => $q->where('generacion_id', $id))
            ->when($request->integer('grado_id'), fn ($q, $id) => $q->where('grado_id', $id))
            ->when($request->integer('grupo_id'), fn ($q, $id) => $q->where('grupo_id', $id))
            ->where('activo', true)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $fichas = FichaDescriptiva::query()
            ->whereIn('inscripcion_id', $alumnos->pluck('id'))
            ->when($cicloEscolarId, fn ($q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
            ->where('periodo', $periodo)
            ->get()
            ->groupBy('inscripcion_id')
            ->map(fn ($items) => $items->keyBy('campo'));

        $alumnoBase = $alumnos->first();

        $educadoraNombre = $alumnoBase
            ? $this->educadoraNombre($alumnoBase, $cicloEscolarId)
            : 'EDUCADORA';

        $pdf = Pdf::loadView('pdf.fichas_descriptivas_grupo_pdf', [
            'alumnos' => $alumnos,
            'periodo' => $periodo,
            'periodoNombre' => $this->nombrePeriodo($periodo),
            'cicloEscolar' => $cicloEscolarId ? cicloEscolar::find($cicloEscolarId) : null,
            'campos' => self::CAMPOS,
            'fichas' => $fichas,
            'fechaLugar' => $request->string('fecha_lugar')->toString(),
            'logoPrincipal' => $this->publicImagePath('logo.png'),
            'logoPenacho' => $this->publicImagePath('imagenes/personajes_preescolar.png') ?: $this->publicImagePath('penacho.jpg'),
            'marcaAgua' => $this->publicImagePath('imagenes/logo-letra.png') ?: $this->publicImagePath('logo.png'),
            'campoImagenes' => $this->campoImagenes(),
            'educadoraNombre' => $educadoraNombre,
            'directoraNombre' => $nivel->director ? $this->nombreCompletoDirector($nivel->director) : 'DIRECCIÓN',
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('fichas-descriptivas-preescolar.pdf');
    }

    public function excel(Request $request)
    {
        $nivel = Nivel::query()->where('slug', 'preescolar')->firstOrFail();

        $periodo = (int) $request->integer('periodo', 1);
        $cicloEscolarId = $request->integer('ciclo_escolar_id') ?: $this->cicloActual()?->id;

        return Excel::download(
            new FichaDescriptivaExport(
                $nivel->id,
                $request->integer('generacion_id') ?: null,
                $request->integer('grado_id') ?: null,
                $request->integer('grupo_id') ?: null,
                $cicloEscolarId,
                $periodo,
                self::CAMPOS
            ),
            'fichas-descriptivas-preescolar.xlsx'
        );
    }

    private function cicloActual(): ?cicloEscolar
    {
        return cicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->first();
    }

    private function nombrePeriodo(int $periodo): string
    {
        return match ($periodo) {
            1 => 'Primera Evaluación Diagnóstica',
            2 => 'Segunda Evaluación',
            3 => 'Tercera Evaluación',
            default => 'Evaluación ' . $periodo,
        };
    }

    private function publicImagePath(string $path): ?string
    {
        $fullPath = public_path($path);

        return file_exists($fullPath) ? $fullPath : null;
    }

    private function campoImagenes(): array
    {
        return [
            'lenguajes' => $this->firstExistingImage([
                'imagenes/campos_formativos/lenguajes.png',
                'imagenes/campos_formativos/lenguajes.jpg',
                'imagenes/lenguajes.png',
                'imagenes/lenguajes.jpg',
            ]),
            'saberes' => $this->firstExistingImage([
                'imagenes/campos_formativos/saberes.png',
                'imagenes/campos_formativos/saberes.jpg',
                'imagenes/saberes.png',
                'imagenes/saberes.jpg',
            ]),
            'etica' => $this->firstExistingImage([
                'imagenes/campos_formativos/etica.png',
                'imagenes/campos_formativos/etica.jpg',
                'imagenes/etica.png',
                'imagenes/etica.jpg',
                'imagenes/etica_naturaleza.png',
                'imagenes/etica_naturaleza.jpg',
            ]),
            'humano' => $this->firstExistingImage([
                'imagenes/campos_formativos/humano.png',
                'imagenes/campos_formativos/humano.jpg',
                'imagenes/humano.png',
                'imagenes/humano.jpg',
                'imagenes/humano_comunitario.png',
                'imagenes/humano_comunitario.jpg',
            ]),
        ];
    }

    private function firstExistingImage(array $paths): ?string
    {
        foreach ($paths as $path) {
            $fullPath = public_path($path);

            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    private function educadoraNombre(Inscripcion $inscripcion, ?int $cicloEscolarId = null): string
    {
        $educadora = $this->obtenerEducadoraDesdePersonaNivelDetalle(
            nivelId: (int) $inscripcion->nivel_id,
            gradoId: $inscripcion->grado_id ? (int) $inscripcion->grado_id : null,
            grupoId: $inscripcion->grupo_id ? (int) $inscripcion->grupo_id : null,
        );

        if ($educadora) {
            return $educadora;
        }

        $docenteGrupo = $this->docenteGrupoNombre($inscripcion, $cicloEscolarId);

        return $docenteGrupo ?: 'EDUCADORA';
    }

    private function obtenerEducadoraDesdePersonaNivelDetalle(
        int $nivelId,
        ?int $gradoId = null,
        ?int $grupoId = null
    ): ?string {
        if (
            !Schema::hasTable('persona_nivel_detalles') ||
            !Schema::hasTable('persona_nivel') ||
            !Schema::hasTable('persona_role') ||
            !Schema::hasTable('role_personas') ||
            !Schema::hasTable('personas')
        ) {
            return null;
        }

        $query = DB::table('persona_nivel_detalles as pnd')
            ->join('persona_nivel as pn', 'pn.id', '=', 'pnd.persona_nivel_id')
            ->join('persona_role as pr', 'pr.id', '=', 'pnd.persona_role_id')
            ->join('role_personas as rp', 'rp.id', '=', 'pr.role_persona_id')
            ->join('personas as p', 'p.id', '=', 'pr.persona_id')
            ->where('pn.nivel_id', $nivelId)
            ->whereColumn('pn.persona_id', 'pr.persona_id')
            ->where(function ($q) {
                $q->whereNull('p.status')
                    ->orWhere('p.status', true);
            })
            ->where(function ($q) {
                $q->whereNull('rp.status')
                    ->orWhere('rp.status', true);
            })
            ->whereIn('rp.slug', [
                'maestro_frente_a_grupo',
                'docente',
                'director_con_grupo',
            ]);

        if ($gradoId) {
            $query->where(function ($q) use ($gradoId) {
                $q->where('pnd.grado_id', $gradoId)
                    ->orWhereNull('pnd.grado_id');
            });
        }

        if ($grupoId) {
            $query->where(function ($q) use ($grupoId) {
                $q->where('pnd.grupo_id', $grupoId)
                    ->orWhereNull('pnd.grupo_id');
            });
        }

        $persona = $query
            ->orderByRaw(
                'CASE
                    WHEN pnd.grupo_id = ? THEN 0
                    WHEN pnd.grupo_id IS NULL THEN 1
                    ELSE 2
                END',
                [$grupoId ?? 0]
            )
            ->orderByRaw(
                'CASE
                    WHEN pnd.grado_id = ? THEN 0
                    WHEN pnd.grado_id IS NULL THEN 1
                    ELSE 2
                END',
                [$gradoId ?? 0]
            )
            ->orderByRaw(
                "CASE rp.slug
                    WHEN 'maestro_frente_a_grupo' THEN 0
                    WHEN 'director_con_grupo' THEN 1
                    WHEN 'docente' THEN 2
                    ELSE 3
                END"
            )
            ->orderBy('pnd.orden')
            ->orderBy('pn.orden')
            ->select([
                'p.titulo',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
            ])
            ->first();

        if (!$persona) {
            return null;
        }

        $nombre = collect([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ])
            ->filter(fn ($valor) => filled($valor))
            ->implode(' ');

        return trim(mb_strtoupper($nombre));
    }

    private function docenteGrupoNombre(Inscripcion $inscripcion, ?int $cicloEscolarId = null): ?string
    {
        if (!$inscripcion->grupo_id) {
            return null;
        }

        $tabla = null;

        if (Schema::hasTable('docente_grupos')) {
            $tabla = 'docente_grupos';
        } elseif (Schema::hasTable('docente_grupo')) {
            $tabla = 'docente_grupo';
        }

        if (!$tabla || !Schema::hasTable('personas')) {
            return null;
        }

        $query = DB::table($tabla)
            ->join('personas', 'personas.id', '=', $tabla . '.persona_id')
            ->where($tabla . '.grupo_id', $inscripcion->grupo_id)
            ->where(function ($q) {
                $q->whereNull('personas.status')
                    ->orWhere('personas.status', true);
            });

        if ($cicloEscolarId && Schema::hasColumn($tabla, 'ciclo_escolar_id')) {
            $query->where(function ($q) use ($tabla, $cicloEscolarId) {
                $q->where($tabla . '.ciclo_escolar_id', $cicloEscolarId)
                    ->orWhereNull($tabla . '.ciclo_escolar_id');
            });
        }

        if (Schema::hasColumn($tabla, 'es_tutor')) {
            $query->orderByDesc($tabla . '.es_tutor');
        }

        $persona = $query
            ->select([
                'personas.titulo',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.apellido_materno',
            ])
            ->first();

        if (!$persona) {
            return null;
        }

        return trim(mb_strtoupper(collect([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' ')));
    }

    private function directorNombre(Inscripcion $inscripcion): string
    {
        $director = $inscripcion->nivel?->director;

        return $director ? $this->nombreCompletoDirector($director) : 'DIRECCIÓN';
    }

    private function nombreCompletoDirector($director): string
    {
        return trim(mb_strtoupper(collect([
            $director->titulo ?? null,
            $director->nombre ?? null,
            $director->apellido_paterno ?? null,
            $director->apellido_materno ?? null,
        ])->filter()->implode(' ')));
    }
}
