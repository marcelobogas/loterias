<?php

namespace App\Livewire\Lottery;

use App\Actions\Game\SaveGameAction;
use App\DataTransferObjects\GameGenerationRequest;
use App\Livewire\Concerns\WithLotteryContext;
use App\Models\Lottery;
use App\Services\Lottery\LotteryGameGeneratorService;
use App\Services\Lottery\LotteryMathService;
use App\Services\Lottery\LotteryPricingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Generator extends Component
{
    use WithLotteryContext;

    public int $numbersPerGame;

    public int $gamesCount = 1;

    public string $strategy = 'random';

    public bool $repeatEnabled = false;

    public int $repeatContests = 4;

    public string $bias = 'hot';

    public ?int $minSum = null;

    public ?int $maxSum = null;

    public ?int $minEvens = null;

    public ?int $maxEvens = null;

    public string $poolInput = '';

    /** @var array<int, int[]> */
    public array $previewGames = [];

    public ?float $pricePerGame = null;

    public ?float $totalPrice = null;

    public function mount(Lottery $lottery): void
    {
        $this->lottery = $lottery;
        $this->numbersPerGame = $lottery->min_numbers_per_game;
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function strategyDetails(LotteryGameGeneratorService $generator): array
    {
        $details = [];

        foreach ($generator->availableStrategies() as $key => $strategy) {
            $details[$key] = [
                'label' => $strategy->label(),
                'description' => $strategy->description(),
            ];
        }

        return $details;
    }

    private function estimatedPrice(LotteryPricingService $pricing): ?float
    {
        if ($this->numbersPerGame < $this->lottery->min_numbers_per_game || $this->numbersPerGame > $this->lottery->max_numbers_per_game) {
            return null;
        }

        return $pricing->estimateBatch($this->lottery, $this->numbersPerGame, max(1, $this->gamesCount));
    }

    public function generate(LotteryGameGeneratorService $generator): void
    {
        $this->validate([
            'numbersPerGame' => "required|integer|min:{$this->lottery->min_numbers_per_game}|max:{$this->lottery->max_numbers_per_game}",
            'gamesCount' => 'required|integer|min:1|max:20',
        ]);

        $request = new GameGenerationRequest(
            numbersPerGame: $this->numbersPerGame,
            gamesCount: $this->gamesCount,
            strategy: $this->strategy,
            filters: array_filter([
                'min_sum' => $this->minSum,
                'max_sum' => $this->maxSum,
                'min_evens' => $this->minEvens,
                'max_evens' => $this->maxEvens,
            ], fn ($value) => $value !== null),
            bias: $this->bias,
            pool: $this->strategy === 'reduced_wheel' ? $this->parsePool() : null,
        );

        $result = $generator->generate($this->lottery, $request);

        $this->previewGames = $result->games;
        $this->pricePerGame = $result->pricePerGame;
        $this->totalPrice = $result->totalPrice;
    }

    public function clearGames(): void
    {
        $this->previewGames = [];
        $this->pricePerGame = null;
        $this->totalPrice = null;
    }

    public function save(SaveGameAction $action): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if ($this->previewGames === [] || $this->pricePerGame === null) {
            return;
        }

        if ($this->repeatEnabled) {
            $this->validate([
                'repeatContests' => 'required|integer|min:2|max:8',
            ]);
        }

        $targetContests = $this->resolveTargetContests();

        $action->execute(auth()->user(), $this->lottery, $this->previewGames, $this->strategy, $this->pricePerGame, $targetContests);

        $this->previewGames = [];

        $this->dispatch('flash', message: $this->repeatEnabled && count($targetContests) === 1
            ? 'Jogos salvos, mas a repetição foi ignorada: nenhum concurso sincronizado ainda.'
            : 'Jogos salvos com sucesso!');
    }

    /**
     * The contest(s) new games should target. Resolved at save/render time
     * (not mount) so a long-lived component still targets the contest that
     * is actually open. Falls back to a single contest (today's behavior)
     * when repeat isn't enabled or no draw has synced yet to project from.
     *
     * @return array<int, int|null>
     */
    private function resolveTargetContests(): array
    {
        $latestDraw = $this->lottery->latestDraw();
        $forContest = $latestDraw ? ($latestDraw->next_contest_number ?? $latestDraw->contest_number + 1) : null;

        if ($forContest === null || ! $this->repeatEnabled) {
            return [$forContest];
        }

        $repeatCount = max(1, min($this->repeatContests, 8));

        return range($forContest, $forContest + $repeatCount - 1);
    }

    /**
     * @return int[]
     */
    private function parsePool(): array
    {
        return collect(preg_split('/[;,\s]+/', trim($this->poolInput), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($number) => (int) $number)
            ->unique()
            ->values()
            ->all();
    }

    public function render(LotteryGameGeneratorService $generator, LotteryPricingService $pricing, LotteryMathService $math)
    {
        $estimatedBatchPrice = $this->estimatedPrice($pricing);

        return view('livewire.lottery.generator', [
            'strategyDetails' => $this->strategyDetails($generator),
            'estimatedBatchPrice' => $estimatedBatchPrice,
            'expectedValue' => $estimatedBatchPrice !== null ? $math->expectedValue($this->lottery, $this->numbersPerGame) : null,
            'repeatMultiplier' => count($this->resolveTargetContests()),
        ]);
    }
}
