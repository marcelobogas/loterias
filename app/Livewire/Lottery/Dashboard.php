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

    private function windowValue(): ?int
    {
        return $this->window === 'all' ? null : (int) $this->window;
    }

    public function render(LotteryStatisticsService $stats)
    {
        $window = $this->windowValue();

        return view('livewire.lottery.dashboard', [
            'latestDraw' => $this->lottery->latestDraw(),
            'drawsCount' => $this->lottery->draws()->count(),
            'frequency' => $stats->frequencyTable($this->lottery, $window),
            'delay' => $stats->delayTable($this->lottery),
            'sumDistribution' => $stats->sumDistribution($this->lottery, $window),
            'parityDistribution' => $stats->parityDistribution($this->lottery, $window),
            'topPairs' => $stats->topCoOccurringPairs($this->lottery, $window),
        ]);
    }
}
