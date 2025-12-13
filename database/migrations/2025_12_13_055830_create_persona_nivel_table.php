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
        Schema::create('persona_nivel', function (Blueprint $table) {
             $table->id();

            $table->foreignId('persona_id')
                ->constrained('personas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Rol que cumple en ese nivel: docente, director, intendente, etc.
            $table->foreignId('role_persona_id')
                ->constrained('role_personas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Control / historial
            $table->date('ingreso_seg')->nullable();
            $table->date('ingreso_sep')->nullable();

            $table->timestamps();

            // Evita duplicados (misma persona, mismo nivel, mismo rol)
            $table->unique(
                ['persona_id', 'nivel_id', 'role_persona_id'],
                'persona_nivel_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona_nivel');
    }
};
