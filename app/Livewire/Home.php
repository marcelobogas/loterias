<?php

namespace App\Livewire;

use App\Models\Lottery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.home', [
            'lotteries' => Lottery::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }
}
