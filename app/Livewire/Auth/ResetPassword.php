<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ResetPassword extends Component
{
    public string $token;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword(ResetsUserPasswords $resetter): void
    {
        $status = Password::reset(
            [
                'token' => $this->token,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ],
            function ($user) use ($resetter) {
                $resetter->reset($user, [
                    'password' => $this->password,
                    'password_confirmation' => $this->password_confirmation,
                ]);

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
