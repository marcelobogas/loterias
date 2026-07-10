<div class="mx-auto max-w-lg space-y-6">
    @if ($statusMessage)
        <x-ui.status-banner>{{ $statusMessage }}</x-ui.status-banner>
    @endif

    <x-ui.panel-card title="Perfil" subtitle="Atualize seu nome e e-mail.">
        <form wire:submit="updateProfile" class="space-y-4">
            <x-ui.text-input name="name" label="Nome" type="text" wire:model="name" />

            <x-ui.text-input name="email" label="E-mail" type="email" wire:model="email" />

            <x-ui.button type="submit">Salvar</x-ui.button>
        </form>
    </x-ui.panel-card>

    <x-ui.panel-card title="Senha" subtitle="Use uma senha longa e única.">
        <form wire:submit="updatePassword" class="space-y-4">
            <x-ui.text-input name="current_password" label="Senha atual" type="password" wire:model="current_password" />

            <x-ui.text-input name="password" label="Nova senha" type="password" wire:model="password" />

            <x-ui.text-input name="password_confirmation" label="Confirmar nova senha" type="password" wire:model="password_confirmation" />

            <x-ui.button type="submit">Atualizar senha</x-ui.button>
        </form>
    </x-ui.panel-card>
</div>
