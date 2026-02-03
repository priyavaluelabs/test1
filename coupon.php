@component('emails.layouts.template', [
    'previewText' => __('mail.customer_purchase.preview_text_short')
])

@php
    $headingStyle   = 'color:#8B1E2D;font-weight:600;font-size:16px;margin-bottom:16px;';
    $paragraphStyle = 'margin-bottom:16px;';
    $detailStyle    = 'margin:10px 0;';
    $mutedText      = 'color:#4F4F4F;';
@endphp

<table class="module" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
    <tbody>
        <tr>
            <td bgcolor="white" style="padding:10px;">
                <div>
                    {{-- Heading --}}
                    <p style="{{ $headingStyle }}">
                        {{ __('mail.customer_purchase.heading') }}
                    </p>

                    {{-- Greeting --}}
                    <p style="{{ $paragraphStyle }}">
                        {{ __('mail.customer_purchase.greeting', ['name' => $customerName]) }}
                    </p>

                    {{-- Thank you --}}
                    <p style="{{ $paragraphStyle }}">
                        {{ __('mail.customer_purchase.thank_you') }}
                    </p>

                    {{-- Order Details --}}
                    <div style="margin-bottom:16px;">
                        <strong style="display:block;margin-bottom:16px;">
                            {{ __('mail.customer_purchase.order_details_title') }}
                        </strong>

                        <div style="{{ $mutedText }}padding-left:25px;">
                            <p style="{{ $detailStyle }}">{{ $productName }}</p>
                            <p style="{{ $detailStyle }}">{{ $trainerName }}</p>
                            <p style="{{ $detailStyle }}">{{ $amount }}</p>
                            <p style="{{ $detailStyle }}">{{ ucfirst($paymentMethod) }}</p>
                            <p style="{{ $detailStyle }}">
                                {{ $purchasedAt->format('F jS Y, h:i A') }}
                            </p>
                        </div>
                    </div>

                    <p style="padding-bottom:8px;">
                        {{ __('mail.thank_you') }}
                    </p>
                </div>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="padding:0 10px 10px;">
                <p style="{{ $paragraphStyle }}">
                    {{ __('mail.customer_purchase.automated_note', ['trainer' => $trainerName]) }}
                </p>
            </td>
        </tr>
    </tbody>
</table>

@endcomponent
