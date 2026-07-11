<?php

use App\Livewire\Lottery\Dashboard;
use App\Models\Lottery;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

test('frequency defaults to numeric order', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    seedDrawWithNumbers($lottery, 1, range(1, 15));
    seedDrawWithNumbers($lottery, 2, range(11, 25));

    $frequency = Livewire::test(Dashboard::class, ['lottery' => $lottery])
        ->viewData('frequency');

    expect($frequency->keys()->all())->toBe(range(1, 25));
});

test('frequency can be sorted by highest and lowest occurrence', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    // Numbers 1-15 appear in both draws (freq 2); 16-25 never appear (freq 0).
    seedDrawWithNumbers($lottery, 1, range(1, 15));
    seedDrawWithNumbers($lottery, 2, range(1, 15));

    $desc = Livewire::test(Dashboard::class, ['lottery' => $lottery])
        ->set('frequencySort', 'desc')
        ->viewData('frequency');

    expect($desc->values()->first())->toBe(2)
        ->and($desc->values()->last())->toBe(0);

    $asc = Livewire::test(Dashboard::class, ['lottery' => $lottery])
        ->set('frequencySort', 'asc')
        ->viewData('frequency');

    expect($asc->values()->first())->toBe(0)
        ->and($asc->values()->last())->toBe(2);
});

test('a perfectly balanced history reads as consistent with a uniform draw', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    // Five cyclic windows of 15 numbers, starts spaced by 5: every number
    // from 1 to 25 appears exactly 3 times, so χ² is exactly zero.
    foreach ([0, 5, 10, 15, 20] as $index => $start) {
        seedDrawWithNumbers($lottery, $index + 1, collect(range(0, 14))
            ->map(fn (int $offset) => ($start + $offset) % 25 + 1)
            ->sort()->values()->all());
    }

    Livewire::test(Dashboard::class, ['lottery' => $lottery])
        ->assertViewHas('randomness', fn ($randomness) => $randomness['chiSquare'] === 0.0 && $randomness['pValue'] === 1.0)
        ->assertSee('Consistente com sorteio uniforme');
});

test('a heavily skewed history is flagged as a statistical deviation', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    foreach (range(1, 10) as $contest) {
        seedDrawWithNumbers($lottery, $contest, range(1, 15));
    }

    Livewire::test(Dashboard::class, ['lottery' => $lottery])
        ->assertViewHas('randomness', fn ($randomness) => $randomness['pValue'] < 0.05)
        ->assertSee('Desvio estatístico nesta janela');
});
