<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

test('a user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'marcelo@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('login fails with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'marcelo@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});
