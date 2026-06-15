<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ficha_descriptivas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();
            $table->unsignedTinyInteger('periodo');
            $table->string('campo', 255);
            $table->text('descripcion')->nullable();
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_captura')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_descriptivas');
    }
};
