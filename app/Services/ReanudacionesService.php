<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Director;
use App\Models\Escuela;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaNivelHistorial;
use App\Models\PlantillaPersonalNivel;
use App\Models\ReanudacionLaboral;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReanudacionesService
{
    public const TIPOS = [
        'receso' => 'Reanudación después del receso escolar de agosto',
        'invierno' => 'Reanudación después de las vacaciones de invierno',
        'primavera' => 'Reanudación después de las vacaciones de primavera',
    ];

    /**
     * Obtiene la plantilla vigente en una fecha del ciclo seleccionado.
     * Retorna una fila única por persona y nivel; si existen cabeceras duplicadas,
     * combina sus funciones y conserva la primera según el orden configurado.
     *
     * @param array<int, int|string> $niveles
     * @return array{listos: Collection<int, array<string,mixed>>, advertencias: Collection<int, array<string,mixed>>, excluidos: Collection<int, array<string,mixed>>}
     */
    public function plantilla(
        CicloEscolar $ciclo,
        string $fechaReferencia,
        array $niveles = [],
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $rolId = null,
        string $busqueda = ''
    ): array {
        $fecha = Carbon::parse($fechaReferencia)->toDateString();
        $niveles = collect($niveles)->map(fn($id) => (int) $id)->filter()->unique()->values();

        $plantillas = PlantillaPersonalNivel::query()
            ->where('ciclo_escolar_id', $ciclo->id)
            ->when($niveles->isNotEmpty(), fn (Builder $q) => $q->whereIn('nivel_id', $niveles))
            ->whereIn('estado', [PlantillaPersonalNivel::ESTADO_PUBLICADA, PlantillaPersonalNivel::ESTADO_CERRADA])
            ->get(['id', 'nivel_id']);

        $plantillaIds = $plantillas->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($plantillaIds === []) {
            return ['listos' => collect(), 'advertencias' => collect(), 'excluidos' => collect()];
        }

        $query = PersonaNivel::query()
            ->with([
                'persona.personaRoles.rolePersona',
                'nivel.director',
                'nivel.supervisor',
                'ciclos' => fn (Relation $membresia) => $membresia
                    ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                    ->whereDate('fecha_inicio', '<=', $fecha)
                    ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha))
                    ->orderBy('orden'),
                'detalles' => fn(Relation $detalle) => $this->aplicarFechaDetalle($detalle, $fecha)
                    ->whereIn('persona_nivel_ciclo_id', function ($sub) use ($plantillaIds, $fecha) {
                        $sub->select('id')
                            ->from('persona_nivel_ciclos')
                            ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                            ->whereDate('fecha_inicio', '<=', $fecha)
                            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha));
                    })
                    ->where('confirmado', true)
                    ->whereNull('archivado_at')
                    ->with([
                        'personaRole.rolePersona',
                        'grado:id,nombre,nivel_id,orden',
                        'grupo:id,asignacion_grupo_id,nivel_id,grado_id,ciclo_escolar_id',
                        'grupo.asignacionGrupo:id,nombre',
                    ])
                    ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('orden')
                    ->orderBy('id'),
            ])
            ->whereHas('ciclos', function (Builder $membresia) use ($plantillaIds, $fecha) {
                $membresia->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                    ->whereDate('fecha_inicio', '<=', $fecha)
                    ->where(fn (Builder $q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha));
            })
            ->whereHas('persona', function (Builder $persona) use ($ciclo) {
                if ($ciclo->es_actual) {
                    $persona->where('status', true);
                }
            })
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->where(function (Builder $q) use ($fecha) {
                $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha);
            })
            ->when($niveles->isNotEmpty(), fn(Builder $q) => $q->whereIn('nivel_id', $niveles))
            ->whereHas('detalles', function (Builder $detalle) use ($fecha, $gradoId, $grupoId, $rolId, $plantillaIds) {
                $this->aplicarFechaDetalle($detalle, $fecha)
                    ->whereIn('persona_nivel_ciclo_id', function ($sub) use ($plantillaIds, $fecha) {
                        $sub->select('id')
                            ->from('persona_nivel_ciclos')
                            ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                            ->whereDate('fecha_inicio', '<=', $fecha)
                            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha));
                    })
                    ->where('confirmado', true)
                    ->whereNull('archivado_at')
                    ->when($gradoId, fn(Builder $q) => $q->where('grado_id', $gradoId))
                    ->when($grupoId, fn(Builder $q) => $q->where('grupo_id', $grupoId))
                    ->when($rolId, fn(Builder $q) => $q->where('persona_role_id', $rolId));
            })
            ->when(trim($busqueda) !== '', function (Builder $q) use ($busqueda) {
                $termino = '%' . trim($busqueda) . '%';
                $q->whereHas('persona', function (Builder $persona) use ($termino) {
                    $persona->where('nombre', 'like', $termino)
                        ->orWhere('apellido_paterno', 'like', $termino)
                        ->orWhere('apellido_materno', 'like', $termino)
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$termino])
                        ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$termino]);
                });
            })
            ->orderBy('nivel_id')
            ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $listos = collect();
        $advertencias = collect();
        $excluidos = collect();

        $query->groupBy(fn(PersonaNivel $item) => $item->persona_id . ':' . $item->nivel_id)
            ->each(function (Collection $duplicados) use ($listos, $advertencias, $excluidos) {
                /** @var PersonaNivel|null $principal */
                $principal = $duplicados->sortBy(fn(PersonaNivel $item) => [
                    $this->ordenPlantilla($item),
                    is_null($item->orden) ? 1 : 0,
                    (int) ($item->orden ?? PHP_INT_MAX),
                    (int) $item->id,
                ])->first();

                if (!$principal?->persona || trim($this->nombrePersona($principal->persona, false)) === '') {
                    $excluidos->push([
                        'motivo' => 'Registro sin nombre completo.',
                        'modelo' => $principal,
                    ]);
                    return;
                }

                $detalles = $duplicados
                    ->flatMap(fn(PersonaNivel $item) => $item->detalles)
                    ->unique('id')
                    ->sortBy(fn(PersonaNivelDetalle $detalle) => [
                        is_null($detalle->orden) ? 1 : 0,
                        (int) ($detalle->orden ?? PHP_INT_MAX),
                        (int) $detalle->id,
                    ])
                    ->values();

                if ($detalles->isEmpty() || $this->cargos($detalles)->isEmpty()) {
                    $excluidos->push([
                        'motivo' => 'No tiene una función válida en la fecha seleccionada.',
                        'modelo' => $principal,
                    ]);
                    return;
                }

                $principal->setRelation('detalles', $detalles);
                $fila = $this->filaPlantilla($principal);

                if ($duplicados->count() > 1) {
                    $fila['advertencias'][] = 'Se combinaron ' . $duplicados->count() . ' asignaciones generales duplicadas del mismo nivel.';
                    $advertencias->push($fila);
                } else {
                    $listos->push($fila);
                }
            });

        $ordenar = fn(Collection $filas) => $filas
            ->sortBy(fn(array $fila) => [
                (int) ($fila['nivel_id'] ?? PHP_INT_MAX),
                (int) ($fila['orden_plantilla'] ?? PHP_INT_MAX),
                (int) ($fila['id'] ?? PHP_INT_MAX),
            ])
            ->values();

        $listos = $ordenar($listos);
        $advertencias = $ordenar($advertencias);

        return compact('listos', 'advertencias', 'excluidos');
    }

    /**
     * Devuelve el mismo orden visible en "Plantilla de personal por nivel".
     *
     * - Preescolar, primaria y bachillerato se muestran como filas y utilizan
     *   persona_nivel_detalles.orden.
     * - Secundaria se muestra por tarjetas de profesor y utiliza persona_nivel.orden.
     */
    private function ordenPlantilla(PersonaNivel $asignacion): int
    {
        $nivelSlug = Str::lower((string) ($asignacion->nivel?->slug ?? ''));
        $ordenCiclo = $asignacion->relationLoaded('ciclos')
            ? $asignacion->ciclos->first()?->orden
            : null;
        $ordenCabecera = !is_null($ordenCiclo)
            ? (int) $ordenCiclo
            : (is_null($asignacion->orden) ? PHP_INT_MAX : (int) $asignacion->orden);

        if ($nivelSlug === 'secundaria' || str_contains($nivelSlug, 'secund')) {
            return $ordenCabecera;
        }

        $ordenDetalle = collect($asignacion->detalles)
            ->pluck('orden')
            ->filter(fn($orden) => !is_null($orden))
            ->map(fn($orden) => (int) $orden)
            ->min();

        return $ordenDetalle ?? $ordenCabecera;
    }

    /** @param Collection<int, PersonaNivelDetalle> $detalles */
    private function cargos(Collection $detalles): Collection
    {
        return $detalles
            ->map(fn(PersonaNivelDetalle $detalle) => trim((string) ($detalle->personaRole?->rolePersona?->nombre ?? '')))
            ->filter()
            ->unique(fn(string $cargo) => Str::lower($cargo))
            ->values();
    }

    /** @return array<string,mixed> */
    private function filaPlantilla(PersonaNivel $asignacion): array
    {
        $detalles = collect($asignacion->detalles);
        $cargos = $this->cargos($detalles);
        $grados = $detalles->pluck('grado.nombre')->filter()->unique()->values();
        $grupos = $detalles->map(fn(PersonaNivelDetalle $detalle) => $detalle->grupo?->asignacionGrupo?->nombre)
            ->filter()->unique()->values();

        return [
            'id' => (int) $asignacion->id,
            'modelo' => $asignacion,
            'persona_id' => (int) $asignacion->persona_id,
            'nivel_id' => (int) $asignacion->nivel_id,
            'orden_plantilla' => $this->ordenPlantilla($asignacion),
            'persona' => $this->nombrePersona($asignacion->persona),
            'nivel' => (string) ($asignacion->nivel?->nombre ?? 'Sin nivel'),
            'nivel_slug' => (string) ($asignacion->nivel?->slug ?? ''),
            'cargos' => $cargos->all(),
            'grados' => $grados->all(),
            'grupos' => $grupos->all(),
            'es_directivo' => $this->esDirectivo($detalles),
            'advertencias' => [],
        ];
    }

    /**
     * @param array<int,int|string> $ids
     * @return array<int,array<string,mixed>>
     */
    public function construirDocumentos(
        array $ids,
        CicloEscolar $ciclo,
        string $tipo,
        string $fechaDirector,
        string $fechaDocente,
        ?string $copias = null
    ): array {
        if (!array_key_exists($tipo, self::TIPOS)) {
            throw ValidationException::withMessages(['tipoReanudacion' => 'El tipo de reanudación no es válido.']);
        }

        $ids = collect($ids)->map(fn($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages(['seleccionados' => 'Selecciona al menos una persona.']);
        }

        $resultado = $this->plantilla(
            ciclo: $ciclo,
            fechaReferencia: $fechaDocente,
        );

        $filas = $resultado['listos']
            ->concat($resultado['advertencias'])
            ->whereIn('id', $ids)
            ->sortBy(fn(array $fila) => [
                (int) ($fila['nivel_id'] ?? PHP_INT_MAX),
                (int) ($fila['orden_plantilla'] ?? PHP_INT_MAX),
                (int) ($fila['id'] ?? PHP_INT_MAX),
            ])
            ->values();

        $faltantes = $ids->diff($filas->pluck('id')->map(fn($id) => (int) $id));
        if ($faltantes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'seleccionados' => 'Una o más asignaciones ya no están vigentes para la fecha y ciclo seleccionados. Actualiza la lista.',
            ]);
        }

        return $filas->map(function (array $fila) use ($ciclo, $tipo, $fechaDirector, $fechaDocente, $copias) {
            /** @var PersonaNivel $asignacion */
            $asignacion = $fila['modelo'];
            $nivel = $asignacion->nivel;
            $detalles = collect($asignacion->detalles);
            $cargos = $this->cargos($detalles);
            $esDirectivo = $this->esDirectivo($detalles);
            $fechaDocumento = $esDirectivo ? $fechaDirector : $fechaDocente;
            $autoridades = $this->autoridades($nivel);

            $snapshot = [
                'persona' => [
                    'titulo' => $asignacion->persona?->titulo,
                    'nombre' => $asignacion->persona?->nombre,
                    'apellido_paterno' => $asignacion->persona?->apellido_paterno,
                    'apellido_materno' => $asignacion->persona?->apellido_materno,
                ],
                'cargos' => $cargos->all(),
                'roles_slugs' => $detalles->map(fn(PersonaNivelDetalle $d) => $d->personaRole?->rolePersona?->slug)->filter()->unique()->values()->all(),
                'grados' => $detalles->pluck('grado.nombre')->filter()->unique()->values()->all(),
                'grupos' => $detalles->map(fn(PersonaNivelDetalle $d) => $d->grupo?->asignacionGrupo?->nombre)->filter()->unique()->values()->all(),
                'nivel' => [
                    'id' => $nivel?->id,
                    'nombre' => $nivel?->nombre,
                    'slug' => $nivel?->slug,
                    'cct' => $nivel?->cct,
                ],
                'ciclo' => [
                    'id' => $ciclo->id,
                    'inicio_anio' => $ciclo->inicio_anio,
                    'fin_anio' => $ciclo->fin_anio,
                    'nombre' => $ciclo->nombre,
                ],
                'autoridades' => $autoridades,
                'escuela' => $this->escuelaSnapshot(),
            ];

            return [
                'asignacion' => $asignacion,
                'nivel' => $nivel,
                'ciclo' => $ciclo,
                'tipo' => $tipo,
                'fecha_director' => $fechaDirector,
                'fecha_docente' => $fechaDocente,
                'fecha_documento' => $fechaDocumento,
                'copias' => trim((string) $copias) ?: null,
                'persona_nombre' => $this->nombrePersona($asignacion->persona),
                'cargos' => $cargos->all(),
                'es_directivo' => $esDirectivo,
                'grado_resumen' => implode(', ', $snapshot['grados']),
                'grupo_resumen' => implode(', ', $snapshot['grupos']),
                'destinatario_nombre' => $autoridades['destinatario_nombre'],
                'destinatario_cargo' => $autoridades['destinatario_cargo'],
                'snapshot' => $snapshot,
            ];
        })->all();
    }

    /** @param array<int,array<string,mixed>> $documentos */
    public function renderPdf(array $documentos)
    {
        if ($documentos === []) {
            abort(422, 'No hay documentos para generar.');
        }

        $niveles = collect($documentos)->pluck('nivel.id')->filter()->unique();
        abort_if($niveles->count() !== 1, 422, 'La vista PDF debe contener un solo nivel.');

        $primero = $documentos[0];
        /** @var Nivel $nivel */
        $nivel = $primero['nivel'];

        if ($nivel->slug === 'bachillerato') {
            return Pdf::loadView('pdf.reanudaciones_bachillerato', [
                'documentos' => $documentos,
                'tipoLabel' => self::TIPOS[$primero['tipo']] ?? 'Reanudación de labores',
            ])->setPaper('letter', 'portrait');
        }

        $view = match ($primero['tipo']) {
            'receso' => 'pdf.reanudaciones_receso',
            'invierno' => 'pdf.reanudaciones_invierno',
            'primavera' => 'pdf.reanudaciones_primavera',
            default => throw ValidationException::withMessages(['tipoReanudacion' => 'Tipo inválido.']),
        };

        return Pdf::loadView($view, [
            'asignacionesNivel' => collect($documentos)->pluck('asignacion'),
            'fecha_director' => $primero['fecha_director'],
            'fecha_docente' => $primero['fecha_docente'],
            'nivel' => $nivel->loadMissing(['director', 'supervisor']),
            'escuela' => Escuela::query()->first(),
            'delegado' => Director::query()->where('identificador', 'delegado-servicios-educativos-tierra-caliente')->first(),
            'cicloEscolar' => $primero['ciclo'],
            'copias' => $primero['copias'],
            'directorAdministracion' => Director::query()->where('identificador', 'director-general-administracion')->first(),
            'directorMagisterio' => Director::query()->where('identificador', 'director-magisterio-estatal')->first(),
        ])->setPaper('letter', 'portrait')->setOption([
                    'fontDir' => public_path('/fonts'),
                    'fontCache' => public_path('/fonts'),
                ]);
    }

    /** @param array<string,mixed> $documento */
    public function guardarRegistro(array $documento, string $loteUuid): ReanudacionLaboral
    {
        /** @var PersonaNivel $asignacion */
        $asignacion = $documento['asignacion'];
        /** @var Nivel $nivel */
        $nivel = $documento['nivel'];
        /** @var CicloEscolar $ciclo */
        $ciclo = $documento['ciclo'];

        $registro = ReanudacionLaboral::query()->create([
            'lote_uuid' => $loteUuid,
            'persona_nivel_id' => $asignacion->id,
            'persona_id' => $asignacion->persona_id,
            'nivel_id' => $nivel->id,
            'ciclo_escolar_id' => $ciclo->id,
            'tipo_reanudacion' => $documento['tipo'],
            'fecha_director' => $documento['fecha_director'],
            'fecha_docente' => $documento['fecha_docente'],
            'fecha_documento' => $documento['fecha_documento'],
            'copias' => $documento['copias'],
            'persona_nombre' => $documento['persona_nombre'],
            'cargos' => $documento['cargos'],
            'es_directivo' => $documento['es_directivo'],
            'nivel_nombre' => $nivel->nombre,
            'nivel_slug' => $nivel->slug,
            'ciclo_nombre' => $ciclo->nombre,
            'grado_resumen' => $documento['grado_resumen'] ?: null,
            'grupo_resumen' => $documento['grupo_resumen'] ?: null,
            'destinatario_nombre' => $documento['destinatario_nombre'],
            'destinatario_cargo' => $documento['destinatario_cargo'],
            'snapshot' => $documento['snapshot'],
            'creado_por' => auth()->id(),
            'actualizado_por' => auth()->id(),
        ]);

        PersonaNivelHistorial::query()->create([
            'persona_nivel_id' => $asignacion->id,
            'persona_id' => $asignacion->persona_id,
            'nivel_id' => $nivel->id,
            'accion' => 'reanudacion_generada',
            'descripcion' => 'Se generó un oficio de ' . (self::TIPOS[$documento['tipo']] ?? 'reanudación de labores') . '.',
            'datos_nuevos' => [
                'reanudacion_laboral_id' => $registro->id,
                'lote_uuid' => $loteUuid,
                'ciclo_escolar' => $ciclo->nombre,
                'fecha_documento' => $documento['fecha_documento'],
            ],
            'usuario_id' => auth()->id(),
            'fecha' => now(),
        ]);

        return $registro;
    }

    public function documentoDesdeRegistro(ReanudacionLaboral $registro): array
    {
        $ciclo = $registro->cicloEscolar;
        abort_unless($ciclo, 422, 'El ciclo escolar del historial ya no está disponible.');

        $documentos = $this->construirDocumentos(
            ids: [(int) $registro->persona_nivel_id],
            ciclo: $ciclo,
            tipo: $registro->tipo_reanudacion,
            fechaDirector: $registro->fecha_director->format('Y-m-d'),
            fechaDocente: $registro->fecha_docente->format('Y-m-d'),
            copias: $registro->copias,
        );

        return $documentos[0];
    }

    public function fechaSugerida(CicloEscolar $ciclo, string $tipo): string
    {
        return match ($tipo) {
            'receso' => Carbon::create((int) $ciclo->inicio_anio, 8, 19)->toDateString(),
            'invierno' => Carbon::create((int) $ciclo->fin_anio, 1, 8)->toDateString(),
            'primavera' => Carbon::create((int) $ciclo->fin_anio, 4, 13)->toDateString(),
            default => now()->toDateString(),
        };
    }

    public function nombrePersona($persona, bool $incluirTitulo = true): string
    {
        if (!$persona) {
            return '';
        }

        return trim(collect([
            $incluirTitulo ? $persona->titulo : null,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' '));
    }

    /** @param Collection<int, PersonaNivelDetalle> $detalles */
    public function esDirectivo(Collection $detalles): bool
    {
        return $detalles->contains(function (PersonaNivelDetalle $detalle) {
            $rol = $detalle->personaRole?->rolePersona;
            $texto = Str::lower(trim(($rol?->slug ?? '') . ' ' . ($rol?->nombre ?? '')));

            return Str::contains($texto, [
                'director',
                'directora',
                'subdirector',
                'subdirectora',
                'rector',
                'coordinador académico',
            ]);
        });
    }

    /** @return array<string,mixed> */
    private function autoridades(?Nivel $nivel): array
    {
        $supervisor = $nivel?->supervisor;
        $delegado = Director::query()->where('identificador', 'delegado-servicios-educativos-tierra-caliente')->first();
        $destinatario = $supervisor ?: $delegado;

        return [
            'destinatario_nombre' => $this->nombreDirector($destinatario),
            'destinatario_cargo' => Str::upper((string) ($destinatario?->cargo ?: 'AUTORIDAD EDUCATIVA')),
            'director_nombre' => $this->nombreDirector($nivel?->director),
            'director_cargo' => Str::upper((string) ($nivel?->director?->cargo ?: 'DIRECTOR(A)')),
            'supervisor_nombre' => $this->nombreDirector($supervisor),
            'supervisor_cargo' => Str::upper((string) ($supervisor?->cargo ?: 'SUPERVISOR ESCOLAR')),
        ];
    }

    private function nombreDirector($director): string
    {
        if (!$director) {
            return '';
        }

        return trim(collect([
            $director->titulo,
            $director->nombre,
            $director->apellido_paterno,
            $director->apellido_materno,
        ])->filter()->implode(' '));
    }

    /** @return array<string,mixed> */
    private function escuelaSnapshot(): array
    {
        $escuela = Escuela::query()->first();

        return [
            'nombre' => $escuela?->nombre,
            'lema' => $escuela?->lema,
            'ciudad' => $escuela?->ciudad,
            'municipio' => $escuela?->municipio,
            'estado' => $escuela?->estado,
            'regional' => $escuela?->regional,
        ];
    }

    private function aplicarFechaDetalle(Builder|Relation $detalle, string $fecha): Builder|Relation
    {
        return $detalle
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->where(function (Builder $q) use ($fecha) {
                $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha);
            });
    }
}
