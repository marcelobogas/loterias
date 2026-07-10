<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('register', Register::class)->name('register');
    Route::get('esqueci-senha', ForgotPassword::class)->name('password.request');
    Route::get('redefinir-senha/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('configuracoes/perfil', Profile::class)->name('profile.edit');

    Route::post('logout', function () {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
