<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_administrativas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('horas_sugeridas', 6, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        DB::table('actividades_administrativas')->insert([
            ['nombre' => 'Coordinación académica', 'horas_sugeridas' => 5, 'activo' => true, 'orden' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Tutoría y seguimiento', 'horas_sugeridas' => 3, 'activo' => true, 'orden' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Planeación y evaluación', 'horas_sugeridas' => 5, 'activo' => true, 'orden' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Control escolar / administrativo', 'horas_sugeridas' => 8, 'activo' => true, 'orden' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Dirección o subdirección', 'horas_sugeridas' => 10, 'activo' => true, 'orden' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Otra actividad', 'horas_sugeridas' => 0, 'activo' => true, 'orden' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('persona_nivel', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('ingreso_ct');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->string('estado', 20)->default('activo')->after('fecha_fin')->index();
            $table->decimal('horas_administrativas', 6, 2)->default(0)->after('estado');
            $table->decimal('limite_horas_semanales', 6, 2)->default(40)->after('horas_administrativas');
            $table->string('actividad_administrativa')->nullable()->after('limite_horas_semanales');
            $table->text('observaciones')->nullable()->after('actividad_administrativa');
            $table->date('fecha_baja')->nullable()->after('observaciones');
            $table->text('motivo_baja')->nullable()->after('fecha_baja');
        });

        Schema::table('persona_nivel_detalles', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('grupo_id');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->string('estado', 20)->default('activo')->after('fecha_fin')->index();
            $table->boolean('es_titular')->default(false)->after('estado');
            $table->boolean('es_titular_principal')->default(false)->after('es_titular');

            $table->foreignId('asignacion_materia_id')
                ->nullable()
                ->after('es_titular_principal')
                ->constrained('asignacion_materias')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('materia_manual')->nullable()->after('asignacion_materia_id');
            $table->decimal('ajuste_horas_frente_grupo', 6, 2)->default(0)->after('materia_manual');
            $table->decimal('horas_administrativas', 6, 2)->default(0)->after('ajuste_horas_frente_grupo');

            $table->foreignId('actividad_administrativa_id')
                ->nullable()
                ->after('horas_administrativas')
                ->constrained('actividades_administrativas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('actividad_administrativa_manual')->nullable()->after('actividad_administrativa_id');
            $table->decimal('limite_horas_semanales', 6, 2)->nullable()->after('actividad_administrativa_manual');
            $table->text('observaciones')->nullable()->after('limite_horas_semanales');
            $table->date('fecha_baja')->nullable()->after('observaciones');
            $table->text('motivo_baja')->nullable()->after('fecha_baja');

            $table->index(['persona_nivel_id', 'estado'], 'persona_nivel_detalles_estado_idx');
            $table->index(['grupo_id', 'es_titular_principal', 'estado'], 'persona_nivel_titular_idx');
        });

        Schema::create('persona_nivel_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_nivel_id')->nullable()->constrained('persona_nivel')->nullOnDelete();
            $table->foreignId('persona_nivel_detalle_id')->nullable()->constrained('persona_nivel_detalles')->nullOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->string('accion', 40);
            $table->string('descripcion')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['persona_id', 'fecha'], 'persona_nivel_historial_persona_idx');
            $table->index(['nivel_id', 'fecha'], 'persona_nivel_historial_nivel_idx');
            $table->index(['accion', 'fecha'], 'persona_nivel_historial_accion_idx');
        });

        // Los registros existentes se consideran vigentes desde su fecha de creación.
        DB::table('persona_nivel')
            ->whereNull('fecha_inicio')
            ->update(['fecha_inicio' => DB::raw('DATE(created_at)')]);

        DB::table('persona_nivel_detalles')
            ->whereNull('fecha_inicio')
            ->update(['fecha_inicio' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_nivel_historial');

        Schema::table('persona_nivel_detalles', function (Blueprint $table) {
            $table->dropIndex('persona_nivel_detalles_estado_idx');
            $table->dropIndex('persona_nivel_titular_idx');
            $table->dropConstrainedForeignId('actividad_administrativa_id');
            $table->dropConstrainedForeignId('asignacion_materia_id');
            $table->dropColumn([
                'fecha_inicio', 'fecha_fin', 'estado', 'es_titular', 'es_titular_principal',
                'materia_manual', 'ajuste_horas_frente_grupo', 'horas_administrativas',
                'actividad_administrativa_manual', 'limite_horas_semanales', 'observaciones',
                'fecha_baja', 'motivo_baja',
            ]);
        });

        Schema::table('persona_nivel', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn([
                'fecha_inicio', 'fecha_fin', 'estado', 'horas_administrativas',
                'limite_horas_semanales', 'actividad_administrativa', 'observaciones',
                'fecha_baja', 'motivo_baja',
            ]);
        });

        Schema::dropIfExists('actividades_administrativas');
    }
};
