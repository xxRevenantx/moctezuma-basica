<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // ✅ Si siempre es obligatorio en tu UI, quítale nullable
            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // ✅ Si siempre es obligatorio en tu UI, quítale nullable
            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->date('ingreso_seg')->nullable();
            $table->date('ingreso_sep')->nullable();

            // ✅ default y unsigned (opcional), pero recomendado
            $table->unsignedInteger('orden')->default(1);

            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_nivel');
    }
};
