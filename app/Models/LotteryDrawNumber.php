<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lottery_draw_id', 'lottery_id', 'number', 'position'])]
class LotteryDrawNumber extends Model
{
    public function draw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_draw_id');
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }
}
