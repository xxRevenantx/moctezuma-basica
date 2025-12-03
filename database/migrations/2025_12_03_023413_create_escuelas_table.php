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
        Schema::create('escuela', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('calle');
            $table->string('no_exterior')->nullable();
            $table->string('no_interior')->nullable();
            $table->string('colonia');
            $table->string('codigo_postal', 5);
            $table->string('ciudad');
            $table->string('municipio');
            $table->string('estado');
            $table->string('telefono', 10);
            $table->string('correo')->nullable();
            $table->string('pagina_web')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escuelas');
    }
};
