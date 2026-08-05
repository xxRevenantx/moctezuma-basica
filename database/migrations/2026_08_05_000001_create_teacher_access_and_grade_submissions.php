<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('activo')->index();
            }
            if (! Schema::hasColumn('users', 'temporary_password_issued_at')) {
                $table->timestamp('temporary_password_issued_at')->nullable()->after('must_change_password');
            }
        });

        if (! Schema::hasTable('calificacion_entregas')) {
            Schema::create('calificacion_entregas', function (Blueprint $table): void {
                $table->id();
                $table->string('folio', 50)->unique();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('persona_id')->constrained('personas')->restrictOnDelete();
                $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
                $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->restrictOnDelete();
                $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete();
                $table->foreignId('generacion_id')->constrained('generaciones')->restrictOnDelete();
                $table->foreignId('grado_id')->constrained('grados')->restrictOnDelete();
                $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete();
                $table->foreignId('semestre_id')->nullable()->constrained('semestres')->nullOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('estado', 25)->default('confirmada')->index();
                $table->string('docente_nombre');
                $table->string('docente_curp', 18);
                $table->string('correo_institucional');
                $table->text('declaracion');
                $table->string('ip_confirmacion', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('confirmada_at');
                $table->json('totales')->nullable();
                $table->string('snapshot_sha256', 64);
                $table->string('pdf_disk', 40)->nullable();
                $table->string('pdf_path')->nullable();
                $table->string('pdf_sha256', 64)->nullable();
                $table->foreignId('reabierta_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reabierta_at')->nullable();
                $table->text('motivo_reapertura')->nullable();
                $table->timestamps();

                $table->unique(
                    ['user_id', 'periodo_id', 'grupo_id', 'version'],
                    'cal_entrega_docente_contexto_version_unique'
                );
                $table->index(
                    ['user_id', 'periodo_id', 'grupo_id', 'estado'],
                    'cal_entrega_docente_contexto_estado_idx'
                );
                $table->index(
                    ['ciclo_escolar_id', 'nivel_id', 'generacion_id', 'grado_id', 'grupo_id'],
                    'cal_entrega_contexto_idx'
                );
            });
        }

        if (! Schema::hasTable('calificacion_entrega_detalles')) {
            Schema::create('calificacion_entrega_detalles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('calificacion_entrega_id')
                    ->constrained('calificacion_entregas')
                    ->cascadeOnDelete();
                $table->foreignId('calificacion_id')->nullable()->constrained('calificaciones')->nullOnDelete();
                $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete();
                $table->foreignId('asignacion_materia_id')->constrained('asignacion_materias')->restrictOnDelete();
                $table->string('matricula')->nullable();
                $table->string('alumno_nombre');
                $table->string('materia_nombre');
                $table->string('calificacion', 10)->nullable();
                $table->text('observacion')->nullable();
                $table->boolean('es_numerica')->default(false);
                $table->decimal('valor_numerico', 5, 2)->nullable();
                $table->timestamps();

                $table->unique(
                    ['calificacion_entrega_id', 'inscripcion_id', 'asignacion_materia_id'],
                    'cal_entrega_detalle_unico'
                );
                $table->index(
                    ['asignacion_materia_id', 'inscripcion_id'],
                    'cal_entrega_detalle_asignacion_alumno_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion_entrega_detalles');
        Schema::dropIfExists('calificacion_entregas');

        Schema::table('users', function (Blueprint $table): void {
            foreach (['temporary_password_issued_at', 'must_change_password'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
