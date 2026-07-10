<div class="mx-auto max-w-lg space-y-6">
    @if ($statusMessage)
        <div class="rounded-lg bg-emerald-500/10 px-4 py-2 text-sm text-emerald-400">{{ $statusMessage }}</div>
    @endif

    <x-ui.panel-card title="Perfil" subtitle="Atualize seu nome e e-mail.">
        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-300">Nome</label>
                <input wire:model="name" id="name" type="text"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('name') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-300">E-mail</label>
                <input wire:model="email" id="email" type="email"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                Salvar
            </button>
        </form>
    </x-ui.panel-card>

    <x-ui.panel-card title="Senha" subtitle="Use uma senha longa e única.">
        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-300">Senha atual</label>
                <input wire:model="current_password" id="current_password" type="password"
                    class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                @error('current_password') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
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
                class="rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                Atualizar senha
            </button>
        </form>
    </x-ui.panel-card>
</div>
