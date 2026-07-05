<?php

namespace App\Console\Commands;

use App\Models\Materia;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditarMateriasExtraBachillerato extends Command
{
    protected $signature = 'materias:auditar-extras-bachillerato
                            {--fix : Normaliza únicamente banderas incompatibles, sin eliminar calificaciones}';

    protected $description = 'Audita que las materias extra de bachillerato sean informativas y no participen en promedios';

    public function handle(): int
    {
        $materias = Materia::query()
            ->where('nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('orden')
            ->get();

        $extras = $materias->filter(fn(Materia $materia) => (bool) $materia->extra)->values();

        $inconsistentes = $materias
            ->filter(function (Materia $materia): bool {
                if ($materia->receso && ($materia->calificable || $materia->extra || $materia->participa_en_calificacion_oficial)) {
                    return true;
                }

                if ($materia->extra && ($materia->receso || $materia->participa_en_calificacion_oficial || ! $materia->calificable)) {
                    return true;
                }

                return $materia->extra && blank($materia->semestre_id);
            })
            ->values();

        $this->components->info('Auditoría de materias extra de bachillerato');
        $this->table(
            ['Indicador', 'Total'],
            [
                ['Materias de bachillerato', $materias->count()],
                ['Materias oficiales/promediables', $materias->filter(fn($materia) => ReglasMateriaBachillerato::esPromediable($materia))->count()],
                ['Materias extra informativas', $extras->count()],
                ['Banderas inconsistentes', $inconsistentes->count()],
                ['Asignaciones de materias extra', $this->contarAsignacionesExtra()],
                ['Calificaciones capturadas en extras', $this->contarCalificacionesExtra()],
            ]
        );

        if ($inconsistentes->isNotEmpty()) {
            $this->newLine();
            $this->warn('Se detectaron materias con banderas incompatibles:');
            $this->table(
                ['ID', 'Materia', 'Semestre', 'Calificable', 'Extra', 'Receso', 'Oficial'],
                $inconsistentes->map(fn(Materia $materia) => [
                    $materia->id,
                    $materia->materia,
                    $materia->semestre_id ?: '—',
                    $materia->calificable ? 'Sí' : 'No',
                    $materia->extra ? 'Sí' : 'No',
                    $materia->receso ? 'Sí' : 'No',
                    $materia->participa_en_calificacion_oficial ? 'Sí' : 'No',
                ])->all()
            );

            if ($this->option('fix')) {
                DB::transaction(function () use ($inconsistentes): void {
                    foreach ($inconsistentes as $materia) {
                        ReglasMateriaBachillerato::normalizarModelo($materia);
                        $materia->save();
                    }
                });

                $this->components->info('Banderas normalizadas. No se eliminaron asignaciones ni calificaciones.');
            } else {
                $this->line('Ejecuta con --fix para normalizar solo las banderas incompatibles.');
            }
        } else {
            $this->components->info('No se detectaron banderas incompatibles.');
        }

        $this->mostrarCoberturaPorSemestre();

        return self::SUCCESS;
    }

    private function contarAsignacionesExtra(): int
    {
        if (!Schema::hasTable('asignacion_materias')) {
            return 0;
        }

        return DB::table('asignacion_materias')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('materias.nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
            ->where('materias.extra', true)
            ->where('materias.receso', false)
            ->count();
    }

    private function contarCalificacionesExtra(): int
    {
        if (!Schema::hasTable('calificaciones') || !Schema::hasTable('asignacion_materias')) {
            return 0;
        }

        return DB::table('calificaciones')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('materias.nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
            ->where('materias.extra', true)
            ->where('materias.receso', false)
            ->count();
    }

    private function mostrarCoberturaPorSemestre(): void
    {
        if (!Schema::hasTable('semestres')) {
            return;
        }

        $configuraciones = Schema::hasTable('materia_promediar')
            ? DB::table('materia_promediar')
                ->where('nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
                ->pluck('numero_materias', 'semestre_id')
            : collect();

        $filas = DB::table('semestres')
            ->join('grados', 'grados.id', '=', 'semestres.grado_id')
            ->where('grados.nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
            ->select('semestres.id', 'semestres.numero', 'grados.nombre as grado')
            ->orderBy('semestres.numero')
            ->get()
            ->map(function ($semestre) use ($configuraciones): array {
                $oficiales = Materia::query()
                    ->where('nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
                    ->where('semestre_id', $semestre->id)
                    ->where('calificable', true)
                    ->where('extra', false)
                    ->where('receso', false)
                    ->count();

                $extras = Materia::query()
                    ->where('nivel_id', ReglasMateriaBachillerato::NIVEL_ID)
                    ->where('semestre_id', $semestre->id)
                    ->where('extra', true)
                    ->where('receso', false)
                    ->count();

                $configurado = (int) ($configuraciones[$semestre->id] ?? 0);

                return [
                    $semestre->numero . '°',
                    $semestre->grado,
                    $oficiales,
                    $extras,
                    $configurado > 0 ? $configurado : 'Automático (' . $oficiales . ')',
                    $configurado > 0 && $configurado !== $oficiales ? 'Revisar' : 'Correcto',
                ];
            })
            ->all();

        $this->newLine();
        $this->line('Cobertura de materias por semestre:');
        $this->table(
            ['Semestre', 'Grado', 'Oficiales', 'Extras', 'Divisor', 'Estado'],
            $filas
        );
    }
}
