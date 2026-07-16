<?php

namespace App\Console\Commands;

use App\Services\SystemBackupService;
use Illuminate\Console\Command;

class CreateSystemBackup extends Command
{
    protected $signature = 'system:backup';
    protected $description = 'Crea un respaldo académico automático y verifica sus archivos.';

    public function handle(SystemBackupService $service): int
    {
        $backup = $service->create();

        if ($backup->status !== 'completed') {
            $this->error($backup->error ?: 'El respaldo no pudo completarse.');
            return self::FAILURE;
        }

        $this->info('Respaldo creado: '.$backup->path);
        return self::SUCCESS;
    }
}
