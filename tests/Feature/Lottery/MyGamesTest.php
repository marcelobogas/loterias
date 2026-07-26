<?php

use App\Contracts\LotteryResultsProviderContract;
use App\DataTransferObjects\DrawData;
use App\Livewire\Lottery\MyGames;
use App\Models\Game;
use App\Models\GameCheck;
use App\Models\Lottery;
use App\Models\LotteryDraw;
use App\Models\User;
use Database\Seeders\LotterySeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('checked games render matched numbers with the hit style and missed numbers with the default style', function () {
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
    // Game picks 1-15; the draw below only comes out 1-11, so 12-15 miss.
    $game->numbers()->insert(
        collect(range(1, 15))->map(fn (int $number) => ['game_id' => $game->id, 'number' => $number])->all()
    );

    $draw = LotteryDraw::create([
        'lottery_id' => $lottery->id,
        'contest_number' => 3731,
        'draw_date' => now()->subDay(),
        'source' => 'api',
    ]);
    $draw->numbers()->insert(
        collect(range(1, 11))->map(fn (int $number) => [
            'lottery_draw_id' => $draw->id,
            'lottery_id' => $lottery->id,
            'number' => $number,
        ])->all()
    );

    GameCheck::create([
        'game_id' => $game->id,
        'lottery_draw_id' => $draw->id,
        'hits' => 11,
        'prize_amount' => 7,
        'checked_at' => now(),
    ]);

    $html = Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->assertSuccessful()
        ->html();

    // number-ball renders zero-padded, e.g. "01", "12".
    preg_match('/<span[^>]*>\s*01\s*<\/span>/', $html, $hit);
    preg_match('/<span[^>]*>\s*12\s*<\/span>/', $html, $miss);

    expect($hit[0] ?? null)->toContain('bg-emerald-500')
        ->and($hit[0] ?? null)->toContain('ring-emerald-300')
        ->and($miss[0] ?? null)->not->toContain('ring-emerald-300');
});

test('the list shows the target contest number for a saved game', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
        'for_contest_number' => 3732,
    ]);

    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->assertSee('Concurso 3732');
});

test('games sharing a repeat group show the Teimosinha badge and standalone games do not', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    $repeatGroupId = (string) Str::uuid();

    foreach ([3732, 3733, 3734, 3735] as $contest) {
        Game::create([
            'user_id' => $user->id,
            'lottery_id' => $lottery->id,
            'numbers_chosen' => 15,
            'price' => 3.5,
            'strategy' => 'random',
            'repeat_group_id' => $repeatGroupId,
            'for_contest_number' => $contest,
        ]);
    }

    Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
        'for_contest_number' => 3740,
    ]);

    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->assertSee('Teimosinha · 4x')
        ->assertSee('Concurso 3740');
});

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

test('a user can delete all their saved games for a lottery at once', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    $games = collect(range(1, 3))->map(fn () => Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
    ]));

    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->call('deleteAllGames')
        ->assertDispatched('flash', message: '3 jogo(s) excluído(s).');

    $games->each(fn (Game $game) => $this->assertSoftDeleted('games', ['id' => $game->id]));
});

test('deleteAllGames does not touch other users\' or other lotteries\' games', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $otherLottery = Lottery::where('slug', 'mega-sena')->firstOrFail();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $baseAttributes = ['numbers_chosen' => 15, 'price' => 3.5, 'strategy' => 'random'];

    $ownGame = Game::create([...$baseAttributes, 'user_id' => $user->id, 'lottery_id' => $lottery->id]);
    $otherUserGame = Game::create([...$baseAttributes, 'user_id' => $otherUser->id, 'lottery_id' => $lottery->id]);
    $otherLotteryGame = Game::create([...$baseAttributes, 'user_id' => $user->id, 'lottery_id' => $otherLottery->id]);

    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->call('deleteAllGames');

    $this->assertSoftDeleted('games', ['id' => $ownGame->id]);
    $this->assertDatabaseHas('games', ['id' => $otherUserGame->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('games', ['id' => $otherLotteryGame->id, 'deleted_at' => null]);
});

test('checkNow syncs the latest contest on demand and checks pending games', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    $game = Game::create([
        'user_id' => $user->id,
        'lottery_id' => $lottery->id,
        'numbers_chosen' => 15,
        'price' => 3.5,
        'strategy' => 'random',
        'for_contest_number' => 3731,
    ]);
    $game->numbers()->insert(
        collect(range(1, 15))->map(fn (int $number) => ['game_id' => $game->id, 'number' => $number])->all()
    );

    // The draw only exists at the API — nothing synced locally yet.
    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(3731)));

    Livewire::actingAs($user)
        ->test(MyGames::class, ['lottery' => $lottery])
        ->call('checkNow')
        ->assertDispatched('flash', message: '1 jogo(s) conferido(s) agora.');

    expect(LotteryDraw::where('lottery_id', $lottery->id)->where('contest_number', 3731)->exists())->toBeTrue()
        ->and($game->refresh()->checked_at)->not->toBeNull();
});

test('checkNow throttles the on-demand sync to one API call per window', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();
    $user = User::factory()->create();

    $provider = new class(makeFakeDraw(3731)) implements LotteryResultsProviderContract
    {
        public int $fetchLatestCalls = 0;

        public function __construct(private readonly DrawData $draw) {}

        public function fetchLatest(string $apiSlug): DrawData
        {
            $this->fetchLatestCalls++;

            return $this->draw;
        }

        public function fetchByContest(string $apiSlug, int $contestNumber): DrawData
        {
            return $this->draw;
        }
    };
    $this->app->instance(LotteryResultsProviderContract::class, $provider);

    $component = Livewire::actingAs($user)->test(MyGames::class, ['lottery' => $lottery]);
    $component->call('checkNow');
    $component->call('checkNow');

    expect($provider->fetchLatestCalls)->toBe(1);
});
