<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grupos', 'ciclo_escolar_id')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->foreignId('ciclo_escolar_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('ciclo_escolares')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasColumn('grupos', 'clave')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->string('clave', 120)
                    ->nullable()
                    ->after('ciclo_escolar_id')
                    ->unique();
            });
        }

        if (! Schema::hasColumn('grupos', 'estado')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->string('estado', 20)
                    ->default('activo')
                    ->after('clave')
                    ->index();
            });
        }

        if (! Schema::hasColumn('grupos', 'motivo_generacion_excepcional')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->text('motivo_generacion_excepcional')
                    ->nullable()
                    ->after('estado');
            });
        }

        if (! Schema::hasIndex('grupos', 'grupos_asignacion_escolar_idx')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->index(
                    ['ciclo_escolar_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'estado'],
                    'grupos_asignacion_escolar_idx'
                );
            });
        }

        if (! Schema::hasColumn('inscripciones', 'ciclo_escolar_id')) {
            Schema::table('inscripciones', function (Blueprint $table): void {
                $table->foreignId('ciclo_escolar_id')
                    ->nullable()
                    ->after('ciclo_id')
                    ->constrained('ciclo_escolares')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if (! Schema::hasIndex('inscripciones', 'inscripciones_ciclo_grupo_activo_idx')) {
            Schema::table('inscripciones', function (Blueprint $table): void {
                $table->index(
                    ['ciclo_escolar_id', 'grupo_id', 'activo'],
                    'inscripciones_ciclo_grupo_activo_idx'
                );
            });
        }

        if (! Schema::hasTable('ciclo_escolar_niveles')) {
            Schema::create('ciclo_escolar_niveles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ciclo_escolar_id')
                    ->constrained('ciclo_escolares')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('nivel_id')
                    ->constrained('niveles')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->string('estado', 30)->default('pendiente')->index();
                $table->timestamp('preparado_at')->nullable();
                $table->foreignId('preparado_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->json('diagnostico')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->unique(
                    ['ciclo_escolar_id', 'nivel_id'],
                    'ciclo_escolar_niveles_unique'
                );
            });
        }

        $this->normalizarOrdenSemestres();
        $this->asignarCicloAGruposExistentes();
        $this->asignarCicloAInscripcionesExistentes();
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclo_escolar_niveles');

        if (Schema::hasIndex('inscripciones', 'inscripciones_ciclo_grupo_activo_idx')) {
            Schema::table('inscripciones', function (Blueprint $table): void {
                $table->dropIndex('inscripciones_ciclo_grupo_activo_idx');
            });
        }

        if (Schema::hasColumn('inscripciones', 'ciclo_escolar_id')) {
            Schema::table('inscripciones', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('ciclo_escolar_id');
            });
        }

        if (Schema::hasIndex('grupos', 'grupos_asignacion_escolar_idx')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->dropIndex('grupos_asignacion_escolar_idx');
            });
        }

        if (Schema::hasColumn('grupos', 'ciclo_escolar_id')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('ciclo_escolar_id');
            });
        }

        if (Schema::hasColumn('grupos', 'clave')) {
            Schema::table('grupos', function (Blueprint $table): void {
                if (Schema::hasIndex('grupos', 'grupos_clave_unique')) {
                    $table->dropUnique('grupos_clave_unique');
                }
                $table->dropColumn('clave');
            });
        }

        if (Schema::hasColumn('grupos', 'motivo_generacion_excepcional')) {
            Schema::table('grupos', function (Blueprint $table): void {
                $table->dropColumn('motivo_generacion_excepcional');
            });
        }

        if (Schema::hasColumn('grupos', 'estado')) {
            Schema::table('grupos', function (Blueprint $table): void {
                if (Schema::hasIndex('grupos', 'grupos_estado_index')) {
                    $table->dropIndex('grupos_estado_index');
                }
                $table->dropColumn('estado');
            });
        }
    }

    private function normalizarOrdenSemestres(): void
    {
        if (!Schema::hasTable('semestres') || !Schema::hasColumn('semestres', 'orden_global')) {
            return;
        }

        DB::table('semestres')
            ->whereNull('orden_global')
            ->orderBy('id')
            ->get(['id', 'numero'])
            ->each(function (object $semestre): void {
                DB::table('semestres')
                    ->where('id', $semestre->id)
                    ->update(['orden_global' => (int) $semestre->numero]);
            });
    }

    private function asignarCicloAGruposExistentes(): void
    {
        if (!Schema::hasTable('grupos')) {
            return;
        }

        $ciclosPorInicio = DB::table('ciclo_escolares')
            ->get(['id', 'inicio_anio'])
            ->keyBy(fn (object $ciclo): int => (int) $ciclo->inicio_anio);

        $generaciones = DB::table('generaciones')
            ->get(['id', 'anio_ingreso', 'anio_egreso'])
            ->keyBy('id');

        $posicionesGrado = collect();

        DB::table('grados')
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'nivel_id'])
            ->groupBy('nivel_id')
            ->each(function ($gradosNivel) use ($posicionesGrado): void {
                foreach ($gradosNivel->values() as $indice => $grado) {
                    $posicionesGrado->put((int) $grado->id, $indice + 1);
                }
            });

        $semestres = DB::table('semestres')
            ->get(['id', 'numero'])
            ->keyBy('id');

        DB::table('grupos')
            ->orderBy('id')
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
                'ciclo_escolar_id',
            ])
            ->each(function (object $grupo) use (
                $ciclosPorInicio,
                $generaciones,
                $posicionesGrado,
                $semestres
            ): void {
                if ($grupo->ciclo_escolar_id) {
                    return;
                }

                $generacion = $generaciones->get($grupo->generacion_id);

                if (!$generacion) {
                    return;
                }

                if ($grupo->semestre_id) {
                    $semestre = $semestres->get($grupo->semestre_id);

                    if (!$semestre) {
                        return;
                    }

                    $desplazamiento = intdiv(max(1, (int) $semestre->numero) - 1, 2);
                } else {
                    $posicion = (int) ($posicionesGrado->get((int) $grupo->grado_id) ?? 0);

                    if ($posicion < 1) {
                        return;
                    }

                    $desplazamiento = $posicion - 1;
                }

                $anioCiclo = (int) $generacion->anio_ingreso + $desplazamiento;
                $ciclo = $ciclosPorInicio->get($anioCiclo);

                if (!$ciclo) {
                    return;
                }

                $clave = $this->claveGrupo(
                    cicloInicio: $anioCiclo,
                    nivelId: (int) $grupo->nivel_id,
                    gradoId: (int) $grupo->grado_id,
                    generacionIngreso: (int) $generacion->anio_ingreso,
                    generacionEgreso: (int) $generacion->anio_egreso,
                    asignacionGrupoId: (int) $grupo->asignacion_grupo_id,
                    semestreId: $grupo->semestre_id ? (int) $grupo->semestre_id : null,
                );

                if (DB::table('grupos')->where('clave', $clave)->where('id', '<>', $grupo->id)->exists()) {
                    $clave .= '-ID' . $grupo->id;
                }

                DB::table('grupos')
                    ->where('id', $grupo->id)
                    ->update([
                        'ciclo_escolar_id' => $ciclo->id,
                        'clave' => $clave,
                        'estado' => 'activo',
                    ]);
            });
    }

    private function asignarCicloAInscripcionesExistentes(): void
    {
        if (!Schema::hasTable('inscripciones')) {
            return;
        }

        DB::table('inscripciones')
            ->whereNull('ciclo_escolar_id')
            ->orderBy('id')
            ->get(['id', 'grupo_id'])
            ->each(function (object $inscripcion): void {
                $cicloEscolarId = DB::table('grupos')
                    ->where('id', $inscripcion->grupo_id)
                    ->value('ciclo_escolar_id');

                if (!$cicloEscolarId && Schema::hasTable('observaciones_inscripciones')) {
                    $cicloEscolarId = DB::table('observaciones_inscripciones')
                        ->where('inscripcion_id', $inscripcion->id)
                        ->orderByDesc('ciclo_escolar_id')
                        ->value('ciclo_escolar_id');
                }

                if ($cicloEscolarId) {
                    DB::table('inscripciones')
                        ->where('id', $inscripcion->id)
                        ->update(['ciclo_escolar_id' => $cicloEscolarId]);
                }
            });
    }

    private function claveGrupo(
        int $cicloInicio,
        int $nivelId,
        int $gradoId,
        int $generacionIngreso,
        int $generacionEgreso,
        int $asignacionGrupoId,
        ?int $semestreId,
    ): string {
        return implode('-', [
            $cicloInicio . '-' . ($cicloInicio + 1),
            'N' . $nivelId,
            'G' . $gradoId,
            $generacionIngreso . '-' . $generacionEgreso,
            'S' . ($semestreId ?: 0),
            'A' . $asignacionGrupoId,
        ]);
    }
};
