<?php

namespace App\DataTransferObjects;

readonly class GameGenerationRequest
{
    /**
     * @param  array<string, int>  $filters  min_sum, max_sum, min_evens, max_evens, min_primes, max_primes
     * @param  int[]|null  $pool  Larger pool of numbers for the reduced-wheel strategy
     */
    public function __construct(
        public int $numbersPerGame,
        public int $gamesCount,
        public string $strategy,
        public array $filters = [],
        public ?string $bias = null,
        public ?array $pool = null,
    ) {}
}
