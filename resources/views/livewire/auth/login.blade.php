<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Entrar" subtitle="Acesse sua conta para salvar e conferir seus jogos.">
        <form wire:submit="login" class="space-y-4">
            <x-ui.text-input name="email" label="E-mail" type="email" wire:model="email" autofocus autocomplete="username" />

            <x-ui.text-input name="password" label="Senha" type="password" wire:model="password" autocomplete="current-password" />

            <x-ui.checkbox-input name="remember" label="Lembrar de mim" wire:model="remember" />

            <x-ui.button type="submit" full>Entrar</x-ui.button>

            <div class="flex items-center justify-between text-sm">
                <a href="{{ route('password.request') }}" wire:navigate class="text-slate-400 hover:text-white">Esqueci minha senha</a>
                <a href="{{ route('register') }}" wire:navigate class="text-slate-400 hover:text-white">Criar conta</a>
            </div>
        </form>
    </x-ui.panel-card>
</div>
