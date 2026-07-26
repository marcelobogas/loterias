<?php

use App\Contracts\LotteryResultsProviderContract;
use App\Jobs\RunLotteryBackfillJob;
use App\Livewire\Admin\SyncPanel;
use App\Models\Lottery;
use App\Models\User;
use Database\Seeders\LotterySeeder;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

test('an admin can trigger a manual sync and see a flash message', function () {
    $this->seed(LotterySeeder::class);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->app->instance(LotteryResultsProviderContract::class, fakeLotteryProvider(makeFakeDraw(1)));

    Livewire::actingAs($admin)
        ->test(SyncPanel::class)
        ->call('syncNow')
        ->assertDispatched('flash');
});

test('starting a backfill validates the interval size', function () {
    $this->seed(LotterySeeder::class);
    $admin = User::factory()->create(['is_admin' => true]);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(SyncPanel::class)
        ->set('backfillSlug', $lottery->slug)
        ->set('backfillFrom', 1)
        ->set('backfillTo', 500)
        ->call('startBackfill')
        ->assertHasErrors('backfillTo');
});

test('starting a backfill dispatches the job and blocks a concurrent duplicate for the same lottery', function () {
    Bus::fake();
    $this->seed(LotterySeeder::class);
    $admin = User::factory()->create(['is_admin' => true]);
    $lottery = Lottery::where('slug', 'lotofacil')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(SyncPanel::class)
        ->set('backfillSlug', $lottery->slug)
        ->set('backfillFrom', 1)
        ->set('backfillTo', 10)
        ->call('startBackfill')
        ->assertDispatched('flash');

    Bus::assertDispatched(RunLotteryBackfillJob::class);

    Livewire::actingAs($admin)
        ->test(SyncPanel::class)
        ->set('backfillSlug', $lottery->slug)
        ->set('backfillFrom', 1)
        ->set('backfillTo', 10)
        ->call('startBackfill')
        ->assertDispatched('flash', message: 'Já existe um backfill em andamento para esta loteria.');

    Bus::assertDispatchedTimes(RunLotteryBackfillJob::class, 1);
});
