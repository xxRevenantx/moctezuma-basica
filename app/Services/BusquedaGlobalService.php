<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Constancia;
use App\Models\DocumentoAlumno;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Oficio;
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

        if (! $usuario->canAccess('administracion.acceder') || mb_strlen($termino) < 2) {
            return [];
        }

        $soloCalificaciones = Str::startsWith(mb_strtolower($termino), ['cal:', 'calificacion:', 'calificación:']);
        $soloHorarios = Str::startsWith(mb_strtolower($termino), ['hor:', 'horario:', 'horarios:']);

        $terminoCalificacion = $soloCalificaciones
            ? trim(Str::after($termino, ':'))
            : $termino;

        $terminoHorario = $soloHorarios
            ? trim(Str::after($termino, ':'))
            : $termino;

        $categorias = [];

        if ($soloHorarios) {
            $categorias[] = $this->categoria(
                'horarios-alumnos',
                'Horarios de alumnos',
                'calendar-days',
                $this->buscarHorariosAlumnos($terminoHorario)
            );

            $categorias[] = $this->categoria(
                'horarios-profesores',
                'Horarios de profesores',
                'clock',
                $this->buscarHorariosProfesores($terminoHorario)
            );
        } elseif (! $soloCalificaciones) {
            $categorias[] = $this->categoria(
                'alumnos',
                'Alumnos',
                'users',
                $this->buscarAlumnos($termino)
            );

            $categorias[] = $this->categoria(
                'horarios-alumnos',
                'Horarios de alumnos',
                'calendar-days',
                $this->buscarHorariosAlumnos($termino)
            );
        }

        if (! $soloHorarios) {
            $categorias[] = $this->categoria(
                'calificaciones',
                'Calificaciones',
                'academic-cap',
                $this->buscarCalificaciones($terminoCalificacion)
            );
        }

        if (! $soloCalificaciones && ! $soloHorarios) {
            $categorias[] = $this->categoria(
                'personal',
                'Personal',
                'briefcase',
                $this->buscarPersonal($termino)
            );

            $categorias[] = $this->categoria(
                'horarios-profesores',
                'Horarios de profesores',
                'clock',
                $this->buscarHorariosProfesores($termino)
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
                'expedientes',
                'Expedientes y documentos',
                'folder-open',
                $this->buscarDocumentos($termino)
            );

            $categorias[] = $this->categoria(
                'oficios',
                'Oficios',
                'envelope-open',
                $this->buscarOficios($termino)
            );

            $categorias[] = $this->categoria(
                'materias',
                'Materias',
                'book-open',
                $this->buscarMaterias($termino)
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
            ->filter(fn(array $categoria): bool => $categoria['resultados'] !== [])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarHorariosAlumnos(string $termino): array
    {
        $termino = trim($termino);

        if (mb_strlen($termino) < 2) {
            return [];
        }

        $like = $this->like($termino);

        return Inscripcion::query()
            ->select([
                'id',
                'matricula',
                'folio',
                'curp',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'nivel_id',
                'grado_id',
                'grupo_id',
                'generacion_id',
                'semestre_id',
                'estatus',
                'activo',
            ])
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->where('activo', true)
            ->whereHas('grupo.horarios')
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
            ->map(function (Inscripcion $alumno): ?array {
                $horario = Horario::query()
                    ->select(['id', 'grupo_id', 'ciclo_escolar_id'])
                    ->with('cicloEscolar:id,inicio_anio,fin_anio,es_actual')
                    ->where('grupo_id', $alumno->grupo_id)
                    ->orderByDesc('ciclo_escolar_id')
                    ->orderByDesc('id')
                    ->first();

                if (! $horario || ! $alumno->nivel?->slug) {
                    return null;
                }

                $nombre = $this->nombreCompleto(
                    $alumno->nombre,
                    $alumno->apellido_paterno,
                    $alumno->apellido_materno
                );

                $ciclo = $horario->cicloEscolar
                    ? $horario->cicloEscolar->inicio_anio . '-' . $horario->cicloEscolar->fin_anio
                    : null;

                return [
                    'tipo' => 'horario-alumno',
                    'titulo' => 'Horario de ' . $nombre,
                    'subtitulo' => collect([
                        $alumno->matricula ? 'Matrícula: ' . $alumno->matricula : null,
                        $ciclo ? 'Ciclo ' . $ciclo : null,
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $alumno->nivel?->nombre,
                        $alumno->grado?->nombre,
                        $alumno->grupo?->asignacionGrupo?->nombre
                            ? 'Grupo ' . $alumno->grupo->asignacionGrupo->nombre
                            : null,
                        $alumno->semestre?->numero
                            ? 'Semestre ' . $alumno->semestre->numero
                            : null,
                        $alumno->generacion?->etiqueta,
                    ])->filter()->join(' · '),
                    'estado' => 'Alumno',
                    'tono' => 'indigo',
                    'iniciales' => 'HA',
                    'url' => route('submodulos.accion', array_filter([
                        'slug_nivel' => $alumno->nivel->slug,
                        'accion' => 'horarios',
                        'generacion' => $alumno->generacion_id,
                        'grado' => $alumno->grado_id,
                        'grupo' => $alumno->grupo_id,
                        'semestre' => $alumno->semestre_id,
                        'ciclo_escolar' => $horario->ciclo_escolar_id,
                        'alumno' => $alumno->id,
                        'origen' => 'busqueda-global',
                    ], static fn($valor): bool => $valor !== null && $valor !== '')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarHorariosProfesores(string $termino): array
    {
        $termino = trim($termino);

        if (mb_strlen($termino) < 2) {
            return [];
        }

        $like = $this->like($termino);

        return Persona::query()
            ->select([
                'id',
                'titulo',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'curp',
                'rfc',
                'correo',
                'status',
                'estado_laboral',
            ])
            ->where('status', true)
            ->where(function (Builder $query): void {
                $query->whereHas('asignacionMaterias.horarios')
                    ->orWhereHas('tallerSesiones.horarios');
            })
            ->where(function (Builder $query) use ($like): void {
                $query->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhere('rfc', 'like', $like)
                    ->orWhere('correo', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        [$like]
                    )
                    ->orWhereHas('asignacionMaterias', function (Builder $asignacion) use ($like): void {
                        $asignacion->whereHas('horarios')
                            ->whereHas('materia', function (Builder $materia) use ($like): void {
                                $materia->where('materia', 'like', $like)
                                    ->orWhere('clave', 'like', $like);
                            });
                    })
                    ->orWhereHas('tallerSesiones', function (Builder $sesion) use ($like): void {
                        $sesion->whereHas('horarios')
                            ->whereHas('taller', function (Builder $taller) use ($like): void {
                                $taller->where('nombre', 'like', $like)
                                    ->orWhere('clave', 'like', $like);
                            });
                    });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Persona $profesor): ?array {
                $horario = Horario::query()
                    ->select([
                        'id',
                        'nivel_id',
                        'grado_id',
                        'grupo_id',
                        'ciclo_escolar_id',
                        'asignacion_materia_id',
                        'taller_sesion_id',
                    ])
                    ->with([
                        'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                        'nivel:id,nombre',
                        'grado:id,nombre',
                        'grupo:id,asignacion_grupo_id',
                        'grupo.asignacionGrupo:id,nombre',
                        'asignacionMateria:id,materia_id,profesor_id',
                        'asignacionMateria.materia:id,materia,clave',
                        'tallerSesion:id,taller_id,profesor_id',
                        'tallerSesion.taller:id,nombre,clave',
                    ])
                    ->where(function (Builder $query) use ($profesor): void {
                        $query->whereHas('asignacionMateria', fn(Builder $asignacion) => $asignacion
                            ->where('profesor_id', $profesor->id))
                            ->orWhereHas('tallerSesion', fn(Builder $sesion) => $sesion
                                ->where('profesor_id', $profesor->id));
                    })
                    ->orderByDesc('ciclo_escolar_id')
                    ->orderByDesc('id')
                    ->first();

                if (! $horario) {
                    return null;
                }

                $nombre = $this->nombreCompleto(
                    $profesor->nombre,
                    $profesor->apellido_paterno,
                    $profesor->apellido_materno
                );

                $actividad = $horario->asignacionMateria?->materia?->materia
                    ?? $horario->tallerSesion?->taller?->nombre;

                $ciclo = $horario->cicloEscolar
                    ? $horario->cicloEscolar->inicio_anio . '-' . $horario->cicloEscolar->fin_anio
                    : null;

                return [
                    'tipo' => 'horario-profesor',
                    'titulo' => 'Horario de ' . trim(collect([$profesor->titulo, $nombre])->filter()->join(' ')),
                    'subtitulo' => collect([
                        $ciclo ? 'Ciclo ' . $ciclo : null,
                        $actividad,
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $horario->nivel?->nombre,
                        $horario->grado?->nombre,
                        $horario->grupo?->asignacionGrupo?->nombre
                            ? 'Grupo ' . $horario->grupo->asignacionGrupo->nombre
                            : null,
                        $profesor->estado_laboral,
                    ])->filter()->join(' · '),
                    'estado' => 'Profesor',
                    'tono' => 'sky',
                    'iniciales' => 'HP',
                    'url' => route('misrutas.profesores', array_filter([
                        'seccion' => 'horario',
                        'profesor_id' => $profesor->id,
                        'ciclo_escolar_id' => $horario->ciclo_escolar_id,
                        'origen' => 'busqueda-global',
                    ], static fn($valor): bool => $valor !== null && $valor !== '')),
                ];
            })
            ->filter()
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
                'id',
                'matricula',
                'folio',
                'curp',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'nivel_id',
                'grado_id',
                'grupo_id',
                'generacion_id',
                'semestre_id',
                'estatus',
                'activo',
                'deleted_at',
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
                        $alumno->matricula ? 'Matrícula: ' . $alumno->matricula : null,
                        $alumno->folio ? 'Folio: ' . $alumno->folio : null,
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $alumno->nivel?->nombre,
                        $alumno->grado?->nombre,
                        $alumno->grupo?->asignacionGrupo?->nombre
                            ? 'Grupo ' . $alumno->grupo->asignacionGrupo->nombre
                            : null,
                        $alumno->semestre?->numero
                            ? 'Semestre ' . $alumno->semestre->numero
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
                'id',
                'inscripcion_id',
                'asignacion_materia_id',
                'nivel_id',
                'grado_id',
                'grupo_id',
                'ciclo_escolar_id',
                'generacion_id',
                'semestre_id',
                'periodo_id',
                'calificacion',
                'valor_numerico',
                'clave_especial',
                'observacion',
                'fecha_validacion',
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
                    ->orWhereHas('nivel', fn(Builder $nivel) => $nivel->where('nombre', 'like', $like))
                    ->orWhereHas('grado', fn(Builder $grado) => $grado->where('nombre', 'like', $like))
                    ->orWhereHas('grupo.asignacionGrupo', fn(Builder $grupo) => $grupo->where('nombre', 'like', $like))
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
                    ? $calificacion->cicloEscolar->inicio_anio . '-' . $calificacion->cicloEscolar->fin_anio
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
                        filled($valor) ? 'Calificación: ' . $valor : 'Sin calificación',
                    ])->filter()->join(' · '),
                    'detalle' => collect([
                        $alumno?->matricula ? 'Matrícula: ' . $alumno->matricula : null,
                        $calificacion->nivel?->nombre,
                        $calificacion->grado?->nombre,
                        $calificacion->grupo?->asignacionGrupo?->nombre
                            ? 'Grupo ' . $calificacion->grupo->asignacionGrupo->nombre
                            : null,
                        $calificacion->semestre?->numero
                            ? 'Semestre ' . $calificacion->semestre->numero
                            : null,
                        $periodoTexto,
                        $ciclo,
                    ])->filter()->join(' · '),
                    'estado' => $calificacion->fecha_validacion ? 'Validada' : 'Sin validar',
                    'tono' => $calificacion->fecha_validacion ? 'emerald' : 'violet',
                    'iniciales' => filled($valor) ? (string) $valor : '—',
                    'url' => route('submodulos.accion', array_filter(
                        $parametros,
                        static fn($valor): bool => $valor !== null && $valor !== ''
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
                'id',
                'titulo',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'curp',
                'rfc',
                'correo',
                'telefono_movil',
                'status',
                'estado_laboral',
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
                        $persona->curp ? 'CURP: ' . $this->ocultarCurp($persona->curp) : null,
                        $persona->rfc ? 'RFC: ' . $persona->rfc : null,
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
                'id',
                'curp',
                'parentesco',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'telefono_celular',
                'telefono_casa',
                'correo_electronico',
                'ciudad',
                'estado',
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
                        $tutor->inscripciones_count . ' alumno(s) relacionado(s)',
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
                'id',
                'inscripcion_id',
                'constancia_plantilla_id',
                'folio',
                'fecha_expedicion',
                'dirigido_a',
                'estado_documento',
                'cancelada_at',
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
                        $alumno?->matricula ? 'Matrícula: ' . $alumno->matricula : null,
                        $constancia->fecha_expedicion?->format('d/m/Y'),
                        $constancia->dirigido_a ? 'Dirigida a: ' . $constancia->dirigido_a : null,
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
    private function buscarDocumentos(string $termino): array
    {
        $like = $this->like($termino);

        return DocumentoAlumno::query()
            ->select([
                'id', 'inscripcion_id', 'tipo_documento_id', 'folio', 'nombre_original',
                'estado', 'fecha_documento', 'es_actual', 'version',
            ])
            ->with([
                'inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno',
                'tipoDocumento:id,nombre,slug',
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('folio', 'like', $like)
                    ->orWhere('nombre_original', 'like', $like)
                    ->orWhere('estado', 'like', $like)
                    ->orWhereHas('tipoDocumento', fn (Builder $tipo) => $tipo->where('nombre', 'like', $like))
                    ->orWhereHas('inscripcion', function (Builder $alumno) use ($like): void {
                        $alumno->where('matricula', 'like', $like)
                            ->orWhere('curp', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                                [$like]
                            );
                    });
            })
            ->orderByDesc('es_actual')
            ->orderByDesc('id')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (DocumentoAlumno $documento): array {
                $alumno = $documento->inscripcion;
                $nombre = $this->nombreCompleto(
                    $alumno?->nombre,
                    $alumno?->apellido_paterno,
                    $alumno?->apellido_materno
                );

                return [
                    'tipo' => 'documento',
                    'titulo' => $documento->tipoDocumento?->nombre ?: ($documento->nombre_original ?: 'Documento de expediente'),
                    'subtitulo' => collect([$nombre, $alumno?->matricula])->filter()->join(' · '),
                    'detalle' => collect([
                        $documento->folio ? 'Folio: '.$documento->folio : null,
                        $documento->fecha_documento?->format('d/m/Y'),
                        'Versión '.$documento->version,
                    ])->filter()->join(' · '),
                    'estado' => Str::headline((string) ($documento->estado ?: 'pendiente')),
                    'tono' => $documento->estado === 'validado' ? 'emerald' : ($documento->estado === 'rechazado' ? 'rose' : 'amber'),
                    'iniciales' => 'EX',
                    'url' => $alumno ? route('misrutas.expedientes.show', ['inscripcion' => $alumno->id]) : route('misrutas.expedientes'),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarOficios(string $termino): array
    {
        $like = $this->like($termino);

        return Oficio::query()
            ->select(['id', 'inscripcion_id', 'folio', 'tipo_oficio', 'seccion', 'fecha_lugar', 'asunto', 'dirigido_1_nombre'])
            ->with('inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno')
            ->where(function (Builder $query) use ($like): void {
                $query->where('folio', 'like', $like)
                    ->orWhere('tipo_oficio', 'like', $like)
                    ->orWhere('asunto', 'like', $like)
                    ->orWhere('dirigido_1_nombre', 'like', $like)
                    ->orWhereHas('inscripcion', function (Builder $alumno) use ($like): void {
                        $alumno->where('matricula', 'like', $like)
                            ->orWhereRaw(
                                "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                                [$like]
                            );
                    });
            })
            ->orderByDesc('id')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Oficio $oficio): array {
                $alumno = $oficio->inscripcion;
                $nombre = $this->nombreCompleto(
                    $alumno?->nombre,
                    $alumno?->apellido_paterno,
                    $alumno?->apellido_materno
                );

                return [
                    'tipo' => 'oficio',
                    'titulo' => $oficio->folio ?: Str::headline((string) ($oficio->tipo_oficio ?: 'Oficio')),
                    'subtitulo' => collect([$oficio->asunto, $nombre])->filter()->join(' · '),
                    'detalle' => collect([$oficio->dirigido_1_nombre, $oficio->fecha_lugar])->filter()->join(' · '),
                    'estado' => 'Oficio',
                    'tono' => 'sky',
                    'iniciales' => 'OF',
                    'url' => route('misrutas.oficios', ['buscar' => $oficio->folio ?: $nombre]),
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buscarMaterias(string $termino): array
    {
        $like = $this->like($termino);

        return Materia::query()
            ->select(['id', 'nivel_id', 'grado_id', 'semestre_id', 'materia', 'clave', 'calificable', 'extra'])
            ->with(['nivel:id,nombre', 'grado:id,nombre', 'semestre:id,numero'])
            ->withCount('asignaciones')
            ->where(function (Builder $query) use ($like): void {
                $query->where('materia', 'like', $like)
                    ->orWhere('clave', 'like', $like)
                    ->orWhereHas('nivel', fn (Builder $nivel) => $nivel->where('nombre', 'like', $like))
                    ->orWhereHas('grado', fn (Builder $grado) => $grado->where('nombre', 'like', $like));
            })
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Materia $materia): array {
                return [
                    'tipo' => 'materia',
                    'titulo' => $materia->materia,
                    'subtitulo' => collect([
                        $materia->clave,
                        $materia->nivel?->nombre,
                        $materia->grado?->nombre,
                        $materia->semestre?->numero ? 'Semestre '.$materia->semestre->numero : null,
                    ])->filter()->join(' · '),
                    'detalle' => $materia->asignaciones_count.' asignación(es)',
                    'estado' => $materia->extra ? 'Extra' : ($materia->calificable ? 'Calificable' : 'No calificable'),
                    'tono' => $materia->calificable ? 'emerald' : 'slate',
                    'iniciales' => $this->iniciales($materia->materia),
                    'url' => route('misrutas.materias', ['buscar' => $materia->materia]),
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
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->with([
                'asignacionGrupo:id,nombre',
                'nivel:id,nombre',
                'grado:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn(Builder $query) => $query
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->whereHas('asignacionGrupo', fn(Builder $grupo) => $grupo->where('nombre', 'like', $like))
                    ->orWhereHas('nivel', fn(Builder $nivel) => $nivel->where('nombre', 'like', $like))
                    ->orWhereHas('grado', fn(Builder $grado) => $grado->where('nombre', 'like', $like))
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
                        'Grupo ' . $nombreGrupo,
                    ])->filter()->join(' · '),
                    'subtitulo' => collect([
                        $grupo->generacion?->etiqueta,
                        $grupo->semestre?->numero ? 'Semestre ' . $grupo->semestre->numero : null,
                    ])->filter()->join(' · '),
                    'detalle' => $grupo->alumnos_activos_count . ' alumno(s) activo(s)',
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
                'id',
                'nivel_id',
                'nombre',
                'anio_ingreso',
                'anio_egreso',
                'status',
                'fecha_inicio',
                'fecha_termino',
            ])
            ->with('nivel:id,nombre')
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn(Builder $query) => $query
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ])
            ->where(function (Builder $query) use ($like): void {
                $query->where('nombre', 'like', $like)
                    ->orWhere('anio_ingreso', 'like', $like)
                    ->orWhere('anio_egreso', 'like', $like)
                    ->orWhereRaw("CONCAT(anio_ingreso, '-', anio_egreso) LIKE ?", [$like])
                    ->orWhereHas('nivel', fn(Builder $nivel) => $nivel->where('nombre', 'like', $like));
            })
            ->orderByDesc('anio_ingreso')
            ->limit(self::LIMITE_POR_CATEGORIA)
            ->get()
            ->map(function (Generacion $generacion): array {
                return [
                    'tipo' => 'generacion',
                    'titulo' => 'Generación ' . $generacion->etiqueta,
                    'subtitulo' => $generacion->nivel?->nombre ?: 'Nivel no disponible',
                    'detalle' => collect([
                        $generacion->alumnos_activos_count . ' alumno(s) activo(s)',
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
        return '%' . addcslashes($termino, '\\%_') . '%';
    }

    private function nombreCompleto(?string $nombre, ?string $paterno, ?string $materno): string
    {
        return collect([$nombre, $paterno, $materno])
            ->map(fn(?string $parte): string => trim((string) $parte))
            ->filter()
            ->join(' ');
    }

    private function iniciales(string $texto): string
    {
        return Str::of($texto)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn(string $palabra): string => mb_strtoupper(mb_substr($palabra, 0, 1)))
            ->implode('');
    }

    private function ocultarCurp(string $curp): string
    {
        if (mb_strlen($curp) <= 8) {
            return $curp;
        }

        return mb_substr($curp, 0, 4) . '••••••' . mb_substr($curp, -4);
    }
}
