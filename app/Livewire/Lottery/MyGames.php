<?php

namespace App\Livewire\Lottery;

use App\Livewire\Concerns\WithLotteryContext;
use App\Services\Lottery\LotteryCheckingService;
use App\Services\Lottery\LotterySyncService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MyGames extends Component
{
    use WithLotteryContext;
    use WithPagination;

    public function checkNow(LotteryCheckingService $checker, LotterySyncService $sync): void
    {
        $this->maybeSyncOnDemand($sync);

        $checked = $checker->checkPendingForUser(auth()->user());

        $this->dispatch('flash', message: $checked > 0
            ? "{$checked} jogo(s) conferido(s) agora."
            : 'Nenhum concurso novo para conferir ainda.');
    }

    /**
     * Pulls the latest contest from the API before checking, so a manual
     * check works even when the scheduled sync hasn't run yet. Throttled
     * across all users to at most one API hit per lottery every 10 minutes.
     */
    private function maybeSyncOnDemand(LotterySyncService $sync): void
    {
        if (! $this->lottery->caixa_api_slug) {
            return;
        }

        $throttleKey = "lottery:{$this->lottery->id}:on-demand-sync";

        if (! Cache::add($throttleKey, now()->toIso8601String(), now()->addMinutes(10))) {
            return;
        }

        $sync->syncLatest($this->lottery);
    }

    public function deleteGame(int $gameId): void
    {
        $game = auth()->user()->games()->where('lottery_id', $this->lottery->id)->findOrFail($gameId);

        $game->delete();

        $this->dispatch('flash', message: 'Jogo excluído.');
        $this->resetPage();
    }

    public function deleteAllGames(): void
    {
        $deleted = auth()->user()->games()->where('lottery_id', $this->lottery->id)->delete();

        $this->dispatch('flash', message: $deleted > 0
            ? "{$deleted} jogo(s) excluído(s)."
            : 'Nenhum jogo para excluir.');

        $this->resetPage();
    }

    public function render(LotteryCheckingService $checker)
    {
        $games = auth()->user()->games()
            ->where('lottery_id', $this->lottery->id)
            ->withCount('repeatGroup')
            ->with(['numbers', 'checks.prizeTier', 'checks.draw.numbers'])
            ->latest()
            ->paginate(10);

        return view('livewire.lottery.my-games', [
            'games' => $games,
            'roi' => $checker->roiSummary(auth()->user(), $this->lottery),
        ]);
    }
}
