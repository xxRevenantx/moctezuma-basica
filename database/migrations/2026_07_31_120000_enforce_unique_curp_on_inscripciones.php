<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inscripciones') || ! Schema::hasColumn('inscripciones', 'curp')) {
            return;
        }

        $duplicadas = DB::table('inscripciones')
            ->selectRaw('UPPER(TRIM(curp)) AS curp_normalizada, COUNT(*) AS total')
            ->whereNotNull('curp')
            ->whereRaw("TRIM(curp) <> ''")
            ->groupByRaw('UPPER(TRIM(curp))')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicadas->isNotEmpty()) {
            $detalle = $duplicadas
                ->map(fn ($fila) => $fila->curp_normalizada . ' (' . $fila->total . ')')
                ->implode(', ');

            throw new \RuntimeException(
                'No se agregó el índice único de CURP porque existen registros duplicados: ' . $detalle
                . '. Corrige los duplicados y vuelve a ejecutar la migración.'
            );
        }

        DB::table('inscripciones')
            ->whereNotNull('curp')
            ->update(['curp' => DB::raw('UPPER(TRIM(curp))')]);

        if ($this->indexExists('inscripciones', 'inscripciones_curp_unique')) {
            return;
        }

        Schema::table('inscripciones', function (Blueprint $table): void {
            $table->unique('curp', 'inscripciones_curp_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inscripciones')) {
            return;
        }

        if ($this->indexExists('inscripciones', 'inscripciones_curp_unique')) {
            Schema::table('inscripciones', function (Blueprint $table): void {
                $table->dropUnique('inscripciones_curp_unique');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $definition): bool => ($definition['name'] ?? null) === $index);
    }
};
