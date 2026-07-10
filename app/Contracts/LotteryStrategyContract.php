<?php

namespace App\Contracts;

use App\Models\Lottery;

interface LotteryStrategyContract
{
    public function key(): string;

    public function label(): string;

    /**
     * @param  array<string, mixed>  $context
     * @return int[] chosen numbers, count == $numbersPerGame
     */
    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array;
}
