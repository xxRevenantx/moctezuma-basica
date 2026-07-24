<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizaciones_documentos_alumnos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('estado', 30)->default('borrador');
            $table->json('asignaciones');
            $table->json('fuentes_ids')->nullable();
            $table->json('retiros_confirmados')->nullable();
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmado_at')->nullable();
            $table->text('error')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();

            $table->unique(['inscripcion_id', 'version'], 'org_docs_alumno_version_unique');
            $table->index(['inscripcion_id', 'estado'], 'org_docs_alumno_estado_idx');
        });

        Schema::table('documentos_alumnos', function (Blueprint $table): void {
            $table->foreignId('organizacion_id')->nullable()->after('inscripcion_id')
                ->constrained('organizaciones_documentos_alumnos')->nullOnDelete();
            $table->boolean('es_fuente')->default(false)->after('es_actual');
            $table->boolean('es_organizado')->default(false)->after('es_fuente');
            $table->unsignedInteger('paginas_total')->default(1)->after('tamano_bytes');

            $table->index(['inscripcion_id', 'es_fuente', 'es_actual'], 'docs_alumno_fuente_actual_idx');
        });

        Schema::create('documentos_alumnos_fuentes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('documento_alumno_id')->nullable()->constrained('documentos_alumnos')->nullOnDelete();
            $table->string('disco', 50)->default('local');
            $table->string('ruta', 500);
            $table->string('ruta_original', 500)->nullable();
            $table->string('nombre_original', 255);
            $table->string('nombre_almacenado', 255)->nullable();
            $table->string('mime_type', 120)->default('application/pdf');
            $table->string('mime_original', 120)->default('application/pdf');
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->char('hash_sha256', 64)->nullable();
            $table->unsignedInteger('paginas')->default(1);
            $table->string('estado', 30)->default('activo');
            $table->boolean('protegido')->default(false);
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadatos')->nullable();
            $table->timestamps();

            $table->index(['inscripcion_id', 'estado'], 'docs_fuentes_alumno_estado_idx');
            $table->index(['inscripcion_id', 'hash_sha256'], 'docs_fuentes_alumno_hash_idx');
            $table->unique('documento_alumno_id', 'docs_fuentes_documento_unique');
        });

        Schema::create('documentos_alumnos_no_aplica', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documentos')->restrictOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();
            $table->text('motivo');
            $table->boolean('activo')->default(true);
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['inscripcion_id', 'tipo_documento_id', 'activo'], 'docs_no_aplica_contexto_idx');
        });

        // Los documentos existentes quedan disponibles como archivos fuente sin
        // alterar su estado ni su historial. El conteo real de páginas se corrige
        // de forma diferida al abrir el organizador o mediante el comando incluido.
        DB::table('documentos_alumnos')
            ->select([
                'id', 'inscripcion_id', 'disco', 'ruta', 'nombre_original', 'mime_type',
                'tamano_bytes', 'hash_sha256', 'subido_por', 'origen', 'tipo_documento_id',
                'nivel_id', 'grado_id', 'grupo_id', 'ciclo_escolar_id', 'version', 'estado',
                'created_at', 'updated_at',
            ])
            ->orderBy('id')
            ->chunkById(250, function ($documentos): void {
                $filas = [];

                foreach ($documentos as $documento) {
                    $filas[] = [
                        'inscripcion_id' => $documento->inscripcion_id,
                        'documento_alumno_id' => $documento->id,
                        'disco' => $documento->disco ?: 'local',
                        'ruta' => (string) $documento->ruta,
                        'ruta_original' => (string) $documento->ruta,
                        'nombre_original' => (string) $documento->nombre_original,
                        'nombre_almacenado' => basename((string) $documento->ruta),
                        'mime_type' => (string) ($documento->mime_type ?: 'application/octet-stream'),
                        'mime_original' => (string) ($documento->mime_type ?: 'application/octet-stream'),
                        'tamano_bytes' => (int) $documento->tamano_bytes,
                        'hash_sha256' => $documento->hash_sha256,
                        'paginas' => 1,
                        'estado' => filled($documento->ruta) ? 'activo' : 'inconsistente',
                        'protegido' => $documento->origen === 'generado',
                        'subido_por' => $documento->subido_por,
                        'metadatos' => json_encode([
                            'migrado' => true,
                            'conteo_paginas_pendiente' => true,
                            'contexto' => [
                                'tipo_documento_id' => $documento->tipo_documento_id,
                                'nivel_id' => $documento->nivel_id,
                                'grado_id' => $documento->grado_id,
                                'grupo_id' => $documento->grupo_id,
                                'ciclo_escolar_id' => $documento->ciclo_escolar_id,
                            ],
                            'version_documento' => $documento->version,
                            'estado_documento' => $documento->estado,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'created_at' => $documento->created_at ?? now(),
                        'updated_at' => $documento->updated_at ?? now(),
                    ];
                }

                if ($filas !== []) {
                    DB::table('documentos_alumnos_fuentes')->insertOrIgnore($filas);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_alumnos_no_aplica');
        Schema::dropIfExists('documentos_alumnos_fuentes');

        Schema::table('documentos_alumnos', function (Blueprint $table): void {
            $table->dropForeign(['organizacion_id']);
            $table->dropIndex('docs_alumno_fuente_actual_idx');
            $table->dropColumn(['organizacion_id', 'es_fuente', 'es_organizado', 'paginas_total']);
        });

        Schema::dropIfExists('organizaciones_documentos_alumnos');
    }
};
