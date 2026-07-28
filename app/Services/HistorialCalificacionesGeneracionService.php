<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\InscripcionCicloAsignacion;
use App\Models\Periodos;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HistorialCalificacionesGeneracionService
{
    /**
     * Confirma de forma diferida el contexto académico usado para capturar una
     * calificación de Bachillerato. No cambia la ubicación actual del alumno:
     * solo crea la evidencia por ciclo y la asignación histórica que faltaba.
     */
    public function asegurarContexto(
        int $inscripcionId,
        int $cicloEscolarId,
        int $nivelId,
        int $gradoId,
        int $generacionId,
        int $grupoId,
        ?int $semestreId,
        ?int $periodoId,
        ?int $usuarioId,
        ?string $motivo = null,
    ): InscripcionCiclo {
        return DB::transaction(function () use (
            $inscripcionId,
            $cicloEscolarId,
            $nivelId,
            $gradoId,
            $generacionId,
            $grupoId,
            $semestreId,
            $periodoId,
            $usuarioId,
            $motivo,
        ): InscripcionCiclo {
            $alumno = Inscripcion::withTrashed()->findOrFail($inscripcionId);

            $this->validarVinculoGeneracion(
                alumno: $alumno,
                nivelId: $nivelId,
                generacionId: $generacionId,
            );

            $this->validarGrupoContexto(
                grupoId: $grupoId,
                cicloEscolarId: $cicloEscolarId,
                nivelId: $nivelId,
                gradoId: $gradoId,
                generacionId: $generacionId,
                semestreId: $semestreId,
            );

            $this->validarGrupoNoDuplicado(
                inscripcionId: $inscripcionId,
                cicloEscolarId: $cicloEscolarId,
                nivelId: $nivelId,
                gradoId: $gradoId,
                generacionId: $generacionId,
                semestreId: $semestreId,
                grupoId: $grupoId,
            );

            [$inicioContexto, $finContexto] = $this->rangoContexto(
                cicloEscolarId: $cicloEscolarId,
                nivelId: $nivelId,
                generacionId: $generacionId,
                semestreId: $semestreId,
                periodoId: $periodoId,
            );

            $ciclo = CicloEscolar::query()->findOrFail($cicloEscolarId);
            $estatus = $this->normalizarEstatus((string) ($alumno->estatus ?: ($alumno->activo ? 'activo' : 'inactivo')));
            $motivoNormalizado = trim((string) $motivo);

            $registro = InscripcionCiclo::query()->firstOrCreate(
                [
                    'inscripcion_id' => $alumno->id,
                    'ciclo_escolar_id' => $cicloEscolarId,
                ],
                [
                    'matricula' => $alumno->matricula,
                    'nivel_id' => $nivelId,
                    'grado_id' => $gradoId,
                    'generacion_id' => $generacionId,
                    'grupo_id' => $grupoId,
                    'semestre_id' => $semestreId,
                    'fecha_ingreso' => $inicioContexto->toDateString(),
                    'fecha_salida' => $ciclo->es_actual && blank($ciclo->cerrado_at)
                        ? null
                        : $finContexto->toDateString(),
                    'estado' => $ciclo->es_actual && blank($ciclo->cerrado_at)
                        ? 'en_curso'
                        : 'finalizado',
                    'estatus_ingreso' => $estatus,
                    'estatus_actual_ciclo' => $estatus,
                    'resultado_final' => null,
                    'promovido' => false,
                    'snapshot_ingreso' => $this->snapshotReconstruccion(
                        alumno: $alumno,
                        cicloEscolarId: $cicloEscolarId,
                        nivelId: $nivelId,
                        gradoId: $gradoId,
                        generacionId: $generacionId,
                        grupoId: $grupoId,
                        semestreId: $semestreId,
                        periodoId: $periodoId,
                        usuarioId: $usuarioId,
                        motivo: $motivoNormalizado,
                    ),
                    'origen' => 'inclusion_generacion',
                    'reconstruido' => true,
                    'nivel_confianza' => 'inferido',
                ]
            );

            $asignacion = InscripcionCicloAsignacion::query()
                ->where('inscripcion_ciclo_id', $registro->id)
                ->where('nivel_id', $nivelId)
                ->where('grado_id', $gradoId)
                ->where('generacion_id', $generacionId)
                ->where('grupo_id', $grupoId)
                ->when(
                    $semestreId,
                    fn (Builder $query) => $query->where('semestre_id', $semestreId),
                    fn (Builder $query) => $query->whereNull('semestre_id')
                )
                ->first();

            if (! $asignacion) {
                $asignacion = InscripcionCicloAsignacion::query()->create([
                    'inscripcion_ciclo_id' => $registro->id,
                    'nivel_id' => $nivelId,
                    'grado_id' => $gradoId,
                    'generacion_id' => $generacionId,
                    'grupo_id' => $grupoId,
                    'semestre_id' => $semestreId,
                    'fecha_inicio' => $inicioContexto->toDateString(),
                    'fecha_fin' => $finContexto->toDateString(),
                    'tipo' => 'inclusion_generacion_calificaciones',
                    'motivo' => $motivoNormalizado !== ''
                        ? $motivoNormalizado
                        : 'Contexto confirmado al capturar la primera calificación del alumno dentro de su generación.',
                    'es_actual' => false,
                    'registrado_por' => $usuarioId,
                    'snapshot' => $this->snapshotReconstruccion(
                        alumno: $alumno,
                        cicloEscolarId: $cicloEscolarId,
                        nivelId: $nivelId,
                        gradoId: $gradoId,
                        generacionId: $generacionId,
                        grupoId: $grupoId,
                        semestreId: $semestreId,
                        periodoId: $periodoId,
                        usuarioId: $usuarioId,
                        motivo: $motivoNormalizado,
                    ),
                ]);
            }

            Calificacion::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->where('grado_id', $gradoId)
                ->where('generacion_id', $generacionId)
                ->where('grupo_id', $grupoId)
                ->when(
                    $semestreId,
                    fn (Builder $query) => $query->where('semestre_id', $semestreId),
                    fn (Builder $query) => $query->whereNull('semestre_id')
                )
                ->whereNull('inscripcion_ciclo_id')
                ->update(['inscripcion_ciclo_id' => $registro->id]);

            return $registro->refresh();
        });
    }

    private function validarVinculoGeneracion(Inscripcion $alumno, int $nivelId, int $generacionId): void
    {
        $vinculado = ((int) $alumno->nivel_id === $nivelId && (int) $alumno->generacion_id === $generacionId)
            || InscripcionCiclo::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('nivel_id', $nivelId)
                ->where('generacion_id', $generacionId)
                ->exists()
            || InscripcionCicloAsignacion::query()
                ->where('nivel_id', $nivelId)
                ->where('generacion_id', $generacionId)
                ->whereHas('inscripcionCiclo', fn (Builder $query) => $query->where('inscripcion_id', $alumno->id))
                ->exists()
            || Calificacion::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('nivel_id', $nivelId)
                ->where('generacion_id', $generacionId)
                ->exists();

        if (! $vinculado) {
            throw ValidationException::withMessages([
                'calificaciones' => [
                    'El alumno no está vinculado a la generación seleccionada. No se creó ningún historial ni se modificó su inscripción actual.',
                ],
            ]);
        }

        $generacionValida = Generacion::query()
            ->whereKey($generacionId)
            ->where('nivel_id', $nivelId)
            ->exists();

        if (! $generacionValida) {
            throw ValidationException::withMessages([
                'calificaciones' => ['La generación seleccionada no pertenece al nivel de Bachillerato.'],
            ]);
        }
    }

    private function validarGrupoContexto(
        int $grupoId,
        int $cicloEscolarId,
        int $nivelId,
        int $gradoId,
        int $generacionId,
        ?int $semestreId,
    ): void {
        $grupoValido = Grupo::withTrashed()
            ->whereKey($grupoId)
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->where('generacion_id', $generacionId)
            ->when(
                $semestreId,
                fn (Builder $query) => $query->where('semestre_id', $semestreId),
                fn (Builder $query) => $query->whereNull('semestre_id')
            )
            ->where(function (Builder $query) use ($cicloEscolarId, $grupoId): void {
                $query
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->orWhereExists(function ($subquery) use ($cicloEscolarId, $grupoId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclos')
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloEscolarId)
                            ->where('inscripcion_ciclos.grupo_id', $grupoId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloEscolarId, $grupoId): void {
                        $subquery->selectRaw('1')
                            ->from('inscripcion_ciclo_asignaciones')
                            ->join(
                                'inscripcion_ciclos',
                                'inscripcion_ciclos.id',
                                '=',
                                'inscripcion_ciclo_asignaciones.inscripcion_ciclo_id'
                            )
                            ->where('inscripcion_ciclos.ciclo_escolar_id', $cicloEscolarId)
                            ->where('inscripcion_ciclo_asignaciones.grupo_id', $grupoId);
                    })
                    ->orWhereExists(function ($subquery) use ($cicloEscolarId, $grupoId): void {
                        $subquery->selectRaw('1')
                            ->from('calificaciones')
                            ->where('calificaciones.ciclo_escolar_id', $cicloEscolarId)
                            ->where('calificaciones.grupo_id', $grupoId);
                    });
            })
            ->exists();

        if (! $grupoValido) {
            throw ValidationException::withMessages([
                'calificaciones' => ['El grupo no corresponde al ciclo, generación, grado y semestre seleccionados.'],
            ]);
        }
    }

    private function validarGrupoNoDuplicado(
        int $inscripcionId,
        int $cicloEscolarId,
        int $nivelId,
        int $gradoId,
        int $generacionId,
        ?int $semestreId,
        int $grupoId,
    ): void {
        $grupos = collect();

        $grupos = $grupos->merge(
            InscripcionCicloAsignacion::query()
                ->where('nivel_id', $nivelId)
                ->where('grado_id', $gradoId)
                ->where('generacion_id', $generacionId)
                ->when(
                    $semestreId,
                    fn (Builder $query) => $query->where('semestre_id', $semestreId),
                    fn (Builder $query) => $query->whereNull('semestre_id')
                )
                ->whereHas('inscripcionCiclo', function (Builder $query) use ($inscripcionId, $cicloEscolarId): void {
                    $query->where('inscripcion_id', $inscripcionId)
                        ->where('ciclo_escolar_id', $cicloEscolarId);
                })
                ->pluck('grupo_id')
        );

        $grupos = $grupos->merge(
            InscripcionCiclo::query()
                ->where('inscripcion_id', $inscripcionId)
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->where('grado_id', $gradoId)
                ->where('generacion_id', $generacionId)
                ->when(
                    $semestreId,
                    fn (Builder $query) => $query->where('semestre_id', $semestreId),
                    fn (Builder $query) => $query->whereNull('semestre_id')
                )
                ->pluck('grupo_id')
        );

        $grupos = $grupos->merge(
            Calificacion::query()
                ->where('inscripcion_id', $inscripcionId)
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $nivelId)
                ->where('grado_id', $gradoId)
                ->where('generacion_id', $generacionId)
                ->when(
                    $semestreId,
                    fn (Builder $query) => $query->where('semestre_id', $semestreId),
                    fn (Builder $query) => $query->whereNull('semestre_id')
                )
                ->pluck('grupo_id')
        );

        $grupos = $grupos
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($grupos->isNotEmpty() && ! $grupos->contains($grupoId)) {
            throw ValidationException::withMessages([
                'calificaciones' => [
                    'El alumno ya tiene evidencia académica en otro grupo para este ciclo, grado y semestre. Revisa o corrige primero su asignación histórica para evitar duplicarlo.',
                ],
            ]);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoContexto(
        int $cicloEscolarId,
        int $nivelId,
        int $generacionId,
        ?int $semestreId,
        ?int $periodoId,
    ): array {
        $periodos = Periodos::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('generacion_id', $generacionId)
            ->when(
                $semestreId,
                fn (Builder $query) => $query->where('semestre_id', $semestreId),
                fn (Builder $query) => $query->whereNull('semestre_id')
            )
            ->get(['id', 'fecha_inicio', 'fecha_fin']);

        $periodoSeleccionado = $periodoId
            ? $periodos->firstWhere('id', $periodoId)
            : null;

        $inicio = $periodos
            ->pluck('fecha_inicio')
            ->filter()
            ->map(fn ($fecha) => Carbon::parse($fecha)->startOfDay())
            ->sortBy(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->first();

        $fin = $periodos
            ->pluck('fecha_fin')
            ->filter()
            ->map(fn ($fecha) => Carbon::parse($fecha)->endOfDay())
            ->sortByDesc(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->first();

        $ciclo = CicloEscolar::query()->findOrFail($cicloEscolarId);

        $inicio ??= $periodoSeleccionado?->fecha_inicio
            ? Carbon::parse($periodoSeleccionado->fecha_inicio)->startOfDay()
            : Carbon::create((int) $ciclo->inicio_anio, 8, 1)->startOfDay();

        $fin ??= $periodoSeleccionado?->fecha_fin
            ? Carbon::parse($periodoSeleccionado->fecha_fin)->endOfDay()
            : Carbon::create((int) $ciclo->fin_anio, 7, 31)->endOfDay();

        if ($inicio->gt($fin)) {
            return [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [$inicio, $fin];
    }

    private function snapshotReconstruccion(
        Inscripcion $alumno,
        int $cicloEscolarId,
        int $nivelId,
        int $gradoId,
        int $generacionId,
        int $grupoId,
        ?int $semestreId,
        ?int $periodoId,
        ?int $usuarioId,
        string $motivo,
    ): array {
        return [
            'origen' => 'inclusion_por_generacion_para_calificaciones',
            'inferido' => true,
            'confirmado_at' => now()->toDateTimeString(),
            'confirmado_por' => $usuarioId,
            'motivo' => $motivo !== '' ? $motivo : null,
            'contexto' => [
                'ciclo_escolar_id' => $cicloEscolarId,
                'nivel_id' => $nivelId,
                'grado_id' => $gradoId,
                'generacion_id' => $generacionId,
                'grupo_id' => $grupoId,
                'semestre_id' => $semestreId,
                'periodo_id' => $periodoId,
            ],
            'inscripcion_real' => [
                'ciclo_escolar_id' => $alumno->getRawOriginal('ciclo_escolar_id'),
                'nivel_id' => $alumno->getRawOriginal('nivel_id'),
                'grado_id' => $alumno->getRawOriginal('grado_id'),
                'generacion_id' => $alumno->getRawOriginal('generacion_id'),
                'grupo_id' => $alumno->getRawOriginal('grupo_id'),
                'semestre_id' => $alumno->getRawOriginal('semestre_id'),
                'fecha_inscripcion' => optional($alumno->fecha_inscripcion)->toDateString(),
                'estatus' => $alumno->estatus,
            ],
        ];
    }

    private function normalizarEstatus(string $estatus): string
    {
        $estatus = mb_strtolower(trim($estatus));

        return $estatus !== '' ? $estatus : 'activo';
    }
}
