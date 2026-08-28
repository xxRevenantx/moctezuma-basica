<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repara todas las cargas históricas para que asignacion_materias.orden
     * coincida con materias.orden en todos los niveles, ciclos y grupos.
     *
     * Si un contexto del catálogo tiene órdenes duplicados, se omite únicamente
     * ese contexto: nunca se inventa un desempate por nombre, ID o fecha.
     */
    public function up(): void
    {
        if (! Schema::hasTable('materias') || ! Schema::hasTable('asignacion_materias')) {
            return;
        }

        $materias = DB::table('materias')
            ->when(
                Schema::hasColumn('materias', 'deleted_at'),
                fn ($query) => $query->whereNull('deleted_at')
            )
            ->where('receso', false)
            ->get(['id', 'nivel_id', 'grado_id', 'semestre_id', 'orden']);

        if ($materias->isEmpty()) {
            return;
        }

        $contextosConflictivos = $materias
            ->groupBy(fn ($materia) => $this->claveContexto($materia))
            ->filter(function (Collection $items): bool {
                return $items
                    ->groupBy(fn ($materia) => (int) $materia->orden)
                    ->contains(fn (Collection $mismoOrden) => $mismoOrden->count() > 1);
            })
            ->keys();

        $ordenesSeguros = $materias
            ->reject(fn ($materia) => $contextosConflictivos->contains($this->claveContexto($materia)))
            ->mapWithKeys(fn ($materia) => [(int) $materia->id => (int) $materia->orden]);

        DB::transaction(function () use ($ordenesSeguros): void {
            foreach ($ordenesSeguros as $materiaId => $ordenOficial) {
                DB::table('asignacion_materias')
                    ->where('materia_id', $materiaId)
                    ->where(function ($query) use ($ordenOficial): void {
                        $query->whereNull('orden')
                            ->orWhere('orden', '!=', $ordenOficial);
                    })
                    ->update([
                        'orden' => $ordenOficial,
                        'updated_at' => now(),
                    ]);
            }
        });

        if ($contextosConflictivos->isNotEmpty()) {
            logger()->warning(
                'Sincronización de orden académico: se omitieron contextos con materias.orden duplicado.',
                ['contextos' => $contextosConflictivos->values()->all()]
            );
        }
    }

    /**
     * No se revierte una corrección de integridad: el valor anterior era un
     * orden independiente y potencialmente incorrecto.
     */
    public function down(): void
    {
        // Intencionalmente vacío.
    }

    private function claveContexto(object $materia): string
    {
        return (int) $materia->nivel_id
            . '|'
            . (int) $materia->grado_id
            . '|'
            . (int) ($materia->semestre_id ?? 0);
    }
};
