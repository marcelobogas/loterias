<?php

use App\Livewire\Lottery\Generator;
use App\Models\Game;
use App\Models\Lottery;
use App\Models\User;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

test('a guest can preview games but must log in to save', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::test(Generator::class, ['lottery' => $lottery])
        ->set('gamesCount', 2)
        ->call('generate')
        ->assertSet('previewGames', fn ($games) => count($games) === 2)
        ->call('save')
        ->assertRedirect(route('login'));

    expect(Game::count())->toBe(0);
});

test('an authenticated user can generate and save games at the correct price', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Generator::class, ['lottery' => $lottery])
        ->set('numbersPerGame', 15)
        ->set('gamesCount', 3)
        ->call('generate')
        ->call('save')
        ->assertSet('previewGames', []);

    expect(Game::where('user_id', $user->id)->count())->toBe(3)
        ->and(Game::where('user_id', $user->id)->first()->price)->toBe('3.00')
        ->and(Game::where('user_id', $user->id)->first()->numbers()->count())->toBe(15);
});
