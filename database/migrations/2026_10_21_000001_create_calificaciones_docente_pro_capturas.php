<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calificaciones_docente_capturas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asignacion_materia_id')->constrained('asignacion_materias')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $table->string('estado', 25)->default('borrador');
            $table->json('valores')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();
            $table->unique(['asignacion_materia_id', 'periodo_id'], 'cal_docente_asignacion_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_docente_capturas');
    }
};
