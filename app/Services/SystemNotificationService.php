<?php

namespace App\Services;

use App\Models\SystemNotification;
use Illuminate\Support\Facades\Schema;

class SystemNotificationService
{
    /** @param array<int,array<string,mixed>> $issues */
    public function syncIntegrityIssues(array $issues): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        $activeKeys = [];

        foreach ($issues as $issue) {
            $sourceKey = 'integrity:'.($issue['key'] ?? md5((string) ($issue['title'] ?? 'issue')));
            $activeKeys[] = $sourceKey;

            SystemNotification::query()->updateOrCreate(
                ['user_id' => null, 'source_key' => $sourceKey],
                [
                    'type' => 'integrity',
                    'severity' => $issue['severity'] ?? 'info',
                    'title' => $issue['title'] ?? 'Revisión del sistema',
                    'message' => ($issue['description'] ?? '').' Registros detectados: '.number_format((int) ($issue['count'] ?? 0)).'.',
                    'action_url' => $issue['url'] ?? null,
                    'metadata' => ['count' => $issue['count'] ?? 0, 'samples' => $issue['samples'] ?? []],
                    'dismissed_at' => null,
                    'expires_at' => now()->addDays(7),
                ]
            );
        }

        SystemNotification::query()
            ->where('type', 'integrity')
            ->when($activeKeys !== [], fn ($query) => $query->whereNotIn('source_key', $activeKeys))
            ->update(['dismissed_at' => now()]);
    }

    public function unreadCount(?int $userId): int
    {
        if (! Schema::hasTable('system_notifications')) {
            return 0;
        }

        return SystemNotification::query()
            ->visibleFor($userId)
            ->whereNull('read_at')
            ->count();
    }
}
