<?php

namespace App\Services;

use App\Exports\Respaldos\RespaldoAcademicoExport;
use App\Models\SystemBackup;
use App\Support\RespaldoAcademico;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class SystemBackupService
{
    public function create(?int $userId = null): SystemBackup
    {
        $disk = (string) config('system.backup_disk', 'local');

        $record = SystemBackup::query()->create([
            'type' => 'academic_full',
            'status' => 'running',
            'disk' => $disk,
            'started_at' => now(),
            'created_by' => $userId,
        ]);

        $folder = 'backups/system/'.now()->format('Y-m-d_H-i-s').'_'.str_pad((string) $record->id, 6, '0', STR_PAD_LEFT);

        try {
            $files = [];
            foreach ([RespaldoAcademico::TIPO_ALUMNOS, RespaldoAcademico::TIPO_CALIFICACIONES] as $type) {
                $path = $folder.'/'.$type.'.xlsx';
                Excel::store(new RespaldoAcademicoExport($type), $path, $disk);

                if (! Storage::disk($disk)->exists($path)) {
                    throw new \RuntimeException("No se pudo confirmar el archivo {$path}.");
                }

                $files[$type] = [
                    'path' => $path,
                    'size' => Storage::disk($disk)->size($path),
                    'sha256' => hash('sha256', Storage::disk($disk)->get($path)),
                ];
            }

            $manifestPath = $folder.'/manifest.json';
            Storage::disk($disk)->put($manifestPath, json_encode([
                'generated_at' => now()->toIso8601String(),
                'application' => config('app.name'),
                'files' => $files,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $totalSize = collect($files)->sum('size') + Storage::disk($disk)->size($manifestPath);
            $record->update([
                'status' => 'completed',
                'path' => $folder,
                'size_bytes' => $totalSize,
                'sha256' => hash('sha256', json_encode($files)),
                'finished_at' => now(),
                'details' => ['files' => $files, 'manifest' => $manifestPath],
            ]);

            $this->cleanupOldBackups();
        } catch (Throwable $exception) {
            report($exception);
            $record->update([
                'status' => 'failed',
                'path' => $folder,
                'finished_at' => now(),
                'error' => $exception->getMessage(),
            ]);
        }

        return $record->fresh();
    }

    public function cleanupOldBackups(?int $days = null): int
    {
        $days ??= (int) config('system.backup_retention_days', 30);
        $deleted = 0;

        SystemBackup::query()
            ->where('created_at', '<', now()->subDays(max(1, $days)))
            ->whereIn('status', ['completed', 'failed'])
            ->each(function (SystemBackup $backup) use (&$deleted): void {
                if ($backup->path) {
                    Storage::disk($backup->disk ?: 'local')->deleteDirectory($backup->path);
                }
                $backup->delete();
                $deleted++;
            });

        return $deleted;
    }
}
