<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carta_compromiso_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_id')->unique()->constrained('niveles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('membrete_tipo', 30)->default('seg');
            $table->string('encabezado_linea_1')->nullable();
            $table->string('encabezado_linea_2')->nullable();
            $table->string('lugar_default')->default('Cd. Altamirano, Gro.');
            $table->string('leyenda_anual')->nullable();
            $table->string('destinatario_institucion')->default('Centro Universitario Moctezuma');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Schema::create('cartas_compromiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('tutor_id')->nullable()->constrained('tutores')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('documento_alumno_id')->nullable()->constrained('documentos_alumnos')->nullOnDelete()->cascadeOnUpdate();

            $table->string('folio', 50)->unique();
            $table->date('fecha_expedicion');
            $table->string('lugar');
            $table->string('asunto')->default('CARTA COMPROMISO');
            $table->string('leyenda_anual')->nullable();

            $table->string('membrete_tipo', 30)->default('seg');
            $table->string('encabezado_linea_1')->nullable();
            $table->string('encabezado_linea_2')->nullable();

            $table->string('destinatario_nombre')->nullable();
            $table->string('destinatario_cargo')->nullable();
            $table->string('destinatario_institucion')->nullable();

            $table->string('suscriptor_nombre');
            $table->string('suscriptor_parentesco')->nullable();
            $table->string('suscriptor_calidad')->nullable();
            $table->string('referencia_alumno')->nullable();
            $table->string('grado_texto')->nullable();
            $table->string('motivo_tipo', 60)->nullable();
            $table->text('motivo_texto')->nullable();
            $table->longText('contenido_cuerpo');

            $table->string('docente_nombre')->nullable();
            $table->string('directora_nombre')->nullable();

            $table->string('estado_documento', 20)->default('emitida');
            $table->timestamp('cancelada_at')->nullable();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['inscripcion_id', 'fecha_expedicion']);
            $table->index(['nivel_id', 'grado_id', 'grupo_id'], 'cartas_compromiso_contexto_idx');
            $table->index(['estado_documento', 'fecha_expedicion'], 'cartas_compromiso_estado_fecha_idx');
        });

        DB::table('tipos_documentos')->updateOrInsert(
            ['slug' => 'carta-compromiso'],
            [
                'nombre' => 'Carta compromiso',
                'descripcion' => 'Carta compromiso emitida por control escolar y firmada por el responsable del alumno.',
                'es_general' => true,
                'requiere_nivel' => false,
                'nivel_aplica_id' => null,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas_compromiso');
        Schema::dropIfExists('carta_compromiso_configuraciones');

        $tipoId = DB::table('tipos_documentos')->where('slug', 'carta-compromiso')->value('id');
        if ($tipoId && ! DB::table('documentos_alumnos')->where('tipo_documento_id', $tipoId)->exists()) {
            DB::table('tipos_documentos')->where('id', $tipoId)->delete();
        }
    }
};
