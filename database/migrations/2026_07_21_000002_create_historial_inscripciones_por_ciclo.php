<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esta migración es intencionalmente idempotente porque MySQL confirma
     * cambios DDL de forma individual. Si una ejecución anterior falló a
     * mitad, puede continuarse sin borrar las tablas ya creadas.
     */
    public function up(): void
    {
        $this->createPreinscripcionesCiclos();
        $this->createInscripcionCiclos();
        $this->createInscripcionCicloAsignaciones();

        $this->addCycleReference('movimientos_alumnos', 'inscripcion_id');
        $this->addCycleReference('cambios_academicos', 'inscripcion_id');
        $this->addCycleReference('calificaciones', 'inscripcion_id');
        $this->addCycleReference('ficha_descriptivas', 'inscripcion_id');
        $this->addCycleReference('calificaciones_campos_formativos', 'inscripcion_id');
        $this->addCycleReference('asistencias_finales_bachillerato', 'inscripcion_id');
        $this->addCycleReference('decisiones_promocion_oficial', 'inscripcion_id');
        $this->addCycleReference('lugares_preescolar', 'inscripcion_id');

        $this->extendProcesosCierreCiclo();
        $this->extendProcesosCierreCicloDetalles();

        $this->backfillCurrentCycles();
        $this->linkExistingAcademicRecords();
    }

    public function down(): void
    {
        if (Schema::hasTable('procesos_cierre_ciclo_detalles')) {
            foreach (['inscripcion_ciclo_origen_id', 'inscripcion_ciclo_destino_id'] as $column) {
                $this->dropForeignsOnColumn('procesos_cierre_ciclo_detalles', $column);
            }

            $columns = array_values(array_filter([
                'inscripcion_ciclo_origen_id',
                'inscripcion_ciclo_destino_id',
                'resultado_propuesto',
                'destino_propuesto',
            ], fn(string $column): bool => Schema::hasColumn('procesos_cierre_ciclo_detalles', $column)));

            if ($columns !== []) {
                Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('procesos_cierre_ciclo')) {
            $this->dropForeignsOnColumn('procesos_cierre_ciclo', 'ciclo_destino_id');

            $columns = array_values(array_filter([
                'ciclo_destino_id',
                'fecha_efectiva',
                'vista_previa_hash',
            ], fn(string $column): bool => Schema::hasColumn('procesos_cierre_ciclo', $column)));

            if ($columns !== []) {
                Schema::table('procesos_cierre_ciclo', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        foreach (
            [
                'lugares_preescolar',
                'decisiones_promocion_oficial',
                'asistencias_finales_bachillerato',
                'calificaciones_campos_formativos',
                'ficha_descriptivas',
                'calificaciones',
                'cambios_academicos',
                'movimientos_alumnos',
            ] as $tableName
        ) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'inscripcion_ciclo_id')) {
                continue;
            }

            $this->dropForeignsOnColumn($tableName, 'inscripcion_ciclo_id');

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('inscripcion_ciclo_id');
            });
        }

        Schema::dropIfExists('inscripcion_ciclo_asignaciones');

        if (Schema::hasTable('inscripcion_ciclos')) {
            $this->dropForeignsOnColumn('inscripcion_ciclos', 'inscripcion_ciclo_destino_id');
        }

        Schema::dropIfExists('inscripcion_ciclos');
        Schema::dropIfExists('preinscripciones_ciclos');
    }

    private function createPreinscripcionesCiclos(): void
    {
        if (Schema::hasTable('preinscripciones_ciclos')) {
            return;
        }

        Schema::create('preinscripciones_ciclos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id');
            $table->foreignId('ciclo_escolar_id');
            $table->foreignId('nivel_id');
            $table->foreignId('grado_id');
            $table->foreignId('generacion_id');
            $table->foreignId('grupo_id');
            $table->foreignId('semestre_id')->nullable();
            $table->string('matricula_propuesta', 50)->nullable();
            $table->date('fecha_preinscripcion');
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('formalizada_at')->nullable();
            $table->foreignId('formalizada_por')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->foreignId('cancelada_por')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['inscripcion_id', 'ciclo_escolar_id'], 'preinscripcion_alumno_ciclo_unique');
            $table->index(['ciclo_escolar_id', 'estado'], 'preinscripciones_ciclo_estado_idx');

            $table->foreign('inscripcion_id', 'preinsc_ins_fk')->references('id')->on('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('ciclo_escolar_id', 'preinsc_ciclo_fk')->references('id')->on('ciclo_escolares')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('nivel_id', 'preinsc_nivel_fk')->references('id')->on('niveles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('grado_id', 'preinsc_grado_fk')->references('id')->on('grados')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('generacion_id', 'preinsc_gen_fk')->references('id')->on('generaciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('grupo_id', 'preinsc_grupo_fk')->references('id')->on('grupos')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('semestre_id', 'preinsc_sem_fk')->references('id')->on('semestres')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('formalizada_por', 'preinsc_form_por_fk')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('cancelada_por', 'preinsc_canc_por_fk')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    private function createInscripcionCiclos(): void
    {
        if (! Schema::hasTable('inscripcion_ciclos')) {
            Schema::create('inscripcion_ciclos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inscripcion_id');
                $table->foreignId('ciclo_escolar_id');
                $table->string('matricula', 50)->nullable();
                $table->foreignId('nivel_id');
                $table->foreignId('grado_id');
                $table->foreignId('generacion_id');
                $table->foreignId('grupo_id');
                $table->foreignId('semestre_id')->nullable();
                $table->date('fecha_ingreso');
                $table->date('fecha_salida')->nullable();
                $table->string('estado', 20)->default('en_curso');
                $table->string('estatus_ingreso', 30)->default('activo');
                $table->string('estatus_actual_ciclo', 30)->default('activo');
                $table->string('resultado_final', 40)->nullable();
                $table->boolean('promovido')->default(false);
                $table->timestamp('cerrado_at')->nullable();
                $table->foreignId('cerrado_por')->nullable();
                $table->text('motivo_cierre')->nullable();
                $table->unsignedBigInteger('inscripcion_ciclo_destino_id')->nullable();
                $table->json('snapshot_ingreso')->nullable();
                $table->json('snapshot_cierre')->nullable();
                $table->string('origen', 30)->default('registro');
                $table->boolean('reconstruido')->default(false);
                $table->string('nivel_confianza', 20)->default('exacto');
                $table->timestamps();

                $table->unique(['inscripcion_id', 'ciclo_escolar_id'], 'inscripcion_alumno_ciclo_unique');
                $table->index(['ciclo_escolar_id', 'nivel_id', 'grado_id', 'grupo_id'], 'inscripcion_ciclo_contexto_idx');
                $table->index(['ciclo_escolar_id', 'estado', 'resultado_final'], 'inscripcion_ciclo_estado_resultado_idx');

                $table->foreign('inscripcion_id', 'inscc_ins_fk')->references('id')->on('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreign('ciclo_escolar_id', 'inscc_ciclo_fk')->references('id')->on('ciclo_escolares')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreign('nivel_id', 'inscc_nivel_fk')->references('id')->on('niveles')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreign('grado_id', 'inscc_grado_fk')->references('id')->on('grados')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreign('generacion_id', 'inscc_gen_fk')->references('id')->on('generaciones')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreign('grupo_id', 'inscc_grupo_fk')->references('id')->on('grupos')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreign('semestre_id', 'inscc_sem_fk')->references('id')->on('semestres')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('cerrado_por', 'inscc_cerrado_por_fk')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            });
        }

        if (! $this->foreignKeyOnColumnExists('inscripcion_ciclos', 'inscripcion_ciclo_destino_id')) {
            Schema::table('inscripcion_ciclos', function (Blueprint $table): void {
                $table->foreign('inscripcion_ciclo_destino_id', 'inscc_destino_fk')
                    ->references('id')
                    ->on('inscripcion_ciclos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    private function createInscripcionCicloAsignaciones(): void
    {
        if (Schema::hasTable('inscripcion_ciclo_asignaciones')) {
            return;
        }

        Schema::create('inscripcion_ciclo_asignaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_ciclo_id');
            $table->foreignId('nivel_id');
            $table->foreignId('grado_id');
            $table->foreignId('generacion_id');
            $table->foreignId('grupo_id');
            $table->foreignId('semestre_id')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('tipo', 40)->default('asignacion_inicial');
            $table->text('motivo')->nullable();
            $table->boolean('es_actual')->default(true);
            $table->foreignId('registrado_por')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['inscripcion_ciclo_id', 'es_actual'], 'asignacion_ciclo_actual_idx');
            $table->index(['grupo_id', 'fecha_inicio', 'fecha_fin'], 'asignacion_grupo_vigencia_idx');

            $table->foreign('inscripcion_ciclo_id', 'inscca_inscc_fk')->references('id')->on('inscripcion_ciclos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('nivel_id', 'inscca_nivel_fk')->references('id')->on('niveles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('grado_id', 'inscca_grado_fk')->references('id')->on('grados')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('generacion_id', 'inscca_gen_fk')->references('id')->on('generaciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('grupo_id', 'inscca_grupo_fk')->references('id')->on('grupos')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('semestre_id', 'inscca_sem_fk')->references('id')->on('semestres')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('registrado_por', 'inscca_reg_por_fk')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    private function extendProcesosCierreCiclo(): void
    {
        if (! Schema::hasTable('procesos_cierre_ciclo')) {
            return;
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'ciclo_destino_id')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->unsignedBigInteger('ciclo_destino_id')->nullable()->after('ciclo_escolar_id');
            });
        }

        if (! $this->foreignKeyOnColumnExists('procesos_cierre_ciclo', 'ciclo_destino_id')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->foreign('ciclo_destino_id', 'pcc_ciclo_destino_fk')
                    ->references('id')
                    ->on('ciclo_escolares')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'fecha_efectiva')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->date('fecha_efectiva')->nullable()->after('fecha_egreso');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'vista_previa_hash')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->string('vista_previa_hash', 64)->nullable()->after('resumen');
            });
        }
    }

    private function extendProcesosCierreCicloDetalles(): void
    {
        if (! Schema::hasTable('procesos_cierre_ciclo_detalles')) {
            return;
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_origen_id')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->unsignedBigInteger('inscripcion_ciclo_origen_id')->nullable()->after('inscripcion_id');
            });
        }

        if (! $this->foreignKeyOnColumnExists('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_origen_id')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->foreign('inscripcion_ciclo_origen_id', 'pccd_inscc_origen_fk')
                    ->references('id')
                    ->on('inscripcion_ciclos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_destino_id')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->unsignedBigInteger('inscripcion_ciclo_destino_id')->nullable()->after('inscripcion_ciclo_origen_id');
            });
        }

        if (! $this->foreignKeyOnColumnExists('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_destino_id')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->foreign('inscripcion_ciclo_destino_id', 'pccd_inscc_destino_fk')
                    ->references('id')
                    ->on('inscripcion_ciclos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'resultado_propuesto')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->string('resultado_propuesto', 40)->nullable()->after('resultado');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'destino_propuesto')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->json('destino_propuesto')->nullable()->after('resultado_propuesto');
            });
        }
    }

    private function addCycleReference(string $tableName, string $after): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'inscripcion_ciclo_id')) {
            Schema::table($tableName, function (Blueprint $table) use ($after): void {
                $table->unsignedBigInteger('inscripcion_ciclo_id')->nullable()->after($after);
            });
        }

        if ($this->foreignKeyOnColumnExists($tableName, 'inscripcion_ciclo_id')) {
            return;
        }

        $constraint = 'hic_' . substr(md5($tableName . '_inscripcion_ciclo_id'), 0, 18) . '_fk';

        Schema::table($tableName, function (Blueprint $table) use ($constraint): void {
            $table->foreign('inscripcion_ciclo_id', $constraint)
                ->references('id')
                ->on('inscripcion_ciclos')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function foreignKeyOnColumnExists(string $tableName, string $columnName): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function dropForeignsOnColumn(string $tableName, string $columnName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->filter()
            ->unique()
            ->values();

        foreach ($constraints as $constraint) {
            Schema::table($tableName, function (Blueprint $table) use ($constraint): void {
                $table->dropForeign((string) $constraint);
            });
        }
    }

    private function backfillCurrentCycles(): void
    {
        DB::table('inscripciones')
            ->whereNotNull('ciclo_escolar_id')
            ->orderBy('id')
            ->chunkById(200, function ($alumnos): void {
                foreach ($alumnos as $alumno) {
                    $snapshot = [
                        'matricula' => $alumno->matricula,
                        'ciclo_escolar_id' => $alumno->ciclo_escolar_id,
                        'nivel_id' => $alumno->nivel_id,
                        'grado_id' => $alumno->grado_id,
                        'generacion_id' => $alumno->generacion_id,
                        'grupo_id' => $alumno->grupo_id,
                        'semestre_id' => $alumno->semestre_id,
                        'estatus' => $alumno->estatus,
                        'activo' => (bool) $alumno->activo,
                    ];
                    $fecha = substr((string) ($alumno->fecha_inscripcion ?: $alumno->created_at ?: now()), 0, 10);

                    if ($alumno->estatus === 'preinscrito') {
                        DB::table('preinscripciones_ciclos')->updateOrInsert(
                            ['inscripcion_id' => $alumno->id, 'ciclo_escolar_id' => $alumno->ciclo_escolar_id],
                            [
                                'nivel_id' => $alumno->nivel_id,
                                'grado_id' => $alumno->grado_id,
                                'generacion_id' => $alumno->generacion_id,
                                'grupo_id' => $alumno->grupo_id,
                                'semestre_id' => $alumno->semestre_id,
                                'matricula_propuesta' => $alumno->matricula,
                                'fecha_preinscripcion' => $fecha,
                                'estado' => 'pendiente',
                                'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                                'created_at' => $alumno->created_at ?: now(),
                                'updated_at' => now(),
                            ]
                        );
                        continue;
                    }

                    $cerrado = in_array($alumno->estatus, ['baja_definitiva', 'trasladado', 'traslado', 'egresado'], true);
                    $resultado = match ($alumno->estatus) {
                        'baja_definitiva' => 'baja_definitiva',
                        'trasladado', 'traslado' => 'trasladado',
                        'egresado' => 'egresado',
                        default => null,
                    };

                    DB::table('inscripcion_ciclos')->updateOrInsert(
                        ['inscripcion_id' => $alumno->id, 'ciclo_escolar_id' => $alumno->ciclo_escolar_id],
                        [
                            'matricula' => $alumno->matricula,
                            'nivel_id' => $alumno->nivel_id,
                            'grado_id' => $alumno->grado_id,
                            'generacion_id' => $alumno->generacion_id,
                            'grupo_id' => $alumno->grupo_id,
                            'semestre_id' => $alumno->semestre_id,
                            'fecha_ingreso' => $fecha,
                            'fecha_salida' => $cerrado ? substr((string) ($alumno->fecha_baja ?: $alumno->fecha_estatus ?: now()), 0, 10) : null,
                            'estado' => $cerrado ? 'cerrado' : 'en_curso',
                            'estatus_ingreso' => 'activo',
                            'estatus_actual_ciclo' => $alumno->estatus ?: 'activo',
                            'resultado_final' => $resultado,
                            'promovido' => false,
                            'cerrado_at' => $cerrado ? ($alumno->fecha_estatus ?: now()) : null,
                            'motivo_cierre' => $cerrado ? ($alumno->motivo_estatus ?: $alumno->motivo_baja) : null,
                            'snapshot_ingreso' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                            'snapshot_cierre' => $cerrado ? json_encode($snapshot, JSON_UNESCAPED_UNICODE) : null,
                            'origen' => 'migracion_estado_actual',
                            'reconstruido' => true,
                            'nivel_confianza' => 'exacto',
                            'created_at' => $alumno->created_at ?: now(),
                            'updated_at' => now(),
                        ]
                    );

                    $cicloId = DB::table('inscripcion_ciclos')
                        ->where('inscripcion_id', $alumno->id)
                        ->where('ciclo_escolar_id', $alumno->ciclo_escolar_id)
                        ->value('id');

                    if ($cicloId && ! DB::table('inscripcion_ciclo_asignaciones')->where('inscripcion_ciclo_id', $cicloId)->exists()) {
                        DB::table('inscripcion_ciclo_asignaciones')->insert([
                            'inscripcion_ciclo_id' => $cicloId,
                            'nivel_id' => $alumno->nivel_id,
                            'grado_id' => $alumno->grado_id,
                            'generacion_id' => $alumno->generacion_id,
                            'grupo_id' => $alumno->grupo_id,
                            'semestre_id' => $alumno->semestre_id,
                            'fecha_inicio' => $fecha,
                            'fecha_fin' => $cerrado ? substr((string) ($alumno->fecha_baja ?: $alumno->fecha_estatus ?: now()), 0, 10) : null,
                            'tipo' => 'asignacion_inicial',
                            'motivo' => 'Reconstrucción del estado vigente al instalar el historial por ciclo.',
                            'es_actual' => ! $cerrado,
                            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }, 'id');
    }

    private function linkExistingAcademicRecords(): void
    {
        foreach (
            [
                'calificaciones',
                'ficha_descriptivas',
                'calificaciones_campos_formativos',
                'asistencias_finales_bachillerato',
                'decisiones_promocion_oficial',
                'lugares_preescolar',
                'movimientos_alumnos',
            ] as $tableName
        ) {
            if (
                ! Schema::hasTable($tableName)
                || ! Schema::hasColumn($tableName, 'inscripcion_ciclo_id')
                || ! Schema::hasColumn($tableName, 'inscripcion_id')
                || ! Schema::hasColumn($tableName, 'ciclo_escolar_id')
            ) {
                continue;
            }

            DB::table($tableName)
                ->whereNull('inscripcion_ciclo_id')
                ->whereNotNull('ciclo_escolar_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($tableName): void {
                    foreach ($rows as $row) {
                        $cycleId = DB::table('inscripcion_ciclos')
                            ->where('inscripcion_id', $row->inscripcion_id)
                            ->where('ciclo_escolar_id', $row->ciclo_escolar_id)
                            ->value('id');

                        if ($cycleId) {
                            DB::table($tableName)->where('id', $row->id)->update([
                                'inscripcion_ciclo_id' => $cycleId,
                            ]);
                        }
                    }
                }, 'id');
        }
    }
};
