<?php

namespace App\Livewire\Admin;

use App\Models\Lottery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LotterySettings extends Component
{
    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function edit(Lottery $lottery): void
    {
        $this->editingId = $lottery->id;
        $this->form = $lottery->only([
            'slug', 'name', 'caixa_api_slug', 'universe_size', 'numbers_drawn',
            'min_numbers_per_game', 'max_numbers_per_game', 'draw_days_of_week',
            'is_active', 'color_hex', 'description',
        ]);
        $this->form['draw_days_of_week'] ??= [];
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.slug' => ['required', 'alpha_dash', 'max:50'],
            'form.name' => ['required', 'max:100'],
            'form.caixa_api_slug' => ['nullable', 'max:50'],
            'form.universe_size' => ['required', 'integer', 'min:1'],
            'form.numbers_drawn' => ['required', 'integer', 'min:1'],
            'form.min_numbers_per_game' => ['required', 'integer', 'min:1'],
            'form.max_numbers_per_game' => ['required', 'integer', 'gte:form.min_numbers_per_game'],
            'form.draw_days_of_week' => ['array'],
            'form.is_active' => ['boolean'],
            'form.color_hex' => ['nullable', 'max:9'],
            'form.description' => ['nullable', 'max:1000'],
        ])['form'];

        Lottery::find($this->editingId)->update($data);

        $this->dispatch('flash', message: 'Loteria atualizada.');
        $this->editingId = null;
    }

    public function cancel(): void
    {
        $this->editingId = null;
    }

    public function toggleActive(Lottery $lottery): void
    {
        if (! $lottery->is_active) {
            $missing = collect([
                'universe_size' => $lottery->universe_size,
                'numbers_drawn' => $lottery->numbers_drawn,
                'min_numbers_per_game' => $lottery->min_numbers_per_game,
                'max_numbers_per_game' => $lottery->max_numbers_per_game,
            ])->filter(fn ($value) => ! $value)->keys();

            if ($missing->isNotEmpty()) {
                $this->dispatch('flash', message: "Preencha antes de ativar: {$missing->implode(', ')}.", type: 'error');

                return;
            }
        }

        $lottery->update(['is_active' => ! $lottery->is_active]);

        $this->dispatch('flash', message: $lottery->is_active
            ? "{$lottery->name} ativada."
            : "{$lottery->name} desativada.");
    }

    public function render()
    {
        return view('livewire.admin.lottery-settings', [
            'lotteries' => Lottery::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }
}
