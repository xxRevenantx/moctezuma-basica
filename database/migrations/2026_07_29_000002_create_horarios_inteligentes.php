<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_docente_configuraciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->cascadeOnDelete();
            $table->unsignedTinyInteger('max_grupos_simultaneos')->default(2);
            $table->unsignedTinyInteger('max_horas_diarias')->default(6);
            $table->unsignedTinyInteger('max_horas_consecutivas')->default(3);
            $table->unsignedTinyInteger('min_descanso_bloques')->default(0);
            $table->unsignedTinyInteger('max_huecos_diarios')->default(2);
            $table->foreignId('primera_hora_id')->nullable()->constrained('horas')->nullOnDelete();
            $table->foreignId('ultima_hora_id')->nullable()->constrained('horas')->nullOnDelete();
            $table->boolean('permitir_multigrado')->default(true);
            $table->boolean('permitir_materias_distintas')->default(false);
            $table->boolean('requiere_motivo_traslape')->default(true);
            $table->boolean('activo')->default(true);
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['persona_id', 'ciclo_escolar_id', 'nivel_id'], 'hdoc_cfg_unica');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'activo'], 'hdoc_cfg_contexto_idx');
        });

        Schema::create('horario_docente_disponibilidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('configuracion_id')->constrained('horario_docente_configuraciones')->cascadeOnDelete();
            $table->foreignId('dia_id')->constrained('dias')->cascadeOnDelete();
            $table->foreignId('hora_id')->constrained('horas')->cascadeOnDelete();
            $table->string('estado', 24)->default('disponible');
            $table->text('motivo')->nullable();
            $table->timestamps();
            $table->unique(['configuracion_id', 'dia_id', 'hora_id'], 'hdoc_disp_unica');
            $table->index(['estado', 'dia_id', 'hora_id'], 'hdoc_disp_bloque_idx');
        });

        Schema::create('horario_docente_excepciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->cascadeOnDelete();
            $table->date('fecha');
            $table->foreignId('hora_id')->nullable()->constrained('horas')->cascadeOnDelete();
            $table->string('estado', 24)->default('no_disponible');
            $table->text('motivo');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['persona_id', 'fecha', 'hora_id'], 'hdoc_exc_fecha_idx');
        });

        Schema::create('horario_asignacion_reglas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asignacion_materia_id')->unique()->constrained('asignacion_materias')->cascadeOnDelete();
            $table->unsignedTinyInteger('sesiones_semanales')->default(1);
            $table->unsignedTinyInteger('max_sesiones_dia')->default(1);
            $table->boolean('permitir_bloques_consecutivos')->default(false);
            $table->unsignedTinyInteger('max_bloques_consecutivos')->default(2);
            $table->unsignedTinyInteger('dias_minimos')->default(1);
            $table->string('preferencia_horaria', 24)->default('cualquiera');
            $table->boolean('permitir_multigrado')->default(true);
            $table->boolean('bloqueada')->default(false);
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('horario_reglas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria', 20)->default('preferencia');
            $table->boolean('activa')->default(true);
            $table->unsignedSmallInteger('peso')->default(10);
            $table->json('parametros')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('horario_versiones', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete();
            $table->foreignId('version_origen_id')->nullable()->constrained('horario_versiones')->nullOnDelete();
            $table->unsignedInteger('numero');
            $table->string('nombre');
            $table->string('estado', 24)->default('borrador');
            $table->string('objetivo', 40)->default('manual');
            $table->decimal('puntaje', 6, 2)->nullable();
            $table->json('metricas')->nullable();
            $table->json('conflictos')->nullable();
            $table->string('hash_integridad', 64)->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('vigencia_desde')->nullable();
            $table->timestamp('publicar_at')->nullable();
            $table->timestamp('publicado_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('publicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['ciclo_escolar_id', 'nivel_id', 'numero'], 'hver_numero_unico');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'estado'], 'hver_contexto_idx');
            $table->index(['estado', 'publicar_at'], 'hver_publicacion_idx');
        });

        Schema::create('horario_version_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('horario_version_id')->constrained('horario_versiones')->cascadeOnDelete();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('grados')->cascadeOnDelete();
            $table->foreignId('generacion_id')->constrained('generaciones')->cascadeOnDelete();
            $table->foreignId('semestre_id')->nullable()->constrained('semestres')->nullOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('hora_id')->constrained('horas')->cascadeOnDelete();
            $table->foreignId('dia_id')->constrained('dias')->cascadeOnDelete();
            $table->foreignId('asignacion_materia_id')->nullable()->constrained('asignacion_materias')->nullOnDelete();
            $table->foreignId('taller_sesion_id')->nullable()->constrained('taller_sesiones')->nullOnDelete();
            $table->foreignId('profesor_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->boolean('sesion_compartida')->default(false);
            $table->string('clave_sesion_compartida', 64)->nullable();
            $table->text('motivo_sesion_compartida')->nullable();
            $table->boolean('traslape_excepcional')->default(false);
            $table->text('motivo_traslape_excepcional')->nullable();
            $table->text('motivo_autorizacion_disponibilidad')->nullable();
            $table->boolean('coensenanza')->default(false);
            $table->boolean('bloqueado')->default(false);
            $table->string('origen', 24)->default('manual');
            $table->timestamps();
            $table->index(['horario_version_id', 'grupo_id', 'dia_id', 'hora_id'], 'hver_det_grupo_idx');
            $table->index(['horario_version_id', 'profesor_id', 'dia_id', 'hora_id'], 'hver_det_docente_idx');
            $table->index(['clave_sesion_compartida'], 'hver_det_compartida_idx');
        });

        Schema::table('horarios', function (Blueprint $table): void {
            $table->foreignId('profesor_id')->nullable()->after('asignacion_materia_id')->constrained('personas')->nullOnDelete();
            $table->foreignId('horario_version_id')->nullable()->after('ciclo_escolar_id')->constrained('horario_versiones')->nullOnDelete();
            $table->boolean('traslape_excepcional')->default(false)->after('motivo_sesion_compartida');
            $table->text('motivo_traslape_excepcional')->nullable()->after('traslape_excepcional');
            $table->text('motivo_autorizacion_disponibilidad')->nullable()->after('motivo_traslape_excepcional');
            $table->boolean('coensenanza')->default(false)->after('motivo_autorizacion_disponibilidad');
            $table->index(['horario_version_id', 'grupo_id', 'dia_id', 'hora_id'], 'horarios_version_bloque_idx');
        });

        DB::statement("UPDATE horarios h LEFT JOIN asignacion_materias am ON am.id = h.asignacion_materia_id LEFT JOIN taller_sesiones ts ON ts.id = h.taller_sesion_id SET h.profesor_id = COALESCE(am.profesor_id, ts.profesor_id) WHERE h.profesor_id IS NULL");

        Schema::create('horario_version_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('horario_version_id')->constrained('horario_versiones')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ocurrido_at')->nullable();
            $table->timestamps();
            $table->index(['horario_version_id', 'ocurrido_at'], 'hver_evento_idx');
        });

        $this->sembrarReglas();
        $this->sincronizarCargas();
        $this->convertirHorariosActualesEnVersionUno();
    }

    private function sembrarReglas(): void
    {
        $ahora = now();
        $reglas = [
            ['codigo' => 'grupo_sin_duplicar', 'nombre' => 'Un grupo no recibe dos clases independientes', 'categoria' => 'obligatoria', 'peso' => 100, 'orden' => 10],
            ['codigo' => 'disponibilidad_docente', 'nombre' => 'Respetar disponibilidad del docente', 'categoria' => 'obligatoria', 'peso' => 100, 'orden' => 20],
            ['codigo' => 'horas_semanales', 'nombre' => 'Cumplir sesiones semanales por materia', 'categoria' => 'obligatoria', 'peso' => 100, 'orden' => 30],
            ['codigo' => 'recesos_bloqueados', 'nombre' => 'No utilizar materias de receso como clase', 'categoria' => 'obligatoria', 'peso' => 100, 'orden' => 40],
            ['codigo' => 'limite_grupos_simultaneos', 'nombre' => 'Máximo configurable de grupos simultáneos por docente', 'categoria' => 'obligatoria', 'peso' => 100, 'orden' => 50],
            ['codigo' => 'reducir_huecos_docente', 'nombre' => 'Reducir huecos del docente', 'categoria' => 'preferencia', 'peso' => 12, 'orden' => 60],
            ['codigo' => 'distribuir_materia', 'nombre' => 'Distribuir una materia en días distintos', 'categoria' => 'preferencia', 'peso' => 10, 'orden' => 70],
            ['codigo' => 'preferencias_horarias', 'nombre' => 'Respetar primeras o últimas horas preferidas', 'categoria' => 'preferencia', 'peso' => 8, 'orden' => 80],
            ['codigo' => 'limitar_consecutivas', 'nombre' => 'Limitar horas consecutivas', 'categoria' => 'preferencia', 'peso' => 8, 'orden' => 90],
            ['codigo' => 'premiar_bloque_preferido', 'nombre' => 'Priorizar bloques marcados como preferidos', 'categoria' => 'preferencia', 'peso' => 6, 'orden' => 100],
            ['codigo' => 'penalizar_traslape_excepcional', 'nombre' => 'Usar simultaneidad excepcional solo cuando sea necesaria', 'categoria' => 'preferencia', 'peso' => 18, 'orden' => 110],
        ];

        foreach ($reglas as $regla) {
            DB::table('horario_reglas')->insert(array_merge($regla, [
                'descripcion' => null,
                'activa' => true,
                'parametros' => null,
                'actualizado_por' => null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]));
        }
    }

    private function sincronizarCargas(): void
    {
        if (! Schema::hasTable('asignacion_materias')) {
            return;
        }

        DB::table('asignacion_materias')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($asignaciones): void {
                $ahora = now();
                $filas = [];
                foreach ($asignaciones as $asignacion) {
                    $sesiones = max(1, (int) DB::table('horarios')->where('asignacion_materia_id', $asignacion->id)->count());
                    $filas[] = [
                        'asignacion_materia_id' => $asignacion->id,
                        'sesiones_semanales' => min(20, $sesiones),
                        'max_sesiones_dia' => 1,
                        'permitir_bloques_consecutivos' => false,
                        'max_bloques_consecutivos' => 2,
                        'dias_minimos' => min(5, $sesiones),
                        'preferencia_horaria' => 'cualquiera',
                        'permitir_multigrado' => true,
                        'bloqueada' => false,
                        'actualizado_por' => null,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }
                if ($filas !== []) {
                    DB::table('horario_asignacion_reglas')->insert($filas);
                }
            });
    }

    private function convertirHorariosActualesEnVersionUno(): void
    {
        if (! Schema::hasTable('horarios') || DB::table('horarios')->count() === 0) {
            return;
        }

        $contextos = DB::table('horarios')
            ->whereNotNull('ciclo_escolar_id')
            ->select('ciclo_escolar_id', 'nivel_id')
            ->distinct()
            ->orderBy('ciclo_escolar_id')
            ->orderBy('nivel_id')
            ->get();

        foreach ($contextos as $contexto) {
            $ahora = now();
            $versionId = DB::table('horario_versiones')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'ciclo_escolar_id' => $contexto->ciclo_escolar_id,
                'nivel_id' => $contexto->nivel_id,
                'generacion_id' => null,
                'version_origen_id' => null,
                'numero' => 1,
                'nombre' => 'Versión 1 · Horario existente',
                'estado' => 'publicada',
                'objetivo' => 'importado',
                'puntaje' => null,
                'metricas' => json_encode(['importado_desde_horarios' => true]),
                'conflictos' => null,
                'hash_integridad' => null,
                'motivo' => 'Conversión automática del horario existente al sistema de versiones.',
                'observaciones' => null,
                'vigencia_desde' => $ahora,
                'publicar_at' => null,
                'publicado_at' => $ahora,
                'creado_por' => null,
                'revisado_por' => null,
                'publicado_por' => null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('horarios')
                ->where('ciclo_escolar_id', $contexto->ciclo_escolar_id)
                ->where('nivel_id', $contexto->nivel_id)
                ->update(['horario_version_id' => $versionId]);

            DB::table('horarios')
                ->where('horarios.ciclo_escolar_id', $contexto->ciclo_escolar_id)
                ->where('horarios.nivel_id', $contexto->nivel_id)
                ->leftJoin('asignacion_materias', 'asignacion_materias.id', '=', 'horarios.asignacion_materia_id')
                ->leftJoin('taller_sesiones', 'taller_sesiones.id', '=', 'horarios.taller_sesion_id')
                ->select([
                    'horarios.*',
                    DB::raw('COALESCE(horarios.profesor_id, asignacion_materias.profesor_id, taller_sesiones.profesor_id) as profesor_resuelto_id'),
                ])
                ->orderBy('horarios.id')
                ->chunk(500, function ($horarios) use ($versionId, $ahora): void {
                    $filas = [];
                    foreach ($horarios as $horario) {
                        $filas[] = [
                            'horario_version_id' => $versionId,
                            'nivel_id' => $horario->nivel_id,
                            'grado_id' => $horario->grado_id,
                            'generacion_id' => $horario->generacion_id,
                            'semestre_id' => $horario->semestre_id,
                            'grupo_id' => $horario->grupo_id,
                            'hora_id' => $horario->hora_id,
                            'dia_id' => $horario->dia_id,
                            'asignacion_materia_id' => $horario->asignacion_materia_id,
                            'taller_sesion_id' => $horario->taller_sesion_id,
                            'profesor_id' => $horario->profesor_resuelto_id,
                            'sesion_compartida' => (bool) $horario->sesion_compartida,
                            'clave_sesion_compartida' => $horario->clave_sesion_compartida,
                            'motivo_sesion_compartida' => $horario->motivo_sesion_compartida,
                            'traslape_excepcional' => false,
                            'motivo_traslape_excepcional' => null,
                            'motivo_autorizacion_disponibilidad' => null,
                            'coensenanza' => false,
                            'bloqueado' => true,
                            'origen' => 'actual',
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                    }
                    if ($filas !== []) {
                        DB::table('horario_version_detalles')->insert($filas);
                    }
                });

            DB::table('horario_version_eventos')->insert([
                'horario_version_id' => $versionId,
                'tipo' => 'importacion',
                'titulo' => 'Horario existente convertido en versión publicada',
                'descripcion' => 'Los bloques actuales se conservaron sin eliminarlos ni modificar su contenido.',
                'metadata' => null,
                'usuario_id' => null,
                'ocurrido_at' => $ahora,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('horarios')) {
            Schema::table('horarios', function (Blueprint $table): void {
                foreach (['horarios_version_bloque_idx'] as $index) {
                    try { $table->dropIndex($index); } catch (\Throwable) {}
                }
                foreach (['horario_version_id', 'profesor_id'] as $foreign) {
                    try { $table->dropForeign([$foreign]); } catch (\Throwable) {}
                }
                foreach (['profesor_id', 'horario_version_id', 'traslape_excepcional', 'motivo_traslape_excepcional', 'motivo_autorizacion_disponibilidad', 'coensenanza'] as $column) {
                    if (Schema::hasColumn('horarios', $column)) { $table->dropColumn($column); }
                }
            });
        }

        Schema::dropIfExists('horario_version_eventos');
        Schema::dropIfExists('horario_version_detalles');
        Schema::dropIfExists('horario_versiones');
        Schema::dropIfExists('horario_reglas');
        Schema::dropIfExists('horario_asignacion_reglas');
        Schema::dropIfExists('horario_docente_excepciones');
        Schema::dropIfExists('horario_docente_disponibilidades');
        Schema::dropIfExists('horario_docente_configuraciones');
    }
};
