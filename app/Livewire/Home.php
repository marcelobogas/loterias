<?php

namespace App\Livewire;

use App\Models\Lottery;
use App\Services\Lottery\LotterySyncService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Home extends Component
{
    public function render(LotterySyncService $sync)
    {
        $lotteries = Lottery::orderByDesc('is_active')->orderBy('name')->get();

        $freshness = $lotteries->mapWithKeys(fn (Lottery $lottery) => [
            $lottery->id => $lottery->is_active ? $sync->isUpToDate($lottery) : null,
        ]);

        return view('livewire.home', [
            'lotteries' => $lotteries,
            'freshness' => $freshness,
        ]);
    }
}
