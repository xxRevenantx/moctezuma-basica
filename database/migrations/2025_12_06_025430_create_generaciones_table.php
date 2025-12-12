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
        Schema::create('generaciones', function (Blueprint $table) {
            $table->id();
            // Nivel al que pertenece la generación (Preescolar, Primaria, Secundaria, Bachillerato)
            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // cámbialo a ->cascadeOnDelete() si quieres borrado en cascada


            // Años escolares de entrada y salida del nivel
            $table->year('anio_ingreso'); // ej. 2021
            $table->year('anio_egreso');  // ej. 2027


            // Estado de la generación
            $table->boolean('status')->default(true);

            // Comentarios generales
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Evitar duplicados de misma generación en el mismo nivel
            $table->unique(
                ['nivel_id', 'anio_ingreso', 'anio_egreso'],
                'generaciones_nivel_anios_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generaciones');
    }
};
