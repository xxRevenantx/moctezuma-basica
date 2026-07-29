<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riesgo_academico_configuraciones', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->json('valor');
            $table->string('descripcion')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Schema::create('riesgo_academico_reglas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete()->cascadeOnUpdate();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria', 40)->default('academico');
            $table->string('tipo_calculo', 60);
            $table->boolean('activo')->default(true);
            $table->decimal('peso', 6, 2)->default(10);
            $table->decimal('max_puntos', 6, 2)->default(20);
            $table->json('parametros')->nullable();
            $table->json('aplica_niveles')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['activo', 'categoria', 'orden'], 'riesgo_reglas_activas_idx');
            $table->index(['nivel_id', 'activo'], 'riesgo_reglas_nivel_idx');
        });

        Schema::create('riesgo_academico_evaluaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_ciclo_id')->nullable()->constrained('inscripcion_ciclos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('semestre_id')->nullable()->constrained('semestres')->nullOnDelete()->cascadeOnUpdate();
            $table->unsignedTinyInteger('puntaje')->default(0);
            $table->string('nivel_riesgo', 20)->default('bajo');
            $table->json('factores')->nullable();
            $table->json('metricas')->nullable();
            $table->json('reglas_aplicadas')->nullable();
            $table->string('origen', 20)->default('automatico');
            $table->string('snapshot_hash', 64)->nullable();
            $table->boolean('es_actual')->default(true);
            $table->timestamp('evaluado_at')->nullable();
            $table->foreignId('evaluado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['inscripcion_id', 'es_actual'], 'riesgo_eval_alumno_actual_idx');
            $table->index(['inscripcion_ciclo_id', 'es_actual'], 'riesgo_eval_historial_actual_idx');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'nivel_riesgo'], 'riesgo_eval_bandeja_idx');
            $table->index(['nivel_riesgo', 'evaluado_at'], 'riesgo_eval_nivel_fecha_idx');
            $table->index('snapshot_hash', 'riesgo_eval_snapshot_idx');
        });

        Schema::create('seguimiento_academico_casos', function (Blueprint $table): void {
            $table->id();
            $table->string('folio', 40)->unique();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_ciclo_id')->nullable()->constrained('inscripcion_ciclos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('riesgo_evaluacion_id')->nullable()->constrained('riesgo_academico_evaluaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete()->cascadeOnUpdate();
            $table->string('estado', 25)->default('abierto');
            $table->string('prioridad', 20)->default('alta');
            $table->string('riesgo_inicial', 20)->default('alto');
            $table->string('riesgo_actual', 20)->default('alto');
            $table->unsignedTinyInteger('puntaje_inicial')->default(0);
            $table->unsignedTinyInteger('puntaje_actual')->default(0);
            $table->text('motivo_apertura');
            $table->text('resumen')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->date('proxima_revision_at')->nullable();
            $table->boolean('apertura_automatica')->default(false);
            $table->timestamp('abierto_at')->nullable();
            $table->foreignId('abierto_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->text('motivo_cierre')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['estado', 'prioridad', 'proxima_revision_at'], 'seguimiento_casos_bandeja_idx');
            $table->index(['inscripcion_id', 'estado'], 'seguimiento_casos_alumno_idx');
            $table->index(['responsable_id', 'estado'], 'seguimiento_casos_responsable_idx');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'riesgo_actual'], 'seguimiento_casos_contexto_idx');
        });

        Schema::create('seguimiento_academico_planes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seguimiento_caso_id')->constrained('seguimiento_academico_casos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('nombre');
            $table->text('objetivo');
            $table->string('estado', 20)->default('activo');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_prevista')->nullable();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['seguimiento_caso_id', 'estado'], 'seguimiento_planes_caso_idx');
        });

        Schema::create('seguimiento_academico_acciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seguimiento_caso_id')->constrained('seguimiento_academico_casos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('plan_id')->nullable()->constrained('seguimiento_academico_planes')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 50)->default('academica');
            $table->text('descripcion');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->date('fecha_limite')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->text('evidencia')->nullable();
            $table->text('resultado')->nullable();
            $table->timestamp('completada_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['seguimiento_caso_id', 'estado', 'fecha_limite'], 'seguimiento_acciones_caso_idx');
            $table->index(['responsable_id', 'estado', 'fecha_limite'], 'seguimiento_acciones_responsable_idx');
        });

        Schema::create('seguimiento_academico_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seguimiento_caso_id')->constrained('seguimiento_academico_casos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('riesgo_evaluacion_id')->nullable()->constrained('riesgo_academico_evaluaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 50);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('ocurrido_at')->nullable();
            $table->timestamps();

            $table->index(['seguimiento_caso_id', 'ocurrido_at'], 'seguimiento_eventos_caso_idx');
            $table->index(['tipo', 'ocurrido_at'], 'seguimiento_eventos_tipo_idx');
        });

        Schema::create('alertas_academicas', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('inscripcion_ciclo_id')->nullable()->constrained('inscripcion_ciclos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('riesgo_evaluacion_id')->nullable()->constrained('riesgo_academico_evaluaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('seguimiento_caso_id')->nullable()->constrained('seguimiento_academico_casos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('destinatario_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 50);
            $table->string('severidad', 20)->default('advertencia');
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('estado', 20)->default('pendiente');
            $table->date('fecha_limite')->nullable();
            $table->timestamp('generada_at')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->timestamp('atendida_at')->nullable();
            $table->foreignId('atendida_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['estado', 'severidad', 'generada_at'], 'alertas_academicas_bandeja_idx');
            $table->index(['destinatario_id', 'estado'], 'alertas_academicas_destinatario_idx');
            $table->index(['inscripcion_id', 'estado'], 'alertas_academicas_alumno_idx');
        });

        $ahora = now();

        DB::table('riesgo_academico_configuraciones')->insert([
            [
                'clave' => 'umbrales',
                'valor' => json_encode(['moderado' => 20, 'alto' => 40, 'critico' => 70], JSON_UNESCAPED_UNICODE),
                'descripcion' => 'Puntajes mínimos para clasificar el semáforo de riesgo.',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'clave' => 'automatizacion',
                'valor' => json_encode(['abrir_caso_desde' => 'alto', 'dias_proyeccion_vencida' => 30, 'dias_alerta_revision' => 3], JSON_UNESCAPED_UNICODE),
                'descripcion' => 'Comportamiento automático de casos y alertas.',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);

        $reglas = [
            ['promedio_bajo', 'Promedio bajo', 'academico', 'promedio_bajo', 20, 30, ['umbral_alerta' => 7, 'umbral_critico' => 6], ['primaria', 'secundaria', 'bachillerato'], 10],
            ['materias_reprobadas', 'Materias o campos reprobados', 'academico', 'materias_reprobadas', 15, 45, ['umbral_aprobacion' => 6], ['primaria', 'secundaria', 'bachillerato'], 20],
            ['tendencia_descendente', 'Descenso entre periodos', 'academico', 'tendencia_descendente', 15, 20, ['descenso_minimo' => 1], ['primaria', 'secundaria', 'bachillerato'], 30],
            ['calificaciones_pendientes', 'Evaluaciones pendientes', 'academico', 'calificaciones_pendientes', 5, 20, ['solo_periodos_concluidos' => true], ['primaria', 'secundaria', 'bachillerato'], 40],
            ['asistencia_baja', 'Asistencia final baja', 'asistencia', 'asistencia_baja', 20, 30, ['umbral' => 80, 'umbral_critico' => 70], ['bachillerato'], 50],
            ['fichas_pendientes', 'Fichas descriptivas pendientes', 'academico', 'fichas_pendientes', 8, 24, ['campos_esperados' => 4], ['preescolar'], 60],
            ['resultado_no_promovido', 'Antecedente de no promoción', 'trayectoria', 'resultado_no_promovido', 15, 15, [], ['primaria', 'secundaria', 'bachillerato'], 70],
            ['integridad_critica', 'Casos críticos de integridad', 'administrativo', 'integridad_critica', 25, 50, [], ['preescolar', 'primaria', 'secundaria', 'bachillerato'], 80],
            ['proyeccion_vencida', 'Continuidad pendiente vencida', 'administrativo', 'proyeccion_vencida', 10, 20, ['dias' => 30], ['preescolar', 'primaria', 'secundaria', 'bachillerato'], 90],
            ['seguimiento_vencido', 'Acciones de seguimiento vencidas', 'seguimiento', 'seguimiento_vencido', 10, 30, [], ['preescolar', 'primaria', 'secundaria', 'bachillerato'], 100],
        ];

        foreach ($reglas as [$codigo, $nombre, $categoria, $tipo, $peso, $maximo, $parametros, $niveles, $orden]) {
            DB::table('riesgo_academico_reglas')->insert([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => 'Regla inicial configurable del semáforo académico.',
                'categoria' => $categoria,
                'tipo_calculo' => $tipo,
                'activo' => true,
                'peso' => $peso,
                'max_puntos' => $maximo,
                'parametros' => json_encode($parametros, JSON_UNESCAPED_UNICODE),
                'aplica_niveles' => json_encode($niveles, JSON_UNESCAPED_UNICODE),
                'orden' => $orden,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_academicas');
        Schema::dropIfExists('seguimiento_academico_eventos');
        Schema::dropIfExists('seguimiento_academico_acciones');
        Schema::dropIfExists('seguimiento_academico_planes');
        Schema::dropIfExists('seguimiento_academico_casos');
        Schema::dropIfExists('riesgo_academico_evaluaciones');
        Schema::dropIfExists('riesgo_academico_reglas');
        Schema::dropIfExists('riesgo_academico_configuraciones');
    }
};
