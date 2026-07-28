<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('generaciones')) {
            return;
        }

        if (! Schema::hasColumn('generaciones', 'estado_cierre')) {
            Schema::table('generaciones', function (Blueprint $table): void {
                $table->string('estado_cierre', 20)
                    ->default('activa')
                    ->after('status')
                    ->index();
            });
        }

        if (! Schema::hasColumn('generaciones', 'cierre_iniciado_at')) {
            Schema::table('generaciones', function (Blueprint $table): void {
                $table->timestamp('cierre_iniciado_at')->nullable()->after('estado_cierre');
            });
        }

        if (! Schema::hasColumn('generaciones', 'cierre_iniciado_por')) {
            Schema::table('generaciones', function (Blueprint $table): void {
                $table->unsignedBigInteger('cierre_iniciado_por')->nullable()->after('cierre_iniciado_at');
            });
        }

        if (! Schema::hasColumn('generaciones', 'archivada_at')) {
            Schema::table('generaciones', function (Blueprint $table): void {
                $table->timestamp('archivada_at')->nullable()->after('reactivada_por');
            });
        }

        if (! Schema::hasColumn('generaciones', 'archivada_por')) {
            Schema::table('generaciones', function (Blueprint $table): void {
                $table->unsignedBigInteger('archivada_por')->nullable()->after('archivada_at');
            });
        }

        $this->addForeignIfMissing('generaciones', 'cierre_iniciado_por', 'generaciones_cierre_iniciado_por_fk');
        $this->addForeignIfMissing('generaciones', 'archivada_por', 'generaciones_archivada_por_fk');

        DB::table('generaciones')
            ->whereNull('estado_cierre')
            ->orWhere('estado_cierre', '')
            ->update([
                'estado_cierre' => DB::raw("CASE WHEN status = 1 THEN 'activa' ELSE 'egresada' END"),
            ]);

        DB::table('generaciones')
            ->where('status', false)
            ->where('estado_cierre', 'activa')
            ->update(['estado_cierre' => 'egresada']);
    }

    private function addForeignIfMissing(string $table, string $column, string $constraint): void
    {
        if (! Schema::hasColumn($table, $column) || $this->foreignKeyExists($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $constraint): void {
            $blueprint->foreign($column, $constraint)
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    public function down(): void
    {
        // Migración de estabilización: no elimina columnas para no perder el
        // estado histórico de cierres, reaperturas y archivos ya realizados.
    }
};
