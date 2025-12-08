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
        Schema::create('periodos_basicos', function (Blueprint $table) {
            $table->id();

            // Ciclo escolar al que aplican estos periodos (2025–2026, etc.)
            $table->foreignId('ciclo_escolar_id')
                ->constrained('ciclo_escolares')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Periodo 1, 2, 3... (catálogo periodos)
            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Fechas para evaluaciones parciales
            $table->date('parcial_inicio')->nullable();
            $table->date('parcial_fin')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_basicos');
    }
};
