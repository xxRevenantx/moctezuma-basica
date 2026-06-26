<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\TallerSesion;
use App\Models\TrayectoriaAcademica;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListaAcademicaService
{
    /**
     * Obtiene los alumnos que realmente pertenecían al grupo de la carga en la
     * fecha de corte indicada. No usa la ubicación actual de inscripciones.
     */
    public function alumnosDeAsignacion(
        AsignacionMateria $asignacion,
        CarbonInterface|string|null $fechaCorte = null
    ): Collection {
        $fecha = $this->fechaCorte($fechaCorte);

        return $this->alumnosPorContexto(
            cicloEscolarId: (int) $asignacion->ciclo_escolar_id,
            grupoIds: [(int) $asignacion->grupo_id],
            fechaCorte: $fecha,
            nivelId: $asignacion->nivel_id,
            gradoId: $asignacion->grado_id,
            generacionId: $asignacion->generacion_id,
            semestreId: $asignacion->semestre_id,
        );
    }

    /**
     * Obtiene los alumnos históricos de un grupo incluido en un taller.
     */
    public function alumnosDeTaller(
        TallerSesion $sesion,
        Grupo|int $grupo,
        CarbonInterface|string|null $fechaCorte = null
    ): Collection {
        $grupo = $grupo instanceof Grupo ? $grupo : Grupo::query()->findOrFail($grupo);

        return $this->alumnosPorContexto(
            cicloEscolarId: (int) $sesion->ciclo_escolar_id,
            grupoIds: [(int) $grupo->id],
            fechaCorte: $this->fechaCorte($fechaCorte),
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
        $fecha = $this->fechaCorte($fechaCorte)->toDateString();

        $trayectorias = TrayectoriaAcademica::query()
            ->with(['inscripcion' => fn ($q) => $q->withTrashed()])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('grupo_id', array_values(array_unique(array_map('intval', $grupoIds))))
            ->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))
            ->when($gradoId, fn ($q) => $q->where('grado_id', $gradoId))
            ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
            ->when($semestreId, fn ($q) => $q->where('semestre_id', $semestreId))
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_inicio')
                    ->orWhereDate('fecha_inicio', '<=', $fecha);
            })
            ->where(function ($q) use ($fecha) {
                // Una baja/cambio con fecha igual al corte ya no pertenece a la lista.
                $q->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>', $fecha);
            })
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('numero_estancia')
            ->get()
            ->filter(fn (TrayectoriaAcademica $trayectoria) => $trayectoria->inscripcion !== null)
            ->unique('inscripcion_id')
            ->values();

        // Respaldo únicamente para el ciclo actual cuando todavía no se ha
        // reconstruido trayectoria. Nunca se usa para inventar ciclos antiguos.
        if ($trayectorias->isEmpty() && $this->esCicloActual($cicloEscolarId)) {
            return Inscripcion::query()
                ->withTrashed()
                ->whereIn('grupo_id', $grupoIds)
                ->when($nivelId, fn ($q) => $q->where('nivel_id', $nivelId))
                ->when($gradoId, fn ($q) => $q->where('grado_id', $gradoId))
                ->when($generacionId, fn ($q) => $q->where('generacion_id', $generacionId))
                ->when($semestreId, fn ($q) => $q->where(function ($sub) use ($semestreId) {
                    $sub->where('semestre_id', $semestreId)->orWhereNull('semestre_id');
                }))
                ->where(function ($q) {
                    $q->where('activo', true)->orWhere('activo', 1);
                })
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre')
                ->get();
        }

        return $trayectorias
            ->map(function (TrayectoriaAcademica $trayectoria) {
                $alumno = $trayectoria->inscripcion;
                $alumno->setAttribute('trayectoria_academica_id', $trayectoria->id);
                $alumno->setAttribute('estatus_historico', $trayectoria->estatus);
                $alumno->setAttribute('fecha_inicio_historica', optional($trayectoria->fecha_inicio)->toDateString());
                $alumno->setAttribute('fecha_fin_historica', optional($trayectoria->fecha_fin)->toDateString());
                $alumno->setRelation('trayectoriaLista', $trayectoria);

                return $alumno;
            })
            ->sortBy(function (Inscripcion $alumno) {
                return mb_strtolower(trim(
                    ($alumno->apellido_paterno ?? '') . '|' .
                    ($alumno->apellido_materno ?? '') . '|' .
                    ($alumno->nombre ?? '')
                ));
            })
            ->values();
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

    private function esCicloActual(int $cicloEscolarId): bool
    {
        return \App\Models\cicloEscolar::query()
            ->whereKey($cicloEscolarId)
            ->where('es_actual', true)
            ->exists();
    }
}
