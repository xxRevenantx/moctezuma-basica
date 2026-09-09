<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesor_documento_plantillas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->string('tipo_documento', 50)->default('portada');
            $table->string('nombre', 120);
            $table->unsignedInteger('version')->default(1);
            $table->string('archivo_path');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->boolean('activo')->default(false);
            $table->json('configuracion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nivel_id', 'tipo_documento', 'activo'], 'prof_doc_plantillas_lookup_idx');
            $table->unique(['nivel_id', 'tipo_documento', 'version'], 'prof_doc_plantillas_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesor_documento_plantillas');
    }
};
