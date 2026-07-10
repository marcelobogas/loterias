<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-white">Gerar jogos · {{ $lottery->name }}</h1>
            <p class="text-sm text-slate-400">Escolha uma estratégia e filtros; o preço segue a tabela oficial da Caixa.</p>
        </div>

        <nav class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('lottery.dashboard', $lottery) }}" wire:navigate class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Análises</a>
            <a href="{{ route('lottery.generator', $lottery) }}" wire:navigate class="rounded-lg bg-emerald-500/10 px-3 py-1.5 font-medium text-emerald-400">Gerar jogos</a>
            <a href="{{ route('lottery.draws', $lottery) }}" wire:navigate class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Resultados</a>
            @auth
                <a href="{{ route('lottery.my-games', $lottery) }}" wire:navigate class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Meus jogos</a>
            @endauth
        </nav>
    </div>

    @if ($statusMessage)
        <div class="rounded-lg bg-emerald-500/10 px-4 py-2 text-sm text-emerald-400">{{ $statusMessage }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.panel-card title="Configuração" class="lg:col-span-1">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Quantidade de números por jogo</label>
                    <input type="number" wire:model.live="numbersPerGame" min="{{ $lottery->min_numbers_per_game }}" max="{{ $lottery->max_numbers_per_game }}"
                        class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-500">Entre {{ $lottery->min_numbers_per_game }} e {{ $lottery->max_numbers_per_game }}.</p>
                    @error('numbersPerGame') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Quantidade de jogos</label>
                    <input type="number" wire:model.live="gamesCount" min="1" max="20"
                        class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                    @error('gamesCount') <p class="mt-1 text-sm text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Estratégia</label>
                    <select wire:model.live="strategy" class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach ($strategyOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($strategy === 'hot_cold')
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Viés</label>
                        <select wire:model.live="bias" class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="hot">Números quentes (mais frequentes)</option>
                            <option value="cold">Números frios (mais atrasados)</option>
                        </select>
                    </div>
                @endif

                @if ($strategy === 'balanced')
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-slate-400">Soma mín.</label>
                            <input type="number" wire:model.live="minSum" class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400">Soma máx.</label>
                            <input type="number" wire:model.live="maxSum" class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400">Pares mín.</label>
                            <input type="number" wire:model.live="minEvens" class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400">Pares máx.</label>
                            <input type="number" wire:model.live="maxEvens" class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 text-sm">
                        </div>
                    </div>
                @endif

                @if ($strategy === 'reduced_wheel')
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Seu conjunto de números (maior que a aposta)</label>
                        <textarea wire:model.live="poolInput" rows="3" placeholder="Ex: 1, 2, 3, 5, 8, 13, 14, 17, 18, 20, 21, 23"
                            class="mt-1 w-full rounded-lg border-white/10 bg-slate-800 text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        <p class="mt-1 text-xs text-slate-500">O sistema distribui esses números em jogos que cobrem o máximo de pares possível — não é uma garantia matemática formal.</p>
                    </div>
                @endif

                <div class="rounded-lg bg-white/5 px-3 py-2 text-sm">
                    <span class="text-slate-400">Custo estimado do lote:</span>
                    <span class="font-medium text-white">
                        {{ $estimatedBatchPrice !== null ? 'R$ '.number_format($estimatedBatchPrice, 2, ',', '.') : '—' }}
                    </span>
                </div>

                <button type="button" wire:click="generate" wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                    Gerar jogos
                </button>
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

                    <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="mt-4 w-full rounded-lg bg-emerald-500 px-4 py-2 font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                        @auth Salvar jogos @else Entrar para salvar @endauth
                    </button>
                </x-ui.panel-card>
            @else
                <x-ui.panel-card>
                    <p class="text-sm text-slate-400">Configure a estratégia e clique em "Gerar jogos" para ver a prévia.</p>
                </x-ui.panel-card>
            @endif
        </div>
    </div>
</div>
