<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->string('clave')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nivel_id', 'slug'], 'talleres_nivel_slug_unique');
            $table->index(['nivel_id', 'activo']);
        });

        Schema::create('taller_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')
                ->constrained('talleres')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('profesor_id')
                ->nullable()
                ->constrained('personas')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')
                ->constrained('ciclo_escolares')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('dia_id')
                ->constrained('dias')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('hora_id')
                ->constrained('horas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('ubicacion')->nullable();
            $table->boolean('conflicto_forzado')->default(false);
            $table->foreignId('forzado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('motivo_conflicto')->nullable();
            $table->timestamps();

            $table->index(['ciclo_escolar_id', 'dia_id', 'hora_id'], 'taller_sesion_bloque_index');
            $table->index(['profesor_id', 'ciclo_escolar_id'], 'taller_sesion_profesor_ciclo_index');
        });

        Schema::create('taller_sesion_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_sesion_id')
                ->constrained('taller_sesiones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['taller_sesion_id', 'grupo_id'], 'taller_sesion_grupo_unique');
            $table->index('grupo_id');
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->foreignId('taller_sesion_id')
                ->nullable()
                ->after('asignacion_materia_id')
                ->constrained('taller_sesiones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('ciclo_escolar_id')
                ->nullable()
                ->after('taller_sesion_id')
                ->constrained('ciclo_escolares')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(
                ['grupo_id', 'dia_id', 'hora_id', 'ciclo_escolar_id'],
                'horarios_grupo_bloque_ciclo_index'
            );
        });

        // Los registros normales conservan su asignación; los talleres usarán taller_sesion_id.
        DB::statement('ALTER TABLE horarios MODIFY asignacion_materia_id BIGINT UNSIGNED NULL');

        $ultimoCicloId = DB::table('ciclo_escolares')->max('id');

        if ($ultimoCicloId) {
            DB::table('horarios')
                ->whereNull('ciclo_escolar_id')
                ->update(['ciclo_escolar_id' => $ultimoCicloId]);
        }
    }

    public function down(): void
    {
        // Primero se eliminan las proyecciones de talleres para poder restaurar NOT NULL.
        DB::table('horarios')->whereNotNull('taller_sesion_id')->delete();

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex('horarios_grupo_bloque_ciclo_index');
            $table->dropConstrainedForeignId('ciclo_escolar_id');
            $table->dropConstrainedForeignId('taller_sesion_id');
        });

        DB::statement('ALTER TABLE horarios MODIFY asignacion_materia_id BIGINT UNSIGNED NOT NULL');

        Schema::dropIfExists('taller_sesion_grupo');
        Schema::dropIfExists('taller_sesiones');
        Schema::dropIfExists('talleres');
    }
};
