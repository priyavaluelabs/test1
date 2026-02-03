@component('emails.layouts.template', [
    'previewText' => __('mail.customer_purchase.preview_text_short')
])
    <table class="module" role="module" data-type="text" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
        <tbody>
            <tr>
                <td bgcolor="white">
                    <div>
                        <p style="color: #8B1E2D; font-weight: 600; font-size:18px; margin-bottom:16px;">
                            {{ __('mail.trainer_purchase.heading') }}
                        </p>
                        <p style="margin-bottom: 16px;">
                           {{ __('mail.trainer_purchase.greeting', ['name' => $trainerName]) }}
                        </p>
                        <p style="margin-bottom: 16px;">
                            {{ __('mail.trainer_purchase.intro') }}
                        </p>
                        <div class="order-box" style="padding-bottom: 16px; color: #4F4F4F; padding-left:25px;">
                            <p style="margin:10px 0;">{{ __('mail.trainer_purchase.order_details.client') }} {{ $clientName }}</p>
                            <p style="margin:10px 0;">{{ __('mail.trainer_purchase.order_details.purchased') }} {{ $productName }}</p>
                            <p style="margin:10px 0;">{{ __('mail.trainer_purchase.order_details.charged') }} {{ $amount }}</p>
                            <p style="margin:10px 0;">{{ __('mail.trainer_purchase.order_details.date') }} {{ $purchasedAt->format('M d Y, h:i A') }}</p>
                        </div>
                        <div style="padding-bottom: 16px;">
                            <p>
                                {{ __('mail.trainer_purchase.booking_intro') }}
                            </p>
                            <ul style="padding-left: 40px;">
                                <li>{{ __('mail.trainer_purchase.booking_points.snap_app') }}</li>
                                <li>{{ __('mail.trainer_purchase.booking_points.glofox_web') }}</li>
                                <li>{{ __('mail.trainer_purchase.booking_points.glofox_app') }}</li>
                            </ul>
                        </div>
                        <p style="padding-bottom: 8px;">
                            {{ __('mail.thank_you') }}
                        </p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:0 10px 10px 10px;">
                    <div>
                        <p style="padding-bottom: 16px; font-style: italic">
                            {{ __('mail.trainer_purchase.automated_note', ['client' => $clientName]) }}
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
@endcomponent
