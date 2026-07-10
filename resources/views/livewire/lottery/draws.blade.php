<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-white">Resultados · {{ $lottery->name }}</h1>
            <p class="text-sm text-slate-400">Histórico de concursos sincronizados.</p>
        </div>

        <nav class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('lottery.dashboard', $lottery) }}" wire:navigate
                class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Análises</a>
            <a href="{{ route('lottery.generator', $lottery) }}" wire:navigate
                class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Gerar jogos</a>
            <a href="{{ route('lottery.draws', $lottery) }}" wire:navigate
                class="rounded-lg bg-emerald-500/10 px-3 py-1.5 font-medium text-emerald-400">Resultados</a>
            @auth
                <a href="{{ route('lottery.my-games', $lottery) }}" wire:navigate
                    class="rounded-lg px-3 py-1.5 text-slate-400 hover:bg-white/5 hover:text-white">Meus jogos</a>
            @endauth
        </nav>
    </div>

    <input wire:model.live.debounce.400ms="search" type="search" placeholder="Buscar por número do concurso..."
        class="w-full max-w-sm rounded-lg border-white/10 bg-slate-800 text-slate-100 placeholder:text-slate-500 focus:border-emerald-500 focus:ring-emerald-500">

    <div class="space-y-2">
        @forelse ($draws as $draw)
            <a href="{{ route('lottery.draws.show', [$lottery, $draw->contest_number]) }}" wire:navigate
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-slate-900/60 px-4 py-3 hover:border-emerald-500/40">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-white">#{{ $draw->contest_number }}</span>
                    <span class="text-xs text-slate-500">{{ $draw->draw_date->format('d/m/Y') }}</span>
                    @if ($draw->accumulated)
                        <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs text-amber-400">Acumulou</span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-1">
                    @foreach ($draw->numbers()->orderBy('number')->pluck('number') as $number)
                        <x-ui.number-ball :number="$number" size="sm" />
                    @endforeach
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-400">Nenhum concurso encontrado.</p>
        @endforelse
    </div>

    {{ $draws->links() }}
</div>
