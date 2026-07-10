<?php

namespace App\Console\Commands;

use App\Jobs\CheckPendingGamesJob;
use App\Models\Lottery;
use App\Services\Lottery\LotterySyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lottery:sync {slug? : Slug of a single lottery to sync (default: every active lottery)}')]
#[Description('Fetch the latest contest (and any small gap) from the Caixa API')]
class SyncLotteryDrawsCommand extends Command
{
    public function handle(LotterySyncService $service): int
    {
        $lotteries = $this->resolveLotteries();

        if ($lotteries->isEmpty()) {
            $this->error('Nenhuma loteria encontrada para sincronizar.');

            return self::FAILURE;
        }

        $syncedAnything = false;

        foreach ($lotteries as $lottery) {
            if (! $lottery->caixa_api_slug) {
                $this->warn("Loteria '{$lottery->slug}' não possui caixa_api_slug configurado, pulando.");

                continue;
            }

            $this->info("Sincronizando {$lottery->name}...");

            $log = $service->syncLatest($lottery);
            $syncedAnything = $syncedAnything || $log->contests_synced > 0;

            match ($log->status) {
                'success' => $this->info("  OK: {$log->contests_synced} concurso(s) sincronizado(s)."),
                'partial' => $this->warn("  Parcial: {$log->contests_synced} concurso(s). {$log->message}"),
                default => $this->error("  Falhou: {$log->message}"),
            };
        }

        if ($syncedAnything) {
            CheckPendingGamesJob::dispatch();
        }

        return self::SUCCESS;
    }

    private function resolveLotteries()
    {
        if ($slug = $this->argument('slug')) {
            return Lottery::where('slug', $slug)->get();
        }

        return Lottery::active()->get();
    }
}
