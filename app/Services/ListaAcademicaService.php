<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\TallerSesion;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListaAcademicaService
{
    /**
     * Devuelve la matrícula vigente de la asignación. El ciclo escolar se
     * conserva en la firma por compatibilidad con calificaciones y reportes,
     * pero la pertenencia del alumno se determina por su generación y
     * ubicación actuales guardadas en inscripciones.
     */
    public function alumnosDeAsignacion(
        AsignacionMateria $asignacion,
        CarbonInterface|string|null $fechaCorte = null
    ): Collection {
        return $this->alumnosPorContexto(
            cicloEscolarId: (int) $asignacion->ciclo_escolar_id,
            grupoIds: [(int) $asignacion->grupo_id],
            fechaCorte: $fechaCorte,
            nivelId: $asignacion->nivel_id,
            gradoId: $asignacion->grado_id,
            generacionId: $asignacion->generacion_id,
            semestreId: $asignacion->semestre_id,
        );
    }

    public function alumnosDeTaller(
        TallerSesion $sesion,
        Grupo|int $grupo,
        CarbonInterface|string|null $fechaCorte = null
    ): Collection {
        $grupo = $grupo instanceof Grupo ? $grupo : Grupo::query()->findOrFail($grupo);

        return $this->alumnosPorContexto(
            cicloEscolarId: (int) $sesion->ciclo_escolar_id,
            grupoIds: [(int) $grupo->id],
            fechaCorte: $fechaCorte,
            nivelId: $grupo->nivel_id,
            gradoId: $grupo->grado_id,
            generacionId: $grupo->generacion_id,
            semestreId: $grupo->semestre_id,
        );
    }

    /**
     * @param array<int> $grupoIds
     */
    public function alumnosPorContexto(
        int $cicloEscolarId,
        array $grupoIds,
        CarbonInterface|string|null $fechaCorte = null,
        ?int $nivelId = null,
        ?int $gradoId = null,
        ?int $generacionId = null,
        ?int $semestreId = null,
    ): Collection {
        $grupoIds = array_values(array_unique(array_filter(array_map('intval', $grupoIds))));

        return Inscripcion::query()
            ->with(['nivel', 'grado', 'semestre', 'grupo.asignacionGrupo', 'generacion'])
            ->when($grupoIds !== [], fn ($q) => $q->whereIn('grupo_id', $grupoIds))
            ->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn ($q) => $q->where('grado_id', $gradoId))
            ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn ($q) => $q->where('semestre_id', $semestreId))
            ->where('activo', true)
            ->whereNotIn('estatus', [
                'baja_temporal',
                'baja_definitiva',
                'trasladado',
                'suspendido',
                'egresado',
                'inactivo',
            ])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    public function fechaCorte(CarbonInterface|string|null $fecha): Carbon
    {
        if ($fecha instanceof CarbonInterface) {
            return Carbon::instance($fecha)->startOfDay();
        }

        return filled($fecha)
            ? Carbon::parse($fecha)->startOfDay()
            : now()->startOfDay();
    }
}
