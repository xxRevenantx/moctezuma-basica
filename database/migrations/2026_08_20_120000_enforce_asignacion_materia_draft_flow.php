<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asignacion_materias') || ! Schema::hasColumn('asignacion_materias', 'estado')) {
            return;
        }

        Schema::table('asignacion_materias', function (Blueprint $table): void {
            // Las cargas nuevas siempre nacen en preparación. La activación debe
            // ocurrir únicamente mediante la confirmación administrativa.
            $table->string('estado', 20)->default('borrador')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('asignacion_materias') || ! Schema::hasColumn('asignacion_materias', 'estado')) {
            return;
        }

        Schema::table('asignacion_materias', function (Blueprint $table): void {
            $table->string('estado', 20)->default('activa')->change();
        });
    }
};
