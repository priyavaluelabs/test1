<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share Stripe publishable key globally
        View::composer('*', function ($view) {
            $user = auth()->user();
            $region = $user?->corporatePartner?->region ?? 'US';

            $publicKey = config("stripe_regions.regions.$region.public");

            $view->with('stripePublicKey', $publicKey);
        });
    }
}
