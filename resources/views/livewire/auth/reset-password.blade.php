<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Redefinir senha">
        <form wire:submit="resetPassword" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">E-mail</label>
                <input wire:model="email" id="email" type="email" autofocus
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-300">Nova senha</label>
                <input wire:model="password" id="password" type="password"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('password') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Confirmar nova senha</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                Redefinir senha
            </button>
        </form>
    </x-ui.panel-card>
</div>
