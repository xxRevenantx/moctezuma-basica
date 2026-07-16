<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AcademicIntegrityService
{
    /** @return array<int,array<string,mixed>> */
    public function analyze(): array
    {
        $issues = [];

        $this->guarded($issues, fn () => $this->studentsMissingAcademicData());
        $this->guarded($issues, fn () => $this->duplicateStudents('curp', 'CURP duplicada'));
        $this->guarded($issues, fn () => $this->duplicateStudents('matricula', 'Matrícula duplicada'));
        $this->guarded($issues, fn () => $this->assignmentsWithoutTeacher());
        $this->guarded($issues, fn () => $this->duplicateAssignments());
        $this->guarded($issues, fn () => $this->groupScheduleConflicts());
        $this->guarded($issues, fn () => $this->teacherScheduleConflicts());
        $this->guarded($issues, fn () => $this->studentsWithoutGrades());
        $this->guarded($issues, fn () => $this->studentsWithoutDocuments());
        $this->guarded($issues, fn () => $this->currentCycleProblems());
        $this->guarded($issues, fn () => $this->orphanedAcademicReferences());

        return collect($issues)
            ->filter(fn (?array $issue): bool => is_array($issue) && ($issue['count'] ?? 0) > 0)
            ->sortBy(fn (array $issue): int => match ($issue['severity']) {
                'critical' => 0,
                'warning' => 1,
                default => 2,
            })
            ->values()
            ->all();
    }

    /** @param array<int,array<string,mixed>> $issues */
    private function guarded(array &$issues, callable $callback): void
    {
        try {
            $result = $callback();
            if ($result) {
                $issues[] = $result;
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function studentsMissingAcademicData(): ?array
    {
        if (! Schema::hasTable('inscripciones')) {
            return null;
        }

        $columns = array_values(array_filter(
            ['nivel_id', 'grado_id', 'grupo_id', 'generacion_id'],
            fn (string $column): bool => Schema::hasColumn('inscripciones', $column)
        ));

        if ($columns === []) {
            return null;
        }

        $query = $this->activeStudents();
        $query->where(function (Builder $builder) use ($columns): void {
            foreach ($columns as $index => $column) {
                $index === 0 ? $builder->whereNull($column) : $builder->orWhereNull($column);
            }
        });

        $count = $query->count();

        return $this->issue(
            'alumnos_sin_asignacion',
            'Alumnos activos con asignación académica incompleta',
            'Hay alumnos sin nivel, grado, grupo o generación. Revisa su matrícula antes de emitir documentos.',
            'critical',
            $count,
            $this->route('misrutas.alumnos')
        );
    }

    private function duplicateStudents(string $column, string $title): ?array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasColumn('inscripciones', $column)) {
            return null;
        }

        $query = $this->activeStudents()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->having('total', '>', 1);

        $duplicates = $query->get();

        return $this->issue(
            'duplicado_'.$column,
            $title,
            'Se encontraron identificadores repetidos entre alumnos. La corrección debe revisarse manualmente.',
            'critical',
            $duplicates->count(),
            $this->route('misrutas.alumnos'),
            $duplicates->take(5)->pluck($column)->filter()->values()->all()
        );
    }

    private function assignmentsWithoutTeacher(): ?array
    {
        if (! Schema::hasTable('asignacion_materias') || ! Schema::hasColumn('asignacion_materias', 'profesor_id')) {
            return null;
        }

        $query = DB::table('asignacion_materias')->whereNull('profesor_id');
        if (Schema::hasColumn('asignacion_materias', 'estado')) {
            $query->where('estado', '!=', 'archivada');
        }

        return $this->issue(
            'materias_sin_profesor',
            'Materias asignadas sin profesor',
            'Estas cargas académicas no pueden generar un horario completo ni listas correctas.',
            'warning',
            $query->count(),
            $this->route('misrutas.profesores')
        );
    }

    private function duplicateAssignments(): ?array
    {
        if (! Schema::hasTable('asignacion_materias')) {
            return null;
        }

        $groupColumns = array_values(array_filter(
            ['grupo_id', 'materia_id', 'ciclo_escolar_id'],
            fn (string $column): bool => Schema::hasColumn('asignacion_materias', $column)
        ));

        if (count($groupColumns) < 2) {
            return null;
        }

        $duplicates = DB::table('asignacion_materias')
            ->select([...$groupColumns, DB::raw('COUNT(*) as total')])
            ->groupBy($groupColumns)
            ->having('total', '>', 1)
            ->get();

        return $this->issue(
            'cargas_duplicadas',
            'Cargas académicas duplicadas',
            'Una misma materia aparece más de una vez para el mismo grupo y ciclo.',
            'warning',
            $duplicates->count(),
            $this->route('misrutas.materias')
        );
    }

    private function groupScheduleConflicts(): ?array
    {
        if (! Schema::hasTable('horarios')) {
            return null;
        }

        $columns = array_values(array_filter(
            ['grupo_id', 'dia_id', 'hora_id', 'ciclo_escolar_id'],
            fn (string $column): bool => Schema::hasColumn('horarios', $column)
        ));

        if (count($columns) < 3) {
            return null;
        }

        $conflicts = DB::table('horarios')
            ->select([...$columns, DB::raw('COUNT(*) as total')])
            ->whereNotNull('grupo_id')
            ->groupBy($columns)
            ->having('total', '>', 1)
            ->get();

        return $this->issue(
            'choques_grupo',
            'Choques de horario por grupo',
            'Un grupo tiene más de una actividad registrada en el mismo día y hora.',
            'critical',
            $conflicts->count(),
            null
        );
    }

    private function teacherScheduleConflicts(): ?array
    {
        if (! Schema::hasTable('horarios') || ! Schema::hasTable('asignacion_materias')) {
            return null;
        }

        foreach (['asignacion_materia_id', 'dia_id', 'hora_id'] as $column) {
            if (! Schema::hasColumn('horarios', $column)) {
                return null;
            }
        }

        $groupColumns = ['am.profesor_id', 'h.dia_id', 'h.hora_id'];
        if (Schema::hasColumn('horarios', 'ciclo_escolar_id')) {
            $groupColumns[] = 'h.ciclo_escolar_id';
        }

        $conflicts = DB::table('horarios as h')
            ->join('asignacion_materias as am', 'am.id', '=', 'h.asignacion_materia_id')
            ->whereNotNull('am.profesor_id')
            ->select([...$groupColumns, DB::raw('COUNT(*) as total')])
            ->groupBy($groupColumns)
            ->having('total', '>', 1)
            ->get();

        return $this->issue(
            'choques_profesor',
            'Choques de horario de profesores',
            'Un profesor aparece en dos o más grupos durante el mismo bloque horario.',
            'critical',
            $conflicts->count(),
            $this->route('misrutas.profesores')
        );
    }

    private function studentsWithoutGrades(): ?array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('calificaciones')) {
            return null;
        }

        $query = $this->activeStudents()
            ->leftJoin('calificaciones', 'calificaciones.inscripcion_id', '=', 'inscripciones.id')
            ->whereNull('calificaciones.id');

        return $this->issue(
            'alumnos_sin_calificaciones',
            'Alumnos activos sin calificaciones registradas',
            'Puede ser normal al inicio del periodo; revisa antes de cerrar o generar boletas.',
            'info',
            $query->distinct('inscripciones.id')->count('inscripciones.id'),
            $this->route('misrutas.alumnos')
        );
    }

    private function studentsWithoutDocuments(): ?array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('documentos_alumnos')) {
            return null;
        }

        $query = $this->activeStudents()
            ->leftJoin('documentos_alumnos', function ($join): void {
                $join->on('documentos_alumnos.inscripcion_id', '=', 'inscripciones.id');
                if (Schema::hasColumn('documentos_alumnos', 'es_actual')) {
                    $join->where('documentos_alumnos.es_actual', true);
                }
                if (Schema::hasColumn('documentos_alumnos', 'deleted_at')) {
                    $join->whereNull('documentos_alumnos.deleted_at');
                }
            })
            ->whereNull('documentos_alumnos.id');

        return $this->issue(
            'alumnos_sin_expediente',
            'Alumnos sin documentos vigentes en expediente',
            'El expediente digital no contiene documentos actuales para estos alumnos.',
            'warning',
            $query->distinct('inscripciones.id')->count('inscripciones.id'),
            $this->route('misrutas.expedientes')
        );
    }

    private function currentCycleProblems(): ?array
    {
        if (! Schema::hasTable('ciclo_escolares') || ! Schema::hasColumn('ciclo_escolares', 'es_actual')) {
            return null;
        }

        $current = DB::table('ciclo_escolares')->where('es_actual', true)->count();

        if ($current === 1) {
            return null;
        }

        return $this->issue(
            'ciclo_actual_invalido',
            $current === 0 ? 'No hay ciclo escolar actual' : 'Hay varios ciclos escolares actuales',
            'El sistema debe tener exactamente un ciclo marcado como actual.',
            'critical',
            abs($current - 1) ?: 1,
            $this->route('misrutas.ciclos')
        );
    }

    private function orphanedAcademicReferences(): ?array
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('niveles') || ! Schema::hasColumn('inscripciones', 'nivel_id')) {
            return null;
        }

        $query = DB::table('inscripciones')
            ->leftJoin('niveles', 'niveles.id', '=', 'inscripciones.nivel_id')
            ->whereNotNull('inscripciones.nivel_id')
            ->whereNull('niveles.id');

        if (Schema::hasColumn('inscripciones', 'deleted_at')) {
            $query->whereNull('inscripciones.deleted_at');
        }

        return $this->issue(
            'referencias_huerfanas',
            'Referencias académicas inexistentes',
            'Existen alumnos apuntando a un nivel que ya no existe.',
            'critical',
            $query->count(),
            $this->route('misrutas.alumnos')
        );
    }

    private function activeStudents(): Builder
    {
        $query = DB::table('inscripciones');

        if (Schema::hasColumn('inscripciones', 'activo')) {
            $query->where('inscripciones.activo', true);
        }
        if (Schema::hasColumn('inscripciones', 'deleted_at')) {
            $query->whereNull('inscripciones.deleted_at');
        }

        return $query;
    }

    private function route(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }

    private function issue(
        string $key,
        string $title,
        string $description,
        string $severity,
        int $count,
        ?string $url,
        array $samples = [],
    ): array {
        return compact('key', 'title', 'description', 'severity', 'count', 'url', 'samples');
    }
}
