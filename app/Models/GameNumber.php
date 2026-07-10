<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['game_id', 'number'])]
class GameNumber extends Model
{
    public $timestamps = false;

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
