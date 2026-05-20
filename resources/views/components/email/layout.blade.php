@props([
    'preheader' => null,
    'title' => null,
    'heading' => null,
])

@php
    $brandName = config('brand.name', 'New Life Campus');
    $brandTagline = config('brand.tagline', 'Campus Move-In Portal');
    $brandMark = config('brand.mark', 'NL');
    $supportEmail = config('brand.support.email');
    $supportPhone = config('brand.support.phone');
    $brandAddress = config('brand.address');
    $websiteUrl = config('brand.website_url') ?: config('app.url');
    $year = now()->year;

    $primary = '#0827be';
    $primaryDark = '#061f98';
    $primaryLight = '#e9edfe';
    $navy = '#040f5c';
    $ink = '#101828';
    $body = '#1f2937';
    $muted = '#6b7280';
    $border = '#e4e7ec';
    $surface = '#ffffff';
    $background = '#f4f5fa';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $title ?? $brandName }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, p, a, h1, h2, h3, h4 { font-family: 'Segoe UI', Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
        body, table, td, p, a, h1, h2, h3, h4 {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a { color: {{ $primary }}; text-decoration: none; }
        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; padding: 0 16px !important; }
            .card { padding: 24px !important; border-radius: 12px !important; }
            h1.email-heading { font-size: 22px !important; line-height: 28px !important; }
            .btn a { display: block !important; width: 100% !important; box-sizing: border-box !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{{ $background }};font-family:'Outfit',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;color:{{ $body }};">
    @if (!empty($preheader))
        <div style="display:none;font-size:1px;color:{{ $background }};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
            {{ $preheader }}
        </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $background }};">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="container" style="width:600px;max-width:600px;">
                    {{-- Header / Logo --}}
                    <tr>
                        <td style="padding:0 0 24px 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="left" valign="middle" style="vertical-align:middle;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td valign="middle" style="vertical-align:middle;width:44px;height:44px;background-color:{{ $primary }};border-radius:10px;text-align:center;color:#ffffff;font-family:'Outfit',Arial,sans-serif;font-size:18px;font-weight:700;letter-spacing:1px;line-height:44px;">
                                                    {{ $brandMark }}
                                                </td>
                                                <td style="width:14px;"></td>
                                                <td valign="middle" style="vertical-align:middle;">
                                                    <div style="font-family:'Outfit',Arial,sans-serif;font-size:18px;font-weight:700;color:{{ $navy }};line-height:1.1;">{{ $brandName }}</div>
                                                    <div style="font-family:'Outfit',Arial,sans-serif;font-size:12px;font-weight:500;color:{{ $muted }};letter-spacing:0.4px;text-transform:uppercase;margin-top:2px;">{{ $brandTagline }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $surface }};border:1px solid {{ $border }};border-radius:16px;box-shadow:0 1px 3px rgba(16,24,40,0.04);">
                                <tr>
                                    <td class="card" style="padding:40px;">
                                        @if (!empty($heading))
                                            <h1 class="email-heading" style="margin:0 0 16px 0;font-family:'Outfit',Arial,sans-serif;font-size:26px;line-height:32px;font-weight:700;color:{{ $ink }};">
                                                {{ $heading }}
                                            </h1>
                                        @endif

                                        <div style="font-family:'Outfit',Arial,sans-serif;font-size:15px;line-height:24px;color:{{ $body }};">
                                            {{ $slot }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:28px 8px 0 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="font-family:'Outfit',Arial,sans-serif;font-size:13px;line-height:20px;color:{{ $muted }};">
                                        <div style="color:{{ $navy }};font-weight:600;margin-bottom:4px;">{{ $brandName }}</div>
                                        <div>{{ $brandTagline }}</div>
                                        @if ($brandAddress)
                                            <div style="margin-top:6px;">{{ $brandAddress }}</div>
                                        @endif
                                        <div style="margin-top:12px;">
                                            @if ($supportEmail)
                                                Questions? <a href="mailto:{{ $supportEmail }}" style="color:{{ $primary }};text-decoration:none;font-weight:500;">{{ $supportEmail }}</a>
                                            @endif
                                            @if ($supportEmail && $supportPhone)
                                                &nbsp;·&nbsp;
                                            @endif
                                            @if ($supportPhone)
                                                <a href="tel:{{ $supportPhone }}" style="color:{{ $primary }};text-decoration:none;font-weight:500;">{{ $supportPhone }}</a>
                                            @endif
                                        </div>
                                        <div style="margin-top:16px;color:{{ $muted }};font-size:12px;">
                                            © {{ $year }} {{ $brandName }}. All rights reserved.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
