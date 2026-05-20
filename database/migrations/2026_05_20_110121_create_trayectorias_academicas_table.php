<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuto la migración.
     */
    public function up(): void
    {
        Schema::create('trayectorias_academicas', function (Blueprint $table) {
            $table->id();

            // Alumno relacionado a la inscripción principal.
            $table->foreignId('inscripcion_id')
                ->constrained('inscripciones')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Ciclo escolar real, ejemplo: 2025-2026.
            $table->foreignId('ciclo_escolar_id')
                ->constrained('ciclo_escolares')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Periodo de inscripción dentro del ciclo escolar:
            // Inicio de ciclo, medio ciclo o fin de ciclo.
            $table->foreignId('ciclo_id')
                ->constrained('ciclos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Datos académicos del alumno en ese ciclo escolar.
            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('grado_id')
                ->constrained('grados')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('generacion_id')
                ->constrained('generaciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Solo aplica para bachillerato.
            $table->foreignId('semestre_id')
                ->nullable()
                ->constrained('semestres')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Estado del alumno dentro de ese ciclo escolar.
            $table->boolean('activo')->default(true);

            // Datos de baja dentro de ese ciclo escolar.
            $table->dateTime('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->string('observaciones_baja')->nullable();

            // Fecha en que se registró al alumno en ese ciclo escolar.
            $table->dateTime('fecha_inscripcion')->nullable();



            $table->timestamps();
        });
    }

    /**
     * Revierto la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('trayectorias_academicas');
    }
};
