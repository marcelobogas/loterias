<?php

use App\Contracts\LotteryResultsProviderContract;
use App\DataTransferObjects\DrawData;
use App\Enums\LotteryFreshnessEnum;
use App\Exceptions\Lottery\LotteryApiUnavailableException;
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

test('checkFreshness returns UpToDate when the local latest contest matches the live API and no draw is overdue', function () {
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
        'contest_number' => 3731,
        'draw_date' => '2026-07-09',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBe(LotteryFreshnessEnum::UpToDate);
});

test('checkFreshness returns Behind when the live API has a newer contest', function () {
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
        'contest_number' => 3731,
        'draw_date' => '2026-07-09',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3733)));

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBe(LotteryFreshnessEnum::Behind);
});

test('checkFreshness returns AwaitingCaixa when a scheduled draw day already passed the cutoff hour but Caixa has not published it', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
        'draw_days_of_week' => [1, 2, 3, 4, 5, 6],
    ]);

    // 2026-07-10 is a Friday; the next scheduled draw day is Saturday 07-11.
    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => '2026-07-10',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    // 23:30 UTC on 07-11 = 20:30 in Brasília — Saturday's draw cutoff already passed.
    $this->travelTo('2026-07-11 23:30:00');

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBe(LotteryFreshnessEnum::AwaitingCaixa);
});

test('checkFreshness returns UpToDate before the next scheduled draw day reaches its cutoff hour', function () {
    $lottery = Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
        'draw_days_of_week' => [1, 2, 3, 4, 5, 6],
    ]);

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => '2026-07-10',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    // 20:00 UTC on 07-11 = 17:00 in Brasília — Saturday's draw hasn't happened yet.
    $this->travelTo('2026-07-11 20:00:00');

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBe(LotteryFreshnessEnum::UpToDate);
});

test('checkFreshness returns null when there are no local draws yet', function () {
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

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBeNull();
});

test('checkFreshness returns null when the lottery has no caixa_api_slug', function () {
    $lottery = Lottery::create([
        'slug' => 'custom',
        'name' => 'Loteria customizada',
        'caixa_api_slug' => null,
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 1,
        'draw_date' => '2026-07-09',
        'source' => 'api',
    ]);

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBeNull();
});

test('checkFreshness returns null and does not throw when the Caixa API is unavailable', function () {
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
        'contest_number' => 3731,
        'draw_date' => '2026-07-09',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, new class implements LotteryResultsProviderContract
    {
        public function fetchLatest(string $apiSlug): DrawData
        {
            throw new LotteryApiUnavailableException('down');
        }

        public function fetchByContest(string $apiSlug, int $contestNumber): DrawData
        {
            throw new LotteryApiUnavailableException('down');
        }
    });

    expect(app(LotterySyncService::class)->checkFreshness($lottery))->toBeNull();
});
