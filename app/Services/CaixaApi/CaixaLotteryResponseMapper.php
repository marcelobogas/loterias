<?php

namespace App\Services\CaixaApi;

use App\DataTransferObjects\DrawData;
use App\DataTransferObjects\DrawPrizeResultData;
use Carbon\CarbonImmutable;

class CaixaLotteryResponseMapper
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function normalize(array $raw): DrawData
    {
        return new DrawData(
            contestNumber: (int) $raw['numero'],
            drawDate: $this->parseDate($raw['dataApuracao'] ?? null) ?? CarbonImmutable::now(),
            numbers: $this->parseNumbers($raw['listaDezenas'] ?? []),
            numbersInDrawOrder: $this->parseNumbers($raw['dezenasSorteadasOrdemSorteio'] ?? []),
            accumulated: (bool) ($raw['acumulado'] ?? false),
            collectionAmount: $this->parseFloat($raw['valorArrecadado'] ?? null),
            accumulatedAmount: $this->parseFloat($raw['valorAcumuladoProximoConcurso'] ?? null),
            estimatedNextPrize: $this->parseFloat($raw['valorEstimadoProximoConcurso'] ?? null),
            nextContestNumber: isset($raw['numeroConcursoProximo']) ? (int) $raw['numeroConcursoProximo'] : null,
            nextDrawDate: $this->parseDate($raw['dataProximoConcurso'] ?? null),
            location: $raw['localSorteio'] ?? null,
            prizeResults: $this->parsePrizeResults($raw['listaRateioPremio'] ?? []),
            rawPayload: $raw,
        );
    }

    /**
     * @param  array<int, mixed>  $numbers
     * @return int[]
     */
    private function parseNumbers(array $numbers): array
    {
        return array_values(array_map(static fn ($number) => (int) $number, $numbers));
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('d/m/Y', $value)?->startOfDay();
    }

    private function parseFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $prizeResults
     * @return DrawPrizeResultData[]
     */
    private function parsePrizeResults(array $prizeResults): array
    {
        return array_values(array_filter(array_map(function (array $tier) {
            $label = $tier['descricaoFaixa'] ?? null;

            if (! preg_match('/(\d+)/', (string) $label, $matches)) {
                return null;
            }

            return new DrawPrizeResultData(
                hits: (int) $matches[1],
                label: $label,
                winnersCount: (int) ($tier['numeroDeGanhadores'] ?? 0),
                prizeAmount: $this->parseFloat($tier['valorPremio'] ?? null),
            );
        }, $prizeResults)));
    }
}
