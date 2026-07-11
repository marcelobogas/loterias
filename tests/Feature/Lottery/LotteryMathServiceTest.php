<?php

use App\Models\Lottery;
use App\Services\Lottery\LotteryMathService;
use Database\Seeders\LotterySeeder;

test('the hypergeometric probability of 15 hits on a 15-number Lotofácil bet is 1 in 3,268,760', function () {
    $math = new LotteryMathService;

    expect($math->combinations(25, 15))->toBe(3268760.0)
        ->and($math->hitProbability(25, 15, 15, 15))->toEqualWithDelta(1 / 3268760, 1e-12);
});

test('the hit distribution sums to 1 for simple and multi-number bets', function () {
    $math = new LotteryMathService;

    foreach ([15, 16, 18, 20] as $betSize) {
        expect(array_sum($math->hitDistribution(25, 15, $betSize)))->toEqualWithDelta(1.0, 1e-9);
    }
});

test('the chi-square p-value approximation behaves at the known landmarks', function () {
    $math = new LotteryMathService;

    // A statistic equal to its degrees of freedom sits near the median.
    expect($math->chiSquarePValue(24, 24))->toBeGreaterThan(0.4)->toBeLessThan(0.55)
        ->and($math->chiSquarePValue(100, 24))->toBeLessThan(0.0001)
        ->and($math->chiSquarePValue(0, 24))->toBe(1.0);
});

test('the expected value combines exact probabilities with historical prizes', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    seedDrawWithNumbers($lottery, 1, range(1, 15), [
        11 => [100000, 7.0],
        12 => [10000, 14.0],
        13 => [1000, 35.0],
        14 => [100, 1500.0],
        15 => [1, 1000000.0],
    ]);

    $math = app(LotteryMathService::class);
    $result = $math->expectedValue($lottery, 15);

    $manual = 0.0;
    foreach ([11 => 7.0, 12 => 14.0, 13 => 35.0, 14 => 1500.0, 15 => 1000000.0] as $hits => $prize) {
        $manual += $math->hitProbability(25, 15, 15, $hits) * $prize;
    }

    expect($result)->not->toBeNull()
        ->and($result['expectedReturn'])->toEqualWithDelta($manual, 1e-6)
        // A single R$ 3,50 bet returns less than it costs — the house keeps ~57%.
        ->and($result['expectedReturn'])->toBeLessThan(3.50);
});

test('tier prize estimates use the median and ignore contests without winners', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    seedDrawWithNumbers($lottery, 1, range(1, 15), [15 => [1, 100.0]]);
    seedDrawWithNumbers($lottery, 2, range(1, 15), [15 => [1, 900000.0]]);
    seedDrawWithNumbers($lottery, 3, range(1, 15), [15 => [2, 300.0]]);
    seedDrawWithNumbers($lottery, 4, range(1, 15), [15 => [0, 0.0]]);

    $estimates = app(LotteryMathService::class)->tierPrizeEstimates($lottery);

    // Median of 100 / 300 / 900000: the accumulated outlier doesn't drag it up.
    expect($estimates[15])->toBe(300.0);
});

test('expected value is null when there is no prize history', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    expect(app(LotteryMathService::class)->expectedValue($lottery, 15))->toBeNull();
});
