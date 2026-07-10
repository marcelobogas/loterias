<?php

use App\Models\Lottery;
use App\Services\Lottery\Strategies\BalancedFilterStrategy;
use App\Services\Lottery\Strategies\RandomStrategy;

test('it returns a game matching an achievable filter', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    $picked = (new BalancedFilterStrategy(new RandomStrategy))->pick($lottery, 15, [
        'filters' => ['min_evens' => 6, 'max_evens' => 8],
    ]);

    $evens = count(array_filter($picked, fn ($n) => $n % 2 === 0));

    expect($picked)->toHaveCount(15)
        ->and($evens)->toBeGreaterThanOrEqual(6)
        ->and($evens)->toBeLessThanOrEqual(8);
});

test('it falls back to the last attempt for an impossible filter instead of failing', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    $picked = (new BalancedFilterStrategy(new RandomStrategy))->pick($lottery, 15, [
        'filters' => ['min_sum' => 999999],
    ]);

    expect($picked)->toHaveCount(15);
});
