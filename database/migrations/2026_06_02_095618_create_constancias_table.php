<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla para guardar el historial de constancias generadas.
     */
    public function up(): void
    {
        Schema::create('constancias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscripcion_id')
                ->constrained('inscripciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('constancia_plantilla_id')
                ->constrained('constancia_plantillas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('folio')->unique();

            $table->date('fecha_expedicion');
            $table->string('dirigido_a')->nullable();

            $table->string('modo_descarga')->default('alumno');

            $table->json('periodos_calificaciones')->nullable();

            $table->longText('contenido_generado_html');

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de constancias.
     */
    public function down(): void
    {
        Schema::dropIfExists('constancias');
    }
};
