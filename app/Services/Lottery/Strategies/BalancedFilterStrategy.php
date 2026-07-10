<?php

namespace App\Services\Lottery\Strategies;

use App\Contracts\LotteryStrategyContract;
use App\Models\Lottery;

/**
 * Generates random candidates and keeps the first one that satisfies the
 * requested combinatorial filters (sum range, parity range, prime count
 * range). Falls back to the last candidate tried if none matched within
 * the attempt budget, rather than failing the whole generation.
 */
class BalancedFilterStrategy implements LotteryStrategyContract
{
    private const MAX_ATTEMPTS = 500;

    public function __construct(
        private readonly RandomStrategy $random,
    ) {}

    public function key(): string
    {
        return 'balanced';
    }

    public function label(): string
    {
        return 'Balanceado por filtros';
    }

    public function pick(Lottery $lottery, int $numbersPerGame, array $context): array
    {
        $filters = $context['filters'] ?? [];
        $candidate = [];

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $this->random->pick($lottery, $numbersPerGame, $context);

            if ($this->matchesFilters($candidate, $filters)) {
                return $candidate;
            }
        }

        return $candidate;
    }

    /**
     * @param  int[]  $numbers
     * @param  array<string, int>  $filters
     */
    private function matchesFilters(array $numbers, array $filters): bool
    {
        $sum = array_sum($numbers);

        if (isset($filters['min_sum']) && $sum < $filters['min_sum']) {
            return false;
        }

        if (isset($filters['max_sum']) && $sum > $filters['max_sum']) {
            return false;
        }

        $evens = count(array_filter($numbers, fn (int $n) => $n % 2 === 0));

        if (isset($filters['min_evens']) && $evens < $filters['min_evens']) {
            return false;
        }

        if (isset($filters['max_evens']) && $evens > $filters['max_evens']) {
            return false;
        }

        $primes = count(array_filter($numbers, $this->isPrime(...)));

        if (isset($filters['min_primes']) && $primes < $filters['min_primes']) {
            return false;
        }

        if (isset($filters['max_primes']) && $primes > $filters['max_primes']) {
            return false;
        }

        return true;
    }

    private function isPrime(int $number): bool
    {
        if ($number < 2) {
            return false;
        }

        for ($i = 2; $i * $i <= $number; $i++) {
            if ($number % $i === 0) {
                return false;
            }
        }

        return true;
    }
}
