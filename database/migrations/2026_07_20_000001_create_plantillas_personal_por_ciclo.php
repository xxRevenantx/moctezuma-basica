<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cabecerasDuplicadas = DB::table('persona_nivel')
            ->select('persona_id', 'nivel_id')
            ->groupBy('persona_id', 'nivel_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($cabecerasDuplicadas) {
            throw new RuntimeException(
                'Existen relaciones persona + nivel duplicadas. Ejecuta primero: php artisan plantilla-personal:previsualizar-migracion'
            );
        }

        // Una sola cabecera por persona y nivel. Aquí viven las fechas SEG, SEP y C.T.,
        // que pueden ser distintas entre Preescolar, Primaria, Secundaria y Bachillerato.
        Schema::table('persona_nivel', function (Blueprint $table) {
            $table->unique(['persona_id', 'nivel_id'], 'persona_nivel_persona_nivel_unique');
        });

        Schema::table('role_personas', function (Blueprint $table) {
            $table->boolean('requiere_grupo')->default(false)->after('status');
            $table->boolean('permite_grupo')->default(false)->after('requiere_grupo');
            $table->boolean('permite_varios_grupos')->default(false)->after('permite_grupo');
            $table->boolean('es_directivo')->default(false)->after('permite_varios_grupos');
            $table->boolean('es_docente')->default(false)->after('es_directivo');
            $table->boolean('aplica_bachillerato')->default(true)->after('es_docente');
        });

        Schema::create('plantillas_personal_nivel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('estado', 30)->default('borrador')->index();
            $table->foreignId('copiada_de_id')->nullable()->constrained('plantillas_personal_nivel')->nullOnDelete();
            $table->timestamp('publicada_at')->nullable();
            $table->foreignId('publicada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrada_at')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reabierta_at')->nullable();
            $table->foreignId('reabierta_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_reapertura')->nullable();
            $table->json('diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['ciclo_escolar_id', 'nivel_id'], 'plantilla_personal_ciclo_nivel_unique');
        });

        Schema::create('persona_nivel_ciclos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantilla_personal_nivel_id')->constrained('plantillas_personal_nivel')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('persona_nivel_id')->constrained('persona_nivel')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('estado', 20)->default('activo')->index();
            $table->unsignedInteger('orden')->default(1);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->text('motivo_baja')->nullable();
            $table->foreignId('copiado_desde_id')->nullable()->constrained('persona_nivel_ciclos')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plantilla_personal_nivel_id', 'persona_nivel_id'], 'persona_nivel_ciclo_unique');
            $table->index(['plantilla_personal_nivel_id', 'estado', 'orden'], 'persona_nivel_ciclo_orden_idx');
        });

        Schema::table('liberaciones_sueldos', function (Blueprint $table) {
            $table->foreignId('ciclo_escolar_id')
                ->nullable()
                ->after('anio')
                ->constrained('ciclo_escolares')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->index(['ciclo_escolar_id', 'nivel_id'], 'liberacion_ciclo_nivel_idx');
        });

        DB::table('liberaciones_sueldos')->orderBy('id')->eachById(function ($registro) {
            if (!$registro->ciclo_escolar) {
                return;
            }

            [$inicio, $fin] = array_pad(explode('-', (string) $registro->ciclo_escolar, 2), 2, null);
            if (!is_numeric($inicio) || !is_numeric($fin)) {
                return;
            }

            $cicloId = DB::table('ciclo_escolares')
                ->where('inicio_anio', (int) $inicio)
                ->where('fin_anio', (int) $fin)
                ->value('id');

            if ($cicloId) {
                DB::table('liberaciones_sueldos')->where('id', $registro->id)->update(['ciclo_escolar_id' => $cicloId]);
            }
        }, 'id');

        Schema::table('persona_nivel_detalles', function (Blueprint $table) {
            $table->foreignId('persona_nivel_ciclo_id')
                ->nullable()
                ->after('persona_nivel_id')
                ->constrained('persona_nivel_ciclos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->boolean('confirmado')->default(true)->after('estado');
            $table->string('pendiente_motivo')->nullable()->after('confirmado');
            $table->timestamp('archivado_at')->nullable()->after('motivo_baja');
            $table->foreignId('archivado_por')->nullable()->after('archivado_at')->constrained('users')->nullOnDelete();
            $table->text('motivo_archivo')->nullable()->after('archivado_por');
            $table->index(['persona_nivel_ciclo_id', 'estado', 'confirmado'], 'persona_nivel_detalle_ciclo_idx');
        });

        $this->configurarReglasRoles();
        $this->migrarPlantillaActual();
    }

    private function configurarReglasRoles(): void
    {
        DB::table('role_personas')->update([
            'requiere_grupo' => false,
            'permite_grupo' => false,
            'permite_varios_grupos' => false,
            'es_directivo' => false,
            'es_docente' => false,
            'aplica_bachillerato' => true,
        ]);

        DB::table('role_personas')
            ->whereIn('slug', ['maestro_frente_a_grupo', 'director_con_grupo'])
            ->update(['requiere_grupo' => true, 'permite_grupo' => true, 'es_docente' => true]);

        DB::table('role_personas')
            ->where(function ($query) {
                $query->where('slug', 'like', '%maestro%')
                    ->orWhere('slug', 'like', '%docente%')
                    ->orWhereIn('slug', ['tutor', 'prefecto', 'apoyo_educativo', 'apoyo_labor_educativa']);
            })
            ->update(['permite_grupo' => true, 'permite_varios_grupos' => true, 'es_docente' => true]);

        DB::table('role_personas')
            ->where(function ($query) {
                $query->where('slug', 'like', '%director%')
                    ->orWhere('slug', 'like', '%subdirector%')
                    ->orWhere('slug', 'like', '%coordinador%')
                    ->orWhere('slug', 'like', '%mando_medio%');
            })
            ->update(['es_directivo' => true]);
    }

    private function migrarPlantillaActual(): void
    {
        $cicloHistorico = DB::table('ciclo_escolares')
            ->where('inicio_anio', 2025)
            ->where('fin_anio', 2026)
            ->first();

        $cicloActual = DB::table('ciclo_escolares')->where('es_actual', true)->first()
            ?: DB::table('ciclo_escolares')->orderByDesc('id')->first();

        if (!$cicloHistorico) {
            $cicloHistorico = $cicloActual;
        }

        if (!$cicloHistorico) {
            return;
        }

        $niveles = DB::table('niveles')->orderBy('id')->pluck('id');
        $plantillasHistoricas = [];
        $plantillasActuales = [];
        $ahora = now();

        foreach ($niveles as $nivelId) {
            $plantillasHistoricas[$nivelId] = DB::table('plantillas_personal_nivel')->insertGetId([
                'ciclo_escolar_id' => $cicloHistorico->id,
                'nivel_id' => $nivelId,
                'estado' => $cicloHistorico->cerrado_at ? 'cerrada' : 'publicada',
                'publicada_at' => $cicloHistorico->cerrado_at ?: $ahora,
                'cerrada_at' => $cicloHistorico->cerrado_at,
                'cerrada_por' => $cicloHistorico->cerrado_por,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            if ($cicloActual && (int) $cicloActual->id !== (int) $cicloHistorico->id) {
                $plantillasActuales[$nivelId] = DB::table('plantillas_personal_nivel')->insertGetId([
                    'ciclo_escolar_id' => $cicloActual->id,
                    'nivel_id' => $nivelId,
                    'estado' => 'borrador',
                    'copiada_de_id' => $plantillasHistoricas[$nivelId],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            DB::table('ciclo_escolar_niveles')->updateOrInsert(
                ['ciclo_escolar_id' => $cicloHistorico->id, 'nivel_id' => $nivelId],
                ['estado' => $cicloHistorico->cerrado_at ? 'cerrado' : 'listo', 'updated_at' => $ahora, 'created_at' => $ahora]
            );

            if ($cicloActual && (int) $cicloActual->id !== (int) $cicloHistorico->id) {
                DB::table('ciclo_escolar_niveles')->updateOrInsert(
                    ['ciclo_escolar_id' => $cicloActual->id, 'nivel_id' => $nivelId],
                    ['estado' => 'en_preparacion', 'updated_at' => $ahora, 'created_at' => $ahora]
                );
            }
        }

        $roles = DB::table('persona_role')
            ->join('role_personas', 'role_personas.id', '=', 'persona_role.role_persona_id')
            ->select('persona_role.id', 'role_personas.requiere_grupo')
            ->get()
            ->keyBy('id');

        DB::table('persona_nivel')->orderBy('id')->eachById(function ($cabecera) use (
            $plantillasHistoricas,
            $plantillasActuales,
            $cicloActual,
            $cicloHistorico,
            $roles,
            $ahora
        ) {
            $plantillaHistoricaId = $plantillasHistoricas[$cabecera->nivel_id] ?? null;
            if (!$plantillaHistoricaId) {
                return;
            }

            $membresiaHistoricaId = DB::table('persona_nivel_ciclos')->insertGetId([
                'plantilla_personal_nivel_id' => $plantillaHistoricaId,
                'persona_nivel_id' => $cabecera->id,
                'estado' => $cabecera->estado ?: 'activo',
                'orden' => $cabecera->orden ?: 1,
                'fecha_inicio' => $cabecera->fecha_inicio,
                'fecha_fin' => $cabecera->fecha_fin,
                'fecha_baja' => $cabecera->fecha_baja,
                'motivo_baja' => $cabecera->motivo_baja,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('persona_nivel_detalles')
                ->where('persona_nivel_id', $cabecera->id)
                ->update(['persona_nivel_ciclo_id' => $membresiaHistoricaId]);

            if (!$cicloActual || (int) $cicloActual->id === (int) $cicloHistorico->id) {
                return;
            }

            $plantillaActualId = $plantillasActuales[$cabecera->nivel_id] ?? null;
            if (!$plantillaActualId) {
                return;
            }

            $membresiaActualId = DB::table('persona_nivel_ciclos')->insertGetId([
                'plantilla_personal_nivel_id' => $plantillaActualId,
                'persona_nivel_id' => $cabecera->id,
                'estado' => 'activo',
                'orden' => $cabecera->orden ?: 1,
                'fecha_inicio' => $cicloActual->inicio_anio . '-07-01',
                'copiado_desde_id' => $membresiaHistoricaId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $detalles = DB::table('persona_nivel_detalles')
                ->where('persona_nivel_id', $cabecera->id)
                ->orderBy('orden')
                ->get();

            foreach ($detalles as $detalle) {
                $rol = $roles->get($detalle->persona_role_id);
                $requiereRevision = !is_null($detalle->grupo_id) || (bool) ($rol->requiere_grupo ?? false);

                DB::table('persona_nivel_detalles')->insert([
                    'persona_nivel_id' => $cabecera->id,
                    'persona_nivel_ciclo_id' => $membresiaActualId,
                    'persona_role_id' => $detalle->persona_role_id,
                    'grado_id' => $requiereRevision ? $detalle->grado_id : null,
                    'grupo_id' => null,
                    'fecha_inicio' => $cicloActual->inicio_anio . '-07-01',
                    'fecha_fin' => null,
                    'estado' => 'activo',
                    'confirmado' => !$requiereRevision,
                    'pendiente_motivo' => $requiereRevision ? 'Confirmar grupo para el nuevo ciclo escolar.' : null,
                    'es_titular' => false,
                    'es_titular_principal' => false,
                    'asignacion_materia_id' => null,
                    'materia_manual' => $detalle->materia_manual,
                    'ajuste_horas_frente_grupo' => 0,
                    'horas_administrativas' => $detalle->horas_administrativas,
                    'actividad_administrativa_id' => $detalle->actividad_administrativa_id,
                    'actividad_administrativa_manual' => $detalle->actividad_administrativa_manual,
                    'limite_horas_semanales' => $detalle->limite_horas_semanales,
                    'observaciones' => $requiereRevision
                        ? trim(($detalle->observaciones ? $detalle->observaciones . "\n" : '') . 'Asignación copiada como propuesta; requiere confirmar grado y grupo.')
                        : $detalle->observaciones,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'orden' => $detalle->orden ?: 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }, 'id');
    }

    public function down(): void
    {
        // Retira las copias creadas para el ciclo nuevo antes de volver al esquema legado.
        // Los detalles originales permanecen porque están ligados a membresías sin copiado_desde_id.
        $membresiasCopiadas = DB::table('persona_nivel_ciclos')
            ->whereNotNull('copiado_desde_id')
            ->pluck('id');

        if ($membresiasCopiadas->isNotEmpty()) {
            DB::table('persona_nivel_detalles')
                ->whereIn('persona_nivel_ciclo_id', $membresiasCopiadas)
                ->delete();
        }

        Schema::table('persona_nivel_detalles', function (Blueprint $table) {
            $table->dropIndex('persona_nivel_detalle_ciclo_idx');
            $table->dropConstrainedForeignId('archivado_por');
            $table->dropConstrainedForeignId('persona_nivel_ciclo_id');
            $table->dropColumn(['confirmado', 'pendiente_motivo', 'archivado_at', 'motivo_archivo']);
        });

        Schema::dropIfExists('persona_nivel_ciclos');
        Schema::dropIfExists('plantillas_personal_nivel');

        Schema::table('liberaciones_sueldos', function (Blueprint $table) {
            $table->dropIndex('liberacion_ciclo_nivel_idx');
            $table->dropConstrainedForeignId('ciclo_escolar_id');
        });

        Schema::table('persona_nivel', function (Blueprint $table) {
            $table->dropUnique('persona_nivel_persona_nivel_unique');
        });

        Schema::table('role_personas', function (Blueprint $table) {
            $table->dropColumn([
                'requiere_grupo', 'permite_grupo', 'permite_varios_grupos',
                'es_directivo', 'es_docente', 'aplica_bachillerato',
            ]);
        });
    }
};
