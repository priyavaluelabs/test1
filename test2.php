<x-filament-panels::page>
    <div class="filament-tables-container 
        rounded-xl border
        border-gray-300
        bg-white shadow-sm
        ">
        <x-payment-tab />
    </div>
    <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 " wire:ignore>
        <div class="tax">
            <!-- Tax Settings -->
            <div class="p-6 space-y-4">
                <div class="font-semibold text-[20px] leading-[28px] tracking-[-0.5px] align-middle mb-2">
                    {{ __('stripe.tax_settings') }}
                </div>
                <div id="stripe-tax-loader" class="flex items-center justify-center h-full">
                    <x-filament::loading-indicator class="h-12 w-12 text-primary-600" />
                </div>
                <div id="stripe-tax-setting-container"
                    data-settings="{{ json_encode([
                        'publishableKey' => config('services.stripe.key'),
                        'clientSecret' => $taxSettingsClientSecret,
                        'type' => $tax_setting_type,
                        'containerId' => 'stripe-tax-setting-container',
                        'loaderId' => 'stripe-tax-loader',
                    ]) }}">
                </div>
            </div>

            <!-- Tax Registrations -->
            <div class="p-6 space-y-4">
                <div class="font-semibold text-[20px] leading-[28px] tracking-[-0.5px] align-middle mb-2">
                    {{ __('stripe.tax_registrations') }}
                </div>
                <div id="stripe-tax-registration-container"
                    data-settings="{{ json_encode([
                        'publishableKey' => config('services.stripe.key'),
                        'clientSecret' => $taxRegistrationsClientSecret,
                        'type' => $tax_registration_type,
                        'containerId' => 'stripe-tax-registration-container',
                        'loaderId' => 'stripe-tax-loader',
                    ]) }}">
                </div>
            </div>

        </div>
    </div>
    <x-stripe.terms-and-condition />
</x-filament-panels::page>
@vite(['resources/js/stripe-dashboard.js'])
