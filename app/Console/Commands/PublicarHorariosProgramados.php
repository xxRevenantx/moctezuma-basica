<?php

namespace App\Console\Commands;

use App\Services\HorarioVersionService;
use Illuminate\Console\Command;

class PublicarHorariosProgramados extends Command
{
    protected $signature = 'horarios:publicar-programados';
    protected $description = 'Publica las versiones de horario cuya fecha programada ya se cumplió.';

    public function handle(HorarioVersionService $service): int
    {
        $total = $service->publicarProgramadas();
        $this->info("Versiones publicadas: {$total}");
        return self::SUCCESS;
    }
}
