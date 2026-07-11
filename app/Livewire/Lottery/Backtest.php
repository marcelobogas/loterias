<?php

namespace App\Livewire\Lottery;

use App\DataTransferObjects\GameGenerationRequest;
use App\Livewire\Concerns\WithLotteryContext;
use App\Models\Lottery;
use App\Services\Lottery\LotteryBacktestService;
use App\Services\Lottery\LotteryGameGeneratorService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Backtest extends Component
{
    use WithLotteryContext;

    public int $numbersPerGame;

    public int $gamesCount = 5;

    public string $strategy = 'random';

    public string $bias = 'hot';

    public string $window = '100';

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    /**
     * Bumped on every run so the chart's wire:key changes and Alpine
     * re-initializes it (same pattern as the dashboard charts).
     */
    public int $runId = 0;

    public function mount(Lottery $lottery): void
    {
        $this->lottery = $lottery;
        $this->numbersPerGame = $lottery->min_numbers_per_game;
    }

    /**
     * @return array<int|string, string>
     */
    public function windowOptions(): array
    {
        return [
            '50' => 'Últimos 50',
            '100' => 'Últimos 100',
            '200' => 'Últimos 200',
            'all' => 'Todo o histórico',
        ];
    }

    /**
     * The reduced wheel needs a user-provided pool and its goal is pair
     * coverage across games, not per-game hit rate — it doesn't belong in
     * a hit-distribution backtest.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function strategyDetails(LotteryGameGeneratorService $generator): array
    {
        $details = [];

        foreach ($generator->availableStrategies() as $key => $strategy) {
            if ($key === 'reduced_wheel') {
                continue;
            }

            $details[$key] = [
                'label' => $strategy->label(),
                'description' => $strategy->description(),
            ];
        }

        return $details;
    }

    public function runBacktest(LotteryBacktestService $backtest): void
    {
        $this->validate([
            'numbersPerGame' => "required|integer|min:{$this->lottery->min_numbers_per_game}|max:{$this->lottery->max_numbers_per_game}",
            'gamesCount' => 'required|integer|min:1|max:'.LotteryBacktestService::MAX_GAMES,
        ]);

        $request = new GameGenerationRequest(
            numbersPerGame: $this->numbersPerGame,
            gamesCount: $this->gamesCount,
            strategy: $this->strategy,
            bias: $this->bias,
        );

        $this->result = $backtest->run(
            $this->lottery,
            $request,
            $this->window === 'all' ? null : (int) $this->window,
        );

        $this->runId++;
    }

    public function render(LotteryGameGeneratorService $generator)
    {
        return view('livewire.lottery.backtest', [
            'strategyDetails' => $this->strategyDetails($generator),
        ]);
    }
}
