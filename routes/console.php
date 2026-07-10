<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lotofácil draws happen ~20h (Brasília time) Monday through Saturday;
// poll hourly through the likely window so a late result is picked up
// without hammering the API the rest of the day.
Schedule::command('lottery:sync')
    ->hourlyAt(15)
    ->between('18:00', '22:00')
    ->days([1, 2, 3, 4, 5, 6])
    ->withoutOverlapping();
