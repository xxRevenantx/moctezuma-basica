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
        Schema::create('persona_nivel_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('persona_nivel_id')->constrained('persona_nivel')->cascadeOnDelete();
            $table->foreignId('persona_role_id')->constrained('persona_role')->cascadeOnDelete();

            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();

            $table->integer('orden')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona_nivel_detalles');
    }
};
