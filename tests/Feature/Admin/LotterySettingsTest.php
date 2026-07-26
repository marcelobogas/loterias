<?php

use App\Livewire\Admin\LotterySettings;
use App\Models\Lottery;
use App\Models\User;
use Database\Seeders\LotterySeeder;
use Livewire\Livewire;

test('an admin can edit and persist a lottery config', function () {
    $this->seed(LotterySeeder::class);
    $admin = User::factory()->create(['is_admin' => true]);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(LotterySettings::class)
        ->call('edit', $lottery)
        ->set('form.is_active', false)
        ->set('form.draw_days_of_week', [1, 2, 3])
        ->call('save')
        ->assertDispatched('flash', message: 'Loteria atualizada.');

    $lottery->refresh();

    expect($lottery->is_active)->toBeFalse()
        ->and($lottery->draw_days_of_week)->toBe([1, 2, 3]);
});

test('toggling a fully configured lottery flips is_active', function () {
    $this->seed(LotterySeeder::class);
    $admin = User::factory()->create(['is_admin' => true]);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(LotterySettings::class)
        ->call('toggleActive', $lottery)
        ->assertDispatched('flash', message: 'Lotofácil desativada.');

    expect($lottery->refresh()->is_active)->toBeFalse();

    Livewire::actingAs($admin)
        ->test(LotterySettings::class)
        ->call('toggleActive', $lottery)
        ->assertDispatched('flash', message: 'Lotofácil ativada.');

    expect($lottery->refresh()->is_active)->toBeTrue();
});

test('activating a lottery missing required onboarding fields is blocked', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $lottery = Lottery::create([
        'slug' => 'nova-loteria',
        'name' => 'Nova Loteria',
        'universe_size' => 0,
        'numbers_drawn' => 0,
        'min_numbers_per_game' => 0,
        'max_numbers_per_game' => 0,
        'is_active' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(LotterySettings::class)
        ->call('toggleActive', $lottery)
        ->assertDispatched('flash', fn ($event, $params) => str_contains($params['message'], 'Preencha antes de ativar'));

    expect($lottery->refresh()->is_active)->toBeFalse();
});

test('cancel discards edits without saving', function () {
    $this->seed(LotterySeeder::class);
    $admin = User::factory()->create(['is_admin' => true]);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(LotterySettings::class)
        ->call('edit', $lottery)
        ->set('form.is_active', false)
        ->call('cancel')
        ->assertSet('editingId', null);

    expect($lottery->refresh()->is_active)->toBeTrue();
});
