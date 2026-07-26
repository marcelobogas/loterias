<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class RunLotteryBackfillJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $lotterySlug,
        public int $from,
        public int $to,
    ) {}

    public function handle(): void
    {
        $exitCode = Artisan::call('lottery:backfill', [
            'slug' => $this->lotterySlug,
            '--from' => $this->from,
            '--to' => $this->to,
        ]);

        Cache::forget("lottery:backfill-running:{$this->lotterySlug}");

        if ($exitCode !== 0) {
            throw new RuntimeException("lottery:backfill falhou para '{$this->lotterySlug}' (exit code {$exitCode}).");
        }
    }

    public function failed(): void
    {
        Cache::forget("lottery:backfill-running:{$this->lotterySlug}");
    }
}
