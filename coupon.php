<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\Stripe\ClubGlofoxStatusService;

class PaymentTab extends Component
{
    public bool $isGlofoxVerified = true;

    public function __construct()
    {
        $user = Auth::user();

        if ($user) {
            $clubs = app(ClubGlofoxStatusService::class)
                ->getForUser($user);

            // If ANY club is not verified → disable tabs
            $this->isGlofoxVerified = collect($clubs)
                ->every(fn ($club) => $club['is_verified'] === true);
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.payment-tab');
    }

    public function getPaymentTabs(): array
    {
        return [
            [
                'path' => 'stripe/onboarding',
                'name' => __('stripe.onboarding'),
                'always_enabled' => true, 
            ],
            [
                'path' => 'stripe/transactions',
                'name' => __('stripe.transactions'),
            ],
            [
                'path' => 'stripe/products',
                'name' => __('stripe.products'),
            ],
            [
                'path' => 'stripe/payouts',
                'name' => __('stripe.payouts'),
            ],
            [
                'path' => 'stripe/invoicing',
                'name' => __('stripe.invoicing'),
            ],
            [
                'path' => 'stripe/discount',
                'name' => __('stripe.discount'),
            ],
            [
                'path' => 'stripe/settings',
                'name' => __('stripe.settings'),
            ],
        ];
    }

    public function isActive($path): bool
    {
        return request()->is($path) || request()->is($path.'/*');
    }
}
