<?php

use App\Livewire\Lottery\Dashboard;
use App\Livewire\Lottery\DrawDetail;
use App\Livewire\Lottery\Draws;
use App\Livewire\Lottery\Generator;
use App\Livewire\Lottery\MyGames;
use Illuminate\Support\Facades\Route;

Route::get('{lottery:slug}', Dashboard::class)->name('lottery.dashboard');
Route::get('{lottery:slug}/gerador', Generator::class)->name('lottery.generator');
Route::get('{lottery:slug}/resultados', Draws::class)->name('lottery.draws');
Route::get('{lottery:slug}/resultados/{contest}', DrawDetail::class)->name('lottery.draws.show');
Route::get('{lottery:slug}/meus-jogos', MyGames::class)->middleware('auth')->name('lottery.my-games');
