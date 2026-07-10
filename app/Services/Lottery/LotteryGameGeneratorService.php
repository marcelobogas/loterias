<?php

namespace App\Services\Lottery;

use App\Contracts\LotteryStrategyContract;
use App\DataTransferObjects\GameGenerationRequest;
use App\DataTransferObjects\GameGenerationResult;
use App\Models\Lottery;
use InvalidArgumentException;

class LotteryGameGeneratorService
{
    private const MAX_ATTEMPTS_PER_GAME = 30;

    /**
     * @param  iterable<LotteryStrategyContract>  $strategies
     */
    public function __construct(
        private readonly iterable $strategies,
        private readonly LotteryPricingService $pricing,
    ) {}

    public function generate(Lottery $lottery, GameGenerationRequest $request): GameGenerationResult
    {
        $strategy = $this->resolveStrategy($request->strategy);

        $context = [
            'filters' => $request->filters,
            'bias' => $request->bias,
            'pool' => $request->pool,
            'coveredPairs' => [],
        ];

        $games = [];

        for ($i = 0; $i < $request->gamesCount; $i++) {
            $candidate = $this->pickAvoidingDuplicates($strategy, $lottery, $request->numbersPerGame, $context, $games);
            $games[] = $candidate;

            if ($strategy->key() === 'reduced_wheel') {
                $context['coveredPairs'] = $this->mergeCoveredPairs($context['coveredPairs'], $candidate);
            }
        }

        $pricePerGame = $this->pricing->priceFor($lottery, $request->numbersPerGame);

        return new GameGenerationResult(
            games: $games,
            pricePerGame: $pricePerGame,
            totalPrice: $pricePerGame * $request->gamesCount,
        );
    }

    /**
     * @return array<string, LotteryStrategyContract>
     */
    public function availableStrategies(): array
    {
        $available = [];

        foreach ($this->strategies as $strategy) {
            $available[$strategy->key()] = $strategy;
        }

        return $available;
    }

    /**
     * @param  array<int, int[]>  $existingGames
     * @param  array<string, mixed>  $context
     * @return int[]
     */
    private function pickAvoidingDuplicates(
        LotteryStrategyContract $strategy,
        Lottery $lottery,
        int $numbersPerGame,
        array $context,
        array $existingGames,
    ): array {
        $candidate = $strategy->pick($lottery, $numbersPerGame, $context);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS_PER_GAME; $attempt++) {
            if (! $this->isDuplicate($candidate, $existingGames)) {
                break;
            }

            $candidate = $strategy->pick($lottery, $numbersPerGame, $context);
        }

        return $candidate;
    }

    /**
     * @param  int[]  $candidate
     * @param  array<int, int[]>  $existingGames
     */
    private function isDuplicate(array $candidate, array $existingGames): bool
    {
        foreach ($existingGames as $existing) {
            if ($existing === $candidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, bool>  $covered
     * @param  int[]  $numbers
     * @return array<string, bool>
     */
    private function mergeCoveredPairs(array $covered, array $numbers): array
    {
        $count = count($numbers);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $numbers[$i];
                $b = $numbers[$j];
                $key = $a < $b ? "{$a}-{$b}" : "{$b}-{$a}";
                $covered[$key] = true;
            }
        }

        return $covered;
    }

    private function resolveStrategy(string $key): LotteryStrategyContract
    {
        return $this->availableStrategies()[$key]
            ?? throw new InvalidArgumentException("Estratégia de geração desconhecida: '{$key}'.");
    }
}
