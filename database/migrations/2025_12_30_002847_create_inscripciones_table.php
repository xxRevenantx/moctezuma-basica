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
    Schema::create('inscripciones', function (Blueprint $table) {
        $table->id();

        /* =========================
         * IDENTIFICACIÓN ESCOLAR
         * ========================= */
        $table->string('curp', 18)->index();
        $table->string('matricula', 50)->unique();
        $table->string('folio', 50)->nullable()->index();

        /* =========================
         * DATOS PERSONALES
         * ========================= */
        $table->string('nombre');
        $table->string('apellido_paterno');
        $table->string('apellido_materno')->nullable();

        $table->date('fecha_nacimiento');
        $table->enum('genero', ['H', 'M']);

        /* =========================
         * DATOS DE NACIMIENTO
         * ========================= */
        $table->string('pais_nacimiento')->nullable();
        $table->string('estado_nacimiento')->nullable();
        $table->string('lugar_nacimiento')->nullable();

        /* =========================
         * DOMICILIO
         * ========================= */
        $table->string('calle')->nullable();
        $table->string('numero_exterior', 20)->nullable();
        $table->string('numero_interior', 20)->nullable();
        $table->string('colonia')->nullable();
        $table->string('codigo_postal', 10)->nullable();
        $table->string('municipio')->nullable();
        $table->string('estado_residencia')->nullable();
        $table->string('ciudad_residencia')->nullable();

        /* =========================
         * ASIGNACIÓN ACADÉMICA
         * ========================= */
        $table->foreignId('nivel_id')
            ->constrained('niveles')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->foreignId('grado_id')
            ->constrained('grados')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->foreignId('generacion_id')
            ->constrained('generaciones')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->foreignId('grupo_id')
            ->constrained('grupos')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->foreignId('semestre_id')
            ->nullable()
            ->constrained('semestres')
            ->cascadeOnUpdate()
            ->restrictOnDelete();

        $table->foreignId('ciclo_id')
            ->constrained('ciclos')
            ->cascadeOnUpdate()
            ->restrictOnDelete();


        /* =========================
         * FOTO
         * ========================= */
        $table->string('foto_path')->nullable();

        /* =========================
         * CONTROL
         * ========================= */
        $table->boolean('activo')->default(true);

        $table->dateTime('fecha_inscripcion');

        $table->timestamps();
        $table->softDeletes();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};
