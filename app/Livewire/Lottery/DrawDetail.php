<?php

namespace App\Livewire\Lottery;

use App\Livewire\Concerns\WithLotteryContext;
use App\Models\Lottery;
use Illuminate\Http\Response;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DrawDetail extends Component
{
    use WithLotteryContext;

    public int $contest;

    public function mount(Lottery $lottery, int $contest): void
    {
        $this->lottery = $lottery;
        $this->contest = $contest;
    }

    public function render()
    {
        $draw = $this->lottery->draws()
            ->where('contest_number', $this->contest)
            ->with(['numbers' => fn ($query) => $query->orderBy('number'), 'prizeResults.prizeTier'])
            ->first();

        abort_if(! $draw, Response::HTTP_NOT_FOUND);

        return view('livewire.lottery.draw-detail', ['draw' => $draw]);
    }
}
