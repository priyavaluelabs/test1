<?php

namespace Klyp\Nomergy\Services\Stripe;

use Stripe\StripeClient;
use Illuminate\Http\Request;
use Klyp\Nomergy\Models\UserPortal;

abstract class BaseStripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $region = 'US';
        
        // Get current HTTP request
        $request = app(Request::class);
        $trainerId = $request->route('trainerId')
            ?? $request->route('trainerId')
            ?? $request->input('trainer_id');

        if ($trainerId) {
            $trainer = UserPortal::find($trainerId);
            if ($trainer) {
                $corporatePartner = $trainer->getCorporatePartnerForFlexUser($trainer);
                if($corporatePartner) {
                    $region = $corporatePartner['region'];
                }
            }
        }

        // Initialize Stripe client
        $config = config("services.stripe.regions.$region");

        $this->stripe = new StripeClient($config['secret']);
    }
}