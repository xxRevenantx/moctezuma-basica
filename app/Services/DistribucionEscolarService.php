<?php

namespace App\Services;

use App\Models\Escuela;
use App\Models\Inscripcion;
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
        $alumnos = $this->query($nivel, $filtros)->get();
        $escuela = Escuela::query()->first();
        $director = $this->nombreDirector($nivel);

        return $alumnos
            ->groupBy(fn (Inscripcion $a) => (string) ($a->generacion_id ?: 0))
            ->map(function (Collection $porGeneracion) use ($nivel, $escuela, $director): array {
                $generacion = $porGeneracion->first()?->generacion;
                $filas = $porGeneracion
                    ->groupBy(fn (Inscripcion $a) => ($a->grado_id ?: 0) . '|' . ($a->semestre_id ?: 0) . '|' . ($a->grupo_id ?: 0))
                    ->map(function (Collection $grupo) use ($nivel, $escuela, $director, $generacion): array {
                        /** @var Inscripcion $primero */
                        $primero = $grupo->first();
                        $categorias = $grupo->countBy(fn (Inscripcion $a) => $this->categoriaAlumno($a));
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
                            'hombres' => $grupo->where('genero', 'H')->count(),
                            'mujeres' => $grupo->where('genero', 'M')->count(),
                            'total_historico' => $grupo->unique('id')->count(),
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
                    ->sortBy(fn (array $f) => sprintf('%04d|%04d|%s', $f['orden'], (int) $f['semestre'], $f['grupo']))
                    ->values();

                return [
                    'ciclo' => 'Generación ' . ($generacion?->etiqueta ?: 'sin asignar'),
                    'filas' => $filas->all(),
                    'totales' => $this->totalesFilas($filas),
                ];
            })
            ->sortByDesc(fn (array $b) => $b['filas'][0]['generacion_ingreso'] ?? 0)
            ->values();
    }

    public function listadoCompleto(Nivel $nivel, array $filtros = []): Collection
    {
        return $this->query($nivel, $filtros)->get()->map(function (Inscripcion $a): array {
            $categoria = $this->categoriaAlumno($a);
            $estado = $this->etiquetaEstatus($a->estatus ?: 'activo');
            return [
                'ciclo' => 'No aplica',
                'matricula' => $a->matricula ?: '—',
                'curp' => $a->curp ?: '—',
                'alumno' => trim(($a->apellido_paterno ?? '') . ' ' . ($a->apellido_materno ?? '') . ' ' . ($a->nombre ?? '')),
                'genero' => $a->genero ?: '—',
                'nivel' => $a->nivel?->nombre ?: '—',
                'grado' => $a->grado?->nombre ?: '—',
                'semestre' => $a->semestre?->numero ?: '—',
                'grupo' => $a->grupo?->asignacionGrupo?->nombre ?: '—',
                'generacion' => $a->generacion?->etiqueta ?: 'Sin generación',
                'estado_historico' => $estado,
                'estado_actual' => $estado,
                'categoria_historica' => $categoria,
                'categoria_actual' => $categoria,
                'fecha_alta' => optional($a->fecha_inscripcion)->format('d/m/Y') ?: '—',
                'fecha_baja' => optional($a->fecha_baja ?: $a->fecha_estatus)->format('d/m/Y') ?: '—',
                'motivo' => $a->motivo_estatus ?: $a->motivo_baja ?: '—',
                'observaciones' => $a->observaciones_baja ?: '—',
                'reconstruido' => 'No',
                'inscripcion_id' => $a->id,
            ];
        })->sortBy('alumno')->values();
    }

    public function detalleFila(Nivel $nivel, array $filtros, ?int $generacionId = null, ?int $gradoId = null, ?int $grupoId = null): Collection
    {
        $filtros = array_merge($filtros, array_filter([
            'generacion_id' => $generacionId,
            'grado_id' => $gradoId,
            'grupo_id' => $grupoId,
        ]));
        return $this->listadoCompleto($nivel, $filtros);
    }

    public function historialAlumno(Nivel $nivel, int $inscripcionId): array
    {
        $alumno = Inscripcion::withTrashed()->with([
            'generacion', 'grado', 'semestre', 'grupo.asignacionGrupo',
            'cambiosAcademicos.usuario', 'movimientos.usuario',
        ])->where('nivel_id', $nivel->id)->findOrFail($inscripcionId);

        return [
            'alumno' => $alumno,
            'cambios' => $alumno->cambiosAcademicos,
            'movimientos' => $alumno->movimientos()->with('usuario')->latest('fecha')->get(),
        ];
    }

    private function query(Nivel $nivel, array $filtros): Builder
    {
        return Inscripcion::query()
            ->with(['nivel', 'generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $nivel->id)
            ->when(filled($filtros['generacion_id'] ?? null), fn (Builder $q) => $q->where('generacion_id', (int) $filtros['generacion_id']))
            ->when(filled($filtros['grado_id'] ?? null), fn (Builder $q) => $q->where('grado_id', (int) $filtros['grado_id']))
            ->when(filled($filtros['grupo_id'] ?? null), fn (Builder $q) => $q->where('grupo_id', (int) $filtros['grupo_id']))
            ->when(filled($filtros['semestre_id'] ?? null), fn (Builder $q) => $q->where('semestre_id', (int) $filtros['semestre_id']))
            ->when(($filtros['solo_ya_no_estan'] ?? false), fn (Builder $q) => $q->whereNotIn('estatus', ['activo', 'reingreso', 'no_promovido']))
            ->when(($filtros['estado'] ?? 'todos') !== 'todos', function (Builder $q) use ($filtros): void {
                match ($filtros['estado']) {
                    'activo' => $q->where('estatus', 'activo'),
                    'reingreso' => $q->where('estatus', 'reingreso'),
                    'no_promovido' => $q->where('estatus', 'no_promovido'),
                    'baja' => $q->whereIn('estatus', ['baja_temporal', 'baja_definitiva']),
                    'traslado' => $q->where('estatus', 'trasladado'),
                    'suspendido' => $q->where('estatus', 'suspendido'),
                    'egresado' => $q->where('estatus', 'egresado'),
                    'inactivo' => $q->where('estatus', 'inactivo'),
                    default => null,
                };
            });
    }

    private function categoriaAlumno(Inscripcion $a): string
    {
        return match ($a->estatus ?: 'activo') {
            'baja_temporal', 'baja_definitiva' => 'baja',
            'trasladado' => 'traslado',
            'suspendido' => 'suspendido',
            'egresado' => 'egresado',
            'inactivo' => 'inactivo',
            'reingreso' => 'reingreso',
            'no_promovido' => 'no_promovido',
            default => $a->activo ? 'activo' : 'inactivo',
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
        $d = $nivel->director;
        return $d ? trim(collect([$d->titulo, $d->nombre, $d->apellido_paterno, $d->apellido_materno])->filter()->implode(' ')) : '—';
    }
}
