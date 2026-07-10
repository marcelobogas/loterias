<?php

use App\Models\Lottery;
use App\Models\LotteryPriceTier;
use App\Services\Lottery\LotteryPricingService;

test('it returns the price for a valid tier and estimates a batch', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    LotteryPriceTier::create([
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'combinations_count' => 1,
        'price' => 3.0,
        'effective_from' => '2024-01-01',
    ]);

    $pricing = new LotteryPricingService;

    expect($pricing->priceFor($lottery, 15))->toBe(3.0)
        ->and($pricing->estimateBatch($lottery, 15, 4))->toBe(12.0);
});

test('it throws when no price tier exists', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    (new LotteryPricingService)->priceFor($lottery, 15);
})->throws(RuntimeException::class);
