<?php

namespace App\Services\Lottery;

use App\Models\Lottery;
use Carbon\CarbonInterface;
use RuntimeException;

class LotteryPricingService
{
    public function priceFor(Lottery $lottery, int $numbersChosen, ?CarbonInterface $atDate = null): float
    {
        $atDate ??= now();

        $tier = $lottery->priceTiers()
            ->where('numbers_chosen', $numbersChosen)
            ->where('effective_from', '<=', $atDate)
            ->where(function ($query) use ($atDate) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $atDate);
            })
            ->orderByDesc('effective_from')
            ->first();

        if (! $tier) {
            throw new RuntimeException(
                "Nenhuma tabela de preço encontrada para '{$lottery->slug}' com {$numbersChosen} números."
            );
        }

        return (float) $tier->price;
    }

    public function estimateBatch(Lottery $lottery, int $numbersChosen, int $gamesCount): float
    {
        return $this->priceFor($lottery, $numbersChosen) * $gamesCount;
    }
}
