<x-filament-panels::page>
    <div class="filament-tables-container 
        rounded-xl border
        border-gray-300
        bg-white shadow-sm
        ">
        <livewire:payment-tab />
    </div>
    @if (! $stripeAvailable)
        <x-stripe.configuration-error :stripeErrorMessage="$stripeErrorMessage"/>
    @else
        @vite('resources/js/stripe-dashboard.js')
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
            <div class="p-6 space-y-4">
                 @if (! $showOnboarding)
                    <x-stripe.trainer-verification :user="$user"
                        :clubsWithGlofoxStatus="$clubsWithGlofoxStatus"
                        :clubNotVerifiedMessage="$clubNotVerifiedMessage"
                        :isUserGlofoxFullyVerified="$isUserGlofoxFullyVerified"
                    />
                @else
                    @if ($stripeStatus && empty($currentlyDue))
                        @php($color = $stripeStatus->uiColors())

                        <div class="p-4 rounded-lg {{ $color['bg'] }} {{ $color['border'] }} {{ $color['text'] }}">
                            <strong>{{ $stripeStatus->title() }}</strong><br>
                            {{ $stripeStatus->message() }}
                        </div>
                    @endif
                    @if(! $user->is_onboarded)
                        <!-- Loader -->
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
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
