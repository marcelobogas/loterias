<?php

namespace App\Services\Lottery;

use App\Contracts\LotteryResultsProviderContract;
use App\Enums\DrawSourceEnum;
use App\Exceptions\Lottery\LotteryApiNotFoundException;
use App\Exceptions\Lottery\LotteryApiUnavailableException;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\LotterySyncLog;

class LotterySyncService
{
    public function __construct(
        private readonly LotteryResultsProviderContract $provider,
        private readonly LotteryDrawPersister $persister,
    ) {}

    /**
     * Fetches the latest contest and backfills any small gap since the last
     * known local contest. Larger historical gaps (e.g. a brand new lottery)
     * should go through `lottery:backfill` instead, which is chunked and
     * logged per range rather than inline here.
     */
    public function syncLatest(Lottery $lottery, int $maxGapFill = 10): LotterySyncLog
    {
        $startedAt = now();
        $synced = 0;

        try {
            $latest = $this->provider->fetchLatest($lottery->caixa_api_slug);
            $localLatest = (int) ($lottery->draws()->max('contest_number') ?? 0);
            $gap = max(0, $latest->contestNumber - $localLatest - 1);

            if ($gap > $maxGapFill) {
                return $this->log($lottery, 'api', $startedAt, 'partial', 0, sprintf(
                    'Gap of %d contests since #%d is larger than the %d-contest inline limit; run lottery:backfill.',
                    $gap,
                    $localLatest,
                    $maxGapFill,
                ));
            }

            for ($contest = $localLatest + 1; $contest < $latest->contestNumber; $contest++) {
                $data = $this->provider->fetchByContest($lottery->caixa_api_slug, $contest);
                $this->persister->persist($lottery, $data, DrawSourceEnum::Api);
                $synced++;
                usleep(config('caixa.backfill_sleep_ms') * 1000);
            }

            $this->persister->persist($lottery, $latest, DrawSourceEnum::Api);
            $synced++;

            return $this->log($lottery, 'api', $startedAt, 'success', $synced);
        } catch (LotteryApiNotFoundException|LotteryApiUnavailableException $exception) {
            return $this->log(
                $lottery,
                'api',
                $startedAt,
                $synced > 0 ? 'partial' : 'failed',
                $synced,
                $exception->getMessage(),
            );
        }
    }

    public function syncByContest(Lottery $lottery, int $contestNumber, bool $force = false): LotteryDraw
    {
        $existing = $lottery->draws()->where('contest_number', $contestNumber)->first();

        if ($existing && ! $force) {
            return $existing;
        }

        $data = $this->provider->fetchByContest($lottery->caixa_api_slug, $contestNumber);

        return $this->persister->persist($lottery, $data, DrawSourceEnum::Api);
    }

    private function log(
        Lottery $lottery,
        string $type,
        \DateTimeInterface $startedAt,
        string $status,
        int $synced,
        ?string $message = null,
    ): LotterySyncLog {
        return LotterySyncLog::create([
            'lottery_id' => $lottery->id,
            'type' => $type,
            'status' => $status,
            'contests_synced' => $synced,
            'message' => $message,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }
}
