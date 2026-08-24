<?php

namespace App\Http\Controllers;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Escuela;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\TallerSesion;
use App\Services\HorarioRecesoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HorariosVaciosPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $datos = $request->validate([
            'slug_nivel' => ['required', 'string'],
            'tipo_formato' => ['nullable', 'in:grupos,profesores'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'alcance' => ['nullable', 'in:nivel,grado,grupos'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupos_seleccionados' => ['nullable', 'array'],
            'grupos_seleccionados.*' => ['integer', 'exists:grupos,id'],
            'profesores_seleccionados' => ['nullable', 'array'],
            'profesores_seleccionados.*' => ['integer', 'exists:personas,id'],
            'hora_inicio_id' => ['required', 'integer', 'exists:horas,id'],
            'hora_fin_id' => ['required', 'integer', 'exists:horas,id'],
            'estilo_celda' => ['required', 'in:vacia,lineas,campos'],
        ]);

        $tipoFormato = $datos['tipo_formato'] ?? 'grupos';
        $alcance = $datos['alcance'] ?? 'nivel';
        $nivel = Nivel::query()->where('slug', $datos['slug_nivel'])->firstOrFail();
        $cicloEscolar = CicloEscolar::query()->findOrFail($datos['ciclo_escolar_id']);
        $esBachillerato = (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';

        $dias = Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'nivel_id', 'dia', 'orden']);

        $horasNivel = Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get(['id', 'nivel_id', 'hora_inicio', 'hora_fin', 'orden']);

        abort_if($dias->isEmpty(), 404, 'No hay días configurados para este nivel.');
        abort_if($horasNivel->isEmpty(), 404, 'No hay horas configuradas para este nivel.');

        $horas = $this->filtrarHoras($horasNivel, (int) $datos['hora_inicio_id'], (int) $datos['hora_fin_id']);
        abort_if($horas->isEmpty(), 404, 'No se pudo determinar el rango de horas a imprimir.');

        if ($tipoFormato === 'profesores') {
            return $this->generarHorariosProfesores(
                nivel: $nivel,
                cicloEscolar: $cicloEscolar,
                dias: $dias,
                horas: $horas,
                profesoresSeleccionados: collect($datos['profesores_seleccionados'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                estiloCelda: $datos['estilo_celda'],
            );
        }

        if (in_array($alcance, ['grado', 'grupos'], true)) {
            abort_unless(
                !empty($datos['generacion_id']) && !empty($datos['grado_id']),
                422,
                'Para imprimir por grado o grupos debes seleccionar generación y grado.'
            );

            if ($esBachillerato) {
                abort_unless(!empty($datos['semestre_id']), 422, 'Para bachillerato debes seleccionar un semestre.');
            }
        }

        if ($alcance === 'grupos') {
            abort_unless(!empty($datos['grupos_seleccionados']), 422, 'Debes seleccionar al menos un grupo.');
        }

        $grupos = $this->consultarGrupos(
            nivel: $nivel,
            cicloEscolar: $cicloEscolar,
            alcance: $alcance,
            generacionId: isset($datos['generacion_id']) ? (int) $datos['generacion_id'] : null,
            gradoId: isset($datos['grado_id']) ? (int) $datos['grado_id'] : null,
            semestreId: isset($datos['semestre_id']) ? (int) $datos['semestre_id'] : null,
            gruposSeleccionados: collect($datos['grupos_seleccionados'] ?? [])->map(fn ($id) => (int) $id)->all(),
        );

        abort_if($grupos->isEmpty(), 404, 'No se encontraron grupos para los filtros seleccionados.');

        $recesosPorGrupo = app(HorarioRecesoService::class)->porGrupo(
            $nivel->id,
            $cicloEscolar->id,
            $grupos,
            $horas,
        );

        $paginas = $grupos->map(function (Grupo $grupo) use ($nivel, $cicloEscolar, $recesosPorGrupo) {
            $titular = $this->obtenerTitular($nivel->id, $cicloEscolar->id, $grupo);
            $materias = $this->obtenerMateriasAsignadas(
                nivelId: $nivel->id,
                cicloEscolarId: $cicloEscolar->id,
                grupo: $grupo,
                titularId: $titular['id'] ?? null,
                nivelSlug: (string) $nivel->slug,
            );

            return [
                'grupo' => $grupo,
                'titular' => $titular['nombre'] ?? null,
                'titular_id' => $titular['id'] ?? null,
                'materias_docentes' => $materias['filas'],
                'mensaje_materias' => $materias['mensaje'],
                'etiqueta_grupo' => $this->etiquetaGrupo($grupo),
                'generacion' => $grupo->generacion?->etiqueta,
                'receso_hora_ids' => collect($recesosPorGrupo->get((int) $grupo->id, []))
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all(),
            ];
        });

        $escuela = Escuela::query()->first();
        abort_if(!$escuela, 404, 'No se encontró la información de la escuela.');

        $nombreArchivo = sprintf(
            'FORMATO_HORARIO_%s_%s-%s.pdf',
            mb_strtoupper(Str::slug((string) $nivel->nombre, '_'), 'UTF-8'),
            $cicloEscolar->inicio_anio,
            $cicloEscolar->fin_anio,
        );

        return Pdf::loadView('pdf.horarios-vacios', [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'cicloEscolar' => $cicloEscolar,
            'dias' => $dias,
            'horas' => $horas,
            'paginas' => $paginas,
            'estiloCelda' => $datos['estilo_celda'],
            'logoIzquierdo' => $this->imagenBase64Publica('imagenes/logo-letra.png'),
            'logoDerecho' => $this->imagenBase64Publica(
                !empty($nivel->logo)
                    ? 'storage/logos/' . $nivel->logo
                    : 'imagenes/logo-letra.png'
            ),
            'imagenNivel' => $this->imagenBase64Publica(match ((string) $nivel->slug) {
                'preescolar' => 'imagenes/personajes_preescolar.png',
                'primaria' => 'imagenes/personajes_primaria.png',
                'secundaria' => 'imagenes/personajes_secundaria.png',
                'bachillerato' => 'imagenes/personajes_bachillerato.png',
                default => null,
            }),
        ])
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
    }

    private function generarHorariosProfesores(
        Nivel $nivel,
        CicloEscolar $cicloEscolar,
        Collection $dias,
        Collection $horas,
        array $profesoresSeleccionados,
        string $estiloCelda,
    ) {
        abort_if(empty($profesoresSeleccionados), 422, 'Debes seleccionar al menos un profesor.');

        $profesores = $this->consultarProfesoresActivos(
            nivel: $nivel,
            cicloEscolar: $cicloEscolar,
            profesoresSeleccionados: $profesoresSeleccionados,
        );

        abort_if($profesores->isEmpty(), 404, 'No se encontraron profesores activos para la selección realizada.');

        $idsValidos = $profesores->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $idsSolicitados = collect($profesoresSeleccionados)->map(fn ($id) => (int) $id)->sort()->values();

        abort_unless(
            $idsValidos->all() === $idsSolicitados->all(),
            422,
            'Uno o más profesores seleccionados ya no están activos en la plantilla de este nivel y ciclo.'
        );

        $gruposNivel = $this->consultarGrupos(
            nivel: $nivel,
            cicloEscolar: $cicloEscolar,
            alcance: 'nivel',
            generacionId: null,
            gradoId: null,
            semestreId: null,
            gruposSeleccionados: [],
        );

        $recesoOficial = app(HorarioRecesoService::class)->oficialNivel(
            $nivel->id,
            $cicloEscolar->id,
            $gruposNivel,
            $horas,
        );

        $paginas = $profesores
            ->map(function (Persona $profesor) use ($nivel, $cicloEscolar) {
                $carga = $this->obtenerCargaAcademicaProfesor(
                    nivelId: (int) $nivel->id,
                    cicloEscolarId: (int) $cicloEscolar->id,
                    profesorId: (int) $profesor->id,
                );

                return [
                    'profesor' => $profesor,
                    'profesor_nombre' => $this->nombrePersona($profesor),
                    'carga' => $carga,
                    'sin_carga' => $carga->isEmpty(),
                ];
            })
            ->sortBy(fn (array $pagina) => Str::lower(Str::ascii($pagina['profesor_nombre'])))
            ->values();

        $escuela = Escuela::query()->first();
        abort_if(!$escuela, 404, 'No se encontró la información de la escuela.');

        $nombreArchivo = sprintf(
            'HORARIOS_INDIVIDUALES_DOCENTES_%s_%s-%s.pdf',
            mb_strtoupper(Str::slug((string) $nivel->nombre, '_'), 'UTF-8'),
            $cicloEscolar->inicio_anio,
            $cicloEscolar->fin_anio,
        );

        return Pdf::loadView('pdf.horarios-vacios-profesores', [
            'escuela' => $escuela,
            'nivel' => $nivel,
            'cicloEscolar' => $cicloEscolar,
            'dias' => $dias,
            'horas' => $horas,
            'paginas' => $paginas,
            'estiloCelda' => $estiloCelda,
            'recesoHoraIds' => collect($recesoOficial['hora_ids'] ?? [])->map(fn ($id) => (int) $id)->values(),
            'recesoInconsistente' => (bool) ($recesoOficial['inconsistente'] ?? false),
            'recesoVariantes' => (int) ($recesoOficial['variantes'] ?? 0),
            'gruposEvaluadosReceso' => (int) ($recesoOficial['grupos_evaluados'] ?? 0),
            'logoIzquierdo' => $this->imagenBase64Publica('imagenes/logo-letra.png'),
            'logoDerecho' => $this->imagenBase64Publica(
                !empty($nivel->logo)
                    ? 'storage/logos/' . $nivel->logo
                    : 'imagenes/logo-letra.png'
            ),
        ])
            ->setPaper('letter', 'landscape')
            ->stream($nombreArchivo);
    }

    private function consultarProfesoresActivos(
        Nivel $nivel,
        CicloEscolar $cicloEscolar,
        array $profesoresSeleccionados,
    ): Collection {
        $ids = PersonaNivelDetalle::query()
            ->with('cabecera:id,persona_id,nivel_id,estado')
            ->vigenteEnCiclo((int) $cicloEscolar->id)
            ->whereHas('personaRole.rolePersona', fn (Builder $q) => $q
                ->where('status', true)
                ->where('es_docente', true))
            ->whereHas('cabecera', fn (Builder $q) => $q
                ->where('nivel_id', $nivel->id)
                ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                ->whereHas('persona', fn (Builder $p) => $p
                    ->where('status', true)
                    ->where('estado_laboral', 'activo')))
            ->get()
            ->pluck('cabecera.persona_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->intersect($profesoresSeleccionados)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Persona::query()
            ->whereIn('id', $ids)
            ->where('status', true)
            ->where('estado_laboral', 'activo')
            ->get(['id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->sortBy(fn (Persona $persona) => Str::lower(Str::ascii($this->nombrePersona($persona))))
            ->values();
    }

    private function obtenerCargaAcademicaProfesor(
        int $nivelId,
        int $cicloEscolarId,
        int $profesorId,
    ): Collection {
        $materias = AsignacionMateria::query()
            ->with([
                'materia:id,materia,orden,receso',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'grupo.grado:id,nombre,orden',
                'grupo.semestre:id,numero,orden_global',
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('profesor_id', $profesorId)
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->whereHas('materia', fn (Builder $q) => $q->where('receso', false))
            ->get()
            ->map(function (AsignacionMateria $asignacion) {
                if (!$asignacion->materia || !$asignacion->grupo) {
                    return null;
                }

                return [
                    'tipo' => 'Materia',
                    'materia' => trim((string) $asignacion->materia->materia),
                    'grupo' => $this->etiquetaGrupo($asignacion->grupo),
                    'orden' => (int) ($asignacion->orden ?? $asignacion->materia->orden ?? 999999),
                ];
            })
            ->filter();

        $talleres = TallerSesion::query()
            ->with([
                'taller:id,nivel_id,nombre,clave',
                'grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupos.asignacionGrupo:id,nombre',
                'grupos.grado:id,nombre,orden',
                'grupos.semestre:id,numero,orden_global',
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('profesor_id', $profesorId)
            ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
            ->whereHas('taller', fn (Builder $q) => $q->where('nivel_id', $nivelId))
            ->get()
            ->map(function (TallerSesion $sesion) {
                if (!$sesion->taller) {
                    return null;
                }

                $grupos = $sesion->grupos
                    ->map(fn (Grupo $grupo) => $this->etiquetaGrupo($grupo))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->implode(', ');

                return [
                    'tipo' => 'Taller',
                    'materia' => trim((string) $sesion->taller->nombre) ?: 'Taller',
                    'grupo' => $grupos !== '' ? $grupos : 'Varios grupos',
                    'orden' => 999998,
                ];
            })
            ->filter();

        return $materias
            ->concat($talleres)
            ->unique(fn (array $item) => Str::lower(Str::ascii(
                $item['tipo'] . '|' . $item['materia'] . '|' . $item['grupo']
            )))
            ->sortBy(fn (array $item) => sprintf(
                '%06d-%s-%s',
                (int) $item['orden'],
                Str::lower(Str::ascii($item['materia'])),
                Str::lower(Str::ascii($item['grupo'])),
            ))
            ->values();
    }

    private function consultarGrupos(
        Nivel $nivel,
        CicloEscolar $cicloEscolar,
        string $alcance,
        ?int $generacionId,
        ?int $gradoId,
        ?int $semestreId,
        array $gruposSeleccionados,
    ): Collection {
        $consulta = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso,status',
                'grado:id,nombre,orden',
                'semestre:id,numero,orden_global',
            ])
            ->where('nivel_id', $nivel->id)
            ->where('ciclo_escolar_id', $cicloEscolar->id);

        if (in_array($alcance, ['grado', 'grupos'], true)) {
            $consulta->where('generacion_id', $generacionId)
                ->where('grado_id', $gradoId);

            if ($semestreId) {
                $consulta->where('semestre_id', $semestreId);
            } elseif ($nivel->slug === 'bachillerato') {
                $consulta->whereNotNull('semestre_id');
            }
        }

        if ($alcance === 'grupos') {
            $consulta->whereIn('id', $gruposSeleccionados);
        }

        return $consulta
            ->get(['id', 'ciclo_escolar_id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id'])
            ->sortBy(function (Grupo $grupo) {
                return sprintf(
                    '%06d-%06d-%s-%06d',
                    (int) ($grupo->grado?->orden ?? 999999),
                    (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
                    Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
                    (int) $grupo->id,
                );
            })
            ->values();
    }

    private function filtrarHoras(Collection $horasNivel, int $horaInicioId, int $horaFinId): Collection
    {
        $indiceInicio = $horasNivel->search(fn (Hora $hora) => (int) $hora->id === $horaInicioId);
        $indiceFin = $horasNivel->search(fn (Hora $hora) => (int) $hora->id === $horaFinId);

        if ($indiceInicio === false || $indiceFin === false) {
            return collect();
        }

        $inicio = min($indiceInicio, $indiceFin);
        $fin = max($indiceInicio, $indiceFin);

        return $horasNivel->slice($inicio, $fin - $inicio + 1)->values();
    }

    /**
     * @return array{id:int,nombre:string}|null
     */
    private function obtenerTitular(int $nivelId, int $cicloEscolarId, Grupo $grupo): ?array
    {
        /*
         * El titular se obtiene de la plantilla publicada o cerrada del ciclo.
         * Primero se respetan las banderas explícitas; para registros históricos
         * también se reconoce la función de frente a grupo en preescolar y
         * primaria, siempre que coincidan nivel, grado y grupo.
         */
        $detalle = PersonaNivelDetalle::query()
            ->with([
                'cabecera.persona:id,titulo,nombre,apellido_paterno,apellido_materno',
                'cabecera.nivel:id,slug',
                'personaRole.rolePersona:id,slug',
            ])
            ->vigenteEnCiclo($cicloEscolarId)
            ->where('grado_id', $grupo->grado_id)
            ->where('grupo_id', $grupo->id)
            ->whereHas('cabecera', fn ($query) => $query->where('nivel_id', $nivelId))
            ->titularReconocido()
            ->orderByRaw("CASE WHEN es_titular_principal = 1 THEN 0 WHEN es_titular = 1 THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN estado = 'activo' THEN 0 ELSE 1 END")
            ->orderBy('orden')
            ->orderBy('id')
            ->first();

        $persona = $detalle?->cabecera?->persona;

        if (!$persona) {
            return null;
        }

        $nombre = $this->nombrePersona($persona);

        if ($nombre === '') {
            return null;
        }

        return [
            'id' => (int) $persona->id,
            'nombre' => $nombre,
        ];
    }

    /**
     * Obtiene la tabla inferior con la misma lógica visual del horario lleno.
     * En preescolar y primaria se excluye al titular para mostrar únicamente
     * materias impartidas por personal distinto al responsable del grupo.
     *
     * @return array{filas:Collection<int,array<string,mixed>>,mensaje:?string}
     */
    private function obtenerMateriasAsignadas(
        int $nivelId,
        int $cicloEscolarId,
        Grupo $grupo,
        ?int $titularId,
        string $nivelSlug,
    ): array {
        $asignaciones = AsignacionMateria::query()
            ->with([
                'materia:id,materia,orden,receso',
                'profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
            ])
            ->utilizables()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $grupo->grado_id)
            ->where('grupo_id', $grupo->id)
            ->when(
                $grupo->generacion_id,
                fn ($query) => $query->where('generacion_id', $grupo->generacion_id)
            )
            ->when(
                $grupo->semestre_id,
                fn ($query) => $query->where('semestre_id', $grupo->semestre_id),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->whereHas('materia', fn ($query) => $query->where('receso', false))
            ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('orden')
            ->orderBy('materia_id')
            ->get(['id', 'materia_id', 'profesor_id', 'orden'])
            ->filter(fn (AsignacionMateria $asignacion) => filled($asignacion->materia?->materia))
            ->unique(fn (AsignacionMateria $asignacion) => implode('-', [
                (int) $asignacion->materia_id,
                (int) ($asignacion->profesor_id ?? 0),
            ]))
            ->values();

        if ($asignaciones->isEmpty()) {
            return [
                'filas' => collect(),
                'mensaje' => 'Aún no hay materias asignadas para este grupo en el ciclo escolar seleccionado.',
            ];
        }

        $excluirTitular = in_array($nivelSlug, ['preescolar', 'primaria'], true) && $titularId !== null;

        $asignacionesVisibles = $asignaciones
            ->reject(function (AsignacionMateria $asignacion) use ($excluirTitular, $titularId) {
                return $excluirTitular
                    && $asignacion->profesor_id !== null
                    && (int) $asignacion->profesor_id === (int) $titularId;
            })
            ->values();

        if ($asignacionesVisibles->isEmpty()) {
            return [
                'filas' => collect(),
                'mensaje' => 'No hay materias asignadas a docentes distintos del titular del grupo.',
            ];
        }

        $filas = $asignacionesVisibles
            ->map(function (AsignacionMateria $asignacion) {
                $profesor = $asignacion->profesor;
                $nombreProfesor = $this->nombrePersona($profesor);

                return [
                    'profesor_id' => $profesor?->id ? (int) $profesor->id : null,
                    'docente' => $nombreProfesor !== '' ? $nombreProfesor : 'Sin docente asignado',
                    'materia' => trim((string) $asignacion->materia->materia),
                    'orden' => (int) ($asignacion->orden ?? $asignacion->materia?->orden ?? 999999),
                    'sin_docente' => !$profesor?->id,
                ];
            })
            ->groupBy(fn (array $item) => $item['profesor_id'] !== null
                ? 'profesor-' . $item['profesor_id']
                : 'sin-docente')
            ->map(function (Collection $items) {
                $itemsOrdenados = $items
                    ->sortBy([
                        ['orden', 'asc'],
                        ['materia', 'asc'],
                    ])
                    ->values();

                $primero = $itemsOrdenados->first();

                return [
                    'profesor_id' => $primero['profesor_id'],
                    'docente' => $primero['docente'],
                    'materias' => $itemsOrdenados
                        ->pluck('materia')
                        ->filter()
                        ->unique(fn ($materia) => mb_strtoupper(trim((string) $materia), 'UTF-8'))
                        ->values()
                        ->all(),
                    'orden' => (int) $itemsOrdenados->min('orden'),
                    'sin_docente' => (bool) $primero['sin_docente'],
                ];
            })
            ->sortBy([
                ['orden', 'asc'],
                ['docente', 'asc'],
            ])
            ->values();

        return [
            'filas' => $filas,
            'mensaje' => null,
        ];
    }

    private function etiquetaGrupo(Grupo $grupo): string
    {
        return trim(collect([
            $grupo->grado?->nombre,
            $grupo->semestre ? 'Semestre ' . $grupo->semestre->numero : null,
            $grupo->asignacionGrupo?->nombre,
        ])->filter()->implode(' · '));
    }

    private function nombrePersona(?Persona $persona): string
    {
        if (!$persona) {
            return '';
        }

        return trim(collect([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' '));
    }

    private function imagenBase64Publica(?string $rutaRelativa): ?string
    {
        if (!$rutaRelativa) {
            return null;
        }

        $ruta = public_path($rutaRelativa);

        if (!is_file($ruta)) {
            return null;
        }

        $contenido = @file_get_contents($ruta);
        if ($contenido === false) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }
}
