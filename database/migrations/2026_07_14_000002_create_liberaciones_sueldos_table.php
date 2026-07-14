<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liberaciones_sueldos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_nivel_id')->nullable()->constrained('persona_nivel')->nullOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();

            $table->string('trabajador_nombre');
            $table->string('nivel_nombre');
            $table->string('encabezado_subsecretaria')->default('SUBSECRETARÍA DE EDUCACIÓN BÁSICA');
            $table->string('encabezado_direccion');
            $table->string('director_nombre')->nullable();
            $table->string('director_cargo')->default('DIRECTOR');
            $table->string('escuela_nombre');
            $table->string('cct', 40)->nullable();
            $table->string('localidad')->nullable();
            $table->string('municipio')->nullable();
            $table->string('supervisor_nombre')->nullable();
            $table->string('supervisor_cargo')->default('SUPERVISOR ESCOLAR');

            $table->date('fecha_documento');
            $table->unsignedSmallInteger('quincena_inicio')->default(13);
            $table->unsignedSmallInteger('quincena_fin')->default(14);
            $table->unsignedSmallInteger('anio');
            $table->string('ciclo_escolar', 20)->nullable();
            $table->date('fecha_reanudacion')->nullable();
            $table->string('clave_presupuestal')->default('S/N');
            $table->string('logo_encabezado_path')->nullable();
            $table->string('archivo_pdf_path')->nullable();
            $table->string('archivo_word_path')->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nivel_id', 'fecha_documento']);
            $table->index(['persona_id', 'fecha_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liberaciones_sueldos');
    }
};
