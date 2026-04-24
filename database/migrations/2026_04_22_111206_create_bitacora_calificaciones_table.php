<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bitacora_calificaciones', function (Blueprint $table) {
            $table->id();

            // Usuario que hizo el cambio
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Contexto principal del registro
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('asignacion_materia_id')->constrained('asignacion_materias')->cascadeOnDelete();

            // Contexto escolar
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete();
            $table->foreignId('semestre_id')->nullable()->constrained('semestres')->nullOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();

            // Valores del cambio
            $table->string('calificacion_anterior', 10)->nullable();
            $table->string('calificacion_nueva', 10)->nullable();

            // Tipo de movimiento
            $table->enum('accion', ['crear', 'editar', 'eliminar']);

            // Opcional: observación breve
            $table->string('comentario')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_calificaciones');
    }
};
