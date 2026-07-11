<?php

use Illuminate\Console\Scheduling\Schedule;

test('lottery:sync is scheduled in the draw timezone', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'lottery:sync'));

    expect($events)->not->toBeEmpty();

    foreach ($events as $event) {
        expect((string) $event->timezone)->toBe('America/Sao_Paulo');
    }
});

test('lottery:sync has an hourly evening window and a daily safety net', function () {
    $expressions = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'lottery:sync'))
        ->map(fn ($event) => $event->expression)
        ->values();

    // Hourly at :15, Monday–Saturday (the 20:00–23:59 window is a between()
    // filter evaluated in the event timezone) plus the 09:00 daily recovery run.
    expect($expressions)->toContain('15 * * * 1,2,3,4,5,6')
        ->and($expressions)->toContain('0 9 * * *');
});
