<footer class="border-t border-white/10 bg-slate-900/80">
    <div class="mx-auto max-w-6xl px-4 py-4 text-center text-xs text-slate-500 sm:px-6">
        Jogo é aleatório: nenhuma análise estatística aumenta a probabilidade real de acerto. As ferramentas deste
        site organizam dados históricos e ajudam a estruturar apostas, mas não garantem prêmios. Jogue com
        responsabilidade — se sentir que perdeu o controle sobre o quanto joga, procure ajuda.
    </div>
    <div class="border-t border-white/5 py-2 text-center text-[11px] text-slate-600">
        Loterias v{{ app(\App\Services\VersionService::class)->current() }}
    </div>
</footer>
