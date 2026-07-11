<?php

use App\Livewire\Lottery\Dashboard;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

function seedDrawWithNumbers(Lottery $lottery, int $contest, array $numbers): void
{
    $draw = LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => $contest,
        'draw_date' => now()->subDays(30 - $contest),
        'source' => 'api',
    ]);

    $draw->numbers()->insert(
        collect($numbers)->map(fn (int $number) => [
            'lottery_draw_id' => $draw->id,
            'lottery_id' => $lottery->id,
            'number' => $number,
        ])->all()
    );
}

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
