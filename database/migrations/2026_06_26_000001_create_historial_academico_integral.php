<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->expandirCiclosEscolares();
        $this->expandirTrayectorias();
        $this->expandirMovimientos();
        $this->crearHistorialMatriculas();
        $this->reconstruirDatosIniciales();
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas_alumnos');

        foreach (
            [
                'movimientos_alumnos_ciclo_escolar_id_foreign',
                'movimientos_alumnos_ciclo_id_foreign',
                'movimientos_alumnos_trayectoria_origen_id_foreign',
            ] as $constraint
        ) {
            if ($this->foreignKeyExists('movimientos_alumnos', $constraint)) {
                DB::statement("ALTER TABLE movimientos_alumnos DROP FOREIGN KEY {$constraint}");
            }
        }

        foreach (['movimientos_ciclo_corte_idx', 'movimientos_tipo_fecha_idx'] as $index) {
            if ($this->indexExists('movimientos_alumnos', $index)) {
                DB::statement("ALTER TABLE movimientos_alumnos DROP INDEX {$index}");
            }
        }

        Schema::table('movimientos_alumnos', function (Blueprint $table) {
            $columns = collect([
                'ciclo_escolar_id',
                'ciclo_id',
                'trayectoria_origen_id',
                'estado_anterior',
                'estado_nuevo',
            ])->filter(fn(string $column) => Schema::hasColumn('movimientos_alumnos', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        foreach (
            [
                'trayectoria_contexto_vigente_idx',
                'trayectoria_alumno_timeline_idx',
                'trayectoria_estatus_idx',
                'trayectoria_estancia_unique',
            ] as $index
        ) {
            if ($this->indexExists('trayectorias_academicas', $index)) {
                DB::statement("ALTER TABLE trayectorias_academicas DROP INDEX {$index}");
            }
        }

        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            $columns = collect([
                'estatus',
                'fecha_inicio',
                'fecha_fin',
                'numero_estancia',
                'vigente_en_corte',
                'es_actual',
                'origen',
                'datos_reconstruidos',
            ])->filter(fn(string $column) => Schema::hasColumn('trayectorias_academicas', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        if ($this->foreignKeyExists('ciclo_escolares', 'ciclo_escolares_cerrado_por_foreign')) {
            DB::statement('ALTER TABLE ciclo_escolares DROP FOREIGN KEY ciclo_escolares_cerrado_por_foreign');
        }

        Schema::table('ciclo_escolares', function (Blueprint $table) {
            $columns = collect(['es_actual', 'cerrado_at', 'cerrado_por'])
                ->filter(fn(string $column) => Schema::hasColumn('ciclo_escolares', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function expandirCiclosEscolares(): void
    {
        Schema::table('ciclo_escolares', function (Blueprint $table) {
            if (!Schema::hasColumn('ciclo_escolares', 'es_actual')) {
                $table->boolean('es_actual')->default(false)->after('fin_anio')->index();
            }

            if (!Schema::hasColumn('ciclo_escolares', 'cerrado_at')) {
                $table->timestamp('cerrado_at')->nullable()->after('es_actual');
            }

            if (!Schema::hasColumn('ciclo_escolares', 'cerrado_por')) {
                $table->foreignId('cerrado_por')->nullable()->after('cerrado_at')
                    ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            }
        });
    }

    private function expandirTrayectorias(): void
    {
        /*
         * La llave foránea de inscripcion_id usa actualmente el índice único
         * trayectoria_inscripcion_ciclo_unique porque inscripcion_id es su
         * primera columna. MySQL no permite eliminar ese índice mientras la
         * llave foránea siga activa.
         *
         * Por eso el orden correcto es:
         * 1) eliminar temporalmente la llave foránea;
         * 2) eliminar el índice único anterior;
         * 3) crear un índice normal para inscripcion_id;
         * 4) volver a crear la llave foránea con ON DELETE RESTRICT.
         */
        $inscripcionForeign = 'trayectorias_academicas_inscripcion_id_foreign';
        $inscripcionIndex = 'trayectoria_inscripcion_idx';
        $indiceUnicoAnterior = 'trayectoria_inscripcion_ciclo_unique';

        if ($this->foreignKeyExists('trayectorias_academicas', $inscripcionForeign)) {
            DB::statement(
                "ALTER TABLE trayectorias_academicas DROP FOREIGN KEY {$inscripcionForeign}"
            );
        }

        if ($this->indexExists('trayectorias_academicas', $indiceUnicoAnterior)) {
            DB::statement(
                "ALTER TABLE trayectorias_academicas DROP INDEX {$indiceUnicoAnterior}"
            );
        }

        if (!$this->indexExists('trayectorias_academicas', $inscripcionIndex)) {
            DB::statement(
                "ALTER TABLE trayectorias_academicas ADD INDEX {$inscripcionIndex} (inscripcion_id)"
            );
        }

        // El historial no debe desaparecer aunque una inscripción sea archivada.
        if (!$this->foreignKeyExists('trayectorias_academicas', $inscripcionForeign)) {
            DB::statement(
                "ALTER TABLE trayectorias_academicas ADD CONSTRAINT {$inscripcionForeign} "
                    . 'FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id) '
                    . 'ON DELETE RESTRICT ON UPDATE CASCADE'
            );
        }

        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            if (!Schema::hasColumn('trayectorias_academicas', 'estatus')) {
                $table->string('estatus', 30)->default('activo')->after('activo');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'fecha_inicio')) {
                $table->dateTime('fecha_inicio')->nullable()->after('fecha_inscripcion');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'fecha_fin')) {
                $table->dateTime('fecha_fin')->nullable()->after('fecha_inicio');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'numero_estancia')) {
                $table->unsignedInteger('numero_estancia')->default(1)->after('fecha_fin');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'vigente_en_corte')) {
                $table->boolean('vigente_en_corte')->default(true)->after('numero_estancia');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'es_actual')) {
                $table->boolean('es_actual')->default(false)->after('vigente_en_corte');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'origen')) {
                $table->string('origen', 30)->default('registro')->after('es_actual');
            }

            if (!Schema::hasColumn('trayectorias_academicas', 'datos_reconstruidos')) {
                $table->boolean('datos_reconstruidos')->default(false)->after('origen');
            }
        });

        if (!$this->indexExists('trayectorias_academicas', 'trayectoria_contexto_vigente_idx')) {
            Schema::table('trayectorias_academicas', function (Blueprint $table) {
                $table->index(
                    ['ciclo_escolar_id', 'ciclo_id', 'nivel_id', 'vigente_en_corte'],
                    'trayectoria_contexto_vigente_idx'
                );
            });
        }

        if (!$this->indexExists('trayectorias_academicas', 'trayectoria_alumno_timeline_idx')) {
            Schema::table('trayectorias_academicas', function (Blueprint $table) {
                $table->index(
                    ['inscripcion_id', 'ciclo_escolar_id', 'ciclo_id', 'numero_estancia'],
                    'trayectoria_alumno_timeline_idx'
                );
            });
        }

        if (!$this->indexExists('trayectorias_academicas', 'trayectoria_estatus_idx')) {
            Schema::table('trayectorias_academicas', function (Blueprint $table) {
                $table->index(['estatus', 'activo'], 'trayectoria_estatus_idx');
            });
        }

        if (!$this->indexExists('trayectorias_academicas', 'trayectoria_estancia_unique')) {
            Schema::table('trayectorias_academicas', function (Blueprint $table) {
                $table->unique(
                    ['inscripcion_id', 'ciclo_escolar_id', 'ciclo_id', 'numero_estancia'],
                    'trayectoria_estancia_unique'
                );
            });
        }
    }

    private function expandirMovimientos(): void
    {
        Schema::table('movimientos_alumnos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos_alumnos', 'ciclo_escolar_id')) {
                $table->foreignId('ciclo_escolar_id')->nullable()->after('trayectoria_academica_id')
                    ->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!Schema::hasColumn('movimientos_alumnos', 'ciclo_id')) {
                $table->foreignId('ciclo_id')->nullable()->after('ciclo_escolar_id')
                    ->constrained('ciclos')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!Schema::hasColumn('movimientos_alumnos', 'trayectoria_origen_id')) {
                $table->foreignId('trayectoria_origen_id')->nullable()->after('ciclo_id')
                    ->constrained('trayectorias_academicas')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!Schema::hasColumn('movimientos_alumnos', 'estado_anterior')) {
                $table->json('estado_anterior')->nullable()->after('observaciones');
            }

            if (!Schema::hasColumn('movimientos_alumnos', 'estado_nuevo')) {
                $table->json('estado_nuevo')->nullable()->after('estado_anterior');
            }
        });

        if (!$this->indexExists('movimientos_alumnos', 'movimientos_ciclo_corte_idx')) {
            Schema::table('movimientos_alumnos', function (Blueprint $table) {
                $table->index(['ciclo_escolar_id', 'ciclo_id'], 'movimientos_ciclo_corte_idx');
            });
        }

        if (!$this->indexExists('movimientos_alumnos', 'movimientos_tipo_fecha_idx')) {
            Schema::table('movimientos_alumnos', function (Blueprint $table) {
                $table->index(['tipo', 'fecha'], 'movimientos_tipo_fecha_idx');
            });
        }
    }

    private function crearHistorialMatriculas(): void
    {
        if (Schema::hasTable('matriculas_alumnos')) {
            return;
        }

        Schema::create('matriculas_alumnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('matricula', 50)->unique();
            $table->date('fecha_asignacion');
            $table->date('fecha_fin')->nullable();
            $table->boolean('vigente')->default(true);
            $table->string('origen', 30)->default('registro');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['inscripcion_id', 'nivel_id', 'vigente'], 'matriculas_alumno_nivel_idx');
        });
    }

    private function reconstruirDatosIniciales(): void
    {
        $ultimoCicloId = DB::table('ciclo_escolares')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->value('id');

        if ($ultimoCicloId) {
            DB::table('ciclo_escolares')->update([
                'es_actual' => false,
            ]);

            DB::table('ciclo_escolares')
                ->where('id', $ultimoCicloId)
                ->update([
                    'es_actual' => true,
                    'cerrado_at' => null,
                    'cerrado_por' => null,
                ]);

            DB::table('ciclo_escolares')
                ->where('id', '!=', $ultimoCicloId)
                ->whereNull('cerrado_at')
                ->update(['cerrado_at' => now()]);
        }

        DB::table('trayectorias_academicas')->update([
            'estatus' => DB::raw("CASE WHEN activo = 1 THEN 'activo' ELSE 'baja_definitiva' END"),
            'fecha_inicio' => DB::raw('COALESCE(fecha_inscripcion, created_at)'),
            'numero_estancia' => 1,
            'vigente_en_corte' => true,
            'es_actual' => false,
            'origen' => 'migracion_inicial',
            'datos_reconstruidos' => false,
        ]);

        $ultimasTrayectorias = DB::table('trayectorias_academicas as t')
            ->join('ciclo_escolares as ce', 'ce.id', '=', 't.ciclo_escolar_id')
            ->orderBy('t.inscripcion_id')
            ->orderByDesc('ce.inicio_anio')
            ->orderByDesc('ce.fin_anio')
            ->orderByDesc('t.ciclo_id')
            ->orderByDesc('t.id')
            ->get(['t.id', 't.inscripcion_id'])
            ->groupBy('inscripcion_id')
            ->map(fn($trayectorias) => $trayectorias->first()->id)
            ->values();

        if ($ultimasTrayectorias->isNotEmpty()) {
            DB::table('trayectorias_academicas')
                ->whereIn('id', $ultimasTrayectorias)
                ->update(['es_actual' => true]);
        }

        DB::table('inscripciones')
            ->whereNotNull('matricula')
            ->whereNotNull('nivel_id')
            ->orderBy('id')
            ->chunkById(200, function ($alumnos) {
                foreach ($alumnos as $alumno) {
                    DB::table('matriculas_alumnos')->updateOrInsert(
                        ['matricula' => $alumno->matricula],
                        [
                            'inscripcion_id' => $alumno->id,
                            'nivel_id' => $alumno->nivel_id,
                            'fecha_asignacion' => $alumno->fecha_inscripcion
                                ? date('Y-m-d', strtotime($alumno->fecha_inscripcion))
                                : now()->toDateString(),
                            'fecha_fin' => null,
                            'vigente' => true,
                            'origen' => 'migracion_inicial',
                            'registrado_por' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
