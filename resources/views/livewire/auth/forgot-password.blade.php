<div class="mx-auto max-w-sm">
    <x-ui.panel-card title="Esqueci minha senha" subtitle="Enviaremos um link de redefinição para seu e-mail.">
        @if ($sent)
            <p class="text-sm text-emerald-400">
                Se o e-mail informado existir na nossa base, você receberá um link para redefinir sua senha.
            </p>
        @else
            <form wire:submit="sendResetLink" class="space-y-4">
                <x-ui.text-input name="email" label="E-mail" type="email" wire:model="email" autofocus />

                <x-ui.button type="submit" full>Enviar link</x-ui.button>
            </form>
        @endif

        <p class="mt-4 text-center text-sm text-slate-400">
            <a href="{{ route('login') }}" wire:navigate class="text-emerald-400 hover:text-emerald-300">Voltar ao login</a>
        </p>
    </x-ui.panel-card>
</div>
