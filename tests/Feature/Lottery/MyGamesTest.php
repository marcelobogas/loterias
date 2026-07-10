<?php

use App\Livewire\Lottery\MyGames;
use App\Models\Game;
use App\Models\Lottery;
use App\Models\User;
use Database\Seeders\LotterySeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('a user can delete their own saved game', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    $game = Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
    ]);

    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->call('deleteGame', $game->id);

    $this->assertSoftDeleted('games', ['id' => $game->id]);
});

test('a user cannot delete another user\'s game', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $game = Game::create([
        'user_id' => $owner->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
    ]);

    expect(fn () => Livewire::actingAs($intruder)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->call('deleteGame', $game->id)
    )->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('games', ['id' => $game->id, 'deleted_at' => null]);
});
