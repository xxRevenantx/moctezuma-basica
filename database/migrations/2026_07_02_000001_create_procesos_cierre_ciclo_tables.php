<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesos_cierre_ciclo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 40)->default('egreso_generacion');
            $table->string('estado', 30)->default('completado');
            $table->date('fecha_egreso')->nullable();
            $table->text('motivo');
            $table->unsignedInteger('total_evaluados')->default(0);
            $table->unsignedInteger('total_procesados')->default(0);
            $table->unsignedInteger('total_excluidos')->default(0);
            $table->boolean('generacion_cerrada')->default(false);
            $table->boolean('ciclo_cerrado')->default(false);
            $table->json('resumen')->nullable();
            $table->foreignId('realizado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('realizado_at')->nullable();
            $table->timestamp('revertido_at')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Schema::create('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proceso_cierre_ciclo_id')->constrained('procesos_cierre_ciclo')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('resultado', 30);
            $table->text('observacion')->nullable();
            $table->json('estado_anterior')->nullable();
            $table->json('estado_nuevo')->nullable();
            $table->timestamps();
            $table->unique(['proceso_cierre_ciclo_id', 'inscripcion_id'], 'proceso_cierre_alumno_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procesos_cierre_ciclo_detalles');
        Schema::dropIfExists('procesos_cierre_ciclo');
    }
};
