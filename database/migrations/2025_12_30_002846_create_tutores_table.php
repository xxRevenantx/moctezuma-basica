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
        Schema::create('tutores', function (Blueprint $table) {
            $table->id();

            $table->string('curp', 18)->unique();
            // GENERALES
            $table->string('parentesco', 50);
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->enum('genero', ['M', 'F', 'O'])->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('ciudad_nacimiento')->nullable();
            $table->string('estado_nacimiento')->nullable();
            $table->string('municipio_nacimiento')->nullable();

            // DOMICILIO Y CONTACTO
            $table->string('calle')->nullable();
            $table->string('colonia')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('telefono_casa', 20)->nullable();
            $table->string('telefono_celular', 20)->nullable();
            $table->string('correo_electronico')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutores');
    }
};
