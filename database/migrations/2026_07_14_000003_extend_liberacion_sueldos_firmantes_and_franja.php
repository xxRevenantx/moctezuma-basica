<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liberacion_sueldo_configuraciones', function (Blueprint $table) {
            $table->string('franja_inferior_path')->nullable()->after('logo_encabezado_path');
            $table->decimal('franja_ancho_mm', 6, 2)->default(200)->after('franja_inferior_path');
            $table->decimal('franja_alto_mm', 6, 2)->default(5.5)->after('franja_ancho_mm');
            $table->decimal('franja_inferior_mm', 6, 2)->default(4)->after('franja_alto_mm');
        });

        Schema::table('liberaciones_sueldos', function (Blueprint $table) {
            $table->unsignedBigInteger('director_persona_id')->nullable()->after('encabezado_direccion');
            $table->unsignedBigInteger('supervisor_director_id')->nullable()->after('supervisor_cargo');
            $table->unsignedBigInteger('jefe_sector_director_id')->nullable()->after('supervisor_director_id');
            $table->string('jefe_sector_nombre')->nullable()->after('jefe_sector_director_id');
            $table->string('jefe_sector_cargo')->default('JEFE DE SECTOR')->after('jefe_sector_nombre');
            $table->boolean('destinatario_es_directivo')->default(false)->after('jefe_sector_cargo');
            $table->string('tipo_firmantes', 40)->default('direccion_supervision')->after('destinatario_es_directivo');
            $table->string('franja_inferior_path')->nullable()->after('logo_encabezado_path');
            $table->decimal('franja_ancho_mm', 6, 2)->default(200)->after('franja_inferior_path');
            $table->decimal('franja_alto_mm', 6, 2)->default(5.5)->after('franja_ancho_mm');
            $table->decimal('franja_inferior_mm', 6, 2)->default(4)->after('franja_alto_mm');

            $table->index('supervisor_director_id', 'lib_sueldos_supervisor_idx');
            $table->index('jefe_sector_director_id', 'lib_sueldos_jefe_sector_idx');
            $table->index('tipo_firmantes', 'lib_sueldos_tipo_firmantes_idx');
        });
    }

    public function down(): void
    {
        Schema::table('liberaciones_sueldos', function (Blueprint $table) {
            $table->dropIndex('lib_sueldos_supervisor_idx');
            $table->dropIndex('lib_sueldos_jefe_sector_idx');
            $table->dropIndex('lib_sueldos_tipo_firmantes_idx');
            $table->dropColumn([
                'director_persona_id',
                'supervisor_director_id',
                'jefe_sector_director_id',
                'jefe_sector_nombre',
                'jefe_sector_cargo',
                'destinatario_es_directivo',
                'tipo_firmantes',
                'franja_inferior_path',
                'franja_ancho_mm',
                'franja_alto_mm',
                'franja_inferior_mm',
            ]);
        });

        Schema::table('liberacion_sueldo_configuraciones', function (Blueprint $table) {
            $table->dropColumn([
                'franja_inferior_path',
                'franja_ancho_mm',
                'franja_alto_mm',
                'franja_inferior_mm',
            ]);
        });
    }
};
