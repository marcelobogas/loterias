<?php

namespace App\Services\Lottery;

use App\Models\Lottery;
use App\Models\LotteryDrawNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * All queries here are keyed against `lottery_draw_numbers`, which
 * denormalizes `lottery_id` onto every row precisely so these can run
 * without joining `lottery_draws` on every call (see migration comment).
 *
 * Every result is cached with the latest known contest number baked into
 * the cache key, so a new synced draw invalidates it automatically without
 * needing an explicit cache-clear anywhere. Cache values are kept as plain
 * arrays (never Collections/stdClass) because Laravel 13's database cache
 * store refuses to unserialize arbitrary objects by default
 * (config/cache.php `serializable_classes`) — arrays are exempt, so we
 * convert on the way in and rewrap with collect() on the way out.
 */
class LotteryStatisticsService
{
    /**
     * @return Collection<int, int> number => times drawn
     */
    public function frequencyTable(Lottery $lottery, ?int $window = null): Collection
    {
        return collect(Cache::remember($this->cacheKey($lottery, 'frequency', $window), now()->addDay(), function () use ($lottery, $window) {
            $counts = LotteryDrawNumber::query()
                ->where('lottery_id', $lottery->id)
                ->when(
                    $drawIds = $this->windowedDrawIds($lottery, $window),
                    fn ($query) => $query->whereIn('lottery_draw_id', $drawIds)
                )
                ->selectRaw('number, count(*) as total')
                ->groupBy('number')
                ->pluck('total', 'number');

            return collect(range(1, $lottery->universe_size))
                ->mapWithKeys(fn (int $number) => [$number => (int) $counts->get($number, 0)])
                ->all();
        }));
    }

    /**
     * Draws since each number's last appearance. Numbers never drawn in the
     * recorded history (should only happen with very little data) are
     * omitted rather than given a made-up delay value.
     *
     * @return Collection<int, int> number => delay, sorted most delayed first
     */
    public function delayTable(Lottery $lottery): Collection
    {
        return collect(Cache::remember($this->cacheKey($lottery, 'delay', null), now()->addDay(), function () use ($lottery) {
            $latestContest = (int) ($lottery->draws()->max('contest_number') ?? 0);

            $lastSeen = LotteryDrawNumber::query()
                ->join('lottery_draws', 'lottery_draws.id', '=', 'lottery_draw_numbers.lottery_draw_id')
                ->where('lottery_draw_numbers.lottery_id', $lottery->id)
                ->selectRaw('lottery_draw_numbers.number, max(lottery_draws.contest_number) as last_contest')
                ->groupBy('lottery_draw_numbers.number')
                ->pluck('last_contest', 'number');

            $delays = [];

            foreach (range(1, $lottery->universe_size) as $number) {
                $last = $lastSeen->get($number);

                if ($last !== null) {
                    $delays[$number] = $latestContest - (int) $last;
                }
            }

            arsort($delays, SORT_NUMERIC);

            return $delays;
        }));
    }

    /**
     * Histogram of the sum of the 15 drawn numbers, bucketed for a bar chart.
     *
     * @return Collection<int, int> bucket start (inclusive) => draw count
     */
    public function sumDistribution(Lottery $lottery, ?int $window = null, int $bucketSize = 10): Collection
    {
        return collect(Cache::remember($this->cacheKey($lottery, "sum_dist:{$bucketSize}", $window), now()->addDay(), function () use ($lottery, $window, $bucketSize) {
            $sums = LotteryDrawNumber::query()
                ->where('lottery_id', $lottery->id)
                ->when(
                    $drawIds = $this->windowedDrawIds($lottery, $window),
                    fn ($query) => $query->whereIn('lottery_draw_id', $drawIds)
                )
                ->selectRaw('lottery_draw_id, sum(number) as total')
                ->groupBy('lottery_draw_id')
                ->pluck('total');

            return $this->tallyIntoBuckets(
                $sums->map(fn ($sum) => (int) (floor($sum / $bucketSize) * $bucketSize))->all()
            );
        }));
    }

    /**
     * How many of the 15 drawn numbers were even, per draw.
     *
     * @return Collection<int, int> even-count => number of draws with that count
     */
    public function parityDistribution(Lottery $lottery, ?int $window = null): Collection
    {
        return collect(Cache::remember($this->cacheKey($lottery, 'parity_dist', $window), now()->addDay(), function () use ($lottery, $window) {
            $evenCounts = LotteryDrawNumber::query()
                ->where('lottery_id', $lottery->id)
                ->when(
                    $drawIds = $this->windowedDrawIds($lottery, $window),
                    fn ($query) => $query->whereIn('lottery_draw_id', $drawIds)
                )
                ->whereRaw('number % 2 = 0')
                ->selectRaw('lottery_draw_id, count(*) as evens')
                ->groupBy('lottery_draw_id')
                ->pluck('evens');

            return $this->tallyIntoBuckets($evenCounts->map(fn ($evens) => (int) $evens)->all());
        }));
    }

    /**
     * @param  int[]  $values
     * @return array<int, int> value => how many times it occurred, sorted by value
     */
    private function tallyIntoBuckets(array $values): array
    {
        $buckets = [];

        foreach ($values as $value) {
            $buckets[$value] = ($buckets[$value] ?? 0) + 1;
        }

        ksort($buckets, SORT_NUMERIC);

        return $buckets;
    }

    /**
     * Most frequent number pairs drawn together.
     *
     * @return Collection<int, array{number_a: int, number_b: int, total: int}>
     */
    public function topCoOccurringPairs(Lottery $lottery, ?int $window = null, int $limit = 15): Collection
    {
        return collect(Cache::remember($this->cacheKey($lottery, "co_occurrence:{$limit}", $window), now()->addDay(), function () use ($lottery, $window, $limit) {
            return DB::table('lottery_draw_numbers as a')
                ->join('lottery_draw_numbers as b', function ($join) {
                    $join->on('a.lottery_draw_id', '=', 'b.lottery_draw_id')
                        ->whereColumn('a.number', '<', 'b.number');
                })
                ->where('a.lottery_id', $lottery->id)
                ->when(
                    $drawIds = $this->windowedDrawIds($lottery, $window),
                    fn ($query) => $query->whereIn('a.lottery_draw_id', $drawIds)
                )
                ->selectRaw('a.number as number_a, b.number as number_b, count(*) as total')
                ->groupBy('a.number', 'b.number')
                ->orderByDesc('total')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => [
                    'number_a' => (int) $row->number_a,
                    'number_b' => (int) $row->number_b,
                    'total' => (int) $row->total,
                ])
                ->all();
        }));
    }

    /**
     * @return int[]|null Draw ids for the last $window contests, or null for "all history".
     */
    private function windowedDrawIds(Lottery $lottery, ?int $window): ?array
    {
        if ($window === null) {
            return null;
        }

        return $lottery->draws()
            ->orderByDesc('contest_number')
            ->limit($window)
            ->pluck('id')
            ->all();
    }

    private function cacheKey(Lottery $lottery, string $type, ?int $window): string
    {
        $latestContest = $lottery->draws()->max('contest_number') ?? 0;

        return "lottery:{$lottery->id}:stats:{$type}:".($window ?? 'all').":{$latestContest}";
    }
}
