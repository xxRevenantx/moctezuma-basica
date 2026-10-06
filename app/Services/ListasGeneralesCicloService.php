<?php

namespace App\Services;

use App\Enums\EstatusAlumnoCiclo;
use App\Enums\ResultadoInscripcionCiclo;
use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListasGeneralesCicloService
{
    /**
     * @param array<string, int|null> $filtros
     * @param array<int, int> $ids
     * @return Collection<int, Inscripcion>
     */
    public function alumnos(
        int $cicloEscolarId,
        array $filtros = [],
        string $estado = 'todos',
        array $ids = [],
    ): Collection {
        $query = InscripcionCiclo::query()
            ->with([
                'inscripcion',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'nivel:id,nombre,slug',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
                'grupo' => fn ($query) => $query->withTrashed()->select(
                    'id',
                    'nivel_id',
                    'grado_id',
                    'generacion_id',
                    'semestre_id',
                    'asignacion_grupo_id',
                    'ciclo_escolar_id',
                    'estado',
                    'archivado_at'
                ),
                'grupo.asignacionGrupo:id,nombre',
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO)
            ->when($filtros['nivel_id'] ?? null, fn (Builder $q, int $id) => $q->where('nivel_id', $id))
            ->when($filtros['generacion_id'] ?? null, fn (Builder $q, int $id) => $q->where('generacion_id', $id))
            ->when($filtros['grado_id'] ?? null, fn (Builder $q, int $id) => $q->where('grado_id', $id))
            ->when(array_key_exists('semestre_id', $filtros) && $filtros['semestre_id'] !== null,
                fn (Builder $q) => $q->where('semestre_id', $filtros['semestre_id']))
            ->when(($filtros['sin_semestre'] ?? null) === 1, fn (Builder $q) => $q->whereNull('semestre_id'))
            ->when($filtros['grupo_id'] ?? null, fn (Builder $q, int $id) => $q->where('grupo_id', $id))
            ->when($ids !== [], fn (Builder $q) => $q->whereIn('inscripcion_id', $ids));

        $this->aplicarEstado($query, $estado);

        return $query
            ->get()
            ->map(fn (InscripcionCiclo $historial): ?Inscripcion => $this->hidratarAlumno($historial))
            ->filter()
            ->unique('id')
            ->sortBy(fn (Inscripcion $alumno): string => sprintf(
                '%06d-%06d-%06d-%s',
                (int) $alumno->nivel_id,
                (int) $alumno->grado_id,
                (int) $alumno->grupo_id,
                mb_strtolower(trim(implode(' ', array_filter([
                    $alumno->apellido_paterno,
                    $alumno->apellido_materno,
                    $alumno->nombre,
                ]))))
            ))
            ->values();
    }

    public function estadoPredeterminado(CicloEscolar $ciclo): string
    {
        return $ciclo->es_actual && blank($ciclo->cerrado_at) ? 'activos' : 'todos';
    }

    /** @return array<string, string> */
    public function estadosDisponibles(): array
    {
        return [
            'todos' => 'Todos',
            'activos' => 'Activos',
            'bajas' => 'Bajas',
            'egresados' => 'Egresados',
            'promovidos' => 'Promovidos',
        ];
    }

    private function aplicarEstado(Builder $query, string $estado): void
    {
        match ($estado) {
            'activos' => $query->whereIn('estatus_actual_ciclo', [
                EstatusAlumnoCiclo::ACTIVO->value,
                EstatusAlumnoCiclo::REINGRESO->value,
                EstatusAlumnoCiclo::NO_PROMOVIDO->value,
            ]),
            'bajas' => $query->where(function (Builder $q): void {
                $q->whereIn('estatus_actual_ciclo', [
                    EstatusAlumnoCiclo::BAJA_TEMPORAL->value,
                    EstatusAlumnoCiclo::BAJA_DEFINITIVA->value,
                ])->orWhereIn('resultado_final', [
                    ResultadoInscripcionCiclo::BAJA_TEMPORAL_AL_CIERRE->value,
                    ResultadoInscripcionCiclo::BAJA_DEFINITIVA->value,
                ]);
            }),
            'egresados' => $query->where(function (Builder $q): void {
                $q->where('estatus_actual_ciclo', EstatusAlumnoCiclo::EGRESADO->value)
                    ->orWhere('resultado_final', ResultadoInscripcionCiclo::EGRESADO->value);
            }),
            'promovidos' => $query->where(function (Builder $q): void {
                $q->where('promovido', true)
                    ->orWhereIn('resultado_final', [
                        ResultadoInscripcionCiclo::PROMOVIDO->value,
                        ResultadoInscripcionCiclo::PROMOVIDO_GRADO->value,
                        ResultadoInscripcionCiclo::PROMOVIDO_NIVEL->value,
                    ]);
            }),
            default => null,
        };
    }

    private function hidratarAlumno(InscripcionCiclo $historial): ?Inscripcion
    {
        $original = $historial->inscripcion;
        if (!$original) {
            return null;
        }

        $alumno = clone $original;
        $alumno->setAttribute('matricula', $historial->matricula ?: $original->matricula);
        $alumno->setAttribute('ciclo_escolar_id', (int) $historial->ciclo_escolar_id);
        $alumno->setAttribute('inscripcion_ciclo_id', (int) $historial->id);
        $alumno->setAttribute('nivel_id', (int) $historial->nivel_id);
        $alumno->setAttribute('generacion_id', (int) $historial->generacion_id);
        $alumno->setAttribute('grado_id', (int) $historial->grado_id);
        $alumno->setAttribute('grupo_id', (int) $historial->grupo_id);
        $alumno->setAttribute('semestre_id', $historial->semestre_id ? (int) $historial->semestre_id : null);
        $alumno->setAttribute('estado_ciclo', (string) $historial->estado);
        $alumno->setAttribute('estatus_historico', (string) $historial->estatus_actual_ciclo);
        $alumno->setAttribute('resultado_final_ciclo', $historial->resultado_final);
        $alumno->setAttribute('promovido_ciclo', (bool) $historial->promovido);
        $alumno->setAttribute('etiqueta_estatus_ciclo', $historial->etiqueta_estatus);

        $alumno->setRelation('cicloEscolar', $historial->cicloEscolar);
        $alumno->setRelation('nivel', $historial->nivel);
        $alumno->setRelation('generacion', $historial->generacion);
        $alumno->setRelation('grado', $historial->grado);
        $alumno->setRelation('semestre', $historial->semestre);
        $alumno->setRelation('grupo', $historial->grupo);

        return $alumno;
    }
}
