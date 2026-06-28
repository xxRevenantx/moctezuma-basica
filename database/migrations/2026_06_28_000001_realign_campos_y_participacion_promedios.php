<?php

use App\Support\CampoFormativoClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materias') || ! Schema::hasTable('campos_formativos')) {
            return;
        }

        if (! Schema::hasColumn('materias', 'campo_formativo_id')) {
            return;
        }

        $campos = DB::table('campos_formativos')->pluck('id', 'slug');
        $sinCampoId = $campos->get(CampoFormativoClassifier::SIN_CAMPO);

        DB::table('materias')
            ->select(['id', 'materia'])
            ->orderBy('id')
            ->chunkById(200, function ($materias) use ($campos, $sinCampoId): void {
                foreach ($materias as $materia) {
                    $slug = CampoFormativoClassifier::sugerir((string) $materia->materia);
                    $campoId = $campos->get($slug, $sinCampoId);

                    if ($campoId !== null) {
                        DB::table('materias')
                            ->where('id', $materia->id)
                            ->update(['campo_formativo_id' => $campoId]);
                    }
                }
            });

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
        // No se revierte porque la migración únicamente corrige datos de catálogo.
    }
};
