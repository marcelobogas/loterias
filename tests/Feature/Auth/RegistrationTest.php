<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

test('a user can register and is logged in', function () {
    Livewire::test(Register::class)
        ->set('name', 'Marcelo Bogas')
        ->set('email', 'marcelo@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();

    expect(User::where('email', 'marcelo@example.com')->exists())->toBeTrue();
});

test('registration requires matching password confirmation', function () {
    Livewire::test(Register::class)
        ->set('name', 'Marcelo Bogas')
        ->set('email', 'marcelo@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors('password');

    $this->assertGuest();
});
