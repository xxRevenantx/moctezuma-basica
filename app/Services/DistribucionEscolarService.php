<?php

namespace App\Services;

use App\Models\Escuela;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DistribucionEscolarService
{
    public const CATEGORIAS = [
        'activo' => 'Activos',
        'baja' => 'Bajas',
        'traslado' => 'Trasladados',
        'suspendido' => 'Suspendidos',
        'egresado' => 'Egresados',
        'inactivo' => 'Inactivos',
        'reingreso' => 'Reingresos',
        'no_promovido' => 'No promovidos',
    ];

    public function categorias(): array
    {
        return self::CATEGORIAS;
    }

    public function bloques(Nivel $nivel, array $filtros = []): Collection
    {
        $registros = $this->query($nivel, $filtros)->get();
        $escuela = Escuela::query()->first();
        $director = $this->nombreDirector($nivel);

        return $registros
            ->groupBy(fn (InscripcionCiclo $registro) => (string) ($registro->generacion_id ?: 0))
            ->map(function (Collection $porGeneracion) use ($nivel, $escuela, $director): array {
                $generacion = $porGeneracion->first()?->generacion;
                $filas = $porGeneracion
                    ->groupBy(fn (InscripcionCiclo $registro) => ($registro->grado_id ?: 0) . '|' . ($registro->semestre_id ?: 0) . '|' . ($registro->grupo_id ?: 0))
                    ->map(function (Collection $grupo) use ($nivel, $escuela, $director, $generacion): array {
                        /** @var InscripcionCiclo $primero */
                        $primero = $grupo->first();
                        $categorias = $grupo->countBy(fn (InscripcionCiclo $registro) => $this->categoriaCiclo($registro));

                        return [
                            'regional' => $escuela?->regional ?: '—',
                            'zona' => $nivel->supervisor?->zona_escolar ?: $nivel->director?->zona_escolar ?: '—',
                            'cct' => $nivel->cct ?: '—',
                            'nombre_ct' => $escuela?->nombre ?: 'CENTRO UNIVERSITARIO MOCTEZUMA',
                            'nivel' => $nivel->nombre,
                            'turno' => 'Matutino',
                            'grado' => $primero->grado?->nombre ?: '—',
                            'semestre' => $primero->semestre?->numero ?: '—',
                            'grupo' => $primero->grupo?->asignacionGrupo?->nombre ?: '—',
                            'hombres' => $grupo->filter(fn (InscripcionCiclo $r) => $r->inscripcion?->genero === 'H')->count(),
                            'mujeres' => $grupo->filter(fn (InscripcionCiclo $r) => $r->inscripcion?->genero === 'M')->count(),
                            'total_historico' => $grupo->unique('inscripcion_id')->count(),
                            'activos' => (int) (($categorias['activo'] ?? 0) + ($categorias['reingreso'] ?? 0) + ($categorias['no_promovido'] ?? 0)),
                            'inactivos' => (int) ($categorias['inactivo'] ?? 0),
                            'bajas' => (int) ($categorias['baja'] ?? 0),
                            'traslados' => (int) ($categorias['traslado'] ?? 0),
                            'suspendidos' => (int) ($categorias['suspendido'] ?? 0),
                            'egresados' => (int) ($categorias['egresado'] ?? 0),
                            'generacion_id' => $generacion?->id,
                            'generacion_ingreso' => $generacion?->anio_ingreso,
                            'generacion' => $generacion?->etiqueta ?: 'Sin generación',
                            'maestro' => '',
                            'director' => $director,
                            'grado_id' => $primero->grado_id,
                            'grupo_id' => $primero->grupo_id,
                            'semestre_id' => $primero->semestre_id,
                            'orden' => (int) ($primero->grado?->orden ?? 999),
                        ];
                    })
                    ->sortBy(fn (array $fila) => sprintf('%04d|%04d|%s', $fila['orden'], (int) $fila['semestre'], $fila['grupo']))
                    ->values();

                return [
                    'ciclo' => 'Generación ' . ($generacion?->etiqueta ?: 'sin asignar'),
                    'filas' => $filas->all(),
                    'totales' => $this->totalesFilas($filas),
                ];
            })
            ->sortByDesc(fn (array $bloque) => $bloque['filas'][0]['generacion_ingreso'] ?? 0)
            ->values();
    }

    public function listadoCompleto(Nivel $nivel, array $filtros = []): Collection
    {
        return $this->query($nivel, $filtros)->get()->map(function (InscripcionCiclo $registro): array {
            $alumno = $registro->inscripcion;
            $categoriaHistorica = $this->categoriaCiclo($registro);
            $estadoHistorico = $this->etiquetaEstatus($registro->resultado_final ?: $registro->estatus_actual_ciclo ?: 'activo');
            $categoriaActual = $alumno ? $this->categoriaAlumnoActual($alumno) : 'inactivo';
            $estadoActual = $alumno ? $this->etiquetaEstatus($alumno->estatus ?: 'activo') : 'Archivado';

            return [
                'ciclo' => $registro->cicloEscolar?->nombre ?: '—',
                'matricula' => $registro->matricula ?: $alumno?->matricula ?: '—',
                'curp' => $alumno?->curp ?: '—',
                'alumno' => trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? '')),
                'genero' => $alumno?->genero ?: '—',
                'nivel' => $registro->nivel?->nombre ?: '—',
                'grado' => $registro->grado?->nombre ?: '—',
                'semestre' => $registro->semestre?->numero ?: '—',
                'grupo' => $registro->grupo?->asignacionGrupo?->nombre ?: '—',
                'generacion' => $registro->generacion?->etiqueta ?: 'Sin generación',
                'estado_historico' => $estadoHistorico,
                'estado_actual' => $estadoActual,
                'categoria_historica' => $categoriaHistorica,
                'categoria_actual' => $categoriaActual,
                'fecha_alta' => optional($registro->fecha_ingreso)->format('d/m/Y') ?: '—',
                'fecha_baja' => optional($registro->fecha_salida)->format('d/m/Y') ?: '—',
                'motivo' => $registro->motivo_cierre ?: '—',
                'observaciones' => $registro->resultado_final ? 'Resultado del ciclo: ' . $this->etiquetaEstatus($registro->resultado_final) : 'En curso',
                'reconstruido' => $registro->reconstruido ? 'Sí (' . $registro->nivel_confianza . ')' : 'No',
                'inscripcion_id' => $registro->inscripcion_id,
                'inscripcion_ciclo_id' => $registro->id,
            ];
        })->sortBy('alumno')->values();
    }

    public function detalleFila(Nivel $nivel, array $filtros, ?int $generacionId = null, ?int $gradoId = null, ?int $grupoId = null): Collection
    {
        return $this->listadoCompleto($nivel, array_merge($filtros, array_filter([
            'generacion_id' => $generacionId,
            'grado_id' => $gradoId,
            'grupo_id' => $grupoId,
        ])));
    }

    public function historialAlumno(Nivel $nivel, int $inscripcionId): array
    {
        $alumno = Inscripcion::withTrashed()->with([
            'generacion', 'grado', 'semestre', 'grupo.asignacionGrupo',
            'cambiosAcademicos.usuario', 'movimientos.usuario',
            'ciclosEscolaresHistorial.cicloEscolar',
            'ciclosEscolaresHistorial.asignaciones.grupo.asignacionGrupo',
            'ciclosEscolaresHistorial.asignaciones.grado',
            'ciclosEscolaresHistorial.asignaciones.semestre',
            'preinscripcionesCiclos.cicloEscolar',
        ])->findOrFail($inscripcionId);

        abort_unless(
            $alumno->ciclosEscolaresHistorial->contains(fn (InscripcionCiclo $ciclo) => (int) $ciclo->nivel_id === (int) $nivel->id)
                || (int) $alumno->nivel_id === (int) $nivel->id,
            404
        );

        return [
            'alumno' => $alumno,
            'ciclos' => $alumno->ciclosEscolaresHistorial,
            'preinscripciones' => $alumno->preinscripcionesCiclos,
            'cambios' => $alumno->cambiosAcademicos,
            'movimientos' => $alumno->movimientos()->with('usuario')->latest('fecha')->get(),
        ];
    }

    private function query(Nivel $nivel, array $filtros): Builder
    {
        return InscripcionCiclo::query()
            ->with([
                'inscripcion', 'cicloEscolar', 'nivel', 'generacion', 'grado', 'semestre', 'grupo.asignacionGrupo',
            ])
            ->where('nivel_id', $nivel->id)
            ->when(filled($filtros['ciclo_escolar_id'] ?? null), fn (Builder $q) => $q->where('ciclo_escolar_id', (int) $filtros['ciclo_escolar_id']))
            ->when(filled($filtros['generacion_id'] ?? null), fn (Builder $q) => $q->where('generacion_id', (int) $filtros['generacion_id']))
            ->when(filled($filtros['grado_id'] ?? null), fn (Builder $q) => $q->where('grado_id', (int) $filtros['grado_id']))
            ->when(filled($filtros['grupo_id'] ?? null), fn (Builder $q) => $q->where('grupo_id', (int) $filtros['grupo_id']))
            ->when(filled($filtros['semestre_id'] ?? null), fn (Builder $q) => $q->where('semestre_id', (int) $filtros['semestre_id']))
            ->when(($filtros['solo_ya_no_estan'] ?? false), fn (Builder $q) => $q->where(function (Builder $inner): void {
                $inner->where('estado', 'cerrado')
                    ->orWhereNotIn('estatus_actual_ciclo', ['activo', 'reingreso', 'no_promovido']);
            }))
            ->when(($filtros['estado'] ?? 'todos') !== 'todos', function (Builder $q) use ($filtros): void {
                match ($filtros['estado']) {
                    'activo' => $q->where('estatus_actual_ciclo', 'activo')->where('estado', 'en_curso'),
                    'reingreso' => $q->where('estatus_actual_ciclo', 'reingreso'),
                    'no_promovido' => $q->where(function (Builder $inner): void {
                        $inner->where('estatus_actual_ciclo', 'no_promovido')->orWhere('resultado_final', 'no_promovido');
                    }),
                    'baja' => $q->whereIn('resultado_final', ['baja_temporal_al_cierre', 'baja_definitiva'])->orWhereIn('estatus_actual_ciclo', ['baja_temporal', 'baja_definitiva']),
                    'traslado' => $q->where('resultado_final', 'trasladado')->orWhereIn('estatus_actual_ciclo', ['trasladado', 'traslado']),
                    'suspendido' => $q->where('estatus_actual_ciclo', 'suspendido'),
                    'egresado' => $q->where('resultado_final', 'egresado')->orWhere('estatus_actual_ciclo', 'egresado'),
                    'inactivo' => $q->where('estatus_actual_ciclo', 'inactivo'),
                    default => null,
                };
            });
    }

    private function categoriaCiclo(InscripcionCiclo $registro): string
    {
        $estado = $registro->resultado_final ?: $registro->estatus_actual_ciclo ?: 'activo';

        return match ($estado) {
            'baja_temporal', 'baja_temporal_al_cierre', 'baja_definitiva' => 'baja',
            'trasladado', 'traslado' => 'traslado',
            'suspendido' => 'suspendido',
            'egresado' => 'egresado',
            'inactivo' => 'inactivo',
            'reingreso' => 'reingreso',
            'no_promovido' => 'no_promovido',
            default => $registro->estado === 'en_curso' ? 'activo' : 'inactivo',
        };
    }

    private function categoriaAlumnoActual(Inscripcion $alumno): string
    {
        return match ($alumno->estatus ?: 'activo') {
            'baja_temporal', 'baja_definitiva' => 'baja',
            'trasladado', 'traslado' => 'traslado',
            'suspendido' => 'suspendido',
            'egresado' => 'egresado',
            'inactivo' => 'inactivo',
            'reingreso' => 'reingreso',
            'no_promovido' => 'no_promovido',
            default => $alumno->activo ? 'activo' : 'inactivo',
        };
    }

    private function etiquetaEstatus(string $estatus): string
    {
        return Str::headline(str_replace('_', ' ', $estatus));
    }

    private function totalesFilas(Collection $filas): array
    {
        return [
            'hombres' => (int) $filas->sum('hombres'),
            'mujeres' => (int) $filas->sum('mujeres'),
            'total_historico' => (int) $filas->sum('total_historico'),
            'activos' => (int) $filas->sum('activos'),
            'inactivos' => (int) $filas->sum('inactivos'),
            'bajas' => (int) $filas->sum('bajas'),
            'traslados' => (int) $filas->sum('traslados'),
            'suspendidos' => (int) $filas->sum('suspendidos'),
            'egresados' => (int) $filas->sum('egresados'),
        ];
    }

    private function nombreDirector(Nivel $nivel): string
    {
        $director = $nivel->director;

        return $director
            ? trim(collect([$director->titulo, $director->nombre, $director->apellido_paterno, $director->apellido_materno])->filter()->implode(' '))
            : '—';
    }
}
