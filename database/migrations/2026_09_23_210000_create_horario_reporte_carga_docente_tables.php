<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_reporte_configuraciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->string('escuela')->nullable();
            $table->string('cct', 40)->nullable();
            $table->string('zona_escolar', 80)->nullable();
            $table->string('turno', 80)->nullable();
            $table->string('director')->nullable();
            $table->string('supervisor')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['nivel_id', 'ciclo_escolar_id'], 'hrep_cfg_contexto_unico');
        });

        Schema::create('horario_reporte_docente_datos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('nombramiento')->nullable();
            $table->string('clave_presupuestal')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['nivel_id', 'ciclo_escolar_id', 'persona_id'],
                'hrep_doc_contexto_unico'
            );
            $table->index(['ciclo_escolar_id', 'nivel_id'], 'hrep_doc_contexto_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_reporte_docente_datos');
        Schema::dropIfExists('horario_reporte_configuraciones');
    }
};
