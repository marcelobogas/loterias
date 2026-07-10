<?php

namespace App\Providers;

use App\Contracts\LotteryResultsProviderContract;
use App\Services\CaixaApi\CaixaLotteryApiClient;
use App\Services\Lottery\LotteryGameGeneratorService;
use App\Services\Lottery\LotteryPricingService;
use App\Services\Lottery\Strategies\BalancedFilterStrategy;
use App\Services\Lottery\Strategies\HotColdStrategy;
use App\Services\Lottery\Strategies\RandomStrategy;
use App\Services\Lottery\Strategies\ReducedWheelHeuristicStrategy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LotteryResultsProviderContract::class, CaixaLotteryApiClient::class);

        $this->app->bind(LotteryGameGeneratorService::class, function ($app) {
            return new LotteryGameGeneratorService(
                strategies: [
                    $app->make(RandomStrategy::class),
                    $app->make(HotColdStrategy::class),
                    $app->make(BalancedFilterStrategy::class),
                    $app->make(ReducedWheelHeuristicStrategy::class),
                ],
                pricing: $app->make(LotteryPricingService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
