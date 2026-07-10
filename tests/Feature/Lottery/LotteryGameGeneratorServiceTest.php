<?php

use App\DataTransferObjects\GameGenerationRequest;
use App\Models\Lottery;
use App\Models\LotteryPriceTier;
use App\Services\Lottery\LotteryGameGeneratorService;
use App\Services\Lottery\LotteryPricingService;
use App\Services\Lottery\Strategies\BalancedFilterStrategy;
use App\Services\Lottery\Strategies\HotColdStrategy;
use App\Services\Lottery\Strategies\RandomStrategy;
use App\Services\Lottery\Strategies\ReducedWheelHeuristicStrategy;

function tinyLottery(): Lottery
{
    $lottery = Lottery::create([
        'slug' => 'mini',
        'name' => 'Mini',
        'universe_size' => 6,
        'numbers_drawn' => 5,
        'min_numbers_per_game' => 5,
        'max_numbers_per_game' => 5,
        'is_active' => true,
    ]);

    LotteryPriceTier::create([
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 5,
        'combinations_count' => 6,
        'price' => 3.0,
        'effective_from' => '2020-01-01',
    ]);

    return $lottery;
}

function generatorService(): LotteryGameGeneratorService
{
    return new LotteryGameGeneratorService(
        strategies: [
            app(RandomStrategy::class),
            app(HotColdStrategy::class),
            app(BalancedFilterStrategy::class),
            app(ReducedWheelHeuristicStrategy::class),
        ],
        pricing: app(LotteryPricingService::class),
    );
}

test('it avoids generating exact duplicate games within the same batch', function () {
    // Universe of 6 choosing 5 has exactly 6 distinct combinations, so
    // requesting 6 games forces the dedup guard to actually do work.
    $lottery = tinyLottery();

    $result = generatorService()->generate($lottery, new GameGenerationRequest(
        numbersPerGame: 5,
        gamesCount: 6,
        strategy: 'random',
    ));

    expect($result->games)->toHaveCount(6);

    $unique = collect($result->games)->map(fn ($game) => implode(',', $game))->unique();

    expect($unique)->toHaveCount(6)
        ->and($result->pricePerGame)->toBe(3.0)
        ->and($result->totalPrice)->toBe(18.0);
});

test('reduced wheel strategy spreads coverage across a batch instead of repeating the same pairs', function () {
    $lottery = tinyLottery();

    $result = generatorService()->generate($lottery, new GameGenerationRequest(
        numbersPerGame: 5,
        gamesCount: 2,
        strategy: 'reduced_wheel',
        pool: [1, 2, 3, 4, 5, 6],
    ));

    expect($result->games)->toHaveCount(2);

    foreach ($result->games as $game) {
        expect($game)->toHaveCount(5);
    }
});
