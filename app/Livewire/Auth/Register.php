<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(CreatesNewUsers $creator): void
    {
        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
