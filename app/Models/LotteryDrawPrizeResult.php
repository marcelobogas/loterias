<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lottery_draw_id', 'lottery_prize_tier_id', 'winners_count', 'prize_amount'])]
class LotteryDrawPrizeResult extends Model
{
    protected function casts(): array
    {
        return [
            'prize_amount' => 'decimal:2',
        ];
    }

    public function draw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_draw_id');
    }

    public function prizeTier(): BelongsTo
    {
        return $this->belongsTo(LotteryPrizeTier::class, 'lottery_prize_tier_id');
    }
}
