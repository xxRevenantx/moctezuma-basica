<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla para guardar plantillas de constancias.
     */
    public function up(): void
    {
        Schema::create('constancia_plantillas', function (Blueprint $table) {
            $table->id();

            $table->string('clave')->unique();
            $table->string('titulo');
            $table->longText('contenido_html');
            $table->json('variables')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de plantillas.
     */
    public function down(): void
    {
        Schema::dropIfExists('constancia_plantillas');
    }
};
