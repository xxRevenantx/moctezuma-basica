<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materias')) {
            return;
        }

        if (! Schema::hasColumn('materias', 'participa_en_calificacion_oficial')) {
            return;
        }

        DB::table('materias')
            ->where(function ($query): void {
                $query->where('calificable', false)
                    ->orWhere('extra', true)
                    ->orWhere('receso', true);
            })
            ->update(['participa_en_calificacion_oficial' => false]);

        DB::table('materias')
            ->select(['id', 'materia'])
            ->orderBy('id')
            ->chunkById(200, function ($materias): void {
                foreach ($materias as $materia) {
                    $nombre = trim((string) preg_replace(
                        '/\s+/u',
                        ' ',
                        Str::lower(Str::ascii((string) $materia->materia))
                    ));

                    $esInformativa = str_contains($nombre, 'tutoria')
                        || str_contains($nombre, 'socioemocional')
                        || str_contains($nombre, 'receso');

                    if ($esInformativa) {
                        DB::table('materias')
                            ->where('id', $materia->id)
                            ->update(['participa_en_calificacion_oficial' => false]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No se revierte porque la migración únicamente corrige la participación
        // de materias en los promedios oficiales.
    }
};
