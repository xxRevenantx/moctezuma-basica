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

    /**
     * Obtiene las filas del directorio aplicando siempre la regla institucional
     * de mostrar únicamente alumnos activos y visibles en listas operativas.
     */
    public function filas(array $filtros): Collection
    {
        $filtros = $this->normalizarFiltros($filtros);

        $alumnos = $this->consultaAcademica($filtros)
            ->get()
            ->flatMap(fn (Inscripcion $alumno): Collection => $this->filasDelAlumno($alumno, $filtros['modo_responsables']))
            ->when(
                $filtros['parentesco'] !== '',
                fn (Collection $filas): Collection => $filas->filter(
                    fn (array $fila): bool => Str::upper(trim($fila['parentesco'])) === $filtros['parentesco']
                )
            )
            ->when(
                $filtros['buscar'] !== '',
                fn (Collection $filas): Collection => $this->filtrarBusqueda($filas, $filtros['buscar'])
            );

        return $this->ordenar($alumnos, $filtros['orden'])->values();
    }

    public function normalizarFiltros(array $filtros): array
    {
        $entero = static fn (mixed $valor): ?int => filter_var($valor, FILTER_VALIDATE_INT) !== false && (int) $valor > 0
            ? (int) $valor
            : null;

        $modo = (string) ($filtros['modo_responsables'] ?? 'principal');
        $orden = (string) ($filtros['orden'] ?? 'academico_alumno');

        return [
            'nivel_id' => $entero($filtros['nivel_id'] ?? null),
            'generacion_id' => $entero($filtros['generacion_id'] ?? null),
            'ciclo_escolar_id' => $entero($filtros['ciclo_escolar_id'] ?? null),
            'grado_id' => $entero($filtros['grado_id'] ?? null),
            'semestre_id' => $entero($filtros['semestre_id'] ?? null),
            'grupo_id' => $entero($filtros['grupo_id'] ?? null),
            'modo_responsables' => in_array($modo, self::MODOS_RESPONSABLES, true) ? $modo : 'principal',
            'parentesco' => Str::upper(trim((string) ($filtros['parentesco'] ?? ''))),
            'buscar' => Str::limit(trim((string) ($filtros['buscar'] ?? '')), 120, ''),
            'orden' => in_array($orden, self::ORDENES, true) ? $orden : 'academico_alumno',
            'salto_grupo' => filter_var($filtros['salto_grupo'] ?? true, FILTER_VALIDATE_BOOL),
        ];
    }

    public function metricas(Collection $filas): array
    {
        return [
            'filas' => $filas->count(),
            'alumnos' => $filas->pluck('alumno_id')->filter()->unique()->count(),
            'responsables' => $filas->pluck('tutor_id')->filter()->unique()->count(),
            'sin_tutor' => $filas->where('sin_tutor', true)->pluck('alumno_id')->unique()->count(),
            'sin_telefono' => $filas->where('sin_telefono', true)->count(),
            'sin_domicilio' => $filas->where('sin_domicilio', true)->count(),
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

    private function consultaAcademica(array $filtros): Builder
    {
        return Inscripcion::query()
            ->visiblesEnListas()
            ->with([
                'nivel:id,nombre,slug,cct,logo,director_id',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,nombre,status',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                'grupo' => fn ($query) => $query
                    ->select([
                        'id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id',
                        'ciclo_escolar_id', 'asignacion_grupo_id',
                    ])
                    ->with('asignacionGrupo:id,nombre'),
                'relacionesTutoresActivas' => fn ($query) => $query
                    ->with('tutor')
                    ->orderByDesc('es_principal')
                    ->orderBy('orden_contacto')
                    ->orderBy('id'),
                'tutor',
            ])
            ->where('nivel_id', $filtros['nivel_id'])
            ->when($filtros['generacion_id'], fn (Builder $query, int $id) => $query->where('generacion_id', $id))
            ->when($filtros['ciclo_escolar_id'], fn (Builder $query, int $id) => $query->where('ciclo_escolar_id', $id))
            ->when($filtros['grado_id'], fn (Builder $query, int $id) => $query->where('grado_id', $id))
            ->when($filtros['semestre_id'], fn (Builder $query, int $id) => $query->where('semestre_id', $id))
            ->when($filtros['grupo_id'], fn (Builder $query, int $id) => $query->where('grupo_id', $id))
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    private function filasDelAlumno(Inscripcion $alumno, string $modo): Collection
    {
        $relaciones = $alumno->relacionesTutoresActivas
            ->filter(fn (InscripcionTutor $relacion): bool => $relacion->tutor instanceof Tutor && $relacion->tutor->activo !== false)
            ->values();

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
                )
            );
        }

        if ($alumno->tutor instanceof Tutor && $alumno->tutor->activo !== false) {
            return collect([
                $this->crearFila(
                    $alumno,
                    $alumno->tutor,
                    $alumno->tutor->parentesco,
                    true,
                ),
            ]);
        }

        return collect([$this->crearFila($alumno, null, null, false)]);
    }

    private function crearFila(Inscripcion $alumno, ?Tutor $tutor, ?string $parentesco, bool $principal): array
    {
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

        return [
            'alumno_id' => (int) $alumno->id,
            'tutor_id' => $tutor?->id ? (int) $tutor->id : null,
            'responsable' => $nombreTutor,
            'parentesco' => Str::headline(trim((string) ($parentesco ?: $tutor?->parentesco ?: 'Sin parentesco registrado'))),
            'es_principal' => $principal,
            'telefono' => $telefono['texto'],
            'domicilio' => $domicilio['texto'],
            'alumno' => $nombreAlumno !== '' ? $nombreAlumno : 'Alumno sin nombre registrado',
            'nivel' => $nivel,
            'grado' => $grado,
            'semestre' => $semestre,
            'grupo' => $grupo,
            'generacion' => $generacion,
            'ciclo_escolar' => $ciclo,
            'sin_tutor' => $tutor === null,
            'sin_telefono' => $telefono['faltante'],
            'sin_domicilio' => $domicilio['faltante'],
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
        ];
    }

    private function telefonoTutor(?Tutor $tutor): array
    {
        if (! $tutor) {
            return ['texto' => 'Sin teléfono registrado', 'faltante' => true];
        }

        $telefonos = collect([
            trim((string) $tutor->telefono_celular) !== '' ? 'Cel. ' . trim((string) $tutor->telefono_celular) : null,
            trim((string) $tutor->telefono_casa) !== '' ? 'Casa ' . trim((string) $tutor->telefono_casa) : null,
        ])->filter()->unique()->values();

        return [
            'texto' => $telefonos->isEmpty() ? 'Sin teléfono registrado' : $telefonos->join(' · '),
            'faltante' => $telefonos->isEmpty(),
        ];
    }

    private function domicilioTutor(?Tutor $tutor): array
    {
        if (! $tutor) {
            return ['texto' => 'Sin domicilio registrado', 'faltante' => true];
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

        return [
            'texto' => $partes->isEmpty() ? 'Sin domicilio registrado' : $partes->join(', '),
            'faltante' => $partes->isEmpty(),
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
                $fila['parentesco'],
                $fila['telefono'],
                $fila['domicilio'],
                $fila['alumno'],
                $fila['nivel'],
                $fila['grado'],
                $fila['semestre'],
                $fila['grupo'],
                $fila['generacion'],
                $fila['ciclo_escolar'],
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
}
