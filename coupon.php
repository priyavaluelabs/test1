@component('emails.layouts.template')
    <table class="module" role="module" data-type="text" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
        <tbody>
            <tr>
                <td bgcolor="white" style="padding:10px;">
                    <div>
                        <p style="color: #8B1E2D; font-weight: 600; padding-bottom: 16px;">
                            {{ __('mail.trainer_onboarding.preview_text') }}
                        </p>
                        @foreach([
                            __('mail.trainer_onboarding.intro', ['name' => $user->name]),
                            __('mail.trainer_onboarding.intro1'),
                            __('mail.trainer_onboarding.setup_info'),
                            __('mail.trainer_onboarding.manager_help'),
                            __('mail.thank_you'),
                        ] as $paragraph)
                            <p style="padding-bottom: 16px;">
                                {{ $paragraph }}
                            </p>
                        @endforeach
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:0 10px 10px 10px;">
                    <div>
                        <p style="padding-bottom: 16px; font-style: italic">
                            {{ __('mail.trainer_onboarding.footer') }}
                        </p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
@endcomponent
