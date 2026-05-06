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
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();

            $table->foreignId('semestre_id')
                ->nullable()
                ->constrained('semestres')
                ->nullOnDelete();

            $table->unsignedInteger('numero_materias');

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
