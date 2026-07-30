<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proyecciones_continuidad')) {
            return;
        }

        Schema::table('proyecciones_continuidad', function (Blueprint $table): void {
            if (! Schema::hasColumn('proyecciones_continuidad', 'revertida_at')) {
                $table->timestamp('revertida_at')->nullable()->after('snapshot_cancelacion');
            }

            if (! Schema::hasColumn('proyecciones_continuidad', 'revertida_por')) {
                $table->foreignId('revertida_por')
                    ->nullable()
                    ->after('revertida_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            if (! Schema::hasColumn('proyecciones_continuidad', 'fecha_reversion')) {
                $table->date('fecha_reversion')->nullable()->after('revertida_por');
            }

            if (! Schema::hasColumn('proyecciones_continuidad', 'tipo_reversion')) {
                $table->string('tipo_reversion', 40)->nullable()->after('fecha_reversion');
            }

            if (! Schema::hasColumn('proyecciones_continuidad', 'motivo_reversion')) {
                $table->text('motivo_reversion')->nullable()->after('tipo_reversion');
            }

            if (! Schema::hasColumn('proyecciones_continuidad', 'snapshot_reversion')) {
                $table->json('snapshot_reversion')->nullable()->after('motivo_reversion');
            }
        });

        if (! $this->indexExists('proyecciones_continuidad', 'proyecciones_estado_revertida_idx')) {
            Schema::table('proyecciones_continuidad', function (Blueprint $table): void {
                $table->index(['estado', 'revertida_at'], 'proyecciones_estado_revertida_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('proyecciones_continuidad')) {
            return;
        }

        Schema::table('proyecciones_continuidad', function (Blueprint $table): void {
            if ($this->indexExists('proyecciones_continuidad', 'proyecciones_estado_revertida_idx')) {
                $table->dropIndex('proyecciones_estado_revertida_idx');
            }

            if (Schema::hasColumn('proyecciones_continuidad', 'revertida_por')) {
                $table->dropForeign(['revertida_por']);
            }

            $columnas = collect([
                'revertida_at',
                'revertida_por',
                'fecha_reversion',
                'tipo_reversion',
                'motivo_reversion',
                'snapshot_reversion',
            ])->filter(fn (string $columna): bool => Schema::hasColumn('proyecciones_continuidad', $columna))->all();

            if ($columnas !== []) {
                $table->dropColumn($columnas);
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
