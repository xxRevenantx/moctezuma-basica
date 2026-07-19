<?php

namespace App\Services\MediaSuperior;

use App\Models\AsignacionMateria;
use App\Models\AsistenciaFinalBachillerato;
use App\Models\Calificacion;
use App\Models\ConfiguracionMediaSuperior;
use App\Models\Escuela;
use App\Models\FirmanteMediaSuperior;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\Semestre;
use App\Models\CicloEscolar;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentosOficialesService
{
    public const TIPO_REGISTRO = 'registro-escolaridad';
    public const TIPO_ACTA = 'acta-resultados';
    public const TIPO_KARDEX = 'kardex';
    public const TIPO_HISTORIAL = 'historial-academico';
    public const TIPO_CERTIFICADO = 'certificado';

    public function nivel(): Nivel
    {
        return Nivel::query()
            ->with('director')
            ->where(function (Builder $query): void {
                $query->where('slug', 'bachillerato')
                    ->orWhere('id', ReglasMateriaBachillerato::NIVEL_ID);
            })
            ->firstOrFail();
    }

    public function ciclos(): Collection
    {
        return CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
    }

    public function generaciones(?int $cicloId = null): Collection
    {
        $nivel = $this->nivel();
        $ciclo = $cicloId ? CicloEscolar::query()->find($cicloId) : null;

        return Generacion::query()
            ->where('nivel_id', $nivel->id)
            ->when($ciclo, function (Builder $query) use ($ciclo): void {
                $query->where(function (Builder $corte) use ($ciclo): void {
                    $corte->where(function (Builder $vigencia) use ($ciclo): void {
                        $vigencia->where('anio_ingreso', '<=', $ciclo->fin_anio)
                            ->where('anio_egreso', '>=', $ciclo->inicio_anio);
                    })
                        ->orWhereHas('grupos', fn(Builder $grupo) => $grupo->whereHas(
                            'calificaciones',
                            fn(Builder $calificacion) => $calificacion->where('ciclo_escolar_id', $ciclo->id)
                        ))
                        ->orWhereHas('grupos', fn(Builder $grupo) => $grupo->whereHas(
                            'asignacionMaterias',
                            fn(Builder $asignacion) => $asignacion->where('ciclo_escolar_id', $ciclo->id)
                        ));
                });
            })
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'nombre', 'anio_ingreso', 'anio_egreso', 'status']);
    }

    public function semestres(?int $generacionId = null, ?int $cicloId = null): Collection
    {
        $nivel = $this->nivel();

        $ids = Grupo::query()
            ->where('nivel_id', $nivel->id)
            ->when($generacionId, fn(Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($cicloId, function (Builder $query) use ($cicloId): void {
                $query->where(function (Builder $sub) use ($cicloId): void {
                    $sub->whereHas('calificaciones', fn(Builder $c) => $c->where('ciclo_escolar_id', $cicloId))
                        ->orWhereHas('asignacionMaterias', fn(Builder $a) => $a->where('ciclo_escolar_id', $cicloId));
                });
            })
            ->whereNotNull('semestre_id')
            ->pluck('semestre_id')
            ->unique();

        if (($generacionId || $cicloId) && $ids->isEmpty()) {
            return collect();
        }

        return Semestre::query()
            ->when($ids->isNotEmpty(), fn(Builder $q) => $q->whereIn('id', $ids))
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global']);
    }

    public function grupos(?int $generacionId = null, ?int $semestreId = null, ?int $cicloId = null): Collection
    {
        $nivel = $this->nivel();

        return Grupo::query()
            ->with(['asignacionGrupo:id,nombre', 'grado:id,nombre', 'semestre:id,numero', 'generacion:id,anio_ingreso,anio_egreso,nombre'])
            ->where('nivel_id', $nivel->id)
            ->when($generacionId, fn(Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn(Builder $q) => $q->where('semestre_id', $semestreId))
            ->when($cicloId, function (Builder $query) use ($cicloId): void {
                $query->where(function (Builder $sub) use ($cicloId): void {
                    $sub->whereHas('calificaciones', fn(Builder $c) => $c->where('ciclo_escolar_id', $cicloId))
                        ->orWhereHas('asignacionMaterias', fn(Builder $a) => $a->where('ciclo_escolar_id', $cicloId));
                });
            })
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('asignacion_grupo_id')
            ->get();
    }

    public function alumnos(?int $generacionId = null, ?int $grupoId = null, string $buscar = ''): Collection
    {
        $nivel = $this->nivel();

        return Inscripcion::withTrashed()
            ->with(['generacion:id,anio_ingreso,anio_egreso,nombre', 'semestre:id,numero', 'grupo.asignacionGrupo:id,nombre'])
            ->where('nivel_id', $nivel->id)
            ->when($generacionId, fn(Builder $q) => $q->where('generacion_id', $generacionId))
            ->when($grupoId, function (Builder $q) use ($grupoId): void {
                $q->where(function (Builder $grupoQuery) use ($grupoId): void {
                    $grupoQuery->where('grupo_id', $grupoId)
                        ->orWhereHas('calificaciones', fn(Builder $calificacion) => $calificacion->where('grupo_id', $grupoId));
                });
            })
            ->when(trim($buscar) !== '', function (Builder $query) use ($buscar): void {
                $termino = '%' . trim($buscar) . '%';
                $query->where(function (Builder $sub) use ($termino): void {
                    $sub->where('nombre', 'like', $termino)
                        ->orWhere('apellido_paterno', 'like', $termino)
                        ->orWhere('apellido_materno', 'like', $termino)
                        ->orWhere('matricula', 'like', $termino)
                        ->orWhere('curp', 'like', $termino)
                        ->orWhere('folio', 'like', $termino);
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    public function asignaciones(int $cicloId, int $grupoId, ?int $semestreId = null, bool $soloOficiales = false): Collection
    {
        $query = AsignacionMateria::query()
            ->with(['materia', 'profesor', 'grupo.semestre'])
            ->where('ciclo_escolar_id', $cicloId)
            ->where('grupo_id', $grupoId)
            ->where('nivel_id', $this->nivel()->id)
            ->where(function (Builder $estado): void {
                $estado->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
                    ->orWhereHas('calificaciones');
            })
            ->when($semestreId, fn(Builder $q) => $q->where('semestre_id', $semestreId));

        if ($soloOficiales) {
            $query->whereHas('materia', function (Builder $materia): void {
                ReglasMateriaBachillerato::aplicarPromediables($materia, '');
            });
        } else {
            $query->whereHas('materia', function (Builder $materia): void {
                ReglasMateriaBachillerato::aplicarCapturables($materia, '');
            });
        }

        return $query
            ->orderBy('orden')
            ->orderBy('materia_id')
            ->get();
    }

    public function institucional(int $cicloId): array
    {
        $nivel = $this->nivel();
        $escuela = Escuela::query()->first();
        $config = ConfiguracionMediaSuperior::query()->where('nivel_id', $nivel->id)->first();
        $ciclo = CicloEscolar::query()->findOrFail($cicloId);

        $direccion = collect([
            $escuela?->calle,
            $escuela?->no_exterior ? 'No. ' . $escuela->no_exterior : null,
            $escuela?->colonia ? 'Col. ' . $escuela->colonia : null,
        ])->filter()->implode(', ');

        return [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'configuracion' => $config,
            'ciclo' => $ciclo,
            'plantel' => $config?->nombre_plantel_oficial ?: $escuela?->nombre ?: 'Centro Universitario Moctezuma',
            'cct' => $nivel->cct,
            'numero_acuerdo' => $config?->numero_acuerdo,
            'fecha_acuerdo' => $config?->fecha_acuerdo,
            'fecha_acuerdo_texto' => $this->fechaAcuerdoTexto($config?->fecha_acuerdo),
            'modalidad' => $config?->modalidad ?: 'Escolarizada',
            'turno' => $config?->turno ?: 'Matutino',
            'calificacion_minima' => (float) ($config?->calificacion_minima ?? 5),
            'calificacion_maxima' => (float) ($config?->calificacion_maxima ?? 10),
            'minima_aprobatoria' => (float) ($config?->minima_aprobatoria ?? 6),
            'direccion' => $direccion,
            'calle' => $escuela?->calle,
            'numero' => $escuela?->no_exterior,
            'colonia' => $escuela?->colonia,
            'codigo_postal' => $escuela?->codigo_postal,
            'ciudad' => $escuela?->ciudad,
            'municipio' => $escuela?->municipio,
            'estado' => $escuela?->estado,
            'regional' => $escuela?->regional,
            'localidad_expedicion' => $config?->localidad_expedicion
                ?: collect([$escuela?->ciudad, $escuela?->estado])->filter()->implode(', '),
            'logo_seg' => $this->rutaPublica($config?->logo_seg_path, 'imagenes/logo-seg.png'),
            'logo_plantel' => $this->rutaPublica($config?->logo_plantel_path, 'imagenes/logo-letra.png'),
            'logo_certificado' => is_file(public_path('logo.png'))
                ? public_path('logo.png')
                : $this->rutaPublica($config?->logo_plantel_path, 'imagenes/logo-letra.png'),
            'texto_certificado' => $config?->texto_certificado ?: $this->textoCertificadoPredeterminado(),
            'leyenda_certificado' => $config?->leyenda_certificado ?: $this->leyendaCertificadoPredeterminada(),
            'mostrar_materias_extra' => (bool) ($config?->mostrar_materias_extra ?? true),
            'firmantes' => [
                'director' => $this->resolverFirmante(FirmanteMediaSuperior::ROL_DIRECTOR, $cicloId, $nivel),
                'control_escolar' => $this->resolverFirmante(FirmanteMediaSuperior::ROL_CONTROL_ESCOLAR, $cicloId, $nivel),
                'jefe_registro' => $this->resolverFirmante(FirmanteMediaSuperior::ROL_JEFE_REGISTRO, $cicloId, $nivel),
            ],
        ];
    }

    public function registroEscolaridad(array $filtros): array
    {
        $contexto = $this->contextoGrupo($filtros);
        $alumnos = $this->alumnosDelContexto($contexto, $filtros['estatus'] ?? 'todos');
        $oficiales = $this->asignaciones($contexto['ciclo']->id, $contexto['grupo']->id, $contexto['semestre']->id, true);
        $resultados = $this->resultados($alumnos->pluck('id'), $oficiales, $contexto['ciclo']->id);

        $filas = $alumnos->values()->map(function (Inscripcion $alumno, int $indice) use ($oficiales, $resultados): array {
            $materias = $oficiales->map(function (AsignacionMateria $asignacion) use ($alumno, $resultados): array {
                return $resultados->get($alumno->id . '|' . $asignacion->id, $this->resultadoVacio($asignacion));
            })->values();

            $noAcreditadas = $materias->filter(fn(array $m) => $m['completa'] && !$m['acreditada'])->count();
            $incompletas = $materias->where('completa', false)->count();

            return [
                'numero' => $indice + 1,
                'alumno' => $alumno,
                'nombre' => $this->nombreAlumno($alumno),
                'sexo' => $alumno->genero,
                'matricula' => $alumno->matricula,
                'materias' => $materias,
                'asignaturas_no_acreditadas' => $noAcreditadas,
                'situacion_escolar' => $this->situacionEscolar($alumno, $noAcreditadas, $incompletas),
            ];
        });

        return array_merge($contexto, [
            'institucional' => $this->institucional($contexto['ciclo']->id),
            'asignaciones' => $oficiales,
            'filas' => $filas,
            'estadistica' => [
                'hombres' => $alumnos->where('genero', 'H')->count(),
                'mujeres' => $alumnos->where('genero', 'M')->count(),
                'total' => $alumnos->count(),
            ],
            'diagnostico' => [
                'sin_materias' => $oficiales->isEmpty(),
                'sin_alumnos' => $alumnos->isEmpty(),
                'calificaciones_pendientes' => $filas->sum(fn(array $f) => $f['materias']->where('completa', false)->count()),
            ],
        ]);
    }

    public function alumnosActa(array $filtros): Collection
    {
        $contexto = $this->contextoGrupo($filtros);

        return $this->alumnosDelContexto($contexto, (string) ($filtros['estatus'] ?? 'todos'));
    }

    public function actaResultados(array $filtros): array
    {
        $contexto = $this->contextoGrupo($filtros);
        $asignacion = AsignacionMateria::query()
            ->with(['materia', 'profesor'])
            ->whereKey((int) ($filtros['asignacion_materia_id'] ?? 0))
            ->where('ciclo_escolar_id', $contexto['ciclo']->id)
            ->where('grupo_id', $contexto['grupo']->id)
            ->firstOrFail();

        if (!ReglasMateriaBachillerato::esPromediable($asignacion->materia)) {
            throw new RuntimeException('El acta oficial solo puede generarse para una materia oficial de bachillerato.');
        }

        $alumnos = $this->alumnosDelContexto($contexto, $filtros['estatus'] ?? 'todos');
        $resultados = $this->resultados($alumnos->pluck('id'), collect([$asignacion]), $contexto['ciclo']->id);
        $asistencias = AsistenciaFinalBachillerato::query()
            ->where('ciclo_escolar_id', $contexto['ciclo']->id)
            ->where('asignacion_materia_id', $asignacion->id)
            ->whereIn('inscripcion_id', $alumnos->pluck('id'))
            ->get()
            ->keyBy('inscripcion_id');

        $filas = $alumnos->values()->map(function (Inscripcion $alumno, int $indice) use ($asignacion, $resultados, $asistencias): array {
            $resultado = $resultados->get($alumno->id . '|' . $asignacion->id, $this->resultadoVacio($asignacion));
            $valorEntero = $resultado['valor_entero'];

            return [
                'numero' => $indice + 1,
                'alumno' => $alumno,
                'matricula' => $alumno->matricula,
                'nombre' => $this->nombreAlumno($alumno),
                'resultado' => $resultado,
                'calificacion_numero' => $resultado['valor'],
                'calificacion_letra' => $valorEntero === null ? '' : $this->numeroEnteroEnLetra($valorEntero),
                'asistencia' => optional($asistencias->get($alumno->id))->porcentaje,
                'acreditado' => $resultado['completa'] ? ($resultado['acreditada'] ? 'SÍ' : 'NO') : '',
            ];
        });

        $institucional = $this->institucional($contexto['ciclo']->id);
        $institucional['firmantes']['profesor'] = [
            'nombre' => $this->nombrePersona($asignacion->profesor),
            'cargo' => 'PROFESOR(A)',
            'configurado' => (bool) $asignacion->profesor,
        ];

        return array_merge($contexto, [
            'institucional' => $institucional,
            'asignacion' => $asignacion,
            'filas' => $filas,
            'diagnostico' => [
                'sin_alumnos' => $alumnos->isEmpty(),
                'calificaciones_pendientes' => $filas->where('resultado.completa', false)->count(),
                'asistencias_pendientes' => $filas->filter(fn(array $f) => $f['asistencia'] === null)->count(),
                'profesor_pendiente' => !$asignacion->profesor,
            ],
        ]);
    }

    public function kardex(int $inscripcionId): array
    {
        $nivel = $this->nivel();
        $alumno = Inscripcion::withTrashed()
            ->with(['nivel', 'generacion', 'grado', 'grupo.asignacionGrupo', 'semestre'])
            ->where('nivel_id', $nivel->id)
            ->findOrFail($inscripcionId);

        $calificaciones = Calificacion::query()
            ->with([
                'periodo.parcialBachillerato',
                'asignacionMateria.materia',
                'asignacionMateria.cicloEscolar',
                'asignacionMateria.semestre',
            ])
            ->where('inscripcion_id', $alumno->id)
            ->where('nivel_id', $nivel->id)
            ->whereHas('asignacionMateria.materia', function (Builder $materia): void {
                ReglasMateriaBachillerato::aplicarCapturables($materia, '');
            })
            ->get();

        // Cada contexto conserva ciclo, grupo y semestre. Esto evita mezclar materias
        // cuando un alumno repite un semestre o cambia de grupo.
        $contextos = $calificaciones
            ->map(fn(Calificacion $calificacion): array => [
                'ciclo_escolar_id' => (int) $calificacion->ciclo_escolar_id,
                'grupo_id' => (int) $calificacion->grupo_id,
                'semestre_id' => (int) $calificacion->semestre_id,
            ])
            ->filter(fn(array $contexto): bool => $contexto['ciclo_escolar_id'] > 0
                && $contexto['grupo_id'] > 0
                && $contexto['semestre_id'] > 0)
            ->unique(fn(array $contexto): string => implode('|', $contexto))
            ->values();

        // Si el alumno está actualmente inscrito en un grupo, se incorpora la carga
        // más reciente aun cuando todavía no tenga ninguna calificación capturada.
        if ($alumno->grupo_id && $alumno->semestre_id) {
            $asignacionActual = AsignacionMateria::query()
                ->where('nivel_id', $nivel->id)
                ->where('grupo_id', $alumno->grupo_id)
                ->where('semestre_id', $alumno->semestre_id)
                ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
                ->orderByDesc('ciclo_escolar_id')
                ->orderByDesc('id')
                ->first(['ciclo_escolar_id', 'grupo_id', 'semestre_id']);

            if ($asignacionActual) {
                $contextos->push([
                    'ciclo_escolar_id' => (int) $asignacionActual->ciclo_escolar_id,
                    'grupo_id' => (int) $asignacionActual->grupo_id,
                    'semestre_id' => (int) $asignacionActual->semestre_id,
                ]);
                $contextos = $contextos
                    ->unique(fn(array $contexto): string => implode('|', $contexto))
                    ->values();
            }
        }

        $asignaciones = collect();

        if ($contextos->isNotEmpty()) {
            $idsConCalificacion = $calificaciones->pluck('asignacion_materia_id')->filter()->unique()->values();

            $asignaciones = AsignacionMateria::query()
                ->with(['materia', 'cicloEscolar', 'semestre', 'grupo.asignacionGrupo'])
                ->where('nivel_id', $nivel->id)
                ->where(function (Builder $query) use ($contextos): void {
                    foreach ($contextos as $contexto) {
                        $query->orWhere(function (Builder $sub) use ($contexto): void {
                            $sub->where('ciclo_escolar_id', $contexto['ciclo_escolar_id'])
                                ->where('grupo_id', $contexto['grupo_id'])
                                ->where('semestre_id', $contexto['semestre_id']);
                        });
                    }
                })
                ->whereHas('materia', function (Builder $materia): void {
                    ReglasMateriaBachillerato::aplicarCapturables($materia, '');
                })
                ->where(function (Builder $query) use ($alumno): void {
                    $query->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
                        ->orWhereHas('calificaciones', fn(Builder $calificacion) => $calificacion
                            ->where('inscripcion_id', $alumno->id));
                })
                ->get()
                ->sort(function (AsignacionMateria $a, AsignacionMateria $b) use ($idsConCalificacion): int {
                    $prioridadA = [
                        $idsConCalificacion->contains($a->id) ? 1 : 0,
                        $a->estado !== AsignacionMateria::ESTADO_ARCHIVADA ? 1 : 0,
                        (int) $a->id,
                    ];
                    $prioridadB = [
                        $idsConCalificacion->contains($b->id) ? 1 : 0,
                        $b->estado !== AsignacionMateria::ESTADO_ARCHIVADA ? 1 : 0,
                        (int) $b->id,
                    ];

                    return $prioridadB <=> $prioridadA;
                })
                // Si existe más de una carga de la misma materia en el mismo corte,
                // se conserva la que tiene calificaciones; de lo contrario la más reciente.
                ->unique(fn(AsignacionMateria $asignacion): string => implode('|', [
                    $asignacion->ciclo_escolar_id,
                    $asignacion->grupo_id,
                    $asignacion->semestre_id,
                    $asignacion->materia_id,
                ]))
                ->values();
        }

        $resultados = $this->resultados(collect([$alumno->id]), $asignaciones, null, $calificaciones);
        $asistencias = AsistenciaFinalBachillerato::query()
            ->where('inscripcion_id', $alumno->id)
            ->when($asignaciones->isNotEmpty(), fn(Builder $query) => $query
                ->whereIn('asignacion_materia_id', $asignaciones->pluck('id')))
            ->get()
            ->keyBy(fn(AsistenciaFinalBachillerato $asistencia): string =>
                $asistencia->asignacion_materia_id . '|' . $asistencia->ciclo_escolar_id);

        $semestres = $asignaciones
            ->groupBy(fn(AsignacionMateria $asignacion): string => implode('|', [
                $asignacion->ciclo_escolar_id,
                $asignacion->grupo_id,
                $asignacion->semestre_id,
            ]))
            ->map(function (Collection $items, string $claveContexto) use ($alumno, $resultados, $asistencias): array {
                $primera = $items->first();
                $semestre = $primera?->semestre ?: Semestre::query()->find($primera?->semestre_id);
                $agregarAsistencia = function (AsignacionMateria $asignacion) use ($alumno, $resultados, $asistencias): array {
                    $resultado = $resultados->get(
                        $alumno->id . '|' . $asignacion->id,
                        $this->resultadoVacio($asignacion),
                    );
                    $asistencia = $asistencias->get($asignacion->id . '|' . $asignacion->ciclo_escolar_id);
                    $resultado['asistencia'] = $asistencia?->porcentaje;

                    return $resultado;
                };

                $oficiales = $items
                    ->filter(fn(AsignacionMateria $asignacion) => ReglasMateriaBachillerato::esPromediable($asignacion->materia))
                    ->sortBy(fn(AsignacionMateria $asignacion) => [
                        $asignacion->orden,
                        $asignacion->materia?->orden,
                        $asignacion->materia?->materia,
                    ])
                    ->map($agregarAsistencia)
                    ->values();
                $extras = $items
                    ->filter(fn(AsignacionMateria $asignacion) => ReglasMateriaBachillerato::esExtraInformativa($asignacion->materia))
                    ->sortBy(fn(AsignacionMateria $asignacion) => [
                        $asignacion->orden,
                        $asignacion->materia?->orden,
                        $asignacion->materia?->materia,
                    ])
                    ->map($agregarAsistencia)
                    ->values();
                $numeroConfigurado = (int) (MateriaPromediar::query()
                    ->where('nivel_id', $primera?->nivel_id)
                    ->where('grado_id', $primera?->grado_id ?: $primera?->grupo?->grado_id)
                    ->where('semestre_id', $primera?->semestre_id)
                    ->value('numero_materias') ?? 0);
                $materiasEsperadas = $numeroConfigurado > 0 ? $numeroConfigurado : $oficiales->count();
                $catalogoConsistente = $materiasEsperadas > 0 && $oficiales->count() === $materiasEsperadas;
                $completo = $catalogoConsistente
                    && $oficiales->every(fn(array $materia) => $materia['completa']);
                $acreditado = $completo
                    && $oficiales->every(fn(array $materia) => $materia['acreditada']);
                // Los documentos oficiales de bachillerato manejan la calificación
                // final de cada materia como entero truncado, nunca redondeado.
                // El promedio documental se calcula con esos enteros para que sea
                // consistente con las calificaciones impresas en Kardex y Certificado.
                $promedio = $completo
                    ? ((float) $oficiales->sum('valor_entero') / $materiasEsperadas)
                    : null;

                return [
                    'clave_contexto' => $claveContexto,
                    'semestre' => $semestre,
                    'numero' => (int) ($semestre?->numero ?? 0),
                    'ciclo' => $primera?->cicloEscolar,
                    'ciclo_id' => (int) ($primera?->ciclo_escolar_id ?? 0),
                    'grupo' => $primera?->grupo,
                    'grupo_id' => (int) ($primera?->grupo_id ?? 0),
                    'oficiales' => $oficiales,
                    'extras' => $extras,
                    'materias_esperadas' => $materiasEsperadas,
                    'materias_oficiales' => $oficiales->count(),
                    'numero_materias_configurado' => $numeroConfigurado > 0 ? $numeroConfigurado : null,
                    'catalogo_consistente' => $catalogoConsistente,
                    'completo' => $completo,
                    'acreditado' => $acreditado,
                    'promedio_preciso' => $promedio,
                    'promedio' => PromedioExcel::formatear($promedio, 1, '—'),
                ];
            })
            ->sort(function (array $a, array $b): int {
                return [$a['numero'], $a['ciclo_id'], $a['grupo_id']]
                    <=> [$b['numero'], $b['ciclo_id'], $b['grupo_id']];
            })
            ->values();

        // Para promedios y certificados se toma un solo intento por semestre: el
        // intento completo más reciente. El kardex conserva visibles todos los intentos.
        $semestresVigentes = $semestres
            ->groupBy('numero')
            ->map(function (Collection $intentos): array {
                $completos = $intentos->where('completo', true);

                return ($completos->isNotEmpty() ? $completos : $intentos)
                    ->sortByDesc('ciclo_id')
                    ->first();
            })
            ->sortBy('numero')
            ->values();

        $promedios = $semestresVigentes->where('completo', true)->pluck('promedio_preciso');
        $promedioGeneral = $promedios->isNotEmpty() ? PromedioExcel::calcular($promedios) : null;
        $ultimoSemestre = $semestres->sortByDesc('ciclo_id')->first();
        $cicloReferencia = is_array($ultimoSemestre) ? ($ultimoSemestre['ciclo_id'] ?? null) : null;
        $cicloReferencia = $cicloReferencia
            ?: CicloEscolar::query()->where('es_actual', true)->value('id')
            ?: CicloEscolar::query()->max('id');

        return [
            'institucional' => $this->institucional((int) $cicloReferencia),
            'alumno' => $alumno,
            'semestres' => $semestres,
            'semestres_vigentes' => $semestresVigentes,
            'promedio_general_preciso' => $promedioGeneral,
            'promedio_general' => PromedioExcel::formatear($promedioGeneral, 1, '—'),
            'semestres_completos' => $semestresVigentes->where('completo', true)->count(),
            'semestres_acreditados' => $semestresVigentes->where('acreditado', true)->count(),
            'diagnostico' => [
                'sin_historial' => $semestres->isEmpty(),
                'semestres_incompletos' => $semestresVigentes
                    ->where('completo', false)
                    ->pluck('numero')
                    ->unique()
                    ->values(),
                'materias_no_acreditadas' => $semestresVigentes->sum(fn(array $semestre) => $semestre['oficiales']->filter(
                    fn(array $materia) => $materia['completa'] && !$materia['acreditada']
                )->count()),
                'catalogos_inconsistentes' => $semestresVigentes
                    ->where('catalogo_consistente', false)
                    ->map(fn(array $semestre) => [
                        'semestre' => $semestre['numero'],
                        'esperadas' => $semestre['materias_esperadas'],
                        'encontradas' => $semestre['materias_oficiales'],
                    ])
                    ->values(),
            ],
        ];
    }


    public function historialAcademico(
        int $inscripcionId,
        string $modo = 'completo',
        bool $mostrarFoto = false,
        bool $incluirFirmasDigitales = true,
    ): array {
        $datos = $this->kardex($inscripcionId);
        $modo = $modo === 'cursado' ? 'cursado' : 'completo';
        $vigentes = $datos['semestres_vigentes']->keyBy('numero');

        $consultaPlan = Materia::query()
            ->with('semestre:id,numero')
            ->where('nivel_id', $this->nivel()->id)
            ->whereHas('semestre', fn (Builder $query) => $query->whereBetween('numero', [1, 6]));
        // El historial académico muestra únicamente materias oficiales.
        ReglasMateriaBachillerato::aplicarPromediables($consultaPlan, '');

        $plan = $consultaPlan
            ->orderBy('semestre_id')
            ->orderBy('orden')
            ->orderBy('materia')
            ->get()
            ->groupBy(fn (Materia $materia): int => (int) ($materia->semestre?->numero ?? 0));

        $semestres = collect(range(1, 6))
            ->map(function (int $numero) use ($vigentes, $plan, $datos, $modo): ?array {
                $registrado = $vigentes->get($numero);

                if ($registrado) {
                    return array_merge($registrado, [
                        'extras' => collect(),
                        'incluido' => true,
                        'ciclo_texto' => $registrado['ciclo']?->nombre
                            ?: $this->cicloEstimado($datos['alumno'], $numero),
                    ]);
                }

                if ($modo === 'cursado') {
                    return null;
                }

                $materias = $plan->get($numero, collect());
                $convertir = fn (Materia $materia): array => [
                    'asignacion' => null,
                    'materia' => $materia,
                    'clave' => $materia->clave,
                    'nombre' => $materia->materia,
                    'creditos_certificados' => $materia->creditos_certificados,
                    'parcial_1' => null,
                    'parcial_2' => null,
                    'completa' => false,
                    'valor_preciso' => null,
                    'valor_entero' => null,
                    'valor' => '',
                    'acreditada' => false,
                    'extra' => ReglasMateriaBachillerato::esExtraInformativa($materia),
                    'asistencia' => null,
                ];
                $oficiales = $materias
                    ->filter(fn (Materia $materia) => ReglasMateriaBachillerato::esPromediable($materia))
                    ->map($convertir)
                    ->values();
                return [
                    'clave_contexto' => 'plan|' . $numero,
                    'semestre' => $materias->first()?->semestre,
                    'numero' => $numero,
                    'ciclo' => null,
                    'ciclo_texto' => $this->cicloEstimado($datos['alumno'], $numero),
                    'grupo' => null,
                    'oficiales' => $oficiales,
                    'extras' => collect(),
                    'materias_esperadas' => $oficiales->count(),
                    'materias_oficiales' => $oficiales->count(),
                    'numero_materias_configurado' => null,
                    'catalogo_consistente' => $oficiales->isNotEmpty(),
                    'completo' => false,
                    'acreditado' => false,
                    'promedio_preciso' => null,
                    'promedio' => '—',
                    'incluido' => false,
                ];
            })
            ->filter()
            ->values();

        $oficiales = $semestres->flatMap(fn (array $semestre) => $semestre['oficiales']);
        $materiasConCalificacion = $oficiales->where('completa', true);
        $materiasAcreditadas = $materiasConCalificacion->where('acreditada', true);
        $materiasNoAcreditadas = $materiasConCalificacion->where('acreditada', false);
        $asistencias = $materiasConCalificacion->pluck('asistencia')->filter(fn ($valor) => $valor !== null);
        $firmantesHistorial = collect(data_get($datos, 'institucional.firmantes', []));
        $firmasDigitalesPendientes = collect([
            'director' => 'Director(a) del plantel',
            'jefe_registro' => 'Jefe del Departamento de Registro y Certificación',
        ])->flatMap(function (string $etiqueta, string $rol) use ($firmantesHistorial): array {
            $firmante = (array) $firmantesHistorial->get($rol, []);
            $pendientes = [];
            if (blank($firmante['firma_ruta'] ?? null)) {
                $pendientes[] = "Firma de {$etiqueta}";
            }
            if (blank($firmante['sello_ruta'] ?? null)) {
                $pendientes[] = "Sello de {$etiqueta}";
            }

            return $pendientes;
        })->values()->all();

        return array_merge($datos, [
            'modo_historial' => $modo,
            'mostrar_foto' => $mostrarFoto,
            'incluir_firmas_digitales' => $incluirFirmasDigitales,
            'foto_data_uri' => $mostrarFoto ? $datos['alumno']->foto_data_uri : null,
            'semestres_historial' => $semestres,
            'semestres_pagina_1' => $semestres->whereIn('numero', [1, 2, 3, 4])->values(),
            'semestres_pagina_2' => $semestres->whereIn('numero', [5, 6])->values(),
            'diagnostico' => array_merge($datos['diagnostico'] ?? [], [
                'firmas_digitales_pendientes' => $firmasDigitalesPendientes,
            ]),
            'resumen_historial' => [
                'materias_plan' => $oficiales->count(),
                'materias_evaluadas' => $materiasConCalificacion->count(),
                'materias_acreditadas' => $materiasAcreditadas->count(),
                'materias_no_acreditadas' => $materiasNoAcreditadas->count(),
                'asistencia_promedio' => $asistencias->isNotEmpty()
                    ? (int) floor((float) $asistencias->avg())
                    : null,
                'situacion' => $materiasConCalificacion->isEmpty()
                    ? 'SIN REGISTROS'
                    : ($materiasNoAcreditadas->isEmpty() ? 'REGULAR' : 'IRREGULAR'),
            ],
        ]);
    }

    private function cicloEstimado(Inscripcion $alumno, int $numeroSemestre): string
    {
        $ingreso = (int) ($alumno->generacion?->anio_ingreso ?? 0);

        if ($ingreso <= 0) {
            return '—';
        }

        $inicio = $ingreso + intdiv(max(0, $numeroSemestre - 1), 2);

        return $inicio . '-' . ($inicio + 1);
    }

    public function certificado(int $inscripcionId, string $modalidad = 'parcial'): array
    {
        $kardex = $this->kardex($inscripcionId);
        $modalidad = $modalidad === 'definitivo' ? 'definitivo' : 'parcial';
        $acreditados = $kardex['semestres_vigentes']->where('acreditado', true)->sortBy('numero')->values();
        $definitivoDisponible = $acreditados->pluck('numero')->unique()->sort()->values()->all() === [1, 2, 3, 4, 5, 6];
        $parcialDisponible = $acreditados->isNotEmpty();

        if ($modalidad === 'definitivo' && ! $definitivoDisponible) {
            throw new RuntimeException('El certificado definitivo requiere los seis semestres completos y acreditados.');
        }

        if ($modalidad === 'parcial' && ! $parcialDisponible) {
            throw new RuntimeException('El certificado parcial requiere por lo menos un semestre completo y acreditado.');
        }

        if (blank($kardex['alumno']->folio)) {
            throw new RuntimeException('El alumno no tiene folio en inscripciones. Captura el folio antes de emitir el certificado.');
        }

        $folioDuplicado = Inscripcion::withTrashed()
            ->where('folio', $kardex['alumno']->folio)
            ->where('id', '!=', $kardex['alumno']->id)
            ->exists();

        if ($folioDuplicado) {
            throw new RuntimeException('El folio de inscripciones está asignado a más de un alumno. Corrige la duplicidad antes de emitir el certificado.');
        }

        $nivel = $this->nivel();
        $consultaPlan = Materia::query()
            ->with('semestre:id,numero')
            ->where('nivel_id', $nivel->id)
            ->whereHas('semestre', fn (Builder $query) => $query->whereBetween('numero', [1, 6]));
        ReglasMateriaBachillerato::aplicarPromediables($consultaPlan, '');

        $materiasPlan = $consultaPlan
            ->orderBy('semestre_id')
            ->orderBy('orden')
            ->orderBy('materia')
            ->get();

        $semestresSinPlan = collect(range(1, 6))
            ->reject(fn (int $numero) => $materiasPlan->contains(
                fn (Materia $materia) => (int) $materia->semestre?->numero === $numero
            ))
            ->values();

        if ($semestresSinPlan->isNotEmpty()) {
            throw new RuntimeException(
                'El plan de estudios está incompleto. No hay materias oficiales configuradas para: '
                . $semestresSinPlan->map(fn (int $numero) => $numero . '° semestre')->implode(', ')
                . '.'
            );
        }

        $sinCreditos = $materiasPlan
            ->filter(fn (Materia $materia) => ! is_numeric($materia->creditos_certificados)
                || (float) $materia->creditos_certificados <= 0)
            ->values();

        if ($sinCreditos->isNotEmpty()) {
            $detalle = $sinCreditos
                ->take(10)
                ->map(fn (Materia $materia) => $materia->materia . ' (' . ($materia->semestre?->numero ?: '?') . '°)')
                ->implode(', ');
            $faltantes = $sinCreditos->count() > 10 ? ' y ' . ($sinCreditos->count() - 10) . ' más' : '';

            throw new RuntimeException(
                'No se puede emitir el certificado porque faltan créditos en materias oficiales: '
                . $detalle . $faltantes . '. Captúralos en el catálogo de materias.'
            );
        }

        $semestresIncluidos = $acreditados;
        $materias = $semestresIncluidos->flatMap(fn (array $semestre) => $semestre['oficiales'])->values();
        $promedio = PromedioExcel::calcular($semestresIncluidos->pluck('promedio_preciso'));

        $semestresMatriz = collect(range(1, 6))->map(function (int $numero) use ($semestresIncluidos): array {
            $semestre = $semestresIncluidos->firstWhere('numero', $numero);

            if ($semestre) {
                return array_merge($semestre, [
                    'incluido' => true,
                ]);
            }

            return [
                'numero' => $numero,
                'ciclo' => null,
                'oficiales' => collect(),
                'extras' => collect(),
                'incluido' => false,
                'promedio' => '—',
                'promedio_preciso' => null,
            ];
        })->values();

        $creditosPlan = (float) $materiasPlan->sum(fn (Materia $materia) => (float) $materia->creditos_certificados);
        $creditosAcreditados = (float) $materias->sum(
            fn (array $materia) => (float) ($materia['materia']?->creditos_certificados ?? 0)
        );

        $institucional = $kardex['institucional'];
        $alumno = $kardex['alumno'];
        $nombreAlumno = Str::upper(trim(implode(' ', array_filter([
            $alumno->nombre,
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
        ]))));
        $acreditacion = $modalidad === 'definitivo' ? 'TOTALMENTE' : 'PARCIALMENTE';

        $textoCertificado = strtr((string) $institucional['texto_certificado'], [
            '{NOMBRE}' => $nombreAlumno,
            '{CURP}' => Str::upper((string) $alumno->curp),
            '{ACREDITACION}' => $acreditacion,
            '{PLANTEL}' => Str::upper((string) $institucional['plantel']),
            '{ACUERDO}' => Str::upper((string) ($institucional['numero_acuerdo'] ?: 'PENDIENTE DE CONFIGURAR')),
            '{FECHA_ACUERDO}' => Str::upper((string) ($institucional['fecha_acuerdo_texto'] ?: 'PENDIENTE DE CONFIGURAR')),
            '{CCT}' => Str::upper((string) $institucional['cct']),
            '{MODALIDAD}' => Str::upper((string) $institucional['modalidad']),
        ]);

        $promedioMostrado = PromedioExcel::formatear($promedio, 1, '—');
        $resumen = sprintf(
            'EL PRESENTE CERTIFICADO AMPARA %d DE %d ASIGNATURAS, LAS CUALES CUBREN %s EL PLAN DE ESTUDIOS DEL BACHILLERATO GENERAL CON UN TOTAL DE %s DE %s CRÉDITOS Y UN PROMEDIO GENERAL DE APROVECHAMIENTO DE %s. LA ESCALA DE CALIFICACIONES ES DE %s A %s Y LA MÍNIMA APROBATORIA ES DE %s.',
            $materias->count(),
            $materiasPlan->count(),
            $acreditacion,
            $this->formatearCreditos($creditosAcreditados),
            $this->formatearCreditos($creditosPlan),
            $promedioMostrado,
            $this->formatearCalificacion((float) $institucional['calificacion_minima']),
            $this->formatearCalificacion((float) $institucional['calificacion_maxima']),
            $this->formatearCalificacion((float) $institucional['minima_aprobatoria']),
        );

        return array_merge($kardex, [
            'modalidad_certificado' => $modalidad,
            'semestres_certificados' => $semestresIncluidos,
            'semestres_certificado_matriz' => $semestresMatriz,
            'semestres_certificado_izquierda' => $semestresMatriz->whereIn('numero', [1, 2, 3])->values(),
            'semestres_certificado_derecha' => $semestresMatriz->whereIn('numero', [4, 5, 6])->values(),
            'materias_certificadas' => $materias,
            'materias_plan' => $materiasPlan,
            'materias_acreditadas_total' => $materias->count(),
            'materias_plan_total' => $materiasPlan->count(),
            'creditos_acreditados' => $creditosAcreditados,
            'creditos_acreditados_texto' => $this->formatearCreditos($creditosAcreditados),
            'creditos_plan' => $creditosPlan,
            'creditos_plan_texto' => $this->formatearCreditos($creditosPlan),
            'promedio_certificado_preciso' => $promedio,
            'promedio_certificado' => $promedioMostrado,
            'texto_certificado_renderizado' => $textoCertificado,
            'resumen_certificado' => $resumen,
            'folio' => $alumno->folio,
            'disponibilidad' => [
                'parcial' => $parcialDisponible,
                'definitivo' => $definitivoDisponible,
            ],
        ]);
    }

    public function claveContexto(string $tipo, array $datos, string $modalidad = ''): string
    {
        $partes = [$tipo];

        foreach (['ciclo', 'generacion', 'semestre', 'grupo', 'asignacion', 'alumno'] as $clave) {
            $modelo = $datos[$clave] ?? null;
            if (is_object($modelo) && isset($modelo->id)) {
                $partes[] = $clave . ':' . $modelo->id;
            }
        }

        if ($modalidad !== '') {
            $partes[] = 'modalidad:' . $modalidad;
        }

        return Str::limit(implode('|', $partes), 191, '');
    }

    private function contextoGrupo(array $filtros): array
    {
        $nivel = $this->nivel();
        $ciclo = CicloEscolar::query()->findOrFail((int) ($filtros['ciclo_escolar_id'] ?? 0));
        $generacion = Generacion::query()
            ->where('nivel_id', $nivel->id)
            ->findOrFail((int) ($filtros['generacion_id'] ?? 0));
        $semestre = Semestre::query()->findOrFail((int) ($filtros['semestre_id'] ?? 0));
        $grupo = Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'semestre', 'generacion'])
            ->where('nivel_id', $nivel->id)
            ->where('generacion_id', $generacion->id)
            ->where('semestre_id', $semestre->id)
            ->findOrFail((int) ($filtros['grupo_id'] ?? 0));

        return compact('nivel', 'ciclo', 'generacion', 'semestre', 'grupo');
    }

    private function alumnosDelContexto(array $contexto, string $estatus): Collection
    {
        $idsHistoricos = Calificacion::query()
            ->where('nivel_id', $contexto['nivel']->id)
            ->where('ciclo_escolar_id', $contexto['ciclo']->id)
            ->where('generacion_id', $contexto['generacion']->id)
            ->where('semestre_id', $contexto['semestre']->id)
            ->where('grupo_id', $contexto['grupo']->id)
            ->pluck('inscripcion_id');

        return Inscripcion::withTrashed()
            ->where('nivel_id', $contexto['nivel']->id)
            ->where('generacion_id', $contexto['generacion']->id)
            ->where(function (Builder $query) use ($contexto, $idsHistoricos): void {
                $query->whereIn('id', $idsHistoricos)
                    ->orWhere(function (Builder $actual) use ($contexto): void {
                        $actual->where('grupo_id', $contexto['grupo']->id)
                            ->where('semestre_id', $contexto['semestre']->id);
                    });
            })
            ->when($estatus !== 'todos', function (Builder $query) use ($estatus): void {
                if ($estatus === 'activos') {
                    $query->where('activo', true)->where('estatus', 'activo');
                } else {
                    $query->where('estatus', $estatus);
                }
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @param Collection<int, int> $alumnoIds
     * @param Collection<int, AsignacionMateria> $asignaciones
     */
    private function resultados(
        Collection $alumnoIds,
        Collection $asignaciones,
        ?int $cicloId = null,
        ?Collection $calificacionesPrecargadas = null,
    ): Collection {
        if ($alumnoIds->isEmpty() || $asignaciones->isEmpty()) {
            return collect();
        }

        $calificaciones = $calificacionesPrecargadas ?: Calificacion::query()
            ->with('periodo.parcialBachillerato')
            ->whereIn('inscripcion_id', $alumnoIds)
            ->whereIn('asignacion_materia_id', $asignaciones->pluck('id'))
            ->when($cicloId, fn(Builder $q) => $q->where('ciclo_escolar_id', $cicloId))
            ->get();

        $porClave = $calificaciones
            ->groupBy(fn(Calificacion $c) => $c->inscripcion_id . '|' . $c->asignacion_materia_id);

        return $alumnoIds->flatMap(function (int $alumnoId) use ($asignaciones, $porClave): array {
            $salida = [];

            foreach ($asignaciones as $asignacion) {
                $clave = $alumnoId . '|' . $asignacion->id;
                $registros = $porClave->get($clave, collect());
                $parciales = [];

                foreach ([1, 2] as $numero) {
                    $registro = $registros
                        ->filter(function (Calificacion $calificacion) use ($numero): bool {
                            return (int) ($calificacion->periodo?->parcialBachillerato?->parcial ?? 0) === $numero;
                        })
                        ->sortByDesc('id')
                        ->first();
                    $parciales[$numero] = $registro && $registro->es_numerica && is_numeric($registro->valor_numerico)
                        ? CalificacionBachillerato::truncarParcial($registro->valor_numerico)
                        : null;
                }

                $completa = $parciales[1] !== null && $parciales[2] !== null;
                $promedio = $completa
                    ? CalificacionBachillerato::promedioMateria($parciales)
                    : null;
                $valorEntero = $promedio;

                $salida[$clave] = [
                    'asignacion' => $asignacion,
                    'materia' => $asignacion->materia,
                    'clave' => $asignacion->materia?->clave,
                    'nombre' => $asignacion->materia?->materia,
                    'creditos_certificados' => $asignacion->materia?->creditos_certificados,
                    'parcial_1' => $parciales[1],
                    'parcial_2' => $parciales[2],
                    'completa' => $completa,
                    'valor_preciso' => $promedio,
                    'valor_entero' => $valorEntero,
                    'valor' => $valorEntero === null ? '' : (string) $valorEntero,
                    'acreditada' => $completa && $valorEntero !== null && $valorEntero >= 6,
                    'extra' => ReglasMateriaBachillerato::esExtraInformativa($asignacion->materia),
                    'asistencia' => null,
                ];
            }

            return $salida;
        });
    }

    private function resultadoVacio(AsignacionMateria $asignacion): array
    {
        return [
            'asignacion' => $asignacion,
            'materia' => $asignacion->materia,
            'clave' => $asignacion->materia?->clave,
            'nombre' => $asignacion->materia?->materia,
            'creditos_certificados' => $asignacion->materia?->creditos_certificados,
            'parcial_1' => null,
            'parcial_2' => null,
            'completa' => false,
            'valor_preciso' => null,
            'valor_entero' => null,
            'valor' => '',
            'acreditada' => false,
            'extra' => ReglasMateriaBachillerato::esExtraInformativa($asignacion->materia),
            'asistencia' => null,
        ];
    }

    private function situacionEscolar(Inscripcion $alumno, int $noAcreditadas, int $incompletas): string
    {
        if ($alumno->estatus !== 'activo') {
            return Str::upper(str_replace('_', ' ', $alumno->estatus));
        }

        if ($incompletas > 0) {
            return 'PENDIENTE';
        }

        return $noAcreditadas > 0 ? 'IRREGULAR' : 'REGULAR';
    }

    private function resolverFirmante(string $rol, int $cicloId, Nivel $nivel): array
    {
        $firmante = FirmanteMediaSuperior::query()
            ->with(['director', 'persona'])
            ->where('nivel_id', $nivel->id)
            ->where('rol', $rol)
            ->vigentePara($cicloId)
            ->latest('id')
            ->first();

        if ($firmante) {
            return [
                'nombre' => Str::upper($firmante->nombreCompleto()),
                'cargo' => Str::upper($firmante->cargo_impresion ?: $this->cargoPredeterminado($rol)),
                'configurado' => true,
                'firma_path' => $firmante->firma_path,
                'sello_path' => $firmante->sello_path,
                'firma_ruta' => $this->rutaArchivoFirmante($firmante->firma_path),
                'sello_ruta' => $this->rutaArchivoFirmante($firmante->sello_path),
            ];
        }

        if ($rol === FirmanteMediaSuperior::ROL_DIRECTOR && $nivel->director) {
            return [
                'nombre' => Str::upper(trim(implode(' ', array_filter([
                    $nivel->director->titulo,
                    $nivel->director->nombre,
                    $nivel->director->apellido_paterno,
                    $nivel->director->apellido_materno,
                ])))),
                'cargo' => 'DIRECTORA DEL PLANTEL',
                'configurado' => true,
                'firma_path' => null,
                'sello_path' => null,
                'firma_ruta' => null,
                'sello_ruta' => null,
            ];
        }

        if ($rol === FirmanteMediaSuperior::ROL_CONTROL_ESCOLAR) {
            $persona = Persona::query()
                ->where('status', true)
                ->where(function (Builder $query): void {
                    $query->whereHas('rolesPersona', fn(Builder $rolQuery) => $rolQuery->whereIn('slug', [
                        'control_escolar',
                        'administrativo_control_escolar',
                    ]))
                        ->orWhere('rfc', 'GABE880722NW4')
                        ->orWhere(function (Builder $nombre): void {
                            $nombre->where('nombre', 'Edgar')
                                ->where('apellido_paterno', 'García')
                                ->where('apellido_materno', 'Basilio');
                        });
                })
                ->orderBy('apellido_paterno')
                ->first();

            if ($persona) {
                return [
                    'nombre' => Str::upper($this->nombrePersona($persona)),
                    'cargo' => 'RESPONSABLE DE CONTROL ESCOLAR',
                    'configurado' => true,
                ];
            }
        }

        return [
            'nombre' => 'SIN CONFIGURAR',
            'cargo' => $this->cargoPredeterminado($rol),
            'configurado' => false,
            'firma_path' => null,
            'sello_path' => null,
            'firma_ruta' => null,
            'sello_ruta' => null,
        ];
    }

    private function rutaArchivoFirmante(?string $ruta): ?string
    {
        if (blank($ruta) || ! Storage::disk('local')->exists($ruta)) {
            return null;
        }

        return Storage::disk('local')->path($ruta);
    }

    private function cargoPredeterminado(string $rol): string
    {
        return match ($rol) {
            FirmanteMediaSuperior::ROL_DIRECTOR => 'DIRECTOR(A) DEL PLANTEL',
            FirmanteMediaSuperior::ROL_CONTROL_ESCOLAR => 'RESPONSABLE DE CONTROL ESCOLAR',
            FirmanteMediaSuperior::ROL_JEFE_REGISTRO => 'JEFE DEL DEPARTAMENTO DE REGISTRO Y CERTIFICACIÓN',
            default => 'FIRMANTE',
        };
    }

    private function rutaPublica(?string $configurada, string $predeterminada): string
    {
        $ruta = trim((string) $configurada);
        $relativa = $ruta !== '' ? ltrim($ruta, '/') : $predeterminada;
        $completa = public_path($relativa);

        return is_file($completa) ? $completa : public_path($predeterminada);
    }

    private function textoCertificadoPredeterminado(): string
    {
        return <<<'TEXT'
CERTIFICA QUE: {NOMBRE}
CON CLAVE ÚNICA DE REGISTRO DE POBLACIÓN (CURP) {CURP}
CURSÓ Y ACREDITÓ {ACREDITACION} EL BACHILLERATO GENERAL
CON RECONOCIMIENTO DE VALIDEZ OFICIAL DE LA SECRETARÍA DE EDUCACIÓN GUERRERO, SEGÚN ACUERDO: {ACUERDO}, DE FECHA {FECHA_ACUERDO} Y CLAVE DE CENTRO DE TRABAJO {CCT}.
TEXT;
    }

    private function leyendaCertificadoPredeterminada(): string
    {
        return 'ESTE CERTIFICADO REQUIERE DE TRÁMITES ADICIONALES DE LEGALIZACIÓN, NO ES VÁLIDO SI PRESENTA BORRADURAS O ENMENDADURAS.';
    }

    private function fechaAcuerdoTexto(mixed $fecha): string
    {
        if (! $fecha) {
            return '';
        }

        try {
            $carbon = $fecha instanceof \Carbon\CarbonInterface ? $fecha : \Carbon\Carbon::parse($fecha);
        } catch (\Throwable) {
            return '';
        }

        $meses = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        return sprintf('%d DE %s DE %d', $carbon->day, $meses[$carbon->month], $carbon->year);
    }

    private function formatearCreditos(float $creditos): string
    {
        return rtrim(rtrim(number_format($creditos, 2, '.', ''), '0'), '.');
    }

    private function formatearCalificacion(float $calificacion): string
    {
        return rtrim(rtrim(number_format($calificacion, 2, '.', ''), '0'), '.');
    }

    private function nombreAlumno(Inscripcion $alumno): string
    {
        return Str::upper(trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ]))));
    }

    private function nombrePersona(?Persona $persona): string
    {
        if (!$persona) {
            return 'SIN CONFIGURAR';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])));
    }


    /**
     * Convierte el promedio de los dos parciales en la calificación oficial
     * entera de bachillerato. Se elimina la parte decimal sin redondear:
     * 6.5 => 6, 8.9 => 8 y 10.0 => 10.
     */
    private function truncarCalificacionOficial(float $valor): int
    {
        return (int) max(0, min(10, floor($valor + 0.000000001)));
    }

    private function numeroEnteroEnLetra(int $numero): string
    {
        return match (max(0, min(10, $numero))) {
            0 => 'CERO',
            1 => 'UNO',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ',
        };
    }
}
