<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('system:backup')->dailyAt((string) config('system.backup_schedule', '02:00'))->withoutOverlapping();
Schedule::command('system:integrity')->dailyAt((string) config('system.integrity_schedule', '06:00'))->withoutOverlapping();
