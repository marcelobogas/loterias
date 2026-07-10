<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Entrar" subtitle="Acesse sua conta para salvar e conferir seus jogos.">
        <form wire:submit="login" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">E-mail</label>
                <input wire:model="email" id="email" type="email" autofocus autocomplete="username"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-300">Senha</label>
                <input wire:model="password" id="password" type="password" autocomplete="current-password"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('password') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input wire:model="remember" type="checkbox" class="rounded border-white/10 bg-slate-800 text-emerald-500 focus:ring-emerald-500">
                Lembrar de mim
            </label>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                Entrar
            </button>

            <div class="flex items-center justify-between text-sm">
                <a href="{{ route('password.request') }}" wire:navigate class="text-slate-400 hover:text-white">Esqueci minha senha</a>
                <a href="{{ route('register') }}" wire:navigate class="text-slate-400 hover:text-white">Criar conta</a>
            </div>
        </form>
    </x-ui.panel-card>
</div>
