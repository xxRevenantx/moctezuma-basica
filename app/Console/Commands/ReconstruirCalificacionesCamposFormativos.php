<?php

namespace App\Console\Commands;

use App\Models\CalificacionCampoFormativo;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use App\Support\PromedioExcel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconstruirCalificacionesCamposFormativos extends Command
{
    protected $signature = 'calificaciones:reconstruir-campos
        {--ciclo= : ID del ciclo escolar que se desea analizar}
        {--aplicar : Guarda las sugerencias reconstruidas sin confirmarlas}';

    protected $description = 'Reconstruye sugerencias de campos formativos de primaria desde las calificaciones internas por materia.';

    public function handle(): int
    {
        if (! Schema::hasTable('calificaciones_campos_formativos')) {
            $this->error('Primero ejecuta las migraciones.');
            return self::FAILURE;
        }

        $nivelId = Nivel::query()->where('slug', 'primaria')->value('id');

        if (! $nivelId) {
            $this->error('No se encontró el nivel primaria.');
            return self::FAILURE;
        }

        $cicloId = $this->option('ciclo');

        if (! $cicloId) {
            $this->error('Indica el ciclo con --ciclo=ID.');
            return self::INVALID;
        }

        $filas = DB::table('calificaciones')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->where('calificaciones.nivel_id', $nivelId)
            ->where('calificaciones.ciclo_escolar_id', (int) $cicloId)
            ->where('calificaciones.es_numerica', true)
            ->whereNotNull('calificaciones.valor_numerico')
            ->whereBetween('calificaciones.valor_numerico', [0, 10])
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->where('materias.participa_en_calificacion_oficial', true)
            ->whereNotNull('materias.campo_formativo_id')
            ->select([
                'calificaciones.inscripcion_id',
                'calificaciones.periodo_id',
                'calificaciones.ciclo_escolar_id',
                'calificaciones.nivel_id',
                'calificaciones.grado_id',
                'calificaciones.grupo_id',
                'calificaciones.generacion_id',
                'materias.campo_formativo_id',
                'calificaciones.valor_numerico',
            ])
            ->get()
            ->groupBy(fn ($fila) => implode('|', [
                $fila->periodo_id,
                $fila->inscripcion_id,
                $fila->campo_formativo_id,
            ]));

        if ($filas->isEmpty()) {
            $this->warn('No se encontraron calificaciones internas para reconstruir.');
            return self::SUCCESS;
        }

        $aplicar = (bool) $this->option('aplicar');
        $creadas = 0;
        $omitidas = 0;

        foreach ($filas as $items) {
            $primero = $items->first();
            $promedio = PromedioExcel::calcular($items->pluck('valor_numerico'));

            if ($promedio === null) {
                $omitidas++;
                continue;
            }

            $trayectoriaId = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $primero->inscripcion_id)
                ->where('ciclo_escolar_id', $primero->ciclo_escolar_id)
                ->where('grado_id', $primero->grado_id)
                ->where('grupo_id', $primero->grupo_id)
                ->latest('id')
                ->value('id');

            $this->line(sprintf(
                '%s · alumno %d · periodo %d · campo %d · sugerencia %.2f',
                $aplicar ? 'GUARDANDO' : 'CREARÍA',
                $primero->inscripcion_id,
                $primero->periodo_id,
                $primero->campo_formativo_id,
                $promedio,
            ));

            if (! $aplicar) {
                $creadas++;
                continue;
            }

            CalificacionCampoFormativo::query()->updateOrCreate(
                [
                    'periodo_id' => $primero->periodo_id,
                    'inscripcion_id' => $primero->inscripcion_id,
                    'campo_formativo_id' => $primero->campo_formativo_id,
                ],
                [
                    'trayectoria_academica_id' => $trayectoriaId,
                    'ciclo_escolar_id' => $primero->ciclo_escolar_id,
                    'nivel_id' => $primero->nivel_id,
                    'grado_id' => $primero->grado_id,
                    'grupo_id' => $primero->grupo_id,
                    'generacion_id' => $primero->generacion_id,
                    'calificacion_sugerida' => $promedio,
                    'calificacion_oficial' => null,
                    'confirmada' => false,
                    'es_reconstruida' => true,
                    'confirmada_por' => null,
                    'confirmada_at' => null,
                ]
            );

            $creadas++;
        }

        $this->newLine();
        $this->info("Sugerencias procesadas: {$creadas}. Omitidas: {$omitidas}.");

        if (! $aplicar) {
            $this->comment('Simulación: no se guardó ningún cambio. Usa --aplicar después de revisar.');
        } else {
            $this->comment('Las sugerencias quedaron pendientes de revisión; no se confirmaron como calificación oficial.');
        }

        return self::SUCCESS;
    }
}
