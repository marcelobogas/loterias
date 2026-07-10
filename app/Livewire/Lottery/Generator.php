<?php

namespace App\Livewire\Lottery;

use App\Actions\Game\SaveGameAction;
use App\DataTransferObjects\GameGenerationRequest;
use App\Livewire\Concerns\WithLotteryContext;
use App\Models\Lottery;
use App\Services\Lottery\LotteryGameGeneratorService;
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

    public ?string $statusMessage = null;

    public function mount(Lottery $lottery): void
    {
        $this->lottery = $lottery;
        $this->numbersPerGame = $lottery->min_numbers_per_game;
    }

    /**
     * @return array<string, string>
     */
    public function strategyOptions(LotteryGameGeneratorService $generator): array
    {
        $options = [];

        foreach ($generator->availableStrategies() as $key => $strategy) {
            $options[$key] = $strategy->label();
        }

        return $options;
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
        $this->statusMessage = null;

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

    public function save(SaveGameAction $action): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if ($this->previewGames === [] || $this->pricePerGame === null) {
            return;
        }

        $action->execute(auth()->user(), $this->lottery, $this->previewGames, $this->strategy, $this->pricePerGame);

        $this->previewGames = [];
        $this->statusMessage = 'Jogos salvos com sucesso!';
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

    public function render(LotteryGameGeneratorService $generator, LotteryPricingService $pricing)
    {
        return view('livewire.lottery.generator', [
            'strategyOptions' => $this->strategyOptions($generator),
            'estimatedBatchPrice' => $this->estimatedPrice($pricing),
        ]);
    }
}
