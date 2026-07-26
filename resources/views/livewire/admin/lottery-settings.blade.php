<div class="space-y-6">
    <div class="mb-2">
        <h1 class="text-2xl font-semibold text-white">Loterias</h1>
        <p class="mt-1 text-slate-400">Ative/desative loterias e ajuste a configuração de sincronização — sem terminal.</p>
    </div>

    <div class="space-y-3">
        @foreach ($lotteries as $lottery)
            <x-ui.panel-card>
                @if ($editingId === $lottery->id)
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-ui.text-input name="form.slug" label="Slug" wire:model="form.slug" />
                            <x-ui.text-input name="form.name" label="Nome" wire:model="form.name" />
                            <x-ui.text-input name="form.caixa_api_slug" label="Slug na API da Caixa" wire:model="form.caixa_api_slug" hint="Vazio se a loteria ainda não tiver integração com a API." />
                            <x-ui.text-input name="form.color_hex" label="Cor (hex)" wire:model="form.color_hex" placeholder="#10b981" />
                            <x-ui.text-input name="form.universe_size" label="Universo de números" type="number" wire:model="form.universe_size" />
                            <x-ui.text-input name="form.numbers_drawn" label="Números sorteados" type="number" wire:model="form.numbers_drawn" />
                            <x-ui.text-input name="form.min_numbers_per_game" label="Mín. números por jogo" type="number" wire:model="form.min_numbers_per_game" />
                            <x-ui.text-input name="form.max_numbers_per_game" label="Máx. números por jogo" type="number" wire:model="form.max_numbers_per_game" />
                        </div>

                        <x-ui.textarea-input name="form.description" label="Descrição" wire:model="form.description" />

                        <div>
                            <p class="block text-sm font-medium text-slate-300">Dias de sorteio</p>
                            <div class="mt-1 flex flex-wrap gap-3">
                                @foreach (['Seg' => 1, 'Ter' => 2, 'Qua' => 3, 'Qui' => 4, 'Sex' => 5, 'Sáb' => 6, 'Dom' => 7] as $label => $value)
                                    <x-ui.checkbox-input name="draw_day_{{ $value }}" label="{{ $label }}"
                                        wire:model="form.draw_days_of_week" value="{{ $value }}" />
                                @endforeach
                            </div>
                        </div>

                        <x-ui.checkbox-input name="form.is_active" label="Ativa (visível na Home e sincronizável)" wire:model="form.is_active" />

                        @if ($form['is_active'] && ! $form['caixa_api_slug'])
                            <p class="text-xs text-amber-400">Atenção: ativar sem "Slug na API da Caixa" deixa a loteria visível mas sem sincronização.</p>
                        @endif

                        <div class="flex gap-2">
                            <x-ui.button type="submit">Salvar</x-ui.button>
                            <button type="button" wire:click="cancel" class="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 hover:bg-white/5">
                                Cancelar
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                style="background-color: {{ $lottery->color_hex ?? '#10b981' }}; color: {{ $lottery->accentForegroundHex() }}">
                                {{ mb_substr($lottery->name, 0, 1) }}
                            </span>
                            <div>
                                <p class="font-medium text-white">{{ $lottery->name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $lottery->is_active ? 'Ativa' : 'Inativa' }}
                                    @if ($lottery->caixa_api_slug)
                                        · API: {{ $lottery->caixa_api_slug }}
                                    @else
                                        · sem API configurada
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            @if ($lottery->is_active)
                                <button type="button"
                                    x-on:click="swalConfirm('Isso remove {{ $lottery->name }} da Home e para a sincronização.', () => $wire.toggleActive('{{ $lottery->slug }}'))"
                                    class="rounded-lg border border-rose-500/30 px-3 py-1.5 text-sm text-rose-400 hover:bg-rose-500/10">
                                    Desativar
                                </button>
                            @else
                                <button type="button" wire:click="toggleActive('{{ $lottery->slug }}')"
                                    class="rounded-lg border border-emerald-500/30 px-3 py-1.5 text-sm text-emerald-400 hover:bg-emerald-500/10">
                                    Ativar
                                </button>
                            @endif

                            <button type="button" wire:click="edit('{{ $lottery->slug }}')"
                                class="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 hover:bg-white/5">
                                Editar
                            </button>
                        </div>
                    </div>
                @endif
            </x-ui.panel-card>
        @endforeach
    </div>
</div>
