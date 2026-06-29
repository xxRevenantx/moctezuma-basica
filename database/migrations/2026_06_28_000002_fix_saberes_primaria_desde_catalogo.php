<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('niveles')
            || ! Schema::hasTable('campos_formativos')
            || ! Schema::hasTable('materias')
            || ! Schema::hasColumn('materias', 'campo_formativo_id')
        ) {
            return;
        }

        $nivelPrimariaId = DB::table('niveles')
            ->where('slug', 'primaria')
            ->value('id');

        $campoSaberesId = DB::table('campos_formativos')
            ->where('slug', 'saberes-pensamiento-cientifico')
            ->where('activo', true)
            ->value('id');

        if (! $nivelPrimariaId || ! $campoSaberesId) {
            return;
        }

        DB::table('materias')
            ->where('nivel_id', $nivelPrimariaId)
            ->where(function ($query): void {
                $query->where('slug', 'saberes-y-pensamiento-cientifico')
                    ->orWhere('materia', 'Saberes y Pensamiento Científico');
            })
            ->update([
                'campo_formativo_id' => $campoSaberesId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No se revierte: la relación correcta queda definida por el catálogo
        // de campos formativos almacenado en la base de datos.
    }
};
