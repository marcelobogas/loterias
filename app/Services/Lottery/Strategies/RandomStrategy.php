<?php

namespace App\Services\Lottery\Strategies;

use App\Contracts\LotteryStrategyContract;
use App\Models\Lottery;

class RandomStrategy implements LotteryStrategyContract
{
    public function key(): string
    {
        return 'random';
    }

    public function label(): string
    {
        return 'Aleatório';
    }

    public function description(): string
    {
        return 'Sorteia os números com a mesma chance para todos, sem nenhum critério. É a base neutra de comparação — nenhuma outra estratégia tem probabilidade de acerto maior que esta.';
    }

    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array
    {
        $pool = range(1, $lottery->universe_size);
        shuffle($pool);

        $picked = array_slice($pool, 0, $numbersPerGame);
        sort($picked);

        return $picked;
    }
}
