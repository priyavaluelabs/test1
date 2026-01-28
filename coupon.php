<div class="rounded-t-lg">
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
        @foreach($this->getPaymentTabs() as $getPaymentTab)
            @php
                $alwaysEnabled = $getPaymentTab['always_enabled'] ?? false;
                $disabled = ! $isGlofoxVerified && ! $alwaysEnabled;
            @endphp

            <li>
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
                        @if($this->isActive($getPaymentTab['path']) && ! $disabled)
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

=====+


<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\Stripe\ClubGlofoxStatusService;
use Livewire\Attributes\On;

class PaymentTab extends Component
{
    public bool $isGlofoxVerified = false;

    public function mount(): void
    {
        $this->loadVerificationStatus();
    }

    // This listens when Verify button is clicked
    #[On('glofox-verified')]
    public function refreshTabs(): void
    {
        $this->loadVerificationStatus();
    }

    private function loadVerificationStatus(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->isGlofoxVerified = false;
            return;
        }

        $clubs = app(ClubGlofoxStatusService::class)
            ->getForUser($user);

        // Disable tabs if ANY club is not verified
        $this->isGlofoxVerified = collect($clubs)
            ->every(fn ($club) => $club['is_verified'] === true);
    }

    public function getPaymentTabs(): array
    {
        return [
            [
                'path' => 'stripe/onboarding',
                'name' => __('stripe.onboarding'),
                'always_enabled' => true,
            ],
            ['path' => 'stripe/transactions', 'name' => __('stripe.transactions')],
            ['path' => 'stripe/products', 'name' => __('stripe.products')],
            ['path' => 'stripe/payouts', 'name' => __('stripe.payouts')],
            ['path' => 'stripe/invoicing', 'name' => __('stripe.invoicing')],
            ['path' => 'stripe/discount', 'name' => __('stripe.discount')],
            ['path' => 'stripe/settings', 'name' => __('stripe.settings')],
        ];
    }

    public function isActive(string $path): bool
    {
        return request()->is($path) || request()->is($path . '/*');
    }

    public function render()
    {
        return view('livewire.payment-tab');
    }
}


