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
        Schema::create('persona_role', function (Blueprint $table) {
               $table->id();

            $table->foreignId('persona_id')
                ->constrained('personas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('role_persona_id')
                ->constrained('role_personas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Evita asignar el mismo rol 2 veces a la misma persona
            $table->unique(['persona_id', 'role_persona_id'], 'persona_role_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona_role');
    }
};
