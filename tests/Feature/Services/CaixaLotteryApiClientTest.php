<?php

use App\Exceptions\Lottery\LotteryApiNotFoundException;
use App\Exceptions\Lottery\LotteryApiUnavailableException;
use App\Services\CaixaApi\CaixaLotteryApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function caixaFixturePath(): string
{
    return base_path('tests/Fixtures/caixa/lotofacil-3731.json');
}

test('fetchLatest returns normalized draw data on success', function () {
    Http::fake([
        '*/lotofacil' => Http::response(json_decode(file_get_contents(caixaFixturePath()), true), 200),
    ]);

    $draw = app(CaixaLotteryApiClient::class)->fetchLatest('lotofacil');

    expect($draw->contestNumber)->toBe(3731);
});

test('fetchByContest throws LotteryApiNotFoundException on non-2xx (matches real API 500-for-missing-contest behavior)', function () {
    Http::fake([
        '*/lotofacil/99999' => Http::response(['exceptionMessage' => 'nope'], 500),
    ]);

    app(CaixaLotteryApiClient::class)->fetchByContest('lotofacil', 99999);
})->throws(LotteryApiNotFoundException::class);

test('fetchLatest throws LotteryApiUnavailableException on connection failure', function () {
    Http::fake(function () {
        throw new ConnectionException('timed out');
    });

    app(CaixaLotteryApiClient::class)->fetchLatest('lotofacil');
})->throws(LotteryApiUnavailableException::class);
