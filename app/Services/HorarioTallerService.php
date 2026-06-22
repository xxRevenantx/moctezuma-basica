<?php

namespace App\Services;

use App\Models\Dia;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Persona;
use App\Models\TallerSesion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HorarioTallerService
{
    /**
     * Detecta conflictos de grupo y profesor contra materias normales y otros
     * talleres conjuntos. Los talleres de ciclos distintos no se consideran.
     */
    public function detectarConflictos(
        array $grupoIds,
        int $profesorId,
        int $cicloEscolarId,
        int $diaId,
        int $horaId,
        ?int $sesionActualId = null
    ): array {
        $grupoIds = collect($grupoIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $dia = Dia::query()->find($diaId);
        $hora = Hora::query()->find($horaId);

        if (!$dia || !$hora || empty($grupoIds)) {
            return [];
        }

        $diaIdsEquivalentes = Dia::query()
            ->whereRaw('LOWER(dia) = ?', [mb_strtolower($dia->dia)])
            ->pluck('id');

        $registros = Horario::query()
            ->with([
                'nivel:id,nombre',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'dia:id,dia',
                'hora:id,hora_inicio,hora_fin',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id',
                'asignacionMateria.materia:id,materia',
                'asignacionMateria.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion:id,taller_id,profesor_id,ciclo_escolar_id,dia_id,hora_id',
                'tallerSesion.taller:id,nombre',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion.grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'tallerSesion.grupos.asignacionGrupo:id,nombre',
                'tallerSesion.grupos.grado:id,nombre,orden',
            ])
            ->whereIn('dia_id', $diaIdsEquivalentes)
            ->whereHas('hora', function ($query) use ($hora) {
                $query->where('hora_inicio', '<', $hora->hora_fin)
                    ->where('hora_fin', '>', $hora->hora_inicio);
            })
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->when($sesionActualId, function ($query) use ($sesionActualId) {
                $query->where(function ($subQuery) use ($sesionActualId) {
                    $subQuery->whereNull('taller_sesion_id')
                        ->orWhere('taller_sesion_id', '!=', $sesionActualId);
                });
            })
            ->where(function ($query) use ($grupoIds, $profesorId) {
                $query->whereIn('grupo_id', $grupoIds)
                    ->orWhereHas('asignacionMateria', function ($subQuery) use ($profesorId) {
                        $subQuery->where('profesor_id', $profesorId);
                    })
                    ->orWhereHas('tallerSesion', function ($subQuery) use ($profesorId) {
                        $subQuery->where('profesor_id', $profesorId);
                    });
            })
            ->get();

        return $registros
            ->groupBy(fn(Horario $horario) => $horario->taller_sesion_id
                ? 'taller-' . $horario->taller_sesion_id
                : 'horario-' . $horario->id)
            ->map(function (Collection $items) use ($grupoIds, $profesorId) {
                /** @var Horario $primero */
                $primero = $items->first();
                $esTaller = $primero->esTallerConjunto();
                $profesor = $primero->profesorActividad();

                $gruposActividad = $esTaller
                    ? $primero->tallerSesion?->grupos ?? collect()
                    : collect([$primero->grupo])->filter();

                $conflictoGrupo = $items->contains(
                    fn(Horario $item) => in_array((int) $item->grupo_id, $grupoIds, true)
                );

                $conflictoProfesor = (int) ($profesor?->id ?? 0) === $profesorId;

                $motivos = collect([
                    $conflictoGrupo ? 'grupo ocupado' : null,
                    $conflictoProfesor ? 'profesor ocupado' : null,
                ])->filter()->values()->all();

                return [
                    'clave' => $esTaller
                        ? 'taller-' . $primero->taller_sesion_id
                        : 'horario-' . $primero->id,
                    'tipo' => $esTaller ? 'Taller conjunto' : 'Materia',
                    'actividad' => $primero->nombreActividad(),
                    'profesor' => $this->nombrePersona($profesor),
                    'grupos' => $gruposActividad
                        ->map(fn(Grupo $grupo) => $this->nombreGrupo($grupo))
                        ->unique()
                        ->values()
                        ->implode(', '),
                    'dia' => $primero->dia?->dia ?? 'Día no definido',
                    'hora' => trim(
                        ($primero->hora?->hora_inicio ?? '') . ' - ' .
                        ($primero->hora?->hora_fin ?? '')
                    ),
                    'motivos' => $motivos,
                    'motivos_texto' => implode(' y ', $motivos),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Proyecta una sesión compartida en el horario de cada grupo. La sesión es
     * la fuente de verdad; estas filas permiten reutilizar consultas y reportes.
     */
    public function sincronizarHorarios(TallerSesion $sesion): void
    {
        $sesion->loadMissing('grupos');

        Horario::query()
            ->where('taller_sesion_id', $sesion->id)
            ->delete();

        foreach ($sesion->grupos as $grupo) {
            Horario::query()->create([
                'nivel_id' => $grupo->nivel_id,
                'grado_id' => $grupo->grado_id,
                'generacion_id' => $grupo->generacion_id,
                'semestre_id' => $grupo->semestre_id,
                'grupo_id' => $grupo->id,
                'hora_id' => $sesion->hora_id,
                'dia_id' => $sesion->dia_id,
                'asignacion_materia_id' => null,
                'taller_sesion_id' => $sesion->id,
                'ciclo_escolar_id' => $sesion->ciclo_escolar_id,
            ]);
        }
    }

    public function nombreGrupo(?Grupo $grupo): string
    {
        if (!$grupo) {
            return 'Grupo no definido';
        }

        $grado = trim((string) ($grupo->grado?->nombre ?? ''));
        $nombre = trim((string) ($grupo->asignacionGrupo?->nombre ?? ''));

        return trim($grado . ($nombre !== '' ? ' ' . $nombre : '')) ?: 'Grupo ' . $grupo->id;
    }

    public function nombrePersona(?Persona $persona): string
    {
        if (!$persona) {
            return 'Sin profesor asignado';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])));
    }
}
