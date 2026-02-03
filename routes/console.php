<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lce:expiry-watchdog')->dailyAt('03:00');

Artisan::command('lce:import-tenders', function () {
    \App\Jobs\ImportTenders::dispatchSync();
})->purpose('Import tenders from external sources (placeholder)');

Schedule::command('lce:send-notifications')->everyThirtyMinutes();
