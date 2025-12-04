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
        Schema::create('directores', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno');
            $table->string('curp', 18)->unique()->nullable();
            $table->string('rfc')->unique()->nullable();
            $table->string('cargo');
            $table->string('identificador')->unique();
            $table->string('zona_escolar')->nullable();
            $table->string('telefono', 10)->nullable();
            $table->string('correo')->nullable();
            $table->boolean('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directors');
    }
};
