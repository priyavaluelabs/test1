<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Services\Stripe\PTbillingBaseStripeService;
use Klyp\Nomergy\Http\Traits\HasCurrencySymbolTrait;
use Klyp\Nomergy\Http\Traits\TrainerInfoTrait;

use function Psy\sh;

class PTBillingProductService extends PTbillingBaseStripeService
{
    use HasCurrencySymbolTrait, TrainerInfoTrait;




<?php

namespace Klyp\Nomergy\Services\Stripe;

use Stripe\StripeClient;

class PTbillingBaseStripeService
{
    protected StripeClient $stripe;

    protected function __construct()
    {
        $key = config("services.stripe.secret_key");

        $this->stripe = new StripeClient($key);
    }
}


[2026-01-06 05:05:06] production.ERROR: Illuminate\Contracts\Container\BindingResolutionException: Target [Klyp\Nomergy\Services\Stripe\PTBillingProductService] is not instantiable while building [Klyp\Nomergy\Http\Controllers\Stripe\ApiPTBillingProductController]. in D:\myproject\liftbrand-app-new\vendor\laravel\framework\src\Illuminate\Container\Container.php:978
