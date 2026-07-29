<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extenderBitacoraCalificaciones();
        $this->extenderCorreccionesCalificaciones();
        $this->crearIndicesHistoricos();
        $this->vincularBitacoraExistente();
        $this->vincularCorreccionesExistentes();
    }

    public function down(): void
    {
        $this->eliminarIndice('calificaciones', 'calificaciones_historial_contexto_idx');
        $this->eliminarIndice('inscripcion_ciclo_asignaciones', 'inscca_generacion_contexto_idx');
        $this->eliminarIndice('inscripcion_ciclos', 'inscc_generacion_contexto_idx');

        foreach (['calificacion_correcciones', 'bitacora_calificaciones'] as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'inscripcion_ciclo_id')) {
                continue;
            }

            $this->eliminarForeignPorColumna($tabla, 'inscripcion_ciclo_id');
            Schema::table($tabla, function (Blueprint $table): void {
                $table->dropColumn('inscripcion_ciclo_id');
            });
        }
    }

    private function extenderBitacoraCalificaciones(): void
    {
        if (! Schema::hasTable('bitacora_calificaciones')) {
            return;
        }

        if (! Schema::hasColumn('bitacora_calificaciones', 'inscripcion_ciclo_id')) {
            Schema::table('bitacora_calificaciones', function (Blueprint $table): void {
                $table->unsignedBigInteger('inscripcion_ciclo_id')->nullable()->after('inscripcion_id');
            });
        }

        if (! $this->indiceExiste('bitacora_calificaciones', 'bitacora_inscripcion_ciclo_idx')) {
            Schema::table('bitacora_calificaciones', function (Blueprint $table): void {
                $table->index(['inscripcion_ciclo_id', 'periodo_id'], 'bitacora_inscripcion_ciclo_idx');
            });
        }

        if (! $this->foreignExiste('bitacora_calificaciones', 'inscripcion_ciclo_id')) {
            Schema::table('bitacora_calificaciones', function (Blueprint $table): void {
                $table->foreign('inscripcion_ciclo_id', 'bitacora_inscripcion_ciclo_fk')
                    ->references('id')
                    ->on('inscripcion_ciclos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    private function extenderCorreccionesCalificaciones(): void
    {
        if (! Schema::hasTable('calificacion_correcciones')) {
            return;
        }

        if (! Schema::hasColumn('calificacion_correcciones', 'inscripcion_ciclo_id')) {
            Schema::table('calificacion_correcciones', function (Blueprint $table): void {
                $table->unsignedBigInteger('inscripcion_ciclo_id')->nullable()->after('inscripcion_id');
            });
        }

        if (! $this->indiceExiste('calificacion_correcciones', 'correcciones_inscripcion_ciclo_idx')) {
            Schema::table('calificacion_correcciones', function (Blueprint $table): void {
                $table->index(['inscripcion_ciclo_id', 'estado'], 'correcciones_inscripcion_ciclo_idx');
            });
        }

        if (! $this->foreignExiste('calificacion_correcciones', 'inscripcion_ciclo_id')) {
            Schema::table('calificacion_correcciones', function (Blueprint $table): void {
                $table->foreign('inscripcion_ciclo_id', 'correcciones_inscripcion_ciclo_fk')
                    ->references('id')
                    ->on('inscripcion_ciclos')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    private function crearIndicesHistoricos(): void
    {
        if (Schema::hasTable('inscripcion_ciclos') && ! $this->indiceExiste('inscripcion_ciclos', 'inscc_generacion_contexto_idx')) {
            Schema::table('inscripcion_ciclos', function (Blueprint $table): void {
                $table->index(
                    ['generacion_id', 'ciclo_escolar_id', 'grado_id', 'grupo_id', 'semestre_id'],
                    'inscc_generacion_contexto_idx'
                );
            });
        }

        if (Schema::hasTable('inscripcion_ciclo_asignaciones') && ! $this->indiceExiste('inscripcion_ciclo_asignaciones', 'inscca_generacion_contexto_idx')) {
            Schema::table('inscripcion_ciclo_asignaciones', function (Blueprint $table): void {
                $table->index(
                    ['generacion_id', 'grado_id', 'semestre_id', 'grupo_id'],
                    'inscca_generacion_contexto_idx'
                );
            });
        }

        if (Schema::hasTable('calificaciones') && ! $this->indiceExiste('calificaciones', 'calificaciones_historial_contexto_idx')) {
            Schema::table('calificaciones', function (Blueprint $table): void {
                $table->index(
                    ['generacion_id', 'ciclo_escolar_id', 'grado_id', 'grupo_id', 'semestre_id', 'inscripcion_id'],
                    'calificaciones_historial_contexto_idx'
                );
            });
        }
    }

    private function vincularBitacoraExistente(): void
    {
        if (
            ! Schema::hasTable('bitacora_calificaciones')
            || ! Schema::hasTable('inscripcion_ciclos')
            || ! Schema::hasColumn('bitacora_calificaciones', 'inscripcion_ciclo_id')
        ) {
            return;
        }

        DB::table('bitacora_calificaciones')
            ->whereNull('inscripcion_ciclo_id')
            ->whereNotNull('inscripcion_id')
            ->whereNotNull('ciclo_escolar_id')
            ->orderBy('id')
            ->chunkById(500, function ($filas): void {
                foreach ($filas as $fila) {
                    $historialId = DB::table('inscripcion_ciclos')
                        ->where('inscripcion_id', $fila->inscripcion_id)
                        ->where('ciclo_escolar_id', $fila->ciclo_escolar_id)
                        ->value('id');

                    if ($historialId) {
                        DB::table('bitacora_calificaciones')
                            ->where('id', $fila->id)
                            ->whereNull('inscripcion_ciclo_id')
                            ->update(['inscripcion_ciclo_id' => $historialId]);
                    }
                }
            });
    }

    private function vincularCorreccionesExistentes(): void
    {
        if (
            ! Schema::hasTable('calificacion_correcciones')
            || ! Schema::hasTable('inscripcion_ciclos')
            || ! Schema::hasColumn('calificacion_correcciones', 'inscripcion_ciclo_id')
        ) {
            return;
        }

        DB::table('calificacion_correcciones')
            ->whereNull('inscripcion_ciclo_id')
            ->orderBy('id')
            ->chunkById(300, function ($filas): void {
                foreach ($filas as $fila) {
                    $propuesto = json_decode((string) ($fila->valor_propuesto ?? ''), true) ?: [];
                    $historialId = (int) ($propuesto['inscripcion_ciclo_id'] ?? 0);

                    if ($historialId <= 0 && $fila->calificacion_id) {
                        $historialId = (int) DB::table('calificaciones')
                            ->where('id', $fila->calificacion_id)
                            ->value('inscripcion_ciclo_id');
                    }

                    if ($historialId <= 0) {
                        $cicloId = DB::table('periodos')
                            ->where('id', $fila->periodo_id)
                            ->value('ciclo_escolar_id');

                        if ($cicloId) {
                            $historialId = (int) DB::table('inscripcion_ciclos')
                                ->where('inscripcion_id', $fila->inscripcion_id)
                                ->where('ciclo_escolar_id', $cicloId)
                                ->value('id');
                        }
                    }

                    if ($historialId > 0) {
                        DB::table('calificacion_correcciones')
                            ->where('id', $fila->id)
                            ->whereNull('inscripcion_ciclo_id')
                            ->update(['inscripcion_ciclo_id' => $historialId]);
                    }
                }
            });
    }

    private function indiceExiste(string $tabla, string $indice): bool
    {
        if (! Schema::hasTable($tabla)) {
            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tabla)
            ->where('INDEX_NAME', $indice)
            ->exists();
    }

    private function foreignExiste(string $tabla, string $columna): bool
    {
        $base = DB::getDatabaseName();

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $base)
            ->where('TABLE_NAME', $tabla)
            ->where('COLUMN_NAME', $columna)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function eliminarIndice(string $tabla, string $indice): void
    {
        if (! $this->indiceExiste($tabla, $indice)) {
            return;
        }

        Schema::table($tabla, function (Blueprint $table) use ($indice): void {
            $table->dropIndex($indice);
        });
    }

    private function eliminarForeignPorColumna(string $tabla, string $columna): void
    {
        $base = DB::getDatabaseName();
        $foreign = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $base)
            ->where('TABLE_NAME', $tabla)
            ->where('COLUMN_NAME', $columna)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($foreign) {
            Schema::table($tabla, function (Blueprint $table) use ($foreign): void {
                $table->dropForeign($foreign);
            });
        }
    }
};
