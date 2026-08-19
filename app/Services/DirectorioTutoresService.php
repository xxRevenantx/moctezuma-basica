<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\InscripcionTutor;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DirectorioTutoresService
{
    public const MODOS_RESPONSABLES = ['principal', 'todos'];
    public const ORDENES = ['academico_alumno', 'alumno', 'tutor'];
    public const ESTADOS_ALUMNO = ['activos', 'egresados', 'no_reinscritos', 'todos'];
    public const VISTAS = ['familias', 'alumnos'];
    public const PESTANAS = ['todos', 'multinivel', 'duplicados', 'incompletos'];
    public const TIPOS_FAMILIA = ['todas', 'uno', 'varios', 'multinivel'];
    public const FILTROS_RAPIDOS = ['', 'sin_tutor', 'sin_telefono', 'sin_domicilio', 'sin_curp', 'varios_hijos', 'multinivel', 'duplicados'];

    /**
     * Construye el directorio global. La consulta académica se hace una sola vez
     * y después se derivan las vistas por alumno y por familia/responsable.
     */
    public function directorio(array $filtros): array
    {
        $filtros = $this->normalizarFiltros($filtros);
        $filasBase = $this->filasBase($filtros);
        $duplicados = $this->analizarDuplicados($filasBase);
        $filasMarcadas = $this->marcarDuplicados($filasBase, $duplicados);
        $familiasBase = $this->familiasDesdeFilas($filasMarcadas);
        $metricas = $this->metricas($filasMarcadas, $familiasBase, $duplicados);

        [$filas, $familias] = $this->aplicarFiltrosDeVista($filasMarcadas, $familiasBase, $filtros);

        return [
            'filtros' => $filtros,
            'filas' => $this->ordenar($filas, $filtros['orden'])->values(),
            'familias' => $this->ordenarFamilias($familias, $filtros['orden'])->values(),
            'metricas' => $metricas,
            'duplicados' => $duplicados,
        ];
    }

    /** Compatibilidad con los consumidores anteriores del servicio. */
    public function filas(array $filtros): Collection
    {
        return $this->directorio($filtros)['filas'];
    }

    public function normalizarFiltros(array $filtros): array
    {
        $entero = static fn (mixed $valor): ?int => filter_var($valor, FILTER_VALIDATE_INT) !== false && (int) $valor > 0
            ? (int) $valor
            : null;

        $modo = (string) ($filtros['modo_responsables'] ?? 'principal');
        $orden = (string) ($filtros['orden'] ?? 'academico_alumno');
        $estado = (string) ($filtros['estado_alumno'] ?? 'activos');
        $vista = (string) ($filtros['vista'] ?? 'familias');
        $pestana = (string) ($filtros['pestana'] ?? 'todos');
        $tipoFamilia = (string) ($filtros['tipo_familia'] ?? 'todas');
        $filtroRapido = (string) ($filtros['filtro_rapido'] ?? '');

        return [
            'nivel_id' => $entero($filtros['nivel_id'] ?? null),
            'generacion_id' => $entero($filtros['generacion_id'] ?? null),
            'ciclo_escolar_id' => $entero($filtros['ciclo_escolar_id'] ?? null),
            'grado_id' => $entero($filtros['grado_id'] ?? null),
            'semestre_id' => $entero($filtros['semestre_id'] ?? null),
            'grupo_id' => $entero($filtros['grupo_id'] ?? null),
            'estado_alumno' => in_array($estado, self::ESTADOS_ALUMNO, true) ? $estado : 'activos',
            'modo_responsables' => in_array($modo, self::MODOS_RESPONSABLES, true) ? $modo : 'principal',
            'parentesco' => Str::upper(trim((string) ($filtros['parentesco'] ?? ''))),
            'buscar' => Str::limit(trim((string) ($filtros['buscar'] ?? '')), 120, ''),
            'orden' => in_array($orden, self::ORDENES, true) ? $orden : 'academico_alumno',
            'vista' => in_array($vista, self::VISTAS, true) ? $vista : 'familias',
            'pestana' => in_array($pestana, self::PESTANAS, true) ? $pestana : 'todos',
            'tipo_familia' => in_array($tipoFamilia, self::TIPOS_FAMILIA, true) ? $tipoFamilia : 'todas',
            'filtro_rapido' => in_array($filtroRapido, self::FILTROS_RAPIDOS, true) ? $filtroRapido : '',
            'salto_grupo' => filter_var($filtros['salto_grupo'] ?? true, FILTER_VALIDATE_BOOL),
        ];
    }

    public function metricas(Collection $filas, ?Collection $familias = null, ?Collection $duplicados = null): array
    {
        $familias ??= $this->familiasDesdeFilas($filas);
        $duplicados ??= $this->analizarDuplicados($filas);

        return [
            'filas' => $filas->count(),
            'alumnos' => $filas->pluck('alumno_id')->filter()->unique()->count(),
            'familias' => $familias->count(),
            'responsables' => $filas->pluck('tutor_id')->filter()->unique()->count(),
            'varios_hijos' => $familias->where('varios_hijos', true)->count(),
            'multinivel' => $familias->where('multinivel', true)->count(),
            'sin_tutor' => $filas->where('sin_tutor', true)->pluck('alumno_id')->unique()->count(),
            'sin_telefono' => $familias->where('sin_telefono', true)->count(),
            'sin_domicilio' => $familias->where('sin_domicilio', true)->count(),
            'sin_curp' => $familias->where('sin_curp', true)->count(),
            'duplicados' => $duplicados->count(),
        ];
    }

    public function secciones(Collection $filas): Collection
    {
        return $filas
            ->groupBy('grupo_clave')
            ->map(function (Collection $items, string $clave): array {
                $primera = $items->first();

                return [
                    'clave' => $clave,
                    'titulo' => $primera['grupo_titulo'],
                    'nivel' => $primera['nivel'],
                    'grado' => $primera['grado'],
                    'semestre' => $primera['semestre'],
                    'grupo' => $primera['grupo'],
                    'generacion' => $primera['generacion'],
                    'ciclo_escolar' => $primera['ciclo_escolar'],
                    'filas' => $items->values(),
                ];
            })
            ->values();
    }

    public function seccionesFamilias(Collection $familias): Collection
    {
        return collect([[
            'clave' => 'familias',
            'titulo' => 'Directorio consolidado de familias',
            'nivel' => 'Todos los niveles seleccionados',
            'grado' => '',
            'semestre' => '',
            'grupo' => '',
            'generacion' => 'Según alumnos relacionados',
            'ciclo_escolar' => $familias->pluck('ciclos')->flatten()->filter()->unique()->sort()->join(' · '),
            'filas' => $familias->values(),
        ]]);
    }

    private function filasBase(array $filtros): Collection
    {
        $filas = $this->consultaAcademica($filtros)
            ->get()
            ->flatMap(fn (Inscripcion $alumno): Collection => $this->filasDelAlumno($alumno, $filtros['modo_responsables']))
            ->when(
                $filtros['parentesco'] !== '',
                fn (Collection $items): Collection => $items->filter(
                    fn (array $fila): bool => Str::upper(trim($fila['parentesco'])) === $filtros['parentesco']
                )
            )
            ->when(
                $filtros['buscar'] !== '',
                fn (Collection $items): Collection => $this->filtrarBusqueda($items, $filtros['buscar'])
            );

        return $filas->values();
    }

    private function consultaAcademica(array $filtros): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'nivel:id,nombre,slug,cct,logo,director_id',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                'grupo' => fn ($grupo) => $grupo
                    ->select([
                        'id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id',
                        'ciclo_escolar_id', 'asignacion_grupo_id',
                    ])
                    ->with('asignacionGrupo:id,nombre'),
                'relacionesTutoresActivas' => fn ($relacion) => $relacion
                    ->with(['tutor.documentosActuales.tipoDocumento'])
                    ->orderByDesc('es_principal')
                    ->orderBy('orden_contacto')
                    ->orderBy('id'),
                'tutor.documentosActuales.tipoDocumento',
            ]);

        match ($filtros['estado_alumno']) {
            'activos' => $query->visiblesEnListas(),
            'egresados' => $query->where('estatus', Inscripcion::ESTATUS_EGRESADO)->whereNull('deleted_at'),
            'no_reinscritos' => $query->whereIn('estatus', ['no_reinscrito', 'pendiente_reinscripcion'])->whereNull('deleted_at'),
            default => $query->whereNull('deleted_at'),
        };

        return $query
            ->when($filtros['nivel_id'], fn (Builder $q, int $id) => $q->where('nivel_id', $id))
            ->when($filtros['generacion_id'], fn (Builder $q, int $id) => $q->where('generacion_id', $id))
            ->when($filtros['ciclo_escolar_id'], fn (Builder $q, int $id) => $q->where('ciclo_escolar_id', $id))
            ->when($filtros['grado_id'], fn (Builder $q, int $id) => $q->where('grado_id', $id))
            ->when($filtros['semestre_id'], fn (Builder $q, int $id) => $q->where('semestre_id', $id))
            ->when($filtros['grupo_id'], fn (Builder $q, int $id) => $q->where('grupo_id', $id))
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    private function filasDelAlumno(Inscripcion $alumno, string $modo): Collection
    {
        $relacionesTodas = $alumno->relacionesTutoresActivas
            ->filter(fn (InscripcionTutor $relacion): bool => $relacion->tutor instanceof Tutor && $relacion->tutor->activo !== false)
            ->values();

        $unidad = $this->unidadFamiliarAlumno($alumno, $relacionesTodas);
        $relaciones = $relacionesTodas;

        if ($modo === 'principal' && $relaciones->isNotEmpty()) {
            $principal = $relaciones->first(fn (InscripcionTutor $relacion): bool => $relacion->es_principal)
                ?? $relaciones->first();
            $relaciones = collect([$principal]);
        }

        if ($relaciones->isNotEmpty()) {
            return $relaciones->map(
                fn (InscripcionTutor $relacion): array => $this->crearFila(
                    $alumno,
                    $relacion->tutor,
                    $relacion->parentesco,
                    (bool) $relacion->es_principal,
                    $unidad,
                )
            );
        }

        if ($alumno->tutor instanceof Tutor && $alumno->tutor->activo !== false) {
            $unidadLegado = $this->unidadFamiliarAlumno($alumno, collect(), $alumno->tutor);

            return collect([
                $this->crearFila(
                    $alumno,
                    $alumno->tutor,
                    $alumno->tutor->parentesco,
                    true,
                    $unidadLegado,
                ),
            ]);
        }

        return collect([$this->crearFila($alumno, null, null, false, [
            'clave' => 'sin-tutor:' . $alumno->id,
            'responsables_count' => 0,
        ])]);
    }

    private function crearFila(
        Inscripcion $alumno,
        ?Tutor $tutor,
        ?string $parentesco,
        bool $principal,
        array $unidad,
    ): array {
        $telefono = $this->telefonoTutor($tutor);
        $domicilio = $this->domicilioTutor($tutor);
        $nombreTutor = $tutor?->nombre_completo ?: 'Sin tutor registrado';
        $nombreAlumno = trim(collect([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])->filter()->join(' '));

        $nivel = $alumno->nivel?->nombre ?? 'Sin nivel';
        $grado = $alumno->grado?->nombre ?? 'Sin grado';
        $semestre = $alumno->semestre?->numero ? 'Semestre ' . $alumno->semestre->numero : '';
        $grupo = $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo';
        $generacion = $alumno->generacion?->etiqueta ?? 'Sin generación';
        $ciclo = $alumno->cicloEscolar
            ? $alumno->cicloEscolar->inicio_anio . '-' . $alumno->cicloEscolar->fin_anio
            : 'Sin ciclo escolar';

        $grupoTitulo = collect([
            $nivel,
            $grado,
            $semestre,
            $grupo !== 'Sin grupo' ? 'Grupo ' . $grupo : 'Sin grupo',
        ])->filter()->join(' · ');

        $identidad = $this->identidadTutor($tutor, $telefono['normalizado']);

        return [
            'alumno_id' => (int) $alumno->id,
            'alumno_curp' => trim((string) $alumno->curp),
            'matricula' => trim((string) $alumno->matricula),
            'folio' => trim((string) $alumno->folio),
            'estatus_alumno' => $alumno->estatusNormalizado(),
            'estatus_alumno_etiqueta' => $alumno->etiqueta_estatus,
            'tutor_id' => $tutor?->id ? (int) $tutor->id : null,
            'tutor_curp' => trim((string) ($tutor?->curp ?? '')),
            'tutor_identificador' => trim((string) ($tutor?->identificador_alternativo ?? '')),
            'responsable' => $nombreTutor,
            'responsable_normalizado' => $this->normalizarTexto($nombreTutor),
            'parentesco' => Str::headline(trim((string) ($parentesco ?: $tutor?->parentesco ?: 'Sin parentesco registrado'))),
            'es_principal' => $principal,
            'telefono' => $telefono['texto'],
            'telefono_normalizado' => $telefono['normalizado'],
            'ine' => $this->ineTutor($tutor),
            'domicilio' => $domicilio['texto'],
            'domicilio_normalizado' => $domicilio['normalizado'],
            'alumno' => $nombreAlumno !== '' ? $nombreAlumno : 'Alumno sin nombre registrado',
            'nivel_id' => $alumno->nivel_id ? (int) $alumno->nivel_id : null,
            'nivel_slug' => (string) ($alumno->nivel?->slug ?? ''),
            'nivel' => $nivel,
            'grado' => $grado,
            'semestre' => $semestre,
            'grupo' => $grupo,
            'generacion' => $generacion,
            'ciclo_escolar' => $ciclo,
            'sin_tutor' => $tutor === null,
            'sin_telefono' => $telefono['faltante'],
            'sin_domicilio' => $domicilio['faltante'],
            'sin_curp' => $tutor === null || trim((string) $tutor->curp) === '',
            'familia_clave' => $identidad['clave'] ?: 'sin-tutor:' . $alumno->id,
            'familia_identidad_tipo' => $identidad['tipo'],
            'unidad_familiar_clave' => $unidad['clave'],
            'unidad_familiar_responsables_count' => $unidad['responsables_count'],
            'grupo_clave' => implode('|', [
                $alumno->nivel_id ?: 0,
                $alumno->generacion_id ?: 0,
                $alumno->ciclo_escolar_id ?: 0,
                $alumno->grado_id ?: 0,
                $alumno->semestre_id ?: 0,
                $alumno->grupo_id ?: 0,
            ]),
            'grupo_titulo' => $grupoTitulo,
            'orden_nivel' => Str::lower($nivel),
            'orden_grado' => (int) ($alumno->grado?->orden ?? 999),
            'orden_semestre' => (int) ($alumno->semestre?->orden_global ?? 999),
            'orden_grupo' => Str::lower($grupo),
            'orden_alumno' => Str::lower(implode('|', [
                $alumno->apellido_paterno,
                $alumno->apellido_materno,
                $alumno->nombre,
            ])),
            'orden_tutor' => Str::lower(implode('|', [
                $tutor?->apellido_paterno,
                $tutor?->apellido_materno,
                $tutor?->nombre,
            ])),
            'posible_duplicado' => false,
            'duplicado_etiqueta' => '',
            'duplicado_confianza' => '',
            'duplicado_clave' => '',
        ];
    }

    private function familiasDesdeFilas(Collection $filas): Collection
    {
        if ($filas->isEmpty()) {
            return collect();
        }

        $conteosUnidad = $filas
            ->filter(fn (array $fila): bool => trim((string) $fila['unidad_familiar_clave']) !== '')
            ->groupBy('unidad_familiar_clave')
            ->map(fn (Collection $items): int => $items->pluck('alumno_id')->unique()->count());

        return $filas
            ->groupBy('familia_clave')
            ->map(function (Collection $items, string $clave) use ($conteosUnidad): array {
                $primera = $items->first();
                $alumnos = $items
                    ->unique('alumno_id')
                    ->map(fn (array $fila): array => [
                        'id' => $fila['alumno_id'],
                        'nombre' => $fila['alumno'],
                        'matricula' => $fila['matricula'],
                        'curp' => $fila['alumno_curp'],
                        'nivel' => $fila['nivel'],
                        'nivel_slug' => $fila['nivel_slug'],
                        'grado' => $fila['grado'],
                        'semestre' => $fila['semestre'],
                        'grupo' => $fila['grupo'],
                        'generacion' => $fila['generacion'],
                        'ciclo_escolar' => $fila['ciclo_escolar'],
                        'estatus' => $fila['estatus_alumno'],
                        'estatus_etiqueta' => $fila['estatus_alumno_etiqueta'],
                    ])
                    ->sortBy(fn (array $alumno): string => Str::lower($alumno['nivel'] . '|' . $alumno['grado'] . '|' . $alumno['grupo'] . '|' . $alumno['nombre']))
                    ->values();

                $tutores = $items
                    ->filter(fn (array $fila): bool => ! $fila['sin_tutor'] && $fila['tutor_id'])
                    ->unique('tutor_id')
                    ->map(fn (array $fila): array => [
                        'id' => $fila['tutor_id'],
                        'nombre' => $fila['responsable'],
                        'curp' => $fila['tutor_curp'],
                        'identificador' => $fila['tutor_identificador'],
                        'telefono' => $fila['telefono'],
                        'ine' => $fila['ine'],
                        'domicilio' => $fila['domicilio'],
                    ])
                    ->values();

                $niveles = $alumnos->pluck('nivel')->filter()->unique()->sort()->values();
                $ciclos = $alumnos->pluck('ciclo_escolar')->filter()->unique()->sort()->values();
                $parentescos = $items->pluck('parentesco')->filter()->unique()->sort()->values();
                $telefonos = $items->reject(fn (array $fila): bool => $fila['sin_telefono'])->pluck('telefono')->filter()->unique()->values();
                $domicilios = $items->reject(fn (array $fila): bool => $fila['sin_domicilio'])->pluck('domicilio')->filter()->unique()->values();
                $ines = $items->pluck('ine')->filter(fn ($valor): bool => trim((string) $valor) !== '')->unique()->values();
                $curps = $items->pluck('tutor_curp')->filter(fn ($valor): bool => trim((string) $valor) !== '')->unique()->values();
                $unidadCompartida = $items->contains(function (array $fila) use ($conteosUnidad): bool {
                    return (int) $fila['unidad_familiar_responsables_count'] >= 2
                        && (int) ($conteosUnidad[$fila['unidad_familiar_clave']] ?? 0) > 1;
                });

                return [
                    'familia_clave' => $clave,
                    'responsable' => $primera['responsable'],
                    'responsables' => $tutores,
                    'tutor_ids' => $tutores->pluck('id')->filter()->values(),
                    'tutor_id' => $tutores->first()['id'] ?? null,
                    'curp' => $curps->join(' · '),
                    'ine' => $ines->join(' · '),
                    'parentescos' => $parentescos,
                    'parentesco' => $parentescos->join(' · '),
                    'telefono' => $telefonos->join(' · '),
                    'domicilio' => $domicilios->join(' · '),
                    'alumnos' => $alumnos,
                    'alumnos_count' => $alumnos->count(),
                    'niveles' => $niveles,
                    'niveles_texto' => $niveles->join(' · '),
                    'niveles_count' => $niveles->count(),
                    'ciclos' => $ciclos,
                    'varios_hijos' => $alumnos->count() > 1,
                    'multinivel' => $niveles->count() > 1,
                    'unidad_familiar_compartida' => $unidadCompartida,
                    'sin_tutor' => $tutores->isEmpty(),
                    'sin_telefono' => $telefonos->isEmpty(),
                    'sin_domicilio' => $domicilios->isEmpty(),
                    'sin_curp' => $tutores->isEmpty() || $curps->isEmpty(),
                    'posible_duplicado' => $items->contains('posible_duplicado', true),
                    'duplicado_etiqueta' => (string) ($items->firstWhere('posible_duplicado', true)['duplicado_etiqueta'] ?? ''),
                    'duplicado_confianza' => (string) ($items->firstWhere('posible_duplicado', true)['duplicado_confianza'] ?? ''),
                    'orden_nivel' => (string) $primera['orden_nivel'],
                    'orden_grado' => (int) $primera['orden_grado'],
                    'orden_grupo' => (string) $primera['orden_grupo'],
                    'orden_alumno' => (string) $primera['orden_alumno'],
                    'orden_tutor' => (string) $primera['orden_tutor'],
                ];
            })
            ->values();
    }

    private function analizarDuplicados(Collection $filas): Collection
    {
        $tutores = $filas
            ->filter(fn (array $fila): bool => ! $fila['sin_tutor'] && $fila['tutor_id'])
            ->groupBy('tutor_id')
            ->map(function (Collection $items, int|string $tutorId): array {
                $primera = $items->first();

                return [
                    'id' => (int) $tutorId,
                    'nombre' => $primera['responsable'],
                    'nombre_normalizado' => $primera['responsable_normalizado'],
                    'curp' => $primera['tutor_curp'],
                    'telefono' => $primera['telefono'],
                    'telefono_normalizado' => $primera['telefono_normalizado'],
                    'domicilio' => $primera['domicilio'],
                    'domicilio_normalizado' => $primera['domicilio_normalizado'],
                    'alumnos' => $items->pluck('alumno')->unique()->sort()->values(),
                    'niveles' => $items->pluck('nivel')->unique()->sort()->values(),
                ];
            })
            ->values();

        $grupos = collect();
        $yaMarcados = collect();

        $agregar = function (Collection $candidatos, string $tipo, string $etiqueta, string $confianza) use (&$grupos, &$yaMarcados): void {
            $candidatos->each(function (Collection $items, string $clave) use (&$grupos, &$yaMarcados, $tipo, $etiqueta, $confianza): void {
                $items = $items->reject(fn (array $tutor): bool => $yaMarcados->contains($tutor['id']))->values();

                if ($items->count() < 2) {
                    return;
                }

                $tutorIds = $items->pluck('id')->values();
                $grupoClave = $tipo . ':' . sha1($clave . '|' . $tutorIds->join(','));

                $grupos->push([
                    'clave' => $grupoClave,
                    'tipo' => $tipo,
                    'etiqueta' => $etiqueta,
                    'confianza' => $confianza,
                    'tutor_ids' => $tutorIds,
                    'tutores' => $items,
                ]);

                $yaMarcados = $yaMarcados->merge($tutorIds)->unique()->values();
            });
        };

        $agregar(
            $tutores
                ->filter(fn (array $tutor): bool => trim($tutor['curp']) !== '')
                ->groupBy(fn (array $tutor): string => Str::upper(trim($tutor['curp']))),
            'curp_exacta',
            'Coincidencia exacta de CURP',
            'alta',
        );

        $agregar(
            $tutores
                ->filter(fn (array $tutor): bool => trim($tutor['curp']) === '' && $tutor['nombre_normalizado'] !== '' && $tutor['telefono_normalizado'] !== '')
                ->groupBy(fn (array $tutor): string => $tutor['nombre_normalizado'] . '|' . $tutor['telefono_normalizado']),
            'nombre_telefono',
            'Mismo nombre y teléfono, sin CURP',
            'alta',
        );

        $agregar(
            $tutores
                ->filter(fn (array $tutor): bool => trim($tutor['curp']) === '' && $tutor['telefono_normalizado'] === '' && $tutor['nombre_normalizado'] !== '' && $tutor['domicilio_normalizado'] !== '')
                ->groupBy(fn (array $tutor): string => $tutor['nombre_normalizado'] . '|' . $tutor['domicilio_normalizado']),
            'nombre_domicilio',
            'Mismo nombre y domicilio; requiere revisión',
            'revisar',
        );

        return $grupos->values();
    }

    private function marcarDuplicados(Collection $filas, Collection $duplicados): Collection
    {
        $porTutor = collect();

        foreach ($duplicados as $grupo) {
            foreach ($grupo['tutor_ids'] as $tutorId) {
                $porTutor[(int) $tutorId] = $grupo;
            }
        }

        return $filas->map(function (array $fila) use ($porTutor): array {
            if (! $fila['tutor_id'] || ! isset($porTutor[$fila['tutor_id']])) {
                return $fila;
            }

            $grupo = $porTutor[$fila['tutor_id']];
            $fila['posible_duplicado'] = true;
            $fila['duplicado_etiqueta'] = $grupo['etiqueta'];
            $fila['duplicado_confianza'] = $grupo['confianza'];
            $fila['duplicado_clave'] = $grupo['clave'];

            return $fila;
        })->values();
    }

    private function aplicarFiltrosDeVista(Collection $filas, Collection $familias, array $filtros): array
    {
        $familias = $familias->when($filtros['tipo_familia'] !== 'todas', function (Collection $items) use ($filtros): Collection {
            return $items->filter(fn (array $familia): bool => match ($filtros['tipo_familia']) {
                'uno' => $familia['alumnos_count'] === 1,
                'varios' => $familia['alumnos_count'] > 1,
                'multinivel' => $familia['multinivel'],
                default => true,
            });
        });

        $familias = $familias->when($filtros['pestana'] !== 'todos', function (Collection $items) use ($filtros): Collection {
            return $items->filter(fn (array $familia): bool => match ($filtros['pestana']) {
                'multinivel' => $familia['multinivel'],
                'duplicados' => $familia['posible_duplicado'],
                'incompletos' => $familia['sin_tutor'] || $familia['sin_telefono'] || $familia['sin_domicilio'] || $familia['sin_curp'],
                default => true,
            });
        });

        $familias = $familias->when($filtros['filtro_rapido'] !== '', function (Collection $items) use ($filtros): Collection {
            return $items->filter(fn (array $familia): bool => match ($filtros['filtro_rapido']) {
                'sin_tutor' => $familia['sin_tutor'],
                'sin_telefono' => $familia['sin_telefono'],
                'sin_domicilio' => $familia['sin_domicilio'],
                'sin_curp' => $familia['sin_curp'],
                'varios_hijos' => $familia['varios_hijos'],
                'multinivel' => $familia['multinivel'],
                'duplicados' => $familia['posible_duplicado'],
                default => true,
            });
        });

        $familiasPermitidas = $familias->pluck('familia_clave')->flip();
        $filas = $filas->filter(fn (array $fila): bool => isset($familiasPermitidas[$fila['familia_clave']]));

        if ($filtros['pestana'] === 'duplicados') {
            $filas = $filas->where('posible_duplicado', true);
        }

        if ($filtros['pestana'] === 'incompletos') {
            $filas = $filas->filter(fn (array $fila): bool => $fila['sin_tutor'] || $fila['sin_telefono'] || $fila['sin_domicilio'] || $fila['sin_curp']);
        }

        return [$filas->values(), $familias->values()];
    }

    private function telefonoTutor(?Tutor $tutor): array
    {
        if (! $tutor) {
            return ['texto' => 'Sin teléfono registrado', 'faltante' => true, 'normalizado' => ''];
        }

        $telefonos = collect([
            trim((string) $tutor->telefono_celular) !== '' ? 'Cel. ' . trim((string) $tutor->telefono_celular) : null,
            trim((string) $tutor->telefono_casa) !== '' ? 'Casa ' . trim((string) $tutor->telefono_casa) : null,
        ])->filter()->unique()->values();

        $normalizado = preg_replace('/\D+/', '', trim((string) ($tutor->telefono_celular ?: $tutor->telefono_casa))) ?: '';

        return [
            'texto' => $telefonos->isEmpty() ? 'Sin teléfono registrado' : $telefonos->join(' · '),
            'faltante' => $telefonos->isEmpty(),
            'normalizado' => $normalizado,
        ];
    }

    private function domicilioTutor(?Tutor $tutor): array
    {
        if (! $tutor) {
            return ['texto' => 'Sin domicilio registrado', 'faltante' => true, 'normalizado' => ''];
        }

        $calleNumero = trim(collect([
            trim((string) $tutor->calle),
            trim((string) $tutor->numero) !== '' ? 'Núm. ' . trim((string) $tutor->numero) : null,
        ])->filter()->join(' '));

        $partes = collect([
            $calleNumero !== '' ? $calleNumero : null,
            trim((string) $tutor->colonia) !== '' ? 'Col. ' . trim((string) $tutor->colonia) : null,
            trim((string) $tutor->codigo_postal) !== '' ? 'C.P. ' . trim((string) $tutor->codigo_postal) : null,
            trim((string) $tutor->ciudad) ?: null,
            trim((string) $tutor->municipio) ?: null,
            trim((string) $tutor->estado) ?: null,
        ])->filter()->unique()->values();

        $texto = $partes->isEmpty() ? 'Sin domicilio registrado' : $partes->join(', ');

        return [
            'texto' => $texto,
            'faltante' => $partes->isEmpty(),
            'normalizado' => $partes->isEmpty() ? '' : $this->normalizarTexto($texto),
        ];
    }

    /**
     * El valor INE mostrado en los directorios corresponde al folio capturado
     * en el documento vigente de tipo "INE del responsable". Si no existe folio,
     * se devuelve cadena vacía tal como requiere el formato institucional.
     */
    private function ineTutor(?Tutor $tutor): string
    {
        if (! $tutor) {
            return '';
        }

        $documentos = $tutor->relationLoaded('documentosActuales')
            ? $tutor->documentosActuales
            : $tutor->documentosActuales()->with('tipoDocumento')->get();

        $documento = $documentos
            ->filter(fn ($item): bool => $item->tipoDocumento?->slug === 'ine-responsable')
            ->reject(fn ($item): bool => in_array((string) $item->estado, ['pendiente', 'rechazado', 'reemplazado', 'cancelado'], true))
            ->sortByDesc('id')
            ->first();

        return trim((string) ($documento?->folio ?? ''));
    }

    private function identidadTutor(?Tutor $tutor, string $telefonoNormalizado = ''): array
    {
        if (! $tutor) {
            return ['clave' => '', 'tipo' => 'sin_tutor'];
        }

        $curp = Str::upper(trim((string) $tutor->curp));
        if ($curp !== '') {
            return ['clave' => 'curp:' . $curp, 'tipo' => 'curp'];
        }

        $nombre = $this->normalizarTexto($tutor->nombre_completo);
        if ($nombre !== '' && $telefonoNormalizado !== '') {
            return [
                'clave' => 'nombre-telefono:' . sha1($nombre . '|' . $telefonoNormalizado),
                'tipo' => 'nombre_telefono',
            ];
        }

        return ['clave' => 'tutor:' . $tutor->id, 'tipo' => 'registro'];
    }

    private function unidadFamiliarAlumno(Inscripcion $alumno, Collection $relaciones, ?Tutor $legado = null): array
    {
        $claves = $relaciones
            ->map(function (InscripcionTutor $relacion): ?string {
                $tutor = $relacion->tutor;
                if (! $tutor) {
                    return null;
                }

                $telefono = preg_replace('/\D+/', '', trim((string) ($tutor->telefono_celular ?: $tutor->telefono_casa))) ?: '';

                return $this->identidadTutor($tutor, $telefono)['clave'] ?: null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($claves->isEmpty() && $legado) {
            $telefono = preg_replace('/\D+/', '', trim((string) ($legado->telefono_celular ?: $legado->telefono_casa))) ?: '';
            $clave = $this->identidadTutor($legado, $telefono)['clave'];
            if ($clave !== '') {
                $claves = collect([$clave]);
            }
        }

        if ($claves->isEmpty()) {
            return ['clave' => 'sin-tutor:' . $alumno->id, 'responsables_count' => 0];
        }

        return [
            'clave' => 'unidad:' . sha1($claves->join('|')),
            'responsables_count' => $claves->count(),
        ];
    }

    private function filtrarBusqueda(Collection $filas, string $buscar): Collection
    {
        $terminos = collect(preg_split('/\s+/u', Str::lower($buscar), -1, PREG_SPLIT_NO_EMPTY));

        if ($terminos->isEmpty()) {
            return $filas;
        }

        return $filas->filter(function (array $fila) use ($terminos): bool {
            $texto = Str::lower(collect([
                $fila['responsable'],
                $fila['tutor_curp'],
                $fila['tutor_identificador'],
                $fila['parentesco'],
                $fila['telefono'],
                $fila['ine'],
                $fila['domicilio'],
                $fila['alumno'],
                $fila['matricula'],
                $fila['alumno_curp'],
                $fila['folio'],
                $fila['nivel'],
                $fila['grado'],
                $fila['semestre'],
                $fila['grupo'],
                $fila['generacion'],
                $fila['ciclo_escolar'],
                $fila['estatus_alumno_etiqueta'],
            ])->join(' '));

            return $terminos->every(fn (string $termino): bool => Str::contains($texto, $termino));
        });
    }

    private function ordenar(Collection $filas, string $orden): Collection
    {
        return $filas->sortBy(function (array $fila) use ($orden): string {
            return match ($orden) {
                'alumno' => implode('|', [
                    $fila['orden_alumno'],
                    $fila['orden_nivel'],
                    str_pad((string) $fila['orden_grado'], 4, '0', STR_PAD_LEFT),
                    $fila['orden_grupo'],
                    $fila['orden_tutor'],
                ]),
                'tutor' => implode('|', [
                    $fila['sin_tutor'] ? '1' : '0',
                    $fila['orden_tutor'],
                    $fila['orden_alumno'],
                ]),
                default => implode('|', [
                    $fila['orden_nivel'],
                    str_pad((string) $fila['orden_grado'], 4, '0', STR_PAD_LEFT),
                    str_pad((string) $fila['orden_semestre'], 4, '0', STR_PAD_LEFT),
                    $fila['orden_grupo'],
                    $fila['orden_alumno'],
                    $fila['orden_tutor'],
                ]),
            };
        }, SORT_NATURAL | SORT_FLAG_CASE);
    }

    private function ordenarFamilias(Collection $familias, string $orden): Collection
    {
        return $familias->sortBy(function (array $familia) use ($orden): string {
            return match ($orden) {
                'alumno' => $familia['orden_alumno'] . '|' . $familia['orden_tutor'],
                'tutor' => ($familia['sin_tutor'] ? '1' : '0') . '|' . $familia['orden_tutor'],
                default => $familia['orden_nivel'] . '|' . str_pad((string) $familia['orden_grado'], 4, '0', STR_PAD_LEFT) . '|' . $familia['orden_grupo'] . '|' . $familia['orden_tutor'],
            };
        }, SORT_NATURAL | SORT_FLAG_CASE);
    }

    private function normalizarTexto(?string $texto): string
    {
        $texto = Str::ascii(Str::lower(trim((string) $texto)));
        $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto) ?: '';

        return trim(preg_replace('/\s+/u', ' ', $texto) ?: '');
    }
}
