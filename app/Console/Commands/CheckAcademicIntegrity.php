<?php

namespace App\Console\Commands;

use App\Services\AcademicIntegrityService;
use App\Services\SystemNotificationService;
use Illuminate\Console\Command;

class CheckAcademicIntegrity extends Command
{
    protected $signature = 'system:integrity';
    protected $description = 'Analiza inconsistencias académicas y actualiza las notificaciones internas.';

    public function handle(AcademicIntegrityService $integrity, SystemNotificationService $notifications): int
    {
        $issues = $integrity->analyze();
        $notifications->syncIntegrityIssues($issues);

        $this->info('Revisión terminada. Incidencias activas: '.count($issues));
        return self::SUCCESS;
    }
}
