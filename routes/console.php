<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sub-minute frequency requires `php artisan schedule:work` (or an
// equivalent always-running process) — a once-a-minute OS cron tick alone
// cannot fire this. See config/antrian.php.
Schedule::command('antrian:sync')
    ->everyThirtySeconds()
    ->withoutOverlapping();
