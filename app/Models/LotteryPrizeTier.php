<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lottery_id', 'hits', 'label'])]
class LotteryPrizeTier extends Model
{
    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }
}
