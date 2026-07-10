<?php

namespace App\Contracts;

use App\DataTransferObjects\DrawData;
use App\Exceptions\Lottery\LotteryApiNotFoundException;
use App\Exceptions\Lottery\LotteryApiUnavailableException;

interface LotteryResultsProviderContract
{
    /**
     * @throws LotteryApiUnavailableException
     */
    public function fetchLatest(string $apiSlug): DrawData;

    /**
     * @throws LotteryApiNotFoundException
     * @throws LotteryApiUnavailableException
     */
    public function fetchByContest(string $apiSlug, int $contestNumber): DrawData;
}
