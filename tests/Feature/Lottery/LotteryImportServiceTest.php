<?php

use App\Models\Lottery;
use App\Services\Lottery\LotteryImportService;

function lotofacilForImportTest(): Lottery
{
    return Lottery::create([
        'slug' => 'lotofacil',
        'name' => 'Lotofácil',
        'caixa_api_slug' => 'lotofacil',
        'universe_size' => 25,
        'numbers_drawn' => 15,
        'min_numbers_per_game' => 15,
        'max_numbers_per_game' => 20,
        'is_active' => true,
    ]);
}

test('it imports valid rows and reports errors for invalid ones', function () {
    $lottery = lotofacilForImportTest();

    $csv = <<<'CSV'
    concurso,data,dezenas
    3730,08/07/2026,"01,02,03,04,05,06,07,08,09,10,11,12,13,14,15"
    3731,09/07/2026,"01 02 03 04 05 06 07 08 09 10 11 12 13 14 16"
    abc,09/07/2026,"01,02,03,04,05,06,07,08,09,10,11,12,13,14,15"
    3733,09/07/2026,"01,02,03"
    CSV;

    $path = tempnam(sys_get_temp_dir(), 'lottery-csv');
    file_put_contents($path, $csv);

    $result = app(LotteryImportService::class)->importFromCsv($lottery, $path);

    unlink($path);

    expect($result->imported)->toBe(2)
        ->and($result->errors)->toHaveCount(2)
        ->and($lottery->draws()->count())->toBe(2);
});
