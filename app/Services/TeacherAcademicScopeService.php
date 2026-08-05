<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\User;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeacherAcademicScopeService
{
    public function personaIdOrFail(?User $user = null): int
    {
        $user ??= auth()->user();

        abort_unless($user?->isProfessor(), 403, 'Esta operación está reservada para cuentas docentes.');
        $user->loadMissing('persona');
        $inactiveEmploymentStates = ['baja', 'inactivo', 'suspendido'];

        abort_unless(
            $user->persona_id
                && $user->persona?->status
                && ! in_array(
                    mb_strtolower(trim((string) $user->persona?->estado_laboral)),
                    $inactiveEmploymentStates,
                    true
                ),
            403,
            'La cuenta docente no está vinculada a un profesor activo.'
        );

        return (int) $user->persona_id;
    }

    public function assignmentsQuery(
        User $user,
        ?int $cicloEscolarId = null,
        ?int $nivelId = null,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $semestreId = null,
    ): Builder {
        $personaId = $this->personaIdOrFail($user);

        return AsignacionMateria::query()
            ->where('profesor_id', $personaId)
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->when($cicloEscolarId, fn (Builder $query) => $query->where('ciclo_escolar_id', $cicloEscolarId))
            ->when($nivelId, fn (Builder $query) => $query->where('nivel_id', $nivelId))
            ->when($generacionId, fn (Builder $query) => $query->where('generacion_id', $generacionId))
            ->when($gradoId, fn (Builder $query) => $query->where('grado_id', $gradoId))
            ->when($grupoId, fn (Builder $query) => $query->where('grupo_id', $grupoId))
            ->when(
                $semestreId,
                fn (Builder $query) => $query->where('semestre_id', $semestreId),
                fn (Builder $query) => $query->whereNull('semestre_id')
            );
    }

    public function assignedLevels(User $user): Collection
    {
        $personaId = $this->personaIdOrFail($user);

        $porMaterias = AsignacionMateria::query()
            ->join('niveles', 'niveles.id', '=', 'asignacion_materias.nivel_id')
            ->where('asignacion_materias.profesor_id', $personaId)
            ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->select('niveles.id', 'niveles.nombre', 'niveles.slug')
            ->distinct()
            ->get();

        $porTitularidad = Nivel::query()
            ->whereExists(function ($query) use ($personaId): void {
                $query->selectRaw('1')
                    ->from('docente_grupos')
                    ->join('grupos', 'grupos.id', '=', 'docente_grupos.grupo_id')
                    ->whereColumn('grupos.nivel_id', 'niveles.id')
                    ->where('docente_grupos.persona_id', $personaId)
                    ->where('docente_grupos.status', true);
            })
            ->select('niveles.id', 'niveles.nombre', 'niveles.slug')
            ->get();

        $hasCurrentPreschoolContext = $this->hasCurrentPreschoolContext($user);

        return $porMaterias
            ->concat($porTitularidad)
            ->unique('id')
            ->reject(fn ($level): bool => $level->slug === 'preescolar' && ! $hasCurrentPreschoolContext)
            ->sortBy('id')
            ->values();
    }

    /**
     * Grupos de preescolar donde el usuario es docente titular del grupo.
     *
     * Una asignación de materia extra (inglés, educación física, música, etc.)
     * no concede acceso a fichas descriptivas. Esta separación evita que un
     * profesor de apoyo consulte o modifique expedientes pedagógicos completos.
     */
    public function preschoolHomeroomGroupIds(User $user, ?int $cicloEscolarId = null): Collection
    {
        $personaId = $this->personaIdOrFail($user);
        $cicloEscolarId ??= CicloEscolar::query()
            ->where('es_actual', true)
            ->whereNull('cerrado_at')
            ->orderByDesc('id')
            ->value('id');
        $preschoolId = Nivel::query()->where('slug', 'preescolar')->value('id');

        if (! $cicloEscolarId || ! $preschoolId) {
            return collect();
        }

        return \App\Models\Grupo::query()
            ->where('nivel_id', (int) $preschoolId)
            ->where('ciclo_escolar_id', (int) $cicloEscolarId)
            ->whereHas('docentes', function (Builder $teachers) use ($personaId): void {
                $teachers->where('personas.id', $personaId)
                    ->where('docente_grupos.status', true);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    public function hasCurrentPreschoolContext(User $user): bool
    {
        return $this->preschoolHomeroomGroupIds($user)->isNotEmpty();
    }

    public function assertGradeContext(
        User $user,
        int $cicloEscolarId,
        int $nivelId,
        int $generacionId,
        int $gradoId,
        int $grupoId,
        ?int $semestreId,
        int $periodoId,
    ): void {
        $hasAssignments = $this->capturableAssignmentsQuery(
            user: $user,
            cicloEscolarId: $cicloEscolarId,
            nivelId: $nivelId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
            semestreId: $semestreId,
        )->exists();

        if (! $hasAssignments) {
            throw ValidationException::withMessages([
                'calificaciones' => 'El grupo seleccionado no contiene materias asignadas al profesor autenticado.',
            ]);
        }

        $periodQuery = Periodos::query()
            ->whereKey($periodoId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId);

        if ($semestreId) {
            $periodQuery
                ->where('generacion_id', $generacionId)
                ->where('semestre_id', $semestreId);
        } else {
            // En educación básica el periodo es global por nivel y ciclo.
            $periodQuery->whereNull('semestre_id');
        }

        if (! $periodQuery->exists()) {
            throw ValidationException::withMessages([
                'calificaciones' => 'El periodo no corresponde al contexto académico seleccionado.',
            ]);
        }
    }

    /** @return array{assignment_ids:array<int,int>,student_ids:array<int,int>} */
    public function validateGradePayload(
        User $user,
        int $cicloEscolarId,
        int $nivelId,
        int $generacionId,
        int $gradoId,
        int $grupoId,
        ?int $semestreId,
        int $periodoId,
        array $payloadAssignmentIds,
        array $payloadStudentIds,
        bool $lock = false,
    ): array {
        $assignmentIds = collect($payloadAssignmentIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $studentIds = collect($payloadStudentIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($assignmentIds->isEmpty() || $studentIds->isEmpty()) {
            throw ValidationException::withMessages([
                'calificaciones' => 'No hay materias o alumnos autorizados para guardar.',
            ]);
        }

        $this->assertGradeContext(
            user: $user,
            cicloEscolarId: $cicloEscolarId,
            nivelId: $nivelId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
            semestreId: $semestreId,
            periodoId: $periodoId,
        );

        $assignments = $this->capturableAssignmentsQuery(
            user: $user,
            cicloEscolarId: $cicloEscolarId,
            nivelId: $nivelId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
            semestreId: $semestreId,
        );

        if ($lock) {
            $assignments->lockForUpdate();
        }

        $authorizedAssignmentIds = $assignments
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($authorizedAssignmentIds->all() !== $assignmentIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'calificaciones' => 'La lista de materias fue alterada o no coincide con todas las asignaciones autorizadas del profesor.',
            ]);
        }

        $period = Periodos::query()->findOrFail($periodoId);
        $cycle = CicloEscolar::query()->findOrFail($cicloEscolarId);
        $startDate = $period->fecha_inicio ?: now()->toDateString();
        $endDate = $period->fecha_fin ?: $startDate;

        /*
         * Se usa exactamente la misma fuente de alumnos que la pantalla de
         * captura. Así se conservan los alumnos autorizados por historial de
         * ciclo y, en bachillerato, los integrantes válidos de la generación,
         * sin confiar en IDs enviados por el navegador.
         */
        $authorizedStudentIds = app(ListaAcademicaService::class)->alumnosPorContexto(
            cicloEscolarId: $cicloEscolarId,
            grupoIds: [$grupoId],
            fechaCorte: $startDate,
            nivelId: $nivelId,
            gradoId: $gradoId,
            generacionId: $generacionId,
            semestreId: $semestreId,
            usarHistorialCiclo: true,
            incluirNoActivos: false,
            fechaInicio: $startDate,
            fechaFin: $endDate,
            periodoId: $periodoId,
            usarActualComoRespaldo: (bool) $cycle->es_actual && blank($cycle->cerrado_at),
            incluirTodaGeneracionBachillerato: $semestreId !== null,
        )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($lock && $authorizedStudentIds->isNotEmpty()) {
            Inscripcion::query()
                ->whereIn('id', $authorizedStudentIds->all())
                ->lockForUpdate()
                ->get(['id']);
        }

        if ($authorizedStudentIds->all() !== $studentIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'calificaciones' => 'La lista de alumnos fue alterada o no coincide con el grupo activo autorizado.',
            ]);
        }

        return [
            'assignment_ids' => $authorizedAssignmentIds->all(),
            'student_ids' => $authorizedStudentIds->all(),
        ];
    }

    private function capturableAssignmentsQuery(
        User $user,
        int $cicloEscolarId,
        int $nivelId,
        int $generacionId,
        int $gradoId,
        int $grupoId,
        ?int $semestreId,
    ): Builder {
        return $this->assignmentsQuery(
            user: $user,
            cicloEscolarId: $cicloEscolarId,
            nivelId: $nivelId,
            generacionId: $generacionId,
            gradoId: $gradoId,
            grupoId: $grupoId,
            semestreId: $semestreId,
        )->whereHas('materia', function (Builder $query) use ($nivelId, $gradoId, $semestreId): void {
            $query->where('nivel_id', $nivelId)
                ->where('grado_id', $gradoId);

            if ($semestreId) {
                $query->where('semestre_id', $semestreId);
                ReglasMateriaBachillerato::aplicarCapturables($query, '');
                return;
            }

            $query->whereNull('semestre_id')
                ->where('calificable', true);
        });
    }
}
