@component('emails.layouts.template', [
    'previewText' => __('mail.customer_purchase.preview_text_short')
])
@php
    $headingStyle = 'color:#8B1E2D;font-weight:600;font-size:18px;margin-bottom:16px;';
    $paragraph    = 'margin-bottom:16px;';
    $orderText    = 'margin:10px 0;color:#4F4F4F;';
@endphp

<table class="module" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
    <tbody>
        <tr>
            <td bgcolor="white">
                <div>
                    <p style="{{ $headingStyle }}">
                        {{ __('mail.trainer_purchase.heading') }}
                    </p>

                    <p style="{{ $paragraph }}">
                        <strong>
                            {{ __('mail.trainer_purchase.greeting', ['name' => $trainerName]) }}
                        </strong>
                    </p>

                    <p style="{{ $paragraph }}">
                        {{ __('mail.trainer_purchase.intro') }}
                    </p>

                    {{-- Order Details --}}
                    <div style="padding:0 0 16px 25px;">
                        <p style="{{ $orderText }}">
                            {{ __('mail.trainer_purchase.order_details.client') }}
                            <strong>{{ $clientName }}</strong>
                        </p>

                        <p style="{{ $orderText }}">
                            {{ __('mail.trainer_purchase.order_details.purchased') }}
                            {{ $productName }}
                        </p>

                        <p style="{{ $orderText }}">
                            {{ __('mail.trainer_purchase.order_details.charged') }}
                            {{ $amount }}
                        </p>

                        <p style="{{ $orderText }}">
                            {{ __('mail.trainer_purchase.order_details.date') }}
                            {{ $purchasedAt->format('M d Y, h:i A') }}
                        </p>
                    </div>

                    {{-- Booking Info --}}
                    <p style="{{ $paragraph }}">
                        {{ __('mail.trainer_purchase.booking_intro') }}
                    </p>

                    <ul style="padding-left:40px;margin-bottom:16px;">
                        <li><strong>{{ __('mail.trainer_purchase.booking_points.snap_app') }}</strong></li>
                        <li><strong>{{ __('mail.trainer_purchase.booking_points.glofox_web') }}</strong></li>
                        <li><strong>{{ __('mail.trainer_purchase.booking_points.glofox_app') }}</strong></li>
                    </ul>

                    <p style="padding-bottom:8px;">
                        {{ __('mail.thank_you') }}
                    </p>
                </div>
            </td>
        </tr>

        {{-- Footer Note --}}
        <tr>
            <td style="padding:0 10px 10px;">
                <p style="padding-bottom:16px;font-style:italic;">
                    {{ __('mail.trainer_purchase.automated_note', ['client' => $clientName]) }}
                </p>
            </td>
        </tr>
    </tbody>
</table>
@endcomponent
