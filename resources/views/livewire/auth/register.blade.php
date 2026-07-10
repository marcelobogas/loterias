<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Criar conta" subtitle="Salve seus jogos e receba a conferência automática dos resultados.">
        <form wire:submit="register" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-300">Nome</label>
                <input wire:model="name" id="name" type="text" autofocus autocomplete="name"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('name') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">E-mail</label>
                <input wire:model="email" id="email" type="email" autocomplete="username"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-300">Senha</label>
                <input wire:model="password" id="password" type="password" autocomplete="new-password"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('password') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Confirmar senha</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                Criar conta
            </button>

            <p class="text-center text-sm text-slate-400">
                Já tem conta?
                <a href="{{ route('login') }}" wire:navigate class="text-emerald-400 hover:text-emerald-300">Entrar</a>
            </p>
        </form>
    </x-ui.panel-card>
</div>
