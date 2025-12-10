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
        Schema::create('periodos_bachilleratos', function (Blueprint $table) {
              $table->id();

            // Generación de bachillerato (2021–2024, etc.)
            $table->foreignId('generacion_id')
                ->constrained('generaciones')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Semestre (1..6). Puede ser null si quieres manejar periodos sólo por generación/mes.
            $table->foreignId('semestre_id')
                ->nullable()
                ->constrained('semestres')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Ciclo escolar al que pertenece el periodo
            $table->foreignId('ciclo_escolar_id')
                ->constrained('ciclo_escolares')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Meses de Bachillerato
            $table->foreignId('mes_id')
                ->constrained('meses_bachilleratos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Fechas del periodo
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_bachilleratos');
    }
};
