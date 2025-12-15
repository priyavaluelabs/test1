<?php

namespace App\Filament\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;
use Stripe\StripeClient;

class StripePayments extends Page
{
    protected static ?string $slug = 'stripe/transactions';
    protected static string $view = 'filament.pages.stripe.dashboard';

    public string $clientSecret;
    public string $type = 'payments';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        // Create a Payments session in the connected account
        $session = $stripe->accountSessions->create([
            'account' => $this->getConnectedAccountId(),
            'components' => [
                'payments' => [
                    'enabled' => true,
                    'features' => [
                        'refund_management' => true,
                        'dispute_management' => true,
                        'capture_payments' => true,
                        'destination_on_behalf_of_charge_management' => false,
                    ],
                ],
            ],
        ]);

        $this->clientSecret = $session->client_secret;
    }

    private function getConnectedAccountId(): string
    {
        return Auth::user()->stripe_account_id;
    }

    public function getHeading(): string
    {
        return __('stripe.transactions');
    }
}


Null check might be required.



Accessing stripe_account_id without null checks can cause fatal errors when users without Stripe accounts access these pages. Add ?string