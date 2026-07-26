<?php

namespace App\Livewire\Admin;

use App\Jobs\CheckPendingGamesJob;
use App\Jobs\RunLotteryBackfillJob;
use App\Models\Lottery;
use App\Models\LotterySyncLog;
use App\Services\Lottery\LotterySyncService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SyncPanel extends Component
{
    use WithPagination;

    public string $backfillSlug = '';

    public ?int $backfillFrom = null;

    public ?int $backfillTo = null;

    public function syncNow(LotterySyncService $service): void
    {
        $summary = [];
        $syncedAnything = false;

        foreach (Lottery::active()->get() as $lottery) {
            if (! $lottery->caixa_api_slug) {
                continue;
            }

            $log = $service->syncLatest($lottery);
            $syncedAnything = $syncedAnything || $log->contests_synced > 0;
            $summary[] = "{$lottery->name}: {$log->status}";
        }

        if ($syncedAnything) {
            CheckPendingGamesJob::dispatch();
        }

        $this->dispatch('flash', message: $summary === []
            ? 'Nenhuma loteria ativa com API configurada.'
            : implode(' · ', $summary));
    }

    public function startBackfill(): void
    {
        $this->validate([
            'backfillSlug' => ['required', 'exists:lotteries,slug'],
            'backfillFrom' => ['required', 'integer', 'min:1'],
            'backfillTo' => [
                'required', 'integer', 'gte:backfillFrom',
                function ($attribute, $value, $fail) {
                    if ($value - $this->backfillFrom + 1 > 100) {
                        $fail('Máximo de 100 concursos por vez pelo painel. Para histórico completo, use o terminal.');
                    }
                },
            ],
        ]);

        $lockKey = "lottery:backfill-running:{$this->backfillSlug}";

        if (! Cache::add($lockKey, true, now()->addMinutes(10))) {
            $this->dispatch('flash', message: 'Já existe um backfill em andamento para esta loteria.', type: 'error');

            return;
        }

        RunLotteryBackfillJob::dispatch($this->backfillSlug, $this->backfillFrom, $this->backfillTo);

        $this->dispatch('flash', message: 'Backfill enfileirado. Acompanhe pelo histórico abaixo.');
    }

    public function render()
    {
        return view('livewire.admin.sync-panel', [
            'lotteries' => Lottery::orderBy('name')->get(),
            'logs' => LotterySyncLog::with('lottery')->latest('started_at')->paginate(15),
        ]);
    }
}
