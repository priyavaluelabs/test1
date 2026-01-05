 <div id="stripe-tax-registration-container"
                    data-settings="{{ json_encode([
                        'publishableKey' => config('services.stripe.key'),
                        'clientSecret' => $taxRegistrationsClientSecret,
                        'type' => $tax_registration_type,
                        'containerId' => 'stripe-tax-registration-container',
                        'loaderId' => 'stripe-tax-loader',
                    ]) }}">
                </div>