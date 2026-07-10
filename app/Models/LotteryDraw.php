<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lottery_id',
    'contest_number',
    'draw_date',
    'accumulated',
    'collection_amount',
    'accumulated_amount',
    'estimated_next_prize',
    'next_contest_number',
    'next_draw_date',
    'location',
    'source',
    'raw_payload',
    'imported_at',
])]
class LotteryDraw extends Model
{
    protected function casts(): array
    {
        return [
            'draw_date' => 'date',
            'accumulated' => 'boolean',
            'collection_amount' => 'decimal:2',
            'accumulated_amount' => 'decimal:2',
            'estimated_next_prize' => 'decimal:2',
            'next_draw_date' => 'date',
            'raw_payload' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }

    public function numbers(): HasMany
    {
        return $this->hasMany(LotteryDrawNumber::class);
    }

    public function prizeResults(): HasMany
    {
        return $this->hasMany(LotteryDrawPrizeResult::class);
    }

    public function gameChecks(): HasMany
    {
        return $this->hasMany(GameCheck::class);
    }
}
