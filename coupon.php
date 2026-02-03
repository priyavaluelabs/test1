@component('emails.layouts.template', [
    'previewText' => __('mail.customer_purchase.preview_text_short')
])
    <table class="module" role="module" data-type="text" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
        <tbody>
            <tr>
                <td bgcolor="white">
                    <div>

                        {{-- Heading --}}
                        <p style="color: #8B1E2D; font-weight: 600; font-size:16px; margin-bottom:16px;">
                            {{ __('mail.customer_purchase.heading') }}
                        </p>

                        {{-- Greeting and Thank You --}}
                        @foreach([
                            __('mail.customer_purchase.greeting', ['name' => $customerName]),
                            __('mail.customer_purchase.thank_you')
                        ] as $paragraph)
                            <p style="margin-bottom:16px;">{{ $paragraph }}</p>
                        @endforeach

                        {{-- Order Details --}}
                        <div style="margin-bottom:16px;">
                            <strong style="display:block; margin-bottom:16px;">
                                {{ __('mail.customer_purchase.order_details_title') }}
                            </strong>
                            <div class="purchase-summary" style="color: #888888; padding-left:25px;">
                                @foreach([
                                    $productName,
                                    $trainerName,
                                    $amount,
                                    ucfirst($paymentMethod),
                                    $purchasedAt->format('F jS Y, h:i A')
                                ] as $detail)
                                    <p style="margin:10px 0;">{{ $detail }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            {{-- Footer --}}
            <tr>
                <td>
                    <div>
                        <p style="margin-bottom:16px;">
                            {{ __('mail.customer_purchase.automated_note', ['trainer' => $trainerName]) }}
                        </p>
                        <p>{{ __('mail.thank_you') }}</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
@endcomponent
