@component('emails.layouts.template', [
    'previewText' => __('mail.trainer_onboarding.preview_text_short')
])


@php
    use App\Filament\Enum\SnapColorPalette;
    use App\Filament\Enum\FlexColorPalette;

    $linkColor = config('app.is_snap_env')
        ? SnapColorPalette::DarkRed->value
        : FlexColorPalette::MidnightBlue->value;

    $logoPath = config('app.is_snap_env')
        ? 'images/snap-fitness-logo.png'
        : 'images/FOD_LOGO_FULL_NAVY.png';
@endphp

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body, p, div {
            font-family: arial, helvetica, sans-serif;
            font-size: 14px;
        }
        body { color: #000000; }
        body a { color: {{ $linkColor }}; text-decoration: none; }
        p { margin: 0; padding: 0; }
        table.wrapper {
            width: 100% !important;
            background-color: #FFFFFF !important;
            table-layout: fixed;
        }
        img.max-width { max-width: 100% !important; }
    </style>
</head>

<body>

    {{-- ================= PREVIEW / PREHEADER TEXT ================= --}}
    <div style="
        display:none !important;
        visibility:hidden;
        mso-hide:all;
        font-size:1px;
        color:#ffffff;
        line-height:1px;
        max-height:0;
        max-width:0;
        opacity:0;
        overflow:hidden;
    ">
        {{ $previewText ?? '' }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>
    {{-- ============================================================ --}}

    <center class="wrapper" data-link-color="{{ $linkColor }}">
        <table cellpadding="0" cellspacing="0" border="0" width="100%" class="wrapper">
            <tr>
                <td align="center">
                    <table width="100%" style="max-width:600px;" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center" style="padding:20px 0;">
                                <img
                                    src="{{ asset($logoPath) }}"
                                    width="180"
                                    style="display:block; width:30%; max-width:180px;"
                                    alt="Logo"
                                >
                            </td>
                        </tr>

                        {{-- Email content --}}
                        <tr>
                            <td>
                                {{ $slot }}
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </center>

</body>
</html>
