<?php

use App\Services\CaixaApi\CaixaLotteryResponseMapper;

function caixaLotofacilFixture(): array
{
    return json_decode(
        file_get_contents(base_path('tests/Fixtures/caixa/lotofacil-3731.json')),
        associative: true,
    );
}

test('it normalizes a real Caixa API payload', function () {
    $data = (new CaixaLotteryResponseMapper)->normalize(caixaLotofacilFixture());

    expect($data->contestNumber)->toBe(3731)
        ->and($data->drawDate->format('Y-m-d'))->toBe('2026-07-09')
        ->and($data->numbers)->toHaveCount(15)
        ->and($data->numbers)->toContain(1, 25)
        ->and($data->numbersInDrawOrder[0])->toBe(13)
        ->and($data->accumulated)->toBeFalse()
        ->and($data->nextContestNumber)->toBe(3732)
        ->and($data->location)->toBe('ESPAÇO DA SORTE')
        ->and($data->prizeResults)->toHaveCount(5);

    $fifteenHits = collect($data->prizeResults)->firstWhere('hits', 15);

    expect($fifteenHits->winnersCount)->toBe(2)
        ->and($fifteenHits->prizeAmount)->toBe(718484.46);
});

test('it tolerates missing optional fields', function () {
    $data = (new CaixaLotteryResponseMapper)->normalize([
        'numero' => 1,
        'dataApuracao' => '01/01/2020',
        'listaDezenas' => ['01', '02'],
    ]);

    expect($data->numbersInDrawOrder)->toBe([])
        ->and($data->accumulated)->toBeFalse()
        ->and($data->collectionAmount)->toBeNull()
        ->and($data->prizeResults)->toBe([]);
});
