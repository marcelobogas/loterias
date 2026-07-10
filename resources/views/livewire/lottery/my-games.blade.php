<div class="space-y-6">
    <x-ui.page-header title="{{ 'Meus jogos · '.$lottery->name }}" subtitle="Histórico dos seus jogos salvos e conferência automática.">
        <x-slot:actions>
            <x-ui.lottery-nav :lottery="$lottery" active="my-games" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($checkStatusMessage)
        <x-ui.status-banner>{{ $checkStatusMessage }}</x-ui.status-banner>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <x-ui.stat-card label="Total gasto" value="R$ {{ number_format($roi['spent'], 2, ',', '.') }}" />
        <x-ui.stat-card label="Total ganho" value="R$ {{ number_format($roi['won'], 2, ',', '.') }}" tone="positive" />
        <x-ui.stat-card label="Saldo" value="R$ {{ number_format($roi['net'], 2, ',', '.') }}" :tone="$roi['net'] >= 0 ? 'positive' : 'negative'" />
    </div>

    <x-ui.button wire:click="checkNow" size="sm">Conferir agora</x-ui.button>

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

                    <div class="flex items-start gap-3">
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

                        <button type="button" wire:click="deleteGame({{ $game->id }})"
                            wire:confirm="Tem certeza que deseja excluir este jogo?"
                            wire:loading.attr="disabled" wire:key="delete-{{ $game->id }}"
                            class="shrink-0 rounded-lg p-1.5 text-slate-500 hover:bg-rose-500/10 hover:text-rose-400"
                            title="Excluir jogo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2m2 0-1 13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7h14Z" />
                            </svg>
                        </button>
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
