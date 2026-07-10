<?php

namespace App\Livewire\Lottery;

use App\Livewire\Concerns\WithLotteryContext;
use App\Services\Lottery\LotteryCheckingService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MyGames extends Component
{
    use WithLotteryContext;
    use WithPagination;

    public ?string $checkStatusMessage = null;

    public function checkNow(LotteryCheckingService $checker): void
    {
        $checked = $checker->checkPendingForUser(auth()->user());

        $this->checkStatusMessage = $checked > 0
            ? "{$checked} jogo(s) conferido(s) agora."
            : 'Nenhum concurso novo para conferir ainda.';
    }

    public function render(LotteryCheckingService $checker)
    {
        $games = auth()->user()->games()
            ->where('lottery_id', $this->lottery->id)
            ->with(['numbers', 'checks.prizeTier'])
            ->latest()
            ->paginate(10);

        return view('livewire.lottery.my-games', [
            'games' => $games,
            'roi' => $checker->roiSummary(auth()->user(), $this->lottery),
        ]);
    }
}
