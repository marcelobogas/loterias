<?php

use App\Models\Lottery;
use App\Services\Lottery\Strategies\UnpopularNumbersStrategy;
use Database\Seeders\LotterySeeder;

test('the popularity score ranks human-looking spread games above clustered ones', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $strategy = app(UnpopularNumbersStrategy::class);

    // Spread out, no full slip lines, balanced parity: the shape people play.
    $spread = [1, 3, 5, 7, 8, 9, 11, 13, 15, 17, 18, 19, 21, 23, 25];

    // Clustered with two complete slip rows and a long run: the shape people avoid.
    $clustered = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16];

    expect($strategy->popularityScore($spread, $lottery))
        ->toBeGreaterThan($strategy->popularityScore($clustered, $lottery));
});

test('iconic combinations and copies of the last draw are hard-penalized', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    $lastDraw = [2, 4, 6, 8, 10, 12, 13, 14, 17, 19, 20, 21, 23, 24, 25];
    seedDrawWithNumbers($lottery, 1, $lastDraw);

    $strategy = app(UnpopularNumbersStrategy::class);

    expect($strategy->popularityScore(range(1, 15), $lottery))->toBeGreaterThanOrEqual(25.0)
        ->and($strategy->popularityScore($lastDraw, $lottery))->toBeGreaterThanOrEqual(25.0);
});

test('picks are valid games of the requested size', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $strategy = app(UnpopularNumbersStrategy::class);

    foreach (range(1, 5) as $ignored) {
        $picked = $strategy->pick($lottery, 15, []);
        $sorted = $picked;
        sort($sorted);

        expect($picked)->toHaveCount(15)
            ->and(count(array_unique($picked)))->toBe(15)
            ->and($picked)->toBe($sorted)
            ->and(min($picked))->toBeGreaterThanOrEqual(1)
            ->and(max($picked))->toBeLessThanOrEqual(25);
    }
});
