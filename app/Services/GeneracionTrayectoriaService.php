<?php

namespace App\Services;

use App\Models\Generacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeneracionTrayectoriaService
{
    /**
     * Agrega a la consulta de generaciones métricas que proceden del historial
     * por ciclo. Los conteos no dependen de la ubicación actual del alumno.
     */
    public function agregarResumenes(Builder $query, ?int $cicloEscolarId = null): Builder
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            return $query;
        }

        $resultado = $this->expresionResultado('ic');

        $query->addSelect([
            'alumnos_historicos_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                'COUNT(DISTINCT ic.inscripcion_id)'
            ),
            'ciclos_historicos_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                'COUNT(DISTINCT ic.ciclo_escolar_id)'
            ),
            'alumnos_en_curso_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN ic.estado = 'en_curso' THEN ic.inscripcion_id END)"
            ),
            'alumnos_promovidos_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN ic.promovido = 1 OR {$resultado} IN ('promovido','promovido_grado','promovido_nivel','continuidad') THEN ic.inscripcion_id END)"
            ),
            'alumnos_no_promovidos_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN {$resultado} IN ('no_promovido','repetidor') THEN ic.inscripcion_id END)"
            ),
            'alumnos_egresados_historicos_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN {$resultado} = 'egresado' THEN ic.inscripcion_id END)"
            ),
            'alumnos_trasladados_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN {$resultado} IN ('trasladado','traslado') THEN ic.inscripcion_id END)"
            ),
            'alumnos_bajas_historicas_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN {$resultado} IN ('baja_temporal','baja_temporal_al_cierre','baja_definitiva','suspendido','inactivo','no_reinscrito') THEN ic.inscripcion_id END)"
            ),
            'contextos_inferidos_count' => $this->subconsultaHistorial(
                $cicloEscolarId,
                "COUNT(DISTINCT CASE WHEN ic.reconstruido = 1 OR COALESCE(ic.nivel_confianza, 'exacto') <> 'exacto' THEN ic.inscripcion_id END)"
            ),
        ]);

        if (Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            $grupos = DB::table('inscripcion_ciclo_asignaciones as ica')
                ->join('inscripcion_ciclos as ich', 'ich.id', '=', 'ica.inscripcion_ciclo_id')
                ->selectRaw('COUNT(DISTINCT ica.grupo_id)')
                ->whereColumn('ich.generacion_id', 'generaciones.id')
                ->when($cicloEscolarId, fn ($sub) => $sub->where('ich.ciclo_escolar_id', $cicloEscolarId));

            $query->addSelect(['grupos_historicos_count' => $grupos]);
        }

        if (Schema::hasTable('calificaciones') && Schema::hasColumn('calificaciones', 'inscripcion_ciclo_id')) {
            $sinVinculo = DB::table('calificaciones as ch')
                ->selectRaw('COUNT(*)')
                ->whereColumn('ch.generacion_id', 'generaciones.id')
                ->whereNull('ch.inscripcion_ciclo_id')
                ->when($cicloEscolarId, fn ($sub) => $sub->where('ch.ciclo_escolar_id', $cicloEscolarId));

            $query->addSelect(['calificaciones_sin_historial_count' => $sinVinculo]);
        }

        return $query;
    }

    public function filtrarPorCiclo(Builder $query, ?int $cicloEscolarId): void
    {
        if (! $cicloEscolarId) {
            return;
        }

        $query->where(function (Builder $filtro) use ($cicloEscolarId): void {
            $filtro->whereHas('inscripcionCiclos', fn (Builder $historial) => $historial
                ->where('ciclo_escolar_id', $cicloEscolarId))
                ->orWhereHas('grupos', fn (Builder $grupos) => $grupos
                    ->where('ciclo_escolar_id', $cicloEscolarId))
                ->orWhereHas('calificaciones', fn (Builder $calificaciones) => $calificaciones
                    ->where('ciclo_escolar_id', $cicloEscolarId));
        });
    }

    public function filtrarPorResultado(Builder $query, string $resultado, ?int $cicloEscolarId): void
    {
        if ($resultado === '') {
            return;
        }

        $query->whereHas('inscripcionCiclos', function (Builder $historial) use ($resultado, $cicloEscolarId): void {
            $historial->when($cicloEscolarId, fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId));

            match ($resultado) {
                'en_curso' => $historial->where('estado', 'en_curso'),
                'promovido' => $historial->where(function (Builder $q): void {
                    $q->where('promovido', true)
                        ->orWhereIn('resultado_final', ['promovido', 'promovido_grado', 'promovido_nivel', 'continuidad']);
                }),
                'no_promovido' => $historial->whereIn('resultado_final', ['no_promovido', 'repetidor']),
                'egresado' => $historial->where(function (Builder $q): void {
                    $q->where('resultado_final', 'egresado')
                        ->orWhere('estatus_actual_ciclo', 'egresado');
                }),
                'trasladado' => $historial->where(function (Builder $q): void {
                    $q->whereIn('resultado_final', ['trasladado', 'traslado'])
                        ->orWhereIn('estatus_actual_ciclo', ['trasladado', 'traslado']);
                }),
                'baja' => $historial->where(function (Builder $q): void {
                    $estados = ['baja_temporal', 'baja_temporal_al_cierre', 'baja_definitiva', 'suspendido', 'inactivo', 'no_reinscrito'];
                    $q->whereIn('resultado_final', $estados)
                        ->orWhereIn('estatus_actual_ciclo', $estados);
                }),
                default => null,
            };
        });
    }

    public function filtrarPorContenido(Builder $query, string $contenido, ?int $cicloEscolarId): void
    {
        match ($contenido) {
            'con_historial' => $query->whereHas('inscripcionCiclos', fn (Builder $q) => $q
                ->when($cicloEscolarId, fn (Builder $historial) => $historial->where('ciclo_escolar_id', $cicloEscolarId))),
            'sin_historial' => $query->whereHas('inscripciones')
                ->whereDoesntHave('inscripcionCiclos', fn (Builder $q) => $q
                    ->when($cicloEscolarId, fn (Builder $historial) => $historial->where('ciclo_escolar_id', $cicloEscolarId))),
            'con_inconsistencias' => $query->where(function (Builder $filtro) use ($cicloEscolarId): void {
                $filtro->whereHas('inscripcionCiclos', fn (Builder $historial) => $historial
                    ->when($cicloEscolarId, fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
                    ->where(function (Builder $q): void {
                        $q->where('reconstruido', true)
                            ->orWhere('nivel_confianza', '!=', 'exacto');
                    }));

                if (Schema::hasTable('calificaciones') && Schema::hasColumn('calificaciones', 'inscripcion_ciclo_id')) {
                    $filtro->orWhereHas('calificaciones', fn (Builder $calificaciones) => $calificaciones
                        ->when($cicloEscolarId, fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
                        ->whereNull('inscripcion_ciclo_id'));
                }
            }),
            default => null,
        };
    }

    /**
     * Devuelve la trayectoria agrupada por ciclo y contexto académico para el
     * modal de consulta. No modifica ninguna inscripción.
     */
    public function detalle(int $generacionId): array
    {
        $generacion = Generacion::query()
            ->with(['nivel', 'cicloEscolarInicio', 'cicloEscolarFin'])
            ->findOrFail($generacionId);

        if (! Schema::hasTable('inscripcion_ciclos')) {
            return [
                'generacion' => $generacion,
                'resumen' => [],
                'ciclos' => collect(),
                'contextos' => collect(),
            ];
        }

        $resultado = $this->expresionResultado('ic');

        $resumen = (array) DB::table('inscripcion_ciclos as ic')
            ->where('ic.generacion_id', $generacionId)
            ->selectRaw("COUNT(DISTINCT ic.inscripcion_id) as alumnos")
            ->selectRaw('COUNT(DISTINCT ic.ciclo_escolar_id) as ciclos')
            ->selectRaw("COUNT(DISTINCT CASE WHEN ic.estado = 'en_curso' THEN ic.inscripcion_id END) as en_curso")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ic.promovido = 1 OR {$resultado} IN ('promovido','promovido_grado','promovido_nivel','continuidad') THEN ic.inscripcion_id END) as promovidos")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} IN ('no_promovido','repetidor') THEN ic.inscripcion_id END) as no_promovidos")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} = 'egresado' THEN ic.inscripcion_id END) as egresados")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} IN ('trasladado','traslado') THEN ic.inscripcion_id END) as trasladados")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} IN ('baja_temporal','baja_temporal_al_cierre','baja_definitiva','suspendido','inactivo','no_reinscrito') THEN ic.inscripcion_id END) as bajas")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ic.reconstruido = 1 OR COALESCE(ic.nivel_confianza, 'exacto') <> 'exacto' THEN ic.inscripcion_id END) as inferidos")
            ->first();

        $ciclos = DB::table('inscripcion_ciclos as ic')
            ->join('ciclo_escolares as ce', 'ce.id', '=', 'ic.ciclo_escolar_id')
            ->leftJoin('inscripcion_ciclo_asignaciones as ica', 'ica.inscripcion_ciclo_id', '=', 'ic.id')
            ->leftJoin('calificaciones as cal', 'cal.inscripcion_ciclo_id', '=', 'ic.id')
            ->where('ic.generacion_id', $generacionId)
            ->groupBy('ce.id', 'ce.inicio_anio', 'ce.fin_anio', 'ce.es_actual', 'ce.cerrado_at')
            ->orderByDesc('ce.inicio_anio')
            ->select([
                'ce.id',
                'ce.inicio_anio',
                'ce.fin_anio',
                'ce.es_actual',
                'ce.cerrado_at',
            ])
            ->selectRaw('COUNT(DISTINCT ic.inscripcion_id) as alumnos')
            ->selectRaw('COUNT(DISTINCT ica.grupo_id) as grupos')
            ->selectRaw('COUNT(DISTINCT cal.id) as calificaciones')
            ->selectRaw("COUNT(DISTINCT CASE WHEN ic.estado = 'en_curso' THEN ic.inscripcion_id END) as en_curso")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ic.promovido = 1 OR {$resultado} IN ('promovido','promovido_grado','promovido_nivel','continuidad') THEN ic.inscripcion_id END) as promovidos")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} IN ('no_promovido','repetidor') THEN ic.inscripcion_id END) as no_promovidos")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} = 'egresado' THEN ic.inscripcion_id END) as egresados")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} IN ('trasladado','traslado') THEN ic.inscripcion_id END) as trasladados")
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$resultado} IN ('baja_temporal','baja_temporal_al_cierre','baja_definitiva','suspendido','inactivo','no_reinscrito') THEN ic.inscripcion_id END) as bajas")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ic.reconstruido = 1 OR COALESCE(ic.nivel_confianza, 'exacto') <> 'exacto' THEN ic.inscripcion_id END) as inferidos")
            ->get();

        $contextos = collect();
        if (Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            $contextos = DB::table('inscripcion_ciclo_asignaciones as ica')
                ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'ica.inscripcion_ciclo_id')
                ->join('ciclo_escolares as ce', 'ce.id', '=', 'ic.ciclo_escolar_id')
                ->leftJoin('grados as gr', 'gr.id', '=', 'ica.grado_id')
                ->leftJoin('semestres as se', 'se.id', '=', 'ica.semestre_id')
                ->leftJoin('grupos as gp', 'gp.id', '=', 'ica.grupo_id')
                ->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'gp.asignacion_grupo_id')
                ->where('ic.generacion_id', $generacionId)
                ->groupBy(
                    'ce.id', 'ce.inicio_anio', 'ce.fin_anio',
                    'ica.grado_id', 'gr.nombre',
                    'ica.semestre_id', 'se.numero',
                    'ica.grupo_id', 'ag.nombre'
                )
                ->orderByDesc('ce.inicio_anio')
                ->orderBy('gr.orden')
                ->orderBy('se.numero')
                ->orderBy('ag.nombre')
                ->select([
                    'ce.id as ciclo_id',
                    'ce.inicio_anio',
                    'ce.fin_anio',
                    'ica.grado_id',
                    'gr.nombre as grado',
                    'ica.semestre_id',
                    'se.numero as semestre',
                    'ica.grupo_id',
                    'ag.nombre as grupo',
                ])
                ->selectRaw('COUNT(DISTINCT ic.inscripcion_id) as alumnos')
                ->get();
        }

        return compact('generacion', 'resumen', 'ciclos', 'contextos');
    }

    private function subconsultaHistorial(?int $cicloEscolarId, string $selectRaw)
    {
        return DB::table('inscripcion_ciclos as ic')
            ->selectRaw($selectRaw)
            ->whereColumn('ic.generacion_id', 'generaciones.id')
            ->when($cicloEscolarId, fn ($sub) => $sub->where('ic.ciclo_escolar_id', $cicloEscolarId));
    }

    private function expresionResultado(string $alias): string
    {
        return "LOWER(COALESCE(NULLIF({$alias}.resultado_final, ''), NULLIF({$alias}.estatus_actual_ciclo, ''), NULLIF({$alias}.estatus_ingreso, ''), 'activo'))";
    }
}
