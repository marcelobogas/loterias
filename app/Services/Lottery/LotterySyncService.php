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
     * known local contest. When the gap exceeds the inline limit, only the
     * most recent contests are filled (the latest always lands) and the sync
     * is logged as partial; the remaining history should go through
     * `lottery:backfill`, which is chunked and logged per range.
     */
    public function syncLatest(Lottery $lottery, int $maxGapFill = 10): LotterySyncLog
    {
        $startedAt = now();
        $synced = 0;

        try {
            $latest = $this->provider->fetchLatest($lottery->caixa_api_slug);
            $localLatest = (int) ($lottery->draws()->max('contest_number') ?? 0);

            $firstToFill = max($localLatest + 1, $latest->contestNumber - $maxGapFill);
            $skipped = $firstToFill - ($localLatest + 1);

            for ($contest = $firstToFill; $contest < $latest->contestNumber; $contest++) {
                $data = $this->provider->fetchByContest($lottery->caixa_api_slug, $contest);
                $this->persister->persist($lottery, $data, DrawSourceEnum::Api);
                $synced++;
                usleep(config('caixa.backfill_sleep_ms') * 1000);
            }

            $this->persister->persist($lottery, $latest, DrawSourceEnum::Api);
            $synced++;

            if ($skipped > 0) {
                return $this->log($lottery, 'api', $startedAt, 'partial', $synced, sprintf(
                    'Skipped %d older contests after #%d (inline limit %d); run lottery:backfill to fill the gap.',
                    $skipped,
                    $localLatest,
                    $maxGapFill,
                ));
            }

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
