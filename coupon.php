@php
    use App\Filament\Enum\SnapColorPalette;
    use App\Filament\Enum\FlexColorPalette;

    $linkColor = config('app.is_snap_env') ? SnapColorPalette::DarkRed->value : FlexColorPalette::MidnightBlue->value;
    $logoPath = config('app.is_snap_env') ? 'images/snap-fitness-logo.png' : 'images/FOD_LOGO_FULL_NAVY.png';
@endphp
<!DOCTYPE html>
<html data-editor-version="2" class="sg-campaigns" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <style>
        body,
        p,
        div {
            font-family: arial, helvetica, sans-serif;
            font-size: 14px;
        }

        body {
            color: #000000;
        }

        body a {
            color: {{ $linkColor }};
            text-decoration: none;
        }

        p {
            margin: 0;
            padding: 0;
        }

        table.wrapper {
            width: 100% !important;
            background-color: #FFFFFF !important;
            table-layout: fixed;
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        img.max-width {
            max-width: 100% !important;
        }

        .column.of-2 {
            width: 50%;
        }

        .column.of-3 {
            width: 33.333%;
        }

        .column.of-4 {
            width: 25%;
        }

        ul ul ul ul {
            list-style-type: disc !important;
        }

        ol ol {
            list-style-type: lower-roman !important;
        }

        ol ol ol {
            list-style-type: lower-latin !important;
        }

        ol ol ol ol {
            list-style-type: decimal !important;
        }

        @media screen and (max-width:480px) {
            .preheader .rightColumnContent,
            .footer .rightColumnContent {
                text-align: left !important;
            }

            .preheader .rightColumnContent div,
            .preheader .rightColumnContent span,
            .footer .rightColumnContent div,
            .footer .rightColumnContent span {
                text-align: left !important;
            }

            .preheader .rightColumnContent,
            .preheader .leftColumnContent {
                font-size: 80% !important;
                padding: 5px 0;
            }

            table.wrapper-mobile {
                width: 100% !important;
                table-layout: fixed;
            }

            img.max-width {
                height: auto !important;
                max-width: 100% !important;
            }

            a.bulletproof-button {
                display: block !important;
                width: auto !important;
                font-size: 80%;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .columns {
                width: 100% !important;
            }

            .column {
                display: block !important;
                width: 100% !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            .social-icon-column {
                display: inline-block !important;
            }
        }
    </style>
</head>
<body>
    <center class="wrapper" data-link-color="{{ $linkColor }}" data-body-style="font-size:14px; font-family:arial,helvetica,sans-serif; color:#000000; background-color:#FFFFFF;">
        <div class="webkit">
            <table cellpadding="0" cellspacing="0" border="0" width="100%" class="wrapper" bgcolor="#FFFFFF">
                <tr>
                    <td valign="top" bgcolor="#FFFFFF" width="100%">
                        <table width="100%" role="content-container" class="outer" align="center" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="100%">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td>
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px;" align="center">
                                                    <tr>
                                                        <td role="modules-container" style="padding:0px 0px 0px 0px; color:#000000; text-align:left;" bgcolor="#fff" width="100%" align="left">
                                                            <table class="wrapper" role="module" data-type="image" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="font-size:6px; line-height:10px; padding:20px 0px 20px 0px;" valign="top" align="center">
                                                                            <img class="max-width" border="0" style="display:block; color:#000000; text-decoration:none; font-family:Helvetica, arial, sans-serif; font-size:16px; max-width:30% !important; width:30%; height:auto !important;" width="180" alt="" src="{{ asset($logoPath) }}">
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>

                                                            {{ $slot }}

                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </center>
</body>
</html>


@component('emails.layouts.template')
    {{-- Hidden preview text --}}
    <span style="display:none !important; visibility:hidden; mso-hide:all; font-size:1px; color:#ffffff; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ __('mail.trainer_onboarding.preview_text_short') }}
    </span>

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
