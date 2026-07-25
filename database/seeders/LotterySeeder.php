<?php

namespace Database\Seeders;

use App\Models\Lottery;
use App\Models\LotteryPriceTier;
use App\Models\LotteryPrizeTier;
use Illuminate\Database\Seeder;

class LotterySeeder extends Seeder
{
    /**
     * Seed the lotteries, their price tiers and prize tiers.
     *
     * Prices below follow the Caixa "combination x unit price" model and are
     * versioned via effective_from/effective_to, since the official table
     * changes over time and should be re-checked against caixa.gov.br.
     */
    public function run(): void
    {
        $this->seedLotofacil();
        $this->seedMegaSena();
        $this->seedQuina();
        $this->seedLotomania();
    }

    private function seedLotofacil(): void
    {
        $lottery = Lottery::updateOrCreate(
            ['slug' => 'lotofacil'],
            [
                'name' => 'Lotofácil',
                'caixa_api_slug' => 'lotofacil',
                'universe_size' => 25,
                'numbers_drawn' => 15,
                'min_numbers_per_game' => 15,
                'max_numbers_per_game' => 20,
                'draw_days_of_week' => [1, 2, 3, 4, 5, 6],
                'is_active' => true,
                'color_hex' => '#930089',
                'description' => 'Escolha de 15 a 20 números entre 1 e 25. São sorteados 15 números e ganha quem acertar de 11 a 15.',
            ]
        );

        // Confirmed directly in the Caixa Loterias app (2026-07-10). Caixa
        // changes this from time to time — re-check periodically.
        $unitPrice = 3.50;
        $effectiveFrom = '2024-01-01';

        foreach (range(15, 20) as $numbersChosen) {
            $combinations = $this->combinations($numbersChosen, 15);

            LotteryPriceTier::updateOrCreate(
                [
                    'lottery_id' => $lottery->id,
                    'numbers_chosen' => $numbersChosen,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'combinations_count' => $combinations,
                    'price' => round($combinations * $unitPrice, 2),
                    'effective_to' => null,
                ]
            );
        }

        foreach (range(11, 15) as $hits) {
            LotteryPrizeTier::updateOrCreate(
                ['lottery_id' => $lottery->id, 'hits' => $hits],
                ['label' => "{$hits} acertos"]
            );
        }
    }

    private function seedMegaSena(): void
    {
        $lottery = Lottery::updateOrCreate(
            ['slug' => 'mega-sena'],
            [
                'name' => 'Mega-Sena',
                'caixa_api_slug' => 'megasena',
                'universe_size' => 60,
                'numbers_drawn' => 6,
                'min_numbers_per_game' => 6,
                'max_numbers_per_game' => 15,
                'draw_days_of_week' => [2, 4, 6],
                'is_active' => true,
                'color_hex' => '#209869',
                'description' => 'Escolha de 6 a 15 números entre 1 e 60. São sorteados 6 números e ganha quem acertar 4, 5 ou 6.',
            ]
        );

        // Confirmed by user (2026-07-25) — re-check periodically, Caixa
        // changes this from time to time.
        $unitPrice = 5.00;
        $effectiveFrom = '2024-01-01';

        foreach (range(6, 15) as $numbersChosen) {
            $combinations = $this->combinations($numbersChosen, 6);

            LotteryPriceTier::updateOrCreate(
                [
                    'lottery_id' => $lottery->id,
                    'numbers_chosen' => $numbersChosen,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'combinations_count' => $combinations,
                    'price' => round($combinations * $unitPrice, 2),
                    'effective_to' => null,
                ]
            );
        }

        foreach (range(4, 6) as $hits) {
            LotteryPrizeTier::updateOrCreate(
                ['lottery_id' => $lottery->id, 'hits' => $hits],
                ['label' => "{$hits} acertos"]
            );
        }
    }

    private function seedQuina(): void
    {
        $lottery = Lottery::updateOrCreate(
            ['slug' => 'quina'],
            [
                'name' => 'Quina',
                'caixa_api_slug' => 'quina',
                'universe_size' => 80,
                'numbers_drawn' => 5,
                'min_numbers_per_game' => 5,
                'max_numbers_per_game' => 15,
                'draw_days_of_week' => [1, 2, 3, 4, 5, 6],
                'is_active' => true,
                'color_hex' => '#260085',
                'description' => 'Escolha de 5 a 15 números entre 1 e 80. São sorteados 5 números e ganha quem acertar 2, 3, 4 ou 5.',
            ]
        );

        // Confirmed by user (2026-07-25) — re-check periodically, Caixa
        // changes this from time to time.
        $unitPrice = 2.50;
        $effectiveFrom = '2024-01-01';

        foreach (range(5, 15) as $numbersChosen) {
            $combinations = $this->combinations($numbersChosen, 5);

            LotteryPriceTier::updateOrCreate(
                [
                    'lottery_id' => $lottery->id,
                    'numbers_chosen' => $numbersChosen,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'combinations_count' => $combinations,
                    'price' => round($combinations * $unitPrice, 2),
                    'effective_to' => null,
                ]
            );
        }

        foreach (range(2, 5) as $hits) {
            LotteryPrizeTier::updateOrCreate(
                ['lottery_id' => $lottery->id, 'hits' => $hits],
                ['label' => "{$hits} acertos"]
            );
        }
    }

    /**
     * Lotomania has a fixed bet format (min == max == 50 numbers out of
     * 100), so unlike the other lotteries there's no "extra numbers"
     * combinatorial pricing — a single flat-price tier. It also uniquely
     * pays a prize for matching zero of the 20 drawn numbers.
     */
    private function seedLotomania(): void
    {
        $lottery = Lottery::updateOrCreate(
            ['slug' => 'lotomania'],
            [
                'name' => 'Lotomania',
                'caixa_api_slug' => 'lotomania',
                'universe_size' => 100,
                'numbers_drawn' => 20,
                'min_numbers_per_game' => 50,
                'max_numbers_per_game' => 50,
                'draw_days_of_week' => [1, 3, 5],
                'is_active' => true,
                'color_hex' => '#F78100',
                'description' => 'Escolha 50 números entre 1 e 100. São sorteados 20 números e ganha quem acertar 15, 16, 17, 18, 19 ou 20 — ou nenhum deles.',
            ]
        );

        // Confirmed by user (2026-07-25) — re-check periodically, Caixa
        // changes this from time to time.
        LotteryPriceTier::updateOrCreate(
            [
                'lottery_id' => $lottery->id,
                'numbers_chosen' => 50,
                'effective_from' => '2024-01-01',
            ],
            [
                'combinations_count' => 1,
                'price' => 3.00,
                'effective_to' => null,
            ]
        );

        foreach ([0, 15, 16, 17, 18, 19, 20] as $hits) {
            LotteryPrizeTier::updateOrCreate(
                ['lottery_id' => $lottery->id, 'hits' => $hits],
                ['label' => "{$hits} acertos"]
            );
        }
    }

    private function combinations(int $n, int $k): int
    {
        $result = '1';

        for ($i = 0; $i < $k; $i++) {
            $result = bcdiv(bcmul($result, (string) ($n - $i)), (string) ($i + 1));
        }

        return (int) $result;
    }
}
