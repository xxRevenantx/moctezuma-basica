<?php

namespace App\Services;

use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListaGeneracionesHistoricasService
{
    public const ESTATUS = [
        'todos',
        'egresado',
        'activo',
        'reingreso',
        'no_promovido',
        'baja_temporal',
        'baja_definitiva',
        'trasladado',
        'suspendido',
        'inactivo',
    ];

    public function generar(
        Nivel $nivel,
        array $generacionIds,
        string $estatus = 'egresado',
        ?int $grupoId = null,
        bool $incluirArchivados = false,
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

        abort_if(
            $generaciones->count() !== $generacionIds->count(),
            422,
            'Una o más generaciones no pertenecen al nivel seleccionado.'
        );

        if ($grupoId) {
            $grupoValido = Grupo::query()
                ->where('id', $grupoId)
                ->where('nivel_id', $nivel->id)
                ->whereIn('generacion_id', $generacionIds)
                ->exists();

            abort_unless($grupoValido, 422, 'El grupo no pertenece a las generaciones seleccionadas.');
        }

        $consulta = Inscripcion::query()
            ->with([
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status,motivo_desactivacion,fecha_termino',
                'grado:id,nivel_id,nombre,orden',
                'semestre',
                'grupo:id,nivel_id,grado_id,generacion_id,semestre_id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
            ])
            ->where('nivel_id', $nivel->id)
            ->whereIn('generacion_id', $generacionIds)
            ->when($grupoId, fn (Builder $query) => $query->where('grupo_id', $grupoId))
            ->when($estatus !== 'todos', fn (Builder $query) => $query->where('estatus', $estatus))
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');

        if ($incluirArchivados) {
            $consulta->withTrashed();
        }

        $alumnos = $consulta->get();

        $bloquesGeneracion = $generaciones->map(function (Generacion $generacion) use ($alumnos): array {
            $alumnosGeneracion = $alumnos
                ->where('generacion_id', $generacion->id)
                ->values();

            $grupos = $alumnosGeneracion
                ->groupBy(fn (Inscripcion $alumno) => (int) ($alumno->grupo_id ?: 0))
                ->map(function (Collection $alumnosGrupo): array {
                    /** @var Inscripcion $primero */
                    $primero = $alumnosGrupo->first();

                    return [
                        'id' => (int) ($primero?->grupo_id ?: 0),
                        'titulo' => $this->tituloGrupo($primero),
                        'orden' => sprintf(
                            '%04d|%04d|%s',
                            (int) ($primero?->grado?->orden ?? 9999),
                            (int) ($primero?->semestre?->numero ?? $primero?->semestre_id ?? 0),
                            mb_strtolower((string) ($primero?->grupo?->asignacionGrupo?->nombre ?? 'zzzz'))
                        ),
                        'alumnos' => $alumnosGrupo
                            ->map(fn (Inscripcion $alumno) => $this->filaAlumno($alumno))
                            ->values(),
                        'resumen' => $this->resumen($alumnosGrupo),
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
                'resumen' => $this->resumen($alumnosGeneracion),
            ];
        })->values();

        return [
            'nivel' => $nivel,
            'titulo' => 'LISTAS HISTÓRICAS DE ALUMNOS POR GENERACIÓN',
            'subtitulo' => 'Padrón institucional de generaciones activas y egresadas',
            'generaciones' => $bloquesGeneracion,
            'resumen' => $this->resumen($alumnos),
            'estatus' => $estatus,
            'estatus_etiqueta' => $this->estatusEtiqueta($estatus),
            'incluir_archivados' => $incluirArchivados,
            'grupo_id' => $grupoId,
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

        if ($generaciones->count() === 1) {
            $generacion = $generaciones->first();

            return sprintf(
                '%s_%s_Generacion_%s-%s',
                $prefijo,
                $nivel,
                $generacion['anio_ingreso'],
                $generacion['anio_egreso']
            );
        }

        return sprintf(
            'Listas_Historicas_%s_%d_Generaciones',
            $nivel,
            $generaciones->count()
        );
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

    private function filaAlumno(Inscripcion $alumno): array
    {
        $nombre = collect([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])->filter()->implode(' ');

        $estatus = $this->estatusEtiqueta((string) $alumno->estatus);

        if ($alumno->trashed()) {
            $estatus .= ' · Archivado';
        }

        return [
            'id' => (int) $alumno->id,
            'matricula' => $alumno->matricula ?: '—',
            'nombre' => mb_strtoupper($nombre),
            'curp' => mb_strtoupper((string) $alumno->curp),
            'genero' => $alumno->genero === 'M' ? 'Mujer' : 'Hombre',
            'generacion' => $this->etiquetaGeneracion($alumno->generacion),
            'grupo' => $this->grupoAlumno($alumno),
            'estatus' => $estatus,
            'estatus_clave' => (string) $alumno->estatus,
            'fecha_egreso' => $alumno->estatus === 'egresado' && $alumno->fecha_estatus
                ? $alumno->fecha_estatus->format('d/m/Y')
                : '—',
            'archivado' => $alumno->trashed(),
        ];
    }

    private function resumen(Collection $alumnos): array
    {
        $total = $alumnos->count();
        $bajas = $alumnos->whereIn('estatus', ['baja_temporal', 'baja_definitiva'])->count();
        $activos = $alumnos->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])->count();
        $egresados = $alumnos->where('estatus', 'egresado')->count();
        $trasladados = $alumnos->where('estatus', 'trasladado')->count();

        return [
            'total' => $total,
            'hombres' => $alumnos->where('genero', 'H')->count(),
            'mujeres' => $alumnos->where('genero', 'M')->count(),
            'activos' => $activos,
            'egresados' => $egresados,
            'bajas' => $bajas,
            'trasladados' => $trasladados,
            'archivados' => $alumnos->filter(fn (Inscripcion $alumno) => $alumno->trashed())->count(),
            'otros' => max(0, $total - $activos - $egresados - $bajas - $trasladados),
        ];
    }

    private function tituloGrupo(?Inscripcion $alumno): string
    {
        if (!$alumno) {
            return 'SIN GRUPO';
        }

        $partes = [];

        if ($alumno->grado?->nombre) {
            $partes[] = $alumno->grado->nombre . '° GRADO';
        }

        if ($alumno->semestre) {
            $numero = $alumno->semestre->numero ?? $alumno->semestre_id;
            $partes[] = 'SEMESTRE ' . $numero;
        }

        $partes[] = 'GRUPO ' . ($alumno->grupo?->asignacionGrupo?->nombre ?? 'SIN ASIGNAR');

        return mb_strtoupper(implode(' · ', $partes));
    }

    private function grupoAlumno(Inscripcion $alumno): string
    {
        $partes = [];

        if ($alumno->grado?->nombre) {
            $partes[] = $alumno->grado->nombre . '°';
        }

        if ($alumno->semestre) {
            $partes[] = 'Sem. ' . ($alumno->semestre->numero ?? $alumno->semestre_id);
        }

        $partes[] = $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo';

        return implode(' · ', $partes);
    }

    private function etiquetaGeneracion(?Generacion $generacion): string
    {
        if (!$generacion) {
            return 'Sin generación';
        }

        return $generacion->nombre ?: $generacion->anio_ingreso . '-' . $generacion->anio_egreso;
    }

    private function logoNivel(Nivel $nivel): ?string
    {
        if ($nivel->logo) {
            $ruta = storage_path('app/public/logos/' . $nivel->logo);

            if (is_file($ruta)) {
                return $this->imagenBase64($ruta);
            }
        }

        return $this->imagenBase64(public_path('imagenes/logo-letra.png'));
    }

    private function imagenBase64(string $ruta): ?string
    {
        if (!is_file($ruta) || !is_readable($ruta)) {
            return null;
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }
}
