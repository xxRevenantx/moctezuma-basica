<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class DocumentConfigurationService
{
    public function defaults(): array
    {
        return [
            'institution_name' => 'Centro Universitario Moctezuma A.C.',
            'primary_color' => '#006492',
            'secondary_color' => '#88AC2E',
            'place' => 'Cd. Altamirano, Guerrero',
            'margin_top' => 15,
            'margin_right' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'show_cycle' => true,
            'ai_mode' => 'suggest_only',
            'notification_channels' => ['system'],
            'backup_retention_days' => 30,
        ];
    }

    public function get(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return $this->defaults();
        }

        return array_replace(
            $this->defaults(),
            SystemSetting::value('documents', 'central', []) ?? []
        );
    }

    public function save(array $settings, ?int $userId): array
    {
        $value = array_replace($this->defaults(), $settings);

        SystemSetting::query()->updateOrCreate(
            ['group' => 'documents', 'key' => 'central'],
            ['value' => $value, 'type' => 'json', 'updated_by' => $userId]
        );

        return $value;
    }
}
