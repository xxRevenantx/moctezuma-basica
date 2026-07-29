<?php

namespace App\Console\Commands;

use App\Services\AcademicIntegrityService;
use App\Services\IntegridadAcademicaService;
use App\Services\SystemNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Throwable;

class CheckAcademicIntegrity extends Command
{
    protected $signature = 'system:integrity';
    protected $description = 'Analiza inconsistencias académicas, sincroniza casos persistentes y actualiza notificaciones.';

    public function handle(
        AcademicIntegrityService $integrity,
        IntegridadAcademicaService $persistentIntegrity,
        SystemNotificationService $notifications,
    ): int {
        $issues = $integrity->analyze();
        $resultado = null;

        try {
            $resultado = $persistentIntegrity->ejecutar(null, 'programado');
            $resumen = (array) ($resultado['resumen'] ?? []);
            $url = Route::has('misrutas.integridad-academica') ? route('misrutas.integridad-academica') : null;

            if ((int) ($resumen['criticos'] ?? 0) > 0) {
                $issues[] = [
                    'key' => 'centro_integridad_criticos',
                    'title' => 'Casos críticos de integridad académica',
                    'description' => 'La bandeja persistente contiene inconsistencias que pueden afectar historial, calificaciones o estatus.',
                    'severity' => 'critical',
                    'count' => (int) $resumen['criticos'],
                    'url' => $url,
                    'samples' => [],
                ];
            }
            if ((int) ($resumen['advertencias'] ?? 0) > 0) {
                $issues[] = [
                    'key' => 'centro_integridad_advertencias',
                    'title' => 'Advertencias de integridad académica',
                    'description' => 'Hay historiales o vínculos que conviene revisar antes del siguiente cierre.',
                    'severity' => 'warning',
                    'count' => (int) $resumen['advertencias'],
                    'url' => $url,
                    'samples' => [],
                ];
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->warn('El centro persistente reportó un error: '.$exception->getMessage());
        }

        $notifications->syncIntegrityIssues($issues);
        $this->info('Revisión terminada. Incidencias generales: '.count($issues).'. Casos académicos detectados: '.(int) ($resultado['detectados'] ?? 0).'.');

        return self::SUCCESS;
    }
}
