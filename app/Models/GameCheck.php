<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'game_id',
    'lottery_draw_id',
    'hits',
    'prize_amount',
    'lottery_prize_tier_id',
    'checked_at',
])]
class GameCheck extends Model
{
    protected function casts(): array
    {
        return [
            'prize_amount' => 'decimal:2',
            'checked_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
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
