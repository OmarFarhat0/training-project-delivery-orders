<?php

namespace App\Providers;

use App\Contracts\DeliveryFeeCalculatorInterface;
use App\Services\DeliveryFee\PercentageDeliveryFeeCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DeliveryFeeCalculatorInterface::class,
            PercentageDeliveryFeeCalculator::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
    }
}
