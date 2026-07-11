<?php

namespace App\Contracts;

use App\Models\Lottery;

interface LotteryStrategyContract
{
    public function key(): string;

    public function label(): string;

    /**
     * Succinct, user-facing explanation of what the strategy does and does
     * not do — shown in the generator UI so players don't overestimate a
     * heuristic's effect on their odds.
     */
    public function description(): string;

    /**
     * @param  array<string, mixed>  $context
     * @return int[] chosen numbers, count == $numbersPerGame
     */
    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array;
}
