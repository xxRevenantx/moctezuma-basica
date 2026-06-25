<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('es_general')->default(true);
            $table->boolean('requiere_nivel')->default(false);
            $table->boolean('es_obligatorio')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        $ahora = now();

        DB::table('tipos_documentos')->insert([
            [
                'nombre' => 'Acta de nacimiento',
                'slug' => 'acta-nacimiento',
                'descripcion' => 'Acta de nacimiento del alumno.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 1,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Registro de nacimiento',
                'slug' => 'registro-nacimiento',
                'descripcion' => 'Registro de nacimiento, distinto al acta.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 2,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'CURP',
                'slug' => 'curp',
                'descripcion' => 'Documento PDF de la CURP del alumno.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 3,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Comprobante de domicilio',
                'slug' => 'comprobante-domicilio',
                'descripcion' => 'Comprobante de domicilio del alumno o tutor.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 4,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'INE del tutor',
                'slug' => 'ine-tutor',
                'descripcion' => 'Identificación oficial del tutor.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 5,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Certificado de estudios',
                'slug' => 'certificado-estudios',
                'descripcion' => 'Certificado del nivel educativo que acredita el alumno.',
                'es_general' => false,
                'requiere_nivel' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 6,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documentos');
    }
};
