<?php

namespace App\Services;

use App\Models\AlertaAcademica;
use App\Models\AsignacionMateria;
use App\Models\AsistenciaFinalBachillerato;
use App\Models\Calificacion;
use App\Models\CalificacionCampoFormativo;
use App\Models\FichaDescriptiva;
use App\Models\InscripcionCiclo;
use App\Models\IntegridadAcademicaCaso;
use App\Models\Periodos;
use App\Models\ProyeccionContinuidad;
use App\Models\RiesgoAcademicoConfiguracion;
use App\Models\RiesgoAcademicoEvaluacion;
use App\Models\RiesgoAcademicoRegla;
use App\Models\SeguimientoAcademicoAccion;
use App\Models\SeguimientoAcademicoCaso;
use App\Models\SeguimientoAcademicoEvento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RiesgoAcademicoService
{
    /** @return array<string, int> */
    public function evaluarLote(array $filtros = [], ?int $usuarioId = null, bool $crearCasos = true): array
    {
        $query = InscripcionCiclo::query()
            ->with(['inscripcion', 'nivel', 'cicloEscolar'])
            ->where('estado', InscripcionCiclo::ESTADO_EN_CURSO)
            ->when($filtros['ciclo_escolar_id'] ?? null, fn (Builder $q, $id) => $q->where('ciclo_escolar_id', $id))
            ->when($filtros['nivel_id'] ?? null, fn (Builder $q, $id) => $q->where('nivel_id', $id))
            ->when($filtros['inscripcion_id'] ?? null, fn (Builder $q, $id) => $q->where('inscripcion_id', $id))
            ->orderBy('id');

        $resultado = ['evaluados' => 0, 'creados' => 0, 'sin_cambios' => 0, 'casos_abiertos' => 0, 'alertas' => 0, 'errores' => 0];

        $query->chunkById(100, function (Collection $historiales) use (&$resultado, $usuarioId, $crearCasos): void {
            foreach ($historiales as $historial) {
                try {
                    $evaluacion = $this->evaluarHistorial($historial, $usuarioId, $crearCasos);
                    $resultado['evaluados']++;
                    $resultado[$evaluacion->wasRecentlyCreated ? 'creados' : 'sin_cambios']++;
                    $resultado['casos_abiertos'] += (int) ($evaluacion->getAttribute('caso_abierto_en_ejecucion') ?? 0);
                    $resultado['alertas'] += (int) ($evaluacion->getAttribute('alertas_generadas_en_ejecucion') ?? 0);
                } catch (\Throwable $e) {
                    report($e);
                    $resultado['errores']++;
                }
            }
        });

        $resultado['alertas'] += $this->sincronizarVencimientos();

        return $resultado;
    }

    public function evaluarHistorial(InscripcionCiclo $historial, ?int $usuarioId = null, bool $crearCaso = true): RiesgoAcademicoEvaluacion
    {
        $historial->loadMissing(['inscripcion', 'nivel', 'cicloEscolar']);
        $metricas = $this->calcularMetricas($historial);
        $reglas = RiesgoAcademicoRegla::query()->activas()->get()
            ->filter(fn (RiesgoAcademicoRegla $regla): bool => $regla->aplicaANivel($historial->nivel?->slug, $historial->nivel_id));

        [$puntaje, $factores, $reglasAplicadas] = $this->aplicarReglas($reglas, $metricas);
        $nivelRiesgo = $this->clasificar($puntaje);
        $snapshot = [
            'historial' => [
                'id' => $historial->id,
                'updated_at' => optional($historial->updated_at)->toIso8601String(),
                'estado' => $historial->estado,
                'estatus' => $historial->estatus_actual_ciclo,
                'resultado' => $historial->resultado_final,
            ],
            'metricas' => $metricas,
            'reglas' => $reglasAplicadas,
            'puntaje' => $puntaje,
            'nivel' => $nivelRiesgo,
        ];
        $hash = hash('sha256', json_encode($this->ordenarRecursivo($snapshot), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return DB::transaction(function () use ($historial, $usuarioId, $crearCaso, $metricas, $factores, $reglasAplicadas, $puntaje, $nivelRiesgo, $hash): RiesgoAcademicoEvaluacion {
            $actual = RiesgoAcademicoEvaluacion::query()
                ->where('inscripcion_ciclo_id', $historial->id)
                ->where('es_actual', true)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($actual && hash_equals((string) $actual->snapshot_hash, $hash)) {
                $actual->forceFill(['evaluado_at' => now(), 'evaluado_por' => $usuarioId])->save();
                $actual->setAttribute('caso_abierto_en_ejecucion', 0);
                $actual->setAttribute('alertas_generadas_en_ejecucion', 0);

                return $actual;
            }

            if ($actual) {
                $actual->forceFill(['es_actual' => false])->save();
            }

            $evaluacion = RiesgoAcademicoEvaluacion::create([
                'inscripcion_id' => $historial->inscripcion_id,
                'inscripcion_ciclo_id' => $historial->id,
                'ciclo_escolar_id' => $historial->ciclo_escolar_id,
                'nivel_id' => $historial->nivel_id,
                'grado_id' => $historial->grado_id,
                'grupo_id' => $historial->grupo_id,
                'generacion_id' => $historial->generacion_id,
                'semestre_id' => $historial->semestre_id,
                'puntaje' => $puntaje,
                'nivel_riesgo' => $nivelRiesgo,
                'factores' => $factores,
                'metricas' => $metricas,
                'reglas_aplicadas' => $reglasAplicadas,
                'origen' => $usuarioId ? 'manual' : 'automatico',
                'snapshot_hash' => $hash,
                'es_actual' => true,
                'evaluado_at' => now(),
                'evaluado_por' => $usuarioId,
            ]);

            [$casoCreado, $alertas] = $this->sincronizarCaso($evaluacion, $actual, $crearCaso, $usuarioId);
            $evaluacion->setAttribute('caso_abierto_en_ejecucion', $casoCreado ? 1 : 0);
            $evaluacion->setAttribute('alertas_generadas_en_ejecucion', $alertas);

            return $evaluacion;
        });
    }

    /** @return array<string, mixed> */
    private function calcularMetricas(InscripcionCiclo $historial): array
    {
        $slug = (string) ($historial->nivel?->slug ?? '');
        $calificaciones = Calificacion::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where(function (Builder $q) use ($historial): void {
                $q->where('inscripcion_ciclo_id', $historial->id)
                    ->orWhere(function (Builder $fallback) use ($historial): void {
                        $fallback->whereNull('inscripcion_ciclo_id')->where('ciclo_escolar_id', $historial->ciclo_escolar_id);
                    });
            })
            ->where('es_numerica', true)
            ->whereNotNull('valor_numerico')
            ->get(['id', 'asignacion_materia_id', 'periodo_id', 'valor_numerico']);

        $campos = CalificacionCampoFormativo::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('ciclo_escolar_id', $historial->ciclo_escolar_id)
            ->where(function (Builder $q) use ($historial): void {
                $q->where('inscripcion_ciclo_id', $historial->id)->orWhereNull('inscripcion_ciclo_id');
            })
            ->get(['campo_formativo_id', 'periodo_id', 'calificacion_oficial', 'calificacion_sugerida']);

        $periodos = Periodos::query()
            ->where('ciclo_escolar_id', $historial->ciclo_escolar_id)
            ->where('nivel_id', $historial->nivel_id)
            ->when($historial->generacion_id, fn (Builder $q, $id) => $q->where(function (Builder $g) use ($id): void {
                $g->where('generacion_id', $id)->orWhereNull('generacion_id');
            }))
            ->when($historial->semestre_id, fn (Builder $q, $id) => $q->where(function (Builder $s) use ($id): void {
                $s->where('semestre_id', $id)->orWhereNull('semestre_id');
            }))
            ->orderBy('fecha_fin')
            ->orderBy('id')
            ->get(['id', 'fecha_inicio', 'fecha_fin']);

        $idsConDatos = $calificaciones->pluck('periodo_id')->merge($campos->pluck('periodo_id'))->filter()->unique();
        $periodosConcluidos = $periodos->filter(fn (Periodos $periodo): bool =>
            ($periodo->fecha_fin && $periodo->fecha_fin->lte(today())) || $idsConDatos->contains($periodo->id)
        );

        $valoresPorElemento = collect();
        if ($slug === 'primaria' && $campos->isNotEmpty()) {
            $valoresPorElemento = $campos->groupBy('campo_formativo_id')->map(function (Collection $items): ?float {
                $valores = $items->map(fn ($item) => $item->calificacion_oficial ?? $item->calificacion_sugerida)->filter(fn ($v) => is_numeric($v));
                return $valores->isNotEmpty() ? round((float) $valores->avg(), 2) : null;
            })->filter(fn ($v) => $v !== null);
        } else {
            $valoresPorElemento = $calificaciones->groupBy('asignacion_materia_id')->map(fn (Collection $items) => round((float) $items->avg('valor_numerico'), 2));
        }

        $promedio = $valoresPorElemento->isNotEmpty() ? round((float) $valoresPorElemento->avg(), 2) : null;
        $reprobadas = $valoresPorElemento->filter(fn ($valor) => (float) $valor < 6)->count();

        $promediosPeriodo = ($slug === 'primaria' && $campos->isNotEmpty())
            ? $campos->groupBy('periodo_id')->map(function (Collection $items): ?float {
                $valores = $items->map(fn ($item) => $item->calificacion_oficial ?? $item->calificacion_sugerida)->filter(fn ($v) => is_numeric($v));
                return $valores->isNotEmpty() ? round((float) $valores->avg(), 2) : null;
            })->filter(fn ($v) => $v !== null)
            : $calificaciones->groupBy('periodo_id')->map(fn (Collection $items) => round((float) $items->avg('valor_numerico'), 2));

        $promediosOrdenados = $periodos->pluck('id')->mapWithKeys(fn ($id) => [$id => $promediosPeriodo->get($id)])->filter(fn ($v) => $v !== null)->values();
        $descenso = $promediosOrdenados->count() >= 2
            ? round((float) $promediosOrdenados->first() - (float) $promediosOrdenados->last(), 2)
            : 0.0;

        $asignacionesEsperadas = 0;
        if (! in_array($slug, ['preescolar', 'primaria'], true)) {
            $asignacionesEsperadas = AsignacionMateria::query()
                ->where('ciclo_escolar_id', $historial->ciclo_escolar_id)
                ->where('nivel_id', $historial->nivel_id)
                ->where('grado_id', $historial->grado_id)
                ->where('grupo_id', $historial->grupo_id)
                ->when($historial->semestre_id, fn (Builder $q, $id) => $q->where('semestre_id', $id))
                ->whereIn('estado', [AsignacionMateria::ESTADO_ACTIVA, AsignacionMateria::ESTADO_CERRADA])
                ->whereHas('materia', fn (Builder $q) => $q->where('calificable', true)->where('extra', false)->where('participa_en_calificacion_oficial', true))
                ->count();
        }

        $pendientes = 0;
        if ($slug === 'primaria') {
            $esperados = $periodosConcluidos->count() * 4;
            $capturados = $campos->whereIn('periodo_id', $periodosConcluidos->pluck('id'))
                ->map(fn ($item) => $item->periodo_id.'-'.$item->campo_formativo_id)->unique()->count();
            $pendientes = max(0, $esperados - $capturados);
        } elseif (! in_array($slug, ['preescolar'], true)) {
            $esperados = $periodosConcluidos->count() * $asignacionesEsperadas;
            $capturados = $calificaciones->whereIn('periodo_id', $periodosConcluidos->pluck('id'))
                ->map(fn ($item) => $item->periodo_id.'-'.$item->asignacion_materia_id)->unique()->count();
            $pendientes = max(0, $esperados - $capturados);
        }

        $fichasPendientes = 0;
        if ($slug === 'preescolar') {
            $fichas = FichaDescriptiva::query()
                ->where('inscripcion_id', $historial->inscripcion_id)
                ->where('ciclo_escolar_id', $historial->ciclo_escolar_id)
                ->get(['periodo_id', 'periodo', 'campo']);
            $esperadas = $periodosConcluidos->count() * 4;
            $capturadas = $fichas->whereIn('periodo_id', $periodosConcluidos->pluck('id'))
                ->map(fn ($item) => ($item->periodo_id ?: $item->periodo).'-'.mb_strtolower(trim((string) $item->campo)))->unique()->count();
            $fichasPendientes = max(0, $esperadas - $capturadas);
        }

        $asistencia = AsistenciaFinalBachillerato::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('ciclo_escolar_id', $historial->ciclo_escolar_id)
            ->whereNotNull('porcentaje')
            ->avg('porcentaje');

        $integridadCritica = IntegridadAcademicaCaso::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('severidad', 'critico')
            ->whereIn('estado', ['pendiente', 'en_revision'])
            ->count();

        $proyeccion = ProyeccionContinuidad::query()
            ->where('inscripcion_id', $historial->inscripcion_id)
            ->where('inscripcion_ciclo_origen_id', $historial->id)
            ->where('estado', 'pendiente')
            ->oldest('fecha_proyeccion')
            ->first();
        $diasProyeccion = $proyeccion?->fecha_proyeccion ? $proyeccion->fecha_proyeccion->diffInDays(today()) : 0;

        $caso = SeguimientoAcademicoCaso::query()->activos()->where('inscripcion_ciclo_id', $historial->id)->first();
        $accionesVencidas = $caso
            ? SeguimientoAcademicoAccion::query()->where('seguimiento_caso_id', $caso->id)->whereIn('estado', ['pendiente', 'en_proceso'])->whereDate('fecha_limite', '<', today())->count()
            : 0;

        return [
            'nivel_slug' => $slug,
            'promedio' => $promedio,
            'elementos_evaluados' => $valoresPorElemento->count(),
            'reprobadas' => $reprobadas,
            'promedios_periodo' => $promediosOrdenados->all(),
            'descenso_periodos' => max(0, $descenso),
            'periodos_concluidos' => $periodosConcluidos->count(),
            'calificaciones_pendientes' => $pendientes,
            'asignaciones_esperadas' => $asignacionesEsperadas,
            'asistencia_promedio' => $asistencia !== null ? round((float) $asistencia, 2) : null,
            'fichas_pendientes' => $fichasPendientes,
            'no_promovido' => in_array($historial->estatus_actual_ciclo, ['no_promovido'], true) || in_array($historial->resultado_final, ['no_promovido', 'repetidor'], true),
            'integridad_critica' => $integridadCritica,
            'dias_proyeccion_pendiente' => $diasProyeccion,
            'acciones_vencidas' => $accionesVencidas,
            'observaciones_registradas' => DB::table('observaciones_inscripciones')->where('inscripcion_id', $historial->inscripcion_id)->where('ciclo_escolar_id', $historial->ciclo_escolar_id)->whereNotNull('contenido')->count(),
        ];
    }

    /** @return array{0:int,1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>} */
    private function aplicarReglas(Collection $reglas, array $metricas): array
    {
        $puntaje = 0.0;
        $factores = [];
        $aplicadas = [];

        foreach ($reglas as $regla) {
            $parametros = $regla->parametros ?? [];
            $peso = (float) $regla->peso;
            $maximo = (float) $regla->max_puntos;
            $puntos = 0.0;
            $detalle = null;
            $valor = null;

            switch ($regla->tipo_calculo) {
                case 'promedio_bajo':
                    $valor = $metricas['promedio'];
                    if ($valor !== null && $valor < (float) ($parametros['umbral_alerta'] ?? 7)) {
                        $puntos = $valor < (float) ($parametros['umbral_critico'] ?? 6) ? $maximo : $peso;
                        $detalle = 'Promedio actual: '.number_format((float) $valor, 1);
                    }
                    break;
                case 'materias_reprobadas':
                    $valor = (int) $metricas['reprobadas'];
                    $puntos = min($maximo, $valor * $peso);
                    $detalle = $valor > 0 ? $valor.' materia(s) o campo(s) con promedio menor a 6' : null;
                    break;
                case 'tendencia_descendente':
                    $valor = (float) $metricas['descenso_periodos'];
                    if ($valor >= (float) ($parametros['descenso_minimo'] ?? 1)) {
                        $puntos = min($maximo, $peso + max(0, floor($valor - 1) * 2));
                        $detalle = 'Descenso de '.number_format($valor, 1).' punto(s) entre periodos';
                    }
                    break;
                case 'calificaciones_pendientes':
                    $valor = (int) $metricas['calificaciones_pendientes'];
                    $puntos = min($maximo, $valor * $peso);
                    $detalle = $valor > 0 ? $valor.' evaluación(es) pendiente(s) en periodos concluidos' : null;
                    break;
                case 'asistencia_baja':
                    $valor = $metricas['asistencia_promedio'];
                    if ($valor !== null && $valor < (float) ($parametros['umbral'] ?? 80)) {
                        $puntos = $valor < (float) ($parametros['umbral_critico'] ?? 70) ? $maximo : $peso;
                        $detalle = 'Asistencia promedio: '.number_format((float) $valor, 1).'%';
                    }
                    break;
                case 'fichas_pendientes':
                    $valor = (int) $metricas['fichas_pendientes'];
                    $puntos = min($maximo, $valor * $peso);
                    $detalle = $valor > 0 ? $valor.' campo(s) descriptivo(s) pendiente(s)' : null;
                    break;
                case 'resultado_no_promovido':
                    $valor = (bool) $metricas['no_promovido'];
                    $puntos = $valor ? min($maximo, $peso) : 0;
                    $detalle = $valor ? 'El ciclo registra no promoción o repetición' : null;
                    break;
                case 'integridad_critica':
                    $valor = (int) $metricas['integridad_critica'];
                    $puntos = min($maximo, $valor * $peso);
                    $detalle = $valor > 0 ? $valor.' caso(s) crítico(s) de integridad abierto(s)' : null;
                    break;
                case 'proyeccion_vencida':
                    $valor = (int) $metricas['dias_proyeccion_pendiente'];
                    $dias = (int) ($parametros['dias'] ?? 30);
                    if ($valor > $dias) {
                        $puntos = min($maximo, $peso + floor(($valor - $dias) / 30) * 5);
                        $detalle = 'Proyección pendiente desde hace '.$valor.' días';
                    }
                    break;
                case 'seguimiento_vencido':
                    $valor = (int) $metricas['acciones_vencidas'];
                    $puntos = min($maximo, $valor * $peso);
                    $detalle = $valor > 0 ? $valor.' acción(es) de intervención vencida(s)' : null;
                    break;
            }

            $puntos = round(max(0, min($maximo, $puntos)), 2);
            $aplicadas[] = ['codigo' => $regla->codigo, 'peso' => $peso, 'max_puntos' => $maximo, 'parametros' => $parametros, 'puntos' => $puntos];

            if ($puntos > 0) {
                $factores[] = [
                    'codigo' => $regla->codigo,
                    'nombre' => $regla->nombre,
                    'categoria' => $regla->categoria,
                    'puntos' => $puntos,
                    'valor' => $valor,
                    'detalle' => $detalle,
                ];
                $puntaje += $puntos;
            }
        }

        return [(int) min(100, round($puntaje)), $factores, $aplicadas];
    }

    private function clasificar(int $puntaje): string
    {
        $umbrales = RiesgoAcademicoConfiguracion::query()->where('clave', 'umbrales')->value('valor')
            ?? ['moderado' => 20, 'alto' => 40, 'critico' => 70];
        if (is_string($umbrales)) {
            $umbrales = json_decode($umbrales, true) ?: [];
        }

        return match (true) {
            $puntaje >= (int) ($umbrales['critico'] ?? 70) => 'critico',
            $puntaje >= (int) ($umbrales['alto'] ?? 40) => 'alto',
            $puntaje >= (int) ($umbrales['moderado'] ?? 20) => 'moderado',
            default => 'bajo',
        };
    }

    /** @return array{0:bool,1:int} */
    private function sincronizarCaso(RiesgoAcademicoEvaluacion $evaluacion, ?RiesgoAcademicoEvaluacion $anterior, bool $crearCaso, ?int $usuarioId): array
    {
        $caso = SeguimientoAcademicoCaso::query()->activos()->where('inscripcion_ciclo_id', $evaluacion->inscripcion_ciclo_id)->lockForUpdate()->first();
        $casoCreado = false;
        $alertas = 0;
        $orden = ['bajo' => 0, 'moderado' => 1, 'alto' => 2, 'critico' => 3];

        if ($crearCaso && ! $caso && in_array($evaluacion->nivel_riesgo, ['alto', 'critico'], true)) {
            $caso = SeguimientoAcademicoCaso::create([
                'folio' => 'SEG-'.now()->format('Y').'-'.Str::upper(Str::substr((string) Str::ulid(), -8)),
                'inscripcion_id' => $evaluacion->inscripcion_id,
                'inscripcion_ciclo_id' => $evaluacion->inscripcion_ciclo_id,
                'riesgo_evaluacion_id' => $evaluacion->id,
                'ciclo_escolar_id' => $evaluacion->ciclo_escolar_id,
                'nivel_id' => $evaluacion->nivel_id,
                'estado' => 'abierto',
                'prioridad' => $evaluacion->nivel_riesgo === 'critico' ? 'critica' : 'alta',
                'riesgo_inicial' => $evaluacion->nivel_riesgo,
                'riesgo_actual' => $evaluacion->nivel_riesgo,
                'puntaje_inicial' => $evaluacion->puntaje,
                'puntaje_actual' => $evaluacion->puntaje,
                'motivo_apertura' => 'Caso abierto automáticamente por el semáforo de riesgo académico.',
                'resumen' => collect($evaluacion->factores ?? [])->pluck('detalle')->filter()->implode(' · '),
                'apertura_automatica' => true,
                'abierto_at' => now(),
                'abierto_por' => $usuarioId,
            ]);
            SeguimientoAcademicoEvento::create([
                'seguimiento_caso_id' => $caso->id,
                'riesgo_evaluacion_id' => $evaluacion->id,
                'tipo' => 'apertura',
                'titulo' => 'Seguimiento abierto automáticamente',
                'descripcion' => 'El alumno alcanzó riesgo '.$evaluacion->etiqueta_riesgo.' con '.$evaluacion->puntaje.' puntos.',
                'datos_nuevos' => ['nivel' => $evaluacion->nivel_riesgo, 'puntaje' => $evaluacion->puntaje, 'factores' => $evaluacion->factores],
                'registrado_por' => $usuarioId,
                'ocurrido_at' => now(),
            ]);
            $casoCreado = true;
        } elseif ($caso) {
            $antes = ['nivel' => $caso->riesgo_actual, 'puntaje' => $caso->puntaje_actual];
            $cambio = $caso->riesgo_actual !== $evaluacion->nivel_riesgo || (int) $caso->puntaje_actual !== (int) $evaluacion->puntaje;
            $caso->forceFill([
                'riesgo_evaluacion_id' => $evaluacion->id,
                'riesgo_actual' => $evaluacion->nivel_riesgo,
                'puntaje_actual' => $evaluacion->puntaje,
                'prioridad' => $evaluacion->nivel_riesgo === 'critico' ? 'critica' : ($evaluacion->nivel_riesgo === 'alto' ? 'alta' : $caso->prioridad),
                'resumen' => collect($evaluacion->factores ?? [])->pluck('detalle')->filter()->implode(' · '),
            ])->save();

            if ($cambio) {
                SeguimientoAcademicoEvento::create([
                    'seguimiento_caso_id' => $caso->id,
                    'riesgo_evaluacion_id' => $evaluacion->id,
                    'tipo' => ($orden[$evaluacion->nivel_riesgo] ?? 0) < ($orden[$antes['nivel']] ?? 0) ? 'mejora' : 'reevaluacion',
                    'titulo' => 'Semáforo académico actualizado',
                    'descripcion' => 'El riesgo cambió de '.ucfirst($antes['nivel']).' a '.$evaluacion->etiqueta_riesgo.'.',
                    'datos_anteriores' => $antes,
                    'datos_nuevos' => ['nivel' => $evaluacion->nivel_riesgo, 'puntaje' => $evaluacion->puntaje, 'factores' => $evaluacion->factores],
                    'registrado_por' => $usuarioId,
                    'ocurrido_at' => now(),
                ]);
            }
        }

        if ($caso && (! $anterior || $anterior->nivel_riesgo !== $evaluacion->nivel_riesgo || in_array($evaluacion->nivel_riesgo, ['alto', 'critico'], true))) {
            $mejora = $anterior && ($orden[$evaluacion->nivel_riesgo] ?? 0) < ($orden[$anterior->nivel_riesgo] ?? 0);
            $fingerprint = hash('sha256', 'riesgo:'.$evaluacion->id.':'.($mejora ? 'mejora' : 'cambio'));
            AlertaAcademica::updateOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'inscripcion_id' => $evaluacion->inscripcion_id,
                    'inscripcion_ciclo_id' => $evaluacion->inscripcion_ciclo_id,
                    'riesgo_evaluacion_id' => $evaluacion->id,
                    'seguimiento_caso_id' => $caso->id,
                    'destinatario_id' => $caso->responsable_id,
                    'tipo' => $mejora ? 'mejora_riesgo' : 'cambio_riesgo',
                    'severidad' => $mejora ? 'informativa' : ($evaluacion->nivel_riesgo === 'critico' ? 'critica' : 'advertencia'),
                    'titulo' => $mejora ? 'Mejora en el nivel de riesgo' : 'Semáforo académico actualizado',
                    'mensaje' => 'El alumno registra riesgo '.$evaluacion->etiqueta_riesgo.' con '.$evaluacion->puntaje.' puntos.',
                    'estado' => 'pendiente',
                    'generada_at' => now(),
                    'metadata' => ['factores' => $evaluacion->factores],
                ]
            );
            $alertas++;
        }

        return [$casoCreado, $alertas];
    }

    public function sincronizarVencimientos(): int
    {
        $generadas = 0;
        $acciones = SeguimientoAcademicoAccion::query()
            ->with('caso')
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<=', today())
            ->get();

        foreach ($acciones as $accion) {
            if (! $accion->caso) {
                continue;
            }
            $vencida = $accion->fecha_limite->lt(today());
            $fingerprint = hash('sha256', 'accion:'.$accion->id.':'.($vencida ? 'vencida' : 'vence_hoy'));
            $alerta = AlertaAcademica::updateOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'inscripcion_id' => $accion->caso->inscripcion_id,
                    'inscripcion_ciclo_id' => $accion->caso->inscripcion_ciclo_id,
                    'seguimiento_caso_id' => $accion->caso->id,
                    'destinatario_id' => $accion->responsable_id ?? $accion->caso->responsable_id,
                    'tipo' => $vencida ? 'accion_vencida' : 'accion_vence_hoy',
                    'severidad' => $vencida ? 'critica' : 'advertencia',
                    'titulo' => $vencida ? 'Acción de intervención vencida' : 'Acción de intervención vence hoy',
                    'mensaje' => Str::limit($accion->descripcion, 220),
                    'estado' => 'pendiente',
                    'fecha_limite' => $accion->fecha_limite,
                    'generada_at' => now(),
                    'metadata' => ['accion_id' => $accion->id],
                ]
            );
            $generadas += $alerta->wasRecentlyCreated ? 1 : 0;
        }

        $automatizacion = RiesgoAcademicoConfiguracion::query()->where('clave', 'automatizacion')->value('valor') ?? [];
        if (is_string($automatizacion)) {
            $automatizacion = json_decode($automatizacion, true) ?: [];
        }
        $diasAviso = max(0, (int) ($automatizacion['dias_alerta_revision'] ?? 3));

        $casosPorRevisar = SeguimientoAcademicoCaso::query()
            ->activos()
            ->whereNotNull('proxima_revision_at')
            ->whereDate('proxima_revision_at', '<=', today()->addDays($diasAviso))
            ->get();

        foreach ($casosPorRevisar as $caso) {
            $vencida = $caso->proxima_revision_at->lt(today());
            $venceHoy = $caso->proxima_revision_at->isSameDay(today());
            $fingerprint = hash('sha256', 'revision:'.$caso->id.':'.$caso->proxima_revision_at->toDateString());
            $alerta = AlertaAcademica::updateOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'inscripcion_id' => $caso->inscripcion_id,
                    'inscripcion_ciclo_id' => $caso->inscripcion_ciclo_id,
                    'riesgo_evaluacion_id' => $caso->riesgo_evaluacion_id,
                    'seguimiento_caso_id' => $caso->id,
                    'destinatario_id' => $caso->responsable_id,
                    'tipo' => $vencida ? 'revision_vencida' : ($venceHoy ? 'revision_vence_hoy' : 'revision_proxima'),
                    'severidad' => $vencida ? 'critica' : 'advertencia',
                    'titulo' => $vencida ? 'Revisión de seguimiento vencida' : ($venceHoy ? 'Revisión programada para hoy' : 'Revisión de seguimiento próxima'),
                    'mensaje' => 'El caso '.$caso->folio.' requiere revisión el '.$caso->proxima_revision_at->format('d/m/Y').'.',
                    'estado' => 'pendiente',
                    'fecha_limite' => $caso->proxima_revision_at,
                    'generada_at' => now(),
                    'metadata' => ['caso_id' => $caso->id, 'tipo' => 'revision'],
                ]
            );
            $generadas += $alerta->wasRecentlyCreated ? 1 : 0;
        }

        return $generadas;
    }

    private function ordenarRecursivo(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }
        if (array_is_list($valor)) {
            return array_map(fn ($item) => $this->ordenarRecursivo($item), $valor);
        }
        ksort($valor);
        foreach ($valor as $clave => $item) {
            $valor[$clave] = $this->ordenarRecursivo($item);
        }
        return $valor;
    }
}
