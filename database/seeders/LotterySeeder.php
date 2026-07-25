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

        // Reajuste oficial vigente desde 09/07/2025 (Caixa Notícias,
        // 2025-07-03) — re-check periodically, Caixa changes this from
        // time to time.
        $unitPrice = 6.00;
        $effectiveFrom = '2025-07-09';

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

        // Reajuste oficial vigente desde 09/07/2025 (Caixa Notícias,
        // 2025-07-03) — re-check periodically, Caixa changes this from
        // time to time.
        $unitPrice = 3.00;
        $effectiveFrom = '2025-07-09';

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

        // Confirmed directly on loterias.caixa.gov.br (2026-07-25) — not
        // part of the 09/07/2025 reajuste, re-check periodically.
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

    /**
     * The remaining Caixa lottery products, shown on the home page as
     * "Em breve" (is_active => false) — no price/prize tiers, matching the
     * placeholder pattern used for Mega-Sena/Quina/Lotomania before they
     * were implemented.
     *
     * Dupla Sena, Timemania and Dia de Sorte roughly fit the "pick K of N"
     * schema (min/max/universe below are real), though each has an extra
     * mechanic the schema doesn't model yet: Dupla Sena draws twice per
     * contest, Timemania also picks a "Time do Coração", Dia de Sorte also
     * picks a "Mês da Sorte".
     *
     * Super Sete, +Milionária, Loteca and Federal do NOT fit this schema at
     * all — Super Sete is 7 independent columns of 0-9 (not one pool),
     * +Milionária has two separate pools (6 of 50 + 2 "trevos" of 6), Loteca
     * is match-outcome picks (not numbers), and Federal is a pre-printed
     * raffle ticket with no player choice. Their universe_size/numbers_drawn/
     * min/max below are meaningless placeholders that only satisfy the
     * NOT NULL columns — real support would need a schema/model change, not
     * just seed data.
     */
    private function seedUpcoming(): void
    {
        $upcoming = [
            [
                'slug' => 'dupla-sena',
                'name' => 'Dupla Sena',
                'caixa_api_slug' => 'duplasena',
                'universe_size' => 50,
                'numbers_drawn' => 6,
                'min_numbers_per_game' => 6,
                'max_numbers_per_game' => 15,
                'color_hex' => '#B0243C',
                'description' => 'Escolha de 6 a 15 números entre 1 e 50. São sorteados 6 números em dois sorteios por concurso.',
            ],
            [
                'slug' => 'timemania',
                'name' => 'Timemania',
                'caixa_api_slug' => 'timemania',
                'universe_size' => 80,
                'numbers_drawn' => 7,
                'min_numbers_per_game' => 10,
                'max_numbers_per_game' => 10,
                'color_hex' => '#2E7D32',
                'description' => 'Escolha 10 números entre 1 e 80 e um Time do Coração. São sorteados 7 números e um time por concurso.',
            ],
            [
                'slug' => 'dia-de-sorte',
                'name' => 'Dia de Sorte',
                'caixa_api_slug' => 'diadesorte',
                'universe_size' => 31,
                'numbers_drawn' => 7,
                'min_numbers_per_game' => 7,
                'max_numbers_per_game' => 15,
                'color_hex' => '#D4A017',
                'description' => 'Escolha de 7 a 15 números entre 1 e 31 e um Mês da Sorte. São sorteados 7 números e um mês por concurso.',
            ],
            [
                'slug' => 'super-sete',
                'name' => 'Super Sete',
                'caixa_api_slug' => 'supersete',
                'universe_size' => 10,
                'numbers_drawn' => 7,
                'min_numbers_per_game' => 7,
                'max_numbers_per_game' => 7,
                'color_hex' => '#00BFA5',
                'description' => 'Escolha 1 número de 0 a 9 em cada uma de 7 colunas independentes.',
            ],
            [
                'slug' => 'mais-milionaria',
                'name' => '+Milionária',
                'caixa_api_slug' => 'maismilionaria',
                'universe_size' => 50,
                'numbers_drawn' => 6,
                'min_numbers_per_game' => 6,
                'max_numbers_per_game' => 6,
                'color_hex' => '#6A1B9A',
                'description' => 'Escolha 6 números entre 1 e 50 e 2 trevos entre 1 e 6.',
            ],
            [
                'slug' => 'loteca',
                'name' => 'Loteca',
                'caixa_api_slug' => 'loteca',
                'universe_size' => 3,
                'numbers_drawn' => 14,
                'min_numbers_per_game' => 14,
                'max_numbers_per_game' => 14,
                'color_hex' => '#1565C0',
                'description' => 'Prognóstico de vitória, empate ou derrota em 14 jogos de futebol.',
            ],
            [
                'slug' => 'federal',
                'name' => 'Loteria Federal',
                'caixa_api_slug' => 'federal',
                'universe_size' => 1,
                'numbers_drawn' => 1,
                'min_numbers_per_game' => 1,
                'max_numbers_per_game' => 1,
                'color_hex' => '#616161',
                'description' => 'Sorteio tradicional por bilhetes numerados pré-impressos, sem escolha de números.',
            ],
        ];

        foreach ($upcoming as $data) {
            Lottery::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    ...$data,
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
