<?php

namespace App\Livewire\Lottery;

use App\Livewire\Concerns\WithLotteryContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Draws extends Component
{
    use WithLotteryContext;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $draws = $this->lottery->draws()
            ->when($this->search !== '', fn ($query) => $query->where('contest_number', 'like', "%{$this->search}%"))
            ->orderByDesc('contest_number')
            ->paginate(20);

        return view('livewire.lottery.draws', ['draws' => $draws]);
    }
}
