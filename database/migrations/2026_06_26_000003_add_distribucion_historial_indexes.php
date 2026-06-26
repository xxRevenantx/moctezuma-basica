<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trayectorias_academicas')) {
            return;
        }

        if (!$this->indexExists('trayectorias_academicas', 'trayectoria_distribucion_historial_idx')) {
            Schema::table('trayectorias_academicas', function (Blueprint $table) {
                $table->index(
                    ['nivel_id', 'ciclo_escolar_id', 'generacion_id', 'grado_id', 'grupo_id'],
                    'trayectoria_distribucion_historial_idx'
                );
            });
        }

        if (!$this->indexExists('trayectorias_academicas', 'trayectoria_estado_actual_historial_idx')) {
            Schema::table('trayectorias_academicas', function (Blueprint $table) {
                $table->index(
                    ['inscripcion_id', 'es_actual', 'activo', 'estatus'],
                    'trayectoria_estado_actual_historial_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('trayectorias_academicas')) {
            return;
        }

        foreach (
            [
                'trayectoria_distribucion_historial_idx',
                'trayectoria_estado_actual_historial_idx',
            ] as $index
        ) {
            if ($this->indexExists('trayectorias_academicas', $index)) {
                DB::statement("ALTER TABLE trayectorias_academicas DROP INDEX {$index}");
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
