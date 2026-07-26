<x-ui.lottery-theme :lottery="$lottery">
    <x-ui.page-header title="{{ 'Resultados · '.$lottery->name }}" subtitle="Histórico de concursos sincronizados.">
        <x-slot:actions>
            <x-ui.lottery-nav :lottery="$lottery" active="draws" />
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.text-input name="search" type="search" wire:model.live.debounce.400ms="search"
        placeholder="Buscar por número do concurso..." class="max-w-sm placeholder:text-slate-500" />

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
</x-ui.lottery-theme>
