<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuto la migración.
     */
    public function up(): void
    {
        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            $table->boolean('promovido')
                ->nullable()
                ->after('activo');

            $table->dateTime('fecha_promocion')
                ->nullable()
                ->after('promovido');

            $table->foreignId('trayectoria_origen_id')
                ->nullable()
                ->after('fecha_promocion')
                ->constrained('trayectorias_academicas')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Revierto la migración.
     */
    public function down(): void
    {
        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            $table->dropForeign(['trayectoria_origen_id']);
            $table->dropIndex('trayectorias_promovido_index');
            $table->dropIndex('trayectorias_origen_index');

            $table->dropColumn([
                'promovido',
                'fecha_promocion',
                'trayectoria_origen_id',
            ]);
        });
    }
};
