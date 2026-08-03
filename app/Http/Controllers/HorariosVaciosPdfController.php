<?php

namespace App\Http\Controllers;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Escuela;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\PersonaNivelDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HorariosVaciosPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $datos = $request->validate([
            'slug_nivel' => ['required', 'string'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'alcance' => ['required', 'in:nivel,grado,grupos'],
            'generacion_id' => ['nullable', 'integer', 'exists:generaciones,id'],
            'grado_id' => ['nullable', 'integer', 'exists:grados,id'],
            'semestre_id' => ['nullable', 'integer', 'exists:semestres,id'],
            'grupos_seleccionados' => ['nullable', 'array'],
            'grupos_seleccionados.*' => ['integer', 'exists:grupos,id'],
            'hora_inicio_id' => ['required', 'integer', 'exists:horas,id'],
            'hora_fin_id' => ['required', 'integer', 'exists:horas,id'],
            'estilo_celda' => ['required', 'in:vacia,lineas,campos'],
        ]);

        $nivel = Nivel::query()->where('slug', $datos['slug_nivel'])->firstOrFail();
        $cicloEscolar = CicloEscolar::query()->findOrFail($datos['ciclo_escolar_id']);
        $esBachillerato = (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';

        if (in_array($datos['alcance'], ['grado', 'grupos'], true)) {
            abort_unless(
                !empty($datos['generacion_id']) && !empty($datos['grado_id']),
                422,
                'Para imprimir por grado o grupos debes seleccionar generación y grado.'
            );

            if ($esBachillerato) {
                abort_unless(!empty($datos['semestre_id']), 422, 'Para bachillerato debes seleccionar un semestre.');
            }
        }

        if ($datos['alcance'] === 'grupos') {
            abort_unless(!empty($datos['grupos_seleccionados']), 422, 'Debes seleccionar al menos un grupo.');
        }

        $grupos = $this->consultarGrupos(
            nivel: $nivel,
            cicloEscolar: $cicloEscolar,
            alcance: $datos['alcance'],
            generacionId: isset($datos['generacion_id']) ? (int) $datos['generacion_id'] : null,
            gradoId: isset($datos['grado_id']) ? (int) $datos['grado_id'] : null,
            semestreId: isset($datos['semestre_id']) ? (int) $datos['semestre_id'] : null,
            gruposSeleccionados: collect($datos['grupos_seleccionados'] ?? [])->map(fn ($id) => (int) $id)->all(),
        );

        abort_if($grupos->isEmpty(), 404, 'No se encontraron grupos para los filtros seleccionados.');

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

        $recesosPorGrupo = $this->obtenerHorasDeRecesoPorGrupo(
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
            ->setPaper('letter', 'portrait')
            ->stream($nombreArchivo);
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

    private function obtenerHorasDeRecesoPorGrupo(
        int $nivelId,
        int $cicloEscolarId,
        Collection $grupos,
        Collection $horas,
    ): Collection
    {
        $grupoIds = $grupos->pluck('id')->all();
        $horaIds = $horas->pluck('id')->all();

        if (empty($grupoIds) || empty($horaIds)) {
            return collect();
        }

        return Horario::query()
            ->with('asignacionMateria.materia:id,receso')
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('grupo_id', $grupoIds)
            ->whereIn('hora_id', $horaIds)
            ->get(['id', 'grupo_id', 'hora_id', 'asignacion_materia_id', 'taller_sesion_id'])
            ->filter(function (Horario $horario) {
                return !$horario->taller_sesion_id
                    && (int) ($horario->asignacionMateria?->materia?->receso ?? 0) === 1;
            })
            ->groupBy(fn (Horario $horario) => (int) $horario->grupo_id)
            ->map(function (Collection $horariosGrupo) {
                return $horariosGrupo
                    ->pluck('hora_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
            });
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

        $nombre = trim(collect([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' '));

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
    ): array
    {
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
                $nombreProfesor = trim(collect([
                    $profesor?->titulo,
                    $profesor?->nombre,
                    $profesor?->apellido_paterno,
                    $profesor?->apellido_materno,
                ])->filter()->implode(' '));

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
