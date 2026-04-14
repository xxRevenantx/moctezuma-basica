<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nivel_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('generacion_id');
            $table->unsignedBigInteger('semestre_id')->nullable();
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('hora_id');
            $table->unsignedBigInteger('dia_id');
            $table->unsignedBigInteger('asignacion_materia_id');


            $table->foreign('nivel_id')->references('id')->on('niveles');
            $table->foreign('grado_id')->references('id')->on('grados');
            $table->foreign('generacion_id')->references('id')->on('generaciones');
            $table->foreign('semestre_id')->references('id')->on('semestres');
            $table->foreign('grupo_id')->references('id')->on('grupos');
            $table->foreign('hora_id')->references('id')->on('horas');
            $table->foreign('dia_id')->references('id')->on('dias');
            $table->foreign('asignacion_materia_id')->references('id')->on('asignacion_materias');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
