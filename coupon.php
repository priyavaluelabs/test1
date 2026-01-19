<?php

namespace Klyp\Nomergy\Services\Stripe;

use Stripe\StripeClient;

abstract class BaseStripeService
{
    protected function stripe(): StripeClient
    {
        $stripe = app('stripe.client');
        
        return $stripe;
    }
}


error : "Return value of Klyp\\Nomergy\\Services\\Stripe\\BaseStripeService::stripe() must be an instance of Stripe\\StripeClient, null returned"s
