<?php

use App\Livewire\Admin\LotterySettings;
use App\Livewire\Admin\SyncPanel;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/sincronizacao');
    Route::get('sincronizacao', SyncPanel::class)->name('sync');
    Route::get('loterias', LotterySettings::class)->name('lotteries');
});
