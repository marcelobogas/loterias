<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lottery_id',
    'numbers_chosen',
    'combinations_count',
    'price',
    'effective_from',
    'effective_to',
])]
class LotteryPriceTier extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }
}
