@component('emails.layouts.template', [
    'previewText' => __('mail.customer_purchase.preview_text_short')
])
    <table class="module" role="module" data-type="text" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
        <tbody>
            <tr>
                <td bgcolor="white">
                    <div>
                        <p style="color: #8B1E2D; font-weight: 600; font-size:16px; padding-bottom: 16px;">
                            {{ __('mail.customer_purchase.heading') }}
                        </p>

                        @foreach([
                            __('mail.customer_purchase.greeting', ['name' => $customerName]),
                            __('mail.customer_purchase.thank_you'),
                        ] as $paragraph)
                            <p style="padding-bottom: 16px;">
                                {{ $paragraph }}
                            </p>
                        @endforeach

                        <div style="padding-bottom: 16px;">
                            <strong style="padding-bottom:16px;">{{ __('mail.customer_purchase.order_details_title') }}</strong>
                            <div class="purchase-summary" style="color: #888888; padding-left:25px;">
                                <p style="padding-bottom:10px; padding-top:10px;">{{ $productName }}</p>
                                <p style="padding-bottom:10px;">{{ $trainerName }}<p>
                                <p style="padding-bottom:10px;">{{ $amount }}<p>
                                <p style="padding-bottom:10px;">{{ ucfirst($paymentMethod) }}<p>
                                <p>{{ $purchasedAt->format('F jS Y, h:i A') }}<p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div>
                        <p style="padding-bottom: 16px;">
                            {{ __('mail.customer_purchase.automated_note', ['trainer' => $trainerName]) }}
                        </p>
                        <p style="padding-bottom: 8px;">
                            {{ __('mail.thank_you') }}
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
@endcomponent
