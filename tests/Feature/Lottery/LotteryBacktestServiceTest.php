<?php

use App\Contracts\LotteryStrategyContract;
use App\DataTransferObjects\GameGenerationRequest;
use App\Models\Lottery;
use App\Services\Lottery\LotteryBacktestService;
use App\Services\Lottery\LotteryGameGeneratorService;
use App\Services\Lottery\LotteryPricingService;
use Database\Seeders\LotterySeeder;

function bindFixedGameStrategy(array $numbers): void
{
    $stub = new class($numbers) implements LotteryStrategyContract
    {
        public function __construct(private readonly array $numbers) {}

        public function key(): string
        {
            return 'random';
        }

        public function label(): string
        {
            return 'Fixo (teste)';
        }

        public function description(): string
        {
            return '';
        }

        public function pick(Lottery $lottery, int $numbersPerGame, array $context): array
        {
            return $this->numbers;
        }
    };

    app()->bind(LotteryGameGeneratorService::class, fn ($app) => new LotteryGameGeneratorService(
        strategies: [$stub],
        pricing: $app->make(LotteryPricingService::class),
    ));
}

test('the backtest replays games against real draws with actual prizes', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    seedDrawWithNumbers($lottery, 1, range(1, 15), [15 => [1, 1000.0]]);
    seedDrawWithNumbers($lottery, 2, range(11, 25), [15 => [1, 1000.0]]);

    bindFixedGameStrategy(range(1, 15));

    $result = app(LotteryBacktestService::class)->run(
        $lottery,
        new GameGenerationRequest(numbersPerGame: 15, gamesCount: 1, strategy: 'random'),
        null,
    );

    expect($result)->not->toBeNull()
        ->and($result['drawsTested'])->toBe(2)
        // Contest 1: 15 hits, wins the seeded R$ 1.000. Contest 2: |{1..15} ∩ {11..25}| = 5 hits, nothing.
        ->and($result['observed'][15])->toBe(1)
        ->and($result['observed'][5])->toBe(1)
        ->and($result['totalReturn'])->toEqualWithDelta(1000.0, 1e-6)
        ->and($result['totalCost'])->toEqualWithDelta(2 * 3.50, 1e-6)
        ->and($result['net'])->toEqualWithDelta(1000.0 - 7.0, 1e-6)
        ->and(array_sum($result['observed']))->toBe(2)
        ->and(array_sum($result['expected']))->toEqualWithDelta(2.0, 1e-9);
});

test('a tier won in a contest where nobody won it falls back to the historical median prize', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    // Contest 1 establishes the historical estimate; in contest 2 nobody won
    // the 15-hit tier, so its recorded prize is zero.
    seedDrawWithNumbers($lottery, 1, range(11, 25), [15 => [1, 800.0]]);
    seedDrawWithNumbers($lottery, 2, range(1, 15), [15 => [0, 0.0]]);

    bindFixedGameStrategy(range(1, 15));

    $result = app(LotteryBacktestService::class)->run(
        $lottery,
        new GameGenerationRequest(numbersPerGame: 15, gamesCount: 1, strategy: 'random'),
        null,
    );

    expect($result['totalReturn'])->toEqualWithDelta(800.0, 1e-6);
});

test('the backtest returns null without draw history', function () {
    $this->seed(LotterySeeder::class);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    $result = app(LotteryBacktestService::class)->run(
        $lottery,
        new GameGenerationRequest(numbersPerGame: 15, gamesCount: 1, strategy: 'random'),
        null,
    );

    expect($result)->toBeNull();
});
