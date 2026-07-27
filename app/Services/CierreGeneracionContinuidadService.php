<?php

namespace App\Services;

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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CierreGeneracionContinuidadService
{
    public const RESULTADOS = [
        'pendiente',
        'continuidad_interna',
        'egresado',
        'traslado',
        'baja_definitiva',
        'no_promovido',
    ];

    private const NIVELES_SIGUIENTES = [
        'preescolar' => 'primaria',
        'primaria' => 'secundaria',
        'secundaria' => 'bachillerato',
    ];

    private const ESTATUS_PROCESABLES = ['activo', 'reingreso', 'no_promovido'];

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

    public function destinoSugerido(Nivel $nivelOrigen, CicloEscolar $cicloOrigen): array
    {
        $nivelDestino = $this->nivelDestinoSugerido($nivelOrigen);
        $cicloDestino = $this->cicloDestinoSugerido($cicloOrigen);

        if (! $nivelDestino || ! $cicloDestino) {
            return [
                'ciclo_destino_id' => $cicloDestino?->id,
                'nivel_destino_id' => $nivelDestino?->id,
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

                $procesableActivo = $registro->estado === 'en_curso'
                    && in_array($estatusActual, self::ESTATUS_PROCESABLES, true)
                    && (int) $alumno->ciclo_escolar_id === (int) $registro->ciclo_escolar_id;

                $proyeccionVigente = $proyeccionesVigentes->get($registro->id);
                $inicioOrigen = (int) ($registro->cicloEscolar?->inicio_anio ?? 0);
                $tieneCicloPosterior = collect($otrosCiclos->get($alumno->id, collect()))
                    ->contains(fn (InscripcionCiclo $otro): bool =>
                        (int) ($otro->cicloEscolar?->inicio_anio ?? 0) > $inicioOrigen
                    );

                // Compatibilidad con egresos realizados antes de existir el módulo
                // de proyecciones. El resultado histórico no se reabre ni se altera;
                // únicamente se permite crear la proyección provisional faltante.
                $soloProyeccionHistorica = $registro->estado === 'cerrado'
                    && $resultadoExistente === 'egresado'
                    && $estatusActual === 'egresado'
                    && $esGradoFinal
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
        int $generacionId,
        int $gradoId,
        ?int $semestreId
    ): Collection {
        return $this->asignacionEscolar->gruposCompatibles(
            $cicloDestinoId,
            $nivelId,
            $generacionId,
            $gradoId,
            $semestreId,
        );
    }

    public function ejecutar(array $configuracion, array $decisiones, int $usuarioId): ProcesoCierreCiclo
    {
        $this->asegurarEsquemaCierreDisponible();

        return DB::transaction(function () use ($configuracion, $decisiones, $usuarioId): ProcesoCierreCiclo {
            $generacion = Generacion::query()->lockForUpdate()->findOrFail((int) $configuracion['generacion_id']);
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

            $estadoGeneracionAnterior = $this->snapshotGeneracion($generacion);
            $generacion->update([
                'estado_cierre' => 'en_proceso',
                'cierre_iniciado_at' => now(),
                'cierre_iniciado_por' => $usuarioId,
            ]);

            $hash = hash('sha256', json_encode([
                'configuracion' => $configuracion,
                'decisiones' => collect($decisiones)->sortKeys()->all(),
                'candidatos' => $candidatos->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $proceso = ProcesoCierreCiclo::query()->create([
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
                'vista_previa_hash' => $hash,
                'estado_anterior_generacion' => $estadoGeneracionAnterior,
                'resumen' => [
                    'configuracion' => $configuracion,
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
                        'estado_nuevo' => $antes,
                    ]);
                    $sinCambio++;
                    continue;
                }

                if (($fila['solo_proyeccion_historica'] ?? false) && $resultado !== 'continuidad_interna') {
                    throw ValidationException::withMessages([
                        "decisiones.{$id}.resultado" => "{$fila['nombre']} ya es egresado histórico. Solo puedes crear su proyección provisional al siguiente nivel.",
                    ]);
                }

                $motivoIndividual = $this->motivoIndividual($configuracion, $decision, $fila);
                $destinoCiclo = null;
                $datosProyeccion = null;

                if ($resultado === 'continuidad_interna') {
                    if (! $fila['es_grado_final']) {
                        throw ValidationException::withMessages([
                            "decisiones.{$id}.resultado" => "{$fila['nombre']} no está en el último grado o semestre del nivel.",
                        ]);
                    }

                    $destino = $this->destinoContinuidad($configuracion, $decision, $alumno);
                    $this->validarDestinoProyeccion($destino);
                    $this->asegurarDestinoDisponible($alumno, (int) $destino['ciclo_escolar_id'], $fila['nombre']);

                    // La continuidad es una proyección, no una inscripción activa.
                    // Si el alumno ya había sido egresado con el flujo anterior, se
                    // conserva intacto ese resultado y únicamente se crea la proyección.
                    if ($fila['solo_proyeccion_historica'] ?? false) {
                        $actualizado = $alumno;
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
                    $this->validarDestinoGrupo($destino, true);
                    $this->asegurarDestinoDisponible($alumno, (int) $destino['ciclo_escolar_id'], $fila['nombre']);
                    $actualizado = $this->gestionAcademica->continuarNoPromovido(
                        $alumno,
                        $destino,
                        $motivoIndividual,
                        $usuarioId,
                        $configuracion['fecha_efectiva'],
                    );
                    $destinoCiclo = $this->historialCiclos->cicloActual($actualizado, (int) $destino['ciclo_escolar_id']);
                } elseif ($resultado === 'egresado') {
                    if (! $fila['es_grado_final']) {
                        throw ValidationException::withMessages([
                            "decisiones.{$id}.resultado" => "{$fila['nombre']} no está en el último grado o semestre; usa baja, traslado o corrige su ubicación.",
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
                        ? 'Se creó una proyección provisional para un egresado histórico sin modificar su resultado de origen.'
                        : $this->textoResultado($resultado),
                    'estado_anterior' => $antes,
                    'estado_nuevo' => $despues,
                ]);

                if ($resultado === 'continuidad_interna' && $datosProyeccion) {
                    $this->crearProyeccionContinuidad(
                        $proceso,
                        $detalle,
                        $origen,
                        $actualizado->fresh(),
                        $datosProyeccion,
                        $motivoIndividual,
                        $configuracion['fecha_efectiva'],
                        $usuarioId,
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

            $cerrarGeneracion = (bool) ($configuracion['cerrar_generacion'] ?? false);
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
                    'estado_cierre' => $pendientesGeneracion > 0 ? 'activa' : 'cerrada',
                    'cierre_iniciado_at' => null,
                    'cierre_iniciado_por' => null,
                ]);
            }

            CambioAcademico::query()->create([
                'generacion_id' => $generacion->id,
                'tipo' => 'cierre_nivel_continuidad',
                'motivo' => trim((string) $configuracion['motivo']),
                'datos_anteriores' => $estadoGeneracionAnterior,
                'datos_nuevos' => array_merge($this->snapshotGeneracion($generacion->fresh()), [
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
                'resumen' => array_merge($proceso->resumen ?? [], [
                    'procesados' => $procesados,
                    'sin_cambio' => $sinCambio,
                    'pendientes_generacion' => $pendientesGeneracion,
                    'generacion_despues' => $this->snapshotGeneracion($generacion->fresh()),
                ]),
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
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 0 WHEN 'confirmada' THEN 1 ELSE 2 END")
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
                $proyeccion = ProyeccionContinuidad::query()->lockForUpdate()->findOrFail($id);
                if ($proyeccion->estado !== 'pendiente') {
                    throw ValidationException::withMessages([
                        'seleccion_proyecciones' => "La proyección #{$proyeccion->id} ya fue {$proyeccion->estado}.",
                    ]);
                }

                $alumno = Inscripcion::withTrashed()->findOrFail($proyeccion->inscripcion_id);
                if (($alumno->estatus ?? '') !== 'egresado') {
                    throw ValidationException::withMessages([
                        'seleccion_proyecciones' => "{$alumno->nombre} ya no está únicamente como egresado. Revisa su trayectoria antes de cancelar.",
                    ]);
                }

                $proyeccion->update([
                    'estado' => 'cancelada',
                    'cancelada_at' => now(),
                    'cancelada_por' => $usuarioId,
                    'motivo_cancelacion' => $motivo,
                    'snapshot_cancelacion' => $this->snapshotAlumno($alumno),
                ]);

                $proyeccion->detalleCierre?->update([
                    'observacion' => 'Proyección cancelada. El alumno permanece como egresado del nivel de origen y no causó baja en el nivel destino. Motivo: '.$motivo,
                ]);

                CambioAcademico::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'inscripcion_ciclo_id' => $proyeccion->inscripcion_ciclo_origen_id,
                    'generacion_id' => $proyeccion->inscripcionCicloOrigen?->generacion_id,
                    'tipo' => 'cancelacion_proyeccion_continuidad',
                    'motivo' => $motivo,
                    'datos_anteriores' => ['proyeccion' => 'pendiente'],
                    'datos_nuevos' => ['proyeccion' => 'cancelada', 'estatus_alumno' => $alumno->estatus],
                    'realizado_por' => $usuarioId,
                    'realizado_at' => now(),
                ]);

                $canceladas++;
            }

            return $canceladas;
        });
    }

    public function bloqueosReversion(ProcesoCierreCiclo $proceso): Collection
    {
        $proceso->loadMissing('detalles.inscripcionCicloDestino');
        $bloqueos = collect();

        if ($proceso->estado !== 'completado' || $proceso->revertido_at) {
            return collect(['El proceso ya fue revertido o no está completado.']);
        }

        foreach ($proceso->detalles as $detalle) {
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
            $detalles = $proceso->detalles()->with(['inscripcionCicloOrigen', 'inscripcionCicloDestino'])->orderByDesc('id')->get();

            foreach ($detalles as $detalle) {
                if ($detalle->resultado === 'sin_cambio' || $detalle->revertido_at) {
                    continue;
                }

                $alumno = Inscripcion::withTrashed()->lockForUpdate()->findOrFail($detalle->inscripcion_id);
                $actual = $this->snapshotAlumno($alumno);
                $anterior = $detalle->estado_anterior ?: [];
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

                $alumno->forceFill(Arr::only($anterior, [
                    'matricula', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id',
                    'ciclo_id', 'estatus', 'activo', 'fecha_estatus', 'motivo_estatus', 'fecha_baja', 'motivo_baja',
                    'observaciones_baja', 'indicador_reingreso', 'tipo_ultimo_ingreso', 'fecha_ultimo_ingreso',
                    'documentacion_reingreso_pendiente', 'usuario_acceso_activo',
                ]))->save();

                if ($alumno->trashed() && empty($anterior['deleted_at'])) {
                    $alumno->restore();
                }

                if ($origen) {
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

                $this->matriculas->asegurarVigente($alumno->fresh(), 'reversion_cierre_generacion', $usuarioId, now()->toDateString());
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
                    'revertido_at' => now(),
                    'revertido_por' => $usuarioId,
                    'motivo_reversion' => $motivo,
                ]);
            }

            $generacion = Generacion::query()->lockForUpdate()->find($proceso->generacion_id);
            if ($generacion && $proceso->estado_anterior_generacion) {
                $generacion->forceFill(Arr::only($proceso->estado_anterior_generacion, [
                    'status', 'estado_cierre', 'cerrada_at', 'cerrada_por', 'motivo_desactivacion', 'observaciones',
                    'cierre_iniciado_at', 'cierre_iniciado_por', 'reactivada_at', 'reactivada_por', 'archivada_at', 'archivada_por',
                ]))->save();
            }

            $proceso->update([
                'estado' => 'revertido',
                'revertido_at' => now(),
                'revertido_por' => $usuarioId,
                'motivo_reversion' => $motivo,
            ]);

            return $proceso->fresh(['detalles.inscripcion', 'generacion']);
        });
    }

    private function asegurarEsquemaCierreDisponible(): void
    {
        $requeridas = [
            'procesos_cierre_ciclo' => [
                'ciclo_destino_id',
                'grupo_origen_id',
                'alcance',
                'fecha_efectiva',
                'vista_previa_hash',
                'estado_anterior_generacion',
                'confirmacion_at',
                'motivo_reversion',
            ],
            'procesos_cierre_ciclo_detalles' => [
                'inscripcion_ciclo_origen_id',
                'inscripcion_ciclo_destino_id',
                'resultado_propuesto',
                'destino_propuesto',
                'revertido_at',
                'revertido_por',
                'motivo_reversion',
            ],
            'proyecciones_continuidad' => [
                'inscripcion_id',
                'inscripcion_ciclo_origen_id',
                'ciclo_destino_id',
                'nivel_destino_id',
                'generacion_destino_id',
                'grado_destino_id',
                'estado',
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
            throw ValidationException::withMessages(['motivo' => 'El motivo debe contener al menos 10 caracteres.']);
        }

        $idsProcesables = $candidatos
            ->filter(fn (array $candidato): bool => (bool) ($candidato['procesable'] ?? false))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($idsProcesables->isEmpty()) {
            throw ValidationException::withMessages([
                'decisiones' => 'No hay alumnos pendientes de resolución en el contexto seleccionado.',
            ]);
        }

        $decisionesProcesables = $idsProcesables->mapWithKeys(function (int $id) use ($decisiones): array {
            return [$id => $decisiones[$id] ?? $decisiones[(string) $id] ?? ['resultado' => 'pendiente']];
        });

        $pendientes = $decisionesProcesables->filter(
            fn (array $decision): bool => ($decision['resultado'] ?? 'pendiente') === 'pendiente'
        );
        if ($pendientes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'decisiones' => 'Todos los alumnos procesables deben tener un resultado definitivo.',
            ]);
        }

        $resultadosInvalidos = $decisionesProcesables->filter(
            fn (array $decision): bool => ! in_array((string) ($decision['resultado'] ?? ''), self::RESULTADOS, true)
                || ($decision['resultado'] ?? null) === 'pendiente'
        );
        if ($resultadosInvalidos->isNotEmpty()) {
            throw ValidationException::withMessages([
                'decisiones' => 'Existe al menos un resultado no válido en la clasificación de alumnos.',
            ]);
        }

        $cicloOrigen = CicloEscolar::query()->findOrFail((int) $configuracion['ciclo_origen_id']);
        $requiereDestino = $decisionesProcesables->contains(
            fn (array $decision): bool => in_array($decision['resultado'] ?? null, ['continuidad_interna', 'no_promovido'], true)
        );

        if ($requiereDestino && blank($configuracion['ciclo_destino_id'] ?? null)) {
            throw ValidationException::withMessages([
                'ciclo_destino_id' => 'Selecciona el ciclo escolar consecutivo para la continuidad o repetición.',
            ]);
        }

        if (filled($configuracion['ciclo_destino_id'] ?? null)) {
            $cicloDestino = CicloEscolar::query()->findOrFail((int) $configuracion['ciclo_destino_id']);
            if ((int) $cicloDestino->inicio_anio !== (int) $cicloOrigen->inicio_anio + 1
                || (int) $cicloDestino->fin_anio !== (int) $cicloOrigen->fin_anio + 1) {
                throw ValidationException::withMessages([
                    'ciclo_destino_id' => 'El ciclo destino debe ser el consecutivo inmediato del ciclo origen.',
                ]);
            }
        }

        $hayContinuidad = $decisionesProcesables->contains(
            fn (array $decision): bool => ($decision['resultado'] ?? null) === 'continuidad_interna'
        );

        if ($hayContinuidad) {
            $nivelOrigen = Nivel::query()->findOrFail((int) $configuracion['nivel_id']);
            $nivelEsperado = $this->nivelDestinoSugerido($nivelOrigen);

            if (! $nivelEsperado) {
                throw ValidationException::withMessages([
                    'nivel_destino_id' => 'Este nivel es terminal dentro del sistema; los alumnos deben egresar, trasladarse, causar baja o repetir.',
                ]);
            }

            if ((int) ($configuracion['nivel_destino_id'] ?? 0) !== (int) $nivelEsperado->id) {
                throw ValidationException::withMessages([
                    'nivel_destino_id' => "La continuidad permitida desde {$nivelOrigen->nombre} es únicamente hacia {$nivelEsperado->nombre}.",
                ]);
            }

            $primerGradoId = Grado::query()
                ->where('nivel_id', $nivelEsperado->id)
                ->orderBy('orden')
                ->orderBy('id')
                ->value('id');
            if ((int) ($configuracion['grado_destino_id'] ?? 0) !== (int) $primerGradoId) {
                throw ValidationException::withMessages([
                    'grado_destino_id' => 'La continuidad entre niveles debe iniciar en el primer grado del nivel destino.',
                ]);
            }
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
        int $usuarioId
    ): ProyeccionContinuidad {
        return ProyeccionContinuidad::query()->updateOrCreate(
            [
                'inscripcion_id' => $alumno->id,
                'ciclo_destino_id' => (int) $destino['ciclo_escolar_id'],
                'nivel_destino_id' => (int) $destino['nivel_id'],
            ],
            [
                'inscripcion_ciclo_origen_id' => $origen->id,
                'proceso_cierre_ciclo_id' => $proceso->id,
                'proceso_cierre_ciclo_detalle_id' => $detalle->id,
                'generacion_destino_id' => (int) $destino['generacion_id'],
                'grado_destino_id' => (int) $destino['grado_id'],
                'semestre_destino_id' => filled($destino['semestre_id'] ?? null) ? (int) $destino['semestre_id'] : null,
                'grupo_destino_id' => filled($destino['grupo_id'] ?? null) ? (int) $destino['grupo_id'] : null,
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
        if (($alumno->estatus ?? '') !== 'egresado') {
            throw ValidationException::withMessages([
                'seleccion_proyecciones' => "{$alumno->nombre} ya no está como egresado. Revisa su trayectoria antes de confirmar.",
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
            trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}")
        );

        $actualizado = $this->gestionAcademica->promoverAlumno(
            $alumno,
            $destino,
            $motivo,
            $usuarioId,
            $fecha,
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
        ]);

        $proyeccion->detalleCierre?->update([
            'inscripcion_ciclo_destino_id' => $destinoCiclo->id,
            'observacion' => 'Continuidad confirmada. El alumno quedó activo en el nivel destino.',
            'estado_nuevo' => $this->snapshotAlumno($actualizado->fresh()),
        ]);

        CambioAcademico::query()->create([
            'inscripcion_id' => $alumno->id,
            'inscripcion_ciclo_id' => $destinoCiclo->id,
            'generacion_id' => $destinoCiclo->generacion_id,
            'tipo' => 'confirmacion_proyeccion_continuidad',
            'motivo' => $motivo,
            'datos_anteriores' => ['proyeccion' => 'pendiente', 'estatus_alumno' => 'egresado'],
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

    private function asegurarDestinoDisponible(Inscripcion $alumno, int $cicloDestinoId, string $nombre): void
    {
        $existente = InscripcionCiclo::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('ciclo_escolar_id', $cicloDestinoId)
            ->lockForUpdate()
            ->first();

        if (! $existente) {
            return;
        }

        throw ValidationException::withMessages([
            "decisiones.{$alumno->id}.resultado" => "{$nombre} ya tiene un registro histórico en el ciclo destino. Revisa su trayectoria o usa el flujo de reingreso/reincorporación antes de procesarlo.",
        ]);
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
        return [
            'ciclo_escolar_id' => (int) ($configuracion['ciclo_destino_id'] ?? 0),
            'nivel_id' => (int) $configuracion['nivel_id'],
            'generacion_id' => (int) $configuracion['generacion_id'],
            'grado_id' => (int) $fila['grado_id'],
            'semestre_id' => filled($fila['semestre_id']) ? (int) $fila['semestre_id'] : null,
            'grupo_id' => (int) ($decision['grupo_destino_id'] ?? 0),
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

        if (! $esGradoFinal) {
            $advertencias[] = 'No está en el último grado o semestre';
        }

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

    private function textoResultado(string $resultado): string
    {
        return match ($resultado) {
            'continuidad_interna' => 'El alumno egresó del nivel de origen y quedó proyectado provisionalmente al siguiente nivel. Aún no está activo en el ciclo destino.',
            'egresado' => 'Egreso aplicado sin convertirlo en baja.',
            'traslado' => 'Traslado aplicado y expediente histórico conservado.',
            'baja_definitiva' => 'Baja definitiva aplicada con fecha y motivo.',
            'no_promovido' => 'Ciclo cerrado como no promovido y continuidad creada en el mismo grado o semestre.',
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
