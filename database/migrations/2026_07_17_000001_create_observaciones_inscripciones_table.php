<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('observaciones_inscripciones')) {
            return;
        }

        Schema::create('observaciones_inscripciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inscripcion_id');
            $table->unsignedBigInteger('ciclo_escolar_id');
            $table->longText('contenido')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamps();

            $table->unique(
                ['inscripcion_id', 'ciclo_escolar_id'],
                'obs_inscripcion_ciclo_unique'
            );

            $table->foreign('inscripcion_id', 'obs_inscripcion_fk')
                ->references('id')->on('inscripciones')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('ciclo_escolar_id', 'obs_ciclo_escolar_fk')
                ->references('id')->on('ciclo_escolares')
                ->cascadeOnUpdate()->restrictOnDelete();

            $table->foreign('creado_por', 'obs_creado_por_fk')
                ->references('id')->on('users')
                ->cascadeOnUpdate()->nullOnDelete();

            $table->foreign('actualizado_por', 'obs_actualizado_por_fk')
                ->references('id')->on('users')
                ->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_inscripciones');
    }
};
