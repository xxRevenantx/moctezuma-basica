<?php

return [
    'backup_disk' => env('SYSTEM_BACKUP_DISK', 'local'),
    'backup_retention_days' => (int) env('SYSTEM_BACKUP_RETENTION_DAYS', 30),
    'backup_schedule' => env('SYSTEM_BACKUP_SCHEDULE', '02:00'),
    'integrity_schedule' => env('SYSTEM_INTEGRITY_SCHEDULE', '06:00'),
];
