<?php

namespace App\Console\Commands;

use App\Contracts\LotteryResultsProviderContract;
use App\Enums\DrawSourceEnum;
use App\Exceptions\Lottery\LotteryApiNotFoundException;
use App\Exceptions\Lottery\LotteryApiUnavailableException;
use App\Models\Lottery;
use App\Models\LotterySyncLog;
use App\Services\Lottery\LotteryDrawPersister;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lottery:backfill {slug : Slug of the lottery} {--from=1 : First contest number} {--to= : Last contest number (default: latest available)}')]
#[Description('Backfill the full (or a ranged) contest history for a lottery from the Caixa API')]
class BackfillLotteryHistoryCommand extends Command
{
    public function handle(LotteryResultsProviderContract $provider, LotteryDrawPersister $persister): int
    {
        $lottery = Lottery::where('slug', $this->argument('slug'))->first();

        if (! $lottery) {
            $this->error("Loteria '{$this->argument('slug')}' não encontrada.");

            return self::FAILURE;
        }

        if (! $lottery->caixa_api_slug) {
            $this->error("Loteria '{$lottery->slug}' não possui caixa_api_slug configurado.");

            return self::FAILURE;
        }

        $from = (int) $this->option('from');
        $to = $this->option('to') !== null
            ? (int) $this->option('to')
            : $provider->fetchLatest($lottery->caixa_api_slug)->contestNumber;

        if ($from < 1 || $to < $from) {
            $this->error('Intervalo inválido.');

            return self::FAILURE;
        }

        $existingContests = $lottery->draws()
            ->whereBetween('contest_number', [$from, $to])
            ->pluck('contest_number')
            ->flip();

        $startedAt = now();
        $synced = 0;
        $failed = [];

        $bar = $this->output->createProgressBar($to - $from + 1);
        $bar->start();

        for ($contest = $from; $contest <= $to; $contest++) {
            $bar->advance();

            if ($existingContests->has($contest)) {
                continue;
            }

            try {
                $data = $provider->fetchByContest($lottery->caixa_api_slug, $contest);
                $persister->persist($lottery, $data, DrawSourceEnum::Api);
                $synced++;
            } catch (LotteryApiNotFoundException|LotteryApiUnavailableException $exception) {
                $failed[] = $contest;
                $this->newLine();
                $this->warn("Concurso {$contest} falhou: {$exception->getMessage()}");
            }

            usleep((int) config('caixa.backfill_sleep_ms') * 1000);
        }

        $bar->finish();
        $this->newLine();

        LotterySyncLog::create([
            'lottery_id' => $lottery->id,
            'type' => 'api',
            'status' => $failed === [] ? 'success' : 'partial',
            'contests_synced' => $synced,
            'message' => $failed !== [] ? 'Concursos que falharam: '.implode(', ', $failed) : null,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);

        $this->info("Concluído: {$synced} concurso(s) sincronizado(s), ".count($failed).' falha(s).');

        return self::SUCCESS;
    }
}
