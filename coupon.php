<x-filament-panels::page>
    <div class="filament-tables-container 
        rounded-xl border
        border-gray-300
        bg-white shadow-sm
        ">
        <x-payment-tab />
    </div>
    @if (! $stripeAvailable)
        <x-stripe.configuration-error :stripeErrorMessage="$stripeErrorMessage"/>
    @else
        @vite('resources/js/stripe-dashboard.js')
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
            <div class="p-6 space-y-6">
                @if (! $showOnboarding)
                    <div id="trainer-verification">
                        <h2 class="text-2xl font-bold leading-9 tracking-wide text-gray-900">
                            Trainer Verification
                        </h2>

                        <p class="text-sm font-normal leading-6 text-gray-500">
                            Let's make sure you're connected.
                        </p>

                        <div class="space-y-5 mt-8">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold leading-5 text-gray-700">
                                    Your Account
                                </span>
                                <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5
                                    text-xs leading-5 text-gray-700">
                                    {{ $user->email }}
                                </span>
                            </div>
                            @foreach ($clubsWithGlofoxStatus as $club)
                                <div class="flex items-center gap-6 rounded-lg bg-gray-100 px-3 w-[280px] h-[60px]">
                                    <span class="px-3 text-sm font-semibold leading-5 text-gray-700">
                                        {{ $club['club_title'] }}
                                    </span>
                                    @if ($club['is_verified'])
                                       
                                    <span
                                        class="inline-flex items-center gap-2
                                            px-2 py-2
                                            rounded-full
                                            border
                                            bg-[#F3F8EE]
                                            border-[#C7D7AE]
                                            text-sm font-semibold
                                            text-[#4D7C0F]">
                                        <span
                                            class="flex items-center justify-center
                                                w-6 h-6
                                                rounded-full
                                                border-2 border-[#4D7C0F]">

                                            <x-heroicon-o-check class="w-3.5 h-3.5 text-[#4D7C0F]" />
                                        </span>
                                        Glofox Verified
                                    </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-2
                                                px-2 py-2
                                                rounded-full
                                                text-sm font-semibold
                                                bg-red-100
                                                text-red-700">

                                            <span
                                                class="flex items-center justify-center
                                                    w-6 h-6
                                                    rounded-full
                                                    border-2 border-red-700">

                                                <x-heroicon-s-x-mark class="w-3.5 h-3.5 text-red-700" />
                                            </span>

                                            Not Verified
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <x-filament::button wire:click="continue" class="mt-4">
                            Verify
                        </x-filament::button>
                        <p class="mt-8 font-inter font-semibold text-sm leading-5 text-[#444444]">
                            Note:
                        </p><b>
                        <p class="text-sm font-normal leading-5 text-[#444444]">
                            We automatically sync your bio and photo from Glofox.
                            If you would like to make updates, please do so in Glofox.
                        </p>
                        <x-filament::button wire:click="continue" class="mt-8 w-[300px] h-[44px]">
                            Continue
                        </x-filament::button>
                    </div>
                @else
                    <div class="onboarding">
                        @if($user->is_onboarded)
                            <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
                                <strong>{{ __('stripe.onboarding_completed') }}</strong><br>
                                {{ __('stripe.onboarding_completed_text') }}
                            </div>
                        @else
                            <div id="onboarding-loader" class="flex items-center justify-center h-full">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600" />
                            </div>
                            <div id="onboarding-container"
                                data-settings="{{ json_encode([
                                    'publishableKey' => $stripePublicKey,
                                    'clientSecret' => $clientSecret,
                                    'type' => $type,
                                    'containerId' => 'onboarding-container',
                                    'loaderId' => 'onboarding-loader',
                                ]) }}">
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>

===


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
                    $alwaysEnabled = $getPaymentTab['always_enabled'] ?? false;
                    $disabled = ! $isGlofoxVerified && ! $alwaysEnabled;
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


