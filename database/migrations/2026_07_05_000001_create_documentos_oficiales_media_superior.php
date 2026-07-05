<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_media_superior', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->unique()->constrained('niveles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nombre_plantel_oficial')->nullable();
            $table->string('numero_acuerdo')->nullable();
            $table->string('modalidad', 80)->nullable();
            $table->string('turno', 80)->nullable();
            $table->string('localidad_expedicion')->nullable();
            $table->string('logo_seg_path')->nullable();
            $table->string('logo_plantel_path')->nullable();
            $table->boolean('mostrar_materias_extra')->default(true);
            $table->timestamps();
        });

        $nivelBachilleratoId = DB::table('niveles')->where('slug', 'bachillerato')->value('id');
        $escuela = DB::table('escuela')->first();

        if ($nivelBachilleratoId) {
            DB::table('configuraciones_media_superior')->insert([
                'nivel_id' => $nivelBachilleratoId,
                'nombre_plantel_oficial' => $escuela?->nombre,
                'numero_acuerdo' => 'SEG/0031/2021',
                'modalidad' => 'Escolarizada',
                'turno' => 'Matutino',
                'localidad_expedicion' => collect([$escuela?->ciudad, $escuela?->estado])->filter()->implode(', '),
                'logo_seg_path' => 'imagenes/logo-seg.png',
                'logo_plantel_path' => 'imagenes/logo-letra.png',
                'mostrar_materias_extra' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::create('firmantes_media_superior', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('rol', 50);
            $table->foreignId('director_id')->nullable()->constrained('directores')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete()->cascadeOnUpdate();
            $table->string('cargo_impresion')->nullable();
            $table->foreignId('ciclo_desde_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('ciclo_hasta_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['nivel_id', 'rol', 'activo'], 'firmantes_ms_nivel_rol_activo_idx');
        });

        if ($nivelBachilleratoId) {
            $directorId = DB::table('niveles')->where('id', $nivelBachilleratoId)->value('director_id');
            $controlEscolarId = DB::table('personas')
                ->where(function ($query): void {
                    $query->where('rfc', 'GABE880722NW4')
                        ->orWhere(function ($persona): void {
                            $persona->where('nombre', 'Edgar')
                                ->where('apellido_paterno', 'García')
                                ->where('apellido_materno', 'Basilio');
                        });
                })
                ->value('id');

            $firmantesIniciales = [];

            if ($directorId) {
                $firmantesIniciales[] = [
                    'nivel_id' => $nivelBachilleratoId,
                    'rol' => 'director_plantel',
                    'director_id' => $directorId,
                    'persona_id' => null,
                    'cargo_impresion' => 'DIRECTORA DEL PLANTEL',
                    'ciclo_desde_id' => null,
                    'ciclo_hasta_id' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($controlEscolarId) {
                $firmantesIniciales[] = [
                    'nivel_id' => $nivelBachilleratoId,
                    'rol' => 'control_escolar',
                    'director_id' => null,
                    'persona_id' => $controlEscolarId,
                    'cargo_impresion' => 'RESPONSABLE DE CONTROL ESCOLAR',
                    'ciclo_desde_id' => null,
                    'ciclo_hasta_id' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($firmantesIniciales !== []) {
                DB::table('firmantes_media_superior')->insert($firmantesIniciales);
            }
        }

        Schema::create('asistencias_finales_bachillerato', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('asignacion_materia_id')->constrained('asignacion_materias')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->foreignId('capturado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('capturado_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['inscripcion_id', 'asignacion_materia_id', 'ciclo_escolar_id'],
                'asistencia_final_bachillerato_unique'
            );
        });

        Schema::create('emisiones_documentos_media_superior', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('inscripcion_id')->nullable()->constrained('inscripciones')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 50);
            $table->string('clave_contexto', 191);
            $table->string('folio')->nullable();
            $table->string('formato', 20);
            $table->json('contexto')->nullable();
            $table->string('hash_datos', 64)->nullable();
            $table->foreignId('emitido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('emitido_at')->nullable();
            $table->timestamps();

            // Al volver a emitir, el registro actual se reemplaza (no se versiona el archivo anterior).
            $table->unique(['tipo', 'clave_contexto', 'formato'], 'emision_ms_contexto_formato_unique');
            $table->index(['nivel_id', 'tipo', 'emitido_at'], 'emision_ms_nivel_tipo_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emisiones_documentos_media_superior');
        Schema::dropIfExists('asistencias_finales_bachillerato');
        Schema::dropIfExists('firmantes_media_superior');
        Schema::dropIfExists('configuraciones_media_superior');
    }
};
