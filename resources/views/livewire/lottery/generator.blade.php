<div class="space-y-6">
    <x-ui.page-header title="{{ 'Gerar jogos · '.$lottery->name }}" subtitle="Escolha uma estratégia e filtros; o preço segue a tabela oficial da Caixa.">
        <x-slot:actions>
            <x-ui.lottery-nav :lottery="$lottery" active="generator" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($statusMessage)
        <x-ui.status-banner>{{ $statusMessage }}</x-ui.status-banner>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.panel-card title="Configuração" class="lg:col-span-1">
            <div class="space-y-4">
                <x-ui.text-input name="numbersPerGame" label="Quantidade de números por jogo" type="number"
                    wire:model.live="numbersPerGame" min="{{ $lottery->min_numbers_per_game }}" max="{{ $lottery->max_numbers_per_game }}"
                    :hint="'Entre '.$lottery->min_numbers_per_game.' e '.$lottery->max_numbers_per_game.'.'" />

                <x-ui.text-input name="gamesCount" label="Quantidade de jogos" type="number"
                    wire:model.live="gamesCount" min="1" max="20" />

                <x-ui.select-input name="strategy" label="Estratégia" wire:model.live="strategy">
                    @foreach ($strategyOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select-input>

                @if ($strategy === 'hot_cold')
                    <x-ui.select-input name="bias" label="Viés" wire:model.live="bias">
                        <option value="hot">Números quentes (mais frequentes)</option>
                        <option value="cold">Números frios (mais atrasados)</option>
                    </x-ui.select-input>
                @endif

                @if ($strategy === 'balanced')
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.text-input name="minSum" label="Soma mín." type="number" wire:model.live="minSum" compact />
                        <x-ui.text-input name="maxSum" label="Soma máx." type="number" wire:model.live="maxSum" compact />
                        <x-ui.text-input name="minEvens" label="Pares mín." type="number" wire:model.live="minEvens" compact />
                        <x-ui.text-input name="maxEvens" label="Pares máx." type="number" wire:model.live="maxEvens" compact />
                    </div>
                @endif

                @if ($strategy === 'reduced_wheel')
                    <x-ui.textarea-input name="poolInput" label="Seu conjunto de números (maior que a aposta)"
                        wire:model.live="poolInput" placeholder="Ex: 1, 2, 3, 5, 8, 13, 14, 17, 18, 20, 21, 23"
                        hint="O sistema distribui esses números em jogos que cobrem o máximo de pares possível — não é uma garantia matemática formal." />
                @endif

                <div class="rounded-lg bg-white/5 px-3 py-2 text-sm">
                    <span class="text-slate-400">Custo estimado do lote:</span>
                    <span class="font-medium text-white">
                        {{ $estimatedBatchPrice !== null ? 'R$ '.number_format($estimatedBatchPrice, 2, ',', '.') : '—' }}
                    </span>
                </div>

                <x-ui.button wire:click="generate" full>Gerar jogos</x-ui.button>
            </div>
        </x-ui.panel-card>

        <div class="space-y-4 lg:col-span-2">
            @if ($previewGames !== [])
                <x-ui.panel-card title="Prévia dos jogos" :subtitle="count($previewGames).' jogo(s) · R$ '.number_format($totalPrice, 2, ',', '.').' no total'">
                    <div class="space-y-3">
                        @foreach ($previewGames as $index => $numbers)
                            <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white/5 px-3 py-2">
                                <span class="w-6 text-xs text-slate-500">#{{ $index + 1 }}</span>
                                @foreach ($numbers as $number)
                                    <x-ui.number-ball :number="$number" size="sm" />
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <x-ui.button wire:click="save" full class="mt-4">
                        @auth Salvar jogos @else Entrar para salvar @endauth
                    </x-ui.button>
                </x-ui.panel-card>
            @else
                <x-ui.panel-card>
                    <p class="text-sm text-slate-400">Configure a estratégia e clique em "Gerar jogos" para ver a prévia.</p>
                </x-ui.panel-card>
            @endif
        </div>
    </div>
</div>
