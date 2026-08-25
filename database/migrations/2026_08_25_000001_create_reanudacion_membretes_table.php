<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reanudacion_membretes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('nombre_original');
            $table->string('archivo_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->decimal('margen_superior_mm', 5, 2)->default(32);
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['ciclo_escolar_id', 'nivel_id', 'version'], 'reanudacion_membrete_version_unique');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'activo'], 'reanudacion_membrete_activo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reanudacion_membretes');
    }
};
