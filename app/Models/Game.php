<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'lottery_id',
    'numbers_chosen',
    'price',
    'strategy',
    'generation_batch_id',
    'label',
    'for_contest_number',
    'checked_at',
    'is_favorite',
])]
class Game extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'checked_at' => 'datetime',
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Lottery, $this>
     */
    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }

    /**
     * @return HasMany<GameNumber, $this>
     */
    public function numbers(): HasMany
    {
        return $this->hasMany(GameNumber::class);
    }

    /**
     * @return HasMany<GameCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(GameCheck::class);
    }
}
