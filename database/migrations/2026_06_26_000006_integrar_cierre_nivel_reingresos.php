<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            if (!Schema::hasColumn('inscripciones', 'indicador_reingreso')) {
                $table->boolean('indicador_reingreso')->default(false)->after('activo')->index();
            }
            if (!Schema::hasColumn('inscripciones', 'tipo_ultimo_ingreso')) {
                $table->string('tipo_ultimo_ingreso', 30)->default('inscripcion')->after('indicador_reingreso');
            }
            if (!Schema::hasColumn('inscripciones', 'fecha_ultimo_ingreso')) {
                $table->date('fecha_ultimo_ingreso')->nullable()->after('tipo_ultimo_ingreso');
            }
            if (!Schema::hasColumn('inscripciones', 'documentacion_reingreso_pendiente')) {
                $table->boolean('documentacion_reingreso_pendiente')->default(false)->after('fecha_ultimo_ingreso')->index();
            }
            if (!Schema::hasColumn('inscripciones', 'usuario_acceso_activo')) {
                $table->boolean('usuario_acceso_activo')->nullable()->after('documentacion_reingreso_pendiente');
            }
        });

        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            if (!Schema::hasColumn('trayectorias_academicas', 'tipo_ingreso')) {
                $table->string('tipo_ingreso', 30)->default('inscripcion')->after('origen')->index();
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'continuidad')) {
                $table->string('continuidad', 40)->nullable()->after('tipo_ingreso')->index();
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'escuela_procedencia')) {
                $table->string('escuela_procedencia')->nullable()->after('continuidad');
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'cct_procedencia')) {
                $table->string('cct_procedencia', 30)->nullable()->after('escuela_procedencia');
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'ciclo_procedencia')) {
                $table->string('ciclo_procedencia', 20)->nullable()->after('cct_procedencia');
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'ultimo_grado_procedencia')) {
                $table->string('ultimo_grado_procedencia', 120)->nullable()->after('ciclo_procedencia');
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'observaciones_procedencia')) {
                $table->text('observaciones_procedencia')->nullable()->after('ultimo_grado_procedencia');
            }
            if (!Schema::hasColumn('trayectorias_academicas', 'documentacion_pendiente')) {
                $table->boolean('documentacion_pendiente')->default(false)->after('observaciones_procedencia')->index();
            }
        });

        Schema::table('generaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('generaciones', 'cerrada_at')) {
                $table->timestamp('cerrada_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('generaciones', 'cerrada_por')) {
                $table->foreignId('cerrada_por')->nullable()->after('cerrada_at')
                    ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            }
        });

        Schema::table('movimientos_alumnos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos_alumnos', 'trayectoria_destino_id')) {
                $table->foreignId('trayectoria_destino_id')->nullable()->after('trayectoria_origen_id')
                    ->constrained('trayectorias_academicas')->nullOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('movimientos_alumnos', 'nivel_anterior_id')) {
                $table->foreignId('nivel_anterior_id')->nullable()->after('trayectoria_destino_id')
                    ->constrained('niveles')->nullOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('movimientos_alumnos', 'nivel_nuevo_id')) {
                $table->foreignId('nivel_nuevo_id')->nullable()->after('nivel_anterior_id')
                    ->constrained('niveles')->nullOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('movimientos_alumnos', 'resultado_continuidad')) {
                $table->string('resultado_continuidad', 40)->nullable()->after('nivel_nuevo_id')->index();
            }
            if (!Schema::hasColumn('movimientos_alumnos', 'usuario_acceso_activo')) {
                $table->boolean('usuario_acceso_activo')->nullable()->after('resultado_continuidad');
            }
        });

        Schema::table('calificaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('calificaciones', 'fuente')) {
                $table->string('fuente', 20)->default('interna')->after('observacion')->index();
            }
            if (!Schema::hasColumn('calificaciones', 'escuela_procedencia')) {
                $table->string('escuela_procedencia')->nullable()->after('fuente');
            }
            if (!Schema::hasColumn('calificaciones', 'documento_respaldo_id')) {
                $table->foreignId('documento_respaldo_id')->nullable()->after('escuela_procedencia')
                    ->constrained('documentos_alumnos')->nullOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('calificaciones', 'equivalencia_autorizada')) {
                $table->boolean('equivalencia_autorizada')->default(false)->after('documento_respaldo_id')->index();
            }
            if (!Schema::hasColumn('calificaciones', 'fecha_validacion')) {
                $table->timestamp('fecha_validacion')->nullable()->after('equivalencia_autorizada');
            }
            if (!Schema::hasColumn('calificaciones', 'validado_por')) {
                $table->foreignId('validado_por')->nullable()->after('fecha_validacion')
                    ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            }
        });

        if (!Schema::hasTable('constancias_traslado')) {
            Schema::create('constancias_traslado', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('trayectoria_academica_id')->nullable()->constrained('trayectorias_academicas')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
                $table->string('folio', 40)->unique();
                $table->date('fecha_emision');
                $table->string('modalidad', 20)->default('generada');
                $table->json('periodos_incluidos')->nullable();
                $table->text('observaciones')->nullable();
                $table->string('ruta_pdf')->nullable();
                $table->foreignId('documento_alumno_id')->nullable()->constrained('documentos_alumnos')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('emitida_por')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->timestamps();
                $table->index(['inscripcion_id', 'fecha_emision'], 'constancias_traslado_alumno_fecha_idx');
            });
        }

        DB::table('tipos_documentos')->updateOrInsert(
            ['slug' => 'constancia-traslado-calificaciones'],
            [
                'nombre' => 'Constancia de traslado con calificaciones',
                'descripcion' => 'Constancia generada por el sistema o documento externo para acreditar traslado y calificaciones.',
                'es_general' => false,
                'requiere_nivel' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 95,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('constancias_traslado');

        $this->dropForeignIfExists('calificaciones', 'calificaciones_validado_por_foreign');
        $this->dropForeignIfExists('calificaciones', 'calificaciones_documento_respaldo_id_foreign');
        $this->dropColumns('calificaciones', [
            'fuente', 'escuela_procedencia', 'documento_respaldo_id', 'equivalencia_autorizada', 'fecha_validacion', 'validado_por',
        ]);

        $this->dropForeignIfExists('movimientos_alumnos', 'movimientos_alumnos_trayectoria_destino_id_foreign');
        $this->dropForeignIfExists('movimientos_alumnos', 'movimientos_alumnos_nivel_anterior_id_foreign');
        $this->dropForeignIfExists('movimientos_alumnos', 'movimientos_alumnos_nivel_nuevo_id_foreign');
        $this->dropColumns('movimientos_alumnos', [
            'trayectoria_destino_id', 'nivel_anterior_id', 'nivel_nuevo_id', 'resultado_continuidad', 'usuario_acceso_activo',
        ]);

        $this->dropForeignIfExists('generaciones', 'generaciones_cerrada_por_foreign');
        $this->dropColumns('generaciones', ['cerrada_at', 'cerrada_por']);

        $this->dropColumns('trayectorias_academicas', [
            'tipo_ingreso', 'continuidad', 'escuela_procedencia', 'cct_procedencia', 'ciclo_procedencia',
            'ultimo_grado_procedencia', 'observaciones_procedencia', 'documentacion_pendiente',
        ]);

        $this->dropColumns('inscripciones', [
            'indicador_reingreso', 'tipo_ultimo_ingreso', 'fecha_ultimo_ingreso',
            'documentacion_reingreso_pendiente', 'usuario_acceso_activo',
        ]);
    }

    private function dropColumns(string $tableName, array $columns): void
    {
        $existing = collect($columns)->filter(fn (string $column) => Schema::hasColumn($tableName, $column))->all();
        if ($existing !== []) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $database = DB::getDatabaseName();
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
