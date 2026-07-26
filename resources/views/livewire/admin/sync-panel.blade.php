<div class="space-y-6">
    <div class="mb-2">
        <h1 class="text-2xl font-semibold text-white">Sincronização</h1>
        <p class="mt-1 text-slate-400">Dispare a sincronização com a API da Caixa e acompanhe o histórico sem precisar do terminal.</p>
    </div>

    <x-ui.panel-card title="Sincronizar agora">
        <p class="mb-3 text-sm text-slate-400">Roda a sincronização de todas as loterias ativas com API configurada (mesmo comportamento do <code>lottery:sync</code>).</p>
        <x-ui.button wire:click="syncNow">
            <span wire:loading.remove wire:target="syncNow">Sincronizar loterias ativas</span>
            <span wire:loading wire:target="syncNow">Sincronizando...</span>
        </x-ui.button>
    </x-ui.panel-card>

    <x-ui.panel-card title="Backfill de histórico" subtitle="Intervalo pequeno (máx. 100 concursos). Para histórico completo, use o terminal.">
        <form wire:submit="startBackfill" class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
            <x-ui.select-input name="backfillSlug" label="Loteria" wire:model="backfillSlug">
                <option value="">Selecione</option>
                @foreach ($lotteries as $lottery)
                    <option value="{{ $lottery->slug }}">{{ $lottery->name }}</option>
                @endforeach
            </x-ui.select-input>

            <x-ui.text-input name="backfillFrom" label="De (concurso)" type="number" wire:model="backfillFrom" min="1" />
            <x-ui.text-input name="backfillTo" label="Até (concurso)" type="number" wire:model="backfillTo" min="1" />

            <div>
                <label class="block select-none text-sm font-medium text-transparent" aria-hidden="true">&nbsp;</label>
                <x-ui.button type="submit" class="mt-1">
                    <span wire:loading.remove wire:target="startBackfill">Enfileirar backfill</span>
                    <span wire:loading wire:target="startBackfill">Enviando...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.panel-card>

    <x-ui.panel-card title="Histórico de sincronizações">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr>
                        <th class="pb-2 pr-4">Loteria</th>
                        <th class="pb-2 pr-4">Status</th>
                        <th class="pb-2 pr-4">Concursos</th>
                        <th class="pb-2 pr-4">Mensagem</th>
                        <th class="pb-2 pr-4">Início</th>
                        <th class="pb-2">Fim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-t border-white/5">
                            <td class="py-2 pr-4 text-slate-200">{{ $log->lottery?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">
                                <span class="rounded-full px-2 py-0.5 text-xs
                                    {{ match ($log->status) {
                                        'success' => 'bg-emerald-500/10 text-emerald-400',
                                        'partial' => 'bg-amber-500/10 text-amber-400',
                                        default => 'bg-rose-500/10 text-rose-400',
                                    } }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="py-2 pr-4 text-slate-300">{{ $log->contests_synced }}</td>
                            <td class="py-2 pr-4 text-xs text-slate-500">{{ $log->message ?? '—' }}</td>
                            <td class="py-2 pr-4 text-xs text-slate-500">{{ $log->started_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 text-xs text-slate-500">{{ $log->finished_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-slate-500">Nenhuma sincronização registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </x-ui.panel-card>
</div>
