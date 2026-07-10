<div class="space-y-6">
    <x-ui.page-header title="{{ $lottery->name.' · Concurso #'.$draw->contest_number }}">
        <x-slot:subtitle>{{ $draw->draw_date->format('d/m/Y') }} @if ($draw->location) · {{ $draw->location }} @endif</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('lottery.draws', $lottery) }}" wire:navigate class="text-sm text-slate-400 hover:text-white">
                &larr; Voltar aos resultados
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.panel-card title="Dezenas sorteadas">
        <div class="flex flex-wrap gap-2">
            @foreach ($draw->numbers as $number)
                <x-ui.number-ball :number="$number->number" variant="selected" size="lg" />
            @endforeach
        </div>
    </x-ui.panel-card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Acumulado" :value="$draw->accumulated ? 'Sim' : 'Não'" :tone="$draw->accumulated ? 'positive' : 'default'" />
        <x-ui.stat-card label="Arrecadação" value="{{ $draw->collection_amount ? 'R$ '.number_format($draw->collection_amount, 2, ',', '.') : '—' }}" />
        <x-ui.stat-card label="Próximo concurso" value="{{ $draw->next_contest_number ? '#'.$draw->next_contest_number : '—' }}" />
        <x-ui.stat-card label="Estimativa próximo prêmio" value="{{ $draw->estimated_next_prize ? 'R$ '.number_format($draw->estimated_next_prize, 2, ',', '.') : '—' }}" />
    </div>

    @if ($draw->prizeResults->isNotEmpty())
        <x-ui.panel-card title="Rateio de prêmios">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[420px] text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-slate-500">
                            <th class="pb-2">Faixa</th>
                            <th class="pb-2">Ganhadores</th>
                            <th class="pb-2">Prêmio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($draw->prizeResults->sortByDesc(fn ($result) => $result->prizeTier->hits ?? 0) as $result)
                            <tr>
                                <td class="py-2 text-slate-200">{{ $result->prizeTier->label ?? $result->prizeTier->hits.' acertos' }}</td>
                                <td class="py-2 text-slate-400">{{ number_format($result->winners_count, 0, ',', '.') }}</td>
                                <td class="py-2 text-slate-400">{{ $result->prize_amount ? 'R$ '.number_format($result->prize_amount, 2, ',', '.') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.panel-card>
    @endif
</div>
