<x-ui.lottery-theme :lottery="$lottery">
    <x-ui.page-header title="{{ 'Simulador · '.$lottery->name }}" subtitle="Testa uma estratégia contra os concursos reais e compara com a curva matemática exata.">
        <x-slot:actions>
            <x-ui.lottery-nav :lottery="$lottery" active="backtest" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.panel-card title="Configuração" class="lg:col-span-1">
            <div class="space-y-4">
                <x-ui.select-input name="btStrategy" label="Estratégia" wire:model.live="strategy">
                    @foreach ($strategyDetails as $key => $details)
                        <option value="{{ $key }}">{{ $details['label'] }}</option>
                    @endforeach
                </x-ui.select-input>

                <p class="text-xs text-slate-500">{{ $strategyDetails[$strategy]['description'] ?? '' }}</p>

                @if ($strategy === 'hot_cold')
                    <x-ui.select-input name="btBias" label="Viés" wire:model.live="bias">
                        <option value="hot">Números quentes (mais frequentes)</option>
                        <option value="cold">Números frios (mais atrasados)</option>
                    </x-ui.select-input>
                @endif

                <x-ui.text-input name="btNumbersPerGame" label="Quantidade de números por jogo" type="number"
                    wire:model.live="numbersPerGame" min="{{ $lottery->min_numbers_per_game }}" max="{{ $lottery->max_numbers_per_game }}" />

                <x-ui.text-input name="btGamesCount" label="Quantidade de jogos" type="number"
                    wire:model.live="gamesCount" min="1" max="10" />

                <x-ui.select-input name="btWindow" label="Concursos a testar" wire:model.live="window">
                    @foreach ($this->windowOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select-input>

                <x-ui.button wire:click="runBacktest" full>Rodar simulação</x-ui.button>

                <p class="text-xs text-slate-500">
                    O resultado esperado de qualquer estratégia é a curva teórica (hipergeométrica). Se uma simulação parecer "melhor", é variância, não vantagem — rode de novo e ela muda. As estratégias baseadas em histórico até "enxergam" aqui os próprios concursos testados, vantagem impossível na vida real, e mesmo assim a curva não desloca.
                </p>
            </div>
        </x-ui.panel-card>

        <div class="space-y-4 lg:col-span-2">
            @if ($result !== null)
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-ui.stat-card label="Concursos testados" value="{{ $result['drawsTested'] }}" :hint="count($result['games']).' jogo(s) por concurso'" />
                    <x-ui.stat-card label="Custo total" value="R$ {{ number_format($result['totalCost'], 2, ',', '.') }}" :hint="'R$ '.number_format($result['pricePerGame'], 2, ',', '.').' por jogo'" />
                    <x-ui.stat-card label="Retorno total" value="R$ {{ number_format($result['totalReturn'], 2, ',', '.') }}" />
                    <x-ui.stat-card label="Saldo" value="R$ {{ number_format($result['net'], 2, ',', '.') }}"
                        :tone="$result['net'] >= 0 ? 'positive' : 'default'"
                        :hint="'R$ '.number_format($result['returnPerDraw'], 2, ',', '.').' por concurso'" />
                </div>

                <x-ui.panel-card title="Acertos: observado × teórico" subtitle="Quantas vezes os jogos fizeram cada quantidade de pontos, contra o esperado pela matemática" wire:key="backtest-chart-{{ $runId }}">
                    <div class="h-64" wire:ignore x-data
                        x-init="window.renderComparisonBarChart($refs.canvas, @js(array_keys($result['observed'])), @js(array_values($result['observed'])), @js(array_map(fn ($v) => round($v, 2), array_values($result['expected']))), 'Observado', 'Teórico')">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </x-ui.panel-card>

                <x-ui.panel-card title="Jogos testados">
                    <div class="space-y-3">
                        @foreach ($result['games'] as $index => $numbers)
                            <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white/5 px-3 py-2">
                                <span class="w-6 text-xs text-slate-500">#{{ $index + 1 }}</span>
                                @foreach ($numbers as $number)
                                    <x-ui.number-ball :number="$number" size="sm" />
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </x-ui.panel-card>
            @else
                <x-ui.panel-card>
                    <p class="text-sm text-slate-400">Configure a simulação e clique em "Rodar simulação" para comparar a estratégia com a curva teórica usando os concursos reais.</p>
                </x-ui.panel-card>
            @endif
        </div>
    </div>
</x-ui.lottery-theme>
