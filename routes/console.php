<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sets:import-new')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('sets:translate --new-only --locale=fr')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('sets:translate --new-only --locale=fr --with-scraping')->weeklyOn(0, '03:00')->withoutOverlapping();
