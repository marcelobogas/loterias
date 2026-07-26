<x-ui.lottery-theme :lottery="$lottery">
    <x-ui.page-header title="{{ 'Gerar jogos · '.$lottery->name }}" subtitle="Escolha uma estratégia e filtros; o preço segue a tabela oficial da Caixa.">
        <x-slot:actions>
            <x-ui.lottery-nav :lottery="$lottery" active="generator" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.panel-card title="Configuração" class="lg:col-span-1">
            <div class="space-y-4">
                <x-ui.text-input name="numbersPerGame" label="Quantidade de números por jogo" type="number"
                    wire:model.live="numbersPerGame" min="{{ $lottery->min_numbers_per_game }}" max="{{ $lottery->max_numbers_per_game }}"
                    :hint="'Entre '.$lottery->min_numbers_per_game.' e '.$lottery->max_numbers_per_game.'.'" />

                <x-ui.text-input name="gamesCount" label="Quantidade de jogos" type="number"
                    wire:model.live="gamesCount" min="1" max="20" />

                <x-ui.checkbox-input name="repeatEnabled" label="Repetir para os próximos concursos (Teimosinha)" wire:model.live="repeatEnabled" />

                @if ($repeatEnabled)
                    <x-ui.text-input name="repeatContests" label="Quantidade de concursos" type="number"
                        wire:model.live="repeatContests" min="2" max="8"
                        hint="Entre 2 e 8 concursos consecutivos, a partir do próximo concurso disponível." />
                @endif

                <div x-data="{ strategyInfoOpen: false }">
                    <x-ui.select-input name="strategy" label="Estratégia" wire:model.live="strategy">
                        @foreach ($strategyDetails as $key => $details)
                            <option value="{{ $key }}">{{ $details['label'] }}</option>
                        @endforeach
                    </x-ui.select-input>

                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ $strategyDetails[$strategy]['description'] ?? '' }}
                        <button type="button" @click="strategyInfoOpen = true" class="font-medium text-[var(--lottery-accent)] hover:text-[var(--lottery-accent-hover)]">Comparar estratégias</button>
                    </p>

                    <div x-show="strategyInfoOpen" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="absolute inset-0 bg-slate-950/70" @click="strategyInfoOpen = false"></div>

                        <div class="relative w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900 p-5 shadow-xl"
                            x-trap="strategyInfoOpen" @keydown.escape.window="strategyInfoOpen = false">
                            <div class="mb-4 flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-white">Estratégias de geração</h3>
                                    <p class="mt-0.5 text-sm text-slate-400">Nenhuma delas muda a probabilidade de acerto — cada sorteio é independente. Elas só mudam como os números são combinados.</p>
                                </div>
                                <button type="button" @click="strategyInfoOpen = false" class="shrink-0 rounded-lg p-1 text-slate-500 hover:bg-white/5 hover:text-slate-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach ($strategyDetails as $key => $details)
                                    <div class="rounded-lg p-3 {{ $key === $strategy ? 'bg-[var(--lottery-accent)]/10 ring-1 ring-[var(--lottery-accent)]/30' : 'bg-white/5' }}">
                                        <p class="text-sm font-medium {{ $key === $strategy ? 'text-[var(--lottery-accent)]' : 'text-slate-200' }}">{{ $details['label'] }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $details['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

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

                <div class="space-y-1 rounded-lg bg-white/5 px-3 py-2 text-sm">
                    <div>
                        <span class="text-slate-400">Custo estimado do lote{{ $repeatMultiplier > 1 ? ' (x'.$repeatMultiplier.' concursos)' : '' }}:</span>
                        <span class="font-medium text-white">
                            {{ $estimatedBatchPrice !== null ? 'R$ '.number_format($estimatedBatchPrice * $repeatMultiplier, 2, ',', '.') : '—' }}
                        </span>
                    </div>
                    @if ($expectedValue !== null && $pricePerGameEstimate = ($estimatedBatchPrice / max(1, $gamesCount)))
                        <div title="Probabilidades exatas (hipergeométrica) × prêmios do histórico: faixas fixas pelo valor vigente, faixas rateadas (14/15) pela mediana dos concursos com ganhador. É uma média de longo prazo, não uma previsão.">
                            <span class="text-slate-400">Retorno esperado por jogo:</span>
                            <span class="font-medium text-white">R$ {{ number_format($expectedValue['expectedReturn'], 2, ',', '.') }}</span>
                            <span class="text-xs text-slate-500">(≈{{ number_format($expectedValue['expectedReturn'] / $pricePerGameEstimate * 100, 0) }}% do custo)</span>
                        </div>
                    @endif
                </div>

                <x-ui.button wire:click="generate" full>Gerar jogos</x-ui.button>
            </div>
        </x-ui.panel-card>

        <div class="space-y-4 lg:col-span-2">
            @if ($previewGames !== [])
                <x-ui.panel-card title="Prévia dos jogos" :subtitle="count($previewGames).' jogo(s) · R$ '.number_format($totalPrice * $repeatMultiplier, 2, ',', '.').' no total'.($repeatMultiplier > 1 ? ' ('.$repeatMultiplier.' concursos)' : '')">
                    <x-slot:actions>
                        <button type="button" wire:click="clearGames"
                            class="rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 hover:bg-white/5">
                            Limpar jogos
                        </button>
                    </x-slot:actions>

                    <div class="space-y-3">
                        @foreach ($previewGames as $index => $numbers)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white/5 px-3 py-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="w-6 text-xs text-slate-500">#{{ $index + 1 }}</span>
                                    @foreach ($numbers as $number)
                                        <x-ui.number-ball :number="$number" size="sm" />
                                    @endforeach
                                </div>

                                @if ($strategy === 'balanced')
                                    @php
                                        $sum = array_sum($numbers);
                                        $withinRange = (is_null($minSum) || $sum >= $minSum) && (is_null($maxSum) || $sum <= $maxSum);
                                    @endphp
                                    <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-medium {{ $withinRange ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}"
                                        title="{{ $withinRange ? 'Dentro da faixa de soma definida' : 'Fora da faixa de soma definida (limite de tentativas atingido)' }}">
                                        Soma: {{ $sum }}
                                    </span>
                                @endif
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
</x-ui.lottery-theme>
