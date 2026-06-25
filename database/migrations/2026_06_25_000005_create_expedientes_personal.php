<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->string('estado_laboral', 30)
                ->default('activo')
                ->after('status')
                ->index();
        });

        DB::table('personas')->where('status', false)->update(['estado_laboral' => 'baja']);
        DB::table('personas')->where('status', true)->update(['estado_laboral' => 'activo']);

        Schema::create('tipos_documentos_personal', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('categoria', 30)->default('personal');
            $table->text('descripcion')->nullable();
            $table->boolean('permite_varios')->default(false);
            $table->boolean('es_obligatorio')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['categoria', 'activo', 'orden'], 'tipos_documentos_personal_categoria_idx');
        });

        $ahora = now();

        DB::table('tipos_documentos_personal')->insert([
            [
                'nombre' => 'Identificación oficial',
                'slug' => 'identificacion-oficial',
                'categoria' => 'personal',
                'descripcion' => 'INE, pasaporte, cédula profesional u otra identificación oficial.',
                'permite_varios' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 1,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Comprobante de domicilio',
                'slug' => 'comprobante-domicilio',
                'categoria' => 'personal',
                'descripcion' => 'Comprobante de domicilio actual del trabajador.',
                'permite_varios' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 2,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'CURP',
                'slug' => 'curp',
                'categoria' => 'personal',
                'descripcion' => 'Documento oficial de la CURP.',
                'permite_varios' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 3,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Acta de nacimiento',
                'slug' => 'acta-nacimiento',
                'categoria' => 'personal',
                'descripcion' => 'Acta de nacimiento del trabajador.',
                'permite_varios' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 4,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Constancia de Situación Fiscal',
                'slug' => 'constancia-situacion-fiscal',
                'categoria' => 'personal',
                'descripcion' => 'Constancia de Situación Fiscal emitida por el SAT.',
                'permite_varios' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 5,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Título profesional',
                'slug' => 'titulo-profesional',
                'categoria' => 'academico',
                'descripcion' => 'Título profesional. Se permiten varios estudios.',
                'permite_varios' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 20,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Cédula profesional',
                'slug' => 'cedula-profesional',
                'categoria' => 'academico',
                'descripcion' => 'Cédula profesional. Se permiten varios registros.',
                'permite_varios' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 21,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);

        Schema::create('documentos_personal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('tipo_documento_personal_id')
                ->constrained('tipos_documentos_personal')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Agrupa las versiones de un mismo documento lógico.
            $table->uuid('serie_uuid');

            // Metadatos opcionales.
            $table->string('subtipo_identificacion', 50)->nullable();
            $table->string('nombre_estudio')->nullable();
            $table->string('institucion')->nullable();
            $table->string('nivel_academico', 50)->nullable();
            $table->string('numero_cedula', 100)->nullable();

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

            $table->index(['persona_id', 'es_actual'], 'documentos_personal_actual_idx');
            $table->index(['tipo_documento_personal_id', 'es_actual'], 'documentos_personal_tipo_idx');
            $table->index(['serie_uuid', 'version'], 'documentos_personal_serie_idx');
            $table->index(['estado', 'es_actual'], 'documentos_personal_estado_idx');
        });

        Schema::create('movimientos_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->string('tipo', 30);
            $table->date('fecha');
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['persona_id', 'fecha'], 'movimientos_personal_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_personal');
        Schema::dropIfExists('documentos_personal');
        Schema::dropIfExists('tipos_documentos_personal');

        Schema::table('personas', function (Blueprint $table) {
            $table->dropIndex(['estado_laboral']);
            $table->dropColumn('estado_laboral');
        });
    }
};
