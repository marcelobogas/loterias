<?php

use App\Models\Lottery;
use App\Services\Lottery\Strategies\RandomStrategy;

test('it picks the right amount of unique numbers within range', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    $picked = (new RandomStrategy)->pick($lottery, 15, []);

    expect($picked)->toHaveCount(15)
        ->and(array_unique($picked))->toHaveCount(15)
        ->and(min($picked))->toBeGreaterThanOrEqual(1)
        ->and(max($picked))->toBeLessThanOrEqual(25)
        ->and($picked)->toBe(collect($picked)->sort()->values()->all());
});
