<?php

namespace App\Actions\Game;

use App\Models\Game;
use App\Models\Lottery;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SaveGameAction
{
    /**
     * @param  array<int, int[]>  $games
     * @param  array<int, int|null>  $forContestNumbers  One row is created per contest, per game.
     *                                                   More than one entry marks the rows as a repeat group (Teimosinha).
     * @return Collection<int, Game>
     */
    public function execute(User $user, Lottery $lottery, array $games, string $strategy, float $pricePerGame, array $forContestNumbers = [null]): Collection
    {
        $batchId = (string) Str::uuid();

        return collect($games)->flatMap(function (array $numbers) use ($user, $lottery, $strategy, $pricePerGame, $batchId, $forContestNumbers) {
            $repeatGroupId = count($forContestNumbers) > 1 ? (string) Str::uuid() : null;

            return collect($forContestNumbers)->map(function (?int $forContestNumber) use ($user, $lottery, $strategy, $pricePerGame, $batchId, $repeatGroupId, $numbers) {
                $game = Game::create([
                    'user_id' => $user->id,
                    'lottery_id' => $lottery->id,
                    'numbers_chosen' => count($numbers),
                    'price' => $pricePerGame,
                    'strategy' => $strategy,
                    'generation_batch_id' => $batchId,
                    'repeat_group_id' => $repeatGroupId,
                    'for_contest_number' => $forContestNumber,
                ]);

                $game->numbers()->insert(
                    collect($numbers)->map(fn (int $number) => ['game_id' => $game->id, 'number' => $number])->all()
                );

                return $game;
            });
        });
    }
}
