<?php

use App\Services\Lottery\Combinatorics\CombinationCalculator;

test('count matches known Lotofácil combination counts', function () {
    $calculator = new CombinationCalculator;

    expect($calculator->count(15, 15))->toBe(1)
        ->and($calculator->count(16, 15))->toBe(16)
        ->and($calculator->count(17, 15))->toBe(136)
        ->and($calculator->count(20, 15))->toBe(15504)
        ->and($calculator->count(5, 10))->toBe(0);
});

test('generate yields every combination exactly once for a small pool', function () {
    $calculator = new CombinationCalculator;

    $combinations = iterator_to_array($calculator->generate([1, 2, 3, 4], 2, limit: 100));

    expect($combinations)->toHaveCount(6)
        ->and($combinations)->toContain([1, 2])
        ->and($combinations)->toContain([3, 4]);
});

test('generate refuses to enumerate past the hard cap', function () {
    $calculator = new CombinationCalculator;

    iterator_to_array($calculator->generate(range(1, 20), 15, limit: 10));
})->throws(OverflowException::class);
