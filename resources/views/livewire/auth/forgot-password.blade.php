<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Esqueci minha senha" subtitle="Enviaremos um link de redefinição para seu e-mail.">
        @if ($sent)
            <p class="text-sm text-emerald-400">
                Se o e-mail informado existir na nossa base, você receberá um link para redefinir sua senha.
            </p>
        @else
            <form wire:submit="sendResetLink" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300">E-mail</label>
                    <input wire:model="email" id="email" type="email" autofocus
                        class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                    @error('email') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                    Enviar link
                </button>
            </form>
        @endif

        <p class="mt-4 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" wire:navigate class="text-emerald-400 hover:text-emerald-300">Voltar ao login</a>
        </p>
    </x-ui.panel-card>
</div>
