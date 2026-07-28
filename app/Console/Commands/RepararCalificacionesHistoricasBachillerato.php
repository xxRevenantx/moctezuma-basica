<?php

namespace App\Console\Commands;

use App\Models\Calificacion;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RepararCalificacionesHistoricasBachillerato extends Command
{
    protected $signature = 'academico:reparar-calificaciones-historicas-bachillerato
        {--aplicar : Guarda las vinculaciones detectadas; sin esta opción solo muestra una simulación}
        {--ciclo= : Limita el proceso a un ciclo escolar}
        {--inscripcion= : Limita el proceso a un alumno}';

    protected $description = 'Vincula calificaciones de bachillerato con la inscripción histórica correcta sin alterar la ubicación actual del alumno.';

    public function handle(): int
    {
        $nivelIds = Nivel::query()
            ->where(function (Builder $query): void {
                $query->where('slug', 'bachillerato')
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%bachillerato%']);
            })
            ->pluck('id');

        if ($nivelIds->isEmpty()) {
            $this->error('No se encontró el nivel Bachillerato.');
            return self::FAILURE;
        }

        $aplicar = (bool) $this->option('aplicar');
        $resumen = [
            'revisadas' => 0,
            'correctas' => 0,
            'vinculables' => 0,
            'actualizadas' => 0,
            'sin_historial_exacto' => 0,
        ];
        $muestras = [];

        Calificacion::query()
            ->whereIn('nivel_id', $nivelIds)
            ->when($this->option('ciclo'), fn (Builder $query) => $query->where('ciclo_escolar_id', (int) $this->option('ciclo')))
            ->when($this->option('inscripcion'), fn (Builder $query) => $query->where('inscripcion_id', (int) $this->option('inscripcion')))
            ->orderBy('id')
            ->chunkById(500, function ($calificaciones) use (&$resumen, &$muestras, $aplicar): void {
                foreach ($calificaciones as $calificacion) {
                    $resumen['revisadas']++;

                    $historial = InscripcionCiclo::query()
                        ->where('inscripcion_id', $calificacion->inscripcion_id)
                        ->where('ciclo_escolar_id', $calificacion->ciclo_escolar_id)
                        ->where('nivel_id', $calificacion->nivel_id)
                        ->where('grado_id', $calificacion->grado_id)
                        ->where('generacion_id', $calificacion->generacion_id)
                        ->where('grupo_id', $calificacion->grupo_id)
                        ->where(function (Builder $query) use ($calificacion): void {
                            if ($calificacion->semestre_id) {
                                $query->where('semestre_id', $calificacion->semestre_id);
                            } else {
                                $query->whereNull('semestre_id');
                            }
                        })
                        ->first();

                    if (! $historial) {
                        $resumen['sin_historial_exacto']++;
                        if (count($muestras) < 20) {
                            $muestras[] = [$calificacion->id, $calificacion->inscripcion_id, $calificacion->ciclo_escolar_id, 'Sin coincidencia exacta'];
                        }
                        continue;
                    }

                    if ((int) $calificacion->inscripcion_ciclo_id === (int) $historial->id) {
                        $resumen['correctas']++;
                        continue;
                    }

                    $resumen['vinculables']++;
                    if ($aplicar) {
                        $calificacion->forceFill(['inscripcion_ciclo_id' => $historial->id])->saveQuietly();
                        $resumen['actualizadas']++;
                    }

                    if (count($muestras) < 20) {
                        $muestras[] = [$calificacion->id, $calificacion->inscripcion_id, $calificacion->ciclo_escolar_id, 'Vincular a historial #'.$historial->id];
                    }
                }
            });

        $this->table(['Concepto', 'Cantidad'], [
            ['Calificaciones revisadas', $resumen['revisadas']],
            ['Ya vinculadas correctamente', $resumen['correctas']],
            ['Vinculaciones detectadas', $resumen['vinculables']],
            ['Vinculaciones aplicadas', $resumen['actualizadas']],
            ['Sin historial exacto', $resumen['sin_historial_exacto']],
        ]);

        if ($muestras) {
            $this->table(['Calificación', 'Alumno', 'Ciclo', 'Resultado'], $muestras);
        }

        $this->comment($aplicar
            ? 'Proceso aplicado. No se modificaron grado, grupo, semestre ni ciclo actuales de los alumnos.'
            : 'Simulación terminada. Ejecuta nuevamente con --aplicar para guardar las vinculaciones exactas.');

        return self::SUCCESS;
    }
}
