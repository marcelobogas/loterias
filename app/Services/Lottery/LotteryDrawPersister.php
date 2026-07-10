<?php

namespace App\Services\Lottery;

use App\DataTransferObjects\DrawData;
use App\Enums\DrawSourceEnum;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\LotteryPrizeTier;
use Illuminate\Support\Facades\DB;

class LotteryDrawPersister
{
    public function persist(Lottery $lottery, DrawData $data, DrawSourceEnum $source): LotteryDraw
    {
        return DB::transaction(function () use ($lottery, $data, $source) {
            $draw = LotteryDraw::updateOrCreate(
                [
                    'lottery_id' => $lottery->id,
                    'contest_number' => $data->contestNumber,
                ],
                [
                    'draw_date' => $data->drawDate,
                    'accumulated' => $data->accumulated,
                    'collection_amount' => $data->collectionAmount,
                    'accumulated_amount' => $data->accumulatedAmount,
                    'estimated_next_prize' => $data->estimatedNextPrize,
                    'next_contest_number' => $data->nextContestNumber,
                    'next_draw_date' => $data->nextDrawDate,
                    'location' => $data->location,
                    'source' => $source->value,
                    'raw_payload' => $data->rawPayload,
                    'imported_at' => now(),
                ]
            );

            $this->syncNumbers($draw, $data);
            $this->syncPrizeResults($lottery, $draw, $data);

            return $draw;
        });
    }

    private function syncNumbers(LotteryDraw $draw, DrawData $data): void
    {
        $draw->numbers()->delete();

        $positions = array_flip($data->numbersInDrawOrder);

        $rows = array_map(fn (int $number) => [
            'lottery_draw_id' => $draw->id,
            'lottery_id' => $draw->lottery_id,
            'number' => $number,
            'position' => isset($positions[$number]) ? $positions[$number] + 1 : null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $data->numbers);

        $draw->numbers()->insert($rows);
    }

    private function syncPrizeResults(Lottery $lottery, LotteryDraw $draw, DrawData $data): void
    {
        // An empty list means "not provided by this source" (e.g. the CSV
        // fallback, which only carries numbers) rather than "zero tiers" —
        // skip so we don't wipe out prize data a previous API sync already
        // stored for this draw.
        if ($data->prizeResults === []) {
            return;
        }

        $draw->prizeResults()->delete();

        foreach ($data->prizeResults as $result) {
            $prizeTier = LotteryPrizeTier::firstOrCreate(
                ['lottery_id' => $lottery->id, 'hits' => $result->hits],
                ['label' => $result->label]
            );

            $draw->prizeResults()->create([
                'lottery_prize_tier_id' => $prizeTier->id,
                'winners_count' => $result->winnersCount,
                'prize_amount' => $result->prizeAmount,
            ]);
        }
    }
}
