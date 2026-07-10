<?php

namespace App\Console\Commands;

use App\Models\Lottery;
use App\Services\Lottery\LotteryImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lottery:import-csv {slug : Slug of the lottery} {path : Path to the CSV file}')]
#[Description('Import lottery draws from a CSV file (fallback when the Caixa API is unavailable)')]
class ImportLotteryCsvCommand extends Command
{
    public function handle(LotteryImportService $service): int
    {
        $lottery = Lottery::where('slug', $this->argument('slug'))->first();

        if (! $lottery) {
            $this->error("Loteria '{$this->argument('slug')}' não encontrada.");

            return self::FAILURE;
        }

        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("Arquivo não encontrado: {$path}");

            return self::FAILURE;
        }

        $result = $service->importFromCsv($lottery, $path);

        $this->info("{$result->imported} concurso(s) importado(s).");

        if ($result->errors !== []) {
            $this->warn(count($result->errors).' linha(s) com erro:');

            foreach ($result->errors as $error) {
                $this->line("  - {$error}");
            }
        }

        return self::SUCCESS;
    }
}
