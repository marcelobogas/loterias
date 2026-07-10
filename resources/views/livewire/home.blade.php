<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-white">Escolha uma loteria</h1>
        <p class="mt-1 text-slate-400">Análises, filtros combinatórios e geração assistida de jogos para as loterias da Caixa.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($lotteries as $lottery)
            @if ($lottery->is_active)
                <a href="{{ route('lottery.dashboard', $lottery) }}" wire:navigate
                    class="group rounded-2xl border border-white/10 bg-slate-900/60 p-5 transition hover:border-emerald-500/50 hover:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-slate-950"
                            style="background-color: {{ $lottery->color_hex ?? '#10b981' }}">
                            {{ mb_substr($lottery->name, 0, 1) }}
                        </span>
                        <div>
                            <h2 class="font-semibold text-white group-hover:text-emerald-400">{{ $lottery->name }}</h2>
                            <p class="text-xs text-slate-500">{{ $lottery->min_numbers_per_game }} a {{ $lottery->max_numbers_per_game }} números · sorteia {{ $lottery->numbers_drawn }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-slate-400">{{ $lottery->description }}</p>
                </a>
            @else
                <div class="rounded-2xl border border-white/5 bg-slate-900/30 p-5 opacity-60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-slate-300">
                            {{ mb_substr($lottery->name, 0, 1) }}
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-300">{{ $lottery->name }}</h2>
                            <p class="text-xs text-slate-500">Em breve</p>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
