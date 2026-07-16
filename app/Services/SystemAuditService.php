<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemAuditService
{
    private const SENSITIVE = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'api_token', 'token', 'secret',
    ];

    public function recordModel(Model $model, string $action): void
    {
        if (! Schema::hasTable('system_audits') || str_starts_with($model::class, 'App\\Models\\System')) {
            return;
        }

        try {
            $old = $action === 'created' ? [] : $this->sanitize($model->getOriginal());
            $new = in_array($action, ['deleted', 'force_deleted'], true)
                ? []
                : $this->sanitize($model->getAttributes());

            DB::table('system_audits')->insert([
                'user_id' => auth()->id(),
                'action' => $action,
                'module' => $this->moduleFor($model),
                'auditable_type' => $model::class,
                'auditable_id' => $model->getKey(),
                'route' => app()->runningInConsole() ? 'console' : request()->route()?->getName(),
                'ip' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : mb_substr((string) request()->userAgent(), 0, 1000),
                'old_values' => $old === [] ? null : json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => $new === [] ? null : json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'metadata' => json_encode([
                    'model' => class_basename($model),
                    'connection' => $model->getConnectionName(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function record(string $action, string $module, array $metadata = []): void
    {
        if (! Schema::hasTable('system_audits')) {
            return;
        }

        try {
            DB::table('system_audits')->insert([
                'user_id' => auth()->id(),
                'action' => $action,
                'module' => $module,
                'auditable_type' => null,
                'auditable_id' => null,
                'route' => app()->runningInConsole() ? 'console' : request()->route()?->getName(),
                'ip' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : mb_substr((string) request()->userAgent(), 0, 1000),
                'old_values' => null,
                'new_values' => null,
                'metadata' => json_encode($this->sanitize($metadata), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sanitize(array $values): array
    {
        foreach (self::SENSITIVE as $key) {
            if (Arr::has($values, $key)) {
                Arr::set($values, $key, '[PROTEGIDO]');
            }
        }

        return $values;
    }

    private function moduleFor(Model $model): string
    {
        return match (true) {
            str_contains($model::class, 'Inscripcion'), str_contains($model::class, 'Tutor') => 'alumnos',
            str_contains($model::class, 'Persona'), str_contains($model::class, 'Profesor'), str_contains($model::class, 'Director') => 'personal',
            str_contains($model::class, 'Calificacion') => 'calificaciones',
            str_contains($model::class, 'Documento'), str_contains($model::class, 'Constancia'), str_contains($model::class, 'Oficio') => 'documentos',
            str_contains($model::class, 'Horario') => 'horarios',
            default => 'academico',
        };
    }
}
