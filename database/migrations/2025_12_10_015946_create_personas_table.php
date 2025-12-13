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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
                // Datos de identificación
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('foto')->nullable();

            $table->string('curp', 18)->nullable()->unique();
            $table->string('rfc', 13)->nullable()->unique();

            // Contacto
            $table->string('correo', 150)->nullable()->unique();
            $table->string('telefono_movil', 10)->nullable();
            $table->string('telefono_fijo', 10)->nullable();

            // Datos generales
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['H', 'M', 'otro'])->nullable();

            // Datos laborales
            $table->string('grado_estudios')->nullable();       // Licenciatura, Maestría...
            $table->string('especialidad')->nullable();        // Matemáticas, Español, etc.


            // Estado
            $table->boolean('status')->default(true);

            // Dirección opcional
            $table->string('calle')->nullable();
            $table->string('numero_exterior', 20)->nullable();
            $table->string('numero_interior', 20)->nullable();
            $table->string('colonia')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('codigo_postal', 10)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
