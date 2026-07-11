<?php

namespace App\Services\Lottery;

use App\DataTransferObjects\GameGenerationRequest;
use App\Models\Lottery;

/**
 * Replays a batch of generated games against real historical draws and
 * compares the observed hit distribution with the exact hypergeometric
 * curve. The whole point is honesty: if a strategy "beats" the curve in a
 * window, that is variance, not edge — and showing both side by side is
 * what makes that visible.
 *
 * Note that history-based strategies (hot/cold) get to "see" the very
 * draws they are tested against here — a lookahead advantage impossible
 * in real play. Even with it, the curve does not shift.
 */
class LotteryBacktestService
{
    public const MAX_GAMES = 10;

    public function __construct(
        private readonly LotteryGameGeneratorService $generator,
        private readonly LotteryMathService $math,
    ) {}

    /**
     * @return array{
     *     games: array<int, int[]>,
     *     drawsTested: int,
     *     observed: array<int, int>,
     *     expected: array<int, float>,
     *     pricePerGame: float,
     *     totalCost: float,
     *     totalReturn: float,
     *     net: float,
     *     returnPerDraw: float
     * }|null null when there is no draw history to test against.
     */
    public function run(Lottery $lottery, GameGenerationRequest $request, ?int $window): ?array
    {
        $draws = $lottery->draws()
            ->with(['numbers', 'prizeResults.prizeTier'])
            ->orderByDesc('contest_number')
            ->when($window !== null, fn ($query) => $query->limit($window))
            ->get();

        if ($draws->isEmpty()) {
            return null;
        }

        $result = $this->generator->generate($lottery, $request);
        $games = $result->games;

        $universe = (int) $lottery->universe_size;
        $drawnCount = (int) $lottery->numbers_drawn;
        $simpleBetSize = (int) $lottery->min_numbers_per_game;
        $betSize = $request->numbersPerGame;

        // Fallback for tiers a winner would have hit in a contest where the
        // recorded prize is zero because nobody actually won it.
        $estimatedPrizes = $this->math->tierPrizeEstimates($lottery);
        $tierHitsList = $lottery->prizeTiers()->pluck('hits')->map(fn ($hits) => (int) $hits)->all();

        $maxHits = min($drawnCount, $betSize);
        $observed = array_fill(0, $maxHits + 1, 0);
        $totalReturn = 0.0;

        foreach ($draws as $draw) {
            $drawnNumbers = array_flip($draw->numbers->pluck('number')->all());

            $prizeByHits = [];
            foreach ($draw->prizeResults as $prizeResult) {
                $prizeByHits[(int) $prizeResult->prizeTier->hits] = (float) $prizeResult->prize_amount;
            }

            foreach ($games as $game) {
                $hits = count(array_filter($game, fn (int $number) => isset($drawnNumbers[$number])));
                $observed[$hits]++;

                foreach ($tierHitsList as $tierHits) {
                    $winningSubBets = $this->math->combinations($hits, $tierHits)
                        * $this->math->combinations($betSize - $hits, $simpleBetSize - $tierHits);

                    if ($winningSubBets > 0) {
                        $prize = ($prizeByHits[$tierHits] ?? 0.0) > 0.0
                            ? $prizeByHits[$tierHits]
                            : ($estimatedPrizes[$tierHits] ?? 0.0);
                        $totalReturn += $winningSubBets * $prize;
                    }
                }
            }
        }

        $trials = count($games) * $draws->count();
        $expected = [];
        foreach ($this->math->hitDistribution($universe, $drawnCount, $betSize) as $hits => $probability) {
            $expected[$hits] = $probability * $trials;
        }

        $totalCost = $result->pricePerGame * $trials;

        return [
            'games' => $games,
            'drawsTested' => $draws->count(),
            'observed' => $observed,
            'expected' => $expected,
            'pricePerGame' => $result->pricePerGame,
            'totalCost' => $totalCost,
            'totalReturn' => $totalReturn,
            'net' => $totalReturn - $totalCost,
            'returnPerDraw' => ($totalReturn - $totalCost) / $draws->count(),
        ];
    }
}
