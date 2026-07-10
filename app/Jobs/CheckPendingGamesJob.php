<?php

namespace App\Jobs;

use App\Services\Lottery\LotteryCheckingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckPendingGamesJob implements ShouldQueue
{
    use Queueable;

    public function handle(LotteryCheckingService $checker): void
    {
        $checker->checkAllPending();
    }
}
