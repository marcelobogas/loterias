<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lotofácil draws happen ~20h (Brasília time) Monday through Saturday and the
// result usually lands between 20h and 23h; poll hourly through that window
// so a late result is picked up without hammering the API the rest of the day.
// The timezone() call makes between()/days() evaluate in Brasília time — the
// app timezone is UTC, where 18:00–22:00 would end hours before the draw.
Schedule::command('lottery:sync')
    ->timezone(config('caixa.draw_timezone'))
    ->hourlyAt(15)
    ->between('20:00', '23:59')
    ->days([1, 2, 3, 4, 5, 6])
    ->withoutOverlapping();

// Daily safety net: recovers days where Caixa published late or the
// scheduler wasn't running during the evening window.
Schedule::command('lottery:sync')
    ->timezone(config('caixa.draw_timezone'))
    ->dailyAt('09:00')
    ->withoutOverlapping();
