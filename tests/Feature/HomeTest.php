<?php

use App\Contracts\LotteryResultsProviderContract;
use App\Livewire\Home;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

test('an active lottery whose local draws match the live API shows the up to date badge', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => '2026-07-09',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    Livewire::test(Home::class)
        ->assertSee('Atualizado')
        ->assertDontSee('Em atraso');
});

test('an active lottery behind the live API shows the delayed badge', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3730,
        'draw_date' => '2026-07-08',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    Livewire::test(Home::class)
        ->assertSee('Em atraso');
});

test('a lottery with no local draws yet shows no freshness badge', function () {
    $this->seed(LotterySeeder::class);

    Livewire::test(Home::class)
        ->assertDontSee('Atualizado')
        ->assertDontSee('Em atraso');
});
