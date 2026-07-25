<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'caixa_api_slug',
    'universe_size',
    'numbers_drawn',
    'min_numbers_per_game',
    'max_numbers_per_game',
    'draw_days_of_week',
    'is_active',
    'color_hex',
    'description',
])]
class Lottery extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'draw_days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<LotteryPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(LotteryPriceTier::class);
    }

    /**
     * @return HasMany<LotteryPrizeTier, $this>
     */
    public function prizeTiers(): HasMany
    {
        return $this->hasMany(LotteryPrizeTier::class);
    }

    /**
     * @return HasMany<LotteryDraw, $this>
     */
    public function draws(): HasMany
    {
        return $this->hasMany(LotteryDraw::class);
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * @return HasMany<LotterySyncLog, $this>
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(LotterySyncLog::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function latestDraw(): ?LotteryDraw
    {
        return $this->draws()->orderByDesc('contest_number')->first();
    }

    public function accentForegroundHex(): string
    {
        [$red, $green, $blue] = sscanf($this->color_hex ?? '#10b981', '#%02x%02x%02x');

        $luminance = (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;

        return $luminance > 0.55 ? '#020617' : '#ffffff';
    }
}
