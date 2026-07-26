<div class="mx-auto max-w-lg space-y-6">
    <x-ui.panel-card title="Perfil" subtitle="Atualize seu nome e e-mail.">
        <div class="mb-6 flex items-center gap-4">
            @if ($photo && $photo->isPreviewable())
                <img src="{{ $photo->temporaryUrl() }}" class="h-16 w-16 shrink-0 rounded-full object-cover">
            @elseif (auth()->user()->avatarUrl())
                <img src="{{ auth()->user()->avatarUrl() }}" class="h-16 w-16 shrink-0 rounded-full object-cover">
            @else
                <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[var(--lottery-accent)] text-xl font-bold text-[var(--lottery-on-accent)]">
                    {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                </span>
            @endif

            <div class="min-w-0 flex-1">
                <p class="font-semibold text-white">{{ $name }}</p>
                <p class="truncate text-sm text-slate-400">{{ $email }}</p>

                <form wire:submit="updatePhoto" class="mt-2 flex flex-wrap items-center gap-2">
                    <input type="file" wire:model="photo" accept="image/*"
                        class="text-xs text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-xs file:text-slate-200 hover:file:bg-slate-700">

                    @if ($photo)
                        <x-ui.button type="submit">Enviar</x-ui.button>
                    @endif

                    @if (auth()->user()->avatar_path)
                        <button type="button" wire:click="removePhoto" wire:loading.attr="disabled"
                            class="text-xs text-slate-500 hover:text-rose-400">Remover foto</button>
                    @endif
                </form>
                @error('photo') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
            </div>
        </div>

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
