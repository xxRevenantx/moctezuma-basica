<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Constancia;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Persona;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BusquedaGlobalService
{
    private const LIMITE_POR_CATEGORIA = 6;

    /**
     * @return array<int, array{
     *     clave:string,
     *     titulo:string,
     *     icono:string,
     *     resultados:array<int, array<string, mixed>>
     * }>
     */
    public function buscar(string $termino, User $usuario): array
    {
        $termino = trim(preg_replace('/\s+/u', ' ', $termino) ?? '');

        if (! $usuario->is_admin || mb_strlen($termino) < 2) {
            return [];
        }

        $soloCalificaciones = Str::startsWith(mb_strtolower($termino), ['cal:', 'calificacion:', 'calificación:']);
        $terminoCalificacion = $soloCalificaciones
            ? trim(Str::after($termino, ':'))
            : $termino;

        $categorias = [];

        if (! $soloCalificaciones) {
            $categorias[] = $this->categoria(
                'alumnos',
                'Alumnos',
                'users',
                $this->buscarAlumnos($termino)
            );
        }

        $categorias[] = $this->categoria(
            'calificaciones',
            'Calificaciones',
            'academic-cap',
            $this->buscarCalificaciones($terminoCalificacion)
        );

        if (! $soloCalificaciones) {
            $categorias[] = $this->categoria(
                'personal',
                'Personal',
                'briefcase',
                $this->buscarPersonal($termino)
            );

            $categorias[] = $this->categoria(
                'tutores',
                'Tutores',
                'user-group',
                $this->buscarTutores($termino)
            );

            $categorias[] = $this->categoria(
                'constancias',
                'Constancias y folios',
                'document-text',
                $this->buscarConstancias($termino)
            );

            $categorias[] = $this->categoria(
                'grupos',
                'Grupos',
                'rectangle-group',
                $this->buscarGrupos($termino)
            );

            $categorias[] = $this->categoria(
                'generaciones',
                'Generaciones',
                'calendar-days',
                $this->buscarGeneraciones($termino)
            );
        }

        return collect($categorias)
            ->filter(fn (array $categoria): bool => $categoria['resultados'] !== [])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function categoria(string $clave, string $titulo, string $icono, array $resultados): array
    {
        return compact('clave', 'titulo', 'icono', 'resultados');
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarAlumnos(string $termino): array
    {
        $like = $this->like($termino);

        return Inscripcion::query()
            ->withTrashed()
            ->select([
                'id', 'matricula', 'folio', 'curp', 'nombre', 'apellido_paterno',
                'apellido_materno', 'nivel_id', 'grado_id', 'grupo_id',
                'generacion_id', 'semestre_id', 'estatus', 'activo', 'deleted_at',
            ])
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('matricula', 'like', $like)
                    ->orWhere('folio', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        [$like]
                    );
            })
            ->orderByRaw(
                'CASE WHEN matricula = ? THEN 0 WHEN curp = ? THEN 1 WHEN folio = ? THEN 2 ELSE 3 END',
                [$termino, $termino, $termino]
            )
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Inscripcion $alumno): array {
                $nombre = $this->nombreCompleto(
                    $alumno->nombre,
                    $alumno->apellido_paterno,
                    $alumno->apellido_materno
                );

                $estado = $alumno->deleted_at
                    ? 'Eliminado'
                    : Str::headline((string) ($alumno->estatus ?: ($alumno->activo ? 'activo' : 'inactivo')));

                return [
                    'tipo' => 'alumno',
                    'titulo' => $nombre,
                    'subtitulo' => collect([
                        $alumno->matricula ? 'Matrícula: '.$alumno->matricula : null,
                        $alumno->folio ? 'Folio: '.$alumno->folio : null,
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $alumno->nivel?->nombre,
                        $alumno->grado?->nombre,
                        $alumno->grupo?->asignacionGrupo?->nombre
                            ? 'Grupo '.$alumno->grupo->asignacionGrupo->nombre
                            : null,
                        $alumno->semestre?->numero
                            ? 'Semestre '.$alumno->semestre->numero
                            : null,
                        $alumno->generacion?->etiqueta,
                    ])->filter()->join(' · '),
                    'estado' => $estado,
                    'tono' => $alumno->activo && ! $alumno->deleted_at ? 'emerald' : 'amber',
                    'iniciales' => $this->iniciales($nombre),
                    'url' => route('misrutas.expedientes.show', ['inscripcion' => $alumno->id]),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarCalificaciones(string $termino): array
    {
        $termino = trim($termino);

        if ($termino === '') {
            return [];
        }

        $like = $this->like($termino);
        $esNumero = is_numeric($termino);

        return Calificacion::query()
            ->select([
                'id', 'inscripcion_id', 'asignacion_materia_id', 'nivel_id',
                'grado_id', 'grupo_id', 'ciclo_escolar_id', 'generacion_id',
                'semestre_id', 'periodo_id', 'calificacion', 'valor_numerico',
                'clave_especial', 'observacion', 'fecha_validacion',
            ])
            ->with([
                'inscripcion:id,matricula,curp,nombre,apellido_paterno,apellido_materno',
                'asignacionMateria:id,materia_id',
                'asignacionMateria.materia:id,materia,clave',
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,numero',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'periodo:id,periodo_basica_id,parcial_bachillerato_id,mes_bachillerato_id',
                'periodo.periodoBasica:id,periodo,descripcion',
                'periodo.parcialBachillerato:id,parcial,descripcion',
                'periodo.mesesBachillerato:id,meses',
            ])
            ->where(function (Builder $query) use ($like, $termino, $esNumero): void {
                $query->where('calificacion', 'like', $like)
                    ->orWhere('clave_especial', 'like', $like)
                    ->orWhere('observacion', 'like', $like)
                    ->orWhereHas('inscripcion', function (Builder $alumno) use ($like): void {
                        $alumno->where('matricula', 'like', $like)
                            ->orWhere('curp', 'like', $like)
                            ->orWhere('nombre', 'like', $like)
                            ->orWhere('apellido_paterno', 'like', $like)
                            ->orWhere('apellido_materno', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                                [$like]
                            )
                            ->orWhereRaw(
                                "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                                [$like]
                            );
                    })
                    ->orWhereHas('asignacionMateria.materia', function (Builder $materia) use ($like): void {
                        $materia->where('materia', 'like', $like)
                            ->orWhere('clave', 'like', $like);
                    })
                    ->orWhereHas('nivel', fn (Builder $nivel) => $nivel->where('nombre', 'like', $like))
                    ->orWhereHas('grado', fn (Builder $grado) => $grado->where('nombre', 'like', $like))
                    ->orWhereHas('grupo.asignacionGrupo', fn (Builder $grupo) => $grupo->where('nombre', 'like', $like))
                    ->orWhereHas('generacion', function (Builder $generacion) use ($like): void {
                        $generacion->where('nombre', 'like', $like)
                            ->orWhereRaw("CONCAT(anio_ingreso, '-', anio_egreso) LIKE ?", [$like]);
                    })
                    ->orWhereHas('cicloEscolar', function (Builder $ciclo) use ($like): void {
                        $ciclo->whereRaw("CONCAT(inicio_anio, '-', fin_anio) LIKE ?", [$like]);
                    });

                if ($esNumero) {
                    $query->orWhere('valor_numerico', '=', (float) $termino);
                }
            })
            ->orderByDesc('id')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Calificacion $calificacion): array {
                $alumno = $calificacion->inscripcion;
                $materia = $calificacion->asignacionMateria?->materia;
                $periodo = $calificacion->periodo;

                $nombre = $this->nombreCompleto(
                    $alumno?->nombre,
                    $alumno?->apellido_paterno,
                    $alumno?->apellido_materno
                );

                $valor = filled($calificacion->clave_especial)
                    ? $calificacion->clave_especial
                    : $calificacion->calificacion;

                $periodoTexto = $periodo?->periodoBasica?->descripcion
                    ?? $periodo?->parcialBachillerato?->descripcion
                    ?? $periodo?->mesesBachillerato?->meses;

                $ciclo = $calificacion->cicloEscolar
                    ? $calificacion->cicloEscolar->inicio_anio.'-'.$calificacion->cicloEscolar->fin_anio
                    : null;

                $parametros = [
                    'slug_nivel' => $calificacion->nivel?->slug,
                    'accion' => 'calificaciones',
                    'generacion' => $calificacion->generacion_id,
                    'grado' => $calificacion->grado_id,
                    'grupo' => $calificacion->grupo_id,
                    'semestre' => $calificacion->semestre_id,
                    'periodo' => $calificacion->periodo_id,
                    'periodo_basica' => $periodo?->periodo_basica_id,
                    'parcial' => $periodo?->parcial_bachillerato_id,
                    'alumno' => $calificacion->inscripcion_id,
                    'buscar' => $alumno?->matricula,
                    'origen' => 'busqueda-global',
                ];

                return [
                    'tipo' => 'calificacion',
                    'titulo' => $nombre ?: 'Alumno no disponible',
                    'subtitulo' => collect([
                        $materia?->materia,
                        filled($valor) ? 'Calificación: '.$valor : 'Sin calificación',
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $alumno?->matricula ? 'Matrícula: '.$alumno->matricula : null,
                        $calificacion->nivel?->nombre,
                        $calificacion->grado?->nombre,
                        $calificacion->grupo?->asignacionGrupo?->nombre
                            ? 'Grupo '.$calificacion->grupo->asignacionGrupo->nombre
                            : null,
                        $calificacion->semestre?->numero
                            ? 'Semestre '.$calificacion->semestre->numero
                            : null,
                        $periodoTexto,
                        $ciclo,
                    ])->filter()->join(' · '),
                    'estado' => $calificacion->fecha_validacion ? 'Validada' : 'Sin validar',
                    'tono' => $calificacion->fecha_validacion ? 'emerald' : 'violet',
                    'iniciales' => filled($valor) ? (string) $valor : '—',
                    'url' => route('submodulos.accion', array_filter(
                        $parametros,
                        static fn ($valor): bool => $valor !== null && $valor !== ''
                    )),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarPersonal(string $termino): array
    {
        $like = $this->like($termino);

        return Persona::query()
            ->select([
                'id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno',
                'curp', 'rfc', 'correo', 'telefono_movil', 'status', 'estado_laboral',
            ])
            ->with('rolesPersona:id,nombre')
            ->where(function (Builder $query) use ($like): void {
                $query->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhere('rfc', 'like', $like)
                    ->orWhere('correo', 'like', $like)
                    ->orWhere('telefono_movil', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        [$like]
                    );
            })
            ->orderByDesc('status')
            ->orderBy('apellido_paterno')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Persona $persona): array {
                $nombre = $this->nombreCompleto(
                    $persona->nombre,
                    $persona->apellido_paterno,
                    $persona->apellido_materno
                );

                return [
                    'tipo' => 'personal',
                    'titulo' => trim(collect([$persona->titulo, $nombre])->filter()->join(' ')),
                    'subtitulo' => collect([
                        $persona->rolesPersona->pluck('nombre')->join(', '),
                        $persona->estado_laboral,
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $persona->curp ? 'CURP: '.$this->ocultarCurp($persona->curp) : null,
                        $persona->rfc ? 'RFC: '.$persona->rfc : null,
                        $persona->correo,
                    ])->filter()->join(' · '),
                    'estado' => $persona->status ? 'Activo' : 'Inactivo',
                    'tono' => $persona->status ? 'sky' : 'slate',
                    'iniciales' => $this->iniciales($nombre),
                    'url' => route('misrutas.expedientes-personal.show', ['persona' => $persona->id]),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarTutores(string $termino): array
    {
        $like = $this->like($termino);

        return Tutor::query()
            ->select([
                'id', 'curp', 'parentesco', 'nombre', 'apellido_paterno',
                'apellido_materno', 'telefono_celular', 'telefono_casa',
                'correo_electronico', 'ciudad', 'estado',
            ])
            ->withCount('inscripciones')
            ->where(function (Builder $query) use ($like): void {
                $query->where('curp', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('parentesco', 'like', $like)
                    ->orWhere('telefono_celular', 'like', $like)
                    ->orWhere('telefono_casa', 'like', $like)
                    ->orWhere('correo_electronico', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        [$like]
                    );
            })
            ->orderBy('apellido_paterno')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Tutor $tutor): array {
                $nombre = $this->nombreCompleto(
                    $tutor->nombre,
                    $tutor->apellido_paterno,
                    $tutor->apellido_materno
                );

                return [
                    'tipo' => 'tutor',
                    'titulo' => $nombre,
                    'subtitulo' => collect([
                        $tutor->parentesco,
                        $tutor->inscripciones_count.' alumno(s) relacionado(s)',
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $tutor->telefono_celular ?: $tutor->telefono_casa,
                        $tutor->correo_electronico,
                        collect([$tutor->ciudad, $tutor->estado])->filter()->join(', '),
                    ])->filter()->join(' · '),
                    'estado' => 'Tutor',
                    'tono' => 'amber',
                    'iniciales' => $this->iniciales($nombre),
                    'url' => route('misrutas.tutores', ['buscar' => $tutor->curp]),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarConstancias(string $termino): array
    {
        $like = $this->like($termino);

        return Constancia::query()
            ->select([
                'id', 'inscripcion_id', 'constancia_plantilla_id', 'folio',
                'fecha_expedicion', 'dirigido_a', 'estado_documento', 'cancelada_at',
            ])
            ->with([
                'inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno',
                'plantilla:id,titulo,clave',
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('folio', 'like', $like)
                    ->orWhere('dirigido_a', 'like', $like)
                    ->orWhereHas('inscripcion', function (Builder $alumno) use ($like): void {
                        $alumno->where('matricula', 'like', $like)
                            ->orWhere('nombre', 'like', $like)
                            ->orWhere('apellido_paterno', 'like', $like)
                            ->orWhere('apellido_materno', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                                [$like]
                            );
                    })
                    ->orWhereHas('plantilla', function (Builder $plantilla) use ($like): void {
                        $plantilla->where('titulo', 'like', $like)
                            ->orWhere('clave', 'like', $like);
                    });
            })
            ->orderByDesc('fecha_expedicion')
            ->orderByDesc('id')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Constancia $constancia): array {
                $alumno = $constancia->inscripcion;
                $nombre = $this->nombreCompleto(
                    $alumno?->nombre,
                    $alumno?->apellido_paterno,
                    $alumno?->apellido_materno
                );
                $cancelada = filled($constancia->cancelada_at)
                    || $constancia->estado_documento === 'cancelada';

                return [
                    'tipo' => 'constancia',
                    'titulo' => $constancia->folio ?: 'Constancia sin folio',
                    'subtitulo' => collect([
                        $constancia->plantilla?->titulo ?: 'Constancia',
                        $nombre,
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $alumno?->matricula ? 'Matrícula: '.$alumno->matricula : null,
                        $constancia->fecha_expedicion?->format('d/m/Y'),
                        $constancia->dirigido_a ? 'Dirigida a: '.$constancia->dirigido_a : null,
                    ])->filter()->join(' · '),
                    'estado' => $cancelada ? 'Cancelada' : Str::headline((string) ($constancia->estado_documento ?: 'vigente')),
                    'tono' => $cancelada ? 'rose' : 'emerald',
                    'iniciales' => 'F',
                    'url' => route('misrutas.constancias', [
                        'buscar_constancia' => $constancia->folio ?: $nombre,
                    ]),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarGrupos(string $termino): array
    {
        $like = $this->like($termino);

        return Grupo::query()
            ->select([
                'id', 'asignacion_grupo_id', 'nivel_id', 'grado_id',
                'generacion_id', 'semestre_id',
            ])
            ->with([
                'asignacionGrupo:id,nombre',
                'nivel:id,nombre',
                'grado:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn (Builder $query) => $query
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->whereHas('asignacionGrupo', fn (Builder $grupo) => $grupo->where('nombre', 'like', $like))
                    ->orWhereHas('nivel', fn (Builder $nivel) => $nivel->where('nombre', 'like', $like))
                    ->orWhereHas('grado', fn (Builder $grado) => $grado->where('nombre', 'like', $like))
                    ->orWhereHas('generacion', function (Builder $generacion) use ($like): void {
                        $generacion->where('nombre', 'like', $like)
                            ->orWhereRaw("CONCAT(anio_ingreso, '-', anio_egreso) LIKE ?", [$like]);
                    });
            })
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Grupo $grupo): array {
                $nombreGrupo = $grupo->asignacionGrupo?->nombre ?: 'Sin nombre';

                return [
                    'tipo' => 'grupo',
                    'titulo' => collect([
                        $grupo->nivel?->nombre,
                        $grupo->grado?->nombre,
                        'Grupo '.$nombreGrupo,
                    ])->filter()->join(' · '),
                    'subtitulo' => collect([
                        $grupo->generacion?->etiqueta,
                        $grupo->semestre?->numero ? 'Semestre '.$grupo->semestre->numero : null,
                    ])->filter()->join(' · '),
                    'detalle' => $grupo->alumnos_activos_count.' alumno(s) activo(s)',
                    'estado' => 'Grupo',
                    'tono' => 'indigo',
                    'iniciales' => mb_strtoupper(mb_substr($nombreGrupo, 0, 2)),
                    'url' => route('misrutas.grupos', [
                        'nivel' => $grupo->nivel_id,
                        'generacion' => $grupo->generacion_id,
                        'grado' => $grupo->grado_id,
                        'semestre' => $grupo->semestre_id,
                        'buscar' => $nombreGrupo,
                    ]),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarGeneraciones(string $termino): array
    {
        $like = $this->like($termino);

        return Generacion::query()
            ->select([
                'id', 'nivel_id', 'nombre', 'anio_ingreso', 'anio_egreso',
                'status', 'fecha_inicio', 'fecha_termino',
            ])
            ->with('nivel:id,nombre')
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn (Builder $query) => $query
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('nombre', 'like', $like)
                    ->orWhere('anio_ingreso', 'like', $like)
                    ->orWhere('anio_egreso', 'like', $like)
                    ->orWhereRaw("CONCAT(anio_ingreso, '-', anio_egreso) LIKE ?", [$like])
                    ->orWhereHas('nivel', fn (Builder $nivel) => $nivel->where('nombre', 'like', $like));
            })
            ->orderByDesc('anio_ingreso')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Generacion $generacion): array {
                return [
                    'tipo' => 'generacion',
                    'titulo' => 'Generación '.$generacion->etiqueta,
                    'subtitulo' => $generacion->nivel?->nombre ?: 'Nivel no disponible',
                    'detalle' => collect([
                        $generacion->alumnos_activos_count.' alumno(s) activo(s)',
                        $generacion->fecha_inicio?->format('d/m/Y'),
                        $generacion->fecha_termino?->format('d/m/Y'),
                    ])->filter()->join(' · '),
                    'estado' => $generacion->status ? 'Activa' : 'Inactiva',
                    'tono' => $generacion->status ? 'emerald' : 'slate',
                    'iniciales' => (string) $generacion->anio_ingreso,
                    'url' => route('misrutas.generaciones', [
                        'buscar' => $generacion->etiqueta,
                        'inactivas' => 1,
                    ]),
                ];
            })
            ->all();
    }

    private function like(string $termino): string
    {
        return '%'.addcslashes($termino, '\\%_').'%';
    }

    private function nombreCompleto(?string $nombre, ?string $paterno, ?string $materno): string
    {
        return collect([$nombre, $paterno, $materno])
            ->map(fn (?string $parte): string => trim((string) $parte))
            ->filter()
            ->join(' ');
    }

    private function iniciales(string $texto): string
    {
        return Str::of($texto)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $palabra): string => mb_strtoupper(mb_substr($palabra, 0, 1)))
            ->implode('');
    }

    private function ocultarCurp(string $curp): string
    {
        if (mb_strlen($curp) <= 8) {
            return $curp;
        }

        return mb_substr($curp, 0, 4).'••••••'.mb_substr($curp, -4);
    }
}
