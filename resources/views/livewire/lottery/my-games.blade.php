<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-white">Meus jogos · {{ $lottery->name }}</h1>
            <p class="text-sm text-slate-400">Histórico dos seus jogos salvos e conferência automática.</p>
        </div>

        <nav class="flex gap-2 text-sm">
            <a href="{{ route('lottery.dashboard', $lottery) }}" wire:navigate class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Análises</a>
            <a href="{{ route('lottery.generator', $lottery) }}" wire:navigate class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Gerar jogos</a>
            <a href="{{ route('lottery.my-games', $lottery) }}" wire:navigate class="rounded-lg bg-emerald-500/10 px-3 py-1.5 font-medium text-emerald-400">Meus jogos</a>
        </nav>
    </div>

    @if ($checkStatusMessage)
        <div class="rounded-lg bg-emerald-500/10 px-4 py-2 text-sm text-emerald-400">{{ $checkStatusMessage }}</div>
    @endif

    <div class="grid grid-cols-3 gap-4">
        <x-ui.stat-card label="Total gasto" value="R$ {{ number_format($roi['spent'], 2, ',', '.') }}" />
        <x-ui.stat-card label="Total ganho" value="R$ {{ number_format($roi['won'], 2, ',', '.') }}" tone="positive" />
        <x-ui.stat-card label="Saldo" value="R$ {{ number_format($roi['net'], 2, ',', '.') }}" :tone="$roi['net'] >= 0 ? 'positive' : 'negative'" />
    </div>

    <button type="button" wire:click="checkNow" wire:loading.attr="disabled"
        class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-medium text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
        Conferir agora
    </button>

    <div class="space-y-3">
        @forelse ($games as $game)
            @php $lastCheck = $game->checks->sortByDesc('checked_at')->first(); @endphp
            <div class="rounded-xl border border-white/10 bg-slate-900/60 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($game->numbers->sortBy('number') as $number)
                            <x-ui.number-ball :number="$number->number" size="sm" />
                        @endforeach
                    </div>

                    <div class="text-right text-sm">
                        <p class="text-slate-400">{{ $game->created_at->format('d/m/Y H:i') }} · R$ {{ number_format($game->price, 2, ',', '.') }}</p>
                        @if ($lastCheck)
                            <p class="font-medium {{ $lastCheck->prize_amount > 0 ? 'text-emerald-400' : 'text-slate-300' }}">
                                {{ $lastCheck->hits }} acerto(s)
                                @if ($lastCheck->prize_amount > 0)
                                    · Prêmio: R$ {{ number_format($lastCheck->prize_amount, 2, ',', '.') }}
                                @endif
                            </p>
                        @else
                            <p class="text-amber-400">Aguardando sorteio</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-ui.panel-card>
                <p class="text-sm text-slate-400">Você ainda não salvou nenhum jogo desta loteria.</p>
                <a href="{{ route('lottery.generator', $lottery) }}" wire:navigate class="mt-2 inline-block text-sm text-emerald-400 hover:text-emerald-300">Gerar meu primeiro jogo &rarr;</a>
            </x-ui.panel-card>
        @endforelse
    </div>

    {{ $games->links() }}
</div>
