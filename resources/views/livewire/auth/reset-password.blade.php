<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Redefinir senha">
        <form wire:submit="resetPassword" class="space-y-4">
            <x-ui.text-input name="email" label="E-mail" type="email" wire:model="email" autofocus />

            <x-ui.text-input name="password" label="Nova senha" type="password" wire:model="password" />

            <x-ui.text-input name="password_confirmation" label="Confirmar nova senha" type="password" wire:model="password_confirmation" />

            <x-ui.button type="submit" full>Redefinir senha</x-ui.button>
        </form>
    </x-ui.panel-card>
</div>
