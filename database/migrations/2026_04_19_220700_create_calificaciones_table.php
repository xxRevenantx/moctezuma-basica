<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            $table->unsignedBigInteger('asignacion_materia_id');

            $table->unsignedBigInteger('nivel_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('grupo_id');

            $table->unsignedBigInteger('ciclo_escolar_id');
            $table->unsignedBigInteger('generacion_id');
            $table->unsignedBigInteger('semestre_id')->nullable();

            $table->unsignedBigInteger('periodo_id')->nullable();


            $table->string('calificacion', 2)->nullable();

            $table->foreign('inscripcion_id')->references('id')->on('inscripciones')->onDelete('cascade');
            $table->foreign('asignacion_materia_id')->references('id')->on('asignacion_materias')->onDelete('cascade');
            $table->foreign('nivel_id')->references('id')->on('niveles')->onDelete('cascade');
            $table->foreign('grado_id')->references('id')->on('grados')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
            $table->foreign('ciclo_escolar_id')->references('id')->on('ciclo_escolares')->onDelete('cascade');
            $table->foreign('generacion_id')->references('id')->on('generaciones')->onDelete('cascade');
            $table->foreign('semestre_id')->references('id')->on('semestres')->onDelete('cascade');
            $table->foreign('periodo_id')->references('id')->on('periodos')->onDelete('cascade');




            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
