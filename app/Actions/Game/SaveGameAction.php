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
     * @return Collection<int, Game>
     */
    public function execute(User $user, Lottery $lottery, array $games, string $strategy, float $pricePerGame): Collection
    {
        $batchId = (string) Str::uuid();

        return collect($games)->map(function (array $numbers) use ($user, $lottery, $strategy, $pricePerGame, $batchId) {
            $game = Game::create([
                'user_id' => $user->id,
                'lottery_id' => $lottery->id,
                'numbers_chosen' => count($numbers),
                'price' => $pricePerGame,
                'strategy' => $strategy,
                'generation_batch_id' => $batchId,
            ]);

            $game->numbers()->insert(
                collect($numbers)->map(fn (int $number) => ['game_id' => $game->id, 'number' => $number])->all()
            );

            return $game;
        });
    }
}
