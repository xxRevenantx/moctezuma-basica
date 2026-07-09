<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromediosTresPeriodosService
{
    public function generar(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
    ): array {
        $nivel = Nivel::query()->select('id', 'nombre', 'slug')->findOrFail($nivelId);
        $ciclo = CicloEscolar::query()
            ->select('id', 'inicio_anio', 'fin_anio', 'es_actual')
            ->findOrFail($cicloEscolarId);

        $esBachillerato = $nivel->slug === 'bachillerato';
        $filas = $this->consultarCalificaciones(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            esBachillerato: $esBachillerato,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
        );

        $esperados = $this->evaluacionesEsperadas($nivelId, $cicloEscolarId, $esBachillerato);
        $detalles = $this->construirDetallesMateria($filas, $esperados, $esBachillerato);
        $alumnos = $this->construirAlumnos($detalles, $nivelId, $esBachillerato);
        $grupos = $this->construirGrupos($alumnos);
        $bloques = $this->construirBloques($grupos, $esBachillerato);
        $resumen = $this->construirResumen($alumnos, $grupos, $bloques);

        return [
            'nivel' => [
                'id' => $nivel->id,
                'nombre' => $nivel->nombre,
                'slug' => $nivel->slug,
            ],
            'ciclo' => [
                'id' => $ciclo->id,
                'texto' => $ciclo->inicio_anio . '-' . $ciclo->fin_anio,
                'es_actual' => (bool) $ciclo->es_actual,
            ],
            'es_bachillerato' => $esBachillerato,
            'etiqueta_evaluaciones' => $esBachillerato ? 'Parciales' : 'Periodos',
            'resumen' => $resumen,
            'bloques' => $bloques,
            'grupos' => $grupos,
            'alumnos' => $alumnos,
            'campos' => $this->catalogoCampos(),
            'nota' => $esBachillerato
                ? 'NOTA: En bachillerato cada parcial se usa como entero truncado, el promedio de materia también es entero truncado y el promedio semestral conserva un decimal.'
                : 'NOTA: Se consideran las evaluaciones de los tres periodos. Las calificaciones y promedios se expresan con un decimal.',
        ];
    }

    private function consultarCalificaciones(
        int $nivelId,
        int $cicloEscolarId,
        bool $esBachillerato,
        ?int $generacionId,
        ?int $gradoId,
        ?int $grupoId,
    ): Collection {
        $numeroEvaluacion = $esBachillerato
            ? 'COALESCE(parciales.parcial, periodos.parcial_bachillerato_id)'
            : 'periodos.periodo_basica_id';

        return DB::table('calificaciones')
            ->join('periodos', 'periodos.id', '=', 'calificaciones.periodo_id')
            ->join('inscripciones', 'inscripciones.id', '=', 'calificaciones.inscripcion_id')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->join('grados', 'grados.id', '=', 'calificaciones.grado_id')
            ->join('grupos', 'grupos.id', '=', 'calificaciones.grupo_id')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->leftJoin('generaciones', 'generaciones.id', '=', 'calificaciones.generacion_id')
            ->leftJoin('semestres', 'semestres.id', '=', 'calificaciones.semestre_id')
            ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
            ->leftJoin('campos_formativos', 'campos_formativos.id', '=', 'materias.campo_formativo_id')
            ->whereNull('inscripciones.deleted_at')
            ->where('calificaciones.nivel_id', $nivelId)
            ->where('calificaciones.ciclo_escolar_id', $cicloEscolarId)
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->when(
                $esBachillerato,
                fn ($query) => $query->whereNotNull('periodos.parcial_bachillerato_id'),
                fn ($query) => $query->whereIn('periodos.periodo_basica_id', [1, 2, 3])
            )
            ->when($generacionId, fn ($query) => $query->where('calificaciones.generacion_id', $generacionId))
            ->when($gradoId, fn ($query) => $query->where('calificaciones.grado_id', $gradoId))
            ->when($grupoId, fn ($query) => $query->where('calificaciones.grupo_id', $grupoId))
            ->selectRaw("\n                calificaciones.id as calificacion_id,\n                calificaciones.inscripcion_id,\n                calificaciones.generacion_id,\n                calificaciones.grado_id,\n                calificaciones.grupo_id,\n                calificaciones.semestre_id,\n                calificaciones.calificacion,\n                calificaciones.valor_numerico,\n                calificaciones.es_numerica,\n                calificaciones.clave_especial,\n                calificaciones.fuente,\n                inscripciones.matricula,\n                inscripciones.nombre,\n                inscripciones.apellido_paterno,\n                inscripciones.apellido_materno,\n                grados.nombre as grado_nombre,\n                grados.orden as grado_orden,\n                asignacion_grupos.nombre as grupo_nombre,\n                generaciones.anio_ingreso,\n                generaciones.anio_egreso,\n                semestres.numero as semestre_numero,\n                materias.id as materia_id,\n                materias.materia as materia_nombre,\n                COALESCE(asignacion_materias.orden, materias.orden, 999) as materia_orden,\n                campos_formativos.id as campo_formativo_id,\n                COALESCE(campos_formativos.nombre, 'Sin campo formativo') as campo_formativo_nombre,\n                COALESCE(campos_formativos.slug, 'sin-campo-formativo') as campo_formativo_slug,\n                COALESCE(campos_formativos.color_fondo, '#E2E8F0') as campo_color_fondo,\n                COALESCE(campos_formativos.color_texto, '#334155') as campo_color_texto,\n                COALESCE(campos_formativos.orden, 99) as campo_orden,\n                {$numeroEvaluacion} as numero_evaluacion\n            ")
            ->orderBy('grados.orden')
            ->orderBy('semestres.numero')
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->orderByRaw('COALESCE(campos_formativos.orden, 99)')
            ->orderByRaw('COALESCE(asignacion_materias.orden, materias.orden, 999)')
            ->orderBy('materias.materia')
            ->orderBy('calificaciones.id')
            ->get();
    }

    private function evaluacionesEsperadas(int $nivelId, int $cicloEscolarId, bool $esBachillerato): array
    {
        if (! $esBachillerato) {
            return ['general' => [1, 2, 3]];
        }

        return DB::table('periodos')
            ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
            ->where('periodos.nivel_id', $nivelId)
            ->where('periodos.ciclo_escolar_id', $cicloEscolarId)
            ->whereNotNull('periodos.semestre_id')
            ->whereNotNull('periodos.parcial_bachillerato_id')
            ->selectRaw('periodos.semestre_id, COALESCE(parciales.parcial, periodos.parcial_bachillerato_id) as numero')
            ->get()
            ->groupBy('semestre_id')
            ->map(fn (Collection $items) => $items->pluck('numero')->map(fn ($numero) => (int) $numero)->unique()->sort()->values()->all())
            ->all();
    }

    private function construirDetallesMateria(Collection $filas, array $esperados, bool $esBachillerato): Collection
    {
        return $filas
            ->groupBy(fn ($fila): string => implode('|', [
                $fila->grado_id,
                $fila->grupo_id,
                $fila->semestre_id ?: 0,
                $fila->inscripcion_id,
                $fila->materia_id,
            ]))
            ->map(function (Collection $registros) use ($esperados, $esBachillerato): array {
                $primero = $registros->first();
                $clavesEsperadas = $esBachillerato
                    ? ($esperados[(int) ($primero->semestre_id ?? 0)] ?? [])
                    : ($esperados['general'] ?? [1, 2, 3]);

                if ($clavesEsperadas === []) {
                    $clavesEsperadas = $registros
                        ->pluck('numero_evaluacion')
                        ->filter()
                        ->map(fn ($numero) => (int) $numero)
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();
                }

                $evaluaciones = [];
                $fuentes = [];
                $especiales = [];

                foreach ($clavesEsperadas as $clave) {
                    $registro = $registros
                        ->filter(fn ($item) => (int) $item->numero_evaluacion === (int) $clave)
                        ->sortBy('calificacion_id')
                        ->last();

                    $esNumerica = $registro
                        && (bool) $registro->es_numerica
                        && is_numeric($registro->valor_numerico)
                        && (float) $registro->valor_numerico >= 0
                        && (float) $registro->valor_numerico <= 10;

                    $evaluaciones[(int) $clave] = $esNumerica
                        ? ($esBachillerato
                            ? CalificacionBachillerato::truncarParcial($registro->valor_numerico)
                            : (float) $registro->valor_numerico)
                        : null;

                    if ($registro) {
                        $fuentes[(int) $clave] = $registro->fuente ?: 'interna';
                        $especial = trim((string) ($registro->clave_especial ?: $registro->calificacion));

                        if (! $esNumerica && $especial !== '') {
                            $especiales[(int) $clave] = mb_strtoupper($especial);
                        }
                    }
                }

                $valores = collect($evaluaciones)
                    ->filter(fn ($valor) => $valor !== null)
                    ->map(fn ($valor) => (float) $valor)
                    ->values();

                $capturadas = $valores->count();
                $esperadas = count($clavesEsperadas);
                $promedioCalculo = $esBachillerato
                    ? CalificacionBachillerato::promedioMateria($valores)
                    : PromedioExcel::calcular($valores);
                $promedio = $esBachillerato
                    ? $promedioCalculo
                    : PromedioExcel::truncar($promedioCalculo);
                $completo = $esperadas > 0 && $capturadas === $esperadas;

                return [
                    'inscripcion_id' => (int) $primero->inscripcion_id,
                    'generacion_id' => $primero->generacion_id ? (int) $primero->generacion_id : null,
                    'matricula' => $primero->matricula ?: '—',
                    'alumno' => trim(implode(' ', array_filter([
                        $primero->apellido_paterno,
                        $primero->apellido_materno,
                        $primero->nombre,
                    ]))),
                    'grado_id' => (int) $primero->grado_id,
                    'grado' => $primero->grado_nombre ?: 'Sin grado',
                    'grado_orden' => (int) ($primero->grado_orden ?? 999),
                    'grupo_id' => (int) $primero->grupo_id,
                    'grupo' => $primero->grupo_nombre ?: 'Sin grupo',
                    'generacion' => $primero->anio_ingreso && $primero->anio_egreso
                        ? $primero->anio_ingreso . '-' . $primero->anio_egreso
                        : 'Sin generación',
                    'semestre_id' => $primero->semestre_id ? (int) $primero->semestre_id : null,
                    'semestre' => $primero->semestre_numero ? (int) $primero->semestre_numero : null,
                    'materia_id' => (int) $primero->materia_id,
                    'materia' => $primero->materia_nombre ?: 'Sin materia',
                    'materia_orden' => (int) ($primero->materia_orden ?? 999),
                    'participa_en_calificacion_oficial' => (bool) ($primero->participa_en_calificacion_oficial ?? true),
                    'campo_formativo_id' => $primero->campo_formativo_id ? (int) $primero->campo_formativo_id : null,
                    'campo_formativo' => $primero->campo_formativo_nombre,
                    'campo_slug' => $primero->campo_formativo_slug,
                    'campo_color_fondo' => $primero->campo_color_fondo,
                    'campo_color_texto' => $primero->campo_color_texto,
                    'campo_orden' => (int) $primero->campo_orden,
                    'evaluaciones_esperadas' => array_map('intval', $clavesEsperadas),
                    'evaluaciones' => $evaluaciones,
                    'fuentes' => $fuentes,
                    'especiales' => $especiales,
                    'valores_numericos' => $valores->all(),
                    'suma' => (float) $valores->sum(),
                    'promedio_calculo' => $promedioCalculo,
                    'capturadas' => $capturadas,
                    'faltantes' => max($esperadas - $capturadas, 0),
                    'promedio' => $promedio,
                    'completo' => $completo,
                    'provisional' => ! $completo,
                    'tiene_calificacion_externa' => collect($fuentes)->contains(fn ($fuente) => $fuente === 'externa'),
                ];
            })
            ->values();
    }

    private function construirAlumnos(Collection $detalles, int $nivelId, bool $esBachillerato): Collection
    {
        $configuraciones = MateriaPromediar::query()
            ->where('nivel_id', $nivelId)
            ->get()
            ->keyBy(fn ($item): string => $item->grado_id . '|' . ($item->semestre_id ?: 0));

        return $detalles
            ->groupBy(fn (array $detalle): string => implode('|', [
                $detalle['grado_id'],
                $detalle['grupo_id'],
                $detalle['semestre_id'] ?: 0,
                $detalle['inscripcion_id'],
            ]))
            ->map(function (Collection $materias) use ($configuraciones, $esBachillerato): array {
                $primero = $materias->first();
                $materias = $materias
                    ->sortBy([
                        ['campo_orden', 'asc'],
                        ['materia_orden', 'asc'],
                        ['materia', 'asc'],
                    ])
                    ->values();

                $materiasParticipantes = $materias
                    ->filter(fn (array $materia) => (bool) ($materia['participa_en_calificacion_oficial'] ?? true))
                    ->values();

                $promedios = $materiasParticipantes
                    ->pluck('promedio_calculo')
                    ->filter(fn ($valor) => $valor !== null)
                    ->map(fn ($valor) => (float) $valor)
                    ->values();

                $promedioGeneralCalculo = $esBachillerato
                    ? CalificacionBachillerato::promedioSemestral($promedios)
                    : PromedioExcel::calcular($promedios);

                $claveConfig = $primero['grado_id'] . '|' . ($esBachillerato ? ($primero['semestre_id'] ?: 0) : 0);
                $numeroConfigurado = (int) ($configuraciones->get($claveConfig)?->numero_materias ?? 0);
                // En primaria y secundaria el catálogo oficial es dinámico por grado.
                // MateriaPromediar se conserva únicamente para bachillerato, donde
                // la configuración histórica por semestre sí sigue siendo útil.
                $numeroEsperado = $esBachillerato && $numeroConfigurado > 0
                    ? $numeroConfigurado
                    : $materiasParticipantes->count();
                $completo = $materiasParticipantes->isNotEmpty()
                    && $materiasParticipantes->every(fn (array $materia) => $materia['completo'] === true)
                    && $promedios->count() >= $numeroEsperado;

                return [
                    'inscripcion_id' => $primero['inscripcion_id'],
                    'generacion_id' => $primero['generacion_id'],
                    'matricula' => $primero['matricula'],
                    'alumno' => $primero['alumno'],
                    'grado_id' => $primero['grado_id'],
                    'grado' => $primero['grado'],
                    'grado_orden' => $primero['grado_orden'],
                    'grupo_id' => $primero['grupo_id'],
                    'grupo' => $primero['grupo'],
                    'generacion' => $primero['generacion'],
                    'semestre_id' => $primero['semestre_id'],
                    'semestre' => $primero['semestre'],
                    'materias' => $materias,
                    'materias_esperadas' => $numeroEsperado,
                    'materias_capturadas' => $promedios->count(),
                    'promedio_general_calculo' => $promedioGeneralCalculo,
                    'promedio_general' => PromedioExcel::truncar($promedioGeneralCalculo),
                    'completo' => $completo,
                    'provisional' => ! $completo,
                ];
            })
            ->sortBy([
                ['grado_orden', 'asc'],
                ['semestre', 'asc'],
                ['grupo', 'asc'],
                ['alumno', 'asc'],
            ])
            ->values();
    }

    private function construirGrupos(Collection $alumnos): Collection
    {
        return $alumnos
            ->groupBy(fn (array $alumno): string => implode('|', [
                $alumno['grado_id'],
                $alumno['grupo_id'],
                $alumno['semestre_id'] ?: 0,
            ]))
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $materias = $this->agruparMaterias($items->flatMap(fn (array $alumno) => $alumno['materias']));
                $promedios = $items->pluck('promedio_general_calculo')->filter(fn ($valor) => $valor !== null)->map(fn ($valor) => (float) $valor);
                $promedioGeneralCalculo = PromedioExcel::calcular($promedios);

                return [
                    'grado_id' => $primero['grado_id'],
                    'grado' => $primero['grado'],
                    'grado_orden' => $primero['grado_orden'],
                    'grupo_id' => $primero['grupo_id'],
                    'grupo' => $primero['grupo'],
                    'generacion' => $primero['generacion'],
                    'semestre_id' => $primero['semestre_id'],
                    'semestre' => $primero['semestre'],
                    'titulo' => $primero['grado'] . ' · Grupo ' . $primero['grupo']
                        . ($primero['semestre'] ? ' · Semestre ' . $primero['semestre'] : ''),
                    'total_alumnos' => $items->count(),
                    'promedio_general_calculo' => $promedioGeneralCalculo,
                    'promedio_general' => PromedioExcel::truncar($promedioGeneralCalculo),
                    'provisional' => $items->contains(fn (array $alumno) => $alumno['provisional']),
                    'materias' => $materias,
                    'alumnos' => $items->values(),
                ];
            })
            ->sortBy([
                ['grado_orden', 'asc'],
                ['semestre', 'asc'],
                ['grupo', 'asc'],
            ])
            ->values();
    }

    private function construirBloques(Collection $grupos, bool $esBachillerato): Collection
    {
        return $grupos
            ->groupBy(fn (array $grupo): string => $esBachillerato
                ? 'semestre|' . ($grupo['semestre_id'] ?: 0)
                : 'grado|' . $grupo['grado_id'])
            ->map(function (Collection $items) use ($esBachillerato): array {
                $primero = $items->first();
                $alumnos = $items->flatMap(fn (array $grupo) => $grupo['alumnos'])->unique('inscripcion_id')->values();
                $materias = $this->agruparMaterias($alumnos->flatMap(fn (array $alumno) => $alumno['materias']));
                $promedios = $alumnos->pluck('promedio_general_calculo')->filter(fn ($valor) => $valor !== null)->map(fn ($valor) => (float) $valor);
                $promedioGeneralCalculo = PromedioExcel::calcular($promedios);

                $campos = $materias
                    ->groupBy('campo_slug')
                    ->map(function (Collection $itemsCampo): array {
                        $primera = $itemsCampo->first();

                        return [
                            'id' => $primera['campo_formativo_id'],
                            'nombre' => $primera['campo_formativo'],
                            'slug' => $primera['campo_slug'],
                            'color_fondo' => $primera['campo_color_fondo'],
                            'color_texto' => $primera['campo_color_texto'],
                            'orden' => $primera['campo_orden'],
                            'colspan' => $itemsCampo->count(),
                            'materias' => $itemsCampo->values(),
                        ];
                    })
                    ->sortBy('orden')
                    ->values();

                return [
                    'clave' => $esBachillerato
                        ? 'semestre-' . ($primero['semestre_id'] ?: 0)
                        : 'grado-' . $primero['grado_id'],
                    'grado_id' => $primero['grado_id'],
                    'grado' => $primero['grado'],
                    'grado_orden' => $primero['grado_orden'],
                    'semestre_id' => $primero['semestre_id'],
                    'semestre' => $primero['semestre'],
                    'titulo' => $esBachillerato
                        ? $this->ordinal((int) ($primero['semestre'] ?? 0)) . ' SEMESTRE'
                        : mb_strtoupper($primero['grado']),
                    'total_grupos' => $items->count(),
                    'total_alumnos' => $alumnos->count(),
                    'promedio_general_calculo' => $promedioGeneralCalculo,
                    'promedio_general' => PromedioExcel::truncar($promedioGeneralCalculo),
                    'provisional' => $alumnos->contains(fn (array $alumno) => $alumno['provisional']),
                    'materias' => $materias,
                    'campos' => $campos,
                    'grupos' => $items->values(),
                ];
            })
            ->sortBy(fn (array $bloque) => $esBachillerato
                ? ($bloque['semestre'] ?? 999)
                : ($bloque['grado_orden'] ?? 999))
            ->values();
    }

    private function agruparMaterias(Collection $detalles): Collection
    {
        return $detalles
            ->groupBy('materia_id')
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $promediosAlumnos = $items->pluck('promedio_calculo')->filter(fn ($valor) => $valor !== null)->map(fn ($valor) => (float) $valor)->values();
                $valores = $items->flatMap(fn (array $materia) => $materia['valores_numericos'])->map(fn ($valor) => (float) $valor)->values();
                $promedioMetodoACalculo = PromedioExcel::calcular($promediosAlumnos);
                $promedioMetodoBCalculo = PromedioExcel::calcular($valores);

                return [
                    'materia_id' => $primero['materia_id'],
                    'materia' => $primero['materia'],
                    'materia_orden' => $primero['materia_orden'],
                    'campo_formativo_id' => $primero['campo_formativo_id'],
                    'campo_formativo' => $primero['campo_formativo'],
                    'campo_slug' => $primero['campo_slug'],
                    'campo_color_fondo' => $primero['campo_color_fondo'],
                    'campo_color_texto' => $primero['campo_color_texto'],
                    'campo_orden' => $primero['campo_orden'],
                    'promedio_metodo_a_calculo' => $promedioMetodoACalculo,
                    'promedio_metodo_b_calculo' => $promedioMetodoBCalculo,
                    'promedio_metodo_a' => PromedioExcel::truncar($promedioMetodoACalculo),
                    'promedio_metodo_b' => PromedioExcel::truncar($promedioMetodoBCalculo),
                    'promedio_calculo' => $promedioMetodoACalculo,
                    'promedio' => PromedioExcel::truncar($promedioMetodoACalculo),
                    'total_alumnos' => $items->pluck('inscripcion_id')->unique()->count(),
                    'total_registros' => $valores->count(),
                    'provisional' => $items->contains(fn (array $materia) => $materia['provisional']),
                ];
            })
            ->sortBy([
                ['campo_orden', 'asc'],
                ['materia_orden', 'asc'],
                ['materia', 'asc'],
            ])
            ->values();
    }

    private function construirResumen(Collection $alumnos, Collection $grupos, Collection $bloques): array
    {
        $promedios = $alumnos->pluck('promedio_general_calculo')->filter(fn ($valor) => $valor !== null)->map(fn ($valor) => (float) $valor);
        $promedioGeneralCalculo = PromedioExcel::calcular($promedios);

        return [
            'turno' => 'MATUTINO',
            'total_alumnos' => $alumnos->count(),
            'total_grupos' => $grupos->count(),
            'total_bloques' => $bloques->count(),
            'total_materias' => $bloques->flatMap(fn (array $bloque) => $bloque['materias'])->pluck('materia_id')->unique()->count(),
            'promedio_general_calculo' => $promedioGeneralCalculo,
            'promedio_general' => PromedioExcel::truncar($promedioGeneralCalculo),
            'alumnos_completos' => $alumnos->where('completo', true)->count(),
            'alumnos_provisionales' => $alumnos->where('provisional', true)->count(),
            'provisional' => $alumnos->contains(fn (array $alumno) => $alumno['provisional']),
        ];
    }

    private function catalogoCampos(): Collection
    {
        return DB::table('campos_formativos')
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug', 'color_fondo', 'color_texto', 'orden']);
    }

    private function ordinal(int $numero): string
    {
        return match ($numero) {
            1 => 'PRIMER',
            2 => 'SEGUNDO',
            3 => 'TERCER',
            4 => 'CUARTO',
            5 => 'QUINTO',
            6 => 'SEXTO',
            default => (string) $numero . '°',
        };
    }
}
