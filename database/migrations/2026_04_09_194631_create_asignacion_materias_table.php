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
        Schema::create('asignacion_materias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nivel_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('semestre')->nullable();
            $table->unsignedBigInteger('profesor_id')->nullable();
            $table->string('materia');
            $table->string('clave')->nullable();
            $table->string('slug');
            $table->boolean('calificable')->default(true);
            $table->integer('orden')->default(0);

            $table->foreign('nivel_id')->references('id')->on('niveles')->onDelete('cascade');
            $table->foreign('grado_id')->references('id')->on('grados')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
            $table->foreign('semestre')->references('id')->on('semestres')->onDelete('set null');
            $table->foreign('profesor_id')->references('id')->on('personas')->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_materias');
    }
};
