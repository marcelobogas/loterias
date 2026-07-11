<?php

use App\Models\Game;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\User;
use App\Services\Lottery\LotteryCheckingService;

function makeCheckingLottery(): Lottery
{
    return Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);
}

test('a game with for_contest_number is checked against exactly that contest', function () {
    $lottery = makeCheckingLottery();
    $user = User::factory()->create();

    foreach ([['n' => 2, 'd' => '2026-07-10'], ['n' => 3, 'd' => '2026-07-11']] as $draw) {
        LotteryDraw::create([
            'lottery_id' => $lottery->id,
            'contest_number' => $draw['n'],
            'draw_date' => $draw['d'],
            'source' => 'api',
        ]);
    }

    $game = Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
        'for_contest_number' => 3,
    ]);

    $check = app(LotteryCheckingService::class)->checkGame($game);

    expect($check)->not->toBeNull()
        ->and($check->draw->contest_number)->toBe(3);
});

test('fallback resolves the same-day draw for a game saved before the draw time', function () {
    $lottery = makeCheckingLottery();
    $user = User::factory()->create();

    foreach ([['n' => 2, 'd' => '2026-07-10'], ['n' => 3, 'd' => '2026-07-11']] as $draw) {
        LotteryDraw::create([
            'lottery_id' => $lottery->id,
            'contest_number' => $draw['n'],
            'draw_date' => $draw['d'],
            'source' => 'api',
        ]);
    }

    // 18:00 UTC on 10/07 = 15:00 in Brasília, well before the ~20h draw.
    $this->travelTo('2026-07-10 18:00:00');

    $game = Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
    ]);

    $check = app(LotteryCheckingService::class)->checkGame($game);

    expect($check)->not->toBeNull()
        ->and($check->draw->contest_number)->toBe(2);
});

test('fallback skips to the next day for a game saved after the draw time', function () {
    $lottery = makeCheckingLottery();
    $user = User::factory()->create();

    foreach ([['n' => 2, 'd' => '2026-07-10'], ['n' => 3, 'd' => '2026-07-11']] as $draw) {
        LotteryDraw::create([
            'lottery_id' => $lottery->id,
            'contest_number' => $draw['n'],
            'draw_date' => $draw['d'],
            'source' => 'api',
        ]);
    }

    // 23:30 UTC on 10/07 = 20:30 in Brasília — the 10/07 draw already happened,
    // so the game can only target the 11/07 contest.
    $this->travelTo('2026-07-10 23:30:00');

    $game = Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
    ]);

    $check = app(LotteryCheckingService::class)->checkGame($game);

    expect($check)->not->toBeNull()
        ->and($check->draw->contest_number)->toBe(3);
});
