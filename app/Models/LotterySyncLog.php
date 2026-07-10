<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lottery_id',
    'type',
    'status',
    'contests_synced',
    'message',
    'started_at',
    'finished_at',
])]
class LotterySyncLog extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }
}
