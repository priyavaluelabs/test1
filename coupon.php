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


===========


<div wire:ignore {{ $attributes->class(['rounded-t-lg']) }}>
    <ul 
        class="
            flex 
            flex-wrap 
            justify-center 
            -mb-px 
            text-sm 
            font-medium 
            text-center 
            text-gray-500 
            dark:text-gray-400
            space-x-2
            sm:space-x-3
            md:space-x-4
            xl:space-x-6
        "
        x-bind:class="$store.sidebar.isOpen ? 'xl:space-x-4' : 'xl:space-x-6'"
    >
        @foreach($getPaymentTabs as $getPaymentTab)
            <li>
                @php
                    $disabled = ! $isGlofoxVerified;
                @endphp

                <a 
                    @if(! $disabled)
                        href="{{ url($getPaymentTab['path']) }}"
                    @endif
                    class="
                        inline-flex 
                        items-center 
                        justify-center 
                        p-4 
                        border-b-2 
                        rounded-t-lg
                        {{ $disabled ? 'opacity-40 cursor-not-allowed pointer-events-none' : '' }}
                       @if($isActive($getPaymentTab['path']) && ! $disabled)
                            border-primary-600
                            dark:border-primary-500 
                            dark:text-primary-500 
                            text-primary-600
                        @else
                            border-transparent 
                            hover:text-gray-600 
                            hover:border-gray-300
                            dark:hover:text-gray-300 
                            group
                        @endif
                    "
                >
                    {{ $getPaymentTab['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
