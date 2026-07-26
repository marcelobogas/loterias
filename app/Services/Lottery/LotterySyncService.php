<?php

namespace App\Services\Lottery;

use App\Contracts\LotteryResultsProviderContract;
use App\Enums\DrawSourceEnum;
use App\Enums\LotteryFreshnessEnum;
use App\Exceptions\Lottery\LotteryApiNotFoundException;
use App\Exceptions\Lottery\LotteryApiUnavailableException;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\LotterySyncLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class LotterySyncService
{
    public function __construct(
        private readonly LotteryResultsProviderContract $provider,
        private readonly LotteryDrawPersister $persister,
    ) {}

    /**
     * Compares the latest locally-synced contest against a live check of the
     * Caixa API, without persisting anything, and — when the two already
     * agree — also checks whether a draw is overdue at Caixa's own end (a
     * scheduled draw day has passed the cutoff hour but Caixa still hasn't
     * published it). Without that second check, "local matches the API"
     * reads as "everything is fine" even when Caixa itself is just late.
     * Cached per lottery for 10 minutes (including failures) so a page every
     * visitor hits doesn't hammer an undocumented, unrated third-party
     * endpoint.
     *
     * @return LotteryFreshnessEnum|null null = unknown (lottery not
     *                                   onboarded yet, no local draws, or
     *                                   the API call failed)
     */
    public function checkFreshness(Lottery $lottery): ?LotteryFreshnessEnum
    {
        if (! $lottery->caixa_api_slug) {
            return null;
        }

        $localDraw = $lottery->latestDraw();

        if (! $localDraw) {
            return null;
        }

        $liveContest = Cache::remember("lottery:{$lottery->id}:live-latest-contest", now()->addMinutes(10), function () use ($lottery) {
            try {
                return $this->provider->fetchLatest($lottery->caixa_api_slug)->contestNumber;
            } catch (LotteryApiNotFoundException|LotteryApiUnavailableException) {
                return -1;
            }
        });

        if ($liveContest === -1) {
            return null;
        }

        if ($localDraw->contest_number < $liveContest) {
            return LotteryFreshnessEnum::Behind;
        }

        return $this->isDrawOverdue($lottery, Carbon::parse($localDraw->draw_date))
            ? LotteryFreshnessEnum::AwaitingCaixa
            : LotteryFreshnessEnum::UpToDate;
    }

    /**
     * Whether a scheduled draw day (per draw_days_of_week) after
     * $lastDrawDate has already passed the draw cutoff hour, in the draw's
     * own timezone — i.e. Caixa should have published a new result by now.
     */
    private function isDrawOverdue(Lottery $lottery, CarbonInterface $lastDrawDate): bool
    {
        $drawDays = $lottery->draw_days_of_week;

        if (is_string($drawDays)) {
            $drawDays = json_decode($drawDays, true);
        }

        if (! is_array($drawDays) || $drawDays === []) {
            return false;
        }

        $timezone = config('caixa.draw_timezone');
        $cutoffHour = config('caixa.draw_cutoff_hour');

        // draw_date is a civil calendar date (the Brasília day of the draw),
        // not a real UTC instant — re-parsing it as midnight in the draw's
        // own timezone avoids shifting to the previous day, which a plain
        // ->timezone() conversion of a UTC-midnight value would do.
        $cursor = Carbon::parse($lastDrawDate->toDateString(), $timezone)->addDay();

        for ($i = 0; $i < 8; $i++) {
            if (in_array($cursor->isoWeekday(), $drawDays, true)) {
                return now($timezone)->greaterThan($cursor->copy()->setTime($cutoffHour, 0));
            }

            $cursor = $cursor->addDay();
        }

        return false;
    }

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
