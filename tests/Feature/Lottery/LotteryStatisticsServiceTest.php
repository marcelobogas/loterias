<?php

use App\Enums\DrawSourceEnum;
use App\Models\Lottery;
use App\Services\Lottery\LotteryDrawPersister;
use App\Services\Lottery\LotteryStatisticsService;
use Illuminate\Support\Collection;

function statsLottery(): Lottery
{
    return Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);
}

function seedStatsDraws(Lottery $lottery): void
{
    $persister = app(LotteryDrawPersister::class);

    foreach ([[1, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]], [2, [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]]] as [$contest, $numbers]) {
        $persister->persist($lottery, makeFakeDraw($contest), DrawSourceEnum::Api);
        $draw = $lottery->draws()->where('contest_number', $contest)->first();
        $draw->numbers()->delete();
        $draw->numbers()->insert(collect($numbers)->map(fn ($n) => [
            'lottery_draw_id' => $draw->id,
            'lottery_id' => $lottery->id,
            'number' => $n,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }
}

test('statistics survive a cache write/read round trip (regression: Laravel 13 blocks unserializing objects by default)', function () {
    // The "array" test cache store never serializes anything, so it can't
    // catch the __PHP_Incomplete_Class regression this test guards against.
    config(['cache.default' => 'database']);

    $lottery = statsLottery();
    seedStatsDraws($lottery);

    $service = app(LotteryStatisticsService::class);

    foreach (range(1, 2) as $attempt) {
        $frequency = $service->frequencyTable($lottery);
        $delay = $service->delayTable($lottery);
        $sums = $service->sumDistribution($lottery);
        $parity = $service->parityDistribution($lottery);
        $pairs = $service->topCoOccurringPairs($lottery);

        expect($frequency)->toBeInstanceOf(Collection::class)
            ->and($frequency->get(3))->toBe(2)
            ->and($delay)->toBeInstanceOf(Collection::class)
            ->and($sums)->toBeInstanceOf(Collection::class)
            ->and($parity)->toBeInstanceOf(Collection::class)
            ->and($pairs)->toBeInstanceOf(Collection::class)
            ->and($pairs->first())->toBeArray()
            ->and($pairs->first())->toHaveKeys(['number_a', 'number_b', 'total']);
    }
});
