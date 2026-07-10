<?php

namespace App\Services\Lottery\Strategies;

use App\Contracts\LotteryStrategyContract;
use App\Models\Lottery;

/**
 * Heuristic (not a formally guaranteed) wheel: given a pool of numbers
 * larger than $numbersPerGame, greedily picks the subset that covers the
 * most pairs not yet covered by earlier games in the same batch
 * (context['coveredPairs'], maintained by LotteryGameGeneratorService).
 *
 * This is NOT a mathematically guaranteed covering design — those require
 * solving a genuinely hard combinatorial optimization problem and published
 * wheel tables. This only maximizes pair coverage heuristically across the
 * games generated in one batch, which is a reasonable and honest
 * approximation for an MVP.
 */
class ReducedWheelHeuristicStrategy implements LotteryStrategyContract
{
    public function key(): string
    {
        return 'reduced_wheel';
    }

    public function label(): string
    {
        return 'Fechamento reduzido (heurístico)';
    }

    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array
    {
        $pool = $context['pool'] ?? range(1, $lottery->universe_size);
        $covered = $context['coveredPairs'] ?? [];

        if (count($pool) <= $numbersPerGame) {
            $chosen = $pool;
            sort($chosen);

            return $chosen;
        }

        $remaining = array_values($pool);
        $seedIndex = array_rand($remaining);
        $chosen = [$remaining[$seedIndex]];
        unset($remaining[$seedIndex]);
        $remaining = array_values($remaining);

        while (count($chosen) < $numbersPerGame && $remaining !== []) {
            $bestNumber = null;
            $bestNewPairs = -1;

            foreach ($remaining as $candidate) {
                $newPairs = 0;

                foreach ($chosen as $already) {
                    if (! isset($covered[$this->pairKey($candidate, $already)])) {
                        $newPairs++;
                    }
                }

                if ($newPairs > $bestNewPairs) {
                    $bestNewPairs = $newPairs;
                    $bestNumber = $candidate;
                }
            }

            $chosen[] = $bestNumber;
            $remaining = array_values(array_diff($remaining, [$bestNumber]));
        }

        sort($chosen);

        return $chosen;
    }

    private function pairKey(int $a, int $b): string
    {
        return $a < $b ? "{$a}-{$b}" : "{$b}-{$a}";
    }
}
