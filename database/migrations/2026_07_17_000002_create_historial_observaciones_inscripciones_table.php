<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('historial_observaciones_inscripciones')) {
            return;
        }

        Schema::create('historial_observaciones_inscripciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('observacion_inscripcion_id');
            $table->unsignedBigInteger('inscripcion_id');
            $table->unsignedBigInteger('ciclo_escolar_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->longText('contenido_anterior')->nullable();
            $table->longText('contenido_nuevo')->nullable();
            $table->string('origen', 30)->default('edicion');
            $table->timestamps();

            $table->index(
                ['inscripcion_id', 'ciclo_escolar_id', 'created_at'],
                'hist_obs_alumno_ciclo_fecha_idx'
            );

            $table->foreign('observacion_inscripcion_id', 'hist_obs_registro_fk')
                ->references('id')->on('observaciones_inscripciones')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('inscripcion_id', 'hist_obs_inscripcion_fk')
                ->references('id')->on('inscripciones')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('ciclo_escolar_id', 'hist_obs_ciclo_fk')
                ->references('id')->on('ciclo_escolares')
                ->cascadeOnUpdate()->restrictOnDelete();

            $table->foreign('usuario_id', 'hist_obs_usuario_fk')
                ->references('id')->on('users')
                ->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_observaciones_inscripciones');
    }
};
