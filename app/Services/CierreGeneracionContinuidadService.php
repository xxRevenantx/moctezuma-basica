<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use App\Models\CambioAcademico;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\ProcesoCierreCiclo;
use App\Models\ProcesoCierreCicloDetalle;
use App\Models\ProyeccionContinuidad;
use App\Models\Semestre;
use App\Models\SimulacionCierreCiclo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CierreGeneracionContinuidadService
{
    public const RESULTADOS = [
        'pendiente',
        'continuidad_interna',
        'no_reinscrito',
        'egresado',
        'traslado',
        'baja_definitiva',
        'no_promovido',
    ];

    public const MODOS = [
        'promocion_grado',
        'cierre_nivel',
        'egreso_terminal',
    ];

    private const NIVELES_SIGUIENTES = [
        'preescolar' => 'primaria',
        'primaria' => 'secundaria',
        'secundaria' => 'bachillerato',
    ];

    private const ESTATUS_PROCESABLES = ['activo', 'reingreso', 'no_promovido'];

    private const SEMESTRES_BACHILLERATO = [1, 2, 3, 4, 5, 6];

    private const SEMESTRES_DESTINO_MISMO_CICLO = [2, 4, 6];

    private const SEMESTRES_DESTINO_CICLO_SIGUIENTE = [3, 5];

    private const SIMULACION_VERSION = 1;

    private const SIMULACION_VIGENCIA_MINUTOS = 30;

    public function __construct(
        private readonly GestionAcademicaService $gestionAcademica,
        private readonly HistorialCicloEscolarService $historialCiclos,
        private readonly AsignacionEscolarService $asignacionEscolar,
        private readonly TrayectoriaAcademicaService $trayectorias,
        private readonly MatriculaAlumnoService $matriculas,
    ) {}

    public function nivelDestinoSugerido(Nivel $origen): ?Nivel
    {
        $slug = self::NIVELES_SIGUIENTES[$origen->slug] ?? null;

        return $slug ? Nivel::query()->where('slug', $slug)->first() : null;
    }

    public function cicloDestinoSugerido(CicloEscolar $origen): ?CicloEscolar
    {
        return CicloEscolar::query()
            ->where('inicio_anio', (int) $origen->inicio_anio + 1)
            ->where('fin_anio', (int) $origen->fin_anio + 1)
            ->first();
    }

    /**
     * Devuelve el semestre inmediato siguiente de Bachillerato usando la
     * numeración oficial 1 a 6. No se apoya únicamente en paridad para evitar
     * aceptar semestres fuera del plan académico.
     */
    public function semestreSiguienteBachillerato(Nivel $nivel, ?int $semestreOrigenId): ?Semestre
    {
        if ($nivel->slug !== 'bachillerato' || ! $semestreOrigenId) {
            return null;
        }

        $origen = Semestre::query()
            ->whereKey($semestreOrigenId)
            ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivel->id))
            ->first();

        $numeroOrigen = (int) ($origen?->numero ?? 0);
        if (! in_array($numeroOrigen, self::SEMESTRES_BACHILLERATO, true) || $numeroOrigen >= 6) {
            return null;
        }

        return Semestre::query()
            ->where('numero', $numeroOrigen + 1)
            ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivel->id))
            ->orderBy('orden_global')
            ->orderBy('id')
            ->first();
    }

    /**
     * Centraliza la regla del ciclo destino. En Bachillerato los cambios
     * 1→2, 3→4 y 5→6 permanecen en el mismo ciclo; 2→3 y 4→5 requieren el
     * ciclo consecutivo. Para repetición de sexto semestre se conserva el
     * comportamiento de usar el ciclo consecutivo.
     *
     * @return array{
     *     tipo:string,
     *     ciclo:?CicloEscolar,
     *     ciclo_id:?int,
     *     etiqueta:string,
     *     semestre_origen:?int,
     *     semestre_destino:?int
     * }
     */
    public function reglaCicloDestino(
        Nivel $nivelOrigen,
        CicloEscolar $cicloOrigen,
        string $modo,
        ?int $semestreOrigenId = null,
        ?int $semestreDestinoId = null,
    ): array {
        $semestreOrigen = $semestreOrigenId
            ? Semestre::query()->find($semestreOrigenId)
            : null;
        $semestreDestino = $semestreDestinoId
            ? Semestre::query()->find($semestreDestinoId)
            : null;

        $numeroOrigen = filled($semestreOrigen?->numero) ? (int) $semestreOrigen->numero : null;
        $numeroDestino = filled($semestreDestino?->numero) ? (int) $semestreDestino->numero : null;

        if ($nivelOrigen->slug === 'bachillerato' && $modo === 'promocion_grado') {
            $semestreDestino ??= $this->semestreSiguienteBachillerato($nivelOrigen, $semestreOrigenId);
            $numeroDestino = filled($semestreDestino?->numero) ? (int) $semestreDestino->numero : null;

            if (in_array($numeroDestino, self::SEMESTRES_DESTINO_MISMO_CICLO, true)) {
                return [
                    'tipo' => 'mismo_ciclo',
                    'ciclo' => $cicloOrigen,
                    'ciclo_id' => (int) $cicloOrigen->id,
                    'etiqueta' => $cicloOrigen->nombre,
                    'semestre_origen' => $numeroOrigen,
                    'semestre_destino' => $numeroDestino,
                ];
            }

            if (! in_array($numeroDestino, self::SEMESTRES_DESTINO_CICLO_SIGUIENTE, true)) {
                return [
                    'tipo' => 'invalido',
                    'ciclo' => null,
                    'ciclo_id' => null,
                    'etiqueta' => 'destino no válido',
                    'semestre_origen' => $numeroOrigen,
                    'semestre_destino' => $numeroDestino,
                ];
            }
        }

        $cicloDestino = $this->cicloDestinoSugerido($cicloOrigen);

        return [
            'tipo' => 'ciclo_consecutivo',
            'ciclo' => $cicloDestino,
            'ciclo_id' => $cicloDestino?->id,
            'etiqueta' => ((int) $cicloOrigen->inicio_anio + 1).'-'.((int) $cicloOrigen->fin_anio + 1),
            'semestre_origen' => $numeroOrigen,
            'semestre_destino' => $numeroDestino,
        ];
    }

    public function ciclosDestinoPermitidos(
        Nivel $nivelOrigen,
        CicloEscolar $cicloOrigen,
        string $modo,
        ?int $semestreOrigenId = null,
        ?int $semestreDestinoId = null,
    ): Collection {
        $regla = $this->reglaCicloDestino(
            $nivelOrigen,
            $cicloOrigen,
            $modo,
            $semestreOrigenId,
            $semestreDestinoId,
        );

        return $regla['ciclo'] ? collect([$regla['ciclo']]) : collect();
    }

    public function destinoSugerido(
        Nivel $nivelOrigen,
        CicloEscolar $cicloOrigen,
        ?int $gradoOrigenId = null,
        ?int $semestreOrigenId = null,
        ?int $generacionOrigenId = null
    ): array {
        $modo = $this->modoDesdeUbicacion($nivelOrigen, $gradoOrigenId, $semestreOrigenId);

        if ($modo === 'egreso_terminal') {
            return [
                'modo' => $modo,
                'tipo_proyeccion' => null,
                'ciclo_destino_id' => null,
                'nivel_destino_id' => null,
                'grado_destino_id' => null,
                'semestre_destino_id' => null,
                'generacion_destino_id' => null,
                'generacion_esperada' => null,
            ];
        }

        if ($modo === 'promocion_grado') {
            $nivelDestino = $nivelOrigen;
            $generacion = $generacionOrigenId
                ? Generacion::query()->whereKey($generacionOrigenId)->where('nivel_id', $nivelOrigen->id)->first()
                : null;

            if ($nivelOrigen->slug === 'bachillerato') {
                $semestre = $this->semestreSiguienteBachillerato($nivelOrigen, $semestreOrigenId);
                $grado = $semestre?->grado;
            } else {
                $gradoOrigen = $gradoOrigenId ? Grado::query()->find($gradoOrigenId) : null;
                $grado = Grado::query()
                    ->where('nivel_id', $nivelOrigen->id)
                    ->when($gradoOrigen, fn ($query) => $query->where('orden', '>', $gradoOrigen->orden))
                    ->orderBy('orden')
                    ->orderBy('id')
                    ->first();
                $semestre = null;
            }

            $reglaCiclo = $this->reglaCicloDestino(
                $nivelOrigen,
                $cicloOrigen,
                $modo,
                $semestreOrigenId,
                $semestre?->id,
            );

            return [
                'modo' => $modo,
                'tipo_proyeccion' => 'siguiente_grado',
                'ciclo_destino_id' => $reglaCiclo['ciclo_id'],
                'nivel_destino_id' => $nivelDestino->id,
                'grado_destino_id' => $grado?->id,
                'semestre_destino_id' => $semestre?->id,
                'generacion_destino_id' => $generacion?->id,
                'generacion_esperada' => $generacion?->etiqueta,
            ];
        }

        $cicloDestino = $this->cicloDestinoSugerido($cicloOrigen);
        if (! $cicloDestino) {
            return [
                'modo' => $modo,
                'tipo_proyeccion' => 'siguiente_nivel',
                'ciclo_destino_id' => null,
                'nivel_destino_id' => null,
                'grado_destino_id' => null,
                'semestre_destino_id' => null,
                'generacion_destino_id' => null,
                'generacion_esperada' => null,
            ];
        }

        $nivelDestino = $this->nivelDestinoSugerido($nivelOrigen);
        if (! $nivelDestino) {
            return [
                'modo' => 'egreso_terminal',
                'tipo_proyeccion' => null,
                'ciclo_destino_id' => $cicloDestino->id,
                'nivel_destino_id' => null,
                'grado_destino_id' => null,
                'semestre_destino_id' => null,
                'generacion_destino_id' => null,
                'generacion_esperada' => null,
            ];
        }

        $grado = Grado::query()
            ->where('nivel_id', $nivelDestino->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->first();
        $semestre = $nivelDestino->slug === 'bachillerato' && $grado
            ? Semestre::query()->where('grado_id', $grado->id)->orderBy('orden_global')->orderBy('numero')->first()
            : null;
        $generacion = $grado
            ? $this->asignacionEscolar->resolverGeneracion($cicloDestino, $nivelDestino, $grado, $semestre)
            : null;

        return [
            'modo' => 'cierre_nivel',
            'tipo_proyeccion' => 'siguiente_nivel',
            'ciclo_destino_id' => $cicloDestino->id,
            'nivel_destino_id' => $nivelDestino->id,
            'grado_destino_id' => $grado?->id,
            'semestre_destino_id' => $semestre?->id,
            'generacion_destino_id' => $generacion?->id,
            'generacion_esperada' => $grado
                ? $this->asignacionEscolar->etiquetaGeneracionEsperada($cicloDestino, $nivelDestino, $grado, $semestre)
                : null,
        ];
    }

    public function modoDesdeUbicacion(Nivel $nivel, ?int $gradoId, ?int $semestreId): string
    {
        if ($nivel->slug === 'bachillerato') {
            $semestre = $semestreId
                ? Semestre::query()
                    ->whereKey($semestreId)
                    ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivel->id))
                    ->first()
                : null;

            return $semestre && (int) $semestre->numero === 6
                ? 'egreso_terminal'
                : 'promocion_grado';
        }

        $ultimoGradoId = Grado::query()
            ->where('nivel_id', $nivel->id)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');

        if ($gradoId && (int) $gradoId === (int) $ultimoGradoId) {
            return $this->nivelDestinoSugerido($nivel) ? 'cierre_nivel' : 'egreso_terminal';
        }

        return 'promocion_grado';
    }

    public function resultadosPermitidos(string $modo): array
    {
        return match ($modo) {
            'promocion_grado' => ['continuidad_interna', 'no_reinscrito', 'traslado', 'baja_definitiva', 'no_promovido'],
            'cierre_nivel' => ['continuidad_interna', 'egresado', 'traslado', 'baja_definitiva', 'no_promovido'],
            'egreso_terminal' => ['egresado', 'traslado', 'baja_definitiva', 'no_promovido'],
            default => [],
        };
    }

    public function candidatos(
        int $nivelId,
        int $cicloEscolarId,
        int $generacionId,
        ?int $grupoId = null
    ): Collection {
        $nivel = Nivel::query()->findOrFail($nivelId);
        $ultimoGradoId = Grado::query()
            ->where('nivel_id', $nivelId)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');
        $ultimoSemestreId = $nivel->slug === 'bachillerato'
            ? Semestre::query()
                ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivelId))
                ->orderByDesc('orden_global')
                ->orderByDesc('numero')
                ->value('id')
            : null;

        $registros = InscripcionCiclo::query()
            ->with([
                'cicloEscolar:id,inicio_anio,fin_anio',
                'inscripcion' => fn ($relacion) => $relacion->withTrashed()->with([
                    'nivel:id,nombre,slug',
                    'grado:id,nombre,orden',
                    'generacion:id,nombre,anio_ingreso,anio_egreso,status',
                    'grupo.asignacionGrupo:id,nombre',
                    'semestre:id,numero,orden_global',
                ]),
                'grado:id,nombre,orden,nivel_id',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero,orden_global,grado_id',
                'asignacionActual.grado:id,nombre,orden,nivel_id',
                'asignacionActual.semestre:id,numero,orden_global,grado_id',
                'asignacionActual.grupo.asignacionGrupo:id,nombre',
            ])
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('generacion_id', $generacionId)
            ->when($grupoId, function ($query) use ($grupoId): void {
                $query->where(function ($sub) use ($grupoId): void {
                    $sub->where('grupo_id', $grupoId)
                        ->orWhereHas('asignaciones', fn ($asignaciones) => $asignaciones
                            ->where('grupo_id', $grupoId)
                            ->where('es_actual', true));
                });
            })
            ->orderBy('grupo_id')
            ->orderBy('id')
            ->get();

        $idsRegistros = $registros->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $idsInscripciones = $registros->pluck('inscripcion_id')->map(fn ($id): int => (int) $id)->unique()->values();

        $proyeccionesVigentes = $idsRegistros->isEmpty()
            ? collect()
            : ProyeccionContinuidad::query()
                ->whereIn('inscripcion_ciclo_origen_id', $idsRegistros)
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->orderByDesc('id')
                ->get()
                ->unique('inscripcion_ciclo_origen_id')
                ->keyBy('inscripcion_ciclo_origen_id');

        $otrosCiclos = $idsInscripciones->isEmpty()
            ? collect()
            : InscripcionCiclo::query()
                ->with('cicloEscolar:id,inicio_anio,fin_anio')
                ->whereIn('inscripcion_id', $idsInscripciones)
                ->whereNotIn('id', $idsRegistros)
                ->get()
                ->groupBy('inscripcion_id');

        return $registros
            ->filter(fn (InscripcionCiclo $registro): bool => (bool) $registro->inscripcion)
            ->map(function (InscripcionCiclo $registro) use (
                $nivel,
                $ultimoGradoId,
                $ultimoSemestreId,
                $proyeccionesVigentes,
                $otrosCiclos
            ): array {
                $alumno = $registro->inscripcion;
                $asignacion = $registro->asignacionActual;
                $gradoId = (int) ($asignacion?->grado_id ?: $registro->grado_id);
                $semestreId = (int) ($asignacion?->semestre_id ?: $registro->semestre_id);
                $grupo = $asignacion?->grupo ?: $registro->grupo;
                $esGradoFinal = $nivel->slug === 'bachillerato'
                    ? $semestreId > 0 && $semestreId === (int) $ultimoSemestreId
                    : $gradoId > 0 && $gradoId === (int) $ultimoGradoId;
                $estatusActual = mb_strtolower((string) ($alumno->estatus ?: 'inactivo'));
                $resultadoExistente = $registro->resultado_final ?: null;

                $proyeccionVigente = $proyeccionesVigentes->get($registro->id);
                $tieneProyeccionPendiente = ($proyeccionVigente?->estado ?? null) === 'pendiente';
                $procesableActivo = $registro->estado === 'en_curso'
                    && in_array($estatusActual, self::ESTATUS_PROCESABLES, true)
                    && (int) $alumno->ciclo_escolar_id === (int) $registro->ciclo_escolar_id
                    && ! $tieneProyeccionPendiente;

                $inicioOrigen = (int) ($registro->cicloEscolar?->inicio_anio ?? 0);
                $tieneCicloPosterior = collect($otrosCiclos->get($alumno->id, collect()))
                    ->contains(fn (InscripcionCiclo $otro): bool =>
                        (int) ($otro->cicloEscolar?->inicio_anio ?? 0) > $inicioOrigen
                    );

                // Compatibilidad con egresos realizados antes de existir el módulo
                // de proyecciones. El resultado histórico no se reabre ni se altera;
                // únicamente se permite crear la proyección provisional faltante.
                $resultadoHistoricoProyectable = in_array($resultadoExistente, ['egresado', 'promovido_grado', 'promovido'], true);
                $estatusHistoricoCompatible = in_array($estatusActual, ['egresado', 'pendiente_reinscripcion', 'no_reinscrito', 'inactivo'], true);
                $soloProyeccionHistorica = $registro->estado === 'cerrado'
                    && $resultadoHistoricoProyectable
                    && $estatusHistoricoCompatible
                    && ! $proyeccionVigente
                    && ! $tieneCicloPosterior;

                $procesable = $procesableActivo || $soloProyeccionHistorica;
                $advertencias = $this->advertenciasAlumno(
                    $registro,
                    $esGradoFinal,
                    $procesable,
                    $soloProyeccionHistorica,
                    $proyeccionVigente?->estado,
                    $tieneCicloPosterior,
                );

                return [
                    'id' => (int) $alumno->id,
                    'inscripcion_ciclo_id' => (int) $registro->id,
                    'nombre' => trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"),
                    'matricula' => (string) ($registro->matricula ?: $alumno->matricula),
                    'curp' => (string) $alumno->curp,
                    'grado_id' => $gradoId,
                    'grado' => $asignacion?->grado?->nombre ?: $registro->grado?->nombre ?: $alumno->grado?->nombre ?: 'Sin grado',
                    'semestre_id' => $semestreId ?: null,
                    'semestre' => $asignacion?->semestre?->numero ?: $registro->semestre?->numero ?: $alumno->semestre?->numero,
                    'grupo_id' => (int) ($grupo?->id ?: $registro->grupo_id),
                    'grupo' => $grupo?->asignacionGrupo?->nombre ?: 'Sin grupo',
                    'estatus' => $estatusActual,
                    'estado_ciclo' => (string) $registro->estado,
                    'resultado_existente' => $resultadoExistente,
                    'es_grado_final' => $esGradoFinal,
                    'procesable' => $procesable,
                    'procesable_activo' => $procesableActivo,
                    'solo_proyeccion_historica' => $soloProyeccionHistorica,
                    'proyeccion_existente_estado' => $proyeccionVigente?->estado,
                    'tiene_ciclo_posterior' => $tieneCicloPosterior,
                    'advertencias' => $advertencias,
                    'advertencia_texto' => implode(' · ', $advertencias),
                ];
            })
            ->sortBy(fn (array $fila): string => mb_strtolower($fila['nombre']))
            ->values();
    }

    public function gruposContinuidad(array $destino): Collection
    {
        if (! $this->destinoCompleto($destino)) {
            return collect();
        }

        return $this->asignacionEscolar->gruposCompatibles(
            (int) $destino['ciclo_destino_id'],
            (int) $destino['nivel_destino_id'],
            (int) $destino['generacion_destino_id'],
            (int) $destino['grado_destino_id'],
            filled($destino['semestre_destino_id'] ?? null) ? (int) $destino['semestre_destino_id'] : null,
        );
    }

    public function gruposRepeticion(
        int $cicloDestinoId,
        int $nivelId,
        int $generacionOrigenId,
        int $gradoId,
        ?int $semestreId
    ): Collection {
        $ciclo = CicloEscolar::query()->find($cicloDestinoId);
        $nivel = Nivel::query()->find($nivelId);
        $grado = Grado::query()->find($gradoId);
        $semestre = $semestreId ? Semestre::query()->find($semestreId) : null;

        if (! $ciclo || ! $nivel || ! $grado) {
            return collect();
        }

        // Al repetir, el alumno se integra a la cohorte que cursará ese mismo grado
        // en el ciclo destino; no se conserva forzosamente la generación de origen.
        $generacion = $this->asignacionEscolar->resolverGeneracion($ciclo, $nivel, $grado, $semestre);
        if (! $generacion) {
            return collect();
        }

        return $this->asignacionEscolar->gruposCompatibles(
            $cicloDestinoId,
            $nivelId,
            $generacion->id,
            $gradoId,
            $semestreId,
        );
    }

    /**
     * Genera una simulación firmada del cierre sin modificar datos.
     *
     * La firma incluye la configuración, las decisiones y el estado actual de
     * cada alumno/historial. Si algo cambia antes de confirmar, la ejecución se
     * bloquea y obliga a revisar nuevamente la simulación.
     */
    public function simular(array $configuracion, array $decisiones, int $usuarioId): array
    {
        $this->asegurarEsquemaCierreDisponible();

        $generacion = Generacion::query()->findOrFail((int) $configuracion['generacion_id']);
        $nivel = Nivel::query()->findOrFail((int) $configuracion['nivel_id']);
        $cicloOrigen = CicloEscolar::query()->findOrFail((int) $configuracion['ciclo_origen_id']);
        $candidatos = $this->candidatos(
            $nivel->id,
            $cicloOrigen->id,
            $generacion->id,
            filled($configuracion['grupo_origen_id'] ?? null) ? (int) $configuracion['grupo_origen_id'] : null,
        )->keyBy('id');

        if ($candidatos->isEmpty()) {
            throw ValidationException::withMessages([
                'generacion_id' => 'No hay alumnos históricos en el contexto seleccionado.',
            ]);
        }

        $this->validarConfiguracion($configuracion, $decisiones, $candidatos);
        $this->prevalidarDecisionesSimulacion($configuracion, $decisiones, $candidatos);

        $contenido = $this->construirContenidoSimulacion(
            $configuracion,
            $decisiones,
            $candidatos,
            $generacion,
            $usuarioId,
        );
        $generadoAt = CarbonImmutable::now();
        $expiraAt = $generadoAt->addMinutes(self::SIMULACION_VIGENCIA_MINUTOS);

        $generadoAtIso = $generadoAt->toIso8601String();
        $expiraAtIso = $expiraAt->toIso8601String();
        $hash = $this->firmarContenido([
            'version' => self::SIMULACION_VERSION,
            'generado_at' => $generadoAtIso,
            'expira_at' => $expiraAtIso,
            'contenido' => $contenido,
        ]);

        $resumen = [
            'total' => (int) ($contenido['totales']['procesables'] ?? 0),
            'total_evaluados' => (int) ($contenido['totales']['evaluados'] ?? 0),
            'sin_cambio' => (int) ($contenido['totales']['sin_cambio'] ?? 0),
            'conteos' => $contenido['conteos'] ?? [],
            'advertencias' => $contenido['advertencias'] ?? [],
            'advertencias_total' => count($contenido['advertencias'] ?? []),
            'respaldo_items' => count($contenido['alumnos'] ?? []),
        ];

        $registro = DB::transaction(function () use (
            $usuarioId,
            $nivel,
            $cicloOrigen,
            $generacion,
            $configuracion,
            $contenido,
            $hash,
            $resumen,
            $generadoAt,
            $expiraAt
        ): SimulacionCierreCiclo {
            SimulacionCierreCiclo::query()
                ->where('usuario_id', $usuarioId)
                ->where('nivel_id', $nivel->id)
                ->where('ciclo_origen_id', $cicloOrigen->id)
                ->where('generacion_id', $generacion->id)
                ->where('estado', 'vigente')
                ->update([
                    'estado' => 'cancelada',
                    'cancelada_at' => now(),
                    'motivo_cancelacion' => 'Sustituida por una simulación más reciente.',
                ]);

            return SimulacionCierreCiclo::query()->create([
                'uuid' => (string) Str::uuid(),
                'usuario_id' => $usuarioId,
                'nivel_id' => $nivel->id,
                'ciclo_origen_id' => $cicloOrigen->id,
                'ciclo_destino_id' => filled($configuracion['ciclo_destino_id'] ?? null)
                    ? (int) $configuracion['ciclo_destino_id']
                    : null,
                'generacion_id' => $generacion->id,
                'grupo_origen_id' => filled($configuracion['grupo_origen_id'] ?? null)
                    ? (int) $configuracion['grupo_origen_id']
                    : null,
                'estado' => 'vigente',
                'contenido' => $contenido,
                'hash' => $hash,
                'resumen' => $resumen,
                'generado_at' => $generadoAt,
                'expira_at' => $expiraAt,
            ]);
        });

        return [
            'id' => $registro->id,
            'uuid' => $registro->uuid,
            'version' => self::SIMULACION_VERSION,
            'hash' => $registro->hash,
            'generado_at' => $registro->generado_at?->toIso8601String(),
            'expira_at' => $registro->expira_at?->toIso8601String(),
            'resumen' => $registro->resumen ?? [],
        ];
    }

    public function ejecutar(array $configuracion, array $decisiones, int $usuarioId, array $simulacion): ProcesoCierreCiclo
    {
        $this->asegurarEsquemaCierreDisponible();

        return DB::transaction(function () use ($configuracion, $decisiones, $usuarioId, $simulacion): ProcesoCierreCiclo {
            $generacion = Generacion::query()->lockForUpdate()->findOrFail((int) $configuracion['generacion_id']);
            $nivel = Nivel::query()->findOrFail((int) $configuracion['nivel_id']);
            $cicloOrigen = CicloEscolar::query()->findOrFail((int) $configuracion['ciclo_origen_id']);
            $modo = (string) ($configuracion['modo_proceso'] ?? '');
            $candidatos = $this->candidatos(
                $nivel->id,
                $cicloOrigen->id,
                $generacion->id,
                filled($configuracion['grupo_origen_id'] ?? null) ? (int) $configuracion['grupo_origen_id'] : null,
            )->keyBy('id');

            if ($candidatos->isEmpty()) {
                throw ValidationException::withMessages([
                    'generacion_id' => 'No hay alumnos históricos en el contexto seleccionado.',
                ]);
            }

            $this->validarConfiguracion($configuracion, $decisiones, $candidatos);
            $this->prevalidarDecisionesSimulacion($configuracion, $decisiones, $candidatos);
            $this->bloquearContextoSimulacion($candidatos);
            $simulacionVerificada = $this->verificarSimulacion(
                $simulacion,
                $configuracion,
                $decisiones,
                $candidatos,
                $generacion,
                $usuarioId,
            );

            $estadoGeneracionAnterior = $this->snapshotGeneracion($generacion);
            $respaldoLogico = $this->construirRespaldoLogico(
                $configuracion,
                $decisiones,
                $candidatos,
                $generacion,
                $usuarioId,
            );
            $respaldoHash = $this->firmarContenido($respaldoLogico);
            $generacion->update([
                'estado_cierre' => 'en_proceso',
                'cierre_iniciado_at' => now(),
                'cierre_iniciado_por' => $usuarioId,
            ]);

            $proceso = ProcesoCierreCiclo::query()->create([
                'simulacion_cierre_ciclo_id' => $simulacionVerificada['id'],
                'nivel_id' => $nivel->id,
                'ciclo_escolar_id' => $cicloOrigen->id,
                'ciclo_destino_id' => filled($configuracion['ciclo_destino_id'] ?? null)
                    ? (int) $configuracion['ciclo_destino_id']
                    : null,
                'generacion_id' => $generacion->id,
                'grupo_origen_id' => filled($configuracion['grupo_origen_id'] ?? null)
                    ? (int) $configuracion['grupo_origen_id']
                    : null,
                'tipo' => 'cierre_nivel_continuidad',
                'alcance' => filled($configuracion['grupo_origen_id'] ?? null) ? 'grupo' : 'generacion',
                'estado' => 'procesando',
                'fecha_egreso' => $configuracion['fecha_efectiva'],
                'fecha_efectiva' => $configuracion['fecha_efectiva'],
                'motivo' => trim((string) $configuracion['motivo']),
                'total_evaluados' => $candidatos->count(),
                'generacion_cerrada' => false,
                'ciclo_cerrado' => false,
                'vista_previa_hash' => (string) $simulacionVerificada['hash'],
                'simulacion' => $simulacionVerificada,
                'simulado_at' => $simulacionVerificada['generado_at'],
                'simulacion_expira_at' => $simulacionVerificada['expira_at'],
                'estado_anterior_generacion' => $estadoGeneracionAnterior,
                'respaldo_logico' => $respaldoLogico,
                'respaldo_hash' => $respaldoHash,
                'respaldo_verificado_at' => now(),
                'integridad_estado' => 'verificado',
                'resumen' => [
                    'configuracion' => $configuracion,
                    'modo_proceso' => $modo,
                    'conteos' => collect($decisiones)->countBy('resultado')->all(),
                ],
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
                'confirmacion_at' => now(),
            ]);

            $procesados = 0;
            $sinCambio = 0;

            foreach ($candidatos as $id => $fila) {
                $decision = $decisiones[$id] ?? $decisiones[(string) $id] ?? ['resultado' => 'pendiente'];
                $resultado = (string) ($decision['resultado'] ?? 'pendiente');
                $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($id);
                $origen = InscripcionCiclo::query()->lockForUpdate()->findOrFail((int) $fila['inscripcion_ciclo_id']);
                $antes = $this->snapshotAlumno($alumno);
                $respaldoDetalle = $this->construirRespaldoDetalle($alumno, $origen);
                $respaldoDetalleHash = $this->firmarContenido($respaldoDetalle);

                if (! $fila['procesable']) {
                    ProcesoCierreCicloDetalle::query()->create([
                        'proceso_cierre_ciclo_id' => $proceso->id,
                        'inscripcion_id' => $alumno->id,
                        'inscripcion_ciclo_origen_id' => $origen->id,
                        'resultado' => 'sin_cambio',
                        'resultado_propuesto' => $fila['resultado_existente'] ?: $resultado,
                        'destino_propuesto' => $decision,
                        'observacion' => 'El alumno ya tenía un resultado histórico o no pertenece actualmente al ciclo origen. No se modificó.',
                        'estado_anterior' => $antes,
                        'respaldo_origen' => $respaldoDetalle,
                        'respaldo_hash' => $respaldoDetalleHash,
                        'respaldo_verificado_at' => now(),
                        'estado_nuevo' => $antes,
                    ]);
                    $sinCambio++;
                    continue;
                }

                if (($fila['solo_proyeccion_historica'] ?? false) && $resultado !== 'continuidad_interna') {
                    throw ValidationException::withMessages([
                        "decisiones.{$id}.resultado" => "{$fila['nombre']} ya tiene un resultado histórico. Solo puedes crear la proyección provisional faltante.",
                    ]);
                }

                $motivoIndividual = $this->motivoIndividual($configuracion, $decision, $fila);
                $destinoCiclo = null;
                $datosProyeccion = null;
                $tipoProyeccion = null;
                $resultadoOrigen = null;

                if ($resultado === 'continuidad_interna') {
                    $destino = $this->destinoContinuidad($configuracion, $decision, $alumno);
                    $this->validarDestinoProyeccion($destino);
                    $this->asegurarDestinoDisponible(
                        $alumno,
                        (int) $destino['ciclo_escolar_id'],
                        $fila['nombre'],
                        (int) $origen->id,
                    );
                    $mismoCiclo = (int) $destino['ciclo_escolar_id'] === (int) $origen->ciclo_escolar_id;

                    $tipoProyeccion = $modo === 'promocion_grado' ? 'siguiente_grado' : 'siguiente_nivel';
                    $resultadoOrigen = $modo === 'promocion_grado' ? 'promovido_grado' : 'egresado';

                    if ($fila['solo_proyeccion_historica'] ?? false) {
                        $actualizado = $alumno;
                        $resultadoOrigen = (string) ($fila['resultado_existente'] ?: $resultadoOrigen);
                    } elseif ($modo === 'promocion_grado' && $mismoCiclo) {
                        // En los cambios 1→2, 3→4 y 5→6 no se cierra el
                        // historial anual ni se desactiva al alumno al crear
                        // la proyección. La asignación semestral se cambia
                        // únicamente cuando Control Escolar confirma el regreso.
                        $actualizado = $alumno;
                    } elseif ($modo === 'promocion_grado') {
                        $actualizado = $this->cerrarOrigenSinActivarDestino(
                            $alumno,
                            $origen,
                            'promovido_grado',
                            'pendiente_reinscripcion',
                            $motivoIndividual,
                            $usuarioId,
                            $configuracion['fecha_efectiva'],
                            true,
                        );
                    } else {
                        $actualizado = $this->gestionAcademica->cambiarEstatus(
                            $alumno,
                            'egresado',
                            $motivoIndividual,
                            $usuarioId,
                            $configuracion['fecha_efectiva'],
                        );
                    }
                    $datosProyeccion = $destino;
                } elseif ($resultado === 'no_promovido') {
                    $destino = $this->destinoRepeticion($configuracion, $decision, $fila, $alumno);
                    $this->validarDestinoProyeccion($destino);
                    $this->asegurarDestinoDisponible(
                        $alumno,
                        (int) $destino['ciclo_escolar_id'],
                        $fila['nombre'],
                        (int) $origen->id,
                    );
                    $tipoProyeccion = 'repeticion';
                    $resultadoOrigen = 'no_promovido';
                    $mismoCiclo = (int) $destino['ciclo_escolar_id'] === (int) $origen->ciclo_escolar_id;
                    $actualizado = $mismoCiclo
                        ? $alumno
                        : $this->cerrarOrigenSinActivarDestino(
                            $alumno,
                            $origen,
                            'no_promovido',
                            'pendiente_reinscripcion',
                            $motivoIndividual,
                            $usuarioId,
                            $configuracion['fecha_efectiva'],
                            false,
                        );
                    $datosProyeccion = $destino;
                } elseif ($resultado === 'no_reinscrito') {
                    if ($modo !== 'promocion_grado') {
                        throw ValidationException::withMessages([
                            "decisiones.{$id}.resultado" => 'La opción no reinscrito solo corresponde a grados o semestres intermedios.',
                        ]);
                    }
                    $resultadoOrigen = 'promovido_grado';
                    $actualizado = $this->cerrarOrigenSinActivarDestino(
                        $alumno,
                        $origen,
                        'promovido_grado',
                        'no_reinscrito',
                        $motivoIndividual,
                        $usuarioId,
                        $configuracion['fecha_efectiva'],
                        true,
                    );
                } elseif ($resultado === 'egresado') {
                    if (! $fila['es_grado_final']) {
                        throw ValidationException::withMessages([
                            "decisiones.{$id}.resultado" => "{$fila['nombre']} no está en el último grado o semestre; utiliza No continuará, Traslado o Baja.",
                        ]);
                    }
                    $actualizado = $this->gestionAcademica->cambiarEstatus(
                        $alumno,
                        'egresado',
                        $motivoIndividual,
                        $usuarioId,
                        $configuracion['fecha_efectiva'],
                    );
                } elseif ($resultado === 'traslado') {
                    $actualizado = $this->gestionAcademica->cambiarEstatus(
                        $alumno,
                        'trasladado',
                        $motivoIndividual,
                        $usuarioId,
                        $configuracion['fecha_efectiva'],
                    );
                    $actualizado->forceFill([
                        'observaciones_baja' => filled($decision['escuela_destino'] ?? null)
                            ? 'Escuela destino: '.trim((string) $decision['escuela_destino'])
                            : $actualizado->observaciones_baja,
                    ])->save();
                } elseif ($resultado === 'baja_definitiva') {
                    $actualizado = $this->gestionAcademica->cambiarEstatus(
                        $alumno,
                        'baja_definitiva',
                        $motivoIndividual,
                        $usuarioId,
                        $configuracion['fecha_efectiva'],
                    );
                } else {
                    throw ValidationException::withMessages([
                        "decisiones.{$id}.resultado" => "Selecciona un resultado definitivo para {$fila['nombre']}.",
                    ]);
                }

                $despues = $this->snapshotAlumno($actualizado->fresh());
                $detalle = ProcesoCierreCicloDetalle::query()->create([
                    'proceso_cierre_ciclo_id' => $proceso->id,
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_origen_id' => $origen->id,
                    'inscripcion_ciclo_destino_id' => $destinoCiclo?->id,
                    'resultado' => $resultado,
                    'resultado_propuesto' => $resultado,
                    'destino_propuesto' => array_merge($decision, [
                        'modo_proceso' => $modo,
                        'tipo_proyeccion' => $tipoProyeccion,
                        'resultado_origen' => $resultadoOrigen,
                        'ciclo_destino_id' => $datosProyeccion['ciclo_escolar_id'] ?? null,
                        'nivel_destino_id' => $datosProyeccion['nivel_id'] ?? null,
                        'grado_destino_id' => $datosProyeccion['grado_id'] ?? null,
                        'semestre_destino_id' => $datosProyeccion['semestre_id'] ?? null,
                        'generacion_destino_id' => $datosProyeccion['generacion_id'] ?? null,
                        'grupo_destino_id' => $datosProyeccion['grupo_id'] ?? ($decision['grupo_destino_id'] ?? null),
                        'fecha_efectiva' => $configuracion['fecha_efectiva'],
                        'motivo_aplicado' => $motivoIndividual,
                    ]),
                    'observacion' => ($fila['solo_proyeccion_historica'] ?? false)
                        ? 'Se creó una proyección provisional faltante sin modificar el resultado histórico del ciclo de origen.'
                        : $this->textoResultado($resultado, $modo),
                    'estado_anterior' => $antes,
                    'respaldo_origen' => $respaldoDetalle,
                    'respaldo_hash' => $respaldoDetalleHash,
                    'respaldo_verificado_at' => now(),
                    'estado_nuevo' => $despues,
                ]);

                if (in_array($resultado, ['continuidad_interna', 'no_promovido'], true) && $datosProyeccion) {
                    $this->crearProyeccionContinuidad(
                        $proceso,
                        $detalle,
                        $origen,
                        $actualizado->fresh(),
                        $datosProyeccion,
                        $motivoIndividual,
                        $configuracion['fecha_efectiva'],
                        $usuarioId,
                        $tipoProyeccion,
                        $resultadoOrigen,
                    );
                }

                $procesados++;
            }

            $pendientesGeneracion = InscripcionCiclo::query()
                ->where('ciclo_escolar_id', $cicloOrigen->id)
                ->where('nivel_id', $nivel->id)
                ->where('generacion_id', $generacion->id)
                ->where('estado', 'en_curso')
                ->count();

            $cerrarGeneracion = $modo !== 'promocion_grado' && (bool) ($configuracion['cerrar_generacion'] ?? false);
            if ($cerrarGeneracion && $pendientesGeneracion > 0) {
                throw ValidationException::withMessages([
                    'cerrar_generacion' => "Aún existen {$pendientesGeneracion} alumno(s) sin resultado definitivo en la generación. Procesa los demás grupos antes de cerrarla.",
                ]);
            }

            if ($cerrarGeneracion) {
                $generacion->update([
                    'status' => false,
                    'estado_cierre' => 'egresada',
                    'cerrada_at' => now(),
                    'cerrada_por' => $usuarioId,
                    'motivo_desactivacion' => trim((string) $configuracion['motivo']),
                    'observaciones' => trim((string) $configuracion['motivo']),
                    'cierre_iniciado_at' => null,
                    'cierre_iniciado_por' => null,
                ]);
            } else {
                $generacion->update([
                    'status' => $modo === 'promocion_grado' ? true : $generacion->status,
                    'estado_cierre' => $modo === 'promocion_grado'
                        ? 'activa'
                        : ($pendientesGeneracion > 0 ? 'activa' : 'cerrada'),
                    'cierre_iniciado_at' => null,
                    'cierre_iniciado_por' => null,
                ]);
            }

            CambioAcademico::query()->create([
                'generacion_id' => $generacion->id,
                'tipo' => 'cierre_grado_nivel_continuidad',
                'motivo' => trim((string) $configuracion['motivo']),
                'datos_anteriores' => $estadoGeneracionAnterior,
                'datos_nuevos' => array_merge($this->snapshotGeneracion($generacion->fresh()), [
                    'modo_proceso' => $modo,
                    'proceso_cierre_id' => $proceso->id,
                    'total_procesados' => $procesados,
                    'total_sin_cambio' => $sinCambio,
                ]),
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            $proceso->update([
                'estado' => 'completado',
                'total_procesados' => $procesados,
                'total_excluidos' => $sinCambio,
                'generacion_cerrada' => $cerrarGeneracion,
                'respaldo_verificado_at' => now(),
                'integridad_estado' => 'verificado',
                'resumen' => array_merge($proceso->resumen ?? [], [
                    'modo_proceso' => $modo,
                    'procesados' => $procesados,
                    'sin_cambio' => $sinCambio,
                    'pendientes_generacion' => $pendientesGeneracion,
                    'generacion_despues' => $this->snapshotGeneracion($generacion->fresh()),
                ]),
            ]);

            SimulacionCierreCiclo::query()
                ->whereKey((int) $simulacionVerificada['id'])
                ->update([
                    'estado' => 'consumida',
                    'consumida_at' => now(),
                ]);

            return $proceso->fresh(['detalles.inscripcion', 'generacion', 'cicloEscolar', 'cicloDestino']);
        });
    }

    public function proyeccionesPorNivelOrigen(
        int $nivelOrigenId,
        ?int $cicloDestinoId = null,
        ?string $estado = null,
        string $buscar = ''
    ): Collection {
        $buscar = trim($buscar);

        return ProyeccionContinuidad::query()
            ->with([
                'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
                'inscripcionCicloOrigen.cicloEscolar',
                'inscripcionCicloOrigen.nivel',
                'inscripcionCicloOrigen.grado',
                'inscripcionCicloOrigen.generacion',
                'inscripcionCicloOrigen.grupo.asignacionGrupo',
                'cicloDestino',
                'nivelDestino',
                'gradoDestino',
                'semestreDestino',
                'generacionDestino',
                'grupoDestino.asignacionGrupo',
                'inscripcionCicloDestino',
                'usuarioProyecto:id,name',
                'usuarioConfirmo:id,name',
                'usuarioCancelo:id,name',
                'usuarioRevirtio:id,name',
            ])
            ->whereHas('inscripcionCicloOrigen', fn ($query) => $query->where('nivel_id', $nivelOrigenId))
            ->when($cicloDestinoId, fn ($query) => $query->where('ciclo_destino_id', $cicloDestinoId))
            ->when(filled($estado), fn ($query) => $query->where('estado', $estado))
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $query->whereHas('inscripcion', function ($alumno) use ($buscar): void {
                    $alumno->where(function ($sub) use ($buscar): void {
                        $sub->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                            ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                            ->orWhere('matricula', 'like', "%{$buscar}%")
                            ->orWhere('curp', 'like', "%{$buscar}%");
                    });
                });
            })
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 0 WHEN 'confirmada' THEN 1 WHEN 'revertida' THEN 2 ELSE 3 END")
            ->latest('id')
            ->get();
    }

    public function gruposParaProyeccion(ProyeccionContinuidad $proyeccion): Collection
    {
        return $this->asignacionEscolar->gruposCompatibles(
            (int) $proyeccion->ciclo_destino_id,
            (int) $proyeccion->nivel_destino_id,
            (int) $proyeccion->generacion_destino_id,
            (int) $proyeccion->grado_destino_id,
            filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
        );
    }

    public function confirmarProyecciones(
        array $ids,
        array $datosPorProyeccion,
        string $motivo,
        string $fecha,
        int $usuarioId
    ): int {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $motivo = trim($motivo);

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => 'Selecciona al menos una proyección pendiente.',
            ]);
        }
        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_confirmacion_proyeccion' => 'Escribe un motivo de confirmación de al menos 10 caracteres.',
            ]);
        }

        return DB::transaction(function () use ($ids, $datosPorProyeccion, $motivo, $fecha, $usuarioId): int {
            $confirmadas = 0;

            foreach ($ids as $id) {
                $proyeccion = ProyeccionContinuidad::query()->lockForUpdate()->findOrFail($id);
                $datos = $datosPorProyeccion[$id] ?? $datosPorProyeccion[(string) $id] ?? [];
                $this->confirmarProyeccionBloqueada($proyeccion, $datos, $motivo, $fecha, $usuarioId);
                $confirmadas++;
            }

            return $confirmadas;
        });
    }

    public function cancelarProyecciones(array $ids, string $motivo, int $usuarioId): int
    {
        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $motivo = trim($motivo);

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => 'Selecciona al menos una proyección pendiente.',
            ]);
        }
        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_cancelacion_proyeccion' => 'Escribe el motivo por el que los alumnos no continuarán.',
            ]);
        }

        return DB::transaction(function () use ($ids, $motivo, $usuarioId): int {
            $canceladas = 0;

            foreach ($ids as $id) {
                $proyeccion = ProyeccionContinuidad::query()->with('inscripcionCicloOrigen')->lockForUpdate()->findOrFail($id);
                if ($proyeccion->estado !== 'pendiente') {
                    throw ValidationException::withMessages([
                        'seleccion_proyecciones' => "La proyección #{$proyeccion->id} ya fue {$proyeccion->estado}.",
                    ]);
                }

                $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($proyeccion->inscripcion_id);
                $antes = $this->snapshotAlumno($alumno);
                $estatusEsperados = array_values(array_unique(array_filter([
                    $proyeccion->estatus_pendiente,
                    ($proyeccion->tipo_proyeccion ?? '') === 'siguiente_nivel'
                        ? 'egresado'
                        : 'pendiente_reinscripcion',
                ])));
                if (! in_array((string) ($alumno->estatus ?? ''), $estatusEsperados, true)) {
                    throw ValidationException::withMessages([
                        'seleccion_proyecciones' => "{$alumno->nombre} ya cambió de estatus. Revisa su trayectoria antes de cancelar la proyección.",
                    ]);
                }

                $origen = $proyeccion->inscripcionCicloOrigen;
                $mismoCiclo = $origen
                    && (int) $proyeccion->ciclo_destino_id === (int) $origen->ciclo_escolar_id;

                if (($proyeccion->tipo_proyeccion ?? '') !== 'siguiente_nivel'
                    && $mismoCiclo
                    && $origen->estado === 'en_curso') {
                    $resultadoOrigen = (string) ($proyeccion->resultado_origen ?: 'promovido_grado');
                    $alumno = $this->cerrarOrigenSinActivarDestino(
                        $alumno,
                        $origen,
                        $resultadoOrigen,
                        'no_reinscrito',
                        $motivo,
                        $usuarioId,
                        now()->toDateString(),
                        in_array($resultadoOrigen, ['promovido', 'promovido_grado', 'promovido_nivel'], true),
                    );
                } elseif (($proyeccion->tipo_proyeccion ?? '') !== 'siguiente_nivel'
                    && ($alumno->estatus ?? '') === 'pendiente_reinscripcion') {
                    $alumno = $this->gestionAcademica->cambiarEstatus(
                        $alumno,
                        'no_reinscrito',
                        $motivo,
                        $usuarioId,
                        now()->toDateString(),
                    );
                }

                $proyeccion->update([
                    'estado' => 'cancelada',
                    'cancelada_at' => now(),
                    'cancelada_por' => $usuarioId,
                    'motivo_cancelacion' => $motivo,
                    'snapshot_cancelacion' => $this->snapshotAlumno($alumno->fresh()),
                ]);

                $proyeccion->detalleCierre?->update([
                    'observacion' => ($proyeccion->tipo_proyeccion === 'siguiente_nivel')
                        ? 'La familia no confirmó la continuidad. El alumno conserva su egreso del nivel de origen y no causó baja en el nivel destino.'
                        : 'La familia no confirmó la reinscripción. Se conserva el resultado académico del ciclo de origen y el alumno queda como no reinscrito, sin registrar una baja.',
                    'estado_nuevo' => $this->snapshotAlumno($alumno->fresh()),
                ]);

                CambioAcademico::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $proyeccion->inscripcion_ciclo_origen_id,
                    'generacion_id' => $proyeccion->inscripcionCicloOrigen?->generacion_id,
                    'tipo' => 'cancelacion_proyeccion_continuidad',
                    'motivo' => $motivo,
                    'datos_anteriores' => array_merge($antes, ['proyeccion' => 'pendiente']),
                    'datos_nuevos' => array_merge($this->snapshotAlumno($alumno->fresh()), ['proyeccion' => 'cancelada']),
                    'realizado_por' => $usuarioId,
                    'realizado_at' => now(),
                ]);
                $canceladas++;
            }

            return $canceladas;
        });
    }

    /**
     * Cambia una decisión previa de "No continuará" a "Continuará".
     *
     * - Si la proyección fue cancelada antes de formalizar el destino, reutiliza
     *   el flujo normal de confirmación y conserva la auditoría de la cancelación.
     * - Si la proyección ya había sido confirmada y después retirada por no
     *   inicio, reactiva el mismo historial destino anulado en lugar de crear un
     *   segundo registro histórico.
     */
    public function reactivarProyeccionNoContinuara(
        int $proyeccionId,
        array $datos,
        string $motivo,
        string $fecha,
        int $usuarioId
    ): ProyeccionContinuidad {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_reactivacion' => 'Escribe un motivo de reactivación de al menos 10 caracteres.',
            ]);
        }

        try {
            $fecha = CarbonImmutable::parse($fecha)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'fecha_reactivacion' => 'La fecha de reactivación no es válida.',
            ]);
        }

        return DB::transaction(function () use ($proyeccionId, $datos, $motivo, $fecha, $usuarioId): ProyeccionContinuidad {
            $proyeccion = ProyeccionContinuidad::query()
                ->with([
                    'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
                    'inscripcionCicloOrigen.cicloEscolar',
                    'inscripcionCicloDestino.cicloEscolar',
                    'detalleCierre',
                ])
                ->lockForUpdate()
                ->findOrFail($proyeccionId);

            if (! in_array($proyeccion->estado, ['cancelada', 'revertida'], true)) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => 'Solo se puede cambiar a Continuará una proyección marcada como No continuará.',
                ]);
            }

            $estadoAnterior = (string) $proyeccion->estado;
            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($proyeccion->inscripcion_id);
            if ($alumno->trashed()) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => 'El expediente del alumno está eliminado. Restáuralo antes de reactivar la continuidad.',
                ]);
            }

            $grupoId = (int) ($datos['grupo_destino_id'] ?? $proyeccion->grupo_destino_id ?? 0);
            $matricula = filled($datos['matricula'] ?? null)
                ? mb_strtoupper(trim((string) $datos['matricula']))
                : (string) ($proyeccion->matricula_sugerida ?: $alumno->matricula);

            $destinoDatos = [
                'ciclo_escolar_id' => (int) $proyeccion->ciclo_destino_id,
                'nivel_id' => (int) $proyeccion->nivel_destino_id,
                'generacion_id' => (int) $proyeccion->generacion_destino_id,
                'grado_id' => (int) $proyeccion->grado_destino_id,
                'semestre_id' => filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
                'grupo_id' => $grupoId,
                'matricula' => $matricula,
            ];
            $this->validarDestinoGrupo($destinoDatos);

            $antesAlumno = $this->snapshotAlumno($alumno);
            $antesProyeccion = [
                'estado' => $estadoAnterior,
                'cancelada_at' => $proyeccion->cancelada_at?->toIso8601String(),
                'motivo_cancelacion' => $proyeccion->motivo_cancelacion,
                'revertida_at' => $proyeccion->revertida_at?->toIso8601String(),
                'motivo_reversion' => $proyeccion->motivo_reversion,
                'inscripcion_ciclo_destino_id' => $proyeccion->inscripcion_ciclo_destino_id,
            ];

            if ($estadoAnterior === 'cancelada') {
                if ($proyeccion->inscripcion_ciclo_destino_id) {
                    throw ValidationException::withMessages([
                        'reactivacion_proyeccion' => 'La proyección cancelada ya tiene un historial destino asociado. Revisa la trayectoria antes de continuar.',
                    ]);
                }

                $origen = $proyeccion->inscripcionCicloOrigen;
                if (! $origen || (int) $alumno->ciclo_escolar_id !== (int) $origen->ciclo_escolar_id) {
                    throw ValidationException::withMessages([
                        'reactivacion_proyeccion' => 'El alumno ya tiene otra ubicación académica vigente. Revisa su trayectoria antes de cambiar la decisión.',
                    ]);
                }

                // Se conserva cancelada_at durante esta llamada para que la
                // confirmación permita el estatus no_reinscrito generado por la
                // cancelación. El flujo normal limpia esos campos al confirmar.
                $proyeccion->forceFill(['estado' => 'pendiente'])->save();

                $confirmada = $this->confirmarProyeccionBloqueada(
                    $proyeccion->fresh(['inscripcionCicloOrigen']),
                    [
                        'grupo_destino_id' => $grupoId,
                        'matricula' => $matricula,
                    ],
                    $motivo,
                    $fecha,
                    $usuarioId,
                );

                CambioAcademico::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $confirmada->inscripcion_ciclo_destino_id,
                    'generacion_id' => $confirmada->generacion_destino_id,
                    'tipo' => 'cambio_no_continuara_a_continuara',
                    'motivo' => $motivo,
                    'datos_anteriores' => [
                        'alumno' => $antesAlumno,
                        'proyeccion' => $antesProyeccion,
                    ],
                    'datos_nuevos' => [
                        'alumno' => $this->snapshotAlumno($alumno->fresh()),
                        'proyeccion' => 'confirmada',
                    ],
                    'realizado_por' => $usuarioId,
                    'realizado_at' => now(),
                ]);

                return $confirmada->fresh([
                    'inscripcion',
                    'inscripcionCicloOrigen',
                    'inscripcionCicloDestino',
                ]);
            }

            $origen = InscripcionCiclo::query()->lockForUpdate()->find($proyeccion->inscripcion_ciclo_origen_id);
            $destino = InscripcionCiclo::query()->lockForUpdate()->find($proyeccion->inscripcion_ciclo_destino_id);

            if (! $origen || ! $destino) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => 'No se encontró el historial de origen o el historial destino que fue retirado.',
                ]);
            }
            if ($destino->estado !== 'anulado' || (string) $destino->resultado_final !== 'no_iniciado') {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => 'El historial destino ya cambió después del retiro. No se puede reactivar automáticamente.',
                ]);
            }
            if ((int) $alumno->ciclo_escolar_id !== (int) $origen->ciclo_escolar_id) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => 'El alumno ya tiene otra ubicación académica vigente. Revisa su trayectoria antes de reactivar el destino.',
                ]);
            }

            $otroCicloVigente = InscripcionCiclo::query()
                ->where('inscripcion_id', $alumno->id)
                ->whereNotIn('id', [$origen->id, $destino->id])
                ->where('estado', 'en_curso')
                ->exists();
            if ($otroCicloVigente) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => 'Existe otro ciclo vigente para el alumno. No se puede reactivar esta continuidad.',
                ]);
            }

            if ($destino->cicloEscolar) {
                $posterior = InscripcionCiclo::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->whereNotIn('id', [$origen->id, $destino->id])
                    ->whereHas('cicloEscolar', fn ($query) => $query->where('inicio_anio', '>', (int) $destino->cicloEscolar->inicio_anio))
                    ->exists();
                if ($posterior) {
                    throw ValidationException::withMessages([
                        'reactivacion_proyeccion' => 'Existe un historial en un ciclo posterior. No se puede reactivar esta continuidad de forma aislada.',
                    ]);
                }
            }

            $bloqueosActividad = [];
            foreach ([
                'alertas_academicas' => 'alertas académicas',
                'calificaciones' => 'calificaciones',
                'calificaciones_campos_formativos' => 'calificaciones de campos formativos',
                'ficha_descriptivas' => 'fichas descriptivas',
                'asistencias_finales_bachillerato' => 'asistencias finales de bachillerato',
                'decisiones_promocion_oficial' => 'decisiones oficiales de promoción',
                'lugares_preescolar' => 'lugares o reconocimientos de preescolar',
                'bitacora_calificaciones' => 'movimientos en la bitácora de calificaciones',
                'calificacion_correcciones' => 'solicitudes o correcciones de calificaciones',
                'integridad_academica_casos' => 'casos de integridad académica',
                'riesgo_academico_evaluaciones' => 'evaluaciones de riesgo académico',
                'seguimiento_academico_casos' => 'casos de seguimiento académico',
                'seguimiento_academico_eventos' => 'eventos de seguimiento académico',
            ] as $tabla => $etiqueta) {
                if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
                    continue;
                }
                $cantidad = (int) DB::table($tabla)->where('inscripcion_ciclo_id', $destino->id)->count();
                if ($cantidad > 0) {
                    $bloqueosActividad[] = "Tiene {$cantidad} registro(s) de {$etiqueta} en el ciclo destino.";
                }
            }
            if (Schema::hasTable('documentos_alumnos')
                && Schema::hasColumn('documentos_alumnos', 'ciclo_escolar_id')) {
                $documentosDestino = (int) DB::table('documentos_alumnos')
                    ->where('inscripcion_id', $destino->inscripcion_id)
                    ->where('ciclo_escolar_id', $destino->ciclo_escolar_id)
                    ->when(Schema::hasColumn('documentos_alumnos', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                    ->count();

                if ($documentosDestino > 0) {
                    $bloqueosActividad[] = "Tiene {$documentosDestino} documento(s) emitido(s) o asociado(s) al ciclo destino.";
                }
            }

            if ($bloqueosActividad !== []) {
                throw ValidationException::withMessages([
                    'reactivacion_proyeccion' => "No se puede reactivar automáticamente porque el historial destino recibió actividad después del retiro:\n- ".implode("\n- ", $bloqueosActividad),
                ]);
            }

            if ($destino->fecha_ingreso && CarbonImmutable::parse($fecha)->lessThan(CarbonImmutable::parse($destino->fecha_ingreso))) {
                throw ValidationException::withMessages([
                    'fecha_reactivacion' => 'La fecha efectiva no puede ser anterior a la fecha de ingreso registrada originalmente en el ciclo destino.',
                ]);
            }

            $antesOrigen = $this->snapshotHistorialParaFirma($origen);
            $antesDestino = $this->snapshotHistorialParaFirma($destino);

            $destino->forceFill([
                'matricula' => $matricula,
                'nivel_id' => (int) $proyeccion->nivel_destino_id,
                'grado_id' => (int) $proyeccion->grado_destino_id,
                'generacion_id' => (int) $proyeccion->generacion_destino_id,
                'grupo_id' => $grupoId,
                'semestre_id' => filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
                'estado' => 'en_curso',
                'fecha_salida' => null,
                'estatus_ingreso' => 'activo',
                'estatus_actual_ciclo' => 'activo',
                'resultado_final' => null,
                'promovido' => false,
                'cerrado_at' => null,
                'cerrado_por' => null,
                'motivo_cierre' => null,
                'inscripcion_ciclo_destino_id' => null,
                'snapshot_cierre' => null,
                'origen' => 'continuidad_reactivada',
            ])->save();

            $destino->asignaciones()->create([
                'nivel_id' => (int) $proyeccion->nivel_destino_id,
                'grado_id' => (int) $proyeccion->grado_destino_id,
                'generacion_id' => (int) $proyeccion->generacion_destino_id,
                'grupo_id' => $grupoId,
                'semestre_id' => filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
                'fecha_inicio' => $fecha,
                'fecha_fin' => null,
                'tipo' => 'reactivacion_continuidad',
                'motivo' => $motivo,
                'es_actual' => true,
                'registrado_por' => $usuarioId,
                'snapshot' => [
                    'ciclo_escolar_id' => (int) $proyeccion->ciclo_destino_id,
                    'nivel_id' => (int) $proyeccion->nivel_destino_id,
                    'grado_id' => (int) $proyeccion->grado_destino_id,
                    'generacion_id' => (int) $proyeccion->generacion_destino_id,
                    'grupo_id' => $grupoId,
                    'semestre_id' => filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
                    'matricula' => $matricula,
                    'estatus' => 'activo',
                ],
            ]);

            $origen->forceFill([
                'estatus_actual_ciclo' => (string) ($proyeccion->estatus_pendiente ?: $origen->estatus_actual_ciclo),
                'inscripcion_ciclo_destino_id' => $destino->id,
            ])->save();

            $alumno->forceFill([
                'matricula' => $matricula,
                'ciclo_escolar_id' => (int) $proyeccion->ciclo_destino_id,
                'nivel_id' => (int) $proyeccion->nivel_destino_id,
                'grado_id' => (int) $proyeccion->grado_destino_id,
                'generacion_id' => (int) $proyeccion->generacion_destino_id,
                'grupo_id' => $grupoId,
                'semestre_id' => filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
                'estatus' => 'activo',
                'activo' => true,
                'fecha_estatus' => $fecha,
                'motivo_estatus' => $motivo,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'indicador_reingreso' => false,
                'documentacion_reingreso_pendiente' => false,
                'usuario_acceso_activo' => true,
            ])->save();
            $alumno = $alumno->fresh();
            $this->matriculas->asegurarVigente($alumno, 'reactivacion_continuidad', $usuarioId, $fecha);

            $proyeccion->forceFill([
                'grupo_destino_id' => $grupoId,
                'matricula_sugerida' => $matricula,
                'estado' => 'confirmada',
                'confirmada_at' => now(),
                'confirmada_por' => $usuarioId,
                'inscripcion_ciclo_destino_id' => $destino->id,
                'snapshot_confirmacion' => $this->snapshotAlumno($alumno),
                'cancelada_at' => null,
                'cancelada_por' => null,
                'motivo_cancelacion' => null,
                'snapshot_cancelacion' => null,
                'revertida_at' => null,
                'revertida_por' => null,
                'fecha_reversion' => null,
                'tipo_reversion' => null,
                'motivo_reversion' => null,
                'snapshot_reversion' => null,
            ])->save();

            $proyeccion->detalleCierre?->update([
                'inscripcion_ciclo_destino_id' => $destino->id,
                'observacion' => 'La decisión administrativa cambió de No continuará a Continuará. Se reactivó el ciclo destino previamente anulado y se conservó el historial de cambios.',
                'estado_nuevo' => $this->snapshotAlumno($alumno),
            ]);

            CambioAcademico::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $destino->id,
                'generacion_id' => $destino->generacion_id,
                'tipo' => 'cambio_no_continuara_a_continuara',
                'motivo' => $motivo,
                'datos_anteriores' => [
                    'alumno' => $antesAlumno,
                    'origen' => $antesOrigen,
                    'destino' => $antesDestino,
                    'proyeccion' => $antesProyeccion,
                ],
                'datos_nuevos' => [
                    'alumno' => $this->snapshotAlumno($alumno),
                    'origen' => $this->snapshotHistorialParaFirma($origen->fresh()),
                    'destino' => $this->snapshotHistorialParaFirma($destino->fresh()),
                    'proyeccion' => 'confirmada',
                ],
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            MovimientoAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $destino->id,
                'ciclo_escolar_id' => $destino->ciclo_escolar_id,
                'ciclo_id' => $alumno->ciclo_id,
                'nivel_anterior_id' => $origen->nivel_id,
                'nivel_nuevo_id' => $destino->nivel_id,
                'resultado_continuidad' => 'continuidad_reactivada',
                'usuario_acceso_activo' => true,
                'tipo' => 'reactivacion_ciclo_destino',
                'fecha' => $fecha,
                'motivo' => $motivo,
                'observaciones' => 'Cambio administrativo de No continuará a Continuará. Se reactivó el mismo historial destino que había quedado como no iniciado.',
                'estado_anterior' => $antesAlumno,
                'estado_nuevo' => $this->snapshotAlumno($alumno),
                'registrado_por' => $usuarioId,
            ]);

            return $proyeccion->fresh([
                'inscripcion',
                'inscripcionCicloOrigen',
                'inscripcionCicloDestino',
                'usuarioConfirmo',
            ]);
        });
    }

    /**
     * Revisa si una continuidad ya confirmada puede anularse de forma
     * individual porque el alumno finalmente no inició el ciclo destino.
     *
     * La promoción o el egreso del ciclo de origen no se revierte. Únicamente
     * se anula la activación administrativa creada en el ciclo destino.
     */
    public function diagnosticoRetiroProyeccion(int $proyeccionId): array
    {
        $proyeccion = ProyeccionContinuidad::query()
            ->with([
                'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
                'inscripcionCicloOrigen.cicloEscolar',
                'inscripcionCicloOrigen.nivel',
                'inscripcionCicloOrigen.grado',
                'inscripcionCicloOrigen.semestre',
                'inscripcionCicloOrigen.grupo.asignacionGrupo',
                'inscripcionCicloDestino.cicloEscolar',
                'inscripcionCicloDestino.nivel',
                'inscripcionCicloDestino.grado',
                'inscripcionCicloDestino.semestre',
                'inscripcionCicloDestino.grupo.asignacionGrupo',
                'cicloDestino',
                'nivelDestino',
                'gradoDestino',
                'semestreDestino',
                'grupoDestino.asignacionGrupo',
            ])
            ->findOrFail($proyeccionId);

        return $this->evaluarRetiroProyeccion($proyeccion);
    }

    /**
     * Anula de manera auditada la activación de un alumno en el ciclo destino.
     * El registro destino se conserva como "anulado / no inició" y el alumno
     * vuelve a mostrar como última ubicación el grado o nivel que concluyó.
     */
    public function retirarProyeccionConfirmada(
        int $proyeccionId,
        string $motivo,
        string $fecha,
        int $usuarioId
    ): ProyeccionContinuidad {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_retiro' => 'Escribe un motivo de al menos 10 caracteres.',
            ]);
        }

        try {
            $fechaRetiro = CarbonImmutable::parse($fecha)->startOfDay();
            if ($fechaRetiro->isAfter(CarbonImmutable::today())) {
                throw ValidationException::withMessages([
                    'fecha_retiro' => 'La fecha del retiro no puede estar en el futuro.',
                ]);
            }
            $fecha = $fechaRetiro->toDateString();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'fecha_retiro' => 'La fecha del retiro no es válida.',
            ]);
        }

        return DB::transaction(function () use ($proyeccionId, $motivo, $fecha, $usuarioId): ProyeccionContinuidad {
            $proyeccion = ProyeccionContinuidad::query()
                ->with([
                    'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
                    'inscripcionCicloOrigen.cicloEscolar',
                    'inscripcionCicloOrigen.nivel',
                    'inscripcionCicloOrigen.grado',
                    'inscripcionCicloOrigen.semestre',
                    'inscripcionCicloOrigen.grupo.asignacionGrupo',
                    'inscripcionCicloDestino.cicloEscolar',
                    'inscripcionCicloDestino.nivel',
                    'inscripcionCicloDestino.grado',
                    'inscripcionCicloDestino.semestre',
                    'inscripcionCicloDestino.grupo.asignacionGrupo',
                ])
                ->lockForUpdate()
                ->findOrFail($proyeccionId);

            $diagnostico = $this->evaluarRetiroProyeccion($proyeccion);
            if (! $diagnostico['puede_retirar']) {
                throw ValidationException::withMessages([
                    'retiro_proyeccion' => "No se puede retirar al alumno del ciclo destino:\n- "
                        .implode("\n- ", $diagnostico['bloqueos']),
                ]);
            }

            $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($proyeccion->inscripcion_id);
            $origen = InscripcionCiclo::query()->lockForUpdate()->findOrFail($proyeccion->inscripcion_ciclo_origen_id);
            $destino = InscripcionCiclo::query()->lockForUpdate()->findOrFail($proyeccion->inscripcion_ciclo_destino_id);

            $antesAlumno = $this->snapshotAlumno($alumno);
            $antesOrigen = $this->snapshotHistorialParaFirma($origen);
            $antesDestino = $this->snapshotHistorialParaFirma($destino);

            $estatusFinal = $this->estatusFinalTrasRetiro($proyeccion);
            $fechaCierreDestino = CarbonImmutable::parse($fecha);
            if ($destino->fecha_ingreso && $fechaCierreDestino->lessThan(CarbonImmutable::parse($destino->fecha_ingreso))) {
                $fechaCierreDestino = CarbonImmutable::parse($destino->fecha_ingreso);
            }
            $fechaCierreDestino = $fechaCierreDestino->toDateString();

            $destino->asignaciones()
                ->where('es_actual', true)
                ->update([
                    'es_actual' => false,
                    'fecha_fin' => $fechaCierreDestino,
                    'motivo' => $motivo,
                    'updated_at' => now(),
                ]);

            $destino->forceFill([
                'estado' => 'anulado',
                'fecha_salida' => $fechaCierreDestino,
                'estatus_ingreso' => 'no_reinscrito',
                'estatus_actual_ciclo' => 'no_reinscrito',
                'resultado_final' => 'no_iniciado',
                'promovido' => false,
                'cerrado_at' => now(),
                'cerrado_por' => $usuarioId,
                'motivo_cierre' => $motivo,
                'inscripcion_ciclo_destino_id' => null,
                'origen' => 'continuidad_confirmada_anulada',
            ])->save();

            $snapshotDestino = $this->snapshotHistorialParaFirma($destino->fresh());
            unset($snapshotDestino['snapshot_cierre']);
            $destino->forceFill(['snapshot_cierre' => $snapshotDestino])->saveQuietly();

            $origen->forceFill([
                'estatus_actual_ciclo' => $estatusFinal,
                'inscripcion_ciclo_destino_id' => null,
            ])->save();
            $snapshotOrigen = $this->snapshotHistorialParaFirma($origen->fresh());
            unset($snapshotOrigen['snapshot_cierre']);
            $origen->forceFill(['snapshot_cierre' => $snapshotOrigen])->saveQuietly();

            $alumno->forceFill([
                'matricula' => $origen->matricula ?: ($proyeccion->snapshot_origen['matricula'] ?? $alumno->matricula),
                'ciclo_escolar_id' => $origen->ciclo_escolar_id,
                'nivel_id' => $origen->nivel_id,
                'grado_id' => $origen->grado_id,
                'generacion_id' => $origen->generacion_id,
                'grupo_id' => $origen->grupo_id,
                'semestre_id' => $origen->semestre_id,
                'estatus' => $estatusFinal,
                'activo' => false,
                'fecha_estatus' => $fecha,
                'motivo_estatus' => $motivo,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'indicador_reingreso' => false,
                'usuario_acceso_activo' => false,
            ])->save();

            $alumno = $alumno->fresh();
            $this->matriculas->cerrarVigentes($alumno, $fecha);

            $proyeccion->forceFill([
                'estado' => 'revertida',
                'revertida_at' => now(),
                'revertida_por' => $usuarioId,
                'fecha_reversion' => $fecha,
                'tipo_reversion' => 'no_inicio_ciclo_destino',
                'motivo_reversion' => $motivo,
                'snapshot_reversion' => [
                    'alumno' => $this->snapshotAlumno($alumno),
                    'origen' => $this->snapshotHistorialParaFirma($origen->fresh()),
                    'destino' => $this->snapshotHistorialParaFirma($destino->fresh()),
                    'resultado' => 'El ciclo destino se conserva como anulado/no iniciado. El resultado del ciclo de origen no fue modificado.',
                ],
            ])->save();

            $proyeccion->detalleCierre?->update([
                'observacion' => $estatusFinal === 'egresado'
                    ? 'La continuidad confirmada fue retirada individualmente porque el alumno no inició el nivel destino. Se conserva el egreso del nivel de origen.'
                    : 'La continuidad confirmada fue retirada individualmente porque el alumno no inició el grado o semestre destino. Se conserva la promoción del ciclo de origen y queda como no reinscrito.',
                'estado_nuevo' => $this->snapshotAlumno($alumno),
            ]);

            CambioAcademico::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $destino->id,
                'generacion_id' => $origen->generacion_id,
                'tipo' => 'reversion_continuidad_confirmada',
                'motivo' => $motivo,
                'datos_anteriores' => [
                    'alumno' => $antesAlumno,
                    'origen' => $antesOrigen,
                    'destino' => $antesDestino,
                    'proyeccion' => 'confirmada',
                ],
                'datos_nuevos' => [
                    'alumno' => $this->snapshotAlumno($alumno),
                    'origen' => $this->snapshotHistorialParaFirma($origen->fresh()),
                    'destino' => $this->snapshotHistorialParaFirma($destino->fresh()),
                    'proyeccion' => 'revertida',
                ],
                'realizado_por' => $usuarioId,
                'realizado_at' => now(),
            ]);

            MovimientoAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'inscripcion_ciclo_id' => $destino->id,
                'ciclo_escolar_id' => $destino->ciclo_escolar_id,
                'ciclo_id' => $alumno->ciclo_id,
                'nivel_anterior_id' => $destino->nivel_id,
                'nivel_nuevo_id' => $origen->nivel_id,
                'resultado_continuidad' => 'no_iniciado',
                'usuario_acceso_activo' => false,
                'tipo' => 'retiro_ciclo_destino',
                'fecha' => $fecha,
                'motivo' => $motivo,
                'observaciones' => 'Retiro individual de una continuidad ya confirmada. No se eliminó el historial y no se modificó el resultado académico del ciclo de origen.',
                'estado_anterior' => $antesAlumno,
                'estado_nuevo' => $this->snapshotAlumno($alumno),
                'registrado_por' => $usuarioId,
            ]);

            return $proyeccion->fresh([
                'inscripcion',
                'inscripcionCicloOrigen',
                'inscripcionCicloDestino',
                'usuarioRevirtio',
            ]);
        });
    }

    private function evaluarRetiroProyeccion(ProyeccionContinuidad $proyeccion): array
    {
        $proyeccion->loadMissing([
            'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
            'inscripcionCicloOrigen.cicloEscolar',
            'inscripcionCicloDestino.cicloEscolar',
            'cicloDestino',
        ]);

        $bloqueos = collect();
        $actividad = collect();
        $alumno = $proyeccion->inscripcion;
        $origen = $proyeccion->inscripcionCicloOrigen;
        $destino = $proyeccion->inscripcionCicloDestino;

        if ($proyeccion->estado !== 'confirmada') {
            $bloqueos->push('La proyección ya no está confirmada o fue atendida anteriormente.');
        }
        if (! $alumno) {
            $bloqueos->push('No se encontró el expediente del alumno.');
        } elseif ($alumno->trashed()) {
            $bloqueos->push('El expediente del alumno está eliminado. Restáuralo antes de continuar.');
        }
        if (! $origen) {
            $bloqueos->push('No se encontró el historial del ciclo de origen.');
        }
        if (! $destino) {
            $bloqueos->push('No se encontró el historial creado en el ciclo destino.');
        }

        if ($origen && $destino && (int) $origen->id === (int) $destino->id) {
            $bloqueos->push('La continuidad fue confirmada dentro del mismo ciclo escolar. No puede retirarse como si existiera un ciclo destino separado; utiliza la reversión auditada del proceso o corrige la asignación semestral desde Control Escolar.');
        }

        if ($alumno && $destino) {
            if ((int) $alumno->ciclo_escolar_id !== (int) $destino->ciclo_escolar_id) {
                $bloqueos->push('El alumno ya tiene otra ubicación académica vigente. Revisa su trayectoria antes de retirar la continuidad.');
            }

            if ($destino->estado !== 'en_curso') {
                $bloqueos->push('El ciclo destino ya fue cerrado, anulado o atendido mediante otro proceso.');
            }

            if (! in_array((string) $destino->estatus_actual_ciclo, ['activo', 'reingreso', 'no_promovido'], true)) {
                $bloqueos->push('El alumno ya tiene una baja, traslado, suspensión u otro estatus en el ciclo destino. Debe conservarse ese movimiento.');
            }

            if (filled($destino->resultado_final)) {
                $bloqueos->push('El ciclo destino ya tiene un resultado final registrado.');
            }

            if ($destino->inscripcion_ciclo_destino_id) {
                $bloqueos->push('El alumno ya fue enlazado con un ciclo posterior.');
            }
        }

        if ($alumno && $destino && $destino->cicloEscolar) {
            $posterior = InscripcionCiclo::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('id', '!=', $destino->id)
                ->whereHas('cicloEscolar', function ($query) use ($destino): void {
                    $query->where('inicio_anio', '>', (int) $destino->cicloEscolar->inicio_anio);
                })
                ->exists();

            if ($posterior) {
                $bloqueos->push('Existe un historial en un ciclo posterior. No se puede retroceder esta continuidad de forma aislada.');
            }
        }

        if ($destino) {
            $tablasActividad = [
                'alertas_academicas' => 'alertas académicas',
                'calificaciones' => 'calificaciones',
                'calificaciones_campos_formativos' => 'calificaciones de campos formativos',
                'ficha_descriptivas' => 'fichas descriptivas',
                'asistencias_finales_bachillerato' => 'asistencias finales de bachillerato',
                'decisiones_promocion_oficial' => 'decisiones oficiales de promoción',
                'lugares_preescolar' => 'lugares o reconocimientos de preescolar',
                'bitacora_calificaciones' => 'movimientos en la bitácora de calificaciones',
                'calificacion_correcciones' => 'solicitudes o correcciones de calificaciones',
                'integridad_academica_casos' => 'casos de integridad académica',
                'riesgo_academico_evaluaciones' => 'evaluaciones de riesgo académico',
                'seguimiento_academico_casos' => 'casos de seguimiento académico',
                'seguimiento_academico_eventos' => 'eventos de seguimiento académico',
            ];

            foreach ($tablasActividad as $tabla => $etiqueta) {
                if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
                    continue;
                }

                $cantidad = (int) DB::table($tabla)
                    ->where('inscripcion_ciclo_id', $destino->id)
                    ->count();

                if ($cantidad > 0) {
                    $actividad->push([
                        'tabla' => $tabla,
                        'etiqueta' => $etiqueta,
                        'cantidad' => $cantidad,
                    ]);
                    $bloqueos->push("Tiene {$cantidad} registro(s) de {$etiqueta} en el ciclo destino.");
                }
            }

            if (Schema::hasTable('documentos_alumnos')
                && Schema::hasColumn('documentos_alumnos', 'ciclo_escolar_id')) {
                $documentosDestino = (int) DB::table('documentos_alumnos')
                    ->where('inscripcion_id', $destino->inscripcion_id)
                    ->where('ciclo_escolar_id', $destino->ciclo_escolar_id)
                    ->whereNull('deleted_at')
                    ->count();

                if ($documentosDestino > 0) {
                    $actividad->push([
                        'tabla' => 'documentos_alumnos',
                        'etiqueta' => 'documentos emitidos o asociados al ciclo destino',
                        'cantidad' => $documentosDestino,
                    ]);
                    $bloqueos->push("Tiene {$documentosDestino} documento(s) emitido(s) o asociado(s) al ciclo destino.");
                }
            }
        }

        $estatusFinal = $this->estatusFinalTrasRetiro($proyeccion);

        return [
            'puede_retirar' => $bloqueos->isEmpty(),
            'bloqueos' => $bloqueos->unique()->values()->all(),
            'actividad' => $actividad->values()->all(),
            'estatus_final' => $estatusFinal,
            'alumno' => $alumno ? trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}") : 'Alumno',
            'origen' => $origen ? [
                'ciclo' => $origen->cicloEscolar ? "{$origen->cicloEscolar->inicio_anio}-{$origen->cicloEscolar->fin_anio}" : 'Ciclo de origen',
                'nivel' => $origen->nivel?->nombre,
                'grado' => $origen->grado?->nombre,
                'semestre' => $origen->semestre?->numero,
                'grupo' => $origen->grupo?->asignacionGrupo?->nombre,
                'resultado' => $origen->resultado_final,
            ] : [],
            'destino' => $destino ? [
                'ciclo' => $destino->cicloEscolar ? "{$destino->cicloEscolar->inicio_anio}-{$destino->cicloEscolar->fin_anio}" : 'Ciclo destino',
                'nivel' => $destino->nivel?->nombre,
                'grado' => $destino->grado?->nombre,
                'semestre' => $destino->semestre?->numero,
                'grupo' => $destino->grupo?->asignacionGrupo?->nombre,
                'estado' => $destino->estado,
            ] : [],
        ];
    }

    private function estatusFinalTrasRetiro(ProyeccionContinuidad $proyeccion): string
    {
        return ($proyeccion->tipo_proyeccion === 'siguiente_nivel'
            || in_array((string) $proyeccion->resultado_origen, ['egresado', 'promovido_nivel'], true))
            ? 'egresado'
            : 'no_reinscrito';
    }

    public function bloqueosReversion(ProcesoCierreCiclo $proceso): Collection
    {
        $proceso->loadMissing('detalles.inscripcionCicloDestino');
        $bloqueos = collect();

        if ($proceso->estado !== 'completado' || $proceso->revertido_at) {
            return collect(['El proceso ya fue revertido o no está completado.']);
        }

        if ($proceso->respaldo_logico && ! $this->firmaValida($proceso->respaldo_logico, $proceso->respaldo_hash)) {
            $bloqueos->push('El respaldo general del proceso no supera la verificación de integridad.');
        }

        foreach ($proceso->detalles as $detalle) {
            if ($detalle->respaldo_origen && ! $this->firmaValida($detalle->respaldo_origen, $detalle->respaldo_hash)) {
                $bloqueos->push(($detalle->inscripcion?->nombre ?: 'Alumno').': el respaldo individual fue alterado o está incompleto.');
            }
            if ($detalle->resultado === 'sin_cambio') {
                continue;
            }

            $destino = $detalle->inscripcionCicloDestino;
            if ($destino) {
                foreach ([
                    'calificaciones' => 'calificaciones',
                    'fichasDescriptivas' => 'fichas descriptivas',
                    'calificacionesCamposFormativos' => 'evaluaciones de campos formativos',
                    'asistenciasFinalesBachillerato' => 'asistencias finales',
                    'decisionesPromocionOficial' => 'decisiones oficiales de promoción',
                    'lugaresPreescolar' => 'lugares o reconocimientos',
                ] as $relacion => $etiqueta) {
                    if ($destino->{$relacion}()->exists()) {
                        $bloqueos->push("{$detalle->inscripcion?->nombre}: tiene {$etiqueta} en el ciclo destino.");
                    }
                }
            }

            if (Schema::hasTable('documentos_alumnos')) {
                $documentosPosteriores = DB::table('documentos_alumnos')
                    ->where('inscripcion_id', $detalle->inscripcion_id)
                    ->where('created_at', '>', $proceso->realizado_at)
                    ->exists();
                if ($documentosPosteriores) {
                    $bloqueos->push("{$detalle->inscripcion?->nombre}: tiene documentos creados después del cierre.");
                }
            }

            $movimientosPosteriores = MovimientoAlumno::query()
                ->where('inscripcion_id', $detalle->inscripcion_id)
                ->where('created_at', '>', $proceso->updated_at)
                ->exists();
            if ($movimientosPosteriores) {
                $bloqueos->push("{$detalle->inscripcion?->nombre}: tiene movimientos posteriores al proceso.");
            }
        }

        return $bloqueos->unique()->values();
    }

    public function revertir(ProcesoCierreCiclo $proceso, string $motivo, int $usuarioId): ProcesoCierreCiclo
    {
        $this->asegurarEsquemaCierreDisponible();

        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_reversion' => 'Escribe un motivo de reversión de al menos 10 caracteres.',
            ]);
        }

        $bloqueos = $this->bloqueosReversion($proceso);
        if ($bloqueos->isNotEmpty()) {
            throw ValidationException::withMessages([
                'motivo_reversion' => "No se puede revertir:\n- ".$bloqueos->implode("\n- "),
            ]);
        }

        return DB::transaction(function () use ($proceso, $motivo, $usuarioId): ProcesoCierreCiclo {
            $proceso = ProcesoCierreCiclo::query()->lockForUpdate()->findOrFail($proceso->id);

            if ($proceso->respaldo_logico && ! $this->firmaValida($proceso->respaldo_logico, $proceso->respaldo_hash)) {
                throw ValidationException::withMessages([
                    'motivo_reversion' => 'El respaldo general no supera la verificación de integridad. No se realizó ningún cambio.',
                ]);
            }

            $detalles = $proceso->detalles()->with(['inscripcionCicloOrigen', 'inscripcionCicloDestino'])->orderByDesc('id')->get();
            $restaurados = 0;
            $omitidos = 0;

            foreach ($detalles as $detalle) {
                if ($detalle->resultado === 'sin_cambio' || $detalle->revertido_at) {
                    $omitidos++;
                    continue;
                }

                if ($detalle->respaldo_origen && ! $this->firmaValida($detalle->respaldo_origen, $detalle->respaldo_hash)) {
                    throw ValidationException::withMessages([
                        'motivo_reversion' => 'El respaldo individual del alumno #'.$detalle->inscripcion_id.' no supera la verificación de integridad.',
                    ]);
                }

                $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($detalle->inscripcion_id);
                $actual = $this->snapshotAlumno($alumno);
                $respaldoDetalle = $detalle->respaldo_origen ?: [];
                $anterior = $respaldoDetalle['inscripcion'] ?? $detalle->estado_anterior ?: [];
                $destino = $detalle->inscripcionCicloDestino;
                $origen = $detalle->inscripcionCicloOrigen;
                $proyeccion = $detalle->proyeccionContinuidad;

                if ($proyeccion) {
                    $proyeccion->delete();
                }

                if ($destino) {
                    if ($origen) {
                        $origen->update(['inscripcion_ciclo_destino_id' => null]);
                    }
                    $destino->asignaciones()->delete();
                    $destino->delete();
                }

                if ($origen && is_array($respaldoDetalle['proyecciones_previas'] ?? null)) {
                    $this->restaurarProyeccionesDesdeRespaldo(
                        $origen,
                        $respaldoDetalle['proyecciones_previas'],
                        $proceso->id,
                        $detalle->id,
                    );
                }

                $alumno->forceFill(Arr::only($anterior, [
                    'matricula', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id',
                    'ciclo_id', 'estatus', 'activo', 'fecha_estatus', 'motivo_estatus', 'fecha_baja', 'motivo_baja',
                    'observaciones_baja', 'indicador_reingreso', 'tipo_ultimo_ingreso', 'fecha_ultimo_ingreso',
                    'documentacion_reingreso_pendiente', 'usuario_acceso_activo', 'deleted_at',
                ]))->save();

                if ($alumno->trashed() && empty($anterior['deleted_at'])) {
                    $alumno->restore();
                }

                if ($origen) {
                    $snapshotOrigen = $respaldoDetalle['inscripcion_ciclo'] ?? null;
                    if (is_array($snapshotOrigen) && $snapshotOrigen !== []) {
                        $origen->forceFill(Arr::except($snapshotOrigen, ['id', 'created_at', 'updated_at']))->saveQuietly();
                        $this->restaurarAsignacionesDesdeRespaldo(
                            $origen,
                            is_array($respaldoDetalle['asignaciones'] ?? null) ? $respaldoDetalle['asignaciones'] : []
                        );
                    } else {
                        $origen->update([
                            'estado' => 'en_curso',
                            'fecha_salida' => null,
                            'estatus_actual_ciclo' => $anterior['estatus'] ?? 'activo',
                            'resultado_final' => null,
                            'promovido' => false,
                            'cerrado_at' => null,
                            'cerrado_por' => null,
                            'motivo_cierre' => null,
                            'snapshot_cierre' => null,
                            'inscripcion_ciclo_destino_id' => null,
                        ]);
                        $ultimaAsignacion = $origen->asignaciones()->latest('fecha_inicio')->latest('id')->first();
                        if ($ultimaAsignacion) {
                            $origen->asignaciones()->update(['es_actual' => false]);
                            $ultimaAsignacion->update(['es_actual' => true, 'fecha_fin' => null]);
                        }
                    }
                }

                if ($detalle->respaldo_origen && is_array($respaldoDetalle['matriculas'] ?? null)) {
                    $this->restaurarMatriculasDesdeRespaldo(
                        $alumno,
                        $respaldoDetalle['matriculas'],
                    );
                } else {
                    $this->matriculas->asegurarVigente(
                        $alumno->fresh(),
                        'reversion_cierre_generacion',
                        $usuarioId,
                        now()->toDateString(),
                    );
                }
                $restaurado = $this->snapshotAlumno($alumno->fresh());

                CambioAcademico::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $origen?->id,
                    'generacion_id' => $anterior['generacion_id'] ?? $proceso->generacion_id,
                    'tipo' => 'reversion_cierre_nivel',
                    'motivo' => $motivo,
                    'datos_anteriores' => $actual,
                    'datos_nuevos' => $restaurado,
                    'realizado_por' => $usuarioId,
                    'realizado_at' => now(),
                ]);

                MovimientoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $origen?->id,
                    'ciclo_escolar_id' => $anterior['ciclo_escolar_id'] ?? $proceso->ciclo_escolar_id,
                    'ciclo_id' => $anterior['ciclo_id'] ?? $alumno->ciclo_id,
                    'nivel_anterior_id' => $actual['nivel_id'] ?? null,
                    'nivel_nuevo_id' => $restaurado['nivel_id'] ?? null,
                    'resultado_continuidad' => 'reversion',
                    'usuario_acceso_activo' => $restaurado['usuario_acceso_activo'] ?? null,
                    'tipo' => 'reversion_cierre_nivel',
                    'fecha' => now()->toDateString(),
                    'motivo' => $motivo,
                    'observaciones' => 'Reversión auditada del proceso de cierre #'.$proceso->id.'.',
                    'estado_anterior' => $actual,
                    'estado_nuevo' => $restaurado,
                    'registrado_por' => $usuarioId,
                ]);

                $detalle->update([
                    'respaldo_verificado_at' => $detalle->respaldo_origen ? now() : $detalle->respaldo_verificado_at,
                    'revertido_at' => now(),
                    'revertido_por' => $usuarioId,
                    'motivo_reversion' => $motivo,
                    'reversion_estado' => $detalle->respaldo_origen ? 'restaurado_desde_respaldo' : 'restaurado_legacy',
                ]);
                $restaurados++;
            }

            $generacion = Generacion::query()->lockForUpdate()->find($proceso->generacion_id);
            $respaldoGeneracion = is_array($proceso->respaldo_logico['generacion'] ?? null)
                ? $proceso->respaldo_logico['generacion']
                : $proceso->estado_anterior_generacion;
            if ($generacion && $respaldoGeneracion) {
                $generacion->forceFill(Arr::only($respaldoGeneracion, [
                    'status', 'estado_cierre', 'cerrada_at', 'cerrada_por', 'motivo_desactivacion', 'observaciones',
                    'cierre_iniciado_at', 'cierre_iniciado_por', 'reactivada_at', 'reactivada_por', 'archivada_at', 'archivada_por',
                ]))->save();
            }

            $proceso->update([
                'estado' => 'revertido',
                'respaldo_verificado_at' => $proceso->respaldo_logico ? now() : $proceso->respaldo_verificado_at,
                'integridad_estado' => $proceso->respaldo_logico ? 'verificado_reversion' : 'legacy',
                'revertido_at' => now(),
                'revertido_por' => $usuarioId,
                'motivo_reversion' => $motivo,
                'reversion_resumen' => [
                    'restaurados' => $restaurados,
                    'omitidos' => $omitidos,
                    'metodo' => $proceso->respaldo_logico ? 'respaldo_logico_firmado' : 'snapshots_legacy',
                    'revertido_at' => now()->toIso8601String(),
                    'revertido_por' => $usuarioId,
                ],
            ]);

            return $proceso->fresh(['detalles.inscripcion', 'generacion']);
        });
    }

    private function prevalidarDecisionesSimulacion(
        array $configuracion,
        array $decisiones,
        Collection $candidatos
    ): void {
        $modo = (string) ($configuracion['modo_proceso'] ?? '');

        foreach ($candidatos->where('procesable', true) as $id => $fila) {
            $decision = $decisiones[$id] ?? $decisiones[(string) $id] ?? ['resultado' => 'pendiente'];
            $resultado = (string) ($decision['resultado'] ?? 'pendiente');
            $alumno = Inscripcion::withTrashed()->findOrFail((int) $fila['id']);

            if (($fila['solo_proyeccion_historica'] ?? false) && $resultado !== 'continuidad_interna') {
                throw ValidationException::withMessages([
                    "decisiones.{$id}.resultado" => "{$fila['nombre']} ya tiene un resultado histórico. Solo puedes crear la proyección provisional faltante.",
                ]);
            }

            if ($resultado === 'continuidad_interna') {
                $destino = $this->destinoContinuidad($configuracion, $decision, $alumno);
                $this->validarDestinoProyeccion($destino);
                $this->asegurarDestinoDisponible(
                    $alumno,
                    (int) $destino['ciclo_escolar_id'],
                    $fila['nombre'],
                    (int) $fila['inscripcion_ciclo_id'],
                );
                continue;
            }

            if ($resultado === 'no_promovido') {
                $destino = $this->destinoRepeticion($configuracion, $decision, $fila, $alumno);
                $this->validarDestinoProyeccion($destino);
                $this->asegurarDestinoDisponible(
                    $alumno,
                    (int) $destino['ciclo_escolar_id'],
                    $fila['nombre'],
                    (int) $fila['inscripcion_ciclo_id'],
                );
                continue;
            }

            if ($resultado === 'no_reinscrito' && $modo !== 'promocion_grado') {
                throw ValidationException::withMessages([
                    "decisiones.{$id}.resultado" => 'La opción no reinscrito solo corresponde a grados o semestres intermedios.',
                ]);
            }

            if ($resultado === 'egresado' && ! ($fila['es_grado_final'] ?? false)) {
                throw ValidationException::withMessages([
                    "decisiones.{$id}.resultado" => "{$fila['nombre']} no está en el último grado o semestre.",
                ]);
            }
        }
    }

    private function bloquearContextoSimulacion(Collection $candidatos): void
    {
        $idsAlumnos = $candidatos->pluck('id')->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $idsHistoriales = $candidatos->pluck('inscripcion_ciclo_id')->map(fn ($id): int => (int) $id)->filter()->unique()->values();

        Inscripcion::withTrashed()->whereIn('id', $idsAlumnos)->lockForUpdate()->get();
        InscripcionCiclo::query()->whereIn('id', $idsHistoriales)->lockForUpdate()->get();

        if (Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            DB::table('inscripcion_ciclo_asignaciones')
                ->whereIn('inscripcion_ciclo_id', $idsHistoriales)
                ->lockForUpdate()
                ->get();
        }

        foreach ([
            'calificaciones',
            'ficha_descriptivas',
            'calificaciones_campos_formativos',
            'asistencias_finales_bachillerato',
            'decisiones_promocion_oficial',
            'lugares_preescolar',
            'movimientos_alumnos',
            'cambios_academicos',
        ] as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
                DB::table($tabla)
                    ->whereIn('inscripcion_ciclo_id', $idsHistoriales)
                    ->lockForUpdate()
                    ->get();
            }
        }

        if (Schema::hasTable('proyecciones_continuidad')) {
            DB::table('proyecciones_continuidad')
                ->whereIn('inscripcion_ciclo_origen_id', $idsHistoriales)
                ->lockForUpdate()
                ->get();
        }

        foreach (['documentos_alumnos', 'matriculas_alumnos'] as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'inscripcion_id')) {
                DB::table($tabla)
                    ->whereIn('inscripcion_id', $idsAlumnos)
                    ->lockForUpdate()
                    ->get();
            }
        }
    }

    private function construirContenidoSimulacion(
        array $configuracion,
        array $decisiones,
        Collection $candidatos,
        Generacion $generacion,
        int $usuarioId
    ): array {
        $idsHistoriales = $candidatos->pluck('inscripcion_ciclo_id')->map(fn ($id): int => (int) $id)->values();
        $idsAlumnos = $candidatos->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $historiales = InscripcionCiclo::query()
            ->with('asignaciones')
            ->whereIn('id', $idsHistoriales)
            ->get()
            ->keyBy('id');
        $alumnos = Inscripcion::withTrashed()->whereIn('id', $idsAlumnos)->get()->keyBy('id');
        $huellasHistorial = $this->huellasDependenciasPorHistorial($idsHistoriales);
        $huellasAlumno = $this->huellasDependenciasPorAlumno($idsAlumnos);

        $filas = $candidatos
            ->sortBy('id')
            ->map(function (array $fila) use (
                $decisiones,
                $historiales,
                $alumnos,
                $huellasHistorial,
                $huellasAlumno
            ): array {
                $id = (int) $fila['id'];
                $historial = $historiales->get((int) $fila['inscripcion_ciclo_id']);
                $alumno = $alumnos->get($id);
                $decision = $this->normalizarDecision(
                    $decisiones[$id] ?? $decisiones[(string) $id] ?? ['resultado' => 'pendiente']
                );

                return [
                    'inscripcion_id' => $id,
                    'inscripcion_ciclo_id' => (int) $fila['inscripcion_ciclo_id'],
                    'nombre' => (string) $fila['nombre'],
                    'procesable' => (bool) $fila['procesable'],
                    'solo_proyeccion_historica' => (bool) ($fila['solo_proyeccion_historica'] ?? false),
                    'resultado_existente' => $fila['resultado_existente'] ?? null,
                    'decision' => $decision,
                    'advertencias' => array_values($fila['advertencias'] ?? []),
                    'inscripcion' => $alumno ? array_merge($this->snapshotAlumno($alumno), [
                        'updated_at' => optional($alumno->updated_at)->toIso8601String(),
                    ]) : [],
                    'historial' => $historial ? $this->snapshotHistorialParaFirma($historial) : [],
                    'asignaciones' => $historial
                        ? $historial->asignaciones->map(fn ($asignacion): array => $asignacion->getAttributes())->values()->all()
                        : [],
                    'dependencias_historial' => $huellasHistorial[(int) $fila['inscripcion_ciclo_id']] ?? [],
                    'dependencias_alumno' => $huellasAlumno[$id] ?? [],
                ];
            })
            ->values();

        $procesables = $filas->where('procesable', true);
        $conteos = $procesables
            ->map(fn (array $fila): string => (string) ($fila['decision']['resultado'] ?? 'pendiente'))
            ->countBy()
            ->sortKeys()
            ->all();
        $advertencias = $filas
            ->flatMap(function (array $fila): array {
                return array_map(
                    fn (string $advertencia): string => $fila['nombre'].': '.$advertencia,
                    $fila['advertencias'] ?? []
                );
            })
            ->unique()
            ->values()
            ->all();

        return [
            'version' => self::SIMULACION_VERSION,
            'simulado_por' => $usuarioId,
            'configuracion' => $this->normalizarConfiguracionSimulacion($configuracion),
            'generacion' => [
                'id' => (int) $generacion->id,
                'snapshot' => $this->snapshotGeneracion($generacion),
                'updated_at' => optional($generacion->updated_at)->toIso8601String(),
            ],
            'totales' => [
                'evaluados' => $filas->count(),
                'procesables' => $procesables->count(),
                'sin_cambio' => $filas->where('procesable', false)->count(),
            ],
            'conteos' => $conteos,
            'advertencias' => $advertencias,
            'alumnos' => $filas->all(),
        ];
    }

    private function verificarSimulacion(
        array $simulacion,
        array $configuracion,
        array $decisiones,
        Collection $candidatos,
        Generacion $generacion,
        int $usuarioId
    ): array {
        foreach (['id', 'uuid', 'hash'] as $campo) {
            if (blank($simulacion[$campo] ?? null)) {
                throw ValidationException::withMessages([
                    'confirmacion' => 'La simulación no está completa. Regresa al paso anterior y vuelve a generarla.',
                ]);
            }
        }

        $registro = SimulacionCierreCiclo::query()
            ->lockForUpdate()
            ->whereKey((int) $simulacion['id'])
            ->where('uuid', (string) $simulacion['uuid'])
            ->first();

        if (! $registro || (int) $registro->usuario_id !== $usuarioId) {
            throw ValidationException::withMessages([
                'confirmacion' => 'La simulación no pertenece al usuario actual o ya no está disponible.',
            ]);
        }

        if ($registro->estado !== 'vigente') {
            throw ValidationException::withMessages([
                'confirmacion' => 'La simulación ya fue utilizada, cancelada o sustituida. Vuelve a generarla.',
            ]);
        }

        if ($registro->expira_at?->isPast()) {
            $registro->update(['estado' => 'expirada']);
            throw ValidationException::withMessages([
                'confirmacion' => 'La simulación expiró. Regresa a revisión para verificar nuevamente los datos.',
            ]);
        }

        if (! hash_equals((string) $registro->hash, (string) $simulacion['hash'])) {
            throw ValidationException::withMessages([
                'confirmacion' => 'La firma enviada no coincide con la simulación guardada.',
            ]);
        }

        $firmaAlmacenada = $this->firmarContenido([
            'version' => self::SIMULACION_VERSION,
            'generado_at' => $registro->generado_at?->toIso8601String(),
            'expira_at' => $registro->expira_at?->toIso8601String(),
            'contenido' => $registro->contenido ?? [],
        ]);
        if (! hash_equals((string) $registro->hash, $firmaAlmacenada)) {
            throw ValidationException::withMessages([
                'confirmacion' => 'La simulación guardada no supera la verificación de integridad.',
            ]);
        }

        $contenidoActual = $this->construirContenidoSimulacion(
            $configuracion,
            $decisiones,
            $candidatos,
            $generacion,
            $usuarioId,
        );
        $hashActual = $this->firmarContenido([
            'version' => self::SIMULACION_VERSION,
            'generado_at' => $registro->generado_at?->toIso8601String(),
            'expira_at' => $registro->expira_at?->toIso8601String(),
            'contenido' => $contenidoActual,
        ]);

        if (! hash_equals((string) $registro->hash, $hashActual)) {
            throw ValidationException::withMessages([
                'confirmacion' => 'Los alumnos, decisiones o destinos cambiaron después de la simulación. Regresa a revisión antes de confirmar.',
            ]);
        }

        return [
            'id' => $registro->id,
            'uuid' => $registro->uuid,
            'version' => self::SIMULACION_VERSION,
            'hash' => $hashActual,
            'generado_at' => $registro->generado_at?->toIso8601String(),
            'expira_at' => $registro->expira_at?->toIso8601String(),
            'contenido' => $contenidoActual,
            'resumen' => $registro->resumen ?? [],
        ];
    }

    private function snapshotHistorialParaFirma(InscripcionCiclo $historial): array
    {
        return Arr::only($historial->getAttributes(), [
            'id', 'inscripcion_id', 'ciclo_escolar_id', 'matricula', 'nivel_id', 'grado_id', 'generacion_id',
            'grupo_id', 'semestre_id', 'fecha_ingreso', 'fecha_salida', 'estado', 'estatus_ingreso',
            'estatus_actual_ciclo', 'resultado_final', 'promovido', 'cerrado_at', 'cerrado_por', 'motivo_cierre',
            'inscripcion_ciclo_destino_id', 'snapshot_ingreso', 'snapshot_cierre', 'origen', 'reconstruido',
            'nivel_confianza', 'updated_at',
        ]);
    }

    private function huellasDependenciasPorHistorial(Collection $idsHistoriales): array
    {
        $ids = $idsHistoriales->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $tablas = [
            'calificaciones',
            'ficha_descriptivas',
            'calificaciones_campos_formativos',
            'asistencias_finales_bachillerato',
            'decisiones_promocion_oficial',
            'lugares_preescolar',
            'movimientos_alumnos',
            'cambios_academicos',
            'proyecciones_continuidad',
        ];
        $huellas = [];

        foreach ($tablas as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
                if ($tabla !== 'proyecciones_continuidad'
                    || ! Schema::hasTable($tabla)
                    || ! Schema::hasColumn($tabla, 'inscripcion_ciclo_origen_id')) {
                    continue;
                }
            }

            $columna = $tabla === 'proyecciones_continuidad'
                ? 'inscripcion_ciclo_origen_id'
                : 'inscripcion_ciclo_id';
            $query = DB::table($tabla)
                ->select($columna)
                ->selectRaw('COUNT(*) AS total');
            if (Schema::hasColumn($tabla, 'updated_at')) {
                $query->selectRaw('MAX(updated_at) AS ultima_actualizacion');
            } else {
                $query->selectRaw('NULL AS ultima_actualizacion');
            }

            foreach ($query->whereIn($columna, $ids)->groupBy($columna)->get() as $fila) {
                $historialId = (int) $fila->{$columna};
                $huellas[$historialId][$tabla] = [
                    'total' => (int) $fila->total,
                    'ultima_actualizacion' => $fila->ultima_actualizacion,
                ];
            }
        }

        foreach ($huellas as &$dependencias) {
            ksort($dependencias);
        }
        unset($dependencias);

        return $huellas;
    }

    private function huellasDependenciasPorAlumno(Collection $idsAlumnos): array
    {
        $ids = $idsAlumnos->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $tablas = ['documentos_alumnos', 'matriculas_alumnos'];
        $huellas = [];

        foreach ($tablas as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'inscripcion_id')) {
                continue;
            }

            $query = DB::table($tabla)
                ->select('inscripcion_id')
                ->selectRaw('COUNT(*) AS total');
            if (Schema::hasColumn($tabla, 'updated_at')) {
                $query->selectRaw('MAX(updated_at) AS ultima_actualizacion');
            } else {
                $query->selectRaw('NULL AS ultima_actualizacion');
            }

            foreach ($query->whereIn('inscripcion_id', $ids)->groupBy('inscripcion_id')->get() as $fila) {
                $alumnoId = (int) $fila->inscripcion_id;
                $huellas[$alumnoId][$tabla] = [
                    'total' => (int) $fila->total,
                    'ultima_actualizacion' => $fila->ultima_actualizacion,
                ];
            }
        }

        foreach ($huellas as &$dependencias) {
            ksort($dependencias);
        }
        unset($dependencias);

        return $huellas;
    }

    private function construirRespaldoLogico(
        array $configuracion,
        array $decisiones,
        Collection $candidatos,
        Generacion $generacion,
        int $usuarioId
    ): array {
        $respaldos = [];

        foreach ($candidatos->sortBy('id') as $id => $fila) {
            $alumno = Inscripcion::withTrashed()->findOrFail((int) $fila['id']);
            $historial = InscripcionCiclo::query()->findOrFail((int) $fila['inscripcion_ciclo_id']);
            $respaldos[] = array_merge(
                $this->construirRespaldoDetalle($alumno, $historial),
                [
                    'decision' => $this->normalizarDecision(
                        $decisiones[$id] ?? $decisiones[(string) $id] ?? ['resultado' => 'pendiente']
                    ),
                    'procesable' => (bool) $fila['procesable'],
                ]
            );
        }

        return [
            'version' => 1,
            'generado_at' => now()->toIso8601String(),
            'generado_por' => $usuarioId,
            'configuracion' => $this->normalizarConfiguracionSimulacion($configuracion),
            'generacion' => $generacion->getAttributes(),
            'alumnos' => $respaldos,
        ];
    }

    private function construirRespaldoDetalle(Inscripcion $alumno, InscripcionCiclo $historial): array
    {
        $historial->loadMissing('asignaciones');

        return [
            'version' => 1,
            'inscripcion_id' => (int) $alumno->id,
            'inscripcion_ciclo_id' => (int) $historial->id,
            'inscripcion' => $alumno->getAttributes(),
            'inscripcion_ciclo' => $historial->getAttributes(),
            'asignaciones' => $historial->asignaciones
                ->map(fn ($asignacion): array => $asignacion->getAttributes())
                ->values()
                ->all(),
            'matriculas' => Schema::hasTable('matriculas_alumnos')
                ? DB::table('matriculas_alumnos')
                    ->where('inscripcion_id', $alumno->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn ($matricula): array => (array) $matricula)
                    ->values()
                    ->all()
                : [],
            'proyecciones_previas' => ProyeccionContinuidad::query()
                ->where('inscripcion_ciclo_origen_id', $historial->id)
                ->orderBy('id')
                ->get()
                ->map(fn (ProyeccionContinuidad $proyeccion): array => $proyeccion->getAttributes())
                ->values()
                ->all(),
        ];
    }

    private function normalizarConfiguracionSimulacion(array $configuracion): array
    {
        return [
            'nivel_id' => (int) ($configuracion['nivel_id'] ?? 0),
            'ciclo_origen_id' => (int) ($configuracion['ciclo_origen_id'] ?? 0),
            'generacion_id' => (int) ($configuracion['generacion_id'] ?? 0),
            'grupo_origen_id' => filled($configuracion['grupo_origen_id'] ?? null) ? (int) $configuracion['grupo_origen_id'] : null,
            'ciclo_destino_id' => filled($configuracion['ciclo_destino_id'] ?? null) ? (int) $configuracion['ciclo_destino_id'] : null,
            'nivel_destino_id' => filled($configuracion['nivel_destino_id'] ?? null) ? (int) $configuracion['nivel_destino_id'] : null,
            'grado_destino_id' => filled($configuracion['grado_destino_id'] ?? null) ? (int) $configuracion['grado_destino_id'] : null,
            'semestre_destino_id' => filled($configuracion['semestre_destino_id'] ?? null) ? (int) $configuracion['semestre_destino_id'] : null,
            'generacion_destino_id' => filled($configuracion['generacion_destino_id'] ?? null) ? (int) $configuracion['generacion_destino_id'] : null,
            'modo_proceso' => (string) ($configuracion['modo_proceso'] ?? ''),
            'tipo_proyeccion' => (string) ($configuracion['tipo_proyeccion'] ?? ''),
            'fecha_efectiva' => (string) ($configuracion['fecha_efectiva'] ?? ''),
            'motivo' => trim((string) ($configuracion['motivo'] ?? '')),
            'cerrar_generacion' => (bool) ($configuracion['cerrar_generacion'] ?? false),
        ];
    }

    private function normalizarDecision(array $decision): array
    {
        return [
            'resultado' => (string) ($decision['resultado'] ?? 'pendiente'),
            'motivo' => trim((string) ($decision['motivo'] ?? '')),
            'escuela_destino' => trim((string) ($decision['escuela_destino'] ?? '')),
            'grupo_destino_id' => filled($decision['grupo_destino_id'] ?? null) ? (int) $decision['grupo_destino_id'] : null,
            'matricula' => mb_strtoupper(trim((string) ($decision['matricula'] ?? ''))),
        ];
    }

    private function firmarContenido(array $contenido): string
    {
        $normalizado = $this->normalizarParaFirma($contenido);
        $json = json_encode(
            $normalizado,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );

        return hash_hmac('sha256', $json, (string) config('app.key'));
    }

    private function firmaValida(?array $contenido, ?string $firma): bool
    {
        if (! $contenido || blank($firma)) {
            return false;
        }

        return hash_equals((string) $firma, $this->firmarContenido($contenido));
    }

    private function normalizarParaFirma(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }

        if (array_is_list($valor)) {
            return array_map(fn (mixed $item): mixed => $this->normalizarParaFirma($item), $valor);
        }

        ksort($valor);
        foreach ($valor as $clave => $item) {
            $valor[$clave] = $this->normalizarParaFirma($item);
        }

        return $valor;
    }

    private function restaurarAsignacionesDesdeRespaldo(InscripcionCiclo $origen, array $asignaciones): void
    {
        $ids = collect($asignaciones)->pluck('id')->filter()->map(fn ($id): int => (int) $id)->values();
        $query = DB::table('inscripcion_ciclo_asignaciones')->where('inscripcion_ciclo_id', $origen->id);

        if ($ids->isEmpty()) {
            $query->delete();
        } else {
            $query->whereNotIn('id', $ids)->delete();
        }

        foreach ($asignaciones as $asignacion) {
            $id = (int) ($asignacion['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            DB::table('inscripcion_ciclo_asignaciones')->updateOrInsert(
                ['id' => $id],
                Arr::except($asignacion, ['id'])
            );
        }
    }

    private function restaurarMatriculasDesdeRespaldo(Inscripcion $alumno, array $matriculas): void
    {
        if (! Schema::hasTable('matriculas_alumnos')) {
            return;
        }

        $ids = collect($matriculas)->pluck('id')->filter()->map(fn ($id): int => (int) $id)->values();
        $query = DB::table('matriculas_alumnos')->where('inscripcion_id', $alumno->id);

        if ($ids->isEmpty()) {
            $query->delete();
        } else {
            $query->whereNotIn('id', $ids)->delete();
        }

        foreach ($matriculas as $matricula) {
            $id = (int) ($matricula['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            DB::table('matriculas_alumnos')->updateOrInsert(
                ['id' => $id],
                Arr::except($matricula, ['id'])
            );
        }
    }

    private function restaurarProyeccionesDesdeRespaldo(
        InscripcionCiclo $origen,
        array $proyecciones,
        int $procesoId,
        int $detalleId
    ): void {
        if (! Schema::hasTable('proyecciones_continuidad')) {
            return;
        }

        $idsPrevios = collect($proyecciones)->pluck('id')->filter()->map(fn ($id): int => (int) $id)->values();

        DB::table('proyecciones_continuidad')
            ->where('inscripcion_ciclo_origen_id', $origen->id)
            ->where(function ($query) use ($procesoId, $detalleId): void {
                $query->where('proceso_cierre_ciclo_id', $procesoId)
                    ->orWhere('proceso_cierre_ciclo_detalle_id', $detalleId);
            })
            ->when($idsPrevios->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $idsPrevios))
            ->delete();

        foreach ($proyecciones as $proyeccion) {
            $id = (int) ($proyeccion['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            DB::table('proyecciones_continuidad')->updateOrInsert(
                ['id' => $id],
                Arr::except($proyeccion, ['id'])
            );
        }
    }

    private function asegurarEsquemaCierreDisponible(): void
    {
        $requeridas = [
            'generaciones' => [
                'estado_cierre',
                'cierre_iniciado_at',
                'cierre_iniciado_por',
                'archivada_at',
                'archivada_por',
            ],
            'simulaciones_cierre_ciclo' => [
                'uuid',
                'usuario_id',
                'nivel_id',
                'ciclo_origen_id',
                'generacion_id',
                'estado',
                'contenido',
                'hash',
                'resumen',
                'generado_at',
                'expira_at',
                'consumida_at',
            ],
            'procesos_cierre_ciclo' => [
                'simulacion_cierre_ciclo_id',
                'ciclo_destino_id',
                'grupo_origen_id',
                'alcance',
                'fecha_efectiva',
                'vista_previa_hash',
                'simulacion',
                'simulado_at',
                'simulacion_expira_at',
                'estado_anterior_generacion',
                'respaldo_logico',
                'respaldo_hash',
                'respaldo_verificado_at',
                'integridad_estado',
                'confirmacion_at',
                'motivo_reversion',
                'reversion_resumen',
            ],
            'procesos_cierre_ciclo_detalles' => [
                'inscripcion_ciclo_origen_id',
                'inscripcion_ciclo_destino_id',
                'resultado_propuesto',
                'destino_propuesto',
                'respaldo_origen',
                'respaldo_hash',
                'respaldo_verificado_at',
                'revertido_at',
                'revertido_por',
                'motivo_reversion',
                'reversion_estado',
            ],
            'proyecciones_continuidad' => [
                'inscripcion_id',
                'inscripcion_ciclo_origen_id',
                'ciclo_destino_id',
                'nivel_destino_id',
                'generacion_destino_id',
                'grado_destino_id',
                'semestre_destino_clave',
                'estado',
                'tipo_proyeccion',
                'resultado_origen',
                'estatus_pendiente',
            ],
        ];

        $faltantes = [];

        foreach ($requeridas as $tabla => $columnas) {
            if (! Schema::hasTable($tabla)) {
                $faltantes[] = $tabla.' (tabla)';
                continue;
            }

            foreach ($columnas as $columna) {
                if (! Schema::hasColumn($tabla, $columna)) {
                    $faltantes[] = $tabla.'.'.$columna;
                }
            }
        }

        if ($faltantes === []) {
            return;
        }

        throw ValidationException::withMessages([
            'confirmacion' => 'La base de datos del módulo de cierre está desactualizada. '
                .'Ejecuta "php artisan migrate" y después "php artisan optimize:clear". '
                .'Elementos faltantes: '.implode(', ', $faltantes).'.',
        ]);
    }

    private function validarConfiguracion(array $configuracion, array $decisiones, Collection $candidatos): void
    {
        foreach (['nivel_id', 'ciclo_origen_id', 'generacion_id', 'fecha_efectiva', 'motivo'] as $campo) {
            if (blank($configuracion[$campo] ?? null)) {
                throw ValidationException::withMessages([$campo => 'Este dato es obligatorio.']);
            }
        }

        if (mb_strlen(trim((string) $configuracion['motivo'])) < 10) {
            throw ValidationException::withMessages([
                'motivo' => 'El motivo debe contener al menos 10 caracteres.',
            ]);
        }

        try {
            \Carbon\Carbon::parse((string) $configuracion['fecha_efectiva']);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'fecha_efectiva' => 'La fecha efectiva no es válida.',
            ]);
        }

        $modo = (string) ($configuracion['modo_proceso'] ?? '');
        if (! in_array($modo, self::MODOS, true)) {
            throw ValidationException::withMessages([
                'generacion_id' => 'No fue posible determinar si corresponde promoción de grado o cierre de nivel.',
            ]);
        }

        $procesables = $candidatos->where('procesable', true);
        $idsProcesables = $procesables->pluck('id')->map(fn ($id): int => (int) $id)->values();
        if ($idsProcesables->isEmpty()) {
            throw ValidationException::withMessages([
                'decisiones' => 'No hay alumnos pendientes de resolución en el contexto seleccionado.',
            ]);
        }

        $contextos = $procesables->map(fn (array $fila): string => (int) $fila['grado_id'].'-'.(int) ($fila['semestre_id'] ?? 0))->unique();
        if ($contextos->count() > 1) {
            throw ValidationException::withMessages([
                'grupo_origen_id' => 'La selección contiene alumnos en grados o semestres diferentes. Procesa un grupo o contexto académico a la vez.',
            ]);
        }

        $decisionesProcesables = $idsProcesables->mapWithKeys(function (int $id) use ($decisiones): array {
            return [$id => $decisiones[$id] ?? $decisiones[(string) $id] ?? ['resultado' => 'pendiente']];
        });
        if ($decisionesProcesables->contains(fn (array $decision): bool => ($decision['resultado'] ?? 'pendiente') === 'pendiente')) {
            throw ValidationException::withMessages([
                'decisiones' => 'Todos los alumnos procesables deben tener un resultado definitivo.',
            ]);
        }

        $permitidos = $this->resultadosPermitidos($modo);
        $invalida = $decisionesProcesables->first(function (array $decision) use ($permitidos): bool {
            return ! in_array((string) ($decision['resultado'] ?? ''), $permitidos, true);
        });
        if ($invalida) {
            throw ValidationException::withMessages([
                'decisiones' => 'Existe un resultado incompatible con el tipo de cierre detectado.',
            ]);
        }

        $cicloOrigen = CicloEscolar::query()->findOrFail((int) $configuracion['ciclo_origen_id']);
        $nivelOrigen = Nivel::query()->findOrFail((int) $configuracion['nivel_id']);
        $filaOrigen = $procesables->first();
        $sugerido = $this->destinoSugerido(
            $nivelOrigen,
            $cicloOrigen,
            (int) $filaOrigen['grado_id'],
            filled($filaOrigen['semestre_id']) ? (int) $filaOrigen['semestre_id'] : null,
            (int) $configuracion['generacion_id'],
        );
        $reglaCiclo = $this->reglaCicloDestino(
            $nivelOrigen,
            $cicloOrigen,
            $modo,
            filled($filaOrigen['semestre_id']) ? (int) $filaOrigen['semestre_id'] : null,
            filled($sugerido['semestre_destino_id'] ?? null) ? (int) $sugerido['semestre_destino_id'] : null,
        );
        $requiereDestino = $decisionesProcesables->contains(
            fn (array $decision): bool => in_array($decision['resultado'] ?? null, ['continuidad_interna', 'no_promovido'], true)
        );
        if ($requiereDestino && blank($configuracion['ciclo_destino_id'] ?? null)) {
            $mensaje = $reglaCiclo['tipo'] === 'mismo_ciclo'
                ? 'El cambio semestral debe conservar el mismo ciclo escolar de origen.'
                : "Primero crea o selecciona el ciclo escolar {$reglaCiclo['etiqueta']} para la proyección.";

            throw ValidationException::withMessages([
                'ciclo_destino_id' => $mensaje,
            ]);
        }

        if ($requiereDestino || filled($configuracion['ciclo_destino_id'] ?? null)) {
            if ($reglaCiclo['tipo'] === 'invalido') {
                throw ValidationException::withMessages([
                    'semestre_destino_id' => 'No existe un semestre inmediato válido dentro del plan de Bachillerato de 1.º a 6.º.',
                ]);
            }

            if (! $reglaCiclo['ciclo_id']) {
                throw ValidationException::withMessages([
                    'ciclo_destino_id' => "Primero crea el ciclo escolar {$reglaCiclo['etiqueta']} para continuar.",
                ]);
            }

            if ((int) ($configuracion['ciclo_destino_id'] ?? 0) !== (int) $reglaCiclo['ciclo_id']) {
                $mensaje = $reglaCiclo['tipo'] === 'mismo_ciclo'
                    ? 'Para este cambio de semestre el ciclo destino debe ser el mismo ciclo de origen.'
                    : 'El ciclo destino debe ser el consecutivo inmediato del ciclo origen.';

                throw ValidationException::withMessages([
                    'ciclo_destino_id' => $mensaje,
                ]);
            }
        }

        $hayContinuidad = $decisionesProcesables->contains(
            fn (array $decision): bool => ($decision['resultado'] ?? null) === 'continuidad_interna'
        );
        if (! $hayContinuidad) {
            return;
        }

        foreach (['nivel_destino_id', 'grado_destino_id', 'generacion_destino_id'] as $campo) {
            if ((int) ($configuracion[$campo] ?? 0) !== (int) ($sugerido[$campo] ?? 0)) {
                throw ValidationException::withMessages([
                    $campo => 'El destino no corresponde al siguiente grado, semestre o nivel permitido para el contexto de origen.',
                ]);
            }
        }
        if ((int) ($configuracion['semestre_destino_id'] ?? 0) !== (int) ($sugerido['semestre_destino_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'semestre_destino_id' => 'El semestre destino debe ser el inmediato siguiente.',
            ]);
        }
    }

    private function crearProyeccionContinuidad(
        ProcesoCierreCiclo $proceso,
        ProcesoCierreCicloDetalle $detalle,
        InscripcionCiclo $origen,
        Inscripcion $alumno,
        array $destino,
        string $motivo,
        string $fecha,
        int $usuarioId,
        ?string $tipoProyeccion = null,
        ?string $resultadoOrigen = null
    ): ProyeccionContinuidad {
        $semestreDestinoClave = filled($destino['semestre_id'] ?? null)
            ? (int) $destino['semestre_id']
            : 0;

        return ProyeccionContinuidad::query()->updateOrCreate(
            [
                'inscripcion_id' => $alumno->id,
                'ciclo_destino_id' => (int) $destino['ciclo_escolar_id'],
                'nivel_destino_id' => (int) $destino['nivel_id'],
                'grado_destino_id' => (int) $destino['grado_id'],
                'semestre_destino_clave' => $semestreDestinoClave,
            ],
            [
                'inscripcion_ciclo_origen_id' => $origen->id,
                'proceso_cierre_ciclo_id' => $proceso->id,
                'proceso_cierre_ciclo_detalle_id' => $detalle->id,
                'generacion_destino_id' => (int) $destino['generacion_id'],
                'semestre_destino_id' => filled($destino['semestre_id'] ?? null) ? (int) $destino['semestre_id'] : null,
                'grupo_destino_id' => filled($destino['grupo_id'] ?? null) ? (int) $destino['grupo_id'] : null,
                'tipo_proyeccion' => $tipoProyeccion,
                'resultado_origen' => $resultadoOrigen,
                'estatus_pendiente' => (string) ($alumno->estatus ?: null),
                'matricula_sugerida' => filled($destino['matricula'] ?? null) ? (string) $destino['matricula'] : null,
                'estado' => 'pendiente',
                'fecha_proyeccion' => $fecha,
                'motivo' => $motivo,
                'snapshot_origen' => $this->snapshotAlumno($alumno),
                'proyectada_por' => $usuarioId,
                'confirmada_at' => null,
                'confirmada_por' => null,
                'inscripcion_ciclo_destino_id' => null,
                'snapshot_confirmacion' => null,
                'cancelada_at' => null,
                'cancelada_por' => null,
                'motivo_cancelacion' => null,
                'snapshot_cancelacion' => null,
                'revertida_at' => null,
                'revertida_por' => null,
                'fecha_reversion' => null,
                'tipo_reversion' => null,
                'motivo_reversion' => null,
                'snapshot_reversion' => null,
            ]
        );
    }

    private function confirmarProyeccionBloqueada(
        ProyeccionContinuidad $proyeccion,
        array $datos,
        string $motivo,
        string $fecha,
        int $usuarioId
    ): ProyeccionContinuidad {
        if ($proyeccion->estado !== 'pendiente') {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => "La proyección #{$proyeccion->id} ya fue {$proyeccion->estado}.",
            ]);
        }

        $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($proyeccion->inscripcion_id);
        if ($alumno->trashed()) {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => "El expediente de {$alumno->nombre} está eliminado. Restáuralo antes de confirmar la continuidad.",
            ]);
        }
        $estatusEsperados = array_values(array_unique(array_filter([
            $proyeccion->estatus_pendiente,
            'egresado',
            'pendiente_reinscripcion',
            $proyeccion->cancelada_at ? 'no_reinscrito' : null,
        ])));
        if (! in_array((string) ($alumno->estatus ?? ''), $estatusEsperados, true)) {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => "{$alumno->nombre} ya cambió de estatus. Revisa su trayectoria antes de confirmar.",
            ]);
        }
        if ((int) $alumno->ciclo_escolar_id !== (int) $proyeccion->inscripcionCicloOrigen?->ciclo_escolar_id) {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => "{$alumno->nombre} ya tiene otra ubicación académica vigente.",
            ]);
        }

        $grupoId = (int) ($datos['grupo_destino_id'] ?? $proyeccion->grupo_destino_id ?? 0);
        $destino = [
            'ciclo_escolar_id' => (int) $proyeccion->ciclo_destino_id,
            'nivel_id' => (int) $proyeccion->nivel_destino_id,
            'generacion_id' => (int) $proyeccion->generacion_destino_id,
            'grado_id' => (int) $proyeccion->grado_destino_id,
            'semestre_id' => filled($proyeccion->semestre_destino_id) ? (int) $proyeccion->semestre_destino_id : null,
            'grupo_id' => $grupoId,
            'matricula' => filled($datos['matricula'] ?? null)
                ? mb_strtoupper(trim((string) $datos['matricula']))
                : (filled($proyeccion->matricula_sugerida)
                    ? (string) $proyeccion->matricula_sugerida
                    : $this->trayectorias->generarMatricula(
                        $alumno,
                        (int) $proyeccion->nivel_destino_id,
                        (int) $proyeccion->generacion_destino_id,
                    )),
        ];

        $this->validarDestinoGrupo($destino);
        $this->asegurarDestinoDisponible(
            $alumno,
            (int) $destino['ciclo_escolar_id'],
            trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"),
            (int) $proyeccion->inscripcion_ciclo_origen_id,
        );

        $resultadoOrigen = (string) ($proyeccion->resultado_origen ?: (
            (int) $proyeccion->nivel_destino_id === (int) $proyeccion->inscripcionCicloOrigen?->nivel_id
                ? 'promovido_grado'
                : 'promovido_nivel'
        ));
        $actualizado = $proyeccion->tipo_proyeccion === 'repeticion'
            ? $this->gestionAcademica->continuarNoPromovido(
                $alumno,
                $destino,
                $motivo,
                $usuarioId,
                $fecha,
            )
            : $this->gestionAcademica->promoverAlumno(
                $alumno,
                $destino,
                $motivo,
                $usuarioId,
                $fecha,
                $resultadoOrigen,
            );
        $destinoCiclo = $this->historialCiclos->cicloActual($actualizado, (int) $destino['ciclo_escolar_id']);
        if (! $destinoCiclo) {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => 'No fue posible crear el historial del ciclo destino.',
            ]);
        }

        $proyeccion->update([
            'grupo_destino_id' => $grupoId,
            'matricula_sugerida' => $destino['matricula'],
            'estado' => 'confirmada',
            'confirmada_at' => now(),
            'confirmada_por' => $usuarioId,
            'inscripcion_ciclo_destino_id' => $destinoCiclo->id,
            'snapshot_confirmacion' => $this->snapshotAlumno($actualizado->fresh()),
            'cancelada_at' => null,
            'cancelada_por' => null,
            'motivo_cancelacion' => null,
            'snapshot_cancelacion' => null,
            'revertida_at' => null,
            'revertida_por' => null,
            'fecha_reversion' => null,
            'tipo_reversion' => null,
            'motivo_reversion' => null,
            'snapshot_reversion' => null,
        ]);

        $proyeccion->detalleCierre?->update([
            'inscripcion_ciclo_destino_id' => $destinoCiclo->id,
            'observacion' => 'Proyección confirmada. El alumno quedó activo en el ciclo y ubicación destino.',
            'estado_nuevo' => $this->snapshotAlumno($actualizado->fresh()),
        ]);

        CambioAcademico::query()->create([
            'inscripcion_id' => $alumno->id,
            'inscripcion_ciclo_id' => $destinoCiclo->id,
            'generacion_id' => $destinoCiclo->generacion_id,
            'tipo' => 'confirmacion_proyeccion_continuidad',
            'motivo' => $motivo,
            'datos_anteriores' => ['proyeccion' => 'pendiente', 'estatus_alumno' => $proyeccion->estatus_pendiente],
            'datos_nuevos' => [
                'proyeccion' => 'confirmada',
                'estatus_alumno' => $actualizado->estatus,
                'inscripcion_ciclo_destino_id' => $destinoCiclo->id,
            ],
            'realizado_por' => $usuarioId,
            'realizado_at' => now(),
        ]);

        return $proyeccion->fresh();
    }

    private function asegurarDestinoDisponible(
        Inscripcion $alumno,
        int $cicloDestinoId,
        string $nombre,
        ?int $inscripcionCicloOrigenId = null,
    ): void
    {
        $existente = InscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('ciclo_escolar_id', $cicloDestinoId)
            ->lockForUpdate()
            ->first();

        if (! $existente || ($inscripcionCicloOrigenId && (int) $existente->id === $inscripcionCicloOrigenId)) {
            return;
        }

        throw ValidationException::withMessages([
            "decisiones.{$alumno->id}.resultado" => "{$nombre} ya tiene un registro histórico en el ciclo destino. Revisa su trayectoria o usa el flujo de reingreso/reincorporación antes de procesarlo.",
        ]);
    }

    private function cerrarOrigenSinActivarDestino(
        Inscripcion $alumno,
        InscripcionCiclo $origen,
        string $resultadoOrigen,
        string $estatusPendiente,
        string $motivo,
        int $usuarioId,
        string $fecha,
        bool $promovido
    ): Inscripcion {
        $actualizado = $this->gestionAcademica->cambiarEstatus(
            $alumno,
            $estatusPendiente,
            $motivo,
            $usuarioId,
            $fecha,
        );
        $this->historialCiclos->cerrarCiclo(
            $origen->fresh(),
            $resultadoOrigen,
            $motivo,
            $usuarioId,
            $fecha,
            $promovido,
        );

        return $actualizado->fresh();
    }

    private function destinoContinuidad(array $configuracion, array $decision, Inscripcion $alumno): array
    {
        $destino = [
            'ciclo_escolar_id' => (int) ($configuracion['ciclo_destino_id'] ?? 0),
            'nivel_id' => (int) ($configuracion['nivel_destino_id'] ?? 0),
            'generacion_id' => (int) ($configuracion['generacion_destino_id'] ?? 0),
            'grado_id' => (int) ($configuracion['grado_destino_id'] ?? 0),
            'semestre_id' => filled($configuracion['semestre_destino_id'] ?? null)
                ? (int) $configuracion['semestre_destino_id']
                : null,
            'grupo_id' => filled($decision['grupo_destino_id'] ?? null)
                ? (int) $decision['grupo_destino_id']
                : null,
        ];

        $destino['matricula'] = filled($decision['matricula'] ?? null)
            ? mb_strtoupper(trim((string) $decision['matricula']))
            : $this->trayectorias->generarMatricula($alumno, $destino['nivel_id'], $destino['generacion_id']);

        return $destino;
    }

    private function destinoRepeticion(array $configuracion, array $decision, array $fila, Inscripcion $alumno): array
    {
        $ciclo = CicloEscolar::query()->find((int) ($configuracion['ciclo_destino_id'] ?? 0));
        $nivel = Nivel::query()->find((int) $configuracion['nivel_id']);
        $grado = Grado::query()->find((int) $fila['grado_id']);
        $semestre = filled($fila['semestre_id']) ? Semestre::query()->find((int) $fila['semestre_id']) : null;
        $grupo = filled($decision['grupo_destino_id'] ?? null)
            ? Grupo::query()->find((int) $decision['grupo_destino_id'])
            : null;
        $generacion = $grupo?->generacion
            ?: (($ciclo && $nivel && $grado)
                ? $this->asignacionEscolar->resolverGeneracion($ciclo, $nivel, $grado, $semestre)
                : null);

        return [
            'ciclo_escolar_id' => (int) ($configuracion['ciclo_destino_id'] ?? 0),
            'nivel_id' => (int) $configuracion['nivel_id'],
            'generacion_id' => (int) ($generacion?->id ?? 0),
            'grado_id' => (int) $fila['grado_id'],
            'semestre_id' => filled($fila['semestre_id']) ? (int) $fila['semestre_id'] : null,
            'grupo_id' => $grupo?->id,
            'matricula' => (string) $alumno->matricula,
        ];
    }

    private function validarDestinoProyeccion(array $destino): void
    {
        if (collect(Arr::only($destino, ['ciclo_escolar_id', 'nivel_id', 'generacion_id', 'grado_id']))
            ->contains(fn ($valor) => blank($valor))) {
            throw ValidationException::withMessages([
                'destino' => 'Falta configurar ciclo, nivel, generación o grado destino para la proyección.',
            ]);
        }

        $generacion = Generacion::query()->findOrFail((int) $destino['generacion_id']);
        if (! $generacion->status || (int) $generacion->nivel_id !== (int) $destino['nivel_id']) {
            throw ValidationException::withMessages([
                'generacion_destino_id' => 'La generación destino debe estar activa y pertenecer al nivel seleccionado.',
            ]);
        }

        $grado = Grado::query()->findOrFail((int) $destino['grado_id']);
        if ((int) $grado->nivel_id !== (int) $destino['nivel_id']) {
            throw ValidationException::withMessages([
                'grado_destino_id' => 'El grado destino no pertenece al nivel seleccionado.',
            ]);
        }

        if (filled($destino['grupo_id'] ?? null)) {
            $this->validarDestinoGrupo($destino);
        }
    }

    private function validarDestinoGrupo(array $destino, bool $permitirGeneracionExcepcional = false): Grupo
    {
        if (collect(Arr::only($destino, ['ciclo_escolar_id', 'nivel_id', 'generacion_id', 'grado_id', 'grupo_id']))->contains(fn ($valor) => blank($valor))) {
            throw ValidationException::withMessages([
                'destino' => 'Falta configurar ciclo, nivel, generación, grado o grupo destino.',
            ]);
        }

        return $this->asignacionEscolar->validarAsignacion($destino, $permitirGeneracionExcepcional);
    }

    private function destinoCompleto(array $destino): bool
    {
        return filled($destino['ciclo_destino_id'] ?? null)
            && filled($destino['nivel_destino_id'] ?? null)
            && filled($destino['generacion_destino_id'] ?? null)
            && filled($destino['grado_destino_id'] ?? null);
    }

    private function advertenciasAlumno(
        InscripcionCiclo $registro,
        bool $esGradoFinal,
        bool $procesable,
        bool $soloProyeccionHistorica = false,
        ?string $estadoProyeccion = null,
        bool $tieneCicloPosterior = false,
    ): array {
        $advertencias = [];

        if ($soloProyeccionHistorica) {
            $advertencias[] = 'Egresado histórico: puede generar una proyección provisional sin modificar su egreso';
        } elseif (! $procesable) {
            if ($estadoProyeccion === 'pendiente') {
                $advertencias[] = 'Ya tiene una proyección pendiente de confirmación';
            } elseif ($estadoProyeccion === 'confirmada') {
                $advertencias[] = 'La continuidad al siguiente nivel ya fue confirmada';
            } elseif ($tieneCicloPosterior) {
                $advertencias[] = 'Ya tiene un registro académico en un ciclo posterior';
            } else {
                $advertencias[] = $registro->resultado_final
                    ? 'Ya tiene resultado histórico: '.str_replace('_', ' ', $registro->resultado_final)
                    : 'No pertenece actualmente al ciclo origen';
            }
        }

        $tieneRegistroAcademico = false;
        foreach (['calificaciones', 'fichasDescriptivas', 'calificacionesCamposFormativos'] as $relacion) {
            if ($registro->{$relacion}()->exists()) {
                $tieneRegistroAcademico = true;
                break;
            }
        }
        if (! $tieneRegistroAcademico) {
            $advertencias[] = 'Sin registros académicos vinculados al ciclo';
        }

        if (Schema::hasTable('documentos_alumnos')) {
            $tieneDocumento = DB::table('documentos_alumnos')
                ->where('inscripcion_id', $registro->inscripcion_id)
                ->where('es_actual', true)
                ->exists();
            if (! $tieneDocumento) {
                $advertencias[] = 'Sin documentos actuales';
            }
        }

        return $advertencias;
    }

    private function motivoIndividual(array $configuracion, array $decision, array $fila): string
    {
        $partes = [trim((string) $configuracion['motivo'])];
        if (filled($decision['motivo'] ?? null)) {
            $partes[] = trim((string) $decision['motivo']);
        }
        if (($decision['resultado'] ?? null) === 'traslado' && filled($decision['escuela_destino'] ?? null)) {
            $partes[] = 'Escuela destino: '.trim((string) $decision['escuela_destino']).'.';
        }
        if (($decision['resultado'] ?? null) === 'baja_definitiva' && $fila['es_grado_final']) {
            $partes[] = 'Advertencia confirmada: el alumno estaba en grado final; se registró baja por decisión administrativa y no egreso.';
        }

        return trim(implode(' ', array_filter($partes)));
    }

    private function textoResultado(string $resultado, string $modo): string
    {
        return match ($resultado) {
            'continuidad_interna' => $modo === 'promocion_grado'
                ? 'El grado o semestre de origen quedó concluido como promovido y se creó una proyección provisional al siguiente grado o semestre.'
                : 'El alumno egresó del nivel de origen y quedó proyectado provisionalmente al siguiente nivel.',
            'no_reinscrito' => 'El alumno acreditó el grado o semestre, pero no continuará en la institución. No se registró como baja.',
            'egresado' => 'Egreso aplicado sin convertirlo en baja.',
            'traslado' => 'Traslado aplicado y expediente histórico conservado.',
            'baja_definitiva' => 'Baja definitiva aplicada con fecha y motivo.',
            'no_promovido' => 'Ciclo cerrado como no promovido y se creó una proyección provisional para repetir el grado o semestre.',
            default => ucfirst(str_replace('_', ' ', $resultado)),
        };
    }

    private function snapshotAlumno(Inscripcion $alumno): array
    {
        return Arr::only($alumno->getAttributes(), [
            'matricula', 'ciclo_escolar_id', 'ciclo_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id',
            'estatus', 'activo', 'fecha_estatus', 'motivo_estatus', 'fecha_baja', 'motivo_baja', 'observaciones_baja',
            'indicador_reingreso', 'tipo_ultimo_ingreso', 'fecha_ultimo_ingreso', 'documentacion_reingreso_pendiente',
            'usuario_acceso_activo', 'deleted_at',
        ]);
    }

    private function snapshotGeneracion(Generacion $generacion): array
    {
        return Arr::only($generacion->getAttributes(), [
            'status', 'estado_cierre', 'cerrada_at', 'cerrada_por', 'motivo_desactivacion', 'observaciones',
            'cierre_iniciado_at', 'cierre_iniciado_por', 'reactivada_at', 'reactivada_por', 'archivada_at', 'archivada_por',
        ]);
    }
}
