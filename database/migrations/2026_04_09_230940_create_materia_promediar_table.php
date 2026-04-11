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
        Schema::create('materia_promediar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nivel_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('grupo_id');
            $table->unsignedBigInteger('semestre_id')->nullable();
            $table->integer('numero_materias');


            $table->foreign('nivel_id')->references('id')->on('niveles')->onDelete('cascade');
            $table->foreign('grado_id')->references('id')->on('grados')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('cascade');
            $table->foreign('semestre_id')->references('id')->on('semestres')->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materia_promediar');
    }
};
