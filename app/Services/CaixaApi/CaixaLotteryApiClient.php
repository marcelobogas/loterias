<?php

namespace App\Services\CaixaApi;

use App\Contracts\LotteryResultsProviderContract;
use App\DataTransferObjects\DrawData;
use App\Exceptions\Lottery\LotteryApiNotFoundException;
use App\Exceptions\Lottery\LotteryApiUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Caixa's undocumented public lottery API.
 *
 * Quirk observed live against the real endpoint: requesting a contest number
 * that has not been drawn yet (or does not exist) does not return a 404 — it
 * returns HTTP 500 with a generic exceptionMessage body. Since transport
 * failures (timeout, DNS, connection refused) throw ConnectionException
 * before a status code is even available, we treat those as "unavailable"
 * and any non-2xx HTTP response as "not found", which matches the endpoint's
 * real-world behavior.
 */
class CaixaLotteryApiClient implements LotteryResultsProviderContract
{
    public function __construct(
        private readonly CaixaLotteryResponseMapper $mapper,
    ) {}

    public function fetchLatest(string $apiSlug): DrawData
    {
        $response = $this->request("/{$apiSlug}");

        if ($response->failed()) {
            throw new LotteryApiUnavailableException(
                "Caixa API returned status {$response->status()} for latest '{$apiSlug}' contest."
            );
        }

        return $this->mapper->normalize($response->json());
    }

    public function fetchByContest(string $apiSlug, int $contestNumber): DrawData
    {
        $response = $this->request("/{$apiSlug}/{$contestNumber}");

        if ($response->failed()) {
            throw new LotteryApiNotFoundException(
                "Contest {$contestNumber} for '{$apiSlug}' is not available yet (status {$response->status()})."
            );
        }

        return $this->mapper->normalize($response->json());
    }

    private function request(string $path): Response
    {
        try {
            return Http::withHeaders([
                'User-Agent' => config('caixa.user_agent'),
                'Accept' => 'application/json',
            ])
                ->timeout(config('caixa.timeout'))
                ->retry(config('caixa.retries'), config('caixa.retry_sleep_ms'), throw: false)
                ->get(rtrim(config('caixa.base_url'), '/').$path);
        } catch (ConnectionException $exception) {
            throw new LotteryApiUnavailableException(
                "Could not reach Caixa API at {$path}: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }
}
