<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integridad_academica_analisis')) {
            Schema::create('integridad_academica_analisis', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('origen', 30)->default('manual')->index();
                $table->string('estado', 20)->default('procesando')->index();
                $table->json('filtros')->nullable();
                $table->json('resumen')->nullable();
                $table->unsignedInteger('total_detectados')->default(0);
                $table->unsignedInteger('nuevos')->default(0);
                $table->unsignedInteger('actualizados')->default(0);
                $table->unsignedInteger('reabiertos')->default(0);
                $table->unsignedInteger('resueltos_automaticamente')->default(0);
                $table->foreignId('ejecutado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('iniciado_at')->nullable();
                $table->timestamp('finalizado_at')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
                $table->index(['estado', 'iniciado_at'], 'ia_analisis_estado_inicio_idx');
            });
        }

        if (! Schema::hasTable('integridad_academica_casos')) {
            Schema::create('integridad_academica_casos', function (Blueprint $table): void {
                $table->id();
                $table->string('folio', 40)->unique();
                $table->string('fingerprint', 64)->unique();
                $table->string('regla', 100)->index();
                $table->string('categoria', 50)->default('historial')->index();
                $table->string('severidad', 20)->default('advertencia')->index();
                $table->string('estado', 25)->default('pendiente')->index();
                $table->foreignId('inscripcion_id')->nullable()->constrained('inscripciones')->nullOnDelete();
                $table->foreignId('inscripcion_ciclo_id')->nullable()->constrained('inscripcion_ciclos')->nullOnDelete();
                $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();
                $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
                $table->foreignId('ultimo_analisis_id')->nullable()->constrained('integridad_academica_analisis')->nullOnDelete();
                $table->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete();
                $table->string('titulo');
                $table->text('descripcion');
                $table->json('evidencia')->nullable();
                $table->json('correccion_sugerida')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedInteger('ocurrencias')->default(1);
                $table->timestamp('primera_deteccion_at')->nullable();
                $table->timestamp('ultima_deteccion_at')->nullable();
                $table->timestamp('revision_iniciada_at')->nullable();
                $table->foreignId('revision_iniciada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resuelto_at')->nullable();
                $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
                $table->text('motivo_resolucion')->nullable();
                $table->timestamp('ignorado_at')->nullable();
                $table->foreignId('ignorado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->text('motivo_ignorado')->nullable();
                $table->timestamps();
                $table->index(['estado', 'severidad', 'ultima_deteccion_at'], 'ia_casos_bandeja_idx');
                $table->index(['inscripcion_id', 'estado'], 'ia_casos_alumno_estado_idx');
                $table->index(['categoria', 'regla'], 'ia_casos_categoria_regla_idx');
            });
        }

        if (! Schema::hasTable('integridad_academica_eventos')) {
            Schema::create('integridad_academica_eventos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('caso_id')->constrained('integridad_academica_casos')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipo', 40)->index();
                $table->text('descripcion')->nullable();
                $table->json('valores_anteriores')->nullable();
                $table->json('valores_nuevos')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['caso_id', 'created_at'], 'ia_eventos_caso_fecha_idx');
            });
        }

        if (! Schema::hasTable('integridad_academica_correcciones')) {
            Schema::create('integridad_academica_correcciones', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('caso_id')->constrained('integridad_academica_casos')->cascadeOnDelete();
                $table->string('clave', 100)->index();
                $table->string('estado', 20)->default('aplicada')->index();
                $table->text('motivo');
                $table->json('parametros');
                $table->json('respaldo_anterior');
                $table->json('resultado_aplicado');
                $table->string('firma', 64);
                $table->foreignId('aplicada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('aplicada_at')->nullable();
                $table->foreignId('revertida_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revertida_at')->nullable();
                $table->text('motivo_reversion')->nullable();
                $table->text('bloqueo_reversion')->nullable();
                $table->timestamps();
                $table->index(['caso_id', 'estado'], 'ia_correcciones_caso_estado_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integridad_academica_correcciones');
        Schema::dropIfExists('integridad_academica_eventos');
        Schema::dropIfExists('integridad_academica_casos');
        Schema::dropIfExists('integridad_academica_analisis');
    }
};
