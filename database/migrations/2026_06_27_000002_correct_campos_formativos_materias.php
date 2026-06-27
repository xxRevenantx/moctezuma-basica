<?php

use App\Support\CampoFormativoClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('campos_formativos')
            || ! Schema::hasTable('materias')
            || ! Schema::hasColumn('materias', 'campo_formativo_id')
        ) {
            return;
        }

        $ids = DB::table('campos_formativos')->pluck('id', 'slug');

        DB::table('materias')
            ->select(['id', 'materia'])
            ->orderBy('id')
            ->chunkById(200, function ($materias) use ($ids): void {
                foreach ($materias as $materia) {
                    $slug = CampoFormativoClassifier::sugerir((string) $materia->materia);

                    if ($slug === CampoFormativoClassifier::SIN_CAMPO) {
                        continue;
                    }

                    $campoId = $ids->get($slug);

                    if (! $campoId) {
                        continue;
                    }

                    DB::table('materias')
                        ->where('id', $materia->id)
                        ->update(['campo_formativo_id' => $campoId]);
                }
            });
    }

    /**
     * Es una corrección de catálogo. No se revierte para no restaurar
     * clasificaciones incorrectas ni sobrescribir ajustes posteriores.
     */
    public function down(): void
    {
        // Sin operación intencional.
    }
};
