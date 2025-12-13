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
        Schema::create('docente_grupos', function (Blueprint $table) {
               $table->id();

            // Docente (persona). Ojo: aquí asignas SOLO personas que tengan rol "docente"
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Grupo (ya contiene nivel, grado, generación y semestre si aplica)
            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Ciclo escolar (para mantener historial por año)
            $table->foreignId('ciclo_escolar_id')
                ->constrained('ciclo_escolares')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Tutor del grupo (opcional)
            $table->boolean('es_tutor')->default(false);

            $table->timestamps();

            // Evitar duplicar al mismo docente en el mismo grupo del mismo ciclo
            $table->unique(
                ['persona_id', 'grupo_id', 'ciclo_escolar_id'],
                'docente_grupo_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docente_grupos');
    }
};
