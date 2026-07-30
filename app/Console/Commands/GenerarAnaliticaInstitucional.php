<?php

namespace App\Console\Commands;

use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Services\AnaliticaInstitucionalService;
use Illuminate\Console\Command;

class GenerarAnaliticaInstitucional extends Command
{
    protected $signature = 'analitica:generar-snapshot
        {--ciclo= : ID del ciclo escolar}
        {--nivel= : ID del nivel educativo}
        {--todos-niveles : Genera una instantánea adicional por cada nivel}';

    protected $description = 'Genera instantáneas de analítica institucional y sincroniza alertas directivas.';

    public function handle(AnaliticaInstitucionalService $service): int
    {
        $cicloId = (int) ($this->option('ciclo') ?: CicloEscolar::query()->where('es_actual', true)->value('id') ?: CicloEscolar::query()->latest('inicio_anio')->value('id'));
        if ($cicloId <= 0) {
            $this->error('No existe un ciclo escolar disponible.');
            return self::FAILURE;
        }

        $nivelId = $this->option('nivel') ? (int) $this->option('nivel') : null;
        $contextos = [['ciclo_escolar_id' => $cicloId, 'nivel_id' => $nivelId]];
        if ($this->option('todos-niveles') && ! $nivelId) {
            foreach (Nivel::query()->orderBy('id')->pluck('id') as $id) {
                $contextos[] = ['ciclo_escolar_id' => $cicloId, 'nivel_id' => (int) $id];
            }
        }

        $barra = $this->output->createProgressBar(count($contextos));
        $barra->start();
        foreach ($contextos as $contexto) {
            $datos = $service->generar($contexto);
            $service->guardarSnapshot($datos, null, 'programado');
            $barra->advance();
        }
        $barra->finish();
        $this->newLine(2);
        $this->info('Instantáneas generadas: '.count($contextos));

        return self::SUCCESS;
    }
}
