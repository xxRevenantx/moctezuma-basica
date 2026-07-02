<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backupTrajectories();
        $this->addStudentStatusFields();
        $this->addGenerationManagementFields();
        $this->createAcademicChangesTable();
        $this->normalizeExistingData();
        $this->removeTrajectoryReferences();

        Schema::dropIfExists('trayectorias_academicas');
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Esta migración elimina definitivamente el sistema de trayectorias. Restaura el respaldo completo de la base de datos para revertirla.'
        );
    }

    private function addStudentStatusFields(): void
    {
        $addEstatus = ! Schema::hasColumn('inscripciones', 'estatus');
        $addFecha = ! Schema::hasColumn('inscripciones', 'fecha_estatus');
        $addMotivo = ! Schema::hasColumn('inscripciones', 'motivo_estatus');

        if (! $addEstatus && ! $addFecha && ! $addMotivo) {
            return;
        }

        Schema::table('inscripciones', function (Blueprint $table) use ($addEstatus, $addFecha, $addMotivo): void {
            if ($addEstatus) {
                $table->string('estatus', 30)->default('activo')->after('activo')->index();
            }
            if ($addFecha) {
                $table->dateTime('fecha_estatus')->nullable()->after('activo');
            }
            if ($addMotivo) {
                $table->text('motivo_estatus')->nullable()->after('activo');
            }
        });
    }

    private function addGenerationManagementFields(): void
    {
        $columns = [
            'nombre' => ! Schema::hasColumn('generaciones', 'nombre'),
            'ciclo_escolar_inicio_id' => ! Schema::hasColumn('generaciones', 'ciclo_escolar_inicio_id'),
            'ciclo_escolar_fin_id' => ! Schema::hasColumn('generaciones', 'ciclo_escolar_fin_id'),
            'fecha_inicio' => ! Schema::hasColumn('generaciones', 'fecha_inicio'),
            'fecha_termino' => ! Schema::hasColumn('generaciones', 'fecha_termino'),
            'motivo_desactivacion' => ! Schema::hasColumn('generaciones', 'motivo_desactivacion'),
            'reactivada_at' => ! Schema::hasColumn('generaciones', 'reactivada_at'),
            'reactivada_por' => ! Schema::hasColumn('generaciones', 'reactivada_por'),
        ];

        if (! in_array(true, $columns, true)) {
            return;
        }

        Schema::table('generaciones', function (Blueprint $table) use ($columns): void {
            if ($columns['nombre']) {
                $table->string('nombre', 50)->nullable();
            }
            if ($columns['ciclo_escolar_inicio_id']) {
                $table->foreignId('ciclo_escolar_inicio_id')->nullable()
                    ->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            }
            if ($columns['ciclo_escolar_fin_id']) {
                $table->foreignId('ciclo_escolar_fin_id')->nullable()
                    ->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            }
            if ($columns['fecha_inicio']) {
                $table->date('fecha_inicio')->nullable();
            }
            if ($columns['fecha_termino']) {
                $table->date('fecha_termino')->nullable();
            }
            if ($columns['motivo_desactivacion']) {
                $table->text('motivo_desactivacion')->nullable();
            }
            if ($columns['reactivada_at']) {
                $table->timestamp('reactivada_at')->nullable();
            }
            if ($columns['reactivada_por']) {
                $table->foreignId('reactivada_por')->nullable()
                    ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            }
        });
    }

    private function createAcademicChangesTable(): void
    {
        if (Schema::hasTable('cambios_academicos')) {
            return;
        }

        Schema::create('cambios_academicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->nullable()->constrained('inscripciones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 50)->index();
            $table->text('motivo');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->foreignId('realizado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('realizado_at')->useCurrent();
            $table->timestamps();

            $table->index(['inscripcion_id', 'realizado_at'], 'cambios_alumno_fecha_idx');
            $table->index(['generacion_id', 'realizado_at'], 'cambios_generacion_fecha_idx');
        });
    }

    private function normalizeExistingData(): void
    {
        DB::table('generaciones')->orderBy('id')->each(function (object $generacion): void {
            $inicio = DB::table('ciclo_escolares')
                ->where('inicio_anio', $generacion->anio_ingreso)
                ->orderBy('id')
                ->value('id');
            $fin = DB::table('ciclo_escolares')
                ->where('fin_anio', $generacion->anio_egreso)
                ->orderByDesc('id')
                ->value('id');

            DB::table('generaciones')->where('id', $generacion->id)->update([
                'nombre' => $generacion->nombre ?: $generacion->anio_ingreso . '-' . $generacion->anio_egreso,
                'ciclo_escolar_inicio_id' => $generacion->ciclo_escolar_inicio_id ?: $inicio,
                'ciclo_escolar_fin_id' => $generacion->ciclo_escolar_fin_id ?: $fin,
                'fecha_inicio' => $generacion->fecha_inicio ?: $generacion->anio_ingreso . '-08-01',
                'fecha_termino' => $generacion->fecha_termino ?: $generacion->anio_egreso . '-07-31',
            ]);
        });

        DB::table('inscripciones')->update([
            'estatus' => DB::raw("CASE WHEN activo = 1 THEN 'activo' WHEN fecha_baja IS NOT NULL THEN 'baja_definitiva' ELSE 'inactivo' END"),
            'fecha_estatus' => DB::raw('COALESCE(fecha_baja, updated_at, created_at)'),
            'motivo_estatus' => DB::raw('COALESCE(motivo_baja, motivo_estatus)'),
        ]);
    }

    private function removeTrajectoryReferences(): void
    {
        foreach ([
            'calificaciones_campos_formativos',
            'decisiones_promocion_oficial',
            'documentos_alumnos',
            'constancias_traslado',
        ] as $table) {
            $this->dropForeignColumn($table, 'trayectoria_academica_id');
        }

        foreach (['trayectoria_academica_id', 'trayectoria_origen_id', 'trayectoria_destino_id'] as $column) {
            $this->dropForeignColumn('movimientos_alumnos', $column);
        }
    }

    private function dropForeignColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $this->dropForeignKeysForColumn($table, $column);
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $constraints = DB::select(
                'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
                 AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table, $column]
            );

            foreach ($constraints as $constraint) {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $table),
                    str_replace('`', '``', $constraint->name)
                ));
            }
            return;
        }

        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
        } catch (Throwable) {
            // La columna puede no tener llave foránea en SQLite u otras instalaciones.
        }
    }

    private function backupTrajectories(): void
    {
        if (! Schema::hasTable('trayectorias_academicas')) {
            return;
        }

        $directory = storage_path('app/private/respaldos/migracion_generaciones');
        File::ensureDirectoryExists($directory);

        $payload = [
            'generado_en' => now()->toIso8601String(),
            'total' => DB::table('trayectorias_academicas')->count(),
            'registros' => DB::table('trayectorias_academicas')->orderBy('id')->get()->all(),
        ];

        File::put(
            $directory . '/historial_anterior_' . now()->format('Ymd_His') . '.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }
};
