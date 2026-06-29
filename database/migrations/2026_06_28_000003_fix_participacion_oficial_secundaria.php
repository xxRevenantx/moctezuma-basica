<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materias')
            || ! Schema::hasTable('niveles')
            || ! Schema::hasTable('campos_formativos')
            || ! Schema::hasColumn('materias', 'participa_en_calificacion_oficial')) {
            return;
        }

        $nivelSecundariaId = DB::table('niveles')
            ->where('slug', 'secundaria')
            ->value('id');

        if (! $nivelSecundariaId) {
            return;
        }

        $sinCampoFormativoId = DB::table('campos_formativos')
            ->where('slug', 'sin-campo-formativo')
            ->value('id');

        DB::table('materias')
            ->where('nivel_id', $nivelSecundariaId)
            ->where(function ($query): void {
                $query->where('calificable', false)
                    ->orWhere('extra', true)
                    ->orWhere('receso', true);
            })
            ->update(['participa_en_calificacion_oficial' => false]);

        if ($sinCampoFormativoId) {
            DB::table('materias')
                ->where('nivel_id', $nivelSecundariaId)
                ->where('campo_formativo_id', $sinCampoFormativoId)
                ->update(['participa_en_calificacion_oficial' => false]);
        }
    }

    public function down(): void
    {
        // No se revierte: la migración corrige la configuración académica
        // conforme a los campos y banderas existentes en la base de datos.
    }
};
