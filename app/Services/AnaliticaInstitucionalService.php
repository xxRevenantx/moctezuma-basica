<?php

namespace App\Services;

use App\Models\AnaliticaInstitucionalAlerta;
use App\Models\AnaliticaInstitucionalSnapshot;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AnaliticaInstitucionalService
{
    public function generar(array $filtros): array
    {
        $filtros = $this->normalizarFiltros($filtros);
        $cicloId = (int) $filtros['ciclo_escolar_id'];
        $comparacionId = $filtros['ciclo_comparacion_id'] ? (int) $filtros['ciclo_comparacion_id'] : null;

        $historial = $this->metricasHistorial($cicloId, $filtros);
        $rendimiento = $this->rendimiento($cicloId, $filtros);
        $riesgo = $this->riesgo($cicloId, $filtros);
        $seguimiento = $this->seguimiento($cicloId, $filtros);
        $integridad = $this->integridad($cicloId, $filtros);
        $documentacion = $this->documentacion($cicloId, $filtros, $historial['matricula']);
        $horarios = $this->horarios($cicloId, $filtros);
        $comparacion = $comparacionId
            ? $this->compararCiclos($historial, $rendimiento, $comparacionId, $filtros)
            : $this->comparacionVacia();

        $resumen = [
            'matricula' => $historial['matricula'],
            'variacion_matricula' => $comparacion['variacion_matricula'],
            'permanencia' => $historial['permanencia'],
            'promocion' => $historial['promocion_porcentaje'],
            'promedio' => $rendimiento['promedio'],
            'reprobacion' => $rendimiento['reprobacion_porcentaje'],
            'riesgo_alto_critico' => $riesgo['alto_critico_porcentaje'],
            'documentacion' => $documentacion['cobertura_porcentaje'],
            'integridad_critica' => $integridad['criticos'],
            'seguimientos_activos' => $seguimiento['activos'],
            'conflictos_horario' => $horarios['conflictos_criticos'],
        ];

        $datos = [
            'filtros' => $filtros,
            'contexto' => $this->contexto($filtros),
            'resumen' => $resumen,
            'matricula' => $historial,
            'comparacion' => $comparacion,
            'rendimiento' => $rendimiento,
            'riesgo' => $riesgo,
            'seguimiento' => $seguimiento,
            'integridad' => $integridad,
            'documentacion' => $documentacion,
            'horarios' => $horarios,
            'tendencia_ciclos' => $this->tendenciaCiclos($filtros),
            'distribucion_niveles' => $this->distribucionNiveles($cicloId, $filtros),
            'grupos' => $this->indicadoresGrupos($cicloId, $filtros),
            'carga_docente' => $this->cargaDocente($cicloId, $filtros),
            'generado_at' => now()->toIso8601String(),
        ];

        $datos['alertas'] = $this->generarAlertas($datos);

        return $datos;
    }

    public function guardarSnapshot(array $datos, ?int $usuarioId = null, string $origen = 'manual'): AnaliticaInstitucionalSnapshot
    {
        $filtros = $datos['filtros'] ?? [];
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $snapshot = AnaliticaInstitucionalSnapshot::query()->create([
            'uuid' => (string) Str::uuid(),
            'ciclo_escolar_id' => $filtros['ciclo_escolar_id'] ?? null,
            'nivel_id' => $filtros['nivel_id'] ?? null,
            'generacion_id' => $filtros['generacion_id'] ?? null,
            'grado_id' => $filtros['grado_id'] ?? null,
            'grupo_id' => $filtros['grupo_id'] ?? null,
            'alcance' => filled($filtros['nivel_id'] ?? null) ? 'nivel' : 'institucional',
            'filtros' => $filtros,
            'datos' => $datos,
            'hash_integridad' => hash('sha256', (string) $json),
            'origen' => $origen,
            'generado_at' => now(),
            'generado_por' => $usuarioId,
        ]);

        $this->sincronizarAlertas($snapshot, $datos['alertas'] ?? []);

        return $snapshot;
    }

    public function ultimoSnapshot(array $filtros): ?AnaliticaInstitucionalSnapshot
    {
        if (! Schema::hasTable('analitica_institucional_snapshots')) {
            return null;
        }

        return AnaliticaInstitucionalSnapshot::query()
            ->when(filled($filtros['ciclo_escolar_id'] ?? null), fn ($q) => $q->where('ciclo_escolar_id', $filtros['ciclo_escolar_id']))
            ->when(filled($filtros['nivel_id'] ?? null), fn ($q) => $q->where('nivel_id', $filtros['nivel_id']), fn ($q) => $q->whereNull('nivel_id'))
            ->when(filled($filtros['generacion_id'] ?? null), fn ($q) => $q->where('generacion_id', $filtros['generacion_id']))
            ->latest('generado_at')
            ->first();
    }

    private function normalizarFiltros(array $filtros): array
    {
        $cicloId = (int) ($filtros['ciclo_escolar_id'] ?? 0);
        if ($cicloId <= 0 && Schema::hasTable('ciclo_escolares')) {
            $cicloId = (int) (DB::table('ciclo_escolares')->where('es_actual', 1)->value('id')
                ?? DB::table('ciclo_escolares')->orderByDesc('inicio_anio')->value('id'));
        }

        return [
            'ciclo_escolar_id' => $cicloId,
            'ciclo_comparacion_id' => $this->nullableInt($filtros['ciclo_comparacion_id'] ?? null),
            'nivel_id' => $this->nullableInt($filtros['nivel_id'] ?? null),
            'generacion_id' => $this->nullableInt($filtros['generacion_id'] ?? null),
            'grado_id' => $this->nullableInt($filtros['grado_id'] ?? null),
            'grupo_id' => $this->nullableInt($filtros['grupo_id'] ?? null),
        ];
    }

    private function nullableInt(mixed $valor): ?int
    {
        return filled($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    private function historialBase(int $cicloId, array $filtros, string $alias = 'ic'): Builder
    {
        $query = DB::table('inscripcion_ciclos as '.$alias)
            ->where($alias.'.ciclo_escolar_id', $cicloId)
            ->where($alias.'.estado', '!=', 'anulado');

        foreach (['nivel_id', 'generacion_id', 'grado_id', 'grupo_id'] as $campo) {
            if (filled($filtros[$campo] ?? null)) {
                $query->where($alias.'.'.$campo, $filtros[$campo]);
            }
        }

        return $query;
    }

    private function metricasHistorial(int $cicloId, array $filtros): array
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            return [
                'matricula' => 0, 'en_curso' => 0, 'promovidos' => 0, 'egresados' => 0,
                'no_promovidos' => 0, 'traslados' => 0, 'bajas' => 0, 'no_reinscritos' => 0,
                'reingresos' => 0, 'salidas' => 0, 'permanencia' => 0, 'promocion_porcentaje' => 0,
            ];
        }

        $base = $this->historialBase($cicloId, $filtros);
        $matricula = (clone $base)->distinct()->count('ic.inscripcion_id');
        $enCurso = (clone $base)->where('ic.estado', 'en_curso')->distinct()->count('ic.inscripcion_id');
        $promovidos = (clone $base)->where(function ($q): void {
            $q->where('ic.promovido', 1)
                ->orWhereIn('ic.resultado_final', ['promovido', 'promovido_grado', 'promovido_nivel', 'continuidad_interna']);
        })->distinct()->count('ic.inscripcion_id');
        $egresados = (clone $base)->where(function ($q): void {
            $q->where('ic.resultado_final', 'egresado')->orWhere('ic.estatus_actual_ciclo', 'egresado');
        })->distinct()->count('ic.inscripcion_id');
        $noPromovidos = (clone $base)->whereIn('ic.resultado_final', ['no_promovido', 'repetidor'])->distinct()->count('ic.inscripcion_id');
        $traslados = (clone $base)->where(function ($q): void {
            $q->whereIn('ic.resultado_final', ['traslado', 'trasladado'])
                ->orWhereIn('ic.estatus_actual_ciclo', ['traslado', 'trasladado']);
        })->distinct()->count('ic.inscripcion_id');
        $bajas = (clone $base)->where(function ($q): void {
            $q->whereIn('ic.resultado_final', ['baja_temporal', 'baja_definitiva'])
                ->orWhereIn('ic.estatus_actual_ciclo', ['baja_temporal', 'baja_definitiva', 'inactivo']);
        })->distinct()->count('ic.inscripcion_id');
        $noReinscritos = (clone $base)->where(function ($q): void {
            $q->where('ic.resultado_final', 'no_reinscrito')->orWhere('ic.estatus_actual_ciclo', 'no_reinscrito');
        })->distinct()->count('ic.inscripcion_id');
        $reingresos = Schema::hasColumn('inscripciones', 'indicador_reingreso')
            ? (clone $base)->join('inscripciones as i', 'i.id', '=', 'ic.inscripcion_id')->where('i.indicador_reingreso', 1)->distinct()->count('ic.inscripcion_id')
            : 0;

        $salidas = min($matricula, $traslados + $bajas + $noReinscritos);
        $permanencia = $matricula > 0 ? round((($matricula - $salidas) / $matricula) * 100, 1) : 0;
        $cerrados = (clone $base)->where('ic.estado', 'cerrado')->distinct()->count('ic.inscripcion_id');
        $promocionBase = max($cerrados, $promovidos + $egresados + $noPromovidos);
        $promocion = $promocionBase > 0 ? round((($promovidos + $egresados) / $promocionBase) * 100, 1) : 0;

        return [
            'matricula' => $matricula,
            'en_curso' => $enCurso,
            'promovidos' => $promovidos,
            'egresados' => $egresados,
            'no_promovidos' => $noPromovidos,
            'traslados' => $traslados,
            'bajas' => $bajas,
            'no_reinscritos' => $noReinscritos,
            'reingresos' => $reingresos,
            'salidas' => $salidas,
            'permanencia' => $permanencia,
            'promocion_porcentaje' => $promocion,
        ];
    }

    private function rendimiento(int $cicloId, array $filtros): array
    {
        if (! Schema::hasTable('calificaciones')) {
            return ['promedio' => 0, 'evaluaciones' => 0, 'aprobadas' => 0, 'reprobadas' => 0, 'pendientes' => 0, 'aprobacion_porcentaje' => 0, 'reprobacion_porcentaje' => 0, 'materias_reprobacion' => []];
        }

        $query = DB::table('calificaciones as c')->where('c.ciclo_escolar_id', $cicloId);
        foreach (['nivel_id', 'generacion_id', 'grado_id', 'grupo_id'] as $campo) {
            if (filled($filtros[$campo] ?? null)) {
                $query->where('c.'.$campo, $filtros[$campo]);
            }
        }

        $numericas = (clone $query)->where('c.es_numerica', 1)->whereNotNull('c.valor_numerico');
        $total = (clone $numericas)->count();
        $promedio = round((float) ((clone $numericas)->avg('c.valor_numerico') ?? 0), 2);
        $minima = (float) config('analitica_institucional.passing_grade', 6);
        $reprobadas = (clone $numericas)->where('c.valor_numerico', '<', $minima)->count();
        $aprobadas = max(0, $total - $reprobadas);
        $pendientes = (clone $query)->where(function ($q): void {
            $q->whereNull('c.calificacion')->orWhere('c.calificacion', '');
        })->count();

        $materias = (clone $numericas)
            ->leftJoin('asignacion_materias as am', 'am.id', '=', 'c.asignacion_materia_id')
            ->leftJoin('materias as m', 'm.id', '=', 'am.materia_id')
            ->selectRaw("COALESCE(m.materia, 'Materia sin nombre') materia, COUNT(*) evaluaciones, SUM(CASE WHEN c.valor_numerico < ? THEN 1 ELSE 0 END) reprobadas, ROUND(AVG(c.valor_numerico), 2) promedio", [$minima])
            ->groupBy('m.id', 'm.materia')
            ->havingRaw('reprobadas > 0')
            ->orderByDesc('reprobadas')
            ->limit(10)
            ->get()
            ->map(fn ($fila) => [
                'materia' => (string) $fila->materia,
                'evaluaciones' => (int) $fila->evaluaciones,
                'reprobadas' => (int) $fila->reprobadas,
                'promedio' => (float) $fila->promedio,
                'porcentaje' => (int) $fila->evaluaciones > 0 ? round(((int) $fila->reprobadas / (int) $fila->evaluaciones) * 100, 1) : 0,
            ])->all();

        return [
            'promedio' => $promedio,
            'evaluaciones' => $total,
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'pendientes' => $pendientes,
            'aprobacion_porcentaje' => $total > 0 ? round(($aprobadas / $total) * 100, 1) : 0,
            'reprobacion_porcentaje' => $total > 0 ? round(($reprobadas / $total) * 100, 1) : 0,
            'materias_reprobacion' => $materias,
        ];
    }

    private function riesgo(int $cicloId, array $filtros): array
    {
        $resultado = ['bajo' => 0, 'moderado' => 0, 'alto' => 0, 'critico' => 0, 'evaluados' => 0, 'alto_critico' => 0, 'alto_critico_porcentaje' => 0, 'factores' => []];
        if (! Schema::hasTable('riesgo_academico_evaluaciones')) {
            return $resultado;
        }

        $query = DB::table('riesgo_academico_evaluaciones as r')
            ->where('r.es_actual', 1)
            ->where('r.ciclo_escolar_id', $cicloId);
        foreach (['nivel_id', 'generacion_id', 'grado_id', 'grupo_id'] as $campo) {
            if (filled($filtros[$campo] ?? null)) {
                $query->where('r.'.$campo, $filtros[$campo]);
            }
        }

        $conteos = (clone $query)->selectRaw('nivel_riesgo, COUNT(*) total')->groupBy('nivel_riesgo')->pluck('total', 'nivel_riesgo');
        foreach (['bajo', 'moderado', 'alto', 'critico'] as $nivel) {
            $resultado[$nivel] = (int) ($conteos[$nivel] ?? 0);
        }
        $resultado['evaluados'] = array_sum([$resultado['bajo'], $resultado['moderado'], $resultado['alto'], $resultado['critico']]);
        $resultado['alto_critico'] = $resultado['alto'] + $resultado['critico'];
        $resultado['alto_critico_porcentaje'] = $resultado['evaluados'] > 0
            ? round(($resultado['alto_critico'] / $resultado['evaluados']) * 100, 1) : 0;

        $factores = [];
        (clone $query)->select('factores')->whereNotNull('factores')->get()->each(function ($fila) use (&$factores): void {
            $lista = is_string($fila->factores) ? json_decode($fila->factores, true) : $fila->factores;
            foreach ((array) $lista as $factor) {
                $nombre = (string) ($factor['nombre'] ?? $factor['detalle'] ?? 'Factor detectado');
                $factores[$nombre] = ($factores[$nombre] ?? 0) + 1;
            }
        });
        arsort($factores);
        $resultado['factores'] = collect($factores)->take(8)->map(fn ($total, $nombre) => ['nombre' => $nombre, 'total' => $total])->values()->all();

        return $resultado;
    }

    private function seguimiento(int $cicloId, array $filtros): array
    {
        $resultado = ['activos' => 0, 'cerrados' => 0, 'sin_responsable' => 0, 'revisiones_vencidas' => 0, 'acciones_vencidas' => 0, 'acciones_completadas' => 0, 'tiempo_atencion_dias' => 0];
        if (! Schema::hasTable('seguimiento_academico_casos')) {
            return $resultado;
        }

        $query = DB::table('seguimiento_academico_casos as s')->where('s.ciclo_escolar_id', $cicloId);
        if (filled($filtros['nivel_id'] ?? null)) {
            $query->where('s.nivel_id', $filtros['nivel_id']);
        }
        if (filled($filtros['generacion_id'] ?? null) || filled($filtros['grado_id'] ?? null) || filled($filtros['grupo_id'] ?? null)) {
            $query->join('inscripcion_ciclos as ic', 'ic.id', '=', 's.inscripcion_ciclo_id');
            foreach (['generacion_id', 'grado_id', 'grupo_id'] as $campo) {
                if (filled($filtros[$campo] ?? null)) {
                    $query->where('ic.'.$campo, $filtros[$campo]);
                }
            }
        }

        $resultado['activos'] = (clone $query)->whereIn('s.estado', ['abierto', 'en_seguimiento', 'pausado'])->count('s.id');
        $resultado['cerrados'] = (clone $query)->where('s.estado', 'cerrado')->count('s.id');
        $resultado['sin_responsable'] = (clone $query)->whereIn('s.estado', ['abierto', 'en_seguimiento', 'pausado'])->whereNull('s.responsable_id')->count('s.id');
        $resultado['revisiones_vencidas'] = (clone $query)->whereIn('s.estado', ['abierto', 'en_seguimiento', 'pausado'])->whereDate('s.proxima_revision_at', '<', today())->count('s.id');
        $resultado['tiempo_atencion_dias'] = round((float) ((clone $query)->where('s.estado', 'cerrado')->whereNotNull('s.abierto_at')->whereNotNull('s.cerrado_at')->selectRaw('AVG(TIMESTAMPDIFF(DAY, s.abierto_at, s.cerrado_at)) promedio')->value('promedio') ?? 0), 1);

        if (Schema::hasTable('seguimiento_academico_acciones')) {
            $acciones = DB::table('seguimiento_academico_acciones as a')
                ->join('seguimiento_academico_casos as s2', 's2.id', '=', 'a.seguimiento_caso_id')
                ->where('s2.ciclo_escolar_id', $cicloId);
            if (filled($filtros['nivel_id'] ?? null)) {
                $acciones->where('s2.nivel_id', $filtros['nivel_id']);
            }
            $resultado['acciones_vencidas'] = (clone $acciones)->whereIn('a.estado', ['pendiente', 'en_proceso'])->whereDate('a.fecha_limite', '<', today())->count('a.id');
            $resultado['acciones_completadas'] = (clone $acciones)->where('a.estado', 'completada')->count('a.id');
        }

        return $resultado;
    }

    private function integridad(int $cicloId, array $filtros): array
    {
        $resultado = ['abiertos' => 0, 'criticos' => 0, 'advertencias' => 0, 'informativos' => 0, 'en_revision' => 0, 'resueltos' => 0, 'categorias' => []];
        if (! Schema::hasTable('integridad_academica_casos')) {
            return $resultado;
        }

        $query = DB::table('integridad_academica_casos as ia')->where(function ($q) use ($cicloId): void {
            $q->where('ia.ciclo_escolar_id', $cicloId)->orWhereNull('ia.ciclo_escolar_id');
        });
        if (filled($filtros['nivel_id'] ?? null)) {
            $query->where(function ($q) use ($filtros): void {
                $q->where('ia.nivel_id', $filtros['nivel_id'])->orWhereNull('ia.nivel_id');
            });
        }

        $resultado['abiertos'] = (clone $query)->whereIn('ia.estado', ['pendiente', 'en_revision'])->count();
        $resultado['criticos'] = (clone $query)->whereIn('ia.estado', ['pendiente', 'en_revision'])->where('ia.severidad', 'critico')->count();
        $resultado['advertencias'] = (clone $query)->whereIn('ia.estado', ['pendiente', 'en_revision'])->where('ia.severidad', 'advertencia')->count();
        $resultado['informativos'] = (clone $query)->whereIn('ia.estado', ['pendiente', 'en_revision'])->where('ia.severidad', 'informativo')->count();
        $resultado['en_revision'] = (clone $query)->where('ia.estado', 'en_revision')->count();
        $resultado['resueltos'] = (clone $query)->where('ia.estado', 'resuelto')->count();
        $resultado['categorias'] = (clone $query)->whereIn('ia.estado', ['pendiente', 'en_revision'])
            ->selectRaw('categoria, COUNT(*) total')->groupBy('categoria')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($fila) => ['categoria' => ucfirst(str_replace('_', ' ', (string) $fila->categoria)), 'total' => (int) $fila->total])->all();

        return $resultado;
    }

    private function documentacion(int $cicloId, array $filtros, int $matricula): array
    {
        $resultado = ['tipos_obligatorios' => 0, 'esperados' => 0, 'cumplidos' => 0, 'pendientes' => 0, 'rechazados' => 0, 'cobertura_porcentaje' => 0];
        if (! Schema::hasTable('tipos_documentos') || ! Schema::hasTable('documentos_alumnos')) {
            return $resultado;
        }

        $tipos = DB::table('tipos_documentos')->where('activo', 1)->where('es_obligatorio', 1)->count();
        $resultado['tipos_obligatorios'] = $tipos;
        $resultado['esperados'] = $matricula * $tipos;

        $alumnos = $this->historialBase($cicloId, $filtros)->distinct()->pluck('ic.inscripcion_id');
        if ($alumnos->isEmpty() || $tipos === 0) {
            return $resultado;
        }

        $validos = DB::table('documentos_alumnos as d')
            ->join('tipos_documentos as td', 'td.id', '=', 'd.tipo_documento_id')
            ->whereIn('d.inscripcion_id', $alumnos)
            ->where('td.activo', 1)->where('td.es_obligatorio', 1)
            ->where('d.es_actual', 1)->whereNull('d.deleted_at')
            ->whereNotIn('d.estado', ['pendiente', 'rechazado', 'reemplazado', 'cancelada'])
            ->selectRaw("COUNT(DISTINCT CONCAT(d.inscripcion_id, '-', d.tipo_documento_id)) total")
            ->value('total');

        $noAplica = Schema::hasTable('documentos_alumnos_no_aplica')
            ? DB::table('documentos_alumnos_no_aplica as dna')
                ->join('tipos_documentos as td2', 'td2.id', '=', 'dna.tipo_documento_id')
                ->whereIn('dna.inscripcion_id', $alumnos)->where('dna.activo', 1)
                ->where('td2.activo', 1)->where('td2.es_obligatorio', 1)
                ->selectRaw("COUNT(DISTINCT CONCAT(dna.inscripcion_id, '-', dna.tipo_documento_id)) total")
                ->value('total')
            : 0;

        $rechazados = DB::table('documentos_alumnos as dr')->whereIn('dr.inscripcion_id', $alumnos)->where('dr.es_actual', 1)->where('dr.estado', 'rechazado')->count();
        $cumplidos = min($resultado['esperados'], (int) $validos + (int) $noAplica);
        $resultado['cumplidos'] = $cumplidos;
        $resultado['pendientes'] = max(0, $resultado['esperados'] - $cumplidos);
        $resultado['rechazados'] = $rechazados;
        $resultado['cobertura_porcentaje'] = $resultado['esperados'] > 0 ? round(($cumplidos / $resultado['esperados']) * 100, 1) : 0;

        return $resultado;
    }

    private function horarios(int $cicloId, array $filtros): array
    {
        $resultado = ['versiones' => 0, 'publicadas' => 0, 'borradores' => 0, 'conflictos_criticos' => 0, 'advertencias' => 0, 'puntaje_promedio' => 0, 'bloques_publicados' => 0, 'sesiones_compartidas' => 0, 'traslapes_excepcionales' => 0, 'docentes' => 0];
        if (! Schema::hasTable('horario_versiones')) {
            return $resultado;
        }

        $query = DB::table('horario_versiones as hv')->where('hv.ciclo_escolar_id', $cicloId);
        if (filled($filtros['nivel_id'] ?? null)) {
            $query->where('hv.nivel_id', $filtros['nivel_id']);
        }
        if (filled($filtros['generacion_id'] ?? null)) {
            $query->where('hv.generacion_id', $filtros['generacion_id']);
        }

        $versiones = (clone $query)->get();
        $resultado['versiones'] = $versiones->count();
        $resultado['publicadas'] = $versiones->where('estado', 'publicada')->count();
        $resultado['borradores'] = $versiones->whereIn('estado', ['propuesta', 'borrador', 'en_revision', 'programada'])->count();
        $resultado['puntaje_promedio'] = round((float) $versiones->whereNotNull('puntaje')->avg('puntaje'), 1);

        foreach ($versiones as $version) {
            $conflictos = is_string($version->conflictos) ? json_decode($version->conflictos, true) : $version->conflictos;
            $metricas = is_string($version->metricas) ? json_decode($version->metricas, true) : $version->metricas;
            $resultado['conflictos_criticos'] += (int) (data_get($conflictos, 'criticos') ?? data_get($metricas, 'conflictos_criticos') ?? 0);
            $resultado['advertencias'] += (int) (data_get($conflictos, 'advertencias') ?? data_get($metricas, 'advertencias') ?? 0);
        }

        if (Schema::hasTable('horario_version_detalles')) {
            $publicadas = (clone $query)->where('hv.estado', 'publicada')->pluck('hv.id');
            if ($publicadas->isNotEmpty()) {
                $detalles = DB::table('horario_version_detalles as hd')->whereIn('hd.horario_version_id', $publicadas);
                if (filled($filtros['grado_id'] ?? null)) $detalles->where('hd.grado_id', $filtros['grado_id']);
                if (filled($filtros['grupo_id'] ?? null)) $detalles->where('hd.grupo_id', $filtros['grupo_id']);
                $resultado['bloques_publicados'] = (clone $detalles)->count();
                $resultado['sesiones_compartidas'] = (clone $detalles)->where('hd.sesion_compartida', 1)->count();
                $resultado['traslapes_excepcionales'] = (clone $detalles)->where('hd.traslape_excepcional', 1)->count();
                $resultado['docentes'] = (clone $detalles)->whereNotNull('hd.profesor_id')->distinct()->count('hd.profesor_id');
            }
        }

        return $resultado;
    }

    private function compararCiclos(array $actual, array $rendimientoActual, int $comparacionId, array $filtros): array
    {
        $anterior = $this->metricasHistorial($comparacionId, $filtros);
        $rendimientoAnterior = $this->rendimiento($comparacionId, $filtros);

        return [
            'ciclo_id' => $comparacionId,
            'matricula_anterior' => $anterior['matricula'],
            'promedio_anterior' => $rendimientoAnterior['promedio'],
            'permanencia_anterior' => $anterior['permanencia'],
            'promocion_anterior' => $anterior['promocion_porcentaje'],
            'variacion_matricula' => $this->variacion($actual['matricula'], $anterior['matricula']),
            'variacion_promedio' => round($rendimientoActual['promedio'] - $rendimientoAnterior['promedio'], 2),
            'variacion_permanencia' => round($actual['permanencia'] - $anterior['permanencia'], 1),
            'variacion_promocion' => round($actual['promocion_porcentaje'] - $anterior['promocion_porcentaje'], 1),
        ];
    }

    private function comparacionVacia(): array
    {
        return ['ciclo_id' => null, 'matricula_anterior' => 0, 'promedio_anterior' => 0, 'permanencia_anterior' => 0, 'promocion_anterior' => 0, 'variacion_matricula' => 0, 'variacion_promedio' => 0, 'variacion_permanencia' => 0, 'variacion_promocion' => 0];
    }

    private function variacion(float|int $actual, float|int $anterior): float
    {
        return $anterior != 0 ? round((($actual - $anterior) / $anterior) * 100, 1) : ($actual > 0 ? 100.0 : 0.0);
    }

    private function tendenciaCiclos(array $filtros): array
    {
        if (! Schema::hasTable('ciclo_escolares')) return [];
        $limite = max(2, (int) config('analitica_institucional.trend_cycles', 5));
        $ciclos = DB::table('ciclo_escolares')->orderByDesc('inicio_anio')->limit($limite)->get()->sortBy('inicio_anio');

        return $ciclos->map(function ($ciclo) use ($filtros): array {
            $h = $this->metricasHistorial((int) $ciclo->id, $filtros);
            $r = $this->rendimiento((int) $ciclo->id, $filtros);
            $riesgo = $this->riesgo((int) $ciclo->id, $filtros);
            return [
                'ciclo_id' => (int) $ciclo->id,
                'ciclo' => $ciclo->inicio_anio.'-'.$ciclo->fin_anio,
                'matricula' => $h['matricula'],
                'promedio' => $r['promedio'],
                'permanencia' => $h['permanencia'],
                'promocion' => $h['promocion_porcentaje'],
                'riesgo' => $riesgo['alto_critico_porcentaje'],
            ];
        })->values()->all();
    }

    private function distribucionNiveles(int $cicloId, array $filtros): array
    {
        if (! Schema::hasTable('inscripcion_ciclos') || ! Schema::hasTable('niveles')) return [];
        $query = DB::table('inscripcion_ciclos as ic')
            ->join('niveles as n', 'n.id', '=', 'ic.nivel_id')
            ->where('ic.ciclo_escolar_id', $cicloId)
            ->where('ic.estado', '!=', 'anulado');
        foreach (['nivel_id', 'generacion_id', 'grado_id', 'grupo_id'] as $campo) {
            if (filled($filtros[$campo] ?? null)) $query->where('ic.'.$campo, $filtros[$campo]);
        }

        return $query->selectRaw('n.id, n.nombre, COUNT(DISTINCT ic.inscripcion_id) total')
            ->groupBy('n.id', 'n.nombre')->orderBy('n.id')->get()
            ->map(fn ($fila) => ['nivel_id' => (int) $fila->id, 'nivel' => (string) $fila->nombre, 'total' => (int) $fila->total])->all();
    }

    private function indicadoresGrupos(int $cicloId, array $filtros): array
    {
        if (! Schema::hasTable('grupos') || ! Schema::hasTable('inscripcion_ciclos')) return [];

        $alumnos = DB::table('inscripcion_ciclos as icg')
            ->where('icg.ciclo_escolar_id', $cicloId)
            ->where('icg.estado', '!=', 'anulado');
        foreach (['nivel_id', 'generacion_id', 'grado_id', 'grupo_id'] as $campo) {
            if (filled($filtros[$campo] ?? null)) $alumnos->where('icg.'.$campo, $filtros[$campo]);
        }
        $alumnos->selectRaw('icg.grupo_id, COUNT(DISTINCT icg.inscripcion_id) alumnos')->groupBy('icg.grupo_id');

        $calificaciones = DB::table('calificaciones as cg')->where('cg.ciclo_escolar_id', $cicloId)->where('cg.es_numerica', 1)->whereNotNull('cg.valor_numerico');
        foreach (['nivel_id', 'generacion_id', 'grado_id', 'grupo_id'] as $campo) {
            if (filled($filtros[$campo] ?? null)) $calificaciones->where('cg.'.$campo, $filtros[$campo]);
        }
        $calificaciones->selectRaw('cg.grupo_id, ROUND(AVG(cg.valor_numerico),2) promedio')->groupBy('cg.grupo_id');

        $riesgos = Schema::hasTable('riesgo_academico_evaluaciones')
            ? DB::table('riesgo_academico_evaluaciones as rg')->where('rg.ciclo_escolar_id', $cicloId)->where('rg.es_actual', 1)
                ->selectRaw("rg.grupo_id, SUM(CASE WHEN rg.nivel_riesgo IN ('alto','critico') THEN 1 ELSE 0 END) riesgo, COUNT(*) evaluados")->groupBy('rg.grupo_id')
            : null;

        $query = DB::query()->fromSub($alumnos, 'a')
            ->join('grupos as g', 'g.id', '=', 'a.grupo_id')
            ->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'g.asignacion_grupo_id')
            ->leftJoin('grados as gr', 'gr.id', '=', 'g.grado_id')
            ->leftJoin('semestres as se', 'se.id', '=', 'g.semestre_id')
            ->leftJoinSub($calificaciones, 'c', 'c.grupo_id', '=', 'g.id');
        if ($riesgos) $query->leftJoinSub($riesgos, 'r', 'r.grupo_id', '=', 'g.id');

        return $query->selectRaw("g.id, COALESCE(ag.nombre, g.clave, 'Sin grupo') grupo, COALESCE(gr.nombre, CONCAT(se.numero, '° semestre'), 'Sin grado') grado, a.alumnos, COALESCE(c.promedio,0) promedio".($riesgos ? ", COALESCE(r.riesgo,0) riesgo, COALESCE(r.evaluados,0) evaluados" : ", 0 riesgo, 0 evaluados"))
            ->orderByDesc('a.alumnos')->limit(25)->get()
            ->map(fn ($fila) => [
                'grupo_id' => (int) $fila->id,
                'grupo' => (string) $fila->grupo,
                'grado' => (string) $fila->grado,
                'alumnos' => (int) $fila->alumnos,
                'promedio' => (float) $fila->promedio,
                'riesgo' => (int) $fila->riesgo,
                'riesgo_porcentaje' => (int) $fila->evaluados > 0 ? round(((int) $fila->riesgo / (int) $fila->evaluados) * 100, 1) : 0,
            ])->all();
    }

    private function cargaDocente(int $cicloId, array $filtros): array
    {
        if (! Schema::hasTable('horario_version_detalles') || ! Schema::hasTable('horario_versiones')) return [];
        $query = DB::table('horario_version_detalles as hd')
            ->join('horario_versiones as hv', 'hv.id', '=', 'hd.horario_version_id')
            ->leftJoin('personas as p', 'p.id', '=', 'hd.profesor_id')
            ->where('hv.ciclo_escolar_id', $cicloId)->where('hv.estado', 'publicada')->whereNotNull('hd.profesor_id');
        if (filled($filtros['nivel_id'] ?? null)) $query->where('hd.nivel_id', $filtros['nivel_id']);
        if (filled($filtros['generacion_id'] ?? null)) $query->where('hd.generacion_id', $filtros['generacion_id']);
        if (filled($filtros['grado_id'] ?? null)) $query->where('hd.grado_id', $filtros['grado_id']);
        if (filled($filtros['grupo_id'] ?? null)) $query->where('hd.grupo_id', $filtros['grupo_id']);

        return $query->selectRaw("hd.profesor_id, TRIM(CONCAT(COALESCE(p.nombre,''),' ',COALESCE(p.apellido_paterno,''),' ',COALESCE(p.apellido_materno,''))) docente, COUNT(*) bloques, COUNT(DISTINCT hd.grupo_id) grupos, SUM(hd.sesion_compartida = 1) compartidas, SUM(hd.traslape_excepcional = 1) excepcionales")
            ->groupBy('hd.profesor_id', 'p.nombre', 'p.apellido_paterno', 'p.apellido_materno')
            ->orderByDesc('bloques')->limit(15)->get()
            ->map(fn ($fila) => [
                'profesor_id' => (int) $fila->profesor_id,
                'docente' => trim((string) $fila->docente) ?: 'Docente sin nombre',
                'bloques' => (int) $fila->bloques,
                'grupos' => (int) $fila->grupos,
                'compartidas' => (int) $fila->compartidas,
                'excepcionales' => (int) $fila->excepcionales,
            ])->all();
    }

    private function contexto(array $filtros): array
    {
        $ciclo = Schema::hasTable('ciclo_escolares') && $filtros['ciclo_escolar_id']
            ? DB::table('ciclo_escolares')->where('id', $filtros['ciclo_escolar_id'])->first() : null;
        $comparacion = Schema::hasTable('ciclo_escolares') && $filtros['ciclo_comparacion_id']
            ? DB::table('ciclo_escolares')->where('id', $filtros['ciclo_comparacion_id'])->first() : null;

        return [
            'ciclo' => $ciclo ? $ciclo->inicio_anio.'-'.$ciclo->fin_anio : 'Sin ciclo',
            'ciclo_comparacion' => $comparacion ? $comparacion->inicio_anio.'-'.$comparacion->fin_anio : null,
            'nivel' => $filtros['nivel_id'] && Schema::hasTable('niveles') ? DB::table('niveles')->where('id', $filtros['nivel_id'])->value('nombre') : 'Todos los niveles',
            'generacion' => $filtros['generacion_id'] && Schema::hasTable('generaciones') ? $this->etiquetaGeneracion((int) $filtros['generacion_id']) : 'Todas las generaciones',
            'grado' => $filtros['grado_id'] && Schema::hasTable('grados') ? DB::table('grados')->where('id', $filtros['grado_id'])->value('nombre') : 'Todos los grados',
            'grupo' => $filtros['grupo_id'] && Schema::hasTable('grupos') ? (DB::table('grupos as g')->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'g.asignacion_grupo_id')->where('g.id', $filtros['grupo_id'])->selectRaw("COALESCE(ag.nombre, g.clave, 'Grupo') etiqueta")->value('etiqueta') ?: 'Grupo no disponible') : 'Todos los grupos',
        ];
    }

    private function etiquetaGeneracion(int $id): string
    {
        $g = DB::table('generaciones')->where('id', $id)->first();
        return $g ? ((string) ($g->nombre ?: $g->anio_ingreso.'-'.$g->anio_egreso)) : 'Generación no disponible';
    }

    private function generarAlertas(array $datos): array
    {
        $umbrales = config('analitica_institucional.alerts', []);
        if (Schema::hasTable('analitica_institucional_configuraciones')) {
            $valor = DB::table('analitica_institucional_configuraciones')->where('clave', 'umbrales_alertas')->value('valor');
            $db = is_string($valor) ? json_decode($valor, true) : $valor;
            if (is_array($db)) $umbrales = array_merge($umbrales, $db);
        }

        $alertas = [];
        $agregar = function (string $categoria, string $severidad, string $titulo, string $mensaje, array $evidencia = []) use (&$alertas): void {
            $alertas[] = compact('categoria', 'severidad', 'titulo', 'mensaje', 'evidencia');
        };

        if (($datos['comparacion']['ciclo_id'] ?? null) && ($datos['comparacion']['variacion_matricula'] ?? 0) <= -abs((float) ($umbrales['matricula_caida_porcentaje'] ?? 5))) {
            $agregar('matricula', 'critico', 'Disminución de matrícula', 'La matrícula disminuyó '.$datos['comparacion']['variacion_matricula'].'% respecto al ciclo comparado.', ['variacion' => $datos['comparacion']['variacion_matricula']]);
        }
        if (($datos['matricula']['permanencia'] ?? 0) < (float) ($umbrales['permanencia_minima_porcentaje'] ?? 90) && ($datos['matricula']['matricula'] ?? 0) > 0) {
            $agregar('permanencia', 'advertencia', 'Permanencia por debajo del objetivo', 'La permanencia calculada es de '.$datos['matricula']['permanencia'].'%.', ['permanencia' => $datos['matricula']['permanencia']]);
        }
        if (($datos['riesgo']['alto_critico_porcentaje'] ?? 0) >= (float) ($umbrales['riesgo_alto_porcentaje'] ?? 10)) {
            $agregar('riesgo', 'critico', 'Concentración de riesgo académico', $datos['riesgo']['alto_critico'].' alumnos están en riesgo alto o crítico ('.$datos['riesgo']['alto_critico_porcentaje'].'%).', ['alumnos' => $datos['riesgo']['alto_critico']]);
        }
        if (($datos['documentacion']['cobertura_porcentaje'] ?? 100) < (float) ($umbrales['documentacion_minima_porcentaje'] ?? 85) && ($datos['documentacion']['esperados'] ?? 0) > 0) {
            $agregar('documentacion', 'advertencia', 'Cobertura documental baja', 'La cobertura de documentos obligatorios es de '.$datos['documentacion']['cobertura_porcentaje'].'%.', ['pendientes' => $datos['documentacion']['pendientes']]);
        }
        if (($datos['integridad']['criticos'] ?? 0) > 0) {
            $agregar('integridad', 'critico', 'Casos críticos de integridad', 'Existen '.$datos['integridad']['criticos'].' casos críticos abiertos que requieren revisión.', ['casos' => $datos['integridad']['criticos']]);
        }
        if (($datos['rendimiento']['pendientes'] ?? 0) > 0) {
            $agregar('calificaciones', 'advertencia', 'Evaluaciones pendientes', 'Se detectaron '.$datos['rendimiento']['pendientes'].' registros de calificación pendientes.', ['pendientes' => $datos['rendimiento']['pendientes']]);
        }
        if (($datos['seguimiento']['acciones_vencidas'] ?? 0) > 0 || ($datos['seguimiento']['revisiones_vencidas'] ?? 0) > 0) {
            $agregar('seguimiento', 'advertencia', 'Seguimientos vencidos', 'Hay '.$datos['seguimiento']['acciones_vencidas'].' acciones y '.$datos['seguimiento']['revisiones_vencidas'].' revisiones vencidas.', ['acciones' => $datos['seguimiento']['acciones_vencidas'], 'revisiones' => $datos['seguimiento']['revisiones_vencidas']]);
        }
        if (($datos['horarios']['conflictos_criticos'] ?? 0) > 0) {
            $agregar('horarios', 'critico', 'Conflictos críticos de horario', 'Las versiones del contexto contienen '.$datos['horarios']['conflictos_criticos'].' conflictos críticos.', ['conflictos' => $datos['horarios']['conflictos_criticos']]);
        }
        if (($datos['horarios']['publicadas'] ?? 0) === 0 && ($datos['matricula']['matricula'] ?? 0) > 0) {
            $agregar('horarios', 'informativo', 'Sin horario publicado', 'No existe una versión de horario publicada para el contexto seleccionado.');
        }

        return $alertas;
    }

    private function sincronizarAlertas(AnaliticaInstitucionalSnapshot $snapshot, array $alertas): void
    {
        if (! Schema::hasTable('analitica_institucional_alertas')) return;
        $activas = [];
        foreach ($alertas as $alerta) {
            $fingerprint = hash('sha256', implode('|', [
                $snapshot->ciclo_escolar_id ?: 'general', $snapshot->nivel_id ?: 'todos',
                $alerta['categoria'] ?? 'general', $alerta['titulo'] ?? 'alerta',
            ]));
            $activas[] = $fingerprint;
            AnaliticaInstitucionalAlerta::query()->updateOrCreate(['fingerprint' => $fingerprint], [
                'snapshot_id' => $snapshot->id,
                'ciclo_escolar_id' => $snapshot->ciclo_escolar_id,
                'nivel_id' => $snapshot->nivel_id,
                'categoria' => $alerta['categoria'] ?? 'general',
                'severidad' => $alerta['severidad'] ?? 'advertencia',
                'estado' => 'activa',
                'titulo' => $alerta['titulo'] ?? 'Alerta institucional',
                'mensaje' => $alerta['mensaje'] ?? '',
                'evidencia' => $alerta['evidencia'] ?? [],
                'detectada_at' => now(),
                'resuelta_at' => null,
                'resuelta_por' => null,
                'motivo_resolucion' => null,
            ]);
        }

        AnaliticaInstitucionalAlerta::query()
            ->where('ciclo_escolar_id', $snapshot->ciclo_escolar_id)
            ->when($snapshot->nivel_id, fn ($q) => $q->where('nivel_id', $snapshot->nivel_id), fn ($q) => $q->whereNull('nivel_id'))
            ->where('estado', 'activa')
            ->when($activas !== [], fn ($q) => $q->whereNotIn('fingerprint', $activas))
            ->update(['estado' => 'resuelta', 'resuelta_at' => now(), 'motivo_resolucion' => 'La condición dejó de detectarse en una instantánea posterior.']);
    }
}
