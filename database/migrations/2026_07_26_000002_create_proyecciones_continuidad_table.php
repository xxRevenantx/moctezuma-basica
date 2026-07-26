<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecciones_continuidad', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_ciclo_origen_id')->constrained('inscripcion_ciclos')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('proceso_cierre_ciclo_id')->nullable()->constrained('procesos_cierre_ciclo')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('proceso_cierre_ciclo_detalle_id')->nullable()->constrained('procesos_cierre_ciclo_detalles')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignId('ciclo_destino_id')->constrained('ciclo_escolares')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('nivel_destino_id')->constrained('niveles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('generacion_destino_id')->constrained('generaciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('grado_destino_id')->constrained('grados')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('semestre_destino_id')->nullable()->constrained('semestres')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('grupo_destino_id')->nullable()->constrained('grupos')->nullOnDelete()->cascadeOnUpdate();

            $table->string('matricula_sugerida', 80)->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->date('fecha_proyeccion');
            $table->text('motivo');
            $table->foreignId('proyectada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->json('snapshot_origen')->nullable();

            $table->timestamp('confirmada_at')->nullable();
            $table->foreignId('confirmada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_ciclo_destino_id')->nullable()->constrained('inscripcion_ciclos')->nullOnDelete()->cascadeOnUpdate();
            $table->json('snapshot_confirmacion')->nullable();

            $table->timestamp('cancelada_at')->nullable();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->text('motivo_cancelacion')->nullable();
            $table->json('snapshot_cancelacion')->nullable();
            $table->timestamps();

            $table->unique(
                ['inscripcion_id', 'ciclo_destino_id', 'nivel_destino_id'],
                'proyeccion_alumno_ciclo_nivel_unica'
            );
            $table->index(['nivel_destino_id', 'ciclo_destino_id', 'estado'], 'proyecciones_destino_estado_idx');
            $table->index(['inscripcion_ciclo_origen_id', 'estado'], 'proyecciones_origen_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecciones_continuidad');
    }
};
