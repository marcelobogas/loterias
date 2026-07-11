<?php

namespace App\Services\Lottery;

use App\Models\Lottery;
use App\Models\LotteryDrawPrizeResult;
use Illuminate\Support\Facades\Cache;

/**
 * Exact combinatorics for "choose K of N" lotteries. Nothing here predicts
 * draws — every probability assumes the draw is uniform and independent,
 * which is also what the randomness test on the dashboard verifies.
 *
 * Prize estimates for the shared tiers (e.g. 14/15 hits on Lotofácil) use
 * the historical MEDIAN of `lottery_draw_prize_results`, not the mean:
 * accumulated jackpots produce outliers that would inflate the mean badly.
 */
class LotteryMathService
{
    /**
     * P(exactly $hits of the drawn numbers appear in a bet of $betSize
     * numbers), hypergeometric: C(d,h)·C(N−d, m−h) / C(N,m).
     */
    public function hitProbability(int $universe, int $drawnCount, int $betSize, int $hits): float
    {
        if ($hits > $drawnCount || $hits > $betSize || $betSize - $hits > $universe - $drawnCount) {
            return 0.0;
        }

        return $this->combinations($drawnCount, $hits)
            * $this->combinations($universe - $drawnCount, $betSize - $hits)
            / $this->combinations($universe, $betSize);
    }

    /**
     * @return array<int, float> hits => probability, for 0..min(drawn, betSize)
     */
    public function hitDistribution(int $universe, int $drawnCount, int $betSize): array
    {
        $distribution = [];

        foreach (range(0, min($drawnCount, $betSize)) as $hits) {
            $distribution[$hits] = $this->hitProbability($universe, $drawnCount, $betSize, $hits);
        }

        return $distribution;
    }

    /**
     * Expected gross return (R$) of a single bet of $betSize numbers,
     * using the historical median prize of each tier. Bets larger than the
     * minimum are expanded into their embedded simple bets: a bet with H
     * total hits contains C(H,j)·C(m−H, s−j) simple bets with exactly j
     * hits, and each is paid independently (this is how Caixa settles them).
     *
     * @return array{expectedReturn: float, perTier: array<int, float>, prizeEstimates: array<int, float>}|null
     *                                                                                                          null when there is no prize history to estimate from.
     */
    public function expectedValue(Lottery $lottery, int $betSize): ?array
    {
        $prizes = $this->tierPrizeEstimates($lottery);

        if ($prizes === []) {
            return null;
        }

        $universe = (int) $lottery->universe_size;
        $drawn = (int) $lottery->numbers_drawn;
        $simpleBetSize = (int) $lottery->min_numbers_per_game;

        $perTier = array_fill_keys(array_keys($prizes), 0.0);

        foreach ($this->hitDistribution($universe, $drawn, $betSize) as $totalHits => $probability) {
            if ($probability <= 0.0) {
                continue;
            }

            foreach ($prizes as $tierHits => $prize) {
                $winningSubBets = $this->combinations($totalHits, $tierHits)
                    * $this->combinations($betSize - $totalHits, $simpleBetSize - $tierHits);

                if ($winningSubBets > 0) {
                    $perTier[$tierHits] += $probability * $winningSubBets * $prize;
                }
            }
        }

        return [
            'expectedReturn' => array_sum($perTier),
            'perTier' => $perTier,
            'prizeEstimates' => $prizes,
        ];
    }

    /**
     * Median historical prize per tier, over contests that actually had
     * winners (a tier with zero winners records R$ 0,00, but a bettor who
     * had won it would not have received zero — the pot would have been his).
     *
     * @return array<int, float> tier hits => median prize
     */
    public function tierPrizeEstimates(Lottery $lottery): array
    {
        $latestContest = $lottery->draws()->max('contest_number') ?? 0;

        return Cache::remember(
            "lottery:{$lottery->id}:math:tier_prizes:{$latestContest}",
            now()->addDay(),
            function () use ($lottery) {
                $estimates = [];

                foreach ($lottery->prizeTiers()->orderBy('hits')->get() as $tier) {
                    $amounts = LotteryDrawPrizeResult::query()
                        ->where('lottery_prize_tier_id', $tier->id)
                        ->where('winners_count', '>', 0)
                        ->pluck('prize_amount')
                        ->map(fn ($amount) => (float) $amount)
                        ->sort()
                        ->values();

                    if ($amounts->isEmpty()) {
                        continue;
                    }

                    $middle = intdiv($amounts->count(), 2);
                    $estimates[(int) $tier->hits] = $amounts->count() % 2 === 1
                        ? $amounts[$middle]
                        : ($amounts[$middle - 1] + $amounts[$middle]) / 2;
                }

                return $estimates;
            }
        );
    }

    /**
     * Upper-tail p-value of a chi-square statistic via the Wilson–Hilferty
     * cube-root normal approximation: (X²/k)^(1/3) is ~Normal with mean
     * 1−2/(9k) and variance 2/(9k). Accurate to ~1e-3 for k ≥ 10, which is
     * plenty for a "consistent with uniform? yes/no" verdict.
     */
    public function chiSquarePValue(float $chiSquare, int $degreesOfFreedom): float
    {
        if ($chiSquare <= 0.0) {
            return 1.0;
        }

        $k = $degreesOfFreedom;
        $z = (pow($chiSquare / $k, 1 / 3) - (1 - 2 / (9 * $k))) / sqrt(2 / (9 * $k));

        return 1 - $this->standardNormalCdf($z);
    }

    /**
     * Abramowitz & Stegun 7.1.26 approximation of Φ(z), |error| < 1.5e-7.
     */
    private function standardNormalCdf(float $z): float
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $density = exp(-$z * $z / 2) / sqrt(2 * M_PI);
        $poly = $t * (0.319381530 + $t * (-0.356563782 + $t * (1.781477937 + $t * (-1.821255978 + $t * 1.330274429))));
        $cdf = 1 - $density * $poly;

        return $z >= 0 ? $cdf : 1 - $cdf;
    }

    /**
     * C(n, k) as float (values stay far below float precision limits for
     * lottery-sized inputs; C(25,15) = 3,268,760).
     */
    public function combinations(int $n, int $k): float
    {
        if ($k < 0 || $k > $n) {
            return 0.0;
        }

        $k = min($k, $n - $k);
        $result = 1.0;

        for ($i = 1; $i <= $k; $i++) {
            $result = $result * ($n - $k + $i) / $i;
        }

        return round($result);
    }
}
