<?php

return [

    // Stripe Account Status - Titles
    'account_status' => [
        'pending' => [
            'title'   => 'Onboarding in Progress',
            'message' => 'Your account details are under review. Please complete the pending verification steps to continue.',
        ],

        'restricted' => [
            'title'   => 'Account Restricted',
            'message' => 'Your Stripe account has some pending requirements. Payouts and charges are temporarily unavailable until these are resolved.',
        ],

        'restricted_soon' => [
            'title'   => 'Action Required Soon',
            'message' => 'Additional information is needed to keep your Stripe account active. Please complete the required steps before the deadline.',
        ],

        'enabled' => [
            'title'   => 'Account Enabled',
            'message' => 'Your Stripe account is active. Some additional details may be required later.',
        ],

        'complete' => [
            'title'   => 'Onboarding Completed!',
            'message' => 'Your Stripe account is active and ready to use.',
        ],

        'rejected' => [
            'title'   => 'Account Not Approved',
            'message' => 'Your Stripe account could not be approved. Please contact support for more information.',
        ],

        'unknown' => [
            'title'   => 'Account Status Unknown',
            'message' => 'We are unable to determine your Stripe account status at the moment.',
        ],
    ],
];



====


  public function title(): string
    {
        return trans("stripe.account_status.{$this->value}.title");
    }

    public function message(): string
    {
        return trans("stripe.account_status.{$this->value}.message");
    }

