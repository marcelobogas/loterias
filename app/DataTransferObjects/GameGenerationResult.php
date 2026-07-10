<?php

namespace App\DataTransferObjects;

readonly class GameGenerationResult
{
    /**
     * @param  array<int, int[]>  $games
     */
    public function __construct(
        public array $games,
        public float $pricePerGame,
        public float $totalPrice,
    ) {}
}
