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
        $this->seedUpcoming();
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

        $unitPrice = 3.00;
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

    /**
     * Placeholder for lotteries not yet supported, proving the "pick K of N"
     * schema needs no code changes to onboard a new game — only data.
     */
    private function seedUpcoming(): void
    {
        $upcoming = [
            [
                'slug' => 'mega-sena',
                'name' => 'Mega-Sena',
                'universe_size' => 60,
                'numbers_drawn' => 6,
                'min_numbers_per_game' => 6,
                'max_numbers_per_game' => 15,
                'color_hex' => '#209869',
                'description' => 'Em breve.',
            ],
            [
                'slug' => 'quina',
                'name' => 'Quina',
                'universe_size' => 80,
                'numbers_drawn' => 5,
                'min_numbers_per_game' => 5,
                'max_numbers_per_game' => 15,
                'color_hex' => '#260085',
                'description' => 'Em breve.',
            ],
            [
                'slug' => 'lotomania',
                'name' => 'Lotomania',
                'universe_size' => 100,
                'numbers_drawn' => 20,
                'min_numbers_per_game' => 50,
                'max_numbers_per_game' => 50,
                'color_hex' => '#F78100',
                'description' => 'Em breve.',
            ],
        ];

        foreach ($upcoming as $data) {
            Lottery::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    ...$data,
                    'caixa_api_slug' => $data['slug'],
                    'draw_days_of_week' => null,
                    'is_active' => false,
                ]
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
