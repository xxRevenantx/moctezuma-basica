<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignacion_materias', function (Blueprint $table) {
            if (!Schema::hasColumn('asignacion_materias', 'ciclo_escolar_id')) {
                $table->unsignedBigInteger('ciclo_escolar_id')->nullable()->after('profesor_id');
            }

            if (!Schema::hasColumn('asignacion_materias', 'nivel_id')) {
                $table->unsignedBigInteger('nivel_id')->nullable()->after('ciclo_escolar_id');
            }

            if (!Schema::hasColumn('asignacion_materias', 'grado_id')) {
                $table->unsignedBigInteger('grado_id')->nullable()->after('nivel_id');
            }

            if (!Schema::hasColumn('asignacion_materias', 'generacion_id')) {
                $table->unsignedBigInteger('generacion_id')->nullable()->after('grado_id');
            }

            if (!Schema::hasColumn('asignacion_materias', 'semestre_id')) {
                $table->unsignedBigInteger('semestre_id')->nullable()->after('generacion_id');
            }

            if (!Schema::hasColumn('asignacion_materias', 'estado')) {
                $table->string('estado', 20)->default('activa')->after('orden');
            }

            if (!Schema::hasColumn('asignacion_materias', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('estado');
            }

            if (!Schema::hasColumn('asignacion_materias', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }

            if (!Schema::hasColumn('asignacion_materias', 'asignacion_origen_id')) {
                $table->unsignedBigInteger('asignacion_origen_id')->nullable()->after('fecha_fin');
            }

            if (!Schema::hasColumn('asignacion_materias', 'confirmada_at')) {
                $table->timestamp('confirmada_at')->nullable()->after('asignacion_origen_id');
            }

            if (!Schema::hasColumn('asignacion_materias', 'confirmada_por')) {
                $table->unsignedBigInteger('confirmada_por')->nullable()->after('confirmada_at');
            }
        });

        $this->normalizarAsignacionesExistentes();

        Schema::table('asignacion_materias', function (Blueprint $table) {
            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_ciclo_escolar_id_foreign')) {
                $table->foreign('ciclo_escolar_id')
                    ->references('id')->on('ciclo_escolares')
                    ->nullOnDelete()->cascadeOnUpdate();
            }

            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_nivel_id_foreign')) {
                $table->foreign('nivel_id')->references('id')->on('niveles')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_grado_id_foreign')) {
                $table->foreign('grado_id')->references('id')->on('grados')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_generacion_id_foreign')) {
                $table->foreign('generacion_id')->references('id')->on('generaciones')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_semestre_id_foreign')) {
                $table->foreign('semestre_id')->references('id')->on('semestres')->nullOnDelete()->cascadeOnUpdate();
            }

            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_asignacion_origen_id_foreign')) {
                $table->foreign('asignacion_origen_id')
                    ->references('id')->on('asignacion_materias')
                    ->nullOnDelete()->cascadeOnUpdate();
            }

            if (!$this->foreignKeyExists('asignacion_materias', 'asignacion_materias_confirmada_por_foreign')) {
                $table->foreign('confirmada_por')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
            }
        });

        if (!$this->indexExists('asignacion_materias', 'carga_ciclo_profesor_estado_idx')) {
            Schema::table('asignacion_materias', function (Blueprint $table) {
                $table->index(
                    ['ciclo_escolar_id', 'profesor_id', 'estado'],
                    'carga_ciclo_profesor_estado_idx'
                );
            });
        }

        if (!$this->indexExists('asignacion_materias', 'carga_contexto_academico_idx')) {
            Schema::table('asignacion_materias', function (Blueprint $table) {
                $table->index(
                    ['ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'grupo_id', 'semestre_id'],
                    'carga_contexto_academico_idx'
                );
            });
        }

        if (!$this->indexExists('asignacion_materias', 'carga_ciclo_materia_grupo_idx')) {
            Schema::table('asignacion_materias', function (Blueprint $table) {
                $table->index(
                    ['ciclo_escolar_id', 'materia_id', 'grupo_id'],
                    'carga_ciclo_materia_grupo_idx'
                );
            });
        }

        if (Schema::hasTable('taller_sesiones')) {
            Schema::table('taller_sesiones', function (Blueprint $table) {
                if (!Schema::hasColumn('taller_sesiones', 'estado')) {
                    $table->string('estado', 20)->default('activa')->after('ciclo_escolar_id');
                }

                if (!Schema::hasColumn('taller_sesiones', 'fecha_inicio')) {
                    $table->date('fecha_inicio')->nullable()->after('estado');
                }

                if (!Schema::hasColumn('taller_sesiones', 'fecha_fin')) {
                    $table->date('fecha_fin')->nullable()->after('fecha_inicio');
                }

                if (!Schema::hasColumn('taller_sesiones', 'confirmada_at')) {
                    $table->timestamp('confirmada_at')->nullable()->after('fecha_fin');
                }

                if (!Schema::hasColumn('taller_sesiones', 'confirmada_por')) {
                    $table->unsignedBigInteger('confirmada_por')->nullable()->after('confirmada_at');
                }
            });

            DB::table('taller_sesiones')
                ->whereNull('estado')
                ->update(['estado' => 'activa']);

            if (!$this->indexExists('taller_sesiones', 'taller_ciclo_profesor_estado_idx')) {
                Schema::table('taller_sesiones', function (Blueprint $table) {
                    $table->index(
                        ['ciclo_escolar_id', 'profesor_id', 'estado'],
                        'taller_ciclo_profesor_estado_idx'
                    );
                });
            }

            if (!$this->foreignKeyExists('taller_sesiones', 'taller_sesiones_confirmada_por_foreign')) {
                Schema::table('taller_sesiones', function (Blueprint $table) {
                    $table->foreign('confirmada_por')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('taller_sesiones')) {
            Schema::table('taller_sesiones', function (Blueprint $table) {
                if ($this->foreignKeyExists('taller_sesiones', 'taller_sesiones_confirmada_por_foreign')) {
                    $table->dropForeign('taller_sesiones_confirmada_por_foreign');
                }

                if ($this->indexExists('taller_sesiones', 'taller_ciclo_profesor_estado_idx')) {
                    $table->dropIndex('taller_ciclo_profesor_estado_idx');
                }

                $columnas = collect(['estado', 'fecha_inicio', 'fecha_fin', 'confirmada_at', 'confirmada_por'])
                    ->filter(fn (string $columna) => Schema::hasColumn('taller_sesiones', $columna))
                    ->all();

                if ($columnas !== []) {
                    $table->dropColumn($columnas);
                }
            });
        }

        Schema::table('asignacion_materias', function (Blueprint $table) {
            foreach ([
                'asignacion_materias_confirmada_por_foreign',
                'asignacion_materias_asignacion_origen_id_foreign',
                'asignacion_materias_semestre_id_foreign',
                'asignacion_materias_generacion_id_foreign',
                'asignacion_materias_grado_id_foreign',
                'asignacion_materias_nivel_id_foreign',
                'asignacion_materias_ciclo_escolar_id_foreign',
            ] as $foreign) {
                if ($this->foreignKeyExists('asignacion_materias', $foreign)) {
                    $table->dropForeign($foreign);
                }
            }

            foreach ([
                'carga_ciclo_profesor_estado_idx',
                'carga_contexto_academico_idx',
                'carga_ciclo_materia_grupo_idx',
            ] as $indice) {
                if ($this->indexExists('asignacion_materias', $indice)) {
                    $table->dropIndex($indice);
                }
            }

            $columnas = collect([
                'ciclo_escolar_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
                'estado',
                'fecha_inicio',
                'fecha_fin',
                'asignacion_origen_id',
                'confirmada_at',
                'confirmada_por',
            ])->filter(fn (string $columna) => Schema::hasColumn('asignacion_materias', $columna))->all();

            if ($columnas !== []) {
                $table->dropColumn($columnas);
            }
        });
    }

    private function normalizarAsignacionesExistentes(): void
    {
        $cicloActualId = DB::table('ciclo_escolares')
            ->where('es_actual', true)
            ->value('id');

        $cicloActualId ??= DB::table('ciclo_escolares')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->value('id');

        $asignaciones = DB::table('asignacion_materias')
            ->orderBy('id')
            ->get();

        foreach ($asignaciones as $asignacion) {
            $grupo = DB::table('grupos')->where('id', $asignacion->grupo_id)->first();

            // Se unen todas las evidencias. Una asignación antigua pudo haber sido
            // reutilizada en horarios de un ciclo y en calificaciones de otro.
            $ciclos = collect();

            if (Schema::hasTable('horarios') && Schema::hasColumn('horarios', 'ciclo_escolar_id')) {
                $ciclos = $ciclos->concat(
                    DB::table('horarios')
                        ->where('asignacion_materia_id', $asignacion->id)
                        ->whereNotNull('ciclo_escolar_id')
                        ->pluck('ciclo_escolar_id')
                );
            }

            if (Schema::hasTable('calificaciones') && Schema::hasColumn('calificaciones', 'ciclo_escolar_id')) {
                $ciclos = $ciclos->concat(
                    DB::table('calificaciones')
                        ->where('asignacion_materia_id', $asignacion->id)
                        ->whereNotNull('ciclo_escolar_id')
                        ->pluck('ciclo_escolar_id')
                );
            }

            $ciclos = $ciclos
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            if ($ciclos->isEmpty() && $cicloActualId) {
                $ciclos = collect([(int) $cicloActualId]);
            }

            $cicloPrincipal = $ciclos->first();

            DB::table('asignacion_materias')
                ->where('id', $asignacion->id)
                ->update([
                    'ciclo_escolar_id' => $cicloPrincipal,
                    'nivel_id' => $grupo?->nivel_id,
                    'grado_id' => $grupo?->grado_id,
                    'generacion_id' => $grupo?->generacion_id,
                    'semestre_id' => $grupo?->semestre_id,
                    'estado' => 'activa',
                    'fecha_inicio' => $this->fechaInicioCiclo($cicloPrincipal),
                    'confirmada_at' => $asignacion->updated_at ?: now(),
                    'updated_at' => now(),
                ]);

            foreach ($ciclos->skip(1) as $cicloId) {
                $nuevoId = DB::table('asignacion_materias')->insertGetId([
                    'materia_id' => $asignacion->materia_id,
                    'grupo_id' => $asignacion->grupo_id,
                    'profesor_id' => $asignacion->profesor_id,
                    'ciclo_escolar_id' => $cicloId,
                    'nivel_id' => $grupo?->nivel_id,
                    'grado_id' => $grupo?->grado_id,
                    'generacion_id' => $grupo?->generacion_id,
                    'semestre_id' => $grupo?->semestre_id,
                    'orden' => $asignacion->orden,
                    'estado' => 'activa',
                    'fecha_inicio' => $this->fechaInicioCiclo((int) $cicloId),
                    'fecha_fin' => null,
                    'asignacion_origen_id' => $asignacion->id,
                    'confirmada_at' => $asignacion->updated_at ?: now(),
                    'confirmada_por' => null,
                    'created_at' => $asignacion->created_at ?: now(),
                    'updated_at' => now(),
                ]);

                if (Schema::hasTable('horarios')) {
                    DB::table('horarios')
                        ->where('asignacion_materia_id', $asignacion->id)
                        ->where('ciclo_escolar_id', $cicloId)
                        ->update(['asignacion_materia_id' => $nuevoId]);
                }

                if (Schema::hasTable('calificaciones')) {
                    DB::table('calificaciones')
                        ->where('asignacion_materia_id', $asignacion->id)
                        ->where('ciclo_escolar_id', $cicloId)
                        ->update(['asignacion_materia_id' => $nuevoId]);
                }

                if (Schema::hasTable('bitacora_calificaciones')) {
                    DB::table('bitacora_calificaciones')
                        ->where('asignacion_materia_id', $asignacion->id)
                        ->where('ciclo_escolar_id', $cicloId)
                        ->update(['asignacion_materia_id' => $nuevoId]);
                }
            }
        }
    }

    private function fechaInicioCiclo(?int $cicloId): ?string
    {
        if (!$cicloId) {
            return null;
        }

        $anio = DB::table('ciclo_escolares')->where('id', $cicloId)->value('inicio_anio');

        return $anio ? sprintf('%04d-08-01', (int) $anio) : null;
    }

    private function indexExists(string $tabla, string $indice): bool
    {
        $base = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $base)
            ->where('table_name', $tabla)
            ->where('index_name', $indice)
            ->exists();
    }

    private function foreignKeyExists(string $tabla, string $foreign): bool
    {
        $base = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', $base)
            ->where('table_name', $tabla)
            ->where('constraint_name', $foreign)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
