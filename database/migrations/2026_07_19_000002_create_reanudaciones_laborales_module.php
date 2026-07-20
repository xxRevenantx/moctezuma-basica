<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reanudaciones_laborales', function (Blueprint $table) {
            $table->id();
            $table->uuid('lote_uuid')->index();
            $table->foreignId('persona_nivel_id')->nullable()->constrained('persona_nivel')->nullOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();

            $table->string('tipo_reanudacion', 30);
            $table->date('fecha_director');
            $table->date('fecha_docente');
            $table->date('fecha_documento');
            $table->text('copias')->nullable();

            $table->string('persona_nombre');
            $table->json('cargos')->nullable();
            $table->boolean('es_directivo')->default(false);
            $table->string('nivel_nombre');
            $table->string('nivel_slug', 60);
            $table->string('ciclo_nombre', 30);
            $table->string('grado_resumen')->nullable();
            $table->string('grupo_resumen')->nullable();
            $table->string('destinatario_nombre')->nullable();
            $table->string('destinatario_cargo')->nullable();
            $table->json('snapshot')->nullable();

            $table->string('archivo_pdf_path')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ciclo_escolar_id', 'nivel_id', 'tipo_reanudacion'], 'reanudacion_ciclo_nivel_tipo_idx');
            $table->index(['persona_id', 'fecha_documento'], 'reanudacion_persona_fecha_idx');
        });

        Schema::create('reanudacion_ccp_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('contenido');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('reanudacion_ccp_plantillas')->insert([
            'nombre' => 'Supervisor y archivo',
            'contenido' => "C.C.P. PROFR. ______________________________, SUPERVISOR ESCOLAR, PARA SU CONOCIMIENTO.\nC.C.P. ARCHIVO.",
            'activo' => true,
            'orden' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reanudacion_ccp_plantillas');
        Schema::dropIfExists('reanudaciones_laborales');
    }
};
