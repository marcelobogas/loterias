<?php

use App\Livewire\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

require __DIR__.'/auth.php';
require __DIR__.'/lottery.php';
