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
        Schema::create('semestres', function (Blueprint $table) {
            // Grado al que pertenece el semestre (en tu caso, grados de Bachillerato)
           $table->id();
            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // o ->cascadeOnDelete() si quieres borrado en cascada

            $table->foreignId('mes_id')
                ->constrained('meses_bachilleratos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Número de semestre dentro del grado (1 o 2)
            $table->unsignedTinyInteger('numero');
            // Ej: 1 = primer semestre del grado, 2 = segundo semestre del grado

            // Orden global (opcional, útil para Bachillerato: 1..6)
            $table->unsignedTinyInteger('orden_global')
                ->nullable();
            // Ej: 1,2 para 1°; 3,4 para 2°; 5,6 para 3°




            $table->timestamps();
            $table->softDeletes();

            // Evitar duplicados de mismo semestre en el mismo grado
            $table->unique(['grado_id', 'numero'], 'semestres_grado_numero_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semestres');
    }
};
