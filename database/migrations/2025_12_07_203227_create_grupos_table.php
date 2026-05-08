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
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();

            // ASIGNACIÓN DE GRUPO: Relación con la tabla asignacion_grupos
            $table->foreignId('asignacion_grupo_id')
                ->constrained('asignacion_grupos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // NIVEL: Preescolar, Primaria, Secundaria, Bachillerato
            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // GRADO: 1°, 2°, 3°, etc. (según el nivel)
            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // GENERACIÓN: 2021–2023, 2021–2027, etc.
            $table->foreignId('generacion_id')
                ->constrained('generaciones')
                ->cascadeOnUpdate()
                ->restrictOnDelete();



            // SEMESTRE: sólo aplica para Bachillerato (para básico será NULL)
            $table->foreignId('semestre_id')
                ->nullable()
                ->constrained('semestres')
                ->cascadeOnUpdate()
                ->restrictOnDelete();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
