<?php

use App\Livewire\Lottery\Backtest;
use App\Models\Lottery;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

test('the backtest page runs a simulation against seeded draws', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    seedDrawWithNumbers($lottery, 1, range(1, 15), [15 => [1, 1000.0]]);
    seedDrawWithNumbers($lottery, 2, range(11, 25), [15 => [1, 1000.0]]);

    Livewire::test(Backtest::class, ['lottery' => $lottery])
        ->set('gamesCount', 2)
        ->set('window', '50')
        ->call('runBacktest')
        ->assertSet('result', fn ($result) => $result !== null && $result['drawsTested'] === 2 && array_sum($result['observed']) === 4)
        ->assertSee('Concursos testados')
        ->assertSee('Acertos: observado × teórico');
});

test('the backtest page explains itself when there is nothing to test', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::test(Backtest::class, ['lottery' => $lottery])
        ->call('runBacktest')
        ->assertSet('result', null);
});

test('the reduced wheel strategy is not offered in the backtest', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::test(Backtest::class, ['lottery' => $lottery])
        ->assertDontSee('Fechamento');
});

test('the simulador route renders for guests', function () {
    $this->seed(LotterySeeder::class);

    $this->get(route('lottery.backtest', 'lotofacil'))->assertOk();
});
