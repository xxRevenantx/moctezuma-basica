<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\LugarPreescolar;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Support\PromedioExcel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CuadroHonorPromediosService
{
    public function __construct(
        private readonly CalificacionOficialPrimariaService $primaria,
        private readonly PromedioSecundariaService $secundaria,
        private readonly PromedioBachilleratoService $bachillerato,
    ) {
    }

    public function generar(
        Nivel $nivel,
        int $cicloEscolarId,
        int $gradoId,
        ?int $generacionId = null,
        ?int $grupoId = null,
        ?int $semestreId = null,
        string $tipoReconocimiento = 'anual',
        int $periodo = 0,
        ?string $fecha = null,
    ): array {
        $ciclo = CicloEscolar::query()->findOrFail($cicloEscolarId);

        $grado = Grado::query()
            ->whereKey($gradoId)
            ->where('nivel_id', $nivel->id)
            ->firstOrFail();

        $generacion = $generacionId
            ? Generacion::query()
                ->whereKey($generacionId)
                ->where('nivel_id', $nivel->id)
                ->firstOrFail()
            : null;

        $grupoSeleccionado = $grupoId
            ? Grupo::query()
                ->with('asignacionGrupo:id,nombre')
                ->whereKey($grupoId)
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
                ->firstOrFail()
            : null;

        $esBachillerato = $nivel->slug === 'bachillerato' || (int) $nivel->id === 4;

        $semestre = $semestreId
            ? Semestre::query()
                ->whereKey($semestreId)
                ->where('grado_id', $grado->id)
                ->firstOrFail()
            : null;

        if ($esBachillerato && ! $semestre) {
            abort(422, 'Selecciona el semestre para generar la lista de bachillerato.');
        }

        if (
            $esBachillerato
            && $grupoSeleccionado
            && (int) ($grupoSeleccionado->semestre_id ?? 0) !== (int) ($semestre?->id ?? 0)
        ) {
            abort(422, 'El grupo seleccionado no pertenece al semestre indicado.');
        }

        $filas = match ($nivel->slug) {
            'preescolar' => $this->filasPreescolar(
                nivel: $nivel,
                cicloEscolarId: $cicloEscolarId,
                gradoId: $gradoId,
                generacionId: $generacionId,
                grupoId: $grupoId,
                tipoReconocimiento: $tipoReconocimiento,
                periodo: $tipoReconocimiento === 'anual' ? 0 : $periodo,
            ),
            'primaria' => $this->filasPrimaria(
                nivel: $nivel,
                cicloEscolarId: $cicloEscolarId,
                gradoId: $gradoId,
                generacionId: $generacionId,
                grupoId: $grupoId,
            ),
            'secundaria' => $this->filasSecundaria(
                nivel: $nivel,
                cicloEscolarId: $cicloEscolarId,
                gradoId: $gradoId,
                generacionId: $generacionId,
                grupoId: $grupoId,
            ),
            default => $esBachillerato
                ? $this->filasBachillerato(
                    nivel: $nivel,
                    cicloEscolarId: $cicloEscolarId,
                    gradoId: $gradoId,
                    generacionId: $generacionId,
                    grupoId: $grupoId,
                    semestreId: $semestreId,
                )
                : collect(),
        };

        if ($filas->isEmpty()) {
            abort(422, 'No hay alumnos disponibles con los filtros seleccionados.');
        }

        if ($nivel->slug !== 'preescolar') {
            $filas = $this->asignarLugaresPorGrupo($filas, $nivel->slug);
        }

        $filas = $this->ordenarFilas($filas, $nivel->slug === 'preescolar');

        $grupos = $filas
            ->groupBy(fn (array $fila) => $this->claveGrupo($fila, $esBachillerato))
            ->map(function (Collection $items) use ($nivel, $cicloEscolarId, $esBachillerato): array {
                $primero = $items->first();
                $promedios = $items
                    ->filter(fn (array $fila) => ($fila['completo'] ?? false) && is_numeric($fila['promedio'] ?? null))
                    ->pluck('promedio');

                return [
                    'grado_id' => (int) ($primero['grado_id'] ?? 0),
                    'grado' => $primero['grado'] ?? 'Sin grado',
                    'grado_orden' => (int) ($primero['grado_orden'] ?? PHP_INT_MAX),
                    'grupo_id' => (int) ($primero['grupo_id'] ?? 0),
                    'grupo' => $primero['grupo'] ?? 'Sin grupo',
                    'semestre_id' => $primero['semestre_id'] ?? null,
                    'semestre' => $primero['semestre'] ?? null,
                    'titulo' => $this->tituloGrupo($primero, $esBachillerato),
                    'total' => $items->count(),
                    'promedio_grupo' => PromedioExcel::formatear(
                        PromedioExcel::calcular($promedios),
                        1,
                        '—'
                    ),
                    'docente' => $this->docenteTitular(
                        nivelId: (int) $nivel->id,
                        gradoId: (int) ($primero['grado_id'] ?? 0),
                        grupoId: (int) ($primero['grupo_id'] ?? 0),
                        cicloEscolarId: $cicloEscolarId,
                    ),
                    'filas' => $items->values(),
                ];
            })
            ->sort(function (array $a, array $b): int {
                $comparacion = $a['grado_orden'] <=> $b['grado_orden'];

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = strnatcasecmp($a['grupo'], $b['grupo']);

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                return (int) ($a['semestre'] ?? PHP_INT_MAX)
                    <=> (int) ($b['semestre'] ?? PHP_INT_MAX);
            })
            ->values();

        $nivel->loadMissing('director');

        return [
            'titulo' => 'CUADRO DE HONOR Y PROMEDIOS GENERALES',
            'nivel' => $nivel,
            'ciclo' => $ciclo,
            'grado' => $grado,
            'generacion' => $generacion,
            'grupo_seleccionado' => $grupoSeleccionado,
            'semestre' => $semestre,
            'es_preescolar' => $nivel->slug === 'preescolar',
            'es_bachillerato' => $esBachillerato,
            'tipo_reconocimiento' => $tipoReconocimiento,
            'periodo' => $tipoReconocimiento === 'anual' ? 0 : $periodo,
            'fecha' => $this->fechaDocumento($fecha),
            'fecha_archivo' => Carbon::parse($fecha ?: now())->format('Y-m-d'),
            'nombre_escuela' => $this->nombreEscuela($nivel),
            'direccion' => 'FRANCISCO I. MADERO OTE. #800, COL. ESQUIPULAS, CD. ALTAMIRANO, GRO.',
            'director' => $this->nombrePersona($nivel->director, 'DIRECCIÓN'),
            'cargo_director' => mb_strtoupper(trim((string) ($nivel->director?->cargo ?: 'DIRECTOR(A) DEL PLANTEL'))),
            'grupos' => $grupos,
            'total_alumnos' => $filas->count(),
        ];
    }

    private function filasPreescolar(
        Nivel $nivel,
        int $cicloEscolarId,
        int $gradoId,
        ?int $generacionId,
        ?int $grupoId,
        string $tipoReconocimiento,
        int $periodo,
    ): Collection {
        $alumnos = Inscripcion::query()
            ->with([
                'grado:id,nombre,orden',
                'grupo.asignacionGrupo:id,nombre',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $gradoId)
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->when($generacionId, fn ($query) => $query->where('generacion_id', $generacionId))
            ->when($grupoId, fn ($query) => $query->where('grupo_id', $grupoId))
            ->get();

        $lugares = LugarPreescolar::query()
            ->whereIn('inscripcion_id', $alumnos->pluck('id'))
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('tipo_reconocimiento', $tipoReconocimiento)
            ->where('periodo', $periodo)
            ->get(['inscripcion_id', 'lugar'])
            ->keyBy('inscripcion_id');

        return $alumnos->map(function (Inscripcion $alumno) use ($lugares): array {
            $lugar = $lugares->get($alumno->id)?->lugar;

            return [
                'inscripcion_id' => (int) $alumno->id,
                'matricula' => (string) ($alumno->matricula ?? ''),
                'alumno' => $this->nombreAlumno($alumno),
                'grado_id' => (int) $alumno->grado_id,
                'grado' => $alumno->grado?->nombre ?? 'Sin grado',
                'grado_orden' => (int) ($alumno->grado?->orden ?? PHP_INT_MAX),
                'grupo_id' => (int) $alumno->grupo_id,
                'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo',
                'semestre_id' => null,
                'semestre' => null,
                'promedio' => null,
                'completo' => $lugar !== null,
                'elegible' => $lugar !== null,
                'lugar' => $lugar !== null ? (int) $lugar : null,
            ];
        })->values();
    }

    private function filasPrimaria(
        Nivel $nivel,
        int $cicloEscolarId,
        int $gradoId,
        ?int $generacionId,
        ?int $grupoId,
    ): Collection {
        $reporte = $this->primaria->reporteAnual(
            nivelId: (int) $nivel->id,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
        );

        return collect($reporte['alumnos'] ?? [])->map(fn (array $fila): array => [
            'inscripcion_id' => (int) $fila['inscripcion_id'],
            'matricula' => (string) ($fila['matricula'] ?? ''),
            'alumno' => (string) ($fila['alumno'] ?? ''),
            'grado_id' => (int) $fila['grado_id'],
            'grado' => (string) ($fila['grado'] ?? 'Sin grado'),
            'grado_orden' => (int) ($fila['grado_orden'] ?? PHP_INT_MAX),
            'grupo_id' => (int) $fila['grupo_id'],
            'grupo' => (string) ($fila['grupo'] ?? 'Sin grupo'),
            'semestre_id' => null,
            'semestre' => null,
            'promedio' => $fila['promedio_general_preciso'] ?? null,
            'promedio_provisional' => $fila['promedio_provisional_preciso'] ?? null,
            'completo' => (bool) ($fila['completo'] ?? false),
            'elegible' => (bool) ($fila['completo'] ?? false)
                && (bool) ($fila['promocion_sugerida'] ?? false),
            'lugar' => null,
        ])->values();
    }

    private function filasSecundaria(
        Nivel $nivel,
        int $cicloEscolarId,
        int $gradoId,
        ?int $generacionId,
        ?int $grupoId,
    ): Collection {
        $reporte = $this->secundaria->reporteAnual(
            nivelId: (int) $nivel->id,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
        );

        return collect($reporte['alumnos'] ?? [])->map(fn (array $fila): array => [
            'inscripcion_id' => (int) $fila['inscripcion_id'],
            'matricula' => (string) ($fila['matricula'] ?? ''),
            'alumno' => (string) ($fila['alumno'] ?? ''),
            'grado_id' => (int) $fila['grado_id'],
            'grado' => (string) ($fila['grado'] ?? 'Sin grado'),
            'grado_orden' => (int) ($fila['grado_orden'] ?? PHP_INT_MAX),
            'grupo_id' => (int) $fila['grupo_id'],
            'grupo' => (string) ($fila['grupo'] ?? 'Sin grupo'),
            'semestre_id' => null,
            'semestre' => null,
            'promedio' => $fila['promedio_general_preciso'] ?? null,
            'promedio_provisional' => $fila['promedio_provisional_preciso'] ?? null,
            'completo' => (bool) ($fila['completo'] ?? false),
            'elegible' => (bool) ($fila['completo'] ?? false)
                && empty($fila['materias_reprobadas'] ?? []),
            'lugar' => null,
        ])->values();
    }

    private function filasBachillerato(
        Nivel $nivel,
        int $cicloEscolarId,
        int $gradoId,
        ?int $generacionId,
        ?int $grupoId,
        ?int $semestreId,
    ): Collection {
        $reporte = $this->bachillerato->reporteSemestral(
            nivelId: (int) $nivel->id,
            cicloEscolarId: $cicloEscolarId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
            semestreId: $semestreId,
        );

        return collect($reporte['alumnos'] ?? [])->map(fn (array $fila): array => [
            'inscripcion_id' => (int) $fila['inscripcion_id'],
            'matricula' => (string) ($fila['matricula'] ?? ''),
            'alumno' => (string) ($fila['alumno'] ?? ''),
            'grado_id' => (int) $fila['grado_id'],
            'grado' => (string) ($fila['grado'] ?? 'Sin grado'),
            'grado_orden' => (int) ($fila['grado_orden'] ?? PHP_INT_MAX),
            'grupo_id' => (int) $fila['grupo_id'],
            'grupo' => (string) ($fila['grupo'] ?? 'Sin grupo'),
            'semestre_id' => isset($fila['semestre_id']) ? (int) $fila['semestre_id'] : null,
            'semestre' => isset($fila['semestre']) ? (int) $fila['semestre'] : null,
            'promedio' => $fila['promedio_general_preciso'] ?? null,
            'promedio_provisional' => $fila['promedio_provisional_preciso'] ?? null,
            'completo' => (bool) ($fila['completo'] ?? false),
            'elegible' => (bool) ($fila['aprobado_general'] ?? false),
            'lugar' => null,
        ])->values();
    }

    private function asignarLugaresPorGrupo(Collection $filas, string $slugNivel): Collection
    {
        $esBachillerato = $slugNivel === 'bachillerato';

        return $filas
            ->groupBy(fn (array $fila) => $this->claveGrupo($fila, $esBachillerato))
            ->flatMap(function (Collection $items): Collection {
                $promediosUnicos = $items
                    ->filter(fn (array $fila) => ($fila['elegible'] ?? false) && is_numeric($fila['promedio'] ?? null))
                    ->sortByDesc('promedio')
                    ->pluck('promedio')
                    ->map(fn ($promedio) => PromedioExcel::claveComparacion($promedio))
                    ->filter()
                    ->unique()
                    ->values();

                return $items->map(function (array $fila) use ($promediosUnicos): array {
                    if (! ($fila['elegible'] ?? false) || ! is_numeric($fila['promedio'] ?? null)) {
                        $fila['lugar'] = null;
                        return $fila;
                    }

                    $indice = $promediosUnicos->search(
                        PromedioExcel::claveComparacion($fila['promedio'])
                    );

                    $fila['lugar'] = $indice !== false ? ((int) $indice) + 1 : null;

                    return $fila;
                });
            })
            ->values();
    }

    private function ordenarFilas(Collection $filas, bool $esPreescolar): Collection
    {
        return $filas->sort(function (array $a, array $b) use ($esPreescolar): int {
            $grupo = strnatcasecmp((string) ($a['grupo'] ?? ''), (string) ($b['grupo'] ?? ''));

            if ($grupo !== 0) {
                return $grupo;
            }

            $categoriaA = $this->categoriaOrden($a, $esPreescolar);
            $categoriaB = $this->categoriaOrden($b, $esPreescolar);

            if ($categoriaA !== $categoriaB) {
                return $categoriaA <=> $categoriaB;
            }

            $lugarA = $a['lugar'] ?? PHP_INT_MAX;
            $lugarB = $b['lugar'] ?? PHP_INT_MAX;

            if ($lugarA !== $lugarB) {
                return $lugarA <=> $lugarB;
            }

            $promedioA = $a['promedio'] ?? $a['promedio_provisional'] ?? -1;
            $promedioB = $b['promedio'] ?? $b['promedio_provisional'] ?? -1;
            $promedio = (float) $promedioB <=> (float) $promedioA;

            return $promedio !== 0
                ? $promedio
                : strnatcasecmp((string) ($a['alumno'] ?? ''), (string) ($b['alumno'] ?? ''));
        })->values();
    }

    private function categoriaOrden(array $fila, bool $esPreescolar): int
    {
        if (is_numeric($fila['lugar'] ?? null)) {
            return 0;
        }

        if ($esPreescolar) {
            return 1;
        }

        return ($fila['completo'] ?? false) ? 1 : 2;
    }

    private function claveGrupo(array $fila, bool $esBachillerato): string
    {
        return implode('|', [
            $fila['grado_id'] ?? 'grado',
            $fila['grupo_id'] ?? 'grupo',
            $esBachillerato ? ($fila['semestre_id'] ?? 'semestre') : 'basica',
        ]);
    }

    private function tituloGrupo(array $fila, bool $esBachillerato): string
    {
        $titulo = ($fila['grado'] ?? 'Sin grado') . ' · Grupo ' . ($fila['grupo'] ?? 'Sin grupo');

        if ($esBachillerato) {
            $titulo .= ' · Semestre ' . ($fila['semestre'] ?? '—');
        }

        return $titulo;
    }

    private function docenteTitular(
        int $nivelId,
        int $gradoId,
        int $grupoId,
        int $cicloEscolarId,
    ): string {
        $persona = null;

        if (
            Schema::hasTable('persona_nivel_detalles')
            && Schema::hasTable('persona_nivel')
            && Schema::hasTable('persona_role')
            && Schema::hasTable('role_personas')
            && Schema::hasTable('personas')
        ) {
            $persona = DB::table('persona_nivel_detalles as pnd')
                ->join('persona_nivel as pn', 'pn.id', '=', 'pnd.persona_nivel_id')
                ->join('persona_role as pr', 'pr.id', '=', 'pnd.persona_role_id')
                ->join('role_personas as rp', 'rp.id', '=', 'pr.role_persona_id')
                ->join('personas as p', 'p.id', '=', 'pr.persona_id')
                ->where('pn.nivel_id', $nivelId)
                ->whereColumn('pn.persona_id', 'pr.persona_id')
                ->where(function ($query): void {
                    $query->whereIn('rp.slug', [
                        'maestro_frente_a_grupo',
                        'docente',
                        'tutor',
                        'director_con_grupo',
                    ])
                        ->orWhere('rp.slug', 'like', 'maestro%')
                        ->orWhere('rp.slug', 'like', 'docente%');
                })
                ->where(function ($query): void {
                    $query->whereNull('p.status')->orWhere('p.status', true);
                })
                ->where(function ($query) use ($gradoId): void {
                    $query->where('pnd.grado_id', $gradoId)->orWhereNull('pnd.grado_id');
                })
                ->where(function ($query) use ($grupoId): void {
                    $query->where('pnd.grupo_id', $grupoId)->orWhereNull('pnd.grupo_id');
                })
                ->orderByRaw(
                    'CASE WHEN pnd.grupo_id = ? THEN 0 WHEN pnd.grupo_id IS NULL THEN 1 ELSE 2 END',
                    [$grupoId]
                )
                ->orderByRaw(
                    'CASE WHEN pnd.grado_id = ? THEN 0 WHEN pnd.grado_id IS NULL THEN 1 ELSE 2 END',
                    [$gradoId]
                )
                ->orderByRaw("CASE
                    WHEN rp.slug = 'maestro_frente_a_grupo' THEN 0
                    WHEN rp.slug = 'tutor' THEN 1
                    WHEN rp.slug = 'director_con_grupo' THEN 2
                    WHEN rp.slug = 'docente' THEN 3
                    WHEN rp.slug LIKE 'maestro%' THEN 4
                    WHEN rp.slug LIKE 'docente%' THEN 5
                    ELSE 6 END")
                ->orderBy('pnd.orden')
                ->select([
                    'p.titulo',
                    'p.nombre',
                    'p.apellido_paterno',
                    'p.apellido_materno',
                ])
                ->first();
        }

        if (! $persona && Schema::hasTable('docente_grupos') && Schema::hasTable('personas')) {
            $query = DB::table('docente_grupos as dg')
                ->join('personas as p', 'p.id', '=', 'dg.persona_id')
                ->where('dg.grupo_id', $grupoId)
                ->where(function ($query): void {
                    $query->whereNull('p.status')->orWhere('p.status', true);
                });

            if (Schema::hasColumn('docente_grupos', 'ciclo_escolar_id')) {
                $query->where('dg.ciclo_escolar_id', $cicloEscolarId);
            }

            if (Schema::hasColumn('docente_grupos', 'es_tutor')) {
                $query->orderByDesc('dg.es_tutor');
            }

            if (Schema::hasColumn('docente_grupos', 'status')) {
                $query->where('dg.status', true);
            }

            $persona = $query
                ->select([
                    'p.titulo',
                    'p.nombre',
                    'p.apellido_paterno',
                    'p.apellido_materno',
                ])
                ->first();
        }

        if (! $persona && Schema::hasTable('asignacion_materias') && Schema::hasTable('personas')) {
            $query = DB::table('asignacion_materias as am')
                ->join('personas as p', 'p.id', '=', 'am.profesor_id')
                ->where('am.grupo_id', $grupoId)
                ->where('am.ciclo_escolar_id', $cicloEscolarId)
                ->whereNotNull('am.profesor_id')
                ->where(function ($query): void {
                    $query->whereNull('p.status')->orWhere('p.status', true);
                });

            if (Schema::hasColumn('asignacion_materias', 'estado')) {
                $query->where(function ($query): void {
                    $query->whereNull('am.estado')->orWhere('am.estado', '!=', 'archivada');
                });
            }

            $persona = $query
                ->groupBy([
                    'p.id',
                    'p.titulo',
                    'p.nombre',
                    'p.apellido_paterno',
                    'p.apellido_materno',
                ])
                ->orderByRaw('COUNT(am.id) DESC')
                ->select([
                    'p.titulo',
                    'p.nombre',
                    'p.apellido_paterno',
                    'p.apellido_materno',
                ])
                ->first();
        }

        return $this->nombrePersona($persona, 'DOCENTE TITULAR');
    }

    private function nombreAlumno(Inscripcion $alumno): string
    {
        return trim(collect([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])->filter()->implode(' '));
    }

    private function nombrePersona(mixed $persona, string $respaldo): string
    {
        if (! $persona) {
            return $respaldo;
        }

        $nombre = trim(collect([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ])->filter()->implode(' '));

        return $nombre !== '' ? mb_strtoupper($nombre) : $respaldo;
    }

    private function fechaDocumento(?string $fecha): string
    {
        try {
            return Carbon::parse($fecha ?: now())
                ->locale('es')
                ->translatedFormat('d \\d\\e F \\d\\e Y');
        } catch (\Throwable) {
            return now()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y');
        }
    }

    private function nombreEscuela(Nivel $nivel): string
    {
        return match ($nivel->slug) {
            'preescolar' => 'JARDÍN DE NIÑOS MOCTEZUMA',
            'primaria' => 'ESCUELA PRIMARIA PARTICULAR MOCTEZUMA',
            'secundaria' => 'ESCUELA SECUNDARIA PARTICULAR MOCTEZUMA',
            'bachillerato' => 'BACHILLERATO GENERAL CENTRO UNIVERSITARIO MOCTEZUMA',
            default => mb_strtoupper((string) $nivel->nombre),
        };
    }
}
