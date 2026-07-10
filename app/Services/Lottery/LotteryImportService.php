<?php

namespace App\Services\Lottery;

use App\DataTransferObjects\CsvImportResult;
use App\DataTransferObjects\DrawData;
use App\Enums\DrawSourceEnum;
use App\Models\Lottery;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Fallback path when the Caixa API is unreachable: expects a CSV with
 * headers `concurso,data,dezenas` where `data` is dd/mm/yyyy and `dezenas`
 * is a list of numbers separated by space, comma or semicolon.
 */
class LotteryImportService
{
    public function __construct(
        private readonly LotteryDrawPersister $persister,
    ) {}

    public function importFromCsv(Lottery $lottery, string $path): CsvImportResult
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException("Não foi possível abrir o arquivo: {$path}");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return new CsvImportResult(0, ['Arquivo vazio.']);
        }

        $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header);

        $imported = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            try {
                $data = $this->rowToDrawData($lottery, array_combine($header, $row));
                $this->persister->persist($lottery, $data, DrawSourceEnum::Csv);
                $imported++;
            } catch (\Throwable $exception) {
                $errors[] = "Linha {$line}: {$exception->getMessage()}";
            }
        }

        fclose($handle);

        return new CsvImportResult($imported, $errors);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function rowToDrawData(Lottery $lottery, array $row): DrawData
    {
        $contestNumber = (int) ($row['concurso'] ?? 0);

        if ($contestNumber <= 0) {
            throw new InvalidArgumentException('Número de concurso inválido.');
        }

        $drawDate = CarbonImmutable::createFromFormat('d/m/Y', trim($row['data'] ?? '')) ?: null;

        if (! $drawDate) {
            throw new InvalidArgumentException('Data inválida, use o formato dd/mm/aaaa.');
        }

        $numbers = collect(preg_split('/[;,\s]+/', trim($row['dezenas'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($number) => (int) $number)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (count($numbers) !== $lottery->numbers_drawn) {
            throw new InvalidArgumentException(sprintf(
                'Esperado %d dezenas, recebido %d.',
                $lottery->numbers_drawn,
                count($numbers),
            ));
        }

        foreach ($numbers as $number) {
            if ($number < 1 || $number > $lottery->universe_size) {
                throw new InvalidArgumentException(
                    "Dezena {$number} fora do intervalo 1-{$lottery->universe_size}."
                );
            }
        }

        return new DrawData(
            contestNumber: $contestNumber,
            drawDate: $drawDate,
            numbers: $numbers,
            numbersInDrawOrder: $numbers,
            accumulated: false,
            collectionAmount: null,
            accumulatedAmount: null,
            estimatedNextPrize: null,
            nextContestNumber: null,
            nextDrawDate: null,
            location: null,
            prizeResults: [],
            rawPayload: $row,
        );
    }
}
