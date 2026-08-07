<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_eventos', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo', 160);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 40)->default('academico');
            $table->string('estado', 30)->default('programado');
            $table->string('prioridad', 20)->default('normal');
            $table->string('audiencia', 30)->default('todos');
            $table->dateTime('inicia_at');
            $table->dateTime('termina_at')->nullable();
            $table->boolean('todo_el_dia')->default(true);
            $table->string('ubicacion', 190)->nullable();
            $table->string('enlace', 500)->nullable();
            $table->string('recurrencia', 20)->default('ninguna');
            $table->date('recurrencia_hasta')->nullable();
            $table->unsignedSmallInteger('recordatorio_dias')->default(0);
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['inicia_at', 'termina_at'], 'cal_eventos_rango_idx');
            $table->index(['estado', 'prioridad', 'tipo'], 'cal_eventos_estado_idx');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'grupo_id'], 'cal_eventos_estructura_idx');
            $table->index(['audiencia', 'recurrencia'], 'cal_eventos_visibilidad_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_eventos');
    }
};
