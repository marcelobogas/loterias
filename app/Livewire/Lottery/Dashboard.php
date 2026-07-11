<?php

namespace App\Livewire\Lottery;

use App\Livewire\Concerns\WithLotteryContext;
use App\Services\Lottery\LotteryStatisticsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithLotteryContext;

    #[Url]
    public string $window = '50';

    #[Url]
    public string $frequencySort = 'numeric';

    /**
     * @return array<int|string, string>
     */
    public function windowOptions(): array
    {
        return [
            '10' => 'Últimos 10',
            '25' => 'Últimos 25',
            '50' => 'Últimos 50',
            '100' => 'Últimos 100',
            'all' => 'Todo o histórico',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function frequencySortOptions(): array
    {
        return [
            'numeric' => 'Ordem numérica',
            'desc' => 'Maiores saídas',
            'asc' => 'Menores saídas',
        ];
    }

    private function windowValue(): ?int
    {
        return $this->window === 'all' ? null : (int) $this->window;
    }

    public function render(LotteryStatisticsService $stats)
    {
        $window = $this->windowValue();

        $frequency = match ($this->frequencySort) {
            'desc' => $stats->frequencyTable($this->lottery, $window)->sortDesc(),
            'asc' => $stats->frequencyTable($this->lottery, $window)->sort(),
            default => $stats->frequencyTable($this->lottery, $window),
        };

        return view('livewire.lottery.dashboard', [
            'latestDraw' => $this->lottery->latestDraw(),
            'drawsCount' => $this->lottery->draws()->count(),
            'frequency' => $frequency,
            'delay' => $stats->delayTable($this->lottery),
            'sumDistribution' => $stats->sumDistribution($this->lottery, $window),
            'parityDistribution' => $stats->parityDistribution($this->lottery, $window),
            'topPairs' => $stats->topCoOccurringPairs($this->lottery, $window),
        ]);
    }
}
