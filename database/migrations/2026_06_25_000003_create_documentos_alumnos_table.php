<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_alumnos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscripcion_id')
                ->constrained('inscripciones')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('tipo_documento_id')
                ->constrained('tipos_documentos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // En certificados representa el nivel que acredita el documento.
            $table->foreignId('nivel_id')
                ->nullable()
                ->constrained('niveles')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Es opcional porque un alumno puede ingresar desde otra institución.
            $table->foreignId('trayectoria_academica_id')
                ->nullable()
                ->constrained('trayectorias_academicas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('disco', 50)->default('local');
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime_type', 120)->default('application/pdf');
            $table->unsignedBigInteger('tamano_bytes');
            $table->char('hash_sha256', 64)->nullable();

            $table->unsignedInteger('version')->default(1);
            $table->boolean('es_actual')->default(true);
            $table->string('estado', 30)->default('recibido');
            $table->text('observaciones')->nullable();

            $table->foreignId('subido_por')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('validado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('validado_at')->nullable();
            $table->timestamps();

            $table->index(['inscripcion_id', 'es_actual'], 'documentos_alumno_actual_idx');
            $table->index(['tipo_documento_id', 'nivel_id'], 'documentos_tipo_nivel_idx');
            $table->index(['estado', 'es_actual'], 'documentos_estado_actual_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_alumnos');
    }
};
