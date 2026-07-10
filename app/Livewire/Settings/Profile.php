<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfile(UpdatesUserProfileInformation $updater): void
    {
        $updater->update(Auth::user(), [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->statusMessage = 'Perfil atualizado com sucesso.';
    }

    public function updatePassword(UpdatesUserPasswords $updater): void
    {
        $updater->update(Auth::user(), [
            'current_password' => $this->current_password,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->statusMessage = 'Senha atualizada com sucesso.';
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}
