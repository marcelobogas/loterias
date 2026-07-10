<?php

namespace App\Livewire\Concerns;

use App\Models\Lottery;

trait WithLotteryContext
{
    public Lottery $lottery;

    public function mount(Lottery $lottery): void
    {
        $this->lottery = $lottery;
    }
}
