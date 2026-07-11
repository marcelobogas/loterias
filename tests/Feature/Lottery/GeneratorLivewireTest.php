<?php

use App\Livewire\Lottery\Generator;
use App\Models\Game;
use App\Models\Lottery;
use App\Models\LotteryDraw;
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
        ->and(Game::where('user_id', $user->id)->first()->price)->toBe('3.50')
        ->and(Game::where('user_id', $user->id)->first()->numbers()->count())->toBe(15);
});

test('saved games target the next contest advertised by the latest draw', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => '2026-07-09',
        'next_contest_number' => 3732,
        'next_draw_date' => '2026-07-10',
        'source' => 'api',
    ]);

    Livewire::actingAs($user)
        ->test(Generator::class, ['lottery' => $lottery])
        ->set('numbersPerGame', 15)
        ->set('gamesCount', 1)
        ->call('generate')
        ->call('save');

    expect(Game::where('user_id', $user->id)->first()->for_contest_number)->toBe(3732);
});

test('saved games have no target contest when no draw has been synced yet', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Generator::class, ['lottery' => $lottery])
        ->set('numbersPerGame', 15)
        ->set('gamesCount', 1)
        ->call('generate')
        ->call('save');

    expect(Game::where('user_id', $user->id)->first()->for_contest_number)->toBeNull();
});

test('clearGames wipes the preview without saving anything', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::test(Generator::class, ['lottery' => $lottery])
        ->set('gamesCount', 2)
        ->call('generate')
        ->assertSet('previewGames', fn ($games) => count($games) === 2)
        ->call('clearGames')
        ->assertSet('previewGames', [])
        ->assertSet('pricePerGame', null)
        ->assertSet('totalPrice', null);

    expect(Game::count())->toBe(0);
});

test('the balanced strategy preview shows each game sum within the requested range', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    $component = Livewire::test(Generator::class, ['lottery' => $lottery])
        ->set('numbersPerGame', 15)
        ->set('gamesCount', 3)
        ->set('strategy', 'balanced')
        ->set('minSum', 150)
        ->set('maxSum', 240)
        ->call('generate');

    foreach ($component->get('previewGames') as $numbers) {
        $sum = array_sum($numbers);
        expect($sum)->toBeGreaterThanOrEqual(150)->toBeLessThanOrEqual(240);
    }

    $component->assertSee('Soma: '.array_sum($component->get('previewGames')[0]));
});
