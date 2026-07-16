<?php

namespace App\Services;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemHealthService
{
    /** @return array<string,array<string,mixed>> */
    public function inspect(): array
    {
        $database = ['ok' => false, 'value' => 'Sin conexión'];
        try {
            DB::connection()->getPdo();
            $database = ['ok' => true, 'value' => DB::connection()->getDatabaseName()];
        } catch (Throwable $exception) {
            $database['detail'] = $exception->getMessage();
        }

        $storagePath = storage_path('app');
        $free = @disk_free_space($storagePath);
        $lastBackup = Schema::hasTable('system_backups')
            ? SystemBackup::query()->where('status', 'completed')->latest('finished_at')->first()
            : null;
        $backupDisk = (string) config('system.backup_disk', 'local');
        $backupDiskOk = true;
        $backupDiskDetail = null;
        try {
            Storage::disk($backupDisk);
        } catch (Throwable $exception) {
            $backupDiskOk = false;
            $backupDiskDetail = $exception->getMessage();
        }

        return [
            'application' => [
                'ok' => true,
                'value' => app()->version().' / PHP '.PHP_VERSION,
                'detail' => app()->environment(),
            ],
            'database' => $database,
            'storage' => [
                'ok' => is_dir($storagePath) && is_writable($storagePath),
                'value' => is_writable($storagePath) ? 'Escritura disponible' : 'Sin permisos de escritura',
                'detail' => is_numeric($free) ? $this->bytes((int) $free).' libres' : null,
            ],
            'queue' => [
                'ok' => true,
                'value' => (string) config('queue.default'),
                'detail' => 'En producción configura cron y queue worker.',
            ],
            'mail' => [
                'ok' => true,
                'value' => (string) config('mail.default'),
                'detail' => config('mail.from.address'),
            ],
            'backup' => [
                'ok' => $backupDiskOk && (bool) $lastBackup,
                'value' => $lastBackup?->finished_at?->diffForHumans() ?? 'Sin respaldo automático',
                'detail' => $backupDiskDetail ?: ($backupDisk.' · '.($lastBackup?->path ?? 'sin archivos verificados')),
            ],
        ];
    }

    private function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $value = max(0, $bytes);
        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1).' '.$units[$index];
    }
}
