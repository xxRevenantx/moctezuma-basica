<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reasignacion_docente_lotes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('profesor_origen_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('profesor_destino_id')->constrained('personas')->restrictOnDelete();
            $table->string('modo', 24)->default('seleccion');
            $table->string('estado', 24)->default('aplicada');
            $table->unsignedInteger('total_asignaciones')->default(0);
            $table->unsignedInteger('total_horarios')->default(0);
            $table->unsignedInteger('total_versiones')->default(0);
            $table->unsignedInteger('total_conflictos')->default(0);
            $table->boolean('conflictos_autorizados')->default(false);
            $table->text('motivo_autorizacion_conflictos')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('aplicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aplicado_at')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revertido_at')->nullable();
            $table->timestamps();

            $table->index(['ciclo_escolar_id', 'nivel_id', 'estado'], 'reasig_lote_contexto_idx');
            $table->index(['profesor_origen_id', 'profesor_destino_id'], 'reasig_lote_docentes_idx');
        });

        Schema::create('reasignacion_docente_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reasignacion_docente_lote_id');
            $table->foreign('reasignacion_docente_lote_id', 'reasig_det_lote_fk')
                ->references('id')
                ->on('reasignacion_docente_lotes')
                ->cascadeOnDelete();
            $table->foreignId('asignacion_materia_id')->nullable()->constrained('asignacion_materias')->nullOnDelete();
            $table->foreignId('profesor_anterior_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('profesor_nuevo_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('materia_id')->nullable()->constrained('materias')->nullOnDelete();
            $table->string('estado_asignacion', 24)->nullable();
            $table->string('resultado', 24)->default('aplicada');
            $table->text('motivo_omision')->nullable();
            $table->json('contexto_snapshot')->nullable();
            $table->json('horarios_snapshot')->nullable();
            $table->json('versiones_snapshot')->nullable();
            $table->timestamp('aplicado_at')->nullable();
            $table->timestamp('revertido_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['reasignacion_docente_lote_id', 'asignacion_materia_id'],
                'reasig_det_lote_asignacion_unica'
            );
            $table->index(['asignacion_materia_id', 'resultado'], 'reasig_det_asignacion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reasignacion_docente_detalles');
        Schema::dropIfExists('reasignacion_docente_lotes');
    }
};
