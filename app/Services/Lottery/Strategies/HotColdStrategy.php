<?php

namespace App\Services\Lottery\Strategies;

use App\Contracts\LotteryStrategyContract;
use App\Models\Lottery;
use App\Services\Lottery\LotteryStatisticsService;

/**
 * Weighted sampling without replacement: numbers with a higher weight are
 * more likely (not guaranteed) to be picked. Context 'bias' selects the
 * weighting source: 'hot' favors more frequent numbers, 'cold' favors
 * numbers that are more overdue.
 */
class HotColdStrategy implements LotteryStrategyContract
{
    public function __construct(
        private readonly LotteryStatisticsService $stats,
    ) {}

    public function key(): string
    {
        return 'hot_cold';
    }

    public function label(): string
    {
        return 'Quente/Frio';
    }

    public function description(): string
    {
        return 'Dá mais peso a números que saíram muito (quentes) ou que estão atrasados (frios) no histórico. Cada sorteio é independente do anterior, então isso não muda sua chance real de acerto — só varia a composição dos jogos.';
    }

    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array
    {
        $bias = $context['bias'] ?? 'hot';

        $source = $bias === 'cold'
            ? $this->stats->delayTable($lottery)
            : $this->stats->frequencyTable($lottery);

        $weights = [];

        foreach (range(1, $lottery->universe_size) as $number) {
            $weights[$number] = max(1, (int) $source->get($number, 1));
        }

        return $this->weightedSampleWithoutReplacement($weights, $numbersPerGame);
    }

    /**
     * @param  array<int, int>  $weights
     * @return int[]
     */
    private function weightedSampleWithoutReplacement(array $weights, int $count): array
    {
        $chosen = [];

        while (count($chosen) < $count && $weights !== []) {
            $total = array_sum($weights);
            $roll = random_int(1, max(1, $total));
            $cumulative = 0;

            foreach ($weights as $number => $weight) {
                $cumulative += $weight;

                if ($roll <= $cumulative) {
                    $chosen[] = $number;
                    unset($weights[$number]);

                    break;
                }
            }
        }

        sort($chosen);

        return $chosen;
    }
}
