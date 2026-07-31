<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\CicloEscolar;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use App\Models\PreinscripcionCiclo;
use Illuminate\Database\Eloquent\Builder;

class AlumnosNoVigentesService
{
    public const CATEGORIAS = [
        'todos',
        'preinscritos',
        'pendientes_reinscripcion',
        'no_reinscritos',
        'egresados',
        'regularizacion',
        'archivados',
    ];

    public const ESTATUS_BAJAS = [
        'baja_temporal',
        'baja_definitiva',
        'traslado',
        'trasladado',
        'suspendido',
        'inactivo',
    ];

    public const ESTATUS_NO_VIGENTES = [
        'preinscrito',
        'pendiente_reinscripcion',
        'no_reinscrito',
        'egresado',
        'reingreso',
        'no_promovido',
        'no_iniciado',
    ];

    public const RESULTADOS_NO_VIGENTES = [
        'no_iniciado',
        'egresado',
        'no_promovido',
    ];

    public function query(
        Nivel $nivel,
        int $cicloEscolarId,
        array $filtros = []
    ): Builder {
        $categoria = in_array($filtros['categoria'] ?? 'todos', self::CATEGORIAS, true)
            ? (string) ($filtros['categoria'] ?? 'todos')
            : 'todos';
        $esCicloActual = (bool) CicloEscolar::query()
            ->whereKey($cicloEscolarId)
            ->value('es_actual');

        $query = $categoria === 'archivados'
            ? Inscripcion::withTrashed()->whereNotNull('inscripciones.deleted_at')
            : Inscripcion::query();

        $contextoHistorial = function (Builder $historial) use ($nivel, $cicloEscolarId, $filtros): void {
            $historial
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivel->id)
                ->when(filled($filtros['generacion_id'] ?? null), fn(Builder $q) => $q->where('generacion_id', (int) $filtros['generacion_id']))
                ->when(filled($filtros['grado_id'] ?? null), fn(Builder $q) => $q->where('grado_id', (int) $filtros['grado_id']))
                ->when(filled($filtros['semestre_id'] ?? null), fn(Builder $q) => $q->where('semestre_id', (int) $filtros['semestre_id']))
                ->when(filled($filtros['grupo_id'] ?? null), fn(Builder $q) => $q->where('grupo_id', (int) $filtros['grupo_id']));
        };

        $contextoPreinscripcion = function (Builder $preinscripcion) use ($nivel, $cicloEscolarId, $filtros): void {
            $preinscripcion
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivel->id)
                ->whereIn('estado', ['pendiente', 'cancelada'])
                ->when(filled($filtros['generacion_id'] ?? null), fn(Builder $q) => $q->where('generacion_id', (int) $filtros['generacion_id']))
                ->when(filled($filtros['grado_id'] ?? null), fn(Builder $q) => $q->where('grado_id', (int) $filtros['grado_id']))
                ->when(filled($filtros['semestre_id'] ?? null), fn(Builder $q) => $q->where('semestre_id', (int) $filtros['semestre_id']))
                ->when(filled($filtros['grupo_id'] ?? null), fn(Builder $q) => $q->where('grupo_id', (int) $filtros['grupo_id']));
        };

        $contextoPrincipal = function (Builder $principal) use ($nivel, $cicloEscolarId, $filtros): void {
            $principal
                ->where('inscripciones.ciclo_escolar_id', $cicloEscolarId)
                ->where('inscripciones.nivel_id', $nivel->id)
                ->when(filled($filtros['generacion_id'] ?? null), fn(Builder $q) => $q->where('inscripciones.generacion_id', (int) $filtros['generacion_id']))
                ->when(filled($filtros['grado_id'] ?? null), fn(Builder $q) => $q->where('inscripciones.grado_id', (int) $filtros['grado_id']))
                ->when(filled($filtros['semestre_id'] ?? null), fn(Builder $q) => $q->where('inscripciones.semestre_id', (int) $filtros['semestre_id']))
                ->when(filled($filtros['grupo_id'] ?? null), fn(Builder $q) => $q->where('inscripciones.grupo_id', (int) $filtros['grupo_id']));
        };

        $query
            ->with([
                'ciclosEscolaresHistorial' => fn($historial) => $historial
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('nivel_id', $nivel->id)
                    ->with([
                        'cicloEscolar',
                        'generacion',
                        'grado',
                        'semestre',
                        'grupo' => fn($grupo) => $grupo->withTrashed()->with('asignacionGrupo'),
                    ]),
                'preinscripcionesCiclos' => fn($preinscripcion) => $preinscripcion
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('nivel_id', $nivel->id)
                    ->with([
                        'cicloEscolar',
                        'generacion',
                        'grado',
                        'semestre',
                        'grupo' => fn($grupo) => $grupo->withTrashed()->with('asignacionGrupo'),
                    ]),
                'nivel',
                'generacion',
                'grado',
                'semestre',
                'grupo.asignacionGrupo',
            ])
            ->where(function (Builder $registro) use ($contextoHistorial, $contextoPreinscripcion, $contextoPrincipal, $esCicloActual, $categoria): void {
                $registro
                    ->whereHas('ciclosEscolaresHistorial', function (Builder $historial) use ($contextoHistorial): void {
                        $contextoHistorial($historial);
                        $this->excluirBajasDelHistorial($historial);
                    })
                    ->orWhereHas('preinscripcionesCiclos', $contextoPreinscripcion);

                // Respaldo para registros legados que conservan el contexto
                // solamente en inscripciones y todavía no tienen historial.
                if ($esCicloActual) {
                    $registro->orWhere(function (Builder $principal) use ($contextoPrincipal, $categoria): void {
                        $contextoPrincipal($principal);

                        if ($categoria !== 'archivados') {
                            $principal->where(function (Builder $noActivo): void {
                                $noActivo
                                    ->where('inscripciones.activo', false)
                                    ->orWhereNull('inscripciones.estatus')
                                    ->orWhere('inscripciones.estatus', '!=', Inscripcion::ESTATUS_VISIBLE_LISTAS);
                            });
                        }
                    });
                }
            })
            ->where(function (Builder $alumno): void {
                $alumno
                    ->whereNull('inscripciones.estatus')
                    ->orWhereNotIn('inscripciones.estatus', self::ESTATUS_BAJAS);
            });

        if ($categoria !== 'archivados') {
            $query->where(function (Builder $clasificacion) use ($contextoHistorial, $contextoPreinscripcion, $categoria, $esCicloActual): void {
                $clasificacion->whereHas('ciclosEscolaresHistorial', function (Builder $historial) use ($contextoHistorial, $categoria): void {
                    $contextoHistorial($historial);
                    $this->excluirBajasDelHistorial($historial);
                    $this->aplicarCategoriaAlHistorial($historial, $categoria);
                });

                if (in_array($categoria, ['todos', 'preinscritos', 'no_reinscritos'], true)) {
                    $clasificacion->orWhereHas('preinscripcionesCiclos', function (Builder $preinscripcion) use ($contextoPreinscripcion, $categoria): void {
                        $contextoPreinscripcion($preinscripcion);
                        $this->aplicarCategoriaAPreinscripcion($preinscripcion, $categoria);
                    });
                }

                // En el ciclo actual también se toma el estatus principal como
                // respaldo para registros históricos antiguos o desincronizados.
                if ($esCicloActual) {
                    $clasificacion->orWhere(function (Builder $principal) use ($categoria): void {
                        $this->aplicarCategoriaAlPrincipal($principal, $categoria);
                    });
                }
            });
        }

        $search = trim((string) ($filtros['search'] ?? ''));

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function (Builder $busqueda) use ($like, $cicloEscolarId): void {
                $busqueda
                    ->where('inscripciones.matricula', 'like', $like)
                    ->orWhere('inscripciones.curp', 'like', $like)
                    ->orWhere('inscripciones.folio', 'like', $like)
                    ->orWhere('inscripciones.nombre', 'like', $like)
                    ->orWhere('inscripciones.apellido_paterno', 'like', $like)
                    ->orWhere('inscripciones.apellido_materno', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', inscripciones.nombre, inscripciones.apellido_paterno, inscripciones.apellido_materno) LIKE ?", [$like])
                    ->orWhereRaw("CONCAT_WS(' ', inscripciones.apellido_paterno, inscripciones.apellido_materno, inscripciones.nombre) LIKE ?", [$like])
                    ->orWhereHas('ciclosEscolaresHistorial', fn(Builder $historial) => $historial
                        ->where('ciclo_escolar_id', $cicloEscolarId)
                        ->where('matricula', 'like', $like))
                    ->orWhereHas('preinscripcionesCiclos', fn(Builder $preinscripcion) => $preinscripcion
                        ->where('ciclo_escolar_id', $cicloEscolarId)
                        ->where('matricula_propuesta', 'like', $like));
            });
        }

        return $query
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre');
    }

    public function resumen(Nivel $nivel, int $cicloEscolarId, array $filtros = []): array
    {
        $base = $filtros;
        unset($base['categoria']);

        return [
            'todos' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'todos'])->count(),
            'preinscritos' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'preinscritos'])->count(),
            'pendientes_reinscripcion' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'pendientes_reinscripcion'])->count(),
            'no_reinscritos' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'no_reinscritos'])->count(),
            'egresados' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'egresados'])->count(),
            'regularizacion' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'regularizacion'])->count(),
            'archivados' => $this->query($nivel, $cicloEscolarId, [...$base, 'categoria' => 'archivados'])->count(),
        ];
    }

    public function contextoDe(Inscripcion $alumno): InscripcionCiclo|PreinscripcionCiclo|null
    {
        return $alumno->ciclosEscolaresHistorial->first()
            ?? $alumno->preinscripcionesCiclos->first();
    }

    public function categoriaDe(InscripcionCiclo|PreinscripcionCiclo|null $contexto, ?Inscripcion $alumno = null): string
    {
        if ($alumno?->trashed()) {
            return 'archivados';
        }

        $estatus = $this->estatusDe($contexto, $alumno);

        return match ($estatus) {
            'preinscrito' => 'preinscritos',
            'pendiente_reinscripcion' => 'pendientes_reinscripcion',
            'no_reinscrito', 'no_iniciado' => 'no_reinscritos',
            'egresado' => 'egresados',
            'reingreso', 'no_promovido', 'pendiente_regularizacion' => 'regularizacion',
            default => 'todos',
        };
    }

    public function estatusContexto(InscripcionCiclo|PreinscripcionCiclo|null $contexto): string
    {
        if (!$contexto) {
            return 'inactivo';
        }

        if ($contexto instanceof PreinscripcionCiclo) {
            return match ((string) $contexto->estado) {
                'pendiente' => 'preinscrito',
                'cancelada' => 'no_reinscrito',
                default => 'activo',
            };
        }

        if ($contexto->estado === InscripcionCiclo::ESTADO_ANULADO) {
            return (string) ($contexto->resultado_final ?: $contexto->estatus_actual_ciclo ?: 'no_iniciado');
        }

        if ($contexto->estado === InscripcionCiclo::ESTADO_EN_CURSO) {
            return (string) ($contexto->estatus_actual_ciclo ?: 'activo');
        }

        return (string) ($contexto->resultado_final ?: $contexto->estatus_actual_ciclo ?: 'inactivo');
    }

    public function estatusDe(InscripcionCiclo|PreinscripcionCiclo|null $contexto, ?Inscripcion $alumno = null): string
    {
        if ($alumno?->trashed()) {
            return 'archivado';
        }

        $estatusContexto = $this->estatusContexto($contexto);

        if ($contexto instanceof PreinscripcionCiclo) {
            return $estatusContexto;
        }

        if (
            $contexto instanceof InscripcionCiclo
            && ($contexto->estado !== InscripcionCiclo::ESTADO_EN_CURSO || $estatusContexto !== 'activo')
        ) {
            return $estatusContexto;
        }

        $principal = $alumno?->estatusNormalizado();

        if (in_array($principal, self::ESTATUS_NO_VIGENTES, true)) {
            return $principal;
        }

        if (
            $alumno && !$alumno->visibleEnListas()
            && !in_array($principal, self::ESTATUS_BAJAS, true)
        ) {
            return 'pendiente_regularizacion';
        }

        return $estatusContexto;
    }

    public function matriculaDe(InscripcionCiclo|PreinscripcionCiclo|null $contexto, Inscripcion $alumno): ?string
    {
        return $contexto instanceof PreinscripcionCiclo
            ? ($contexto->matricula_propuesta ?: $alumno->matricula)
            : ($contexto?->matricula ?: $alumno->matricula);
    }

    public function fechaIngresoDe(InscripcionCiclo|PreinscripcionCiclo|null $contexto)
    {
        return $contexto instanceof PreinscripcionCiclo
            ? $contexto->fecha_preinscripcion
            : $contexto?->fecha_ingreso;
    }

    public function fechaSalidaDe(InscripcionCiclo|PreinscripcionCiclo|null $contexto)
    {
        if ($contexto instanceof PreinscripcionCiclo) {
            return $contexto->cancelada_at;
        }

        return $contexto?->fecha_salida ?: $contexto?->cerrado_at;
    }

    public function motivoDe(InscripcionCiclo|PreinscripcionCiclo|null $contexto, Inscripcion $alumno): ?string
    {
        return $contexto instanceof PreinscripcionCiclo
            ? ($contexto->motivo_cancelacion ?: $alumno->motivo_estatus)
            : ($contexto?->motivo_cierre ?: $alumno->motivo_estatus);
    }

    public function etiquetaCategoria(string $categoria): string
    {
        return match ($categoria) {
            'preinscritos' => 'Preinscritos',
            'pendientes_reinscripcion' => 'Pendientes de reinscripción',
            'no_reinscritos' => 'No reinscritos / no iniciaron',
            'egresados' => 'Egresados',
            'regularizacion' => 'Pendientes de regularización',
            'archivados' => 'Archivados',
            default => 'Todos los no vigentes',
        };
    }

    public function etiquetaEstatus(string $estatus): string
    {
        return match ($estatus) {
            'preinscrito' => 'Preinscrito',
            'pendiente_reinscripcion' => 'Pendiente de reinscripción',
            'no_reinscrito' => 'No reinscrito',
            'no_iniciado' => 'No inició el ciclo',
            'egresado' => 'Egresado',
            'reingreso' => 'Reingreso pendiente de formalización',
            'no_promovido' => 'No promovido pendiente de reasignación',
            'archivado' => 'Archivado',
            'pendiente_regularizacion' => 'Estado pendiente de regularizar',
            default => ucfirst(str_replace('_', ' ', $estatus)),
        };
    }

    private function excluirBajasDelHistorial(Builder $historial): void
    {
        $historial
            ->where(function (Builder $estado): void {
                $estado
                    ->whereNull('estatus_actual_ciclo')
                    ->orWhereNotIn('estatus_actual_ciclo', self::ESTATUS_BAJAS);
            })
            ->where(function (Builder $resultado): void {
                $resultado
                    ->whereNull('resultado_final')
                    ->orWhereNotIn('resultado_final', self::ESTATUS_BAJAS);
            });
    }

    private function aplicarCategoriaAlHistorial(Builder $historial, string $categoria): void
    {
        match ($categoria) {
            'preinscritos' => $historial->where('estatus_actual_ciclo', 'preinscrito'),
            'pendientes_reinscripcion' => $historial->where('estatus_actual_ciclo', 'pendiente_reinscripcion'),
            'no_reinscritos' => $historial->where(function (Builder $estado): void {
                    $estado
                    ->where('estado', InscripcionCiclo::ESTADO_ANULADO)
                    ->orWhereIn('estatus_actual_ciclo', ['no_reinscrito', 'no_iniciado'])
                    ->orWhere('resultado_final', 'no_iniciado');
                }),
            'egresados' => $historial->where(function (Builder $estado): void {
                    $estado
                    ->where('estatus_actual_ciclo', 'egresado')
                    ->orWhere('resultado_final', 'egresado');
                }),
            'regularizacion' => $historial->where(function (Builder $estado): void {
                    $estado
                    ->whereIn('estatus_actual_ciclo', ['reingreso', 'no_promovido'])
                    ->orWhere(function (Builder $sinDestino): void {
                        $sinDestino
                        ->where('resultado_final', 'no_promovido')
                        ->whereNull('inscripcion_ciclo_destino_id');
                    });
                }),
            default => $historial->where(function (Builder $estado): void {
                    $estado
                    ->where('estado', InscripcionCiclo::ESTADO_ANULADO)
                    ->orWhereIn('estatus_actual_ciclo', self::ESTATUS_NO_VIGENTES)
                    ->orWhereIn('resultado_final', self::RESULTADOS_NO_VIGENTES);
                }),
        };
    }

    private function aplicarCategoriaAPreinscripcion(Builder $preinscripcion, string $categoria): void
    {
        match ($categoria) {
            'preinscritos' => $preinscripcion->where('estado', 'pendiente'),
            'no_reinscritos' => $preinscripcion->where('estado', 'cancelada'),
            default => $preinscripcion->whereIn('estado', ['pendiente', 'cancelada']),
        };
    }

    private function aplicarCategoriaAlPrincipal(Builder $principal, string $categoria): void
    {
        match ($categoria) {
            'preinscritos' => $principal->where('inscripciones.estatus', 'preinscrito'),
            'pendientes_reinscripcion' => $principal->where('inscripciones.estatus', 'pendiente_reinscripcion'),
            'no_reinscritos' => $principal->whereIn('inscripciones.estatus', ['no_reinscrito', 'no_iniciado']),
            'egresados' => $principal->where('inscripciones.estatus', 'egresado'),
            'regularizacion' => $principal->where(function (Builder $estado): void {
                    $estado
                    ->whereIn('inscripciones.estatus', ['reingreso', 'no_promovido'])
                    ->orWhere(function (Builder $inconsistente): void {
                        $inconsistente
                        ->where('inscripciones.activo', false)
                        ->where(function (Builder $estatus): void {
                            $estatus
                            ->whereNull('inscripciones.estatus')
                            ->orWhere('inscripciones.estatus', 'activo');
                        });
                    });
                }),
            default => $principal->where(function (Builder $estado): void {
                    $estado
                    ->whereIn('inscripciones.estatus', self::ESTATUS_NO_VIGENTES)
                    ->orWhere(function (Builder $inconsistente): void {
                        $inconsistente
                        ->where('inscripciones.activo', false)
                        ->where(function (Builder $noBaja): void {
                            $noBaja
                            ->whereNull('inscripciones.estatus')
                            ->orWhereNotIn('inscripciones.estatus', self::ESTATUS_BAJAS);
                        });
                    });
                }),
        };
    }

}
