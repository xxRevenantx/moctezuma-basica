<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ciclo_escolares')) {
            return;
        }

        $agregarFecha = ! Schema::hasColumn('ciclo_escolares', 'fecha_corte_continuidad');
        $agregarUsuario = ! Schema::hasColumn('ciclo_escolares', 'fecha_corte_continuidad_por');
        $agregarMarcaTiempo = ! Schema::hasColumn('ciclo_escolares', 'fecha_corte_continuidad_at');

        if (! $agregarFecha && ! $agregarUsuario && ! $agregarMarcaTiempo) {
            return;
        }

        Schema::table('ciclo_escolares', function (Blueprint $table) use ($agregarFecha, $agregarUsuario, $agregarMarcaTiempo): void {
            if ($agregarFecha) {
                $table->date('fecha_corte_continuidad')->nullable()->after('cerrado_por');
            }

            if ($agregarUsuario) {
                $table->foreignId('fecha_corte_continuidad_por')
                    ->nullable()
                    ->after('fecha_corte_continuidad')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            if ($agregarMarcaTiempo) {
                $table->timestamp('fecha_corte_continuidad_at')
                    ->nullable()
                    ->after('fecha_corte_continuidad_por');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ciclo_escolares')) {
            return;
        }

        $tieneUsuario = Schema::hasColumn('ciclo_escolares', 'fecha_corte_continuidad_por');
        $columnas = collect([
            'fecha_corte_continuidad',
            'fecha_corte_continuidad_por',
            'fecha_corte_continuidad_at',
        ])->filter(fn (string $columna): bool => Schema::hasColumn('ciclo_escolares', $columna))->values()->all();

        if ($tieneUsuario) {
            Schema::table('ciclo_escolares', function (Blueprint $table): void {
                $table->dropForeign(['fecha_corte_continuidad_por']);
            });
        }

        if ($columnas !== []) {
            Schema::table('ciclo_escolares', function (Blueprint $table) use ($columnas): void {
                $table->dropColumn($columnas);
            });
        }
    }
};
