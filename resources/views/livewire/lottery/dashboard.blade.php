<div class="space-y-6">
    <x-ui.page-header :title="$lottery->name" :subtitle="$drawsCount.' concurso(s) no histórico.'">
        <x-slot:actions>
            <x-ui.lottery-nav :lottery="$lottery" active="dashboard" />
        </x-slot:actions>
    </x-ui.page-header>

    @if ($latestDraw)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-ui.stat-card label="Último concurso" value="#{{ $latestDraw->contest_number }}" :hint="$latestDraw->draw_date->format('d/m/Y')" />
            <x-ui.stat-card label="Próximo sorteio" value="{{ $latestDraw->next_draw_date?->format('d/m/Y') ?? '—' }}" :hint="$latestDraw->next_contest_number ? 'Concurso #'.$latestDraw->next_contest_number : null" />
            <x-ui.stat-card label="Acumulado" :value="$latestDraw->accumulated ? 'Sim' : 'Não'" :tone="$latestDraw->accumulated ? 'positive' : 'default'" />
            <x-ui.stat-card label="Estimativa próximo concurso" value="{{ $latestDraw->estimated_next_prize ? 'R$ '.number_format($latestDraw->estimated_next_prize, 2, ',', '.') : '—' }}" />
            <x-ui.stat-card label="Local do sorteio" value="{{ $latestDraw->location ?? '—' }}" />
        </div>

        <x-ui.panel-card title="Dezenas do último sorteio" :subtitle="'Concurso #'.$latestDraw->contest_number">
            <div class="flex flex-wrap gap-2">
                @foreach ($latestDraw->numbers()->orderBy('number')->pluck('number') as $number)
                    <x-ui.number-ball :number="$number" variant="selected" />
                @endforeach
            </div>
        </x-ui.panel-card>
    @else
        <x-ui.panel-card>
            <p class="text-sm text-slate-400">Ainda não há concursos sincronizados para esta loteria.</p>
        </x-ui.panel-card>
    @endif

    <div class="flex items-center gap-2 text-sm">
        <span class="text-slate-400">Janela de análise:</span>
        @foreach ($this->windowOptions() as $value => $label)
            <button type="button" wire:click="$set('window', '{{ $value }}')"
                class="rounded-lg px-3 py-1 {{ $window === (string) $value ? 'bg-emerald-500 text-slate-950 font-medium' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.panel-card title="Frequência" subtitle="Quantas vezes cada dezena saiu na janela selecionada">
            @php $max = max($frequency->max(), 1); @endphp
            <div class="flex flex-wrap gap-2">
                @foreach ($frequency as $number => $count)
                    <div class="flex flex-col items-center gap-1" title="{{ $count }}x">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-medium text-white"
                            style="background-color: rgba(16, 185, 129, {{ 0.15 + 0.85 * ($count / $max) }})">
                            {{ str_pad($number, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="text-[10px] text-slate-500">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.panel-card>

        <x-ui.panel-card title="Atraso" subtitle="Concursos desde a última vez que cada dezena saiu (10 mais atrasadas)">
            <ol class="space-y-1.5">
                @foreach ($delay->take(10) as $number => $draws)
                    <li class="flex items-center gap-3 text-sm">
                        <x-ui.number-ball :number="$number" size="sm" />
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/5">
                            <div class="h-full rounded-full bg-sky-500" style="width: {{ $delay->max() ? min(100, ($draws / $delay->max()) * 100) : 0 }}%"></div>
                        </div>
                        <span class="w-16 text-right text-slate-400">{{ $draws }}x</span>
                    </li>
                @endforeach
            </ol>
        </x-ui.panel-card>

        <x-ui.panel-card title="Distribuição da soma" subtitle="Soma das 15 dezenas sorteadas, agrupada em faixas" wire:key="sum-panel-{{ $window }}">
            <div class="h-56" wire:ignore x-data
                x-init="window.renderBarChart($refs.canvas, @js($sumDistribution->keys()->map(fn ($v) => $v.'-'.($v + 9))), @js($sumDistribution->values()), '#10b981')">
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-ui.panel-card>

        <x-ui.panel-card title="Distribuição de paridade" subtitle="Quantidade de dezenas pares por sorteio" wire:key="parity-panel-{{ $window }}">
            <div class="h-56" wire:ignore x-data
                x-init="window.renderBarChart($refs.canvas, @js($parityDistribution->keys()), @js($parityDistribution->values()), '#0ea5e9')">
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-ui.panel-card>
    </div>

    <x-ui.panel-card title="Pares mais frequentes" subtitle="Dezenas que mais saíram juntas na janela selecionada">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            @foreach ($topPairs as $pair)
                <div class="flex items-center justify-between rounded-lg bg-white/5 px-3 py-2">
                    <div class="flex items-center gap-1.5">
                        <x-ui.number-ball :number="$pair['number_a']" size="sm" />
                        <x-ui.number-ball :number="$pair['number_b']" size="sm" />
                    </div>
                    <span class="text-sm text-slate-400">{{ $pair['total'] }}x</span>
                </div>
            @endforeach
        </div>
    </x-ui.panel-card>
</div>
