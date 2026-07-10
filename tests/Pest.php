<?php

use App\Contracts\LotteryResultsProviderContract;
use App\DataTransferObjects\DrawData;
use App\DataTransferObjects\DrawPrizeResultData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeFakeDraw(int $contestNumber): DrawData
{
    return new DrawData(
        contestNumber: $contestNumber,
        drawDate: CarbonImmutable::parse('2026-07-09'),
        numbers: range(1, 15),
        numbersInDrawOrder: range(1, 15),
        accumulated: false,
        collectionAmount: 100.0,
        accumulatedAmount: 0.0,
        estimatedNextPrize: 500.0,
        nextContestNumber: $contestNumber + 1,
        nextDrawDate: CarbonImmutable::parse('2026-07-10'),
        location: 'SÃO PAULO',
        prizeResults: [
            new DrawPrizeResultData(hits: 15, label: '15 acertos', winnersCount: 1, prizeAmount: 1000.0),
        ],
        rawPayload: ['numero' => $contestNumber],
    );
}

function fakeLotteryProvider(DrawData $draw): LotteryResultsProviderContract
{
    return new class($draw) implements LotteryResultsProviderContract
    {
        public function __construct(private readonly DrawData $draw) {}

        public function fetchLatest(string $apiSlug): DrawData
        {
            return $this->draw;
        }

        public function fetchByContest(string $apiSlug, int $contestNumber): DrawData
        {
            return $this->draw;
        }
    };
}
