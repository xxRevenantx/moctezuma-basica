<?php

namespace App\Services;

use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListaGeneracionesHistoricasService
{
    public const ESTATUS = [
        'todos', 'egresado', 'activo', 'reingreso', 'no_promovido',
        'baja_temporal', 'baja_definitiva', 'trasladado', 'suspendido', 'inactivo',
    ];

    public function generar(
        Nivel $nivel,
        array $generacionIds,
        string $estatus = 'egresado',
        ?int $grupoId = null,
        bool $incluirArchivados = false,
        ?int $cicloEscolarId = null,
    ): array {
        $generacionIds = collect($generacionIds)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($generacionIds->isEmpty(), 422, 'Selecciona al menos una generación.');
        abort_unless(in_array($estatus, self::ESTATUS, true), 422, 'El estatus seleccionado no es válido.');

        $generaciones = Generacion::query()
            ->where('nivel_id', $nivel->id)
            ->whereNull('deleted_at')
            ->whereIn('id', $generacionIds)
            ->orderBy('anio_ingreso')
            ->orderBy('anio_egreso')
            ->get();

        abort_if($generaciones->count() !== $generacionIds->count(), 422, 'Una o más generaciones no pertenecen al nivel seleccionado.');

        if ($grupoId) {
            $grupoValido = Grupo::query()
                ->where('id', $grupoId)
                ->where('nivel_id', $nivel->id)
                ->whereIn('generacion_id', $generacionIds)
                ->when($cicloEscolarId, fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
                ->exists();
            abort_unless($grupoValido, 422, 'El grupo no pertenece al ciclo y generaciones seleccionados.');
        }

        $consulta = InscripcionCiclo::query()
            ->with([
                'inscripcion',
                'cicloEscolar',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status,motivo_desactivacion,fecha_termino',
                'grado:id,nivel_id,nombre,orden',
                'semestre',
                'grupo:id,nivel_id,grado_id,generacion_id,semestre_id,asignacion_grupo_id,ciclo_escolar_id',
                'grupo.asignacionGrupo:id,nombre',
            ])
            ->where('nivel_id', $nivel->id)
            ->whereIn('generacion_id', $generacionIds)
            ->when($cicloEscolarId, fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId))
            ->when(! $cicloEscolarId, function (Builder $q): void {
                $q->whereIn('id', function ($sub): void {
                    $sub->from('inscripcion_ciclos')
                        ->selectRaw('MAX(id)')
                        ->groupBy('inscripcion_id');
                });
            })
            ->when($grupoId, fn (Builder $q) => $q->where('grupo_id', $grupoId));

        $this->aplicarEstatus($consulta, $estatus);

        $registros = $consulta
            ->get()
            ->filter(fn (InscripcionCiclo $registro) => $incluirArchivados || ! $registro->inscripcion?->trashed())
            ->sortBy(fn (InscripcionCiclo $registro) => mb_strtolower(trim(
                ($registro->inscripcion?->apellido_paterno ?? '') . ' ' .
                ($registro->inscripcion?->apellido_materno ?? '') . ' ' .
                ($registro->inscripcion?->nombre ?? '')
            )))
            ->values();

        $bloquesGeneracion = $generaciones->map(function (Generacion $generacion) use ($registros): array {
            $registrosGeneracion = $registros->where('generacion_id', $generacion->id)->values();
            $grupos = $registrosGeneracion
                ->groupBy(fn (InscripcionCiclo $registro) => (int) ($registro->grupo_id ?: 0))
                ->map(function (Collection $registrosGrupo): array {
                    /** @var InscripcionCiclo $primero */
                    $primero = $registrosGrupo->first();

                    return [
                        'id' => (int) ($primero?->grupo_id ?: 0),
                        'titulo' => $this->tituloGrupo($primero),
                        'orden' => sprintf(
                            '%04d|%04d|%s',
                            (int) ($primero?->grado?->orden ?? 9999),
                            (int) ($primero?->semestre?->numero ?? $primero?->semestre_id ?? 0),
                            mb_strtolower((string) ($primero?->grupo?->asignacionGrupo?->nombre ?? 'zzzz'))
                        ),
                        'alumnos' => $registrosGrupo->map(fn (InscripcionCiclo $registro) => $this->filaAlumno($registro))->values(),
                        'resumen' => $this->resumen($registrosGrupo),
                    ];
                })
                ->sortBy('orden')
                ->values();

            return [
                'id' => (int) $generacion->id,
                'etiqueta' => $this->etiquetaGeneracion($generacion),
                'anio_ingreso' => (int) $generacion->anio_ingreso,
                'anio_egreso' => (int) $generacion->anio_egreso,
                'activa' => (bool) $generacion->status,
                'estado' => $generacion->status ? 'Activa' : 'Egresada / cerrada',
                'fecha_termino' => $generacion->fecha_termino?->format('d/m/Y'),
                'motivo' => $generacion->motivo_desactivacion,
                'grupos' => $grupos,
                'resumen' => $this->resumen($registrosGeneracion),
            ];
        })->values();

        return [
            'nivel' => $nivel,
            'titulo' => 'LISTAS HISTÓRICAS DE ALUMNOS POR GENERACIÓN',
            'subtitulo' => $cicloEscolarId
                ? 'Padrón congelado del ciclo escolar seleccionado'
                : 'Última ubicación histórica disponible por alumno',
            'generaciones' => $bloquesGeneracion,
            'resumen' => $this->resumen($registros),
            'estatus' => $estatus,
            'estatus_etiqueta' => $this->estatusEtiqueta($estatus),
            'incluir_archivados' => $incluirArchivados,
            'grupo_id' => $grupoId,
            'ciclo_escolar_id' => $cicloEscolarId,
            'ciclo_escolar' => $registros->first()?->cicloEscolar?->nombre,
            'fecha_generacion' => now()->translatedFormat('d \d\e F \d\e Y'),
            'logo_institucional' => $this->imagenBase64(public_path('imagenes/logo-letra.png')),
            'logo_nivel' => $this->logoNivel($nivel),
        ];
    }

    public function nombreBase(array $datos): string
    {
        $generaciones = collect($datos['generaciones']);
        $prefijo = $datos['estatus'] === 'egresado' ? 'Lista_Egresados' : 'Lista_Alumnos';
        $nivel = Str::studly(Str::ascii((string) $datos['nivel']->nombre));
        $ciclo = filled($datos['ciclo_escolar'] ?? null) ? '_Ciclo_' . Str::slug($datos['ciclo_escolar'], '_') : '';

        if ($generaciones->count() === 1) {
            $generacion = $generaciones->first();
            return sprintf('%s_%s_Generacion_%s-%s%s', $prefijo, $nivel, $generacion['anio_ingreso'], $generacion['anio_egreso'], $ciclo);
        }

        return sprintf('Listas_Historicas_%s_%d_Generaciones%s', $nivel, $generaciones->count(), $ciclo);
    }

    public function estatusEtiqueta(string $estatus): string
    {
        return match ($estatus) {
            'todos' => 'Todos los estatus',
            'egresado' => 'Egresados',
            'activo' => 'Activos',
            'reingreso' => 'Reingresos',
            'no_promovido' => 'No promovidos',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'trasladado' => 'Trasladados',
            'suspendido' => 'Suspendidos',
            'inactivo' => 'Inactivos',
            default => Str::headline($estatus),
        };
    }

    private function aplicarEstatus(Builder $query, string $estatus): void
    {
        if ($estatus === 'todos') {
            return;
        }

        match ($estatus) {
            'egresado' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'egresado')->orWhere('estatus_actual_ciclo', 'egresado')),
            'activo' => $query->where('estado', 'en_curso')->where('estatus_actual_ciclo', 'activo'),
            'reingreso' => $query->where('estatus_actual_ciclo', 'reingreso'),
            'no_promovido' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'no_promovido')->orWhere('estatus_actual_ciclo', 'no_promovido')),
            'baja_temporal' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'baja_temporal_al_cierre')->orWhere('estatus_actual_ciclo', 'baja_temporal')),
            'baja_definitiva' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'baja_definitiva')->orWhere('estatus_actual_ciclo', 'baja_definitiva')),
            'trasladado' => $query->where(fn (Builder $q) => $q->where('resultado_final', 'trasladado')->orWhereIn('estatus_actual_ciclo', ['trasladado', 'traslado'])),
            'suspendido' => $query->where('estatus_actual_ciclo', 'suspendido'),
            'inactivo' => $query->where('estatus_actual_ciclo', 'inactivo'),
            default => null,
        };
    }

    private function filaAlumno(InscripcionCiclo $registro): array
    {
        $alumno = $registro->inscripcion;
        $nombre = collect([$alumno?->apellido_paterno, $alumno?->apellido_materno, $alumno?->nombre])->filter()->implode(' ');
        $estatusClave = $registro->resultado_final ?: $registro->estatus_actual_ciclo;
        $estatus = $this->estatusEtiqueta((string) $estatusClave);
        if ($alumno?->trashed()) {
            $estatus .= ' · Archivado';
        }

        return [
            'id' => (int) $registro->inscripcion_id,
            'matricula' => $registro->matricula ?: $alumno?->matricula ?: '—',
            'nombre' => mb_strtoupper($nombre),
            'curp' => mb_strtoupper((string) $alumno?->curp),
            'genero' => $alumno?->genero === 'M' ? 'Mujer' : 'Hombre',
            'generacion' => $this->etiquetaGeneracion($registro->generacion),
            'grupo' => $this->grupoAlumno($registro),
            'estatus' => $estatus,
            'estatus_clave' => (string) $estatusClave,
            'fecha_egreso' => $registro->resultado_final === 'egresado' && $registro->fecha_salida
                ? $registro->fecha_salida->format('d/m/Y')
                : '—',
            'archivado' => (bool) $alumno?->trashed(),
            'ciclo' => $registro->cicloEscolar?->nombre ?: '—',
        ];
    }

    private function resumen(Collection $registros): array
    {
        $estatus = $registros->map(fn (InscripcionCiclo $r) => $r->resultado_final ?: $r->estatus_actual_ciclo);
        $total = $registros->count();
        $bajas = $estatus->filter(fn ($e) => in_array($e, ['baja_temporal', 'baja_temporal_al_cierre', 'baja_definitiva'], true))->count();
        $activos = $registros->filter(fn (InscripcionCiclo $r) => $r->estado === 'en_curso' && in_array($r->estatus_actual_ciclo, ['activo', 'reingreso', 'no_promovido'], true))->count();
        $egresados = $estatus->where('egresado')->count();
        $trasladados = $estatus->filter(fn ($e) => in_array($e, ['trasladado', 'traslado'], true))->count();

        return [
            'total' => $total,
            'hombres' => $registros->filter(fn (InscripcionCiclo $r) => $r->inscripcion?->genero === 'H')->count(),
            'mujeres' => $registros->filter(fn (InscripcionCiclo $r) => $r->inscripcion?->genero === 'M')->count(),
            'activos' => $activos,
            'egresados' => $egresados,
            'bajas' => $bajas,
            'trasladados' => $trasladados,
            'archivados' => $registros->filter(fn (InscripcionCiclo $r) => $r->inscripcion?->trashed())->count(),
            'otros' => max(0, $total - $activos - $egresados - $bajas - $trasladados),
        ];
    }

    private function tituloGrupo(?InscripcionCiclo $registro): string
    {
        if (! $registro) return 'SIN GRUPO';
        $partes = [];
        if ($registro->grado?->nombre) $partes[] = $registro->grado->nombre . '° GRADO';
        if ($registro->semestre) $partes[] = 'SEMESTRE ' . ($registro->semestre->numero ?? $registro->semestre_id);
        $partes[] = 'GRUPO ' . ($registro->grupo?->asignacionGrupo?->nombre ?? 'SIN ASIGNAR');
        return mb_strtoupper(implode(' · ', $partes));
    }

    private function grupoAlumno(InscripcionCiclo $registro): string
    {
        $partes = [];
        if ($registro->grado?->nombre) $partes[] = $registro->grado->nombre . '°';
        if ($registro->semestre) $partes[] = 'Sem. ' . ($registro->semestre->numero ?? $registro->semestre_id);
        $partes[] = $registro->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo';
        return implode(' · ', $partes);
    }

    private function etiquetaGeneracion(?Generacion $generacion): string
    {
        return $generacion ? ($generacion->nombre ?: $generacion->anio_ingreso . '-' . $generacion->anio_egreso) : 'Sin generación';
    }

    private function logoNivel(Nivel $nivel): ?string
    {
        if ($nivel->logo) {
            $ruta = storage_path('app/public/logos/' . $nivel->logo);
            if (is_file($ruta)) return $this->imagenBase64($ruta);
        }
        return $this->imagenBase64(public_path('imagenes/logo-letra.png'));
    }

    private function imagenBase64(string $ruta): ?string
    {
        if (! is_file($ruta) || ! is_readable($ruta)) return null;
        $contenido = file_get_contents($ruta);
        if ($contenido === false) return null;
        return 'data:' . (mime_content_type($ruta) ?: 'image/png') . ';base64,' . base64_encode($contenido);
    }
}
