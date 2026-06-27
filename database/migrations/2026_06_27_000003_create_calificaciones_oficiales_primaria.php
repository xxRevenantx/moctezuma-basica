<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('materias', 'participa_en_calificacion_oficial')) {
            Schema::table('materias', function (Blueprint $table): void {
                $table->boolean('participa_en_calificacion_oficial')
                    ->default(true)
                    ->after('receso')
                    ->index();
            });
        }

        if (! Schema::hasTable('calificaciones_campos_formativos')) {
            Schema::create('calificaciones_campos_formativos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('trayectoria_academica_id')->nullable()->constrained('trayectorias_academicas')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('campo_formativo_id')->constrained('campos_formativos')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('grado_id')->constrained('grados')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('generacion_id')->constrained('generaciones')->restrictOnDelete()->cascadeOnUpdate();
                $table->decimal('calificacion_sugerida', 5, 2)->nullable();
                $table->unsignedTinyInteger('calificacion_oficial')->nullable();
                $table->boolean('confirmada')->default(false)->index();
                $table->boolean('es_reconstruida')->default(false)->index();
                $table->text('observaciones')->nullable();
                $table->foreignId('confirmada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('confirmada_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['periodo_id', 'inscripcion_id', 'campo_formativo_id'],
                    'calif_campo_unica_periodo_alumno'
                );
                $table->index(
                    ['ciclo_escolar_id', 'nivel_id', 'grado_id', 'grupo_id'],
                    'calif_campos_contexto_idx'
                );
            });
        }

        if (! Schema::hasTable('decisiones_promocion_oficial')) {
            Schema::create('decisiones_promocion_oficial', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('trayectoria_academica_id')->nullable()->constrained('trayectorias_academicas')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('grado_id')->constrained('grados')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('generacion_id')->constrained('generaciones')->restrictOnDelete()->cascadeOnUpdate();
                $table->decimal('promedio_final', 5, 2)->nullable();
                $table->boolean('promocion_sugerida')->nullable();
                $table->boolean('promocion_confirmada')->nullable();
                $table->text('motivo')->nullable();
                $table->foreignId('confirmada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('confirmada_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['inscripcion_id', 'ciclo_escolar_id', 'grado_id'],
                    'decision_promocion_alumno_ciclo_grado'
                );
            });
        }

        if (Schema::hasColumn('materias', 'participa_en_calificacion_oficial')) {
            DB::table('materias')->update([
                'participa_en_calificacion_oficial' => DB::raw(
                    "CASE WHEN calificable = 1 AND extra = 0 AND receso = 0 THEN 1 ELSE 0 END"
                ),
            ]);

            DB::table('materias')
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(materia) LIKE ?', ['%tutor%'])
                        ->orWhereRaw('LOWER(materia) LIKE ?', ['%socioemocional%'])
                        ->orWhereRaw('LOWER(materia) LIKE ?', ['%receso%']);
                })
                ->update(['participa_en_calificacion_oficial' => false]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('decisiones_promocion_oficial');
        Schema::dropIfExists('calificaciones_campos_formativos');

        if (Schema::hasColumn('materias', 'participa_en_calificacion_oficial')) {
            Schema::table('materias', function (Blueprint $table): void {
                $table->dropColumn('participa_en_calificacion_oficial');
            });
        }
    }
};
