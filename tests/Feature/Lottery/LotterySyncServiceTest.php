<?php

use App\Contracts\LotteryResultsProviderContract;
use App\DataTransferObjects\DrawData;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Services\Lottery\LotterySyncService;

test('syncing the same contest twice does not duplicate draws or numbers', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    // Seed contest 3730 locally so the gap to 3731 stays within the inline fill limit.
    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3730,
        'draw_date' => '2026-07-08',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    $service = app(LotterySyncService::class);

    $service->syncLatest($lottery);
    $log = $service->syncLatest($lottery);

    expect($log->status)->toBe('success')
        ->and(LotteryDraw::where('lottery_id', $lottery->id)->where('contest_number', 3731)->count())->toBe(1);

    $draw = LotteryDraw::where('lottery_id', $lottery->id)->where('contest_number', 3731)->first();

    expect($draw->numbers()->count())->toBe(15)
        ->and($draw->prizeResults()->count())->toBe(1);
});

test('a gap larger than the inline limit still syncs the most recent contests as partial', function () {
    config()->set('caixa.backfill_sleep_ms', 0);

    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 100,
        'draw_date' => '2016-01-01',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, new class implements LotteryResultsProviderContract
    {
        public function fetchLatest(string $apiSlug): DrawData
        {
            return makeFakeDraw(3731);
        }

        public function fetchByContest(string $apiSlug, int $contestNumber): DrawData
        {
            return makeFakeDraw($contestNumber);
        }
    });

    $log = app(LotterySyncService::class)->syncLatest($lottery);

    // The latest contest and the 10 before it land; the older gap is left for lottery:backfill.
    expect($log->status)->toBe('partial')
        ->and($log->contests_synced)->toBe(11)
        ->and(LotteryDraw::where('lottery_id', $lottery->id)->where('contest_number', 3731)->exists())->toBeTrue()
        ->and(LotteryDraw::where('lottery_id', $lottery->id)->where('contest_number', 3721)->exists())->toBeTrue()
        ->and(LotteryDraw::where('lottery_id', $lottery->id)->where('contest_number', 101)->exists())->toBeFalse();
});
