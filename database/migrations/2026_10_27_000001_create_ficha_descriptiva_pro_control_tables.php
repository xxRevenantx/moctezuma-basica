<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ficha_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->unsignedTinyInteger('periodo');
            $table->string('estado', 24)->default('borrador');
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();
            $table->unique(['inscripcion_id', 'ciclo_escolar_id', 'periodo'], 'ficha_estado_unique');
        });

        Schema::create('ficha_historiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->unsignedTinyInteger('periodo');
            $table->string('campo', 50)->nullable();
            $table->string('accion', 35);
            $table->longText('valor_anterior')->nullable();
            $table->longText('valor_nuevo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inscripcion_id', 'ciclo_escolar_id', 'periodo'], 'ficha_historial_contexto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_historiales');
        Schema::dropIfExists('ficha_estados');
    }
};
