<?php

use App\Contracts\LotteryResultsProviderContract;
use App\DataTransferObjects\DrawData;
use App\DataTransferObjects\DrawPrizeResultData;
use App\Livewire\Lottery\Generator;
use App\Livewire\Lottery\MyGames;
use App\Models\Game;
use App\Models\GameCheck;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\User;
use App\Services\Lottery\LotterySyncService;
use Carbon\CarbonImmutable;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

test('generate, save, sync a new draw and auto-check end to end', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    // Seed contest 1 as history so the game is created "before" contest 2 is drawn.
    LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 1,
        'draw_date' => now()->subDay(),
        'source' => 'api',
    ]);

    // Generate and save a game with a fixed, known set of numbers via the random
    // strategy is non-deterministic, so instead save directly through the Livewire
    // component (exercised in GeneratorLivewireTest) and grab whatever it produced.
    Livewire::actingAs($user)
        ->test(Generator::class, ['lottery' => $lottery])
        ->set('numbersPerGame', 15)
        ->set('gamesCount', 1)
        ->call('generate')
        ->call('save');

    $game = Game::where('user_id', $user->id)->firstOrFail();
    expect($game->checked_at)->toBeNull();

    $gameNumbers = $game->numbers()->pluck('number')->sort()->values()->all();

    // The next draw (contest 2) matches the saved game exactly, guaranteeing a 15-hit win.
    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(new DrawData(
        contestNumber: 2,
        drawDate: CarbonImmutable::now(),
        numbers: $gameNumbers,
        numbersInDrawOrder: $gameNumbers,
        accumulated: false,
        collectionAmount: 100.0,
        accumulatedAmount: 0.0,
        estimatedNextPrize: 500.0,
        nextContestNumber: 3,
        nextDrawDate: CarbonImmutable::now()->addDays(2),
        location: 'SÃO PAULO',
        prizeResults: [
            new DrawPrizeResultData(hits: 15, label: '15 acertos', winnersCount: 1, prizeAmount: 250000.0),
        ],
        rawPayload: ['numero' => 2],
    )));

    app(LotterySyncService::class)->syncLatest($lottery);

    // The sync command dispatches CheckPendingGamesJob to the queue; here we
    // exercise the same checking path a logged-in user gets via "Conferir agora".
    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->call('checkNow');

    $game->refresh();

    expect($game->checked_at)->not->toBeNull();

    $check = GameCheck::where('game_id', $game->id)->firstOrFail();

    expect($check->hits)->toBe(15)
        ->and((float) $check->prize_amount)->toBe(250000.0);
});
