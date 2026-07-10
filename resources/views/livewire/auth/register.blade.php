<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Criar conta" subtitle="Salve seus jogos e receba a conferência automática dos resultados.">
        <form wire:submit="register" class="space-y-4">
            <x-ui.text-input name="name" label="Nome" type="text" wire:model="name" autofocus autocomplete="name" />

            <x-ui.text-input name="email" label="E-mail" type="email" wire:model="email" autocomplete="username" />

            <x-ui.text-input name="password" label="Senha" type="password" wire:model="password" autocomplete="new-password" />

            <x-ui.text-input name="password_confirmation" label="Confirmar senha" type="password" wire:model="password_confirmation" autocomplete="new-password" />

            <x-ui.button type="submit" full>Criar conta</x-ui.button>

            <p class="text-center text-sm text-slate-400">
                Já tem conta?
                <a href="{{ route('login') }}" wire:navigate class="text-emerald-400 hover:text-emerald-300">Entrar</a>
            </p>
        </form>
    </x-ui.panel-card>
</div>
