<?php

namespace App\Services\Lottery\Combinatorics;

use Generator;
use OverflowException;

class CombinationCalculator
{
    /**
     * Number of k-combinations of n, computed with bcmath so it stays exact
     * for larger universes (e.g. Lotomania's C(50,20)) instead of overflowing.
     */
    public function count(int $n, int $k): int
    {
        if ($k < 0 || $k > $n) {
            return 0;
        }

        $result = '1';

        for ($i = 0; $i < $k; $i++) {
            $result = bcdiv(bcmul($result, (string) ($n - $i)), (string) ($i + 1));
        }

        return (int) $result;
    }

    /**
     * Lazily yields every k-combination of $pool. Hard-capped: callers must
     * not enumerate combinatorial explosions, so this refuses to iterate
     * past $limit results.
     *
     * @param  int[]  $pool
     * @return Generator<int, int[]>
     *
     * @throws OverflowException
     */
    public function generate(array $pool, int $k, int $limit = 5000): Generator
    {
        if ($this->count(count($pool), $k) > $limit) {
            throw new OverflowException(
                "Refusing to enumerate combinations: exceeds the {$limit}-result limit."
            );
        }

        yield from $this->combinations(array_values($pool), $k);
    }

    /**
     * @param  int[]  $pool
     * @return Generator<int, int[]>
     */
    private function combinations(array $pool, int $k): Generator
    {
        if ($k === 0) {
            yield [];

            return;
        }

        $n = count($pool);

        if ($k > $n) {
            return;
        }

        foreach ($pool as $index => $item) {
            $rest = array_slice($pool, $index + 1);

            foreach ($this->combinations($rest, $k - 1) as $tail) {
                yield [$item, ...$tail];
            }
        }
    }
}
