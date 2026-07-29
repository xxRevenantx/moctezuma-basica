<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IntegridadAcademicaDetectorService
{
    /** @return array<int,array<string,mixed>> */
    public function detectar(?int $inscripcionId = null): array
    {
        $casos = collect();

        $this->agregar($casos, fn () => $this->historialesAbiertosDuplicados($inscripcionId));
        $this->agregar($casos, fn () => $this->asignacionesActualesDuplicadas($inscripcionId));
        $this->agregar($casos, fn () => $this->contextoActualDesalineado($inscripcionId));
        $this->agregar($casos, fn () => $this->egresadosConEstatusContradictorio($inscripcionId));
        $this->agregar($casos, fn () => $this->calificacionesSinHistorial($inscripcionId));
        $this->agregar($casos, fn () => $this->calificacionesConHistorialIncorrecto($inscripcionId));
        $this->agregar($casos, fn () => $this->continuidadesPendientes($inscripcionId));
        $this->agregar($casos, fn () => $this->historialesReconstruidos($inscripcionId));
        $this->agregar($casos, fn () => $this->gruposDeCicloIncompatible($inscripcionId));
        $this->agregar($casos, fn () => $this->matriculasVigentesDuplicadas($inscripcionId));
        $this->agregar($casos, fn () => $this->matriculasVigentesDesalineadas($inscripcionId));
        $this->agregar($casos, fn () => $this->curpDuplicadas($inscripcionId));

        if ($inscripcionId === null) {
            $this->agregar($casos, fn () => $this->cicloActualInvalido());
            $this->agregar($casos, fn () => $this->cierresEstancados());
        }

        return $casos
            ->filter(fn ($caso) => is_array($caso))
            ->unique('fingerprint')
            ->values()
            ->all();
    }

    private function agregar(Collection $casos, callable $callback): void
    {
        try {
            $resultado = $callback();
            if (is_array($resultado)) {
                foreach ($resultado as $caso) {
                    if (is_array($caso)) {
                        $casos->push($caso);
                    }
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function historialesAbiertosDuplicados(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            return [];
        }

        $duplicados = DB::table('inscripcion_ciclos')
            ->select('inscripcion_id', DB::raw('COUNT(*) as total'))
            ->where('estado', 'en_curso')
            ->when($inscripcionId, fn ($query) => $query->where('inscripcion_id', $inscripcionId))
            ->groupBy('inscripcion_id')
            ->having('total', '>', 1)
            ->get();

        return $duplicados->map(function ($fila): array {
            $historiales = DB::table('inscripcion_ciclos as ic')
                ->leftJoin('ciclo_escolares as ce', 'ce.id', '=', 'ic.ciclo_escolar_id')
                ->leftJoin('niveles as n', 'n.id', '=', 'ic.nivel_id')
                ->leftJoin('grados as g', 'g.id', '=', 'ic.grado_id')
                ->leftJoin('grupos as gr', 'gr.id', '=', 'ic.grupo_id')
                ->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'gr.asignacion_grupo_id')
                ->where('ic.inscripcion_id', $fila->inscripcion_id)
                ->where('ic.estado', 'en_curso')
                ->orderByDesc('ce.inicio_anio')
                ->orderByDesc('ic.fecha_ingreso')
                ->orderByDesc('ic.id')
                ->get([
                    'ic.id', 'ic.ciclo_escolar_id', 'ic.nivel_id', 'ic.grado_id', 'ic.grupo_id',
                    'ic.semestre_id', 'ic.fecha_ingreso', 'ic.estatus_actual_ciclo',
                    'ce.inicio_anio', 'ce.fin_anio', 'n.nombre as nivel', 'g.nombre as grado',
                    'ag.nombre as grupo',
                ]);

            return $this->caso(
                regla: 'historiales_abiertos_duplicados',
                categoria: 'historial',
                severidad: 'critico',
                inscripcionId: (int) $fila->inscripcion_id,
                inscripcionCicloId: (int) ($historiales->first()->id ?? 0) ?: null,
                cicloEscolarId: (int) ($historiales->first()->ciclo_escolar_id ?? 0) ?: null,
                nivelId: (int) ($historiales->first()->nivel_id ?? 0) ?: null,
                titulo: 'El alumno tiene más de un ciclo marcado como vigente',
                descripcion: 'Solo debe existir un historial en curso. El sistema no cerrará registros automáticamente porque primero debe confirmarse cuál representa la situación real.',
                evidencia: [
                    'alumno' => $this->alumno((int) $fila->inscripcion_id),
                    'total_historiales_abiertos' => (int) $fila->total,
                    'historiales' => $historiales->map(fn ($h) => [
                        'id' => (int) $h->id,
                        'ciclo' => $this->ciclo($h->inicio_anio, $h->fin_anio),
                        'nivel' => $h->nivel,
                        'grado' => $h->grado,
                        'grupo' => $h->grupo,
                        'fecha_ingreso' => $h->fecha_ingreso,
                        'estatus' => $h->estatus_actual_ciclo,
                    ])->all(),
                ],
                correccion: null,
                identidad: ['inscripcion_id' => (int) $fila->inscripcion_id],
            );
        })->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function asignacionesActualesDuplicadas(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            return [];
        }

        $query = DB::table('inscripcion_ciclo_asignaciones as a')
            ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'a.inscripcion_ciclo_id')
            ->select('a.inscripcion_ciclo_id', 'ic.inscripcion_id', DB::raw('COUNT(*) as total'))
            ->where('a.es_actual', true)
            ->when($inscripcionId, fn ($q) => $q->where('ic.inscripcion_id', $inscripcionId))
            ->groupBy('a.inscripcion_ciclo_id', 'ic.inscripcion_id')
            ->having('total', '>', 1)
            ->get();

        return $query->map(function ($fila): array {
            $asignaciones = DB::table('inscripcion_ciclo_asignaciones as a')
                ->leftJoin('niveles as n', 'n.id', '=', 'a.nivel_id')
                ->leftJoin('grados as g', 'g.id', '=', 'a.grado_id')
                ->leftJoin('grupos as gr', 'gr.id', '=', 'a.grupo_id')
                ->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'gr.asignacion_grupo_id')
                ->where('a.inscripcion_ciclo_id', $fila->inscripcion_ciclo_id)
                ->where('a.es_actual', true)
                ->orderByDesc('a.fecha_inicio')
                ->orderByDesc('a.id')
                ->get(['a.*', 'n.nombre as nivel', 'g.nombre as grado', 'ag.nombre as grupo']);

            $principal = $asignaciones->first();
            $cerrar = $asignaciones->skip(1)->pluck('id')->map(fn ($id) => (int) $id)->all();

            return $this->caso(
                regla: 'asignaciones_actuales_duplicadas',
                categoria: 'historial',
                severidad: 'critico',
                inscripcionId: (int) $fila->inscripcion_id,
                inscripcionCicloId: (int) $fila->inscripcion_ciclo_id,
                cicloEscolarId: $this->cicloIdDelHistorial((int) $fila->inscripcion_ciclo_id),
                nivelId: (int) ($principal->nivel_id ?? 0) ?: null,
                titulo: 'El ciclo tiene más de una ubicación académica actual',
                descripcion: 'Dos o más asignaciones aparecen como actuales dentro del mismo ciclo. La corrección sugerida conserva la asignación más reciente y cierra las anteriores.',
                evidencia: [
                    'alumno' => $this->alumno((int) $fila->inscripcion_id),
                    'historial_id' => (int) $fila->inscripcion_ciclo_id,
                    'asignaciones' => $asignaciones->map(fn ($a) => [
                        'id' => (int) $a->id,
                        'nivel' => $a->nivel,
                        'grado' => $a->grado,
                        'grupo' => $a->grupo,
                        'fecha_inicio' => $a->fecha_inicio,
                        'tipo' => $a->tipo,
                    ])->all(),
                ],
                correccion: $principal && $cerrar !== [] ? [
                    'clave' => 'conservar_asignacion_actual_mas_reciente',
                    'etiqueta' => 'Conservar la ubicación más reciente',
                    'descripcion' => 'Marcará como históricas las asignaciones anteriores y conservará una sola ubicación vigente.',
                    'parametros' => [
                        'inscripcion_ciclo_id' => (int) $fila->inscripcion_ciclo_id,
                        'asignacion_principal_id' => (int) $principal->id,
                        'asignaciones_cerrar' => $cerrar,
                    ],
                ] : null,
                identidad: ['inscripcion_ciclo_id' => (int) $fila->inscripcion_ciclo_id],
            );
        })->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function contextoActualDesalineado(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('inscripcion_ciclos')) {
            return [];
        }

        $historiales = DB::table('inscripcion_ciclos as ic')
            ->join('inscripciones as i', 'i.id', '=', 'ic.inscripcion_id')
            ->leftJoin('inscripcion_ciclo_asignaciones as a', function ($join): void {
                $join->on('a.inscripcion_ciclo_id', '=', 'ic.id')->where('a.es_actual', true);
            })
            ->where('ic.estado', 'en_curso')
            ->when(Schema::hasColumn('inscripciones', 'deleted_at'), fn ($q) => $q->whereNull('i.deleted_at'))
            ->when($inscripcionId, fn ($q) => $q->where('i.id', $inscripcionId))
            ->get([
                'i.id as inscripcion_id', 'i.nivel_id as i_nivel_id', 'i.grado_id as i_grado_id',
                'i.generacion_id as i_generacion_id', 'i.grupo_id as i_grupo_id',
                'i.semestre_id as i_semestre_id', 'i.ciclo_escolar_id as i_ciclo_escolar_id',
                'ic.id as historial_id', 'ic.ciclo_escolar_id',
                DB::raw('COALESCE(a.nivel_id, ic.nivel_id) as nivel_id'),
                DB::raw('COALESCE(a.grado_id, ic.grado_id) as grado_id'),
                DB::raw('COALESCE(a.generacion_id, ic.generacion_id) as generacion_id'),
                DB::raw('COALESCE(a.grupo_id, ic.grupo_id) as grupo_id'),
                DB::raw('COALESCE(a.semestre_id, ic.semestre_id) as semestre_id'),
            ]);

        return $historiales->filter(function ($fila): bool {
            return (int) $fila->i_nivel_id !== (int) $fila->nivel_id
                || (int) $fila->i_grado_id !== (int) $fila->grado_id
                || (int) $fila->i_generacion_id !== (int) $fila->generacion_id
                || (int) $fila->i_grupo_id !== (int) $fila->grupo_id
                || (int) ($fila->i_semestre_id ?? 0) !== (int) ($fila->semestre_id ?? 0)
                || (int) ($fila->i_ciclo_escolar_id ?? 0) !== (int) $fila->ciclo_escolar_id;
        })->map(function ($fila): array {
            $actual = [
                'nivel_id' => (int) $fila->i_nivel_id,
                'grado_id' => (int) $fila->i_grado_id,
                'generacion_id' => (int) $fila->i_generacion_id,
                'grupo_id' => (int) $fila->i_grupo_id,
                'semestre_id' => $fila->i_semestre_id ? (int) $fila->i_semestre_id : null,
                'ciclo_escolar_id' => $fila->i_ciclo_escolar_id ? (int) $fila->i_ciclo_escolar_id : null,
            ];
            $esperado = [
                'nivel_id' => (int) $fila->nivel_id,
                'grado_id' => (int) $fila->grado_id,
                'generacion_id' => (int) $fila->generacion_id,
                'grupo_id' => (int) $fila->grupo_id,
                'semestre_id' => $fila->semestre_id ? (int) $fila->semestre_id : null,
                'ciclo_escolar_id' => (int) $fila->ciclo_escolar_id,
            ];

            return $this->caso(
                regla: 'contexto_actual_desalineado',
                categoria: 'ubicacion',
                severidad: 'advertencia',
                inscripcionId: (int) $fila->inscripcion_id,
                inscripcionCicloId: (int) $fila->historial_id,
                cicloEscolarId: (int) $fila->ciclo_escolar_id,
                nivelId: (int) $fila->nivel_id,
                titulo: 'La ubicación actual no coincide con el historial vigente',
                descripcion: 'La ficha principal del alumno y su historial por ciclo muestran ubicaciones diferentes. La propuesta toma como fuente el historial vigente y su asignación actual.',
                evidencia: [
                    'alumno' => $this->alumno((int) $fila->inscripcion_id),
                    'ubicacion_en_inscripcion' => $this->contextoEtiquetado($actual),
                    'ubicacion_en_historial' => $this->contextoEtiquetado($esperado),
                    'valores_actuales' => $actual,
                    'valores_esperados' => $esperado,
                ],
                correccion: [
                    'clave' => 'alinear_inscripcion_con_historial',
                    'etiqueta' => 'Alinear ficha actual con el historial',
                    'descripcion' => 'Actualizará únicamente la ubicación actual del alumno; no cambiará calificaciones ni registros históricos.',
                    'parametros' => [
                        'inscripcion_id' => (int) $fila->inscripcion_id,
                        'inscripcion_ciclo_id' => (int) $fila->historial_id,
                        'valores_esperados' => $esperado,
                    ],
                ],
                identidad: ['inscripcion_id' => (int) $fila->inscripcion_id, 'historial_id' => (int) $fila->historial_id],
            );
        })->values()->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function egresadosConEstatusContradictorio(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('inscripcion_ciclos')) {
            return [];
        }

        $filas = DB::table('inscripcion_ciclos as ic')
            ->join('inscripciones as i', 'i.id', '=', 'ic.inscripcion_id')
            ->where('ic.resultado_final', 'egresado')
            ->where(function ($q): void {
                $q->where('i.estatus', '!=', 'egresado')->orWhere('i.activo', true);
            })
            ->when($inscripcionId, fn ($q) => $q->where('i.id', $inscripcionId))
            ->orderByDesc('ic.ciclo_escolar_id')
            ->get(['i.id as inscripcion_id', 'i.estatus', 'i.activo', 'i.fecha_baja', 'ic.id as historial_id', 'ic.ciclo_escolar_id', 'ic.nivel_id']);

        return $filas->unique('inscripcion_id')->map(function ($fila): array {
            $severidad = in_array($fila->estatus, ['baja_definitiva', 'baja_temporal', 'trasladado'], true) ? 'critico' : 'advertencia';

            return $this->caso(
                regla: 'egresado_estatus_contradictorio',
                categoria: 'estatus',
                severidad: $severidad,
                inscripcionId: (int) $fila->inscripcion_id,
                inscripcionCicloId: (int) $fila->historial_id,
                cicloEscolarId: (int) $fila->ciclo_escolar_id,
                nivelId: (int) $fila->nivel_id,
                titulo: 'El alumno egresó, pero su estatus institucional es incompatible',
                descripcion: 'El último resultado académico indica egreso, mientras la ficha institucional conserva otro estatus o sigue activa.',
                evidencia: [
                    'alumno' => $this->alumno((int) $fila->inscripcion_id),
                    'resultado_historico' => 'egresado',
                    'estatus_institucional' => $fila->estatus,
                    'activo' => (bool) $fila->activo,
                    'fecha_baja_conservada' => $fila->fecha_baja,
                ],
                correccion: [
                    'clave' => 'normalizar_estatus_egresado',
                    'etiqueta' => 'Conservar estatus de egresado',
                    'descripcion' => 'Cambiará el estatus institucional a egresado y desactivará la inscripción. Los motivos o fechas antiguas se conservarán como evidencia.',
                    'parametros' => [
                        'inscripcion_id' => (int) $fila->inscripcion_id,
                        'inscripcion_ciclo_id' => (int) $fila->historial_id,
                    ],
                ],
                identidad: ['inscripcion_id' => (int) $fila->inscripcion_id],
            );
        })->values()->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function calificacionesSinHistorial(?int $inscripcionId): array
    {
        if (! Schema::hasTable('calificaciones') || ! Schema::hasColumn('calificaciones', 'inscripcion_ciclo_id')) {
            return [];
        }

        $grupos = DB::table('calificaciones')
            ->select('inscripcion_id', DB::raw('COUNT(*) as total'))
            ->whereNull('inscripcion_ciclo_id')
            ->when($inscripcionId, fn ($q) => $q->where('inscripcion_id', $inscripcionId))
            ->groupBy('inscripcion_id')
            ->get();

        return $grupos->map(function ($grupo): array {
            $calificaciones = DB::table('calificaciones')
                ->where('inscripcion_id', $grupo->inscripcion_id)
                ->whereNull('inscripcion_ciclo_id')
                ->get(['id', 'ciclo_escolar_id', 'periodo_id', 'grado_id', 'grupo_id', 'semestre_id']);

            $mapeos = [];
            $ambiguas = [];
            foreach ($calificaciones as $calificacion) {
                $historiales = DB::table('inscripcion_ciclos')
                    ->where('inscripcion_id', $grupo->inscripcion_id)
                    ->where('ciclo_escolar_id', $calificacion->ciclo_escolar_id)
                    ->pluck('id');

                if ($historiales->count() === 1) {
                    $mapeos[(string) $calificacion->id] = (int) $historiales->first();
                } else {
                    $ambiguas[] = (int) $calificacion->id;
                }
            }

            return $this->caso(
                regla: 'calificaciones_sin_historial',
                categoria: 'calificaciones',
                severidad: $ambiguas === [] ? 'advertencia' : 'critico',
                inscripcionId: (int) $grupo->inscripcion_id,
                inscripcionCicloId: $mapeos !== [] ? (int) reset($mapeos) : null,
                cicloEscolarId: null,
                nivelId: null,
                titulo: 'Existen calificaciones sin vínculo al ciclo histórico',
                descripcion: $ambiguas === []
                    ? 'El sistema encontró un historial exacto para cada calificación y puede vincularlas con respaldo reversible.'
                    : 'Algunas calificaciones no tienen un único historial compatible y deben revisarse manualmente.',
                evidencia: [
                    'alumno' => $this->alumno((int) $grupo->inscripcion_id),
                    'total' => (int) $grupo->total,
                    'mapeos_exactos' => $mapeos,
                    'calificaciones_ambiguas' => $ambiguas,
                ],
                correccion: $mapeos !== [] && $ambiguas === [] ? [
                    'clave' => 'vincular_calificaciones_con_historial',
                    'etiqueta' => 'Vincular calificaciones al ciclo correcto',
                    'descripcion' => 'Asignará el historial exacto a cada calificación sin modificar su valor.',
                    'parametros' => ['mapeos' => $mapeos],
                ] : null,
                identidad: ['inscripcion_id' => (int) $grupo->inscripcion_id],
            );
        })->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function calificacionesConHistorialIncorrecto(?int $inscripcionId): array
    {
        if (! Schema::hasTable('calificaciones') || ! Schema::hasTable('inscripcion_ciclos')) {
            return [];
        }

        $filas = DB::table('calificaciones as c')
            ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'c.inscripcion_ciclo_id')
            ->where(function ($q): void {
                $q->whereColumn('c.inscripcion_id', '!=', 'ic.inscripcion_id')
                    ->orWhereColumn('c.ciclo_escolar_id', '!=', 'ic.ciclo_escolar_id');
            })
            ->when($inscripcionId, fn ($q) => $q->where('c.inscripcion_id', $inscripcionId))
            ->get(['c.id', 'c.inscripcion_id', 'c.inscripcion_ciclo_id', 'c.ciclo_escolar_id', 'ic.inscripcion_id as historial_alumno_id', 'ic.ciclo_escolar_id as historial_ciclo_id']);

        return $filas->groupBy('inscripcion_id')->map(function (Collection $items, $alumnoId): array {
            $mapeos = [];
            $ambiguas = [];
            foreach ($items as $item) {
                $correctos = DB::table('inscripcion_ciclos')
                    ->where('inscripcion_id', $item->inscripcion_id)
                    ->where('ciclo_escolar_id', $item->ciclo_escolar_id)
                    ->pluck('id');
                if ($correctos->count() === 1) {
                    $mapeos[(string) $item->id] = (int) $correctos->first();
                } else {
                    $ambiguas[] = (int) $item->id;
                }
            }

            return $this->caso(
                regla: 'calificaciones_historial_incorrecto',
                categoria: 'calificaciones',
                severidad: 'critico',
                inscripcionId: (int) $alumnoId,
                inscripcionCicloId: null,
                cicloEscolarId: null,
                nivelId: null,
                titulo: 'Calificaciones vinculadas a un alumno o ciclo diferente',
                descripcion: 'El vínculo histórico de estas calificaciones no coincide con el alumno o el ciclo almacenado en la propia calificación.',
                evidencia: [
                    'alumno' => $this->alumno((int) $alumnoId),
                    'registros' => $items->map(fn ($i) => [
                        'calificacion_id' => (int) $i->id,
                        'historial_actual_id' => (int) $i->inscripcion_ciclo_id,
                        'ciclo_calificacion_id' => (int) $i->ciclo_escolar_id,
                        'alumno_del_historial_id' => (int) $i->historial_alumno_id,
                        'ciclo_del_historial_id' => (int) $i->historial_ciclo_id,
                    ])->all(),
                    'mapeos_exactos' => $mapeos,
                    'ambiguas' => $ambiguas,
                ],
                correccion: $mapeos !== [] && $ambiguas === [] ? [
                    'clave' => 'vincular_calificaciones_con_historial',
                    'etiqueta' => 'Corregir vínculos históricos',
                    'descripcion' => 'Reasignará únicamente el vínculo histórico; no cambiará las calificaciones.',
                    'parametros' => ['mapeos' => $mapeos],
                ] : null,
                identidad: ['inscripcion_id' => (int) $alumnoId],
            );
        })->values()->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function continuidadesPendientes(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripcion_ciclos') || ! Schema::hasTable('ciclo_escolares')) {
            return [];
        }

        $resultados = ['promovido', 'promovido_grado', 'promovido_nivel', 'no_promovido'];
        $filas = DB::table('inscripcion_ciclos as ic')
            ->join('inscripciones as i', 'i.id', '=', 'ic.inscripcion_id')
            ->join('ciclo_escolares as ce', 'ce.id', '=', 'ic.ciclo_escolar_id')
            ->where('ic.estado', 'cerrado')
            ->whereIn('ic.resultado_final', $resultados)
            ->whereNull('ic.inscripcion_ciclo_destino_id')
            ->whereNotExists(function ($q): void {
                $q->selectRaw('1')->from('inscripcion_ciclos as siguiente')
                    ->join('ciclo_escolares as ces', 'ces.id', '=', 'siguiente.ciclo_escolar_id')
                    ->whereColumn('siguiente.inscripcion_id', 'ic.inscripcion_id')
                    ->whereColumn('ces.inicio_anio', '>', 'ce.inicio_anio');
            })
            ->when(Schema::hasTable('proyecciones_continuidad'), function ($q): void {
                $q->whereNotExists(function ($sub): void {
                    $sub->selectRaw('1')->from('proyecciones_continuidad as pc')
                        ->whereColumn('pc.inscripcion_ciclo_origen_id', 'ic.id')
                        ->whereIn('pc.estado', ['pendiente', 'confirmada']);
                });
            })
            ->whereNotIn('i.estatus', ['no_reinscrito', 'baja_definitiva', 'trasladado', 'egresado'])
            ->when($inscripcionId, fn ($q) => $q->where('ic.inscripcion_id', $inscripcionId))
            ->get(['ic.id', 'ic.inscripcion_id', 'ic.ciclo_escolar_id', 'ic.nivel_id', 'ic.resultado_final', 'ce.inicio_anio', 'ce.fin_anio']);

        return $filas->map(fn ($fila) => $this->caso(
            regla: 'continuidad_sin_destino',
            categoria: 'continuidad',
            severidad: 'advertencia',
            inscripcionId: (int) $fila->inscripcion_id,
            inscripcionCicloId: (int) $fila->id,
            cicloEscolarId: (int) $fila->ciclo_escolar_id,
            nivelId: (int) $fila->nivel_id,
            titulo: 'El ciclo terminó con promoción, pero no existe continuidad registrada',
            descripcion: 'No se encontró historial posterior ni proyección pendiente. Puede ser una omisión o una decisión de no reinscripción aún no registrada.',
            evidencia: [
                'alumno' => $this->alumno((int) $fila->inscripcion_id),
                'ciclo_origen' => $this->ciclo($fila->inicio_anio, $fila->fin_anio),
                'resultado' => $fila->resultado_final,
                'historial_origen_id' => (int) $fila->id,
            ],
            correccion: null,
            identidad: ['historial_id' => (int) $fila->id],
        ))->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function historialesReconstruidos(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            return [];
        }

        $filas = DB::table('inscripcion_ciclos')
            ->where(function ($q): void {
                $q->where('reconstruido', true)->orWhere('nivel_confianza', '!=', 'exacto');
            })
            ->when($inscripcionId, fn ($q) => $q->where('inscripcion_id', $inscripcionId))
            ->get(['id', 'inscripcion_id', 'ciclo_escolar_id', 'nivel_id', 'origen', 'reconstruido', 'nivel_confianza']);

        return $filas->map(fn ($fila) => $this->caso(
            regla: 'historial_reconstruido',
            categoria: 'historial',
            severidad: $fila->nivel_confianza === 'bajo' ? 'advertencia' : 'informativo',
            inscripcionId: (int) $fila->inscripcion_id,
            inscripcionCicloId: (int) $fila->id,
            cicloEscolarId: (int) $fila->ciclo_escolar_id,
            nivelId: (int) $fila->nivel_id,
            titulo: 'Historial reconstruido que conviene validar',
            descripcion: 'Este ciclo fue generado a partir de información existente. No necesariamente es incorrecto, pero debe confirmarse antes de emitir documentos sensibles.',
            evidencia: [
                'alumno' => $this->alumno((int) $fila->inscripcion_id),
                'historial_id' => (int) $fila->id,
                'origen' => $fila->origen,
                'reconstruido' => (bool) $fila->reconstruido,
                'nivel_confianza' => $fila->nivel_confianza,
            ],
            correccion: null,
            identidad: ['historial_id' => (int) $fila->id],
        ))->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function gruposDeCicloIncompatible(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripcion_ciclo_asignaciones') || ! Schema::hasTable('grupos')) {
            return [];
        }

        $filas = DB::table('inscripcion_ciclo_asignaciones as a')
            ->join('inscripcion_ciclos as ic', 'ic.id', '=', 'a.inscripcion_ciclo_id')
            ->join('grupos as g', 'g.id', '=', 'a.grupo_id')
            ->whereNotNull('g.ciclo_escolar_id')
            ->whereColumn('g.ciclo_escolar_id', '!=', 'ic.ciclo_escolar_id')
            ->when($inscripcionId, fn ($q) => $q->where('ic.inscripcion_id', $inscripcionId))
            ->get(['a.id', 'a.inscripcion_ciclo_id', 'a.grupo_id', 'ic.inscripcion_id', 'ic.ciclo_escolar_id', 'ic.nivel_id', 'g.ciclo_escolar_id as grupo_ciclo_id']);

        return $filas->map(fn ($fila) => $this->caso(
            regla: 'grupo_ciclo_incompatible',
            categoria: 'ubicacion',
            severidad: 'advertencia',
            inscripcionId: (int) $fila->inscripcion_id,
            inscripcionCicloId: (int) $fila->inscripcion_ciclo_id,
            cicloEscolarId: (int) $fila->ciclo_escolar_id,
            nivelId: (int) $fila->nivel_id,
            titulo: 'El grupo histórico pertenece a otro ciclo escolar',
            descripcion: 'La asignación del alumno usa un grupo cuyo ciclo no coincide con el historial. Debe decidirse si el grupo es excepcional o si la asignación necesita corregirse.',
            evidencia: [
                'alumno' => $this->alumno((int) $fila->inscripcion_id),
                'asignacion_id' => (int) $fila->id,
                'grupo_id' => (int) $fila->grupo_id,
                'ciclo_historial_id' => (int) $fila->ciclo_escolar_id,
                'ciclo_grupo_id' => (int) $fila->grupo_ciclo_id,
            ],
            correccion: null,
            identidad: ['asignacion_id' => (int) $fila->id],
        ))->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function matriculasVigentesDuplicadas(?int $inscripcionId): array
    {
        if (! Schema::hasTable('matriculas_alumnos')) {
            return [];
        }

        $duplicados = DB::table('matriculas_alumnos')
            ->select('inscripcion_id', DB::raw('COUNT(*) as total'))
            ->where('vigente', true)
            ->when($inscripcionId, fn ($q) => $q->where('inscripcion_id', $inscripcionId))
            ->groupBy('inscripcion_id')
            ->having('total', '>', 1)
            ->get();

        return $duplicados->map(function ($fila): array {
            $matriculas = DB::table('matriculas_alumnos')
                ->where('inscripcion_id', $fila->inscripcion_id)
                ->where('vigente', true)
                ->orderByDesc('fecha_asignacion')
                ->orderByDesc('id')
                ->get(['id', 'nivel_id', 'matricula', 'fecha_asignacion', 'origen']);

            return $this->caso(
                regla: 'matriculas_vigentes_duplicadas',
                categoria: 'matricula',
                severidad: 'critico',
                inscripcionId: (int) $fila->inscripcion_id,
                inscripcionCicloId: null,
                cicloEscolarId: null,
                nivelId: (int) ($matriculas->first()->nivel_id ?? 0) ?: null,
                titulo: 'El alumno tiene más de una matrícula marcada como vigente',
                descripcion: 'Debe confirmarse qué matrícula corresponde a su nivel actual. El sistema no elegirá una automáticamente.',
                evidencia: [
                    'alumno' => $this->alumno((int) $fila->inscripcion_id),
                    'total' => (int) $fila->total,
                    'matriculas' => $matriculas->map(fn ($m) => (array) $m)->all(),
                ],
                correccion: null,
                identidad: ['inscripcion_id' => (int) $fila->inscripcion_id],
            );
        })->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function matriculasVigentesDesalineadas(?int $inscripcionId): array
    {
        if (! Schema::hasTable('matriculas_alumnos') || ! Schema::hasTable('inscripciones')) {
            return [];
        }

        $filas = DB::table('inscripciones as i')
            ->join('matriculas_alumnos as m', function ($join): void {
                $join->on('m.inscripcion_id', '=', 'i.id')->where('m.vigente', true);
            })
            ->whereRaw('(SELECT COUNT(*) FROM matriculas_alumnos mv WHERE mv.inscripcion_id = i.id AND mv.vigente = 1) = 1')
            ->whereColumn('i.matricula', '!=', 'm.matricula')
            ->when($inscripcionId, fn ($q) => $q->where('i.id', $inscripcionId))
            ->get(['i.id as inscripcion_id', 'i.matricula as matricula_actual', 'm.id as matricula_historial_id', 'm.matricula as matricula_vigente', 'i.nivel_id']);

        return $filas->map(fn ($fila) => $this->caso(
            regla: 'matricula_vigente_desalineada',
            categoria: 'matricula',
            severidad: 'advertencia',
            inscripcionId: (int) $fila->inscripcion_id,
            inscripcionCicloId: null,
            cicloEscolarId: null,
            nivelId: (int) $fila->nivel_id,
            titulo: 'La matrícula actual no coincide con el historial vigente',
            descripcion: 'La ficha del alumno y la tabla de matrículas señalan claves distintas. La propuesta conserva como principal la matrícula histórica vigente.',
            evidencia: [
                'alumno' => $this->alumno((int) $fila->inscripcion_id),
                'matricula_en_inscripcion' => $fila->matricula_actual,
                'matricula_vigente' => $fila->matricula_vigente,
                'registro_matricula_id' => (int) $fila->matricula_historial_id,
            ],
            correccion: [
                'clave' => 'alinear_matricula_vigente',
                'etiqueta' => 'Usar la matrícula histórica vigente',
                'descripcion' => 'Actualizará la matrícula principal del alumno sin eliminar su historial de matrículas.',
                'parametros' => [
                    'inscripcion_id' => (int) $fila->inscripcion_id,
                    'matricula' => (string) $fila->matricula_vigente,
                    'matricula_historial_id' => (int) $fila->matricula_historial_id,
                ],
            ],
            identidad: ['inscripcion_id' => (int) $fila->inscripcion_id],
        ))->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function curpDuplicadas(?int $inscripcionId): array
    {
        if (! Schema::hasTable('inscripciones')) {
            return [];
        }

        $query = DB::table('inscripciones')
            ->whereNotNull('curp')->where('curp', '!=', '')
            ->when(Schema::hasColumn('inscripciones', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'));

        if ($inscripcionId) {
            $curp = (clone $query)->where('id', $inscripcionId)->value('curp');
            if (! $curp) {
                return [];
            }
            $query->where('curp', $curp);
        }

        $duplicados = $query->select('curp', DB::raw('COUNT(*) as total'))->groupBy('curp')->having('total', '>', 1)->get();

        return $duplicados->map(function ($fila): array {
            $alumnos = DB::table('inscripciones')->where('curp', $fila->curp)
                ->when(Schema::hasColumn('inscripciones', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->get(['id', 'matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'estatus']);

            return $this->caso(
                regla: 'curp_duplicada',
                categoria: 'identidad',
                severidad: 'critico',
                inscripcionId: $alumnos->count() === 1 ? (int) $alumnos->first()->id : null,
                inscripcionCicloId: null,
                cicloEscolarId: null,
                nivelId: null,
                titulo: 'La CURP está registrada en más de un alumno',
                descripcion: 'La unificación de alumnos requiere comparar documentos, calificaciones y expedientes; por seguridad no se propone una corrección automática.',
                evidencia: [
                    'curp' => $fila->curp,
                    'total' => (int) $fila->total,
                    'alumnos' => $alumnos->map(fn ($a) => [
                        'id' => (int) $a->id,
                        'matricula' => $a->matricula,
                        'nombre' => trim("{$a->nombre} {$a->apellido_paterno} {$a->apellido_materno}"),
                        'estatus' => $a->estatus,
                    ])->all(),
                ],
                correccion: null,
                identidad: ['curp' => (string) $fila->curp],
            );
        })->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function cicloActualInvalido(): array
    {
        if (! Schema::hasTable('ciclo_escolares')) {
            return [];
        }

        $actuales = DB::table('ciclo_escolares')->where('es_actual', true)->get(['id', 'inicio_anio', 'fin_anio']);
        if ($actuales->count() === 1) {
            return [];
        }

        return [$this->caso(
            regla: 'ciclo_actual_invalido',
            categoria: 'configuracion',
            severidad: 'critico',
            inscripcionId: null,
            inscripcionCicloId: null,
            cicloEscolarId: $actuales->count() === 1 ? (int) $actuales->first()->id : null,
            nivelId: null,
            titulo: $actuales->isEmpty() ? 'No existe un ciclo escolar actual' : 'Hay varios ciclos marcados como actuales',
            descripcion: 'El sistema necesita exactamente un ciclo actual para interpretar correctamente inscripciones, periodos y continuidad.',
            evidencia: [
                'total_ciclos_actuales' => $actuales->count(),
                'ciclos' => $actuales->map(fn ($c) => ['id' => (int) $c->id, 'nombre' => $this->ciclo($c->inicio_anio, $c->fin_anio)])->all(),
            ],
            correccion: null,
            identidad: ['global' => true],
        )];
    }

    /** @return array<int,array<string,mixed>> */
    private function cierresEstancados(): array
    {
        if (! Schema::hasTable('procesos_cierre_ciclo')) {
            return [];
        }

        $filas = DB::table('procesos_cierre_ciclo')
            ->whereNotIn('estado', ['completado', 'revertido', 'cancelado'])
            ->where('updated_at', '<', now()->subHours(2))
            ->get(['id', 'estado', 'nivel_id', 'ciclo_escolar_id', 'generacion_id', 'total_evaluados', 'total_procesados', 'updated_at']);

        return $filas->map(fn ($fila) => $this->caso(
            regla: 'cierre_estancado',
            categoria: 'cierre',
            severidad: 'critico',
            inscripcionId: null,
            inscripcionCicloId: null,
            cicloEscolarId: $fila->ciclo_escolar_id ? (int) $fila->ciclo_escolar_id : null,
            nivelId: $fila->nivel_id ? (int) $fila->nivel_id : null,
            titulo: 'Proceso de cierre sin terminar',
            descripcion: 'El proceso lleva más de dos horas en un estado intermedio. Debe revisarse antes de iniciar otro cierre sobre la misma población.',
            evidencia: [
                'proceso_id' => (int) $fila->id,
                'estado' => $fila->estado,
                'total_evaluados' => (int) $fila->total_evaluados,
                'total_procesados' => (int) $fila->total_procesados,
                'ultima_actualizacion' => $fila->updated_at,
            ],
            correccion: null,
            identidad: ['proceso_id' => (int) $fila->id],
        ))->all();
    }

    private function caso(
        string $regla,
        string $categoria,
        string $severidad,
        ?int $inscripcionId,
        ?int $inscripcionCicloId,
        ?int $cicloEscolarId,
        ?int $nivelId,
        string $titulo,
        string $descripcion,
        array $evidencia,
        ?array $correccion,
        array $identidad,
    ): array {
        $fingerprint = hash('sha256', $regla.'|'.json_encode($this->ordenar($identidad), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'fingerprint' => $fingerprint,
            'regla' => $regla,
            'categoria' => $categoria,
            'severidad' => $severidad,
            'inscripcion_id' => $inscripcionId,
            'inscripcion_ciclo_id' => $inscripcionCicloId,
            'ciclo_escolar_id' => $cicloEscolarId,
            'nivel_id' => $nivelId,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'evidencia' => $evidencia,
            'correccion_sugerida' => $correccion,
            'metadata' => ['identidad' => $identidad],
        ];
    }

    private function alumno(int $id): array
    {
        $alumno = DB::table('inscripciones')->where('id', $id)->first([
            'id', 'matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'estatus', 'activo',
        ]);

        if (! $alumno) {
            return ['id' => $id, 'nombre' => 'Alumno no disponible'];
        }

        return [
            'id' => (int) $alumno->id,
            'matricula' => $alumno->matricula,
            'nombre' => trim(implode(' ', array_filter([$alumno->nombre, $alumno->apellido_paterno, $alumno->apellido_materno]))),
            'estatus' => $alumno->estatus,
            'activo' => (bool) $alumno->activo,
        ];
    }

    private function cicloIdDelHistorial(int $historialId): ?int
    {
        $id = DB::table('inscripcion_ciclos')->where('id', $historialId)->value('ciclo_escolar_id');
        return $id ? (int) $id : null;
    }

    private function ciclo($inicio, $fin): string
    {
        return $inicio && $fin ? "{$inicio}-{$fin}" : 'Ciclo no disponible';
    }

    private function contextoEtiquetado(array $contexto): array
    {
        $catalogos = [
            'nivel' => $contexto['nivel_id'] ? DB::table('niveles')->where('id', $contexto['nivel_id'])->value('nombre') : null,
            'grado' => $contexto['grado_id'] ? DB::table('grados')->where('id', $contexto['grado_id'])->value('nombre') : null,
            'generacion' => $contexto['generacion_id'] ? DB::table('generaciones')->where('id', $contexto['generacion_id'])->value('nombre') : null,
            'grupo' => null,
            'semestre' => $contexto['semestre_id'] ? DB::table('semestres')->where('id', $contexto['semestre_id'])->value('nombre') : null,
            'ciclo' => null,
        ];

        if ($contexto['grupo_id']) {
            $catalogos['grupo'] = DB::table('grupos as g')->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'g.asignacion_grupo_id')
                ->where('g.id', $contexto['grupo_id'])->value('ag.nombre');
        }
        if ($contexto['ciclo_escolar_id']) {
            $ciclo = DB::table('ciclo_escolares')->where('id', $contexto['ciclo_escolar_id'])->first(['inicio_anio', 'fin_anio']);
            $catalogos['ciclo'] = $ciclo ? $this->ciclo($ciclo->inicio_anio, $ciclo->fin_anio) : null;
        }

        return $catalogos;
    }

    private function ordenar(array $valor): array
    {
        ksort($valor);
        foreach ($valor as $clave => $item) {
            if (is_array($item)) {
                $valor[$clave] = $this->ordenar($item);
            }
        }
        return $valor;
    }
}
