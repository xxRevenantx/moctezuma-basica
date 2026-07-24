<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Escuela;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\PersonaNivel;
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

        $recesoHoraIds = $this->obtenerHorasDeReceso($nivel->id, $cicloEscolar->id, $grupos, $horas)
            ->values()
            ->all();

        $paginas = $grupos->map(function (Grupo $grupo) use ($nivel, $cicloEscolar, $recesoHoraIds) {
            return [
                'grupo' => $grupo,
                'titular' => $this->obtenerTitular($nivel->id, $cicloEscolar->id, $grupo),
                'etiqueta_grupo' => $this->etiquetaGrupo($grupo),
                'generacion' => $grupo->generacion?->etiqueta,
                'receso_hora_ids' => $recesoHoraIds,
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
        ])
            ->setPaper('letter', 'landscape')
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

    private function obtenerHorasDeReceso(int $nivelId, int $cicloEscolarId, Collection $grupos, Collection $horas): Collection
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
            ->pluck('hora_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function obtenerTitular(int $nivelId, int $cicloEscolarId, Grupo $grupo): ?string
    {
        $personaNivel = PersonaNivel::query()
            ->with('persona:id,titulo,nombre,apellido_paterno,apellido_materno')
            ->where('nivel_id', $nivelId)
            ->whereHas('detalles', function ($query) use ($grupo, $cicloEscolarId) {
                $query
                    ->vigenteEnCiclo($cicloEscolarId)
                    ->where('grado_id', $grupo->grado_id)
                    ->where('grupo_id', $grupo->id)
                    ->where(function ($condicion) {
                        $condicion->where('es_titular_principal', true)
                            ->orWhere('es_titular', true);
                    });
            })
            ->first();

        $persona = $personaNivel?->persona;

        if (!$persona) {
            return null;
        }

        $nombre = trim(collect([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' '));

        return $nombre !== '' ? $nombre : null;
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
