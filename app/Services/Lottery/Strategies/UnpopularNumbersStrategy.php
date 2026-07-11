<?php

namespace App\Services\Lottery\Strategies;

use App\Contracts\LotteryStrategyContract;
use App\Models\Lottery;
use App\Models\LotteryDrawNumber;

/**
 * Picks, among random candidates, the combination least similar to how
 * humans typically fill a bet slip — so that IF it wins one of the shared
 * tiers (14/15 hits), fewer other bettors are likely to split the prize.
 * It does NOT change the probability of hitting anything.
 *
 * Calibrated against this project's own draw history (502 Lotofácil
 * contests, tiers 11/12): the winners ratio w11/w12 — which cancels out
 * sales volume — correlates POSITIVELY with the draw's longest consecutive
 * run (Pearson 0.42) and with complete 5-number grid rows (0.39). Draws
 * that "look clustered" produce relatively fewer high-tier winners, i.e.
 * the betting public spreads its numbers out and avoids runs/lines. This
 * matches the conscious-selection literature (JRSS-A 172(4), 2009).
 *
 * Two regimes the historical data cannot see are handled by hard
 * penalties instead: iconic combinations (a near-perfect 1..15 straight)
 * and replaying the previous draw's numbers, both known to be bet by
 * thousands of players on purpose.
 */
class UnpopularNumbersStrategy implements LotteryStrategyContract
{
    private const CANDIDATES = 40;

    /** @var array<int, int[]> latest draw numbers, memoized per lottery id */
    private array $latestDrawNumbers = [];

    public function __construct(
        private readonly RandomStrategy $random,
    ) {}

    public function key(): string
    {
        return 'unpopular';
    }

    public function label(): string
    {
        return 'Menos populares (anti-divisão)';
    }

    public function description(): string
    {
        return 'Escolhe combinações diferentes das que a maioria das pessoas joga (números espalhados, sem sequências). Não muda a chance de acertar — mas, se acertar 14 ou 15 pontos, tende a dividir o prêmio com menos gente.';
    }

    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array
    {
        $best = null;
        $bestScore = PHP_FLOAT_MAX;

        for ($i = 0; $i < self::CANDIDATES; $i++) {
            $candidate = $this->random->pick($lottery, $numbersPerGame, $context);
            $score = $this->popularityScore($candidate, $lottery);

            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * Higher = more similar to typical human picks (bad for prize sharing).
     *
     * @param  int[]  $numbers  sorted ascending
     */
    public function popularityScore(array $numbers, Lottery $lottery): float
    {
        $score = 0.0;

        // Humans spread their numbers: short runs are the popular shape.
        $maxRun = $this->maxConsecutiveRun($numbers);
        $score += max(0, 5 - $maxRun) * 2;

        // ...and avoid completing rows/columns of the 5×5 slip grid.
        $lines = $this->completeGridLines($numbers, $lottery->universe_size);
        $score += max(0, 2 - $lines) * 2;

        // Humans also balance odd/even counts near the middle.
        $evens = count(array_filter($numbers, fn (int $n) => $n % 2 === 0));
        $half = count($numbers) / 2;
        if (abs($evens - $half) <= 1) {
            $score += 2;
        }

        // Iconic combos the calibration data can't see: near-straight 1..K
        // and near-copies of the previous result are bet en masse.
        if ($maxRun >= count($numbers) - 1) {
            $score += 25;
        }

        $lastDraw = $this->lastDrawNumbers($lottery);
        if ($lastDraw !== [] && count(array_intersect($numbers, $lastDraw)) >= count($numbers) - 1) {
            $score += 25;
        }

        return $score;
    }

    /**
     * @param  int[]  $numbers  sorted ascending
     */
    private function maxConsecutiveRun(array $numbers): int
    {
        $best = $run = 1;

        for ($i = 1, $count = count($numbers); $i < $count; $i++) {
            $run = $numbers[$i] === $numbers[$i - 1] + 1 ? $run + 1 : 1;
            $best = max($best, $run);
        }

        return $best;
    }

    /**
     * Complete rows + columns of the bet slip grid (5 numbers per row for
     * a 25-number universe; generic width of 5 otherwise, matching how
     * Caixa lays out its slips).
     *
     * @param  int[]  $numbers
     */
    private function completeGridLines(array $numbers, int $universeSize): int
    {
        $width = 5;
        $rows = (int) ceil($universeSize / $width);
        $set = array_flip($numbers);
        $lines = 0;

        for ($row = 0; $row < $rows; $row++) {
            $complete = true;
            for ($col = 1; $col <= $width; $col++) {
                $number = $row * $width + $col;
                if ($number > $universeSize || ! isset($set[$number])) {
                    $complete = false;
                    break;
                }
            }
            $lines += $complete ? 1 : 0;
        }

        for ($col = 1; $col <= $width; $col++) {
            $complete = true;
            for ($row = 0; $row < $rows; $row++) {
                $number = $row * $width + $col;
                if ($number > $universeSize || ! isset($set[$number])) {
                    $complete = false;
                    break;
                }
            }
            $lines += $complete ? 1 : 0;
        }

        return $lines;
    }

    /**
     * @return int[]
     */
    private function lastDrawNumbers(Lottery $lottery): array
    {
        return $this->latestDrawNumbers[$lottery->id] ??= (function () use ($lottery) {
            $latest = $lottery->latestDraw();

            return $latest
                ? LotteryDrawNumber::query()->where('lottery_draw_id', $latest->id)->pluck('number')->all()
                : [];
        })();
    }
}
