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

    // Lotofácil draws Mon-Sat; Friday 07-10 to Saturday 07-11 keeps the check
    // inside the same still-open window (before Saturday's cutoff).
    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => '2026-07-10',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    // 20:00 UTC on 07-11 = 17:00 in Brasília — before Saturday's draw cutoff.
    $this->travelTo('2026-07-11 20:00:00');

    Livewire::test(Home::class)
        ->assertSee('Atualizado')
        ->assertDontSee('Em atraso')
        ->assertDontSee('Aguardando divulgação');
});

test('an active lottery with an overdue draw shows the awaiting Caixa badge, not up to date', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => '2026-07-10',
        'source' => 'api',
    ]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    // 23:30 UTC on 07-11 = 20:30 in Brasília — Saturday's draw cutoff already passed
    // and Caixa still hasn't published a newer contest.
    $this->travelTo('2026-07-11 23:30:00');

    Livewire::test(Home::class)
        ->assertSee('Aguardando divulgação')
        ->assertDontSee('Atualizado')
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
