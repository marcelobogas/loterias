<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

readonly class DrawData
{
    /**
     * @param  int[]  $numbers  Drawn numbers, ascending.
     * @param  int[]  $numbersInDrawOrder  Drawn numbers in the order they came out, if known.
     * @param  DrawPrizeResultData[]  $prizeResults
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public int $contestNumber,
        public CarbonImmutable $drawDate,
        public array $numbers,
        public array $numbersInDrawOrder,
        public bool $accumulated,
        public ?float $collectionAmount,
        public ?float $accumulatedAmount,
        public ?float $estimatedNextPrize,
        public ?int $nextContestNumber,
        public ?CarbonImmutable $nextDrawDate,
        public ?string $location,
        public array $prizeResults,
        public array $rawPayload,
    ) {}
}
