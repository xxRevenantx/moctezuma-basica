<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendPeriodos();
        $this->extendFichas();
        $this->extendHorarios();
        $this->extendAsignaciones();
        $this->extendGrupos();
        $this->createCalificacionCorrecciones();

        $this->backfillPeriodDates();
        $this->markExistingSharedSessions();
        $this->markAssignmentCycleMismatches();
        $this->archiveLegacyGroup28();
        $this->clearInvalidGenerationEndCycles();
        $this->backfillActiveEnrollmentMatriculas();
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion_correcciones');

        Schema::table('grupos', function (Blueprint $table): void {
            foreach (['motivo_archivo', 'archivado_at', 'archivado_por'] as $column) {
                if (Schema::hasColumn('grupos', $column)) {
                    if ($column === 'archivado_por') {
                        try { $table->dropForeign(['archivado_por']); } catch (Throwable) {}
                    }
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('asignacion_materias', function (Blueprint $table): void {
            if (Schema::hasColumn('asignacion_materias', 'revision_ciclo_por')) {
                try { $table->dropForeign(['revision_ciclo_por']); } catch (Throwable) {}
            }
            foreach (['revision_ciclo_estado', 'revision_ciclo_observacion', 'revision_ciclo_at', 'revision_ciclo_por'] as $column) {
                if (Schema::hasColumn('asignacion_materias', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('horarios', function (Blueprint $table): void {
            foreach (['sesion_compartida', 'clave_sesion_compartida', 'motivo_sesion_compartida'] as $column) {
                if (Schema::hasColumn('horarios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('ficha_descriptivas', function (Blueprint $table): void {
            if (Schema::hasColumn('ficha_descriptivas', 'periodo_id')) {
                try { $table->dropForeign(['periodo_id']); } catch (Throwable) {}
                $table->dropColumn('periodo_id');
            }
        });

        Schema::table('periodos', function (Blueprint $table): void {
            foreach ([
                'fecha_evaluacion_inicio', 'fecha_evaluacion_fin',
                'fecha_captura_inicio', 'fecha_captura_fin',
                'traslape_confirmado', 'motivo_traslape',
            ] as $column) {
                if (Schema::hasColumn('periodos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function extendPeriodos(): void
    {
        Schema::table('periodos', function (Blueprint $table): void {
            if (!Schema::hasColumn('periodos', 'fecha_evaluacion_inicio')) {
                $table->date('fecha_evaluacion_inicio')->nullable()->after('fecha_fin');
            }
            if (!Schema::hasColumn('periodos', 'fecha_evaluacion_fin')) {
                $table->date('fecha_evaluacion_fin')->nullable()->after('fecha_evaluacion_inicio');
            }
            if (!Schema::hasColumn('periodos', 'fecha_captura_inicio')) {
                $table->date('fecha_captura_inicio')->nullable()->after('fecha_evaluacion_fin');
            }
            if (!Schema::hasColumn('periodos', 'fecha_captura_fin')) {
                $table->date('fecha_captura_fin')->nullable()->after('fecha_captura_inicio');
            }
            if (!Schema::hasColumn('periodos', 'traslape_confirmado')) {
                $table->boolean('traslape_confirmado')->default(false)->after('fecha_captura_fin');
            }
            if (!Schema::hasColumn('periodos', 'motivo_traslape')) {
                $table->text('motivo_traslape')->nullable()->after('traslape_confirmado');
            }
        });
    }

    private function extendFichas(): void
    {
        Schema::table('ficha_descriptivas', function (Blueprint $table): void {
            if (!Schema::hasColumn('ficha_descriptivas', 'periodo_id')) {
                $table->foreignId('periodo_id')
                    ->nullable()
                    ->after('ciclo_escolar_id')
                    ->constrained('periodos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    private function extendHorarios(): void
    {
        Schema::table('horarios', function (Blueprint $table): void {
            if (!Schema::hasColumn('horarios', 'sesion_compartida')) {
                $table->boolean('sesion_compartida')->default(false)->after('ciclo_escolar_id')->index();
            }
            if (!Schema::hasColumn('horarios', 'clave_sesion_compartida')) {
                $table->string('clave_sesion_compartida', 64)->nullable()->after('sesion_compartida')->index();
            }
            if (!Schema::hasColumn('horarios', 'motivo_sesion_compartida')) {
                $table->text('motivo_sesion_compartida')->nullable()->after('clave_sesion_compartida');
            }
        });
    }

    private function extendAsignaciones(): void
    {
        Schema::table('asignacion_materias', function (Blueprint $table): void {
            if (!Schema::hasColumn('asignacion_materias', 'revision_ciclo_estado')) {
                $table->string('revision_ciclo_estado', 20)->nullable()->after('confirmada_por')->index();
            }
            if (!Schema::hasColumn('asignacion_materias', 'revision_ciclo_observacion')) {
                $table->text('revision_ciclo_observacion')->nullable()->after('revision_ciclo_estado');
            }
            if (!Schema::hasColumn('asignacion_materias', 'revision_ciclo_at')) {
                $table->timestamp('revision_ciclo_at')->nullable()->after('revision_ciclo_observacion');
            }
            if (!Schema::hasColumn('asignacion_materias', 'revision_ciclo_por')) {
                $table->foreignId('revision_ciclo_por')
                    ->nullable()
                    ->after('revision_ciclo_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    private function extendGrupos(): void
    {
        Schema::table('grupos', function (Blueprint $table): void {
            if (!Schema::hasColumn('grupos', 'archivado_at')) {
                $table->timestamp('archivado_at')->nullable()->after('motivo_generacion_excepcional');
            }
            if (!Schema::hasColumn('grupos', 'archivado_por')) {
                $table->foreignId('archivado_por')
                    ->nullable()
                    ->after('archivado_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('grupos', 'motivo_archivo')) {
                $table->text('motivo_archivo')->nullable()->after('archivado_por');
            }
        });
    }

    private function createCalificacionCorrecciones(): void
    {
        if (Schema::hasTable('calificacion_correcciones')) {
            return;
        }

        Schema::create('calificacion_correcciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calificacion_id')->nullable()->constrained('calificaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('estado', 20)->default('solicitada')->index();
            $table->text('motivo');
            $table->json('valor_anterior')->nullable();
            $table->json('valor_propuesto')->nullable();
            $table->foreignId('solicitada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('solicitada_at')->nullable();
            $table->foreignId('autorizada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizada_at')->nullable();
            $table->text('observacion_autorizacion')->nullable();
            $table->foreignId('aplicada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aplicada_at')->nullable();
            $table->timestamps();

            $table->index(['periodo_id', 'estado'], 'correcciones_periodo_estado_idx');
            $table->index(['inscripcion_id', 'created_at'], 'correcciones_alumno_fecha_idx');
        });
    }

    private function backfillPeriodDates(): void
    {
        DB::table('periodos')
            ->whereNull('fecha_evaluacion_inicio')
            ->whereNotNull('fecha_inicio')
            ->update(['fecha_evaluacion_inicio' => DB::raw('fecha_inicio')]);

        DB::table('periodos')
            ->whereNull('fecha_evaluacion_fin')
            ->whereNotNull('fecha_fin')
            ->update(['fecha_evaluacion_fin' => DB::raw('fecha_fin')]);
    }

    private function markExistingSharedSessions(): void
    {
        if (!Schema::hasTable('horarios') || !Schema::hasTable('asignacion_materias')) {
            return;
        }

        $conflicts = DB::table('horarios as h')
            ->join('asignacion_materias as am', 'am.id', '=', 'h.asignacion_materia_id')
            ->whereNotNull('am.profesor_id')
            ->select('am.profesor_id', 'h.ciclo_escolar_id', 'h.dia_id', 'h.hora_id', DB::raw('COUNT(*) as total'))
            ->groupBy('am.profesor_id', 'h.ciclo_escolar_id', 'h.dia_id', 'h.hora_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($conflicts as $conflict) {
            $ids = DB::table('horarios as h')
                ->join('asignacion_materias as am', 'am.id', '=', 'h.asignacion_materia_id')
                ->where('am.profesor_id', $conflict->profesor_id)
                ->where('h.ciclo_escolar_id', $conflict->ciclo_escolar_id)
                ->where('h.dia_id', $conflict->dia_id)
                ->where('h.hora_id', $conflict->hora_id)
                ->pluck('h.id');

            if ($ids->count() < 2) {
                continue;
            }

            DB::table('horarios')->whereIn('id', $ids)->update([
                'sesion_compartida' => true,
                'clave_sesion_compartida' => 'legacy-' . Str::uuid(),
                'motivo_sesion_compartida' => 'Traslape histórico reconocido como sesión compartida durante la estabilización por ciclos.',
            ]);
        }
    }

    private function markAssignmentCycleMismatches(): void
    {
        DB::table('asignacion_materias as am')
            ->join('grupos as g', 'g.id', '=', 'am.grupo_id')
            ->whereNotNull('am.ciclo_escolar_id')
            ->whereNotNull('g.ciclo_escolar_id')
            ->whereColumn('am.ciclo_escolar_id', '!=', 'g.ciclo_escolar_id')
            ->update([
                'am.revision_ciclo_estado' => 'pendiente',
                'am.revision_ciclo_observacion' => DB::raw("CONCAT('La carga indica ciclo ', am.ciclo_escolar_id, ' y su grupo pertenece al ciclo ', g.ciclo_escolar_id, '. Requiere revisión administrativa antes de modificarla.')"),
            ]);
    }

    private function archiveLegacyGroup28(): void
    {
        $grupo = DB::table('grupos')->where('id', 28)->first();
        if (!$grupo || $grupo->ciclo_escolar_id !== null) {
            return;
        }

        $usado = false;
        foreach ([
            ['inscripciones', 'grupo_id'],
            ['asignacion_materias', 'grupo_id'],
            ['horarios', 'grupo_id'],
            ['persona_nivel_detalles', 'grupo_id'],
            ['calificaciones', 'grupo_id'],
        ] as [$tabla, $columna]) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, $columna)
                && DB::table($tabla)->where($columna, 28)->exists()) {
                $usado = true;
                break;
            }
        }

        if (!$usado) {
            DB::table('grupos')->where('id', 28)->update([
                'estado' => 'archivado',
                'archivado_at' => now(),
                'motivo_archivo' => 'Grupo histórico sin ciclo y sin relaciones. Se archivó sin eliminarlo; puede editarse y reactivarse desde Grupos.',
                'updated_at' => now(),
            ]);
        }
    }


    private function backfillActiveEnrollmentMatriculas(): void
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasTable('matriculas_alumnos')) {
            return;
        }

        DB::table('inscripciones')
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])
            ->whereNotNull('matricula')
            ->where('matricula', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($alumnos): void {
                foreach ($alumnos as $alumno) {
                    $vigente = DB::table('matriculas_alumnos')
                        ->where('inscripcion_id', $alumno->id)
                        ->where('vigente', true)
                        ->first();

                    if ($vigente) {
                        continue;
                    }

                    $ocupadaPorOtro = DB::table('matriculas_alumnos')
                        ->where('matricula', $alumno->matricula)
                        ->where('inscripcion_id', '!=', $alumno->id)
                        ->exists();

                    if ($ocupadaPorOtro) {
                        continue;
                    }

                    $historica = DB::table('matriculas_alumnos')
                        ->where('inscripcion_id', $alumno->id)
                        ->where('matricula', $alumno->matricula)
                        ->first();

                    $fechaAsignacion = $alumno->fecha_inscripcion
                        ? substr((string) $alumno->fecha_inscripcion, 0, 10)
                        : ($alumno->fecha_ultimo_ingreso ?: now()->toDateString());

                    if ($historica) {
                        DB::table('matriculas_alumnos')
                            ->where('id', $historica->id)
                            ->update([
                                'nivel_id' => $alumno->nivel_id,
                                'fecha_fin' => null,
                                'vigente' => true,
                                'origen' => 'reconstruccion_ciclo',
                                'updated_at' => now(),
                            ]);

                        continue;
                    }

                    DB::table('matriculas_alumnos')->insert([
                        'inscripcion_id' => $alumno->id,
                        'nivel_id' => $alumno->nivel_id,
                        'matricula' => $alumno->matricula,
                        'fecha_asignacion' => $fechaAsignacion,
                        'fecha_fin' => null,
                        'vigente' => true,
                        'origen' => 'reconstruccion_ciclo',
                        'registrado_por' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function clearInvalidGenerationEndCycles(): void
    {
        if (!Schema::hasColumn('generaciones', 'ciclo_escolar_fin_id')) {
            return;
        }

        DB::table('generaciones as gen')
            ->join('ciclo_escolares as ci', 'ci.id', '=', 'gen.ciclo_escolar_fin_id')
            ->whereColumn('ci.inicio_anio', '<', 'gen.anio_egreso')
            ->update(['gen.ciclo_escolar_fin_id' => null]);
    }
};
