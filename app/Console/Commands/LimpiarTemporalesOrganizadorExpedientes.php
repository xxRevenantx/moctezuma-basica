<?php

namespace App\Console\Commands;

use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Console\Command;

class LimpiarTemporalesOrganizadorExpedientes extends Command
{
    protected $signature = 'expedientes:limpiar-temporales-organizador';

    protected $description = 'Elimina vistas previas y copias temporales vencidas del organizador de expedientes.';

    public function handle(OrganizadorExpedienteService $servicio): int
    {
        $eliminados = $servicio->limpiarTemporales();
        $this->components->info("Se eliminaron {$eliminados} archivo(s) temporal(es).");

        return self::SUCCESS;
    }
}
