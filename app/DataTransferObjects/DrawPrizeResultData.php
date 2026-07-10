<?php

namespace App\DataTransferObjects;

readonly class DrawPrizeResultData
{
    public function __construct(
        public int $hits,
        public ?string $label,
        public int $winnersCount,
        public ?float $prizeAmount,
    ) {}
}
