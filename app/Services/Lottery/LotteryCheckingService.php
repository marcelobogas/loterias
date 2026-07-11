<?php

namespace App\Services\Lottery;

use App\Models\Game;
use App\Models\GameCheck;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\User;

class LotteryCheckingService
{
    public function checkGame(Game $game, ?LotteryDraw $draw = null): ?GameCheck
    {
        $draw ??= $this->resolveDrawForGame($game);

        if (! $draw) {
            return null;
        }

        $gameNumbers = $game->numbers()->pluck('number')->all();
        $drawNumbers = $draw->numbers()->pluck('number')->all();
        $hits = count(array_intersect($gameNumbers, $drawNumbers));

        $prizeTier = $game->lottery->prizeTiers()->where('hits', $hits)->first();
        $prizeAmount = 0.0;

        if ($prizeTier) {
            $result = $draw->prizeResults()->where('lottery_prize_tier_id', $prizeTier->id)->first();
            $prizeAmount = (float) ($result->prize_amount ?? 0.0);
        }

        $check = GameCheck::updateOrCreate(
            ['game_id' => $game->id, 'lottery_draw_id' => $draw->id],
            [
                'hits' => $hits,
                'prize_amount' => $prizeAmount,
                'lottery_prize_tier_id' => $prizeTier?->id,
                'checked_at' => now(),
            ]
        );

        $game->update(['checked_at' => now()]);

        return $check;
    }

    /**
     * @return array{checked: int, skipped: int}
     */
    public function checkAllPending(): array
    {
        $checked = 0;
        $skipped = 0;

        foreach (Game::query()->whereNull('checked_at')->cursor() as $game) {
            if ($this->checkGame($game)) {
                $checked++;
            } else {
                $skipped++;
            }
        }

        return ['checked' => $checked, 'skipped' => $skipped];
    }

    public function checkPendingForUser(User $user): int
    {
        $checked = 0;

        foreach ($user->games()->whereNull('checked_at')->get() as $game) {
            if ($this->checkGame($game)) {
                $checked++;
            }
        }

        return $checked;
    }

    /**
     * @return array{spent: float, won: float, net: float}
     */
    public function roiSummary(User $user, ?Lottery $lottery = null): array
    {
        $gameIds = $user->games()
            ->when($lottery, fn ($query) => $query->where('lottery_id', $lottery->id))
            ->pluck('id');

        $spent = (float) Game::whereIn('id', $gameIds)->sum('price');
        $won = (float) GameCheck::whereIn('game_id', $gameIds)->sum('prize_amount');

        return ['spent' => $spent, 'won' => $won, 'net' => $won - $spent];
    }

    /**
     * Resolves which draw a game should be checked against: the contest it
     * was explicitly generated for, or otherwise the first contest drawn
     * after the game was saved. Returns null (leaving the game pending) if
     * that draw hasn't happened yet.
     */
    private function resolveDrawForGame(Game $game): ?LotteryDraw
    {
        if ($game->for_contest_number) {
            return $game->lottery->draws()->where('contest_number', $game->for_contest_number)->first();
        }

        // created_at is stored in UTC; compare in the draw's local timezone,
        // and a game saved after the ~20h draw can only target the next day.
        $createdLocal = $game->created_at->copy()->timezone(config('caixa.draw_timezone'));
        $earliestDrawDate = $createdLocal->hour >= config('caixa.draw_cutoff_hour')
            ? $createdLocal->addDay()->toDateString()
            : $createdLocal->toDateString();

        return $game->lottery->draws()
            ->whereDate('draw_date', '>=', $earliestDrawDate)
            ->orderBy('contest_number')
            ->first();
    }
}
